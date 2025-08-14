<?php
namespace App\Controllers;

use App\Controllers\BaseController;

class ResultsController extends BaseController
{
    // ===== Helpers =====
    private function csvToSet(?string $csv): array {
        if (!$csv) return [];
        $a = array_map('trim', explode(',', $csv));
        $a = array_filter($a, fn($v)=>$v!=='');
        $a = array_map('intval', $a);
        $a = array_values(array_unique($a));
        sort($a, SORT_NUMERIC);
        return array_flip($a); // set
    }
    private function evalDrawIdForTicket(array $t): int {
        // losowanie docelowe = check + 1
        return (int)$t['next_draw_system_id'] + 1;
    }

    private function calcWinFor(int $gameId, ?int $kA, ?int $kB, int $hitsA, ?int $hitsB): array {

        // Ogólny mechanizm: szukamy reguły wypłaty w Twoich "Wysokościach wygranych".
        // Zakładam, że masz tablicę/DB z regułami (np. table game_payouts).
        // Tu robimy bezpieczny stub: kwoty = 0, factor = null; label to "hits".
        $label = ($kB ? ($hitsA.'/'.$kA.' + '.$hitsB.'/'.$kB) : ($hitsA.'/'.$kA));
        return [
            'amount'  => null,   // PLN (jeśli masz stałe kwoty w DB – tu wstaw logikę)
            'factor'  => null,   // współczynnik (np. EJ)
            'label'   => $label,
            'winner'  => ($hitsA>0) || ($hitsB!==null && $hitsB>0),
            'currency'=> 'PLN',
        ];
    }

    // ===== Akcje =====
    public function index()
    {
        $req   = $this->request;
        $gameId= (int)($req->getGet('game_id') ?? 0);
        $batch = (int)($req->getGet('batch_id') ?? 0);
        $month = trim((string)($req->getGet('month') ?? '')); // 'YYYY-MM' do filtrów

        // listy pomocnicze
        $games = [];
        foreach ((new \App\Models\GameModel())->orderBy('id','ASC')->findAll() as $g) {
            $games[(int)$g['id']] = $g;
        }

        // serie do selecta
        $batches = (new \App\Models\BetBatchModel())
            ->orderBy('id','DESC')
            ->findAll(200);

        // wczytaj detale serii (lewa kolumna) – tylko jedna seria pokazuje kupony
        $tickets = [];
        $leftInfo = null;
        if ($batch) {
            $bb = (new \App\Models\BetBatchModel())->find($batch);
            if ($bb) {
                $leftInfo = $bb;
                $tickets = (new \App\Models\BetTicketModel())
                    ->where('batch_id', $batch)
                    ->orderBy('id','ASC')
                    ->findAll(2000);
            }
        }

        // agregaty tygodniowe i miesięczne (środkowa/prawa kolumna)
        // najpierw dołącz wyniki do losowań
        $drM = new \App\Models\DrawResultModel();
        $brM = new \App\Models\BetResultModel();

        $whereGame = $gameId ? ' AND br.game_id = '.$gameId : '';
        $whereMonth= ($month!=='') ? " AND DATE_FORMAT(dr.draw_date,'%Y-%m') = ".$drM->db->escape($month) : '';

        // TYGODNIOWO: zsumowane kwoty + liczba trafień (per tydzień ISO)
        $weekly = $brM->db->query("
            SELECT 
              YEARWEEK(dr.draw_date, 3) as yrwk,
              MIN(dr.draw_date) as date_from,
              MAX(dr.draw_date) as date_to,
              SUM(COALESCE(br.win_amount,0)) AS win_sum,
              COUNT(*) AS tickets_evaluated
            FROM bet_results br
            JOIN draw_results dr 
              ON dr.game_id = br.game_id AND dr.draw_system_id = br.evaluation_draw_system_id
            WHERE 1=1 {$whereGame} {$whereMonth}
            GROUP BY YEARWEEK(dr.draw_date, 3)
            ORDER BY date_from DESC
            LIMIT 26
        ")->getResultArray();

        // MIESIĘCZNIE
        $monthly = $brM->db->query("
            SELECT 
              DATE_FORMAT(dr.draw_date,'%Y-%m') AS ym,
              MIN(dr.draw_date) as date_from,
              MAX(dr.draw_date) as date_to,
              SUM(COALESCE(br.win_amount,0)) AS win_sum,
              COUNT(*) AS tickets_evaluated
            FROM bet_results br
            JOIN draw_results dr 
              ON dr.game_id = br.game_id AND dr.draw_system_id = br.evaluation_draw_system_id
            WHERE 1=1 {$whereGame} {$whereMonth}
            GROUP BY ym
            ORDER BY ym DESC
            LIMIT 12
        ")->getResultArray();

        // render
        $content = view('results/index', [
            'games'    => $games,
            'batches'  => $batches,
            'tickets'  => $tickets,   // lewa kolumna (detal wybranej serii)
            'leftInfo' => $leftInfo,
            'weekly'   => $weekly,    // środkowa
            'monthly'  => $monthly,   // prawa
            'gameId'   => $gameId,
            'batchId'  => $batch,
            'month'    => $month,
        ]);

        return view('layouts/adminlte', [
            'title'   => 'Wyniki',
            'content' => $content,
        ]);
    }

    public function recalc()
    {
        // PRZELICZ wyniki dla (gry, opcjonalnie serii). Liczymy brakujące rekordy w bet_results.
        $req    = $this->request;
        $gameId = (int)$req->getPost('game_id');
        $batch  = (int)$req->getPost('batch_id');

        $tM = new \App\Models\BetTicketModel();
        $dM = new \App\Models\DrawResultModel();
        $rM = new \App\Models\BetResultModel();

        $qb = $tM->where('game_id', $gameId);
        if ($batch) $qb->where('batch_id', $batch);
        $tickets = $qb->orderBy('id','ASC')->findAll(100000);

        $inserted = 0; $updated = 0;

        foreach ($tickets as $t) {
            $evalId = $this->evalDrawIdForTicket($t);

            // sprawdź, czy mamy już wynik dla (ticket_id, evalId)
            $exists = $rM->where('ticket_id', (int)$t['id'])
                         ->where('evaluation_draw_system_id', $evalId)
                         ->first();

            // pobierz losowanie evalId
            $dr = $dM->where('game_id', (int)$t['game_id'])
                     ->where('draw_system_id', $evalId)
                     ->first();
            if (!$dr) continue; // brak losowania → pomiń (jeszcze nie w bazie)

            // policz trafienia
            $setA = $this->csvToSet($dr['numbers_a'] ?? '');
            $setB = $this->csvToSet($dr['numbers_b'] ?? '');
            $pickA= array_keys($this->csvToSet($t['numbers_a'] ?? ''));
            $pickB= array_keys($this->csvToSet($t['numbers_b'] ?? ''));

            $hitsA = 0;
            foreach ($pickA as $n) { if (isset($setA[$n])) $hitsA++; }
            $hitsB = null;
            if (!empty($pickB)) {
                $hitsB = 0;
                foreach ($pickB as $n) { if (isset($setB[$n])) $hitsB++; }
            }

            // wygrana
            $kA = $t['k_a'] ? (int)$t['k_a'] : null;
            $kB = $t['k_b'] ? (int)$t['k_b'] : null;

            $win = $this->calcWinFor((int)$t['game_id'], $kA, $kB, $hitsA, $hitsB);
            $row = [
                'ticket_id'                => (int)$t['id'],
                'batch_id'                 => (int)$t['batch_id'],
                'game_id'                  => (int)$t['game_id'],
                'strategy_id'              => (int)$t['strategy_id'],
                'is_baseline'              => (int)$t['is_baseline'],
                'next_draw_system_id'      => (int)$t['next_draw_system_id'],
                'evaluation_draw_system_id'=> $evalId,
                'hits_a'                   => $hitsA,
                'hits_b'                   => $hitsB,
                'k_a'                      => $kA,
                'k_b'                      => $kB,
                'win_amount'               => $win['amount'],
                'win_factor'               => $win['factor'],
                'win_currency'             => $win['currency'],
                'prize_label'              => $win['label'],
                'is_winner'                => $win['winner'] ? 1 : 0,
                'created_at'               => date('Y-m-d H:i:s'),
            ];

            if ($exists) { $rM->update((int)$exists['id'], $row); $updated++; }
            else         { $rM->insert($row); $inserted++; }
        }

        return redirect()->to('/wyniki')
            ->with('success', "Policzono wyniki: dodane {$inserted}, zaktualizowane {$updated}.");
    }
}
