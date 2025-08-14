<?php
namespace App\Controllers;

use App\Controllers\BaseController;

class BetsController extends BaseController
{
    // ========= Helpery lokalne =========
    private function explodeNums(?string $csv): array {
        if (!$csv) return [];
        $a = array_map('trim', explode(',', $csv));
        $a = array_filter($a, fn($v)=>$v!=='');
        $a = array_map('intval', $a);
        $a = array_values(array_unique($a));
        sort($a, SORT_NUMERIC);
        return $a;
    }
    private function implodeNums(array $arr): string {
        sort($arr, SORT_NUMERIC);
        return implode(',', $arr);
    }
    private function sampleUnique(array $pool, int $k): array {
        $pool = array_values(array_unique($pool));
        if ($k <= 0) return [];
        if (count($pool) <= $k) {
            sort($pool, SORT_NUMERIC);
            return $pool;
        }
        $keys = array_rand($pool, $k);
        if (!is_array($keys)) $keys = [$keys];
        $out = [];
        foreach ($keys as $i) $out[] = $pool[$i];
        sort($out, SORT_NUMERIC);
        return $out;
    }
    private function rangeForGame(int $gameId, string $which = 'A'): array {
        $g = (new \App\Models\GameModel())->find($gameId);
        if (!$g) return [null, null];
        if ($which === 'A') return [ (int)$g['range_a_min'], (int)$g['range_a_max'] ];
        return [
            isset($g['range_b_min']) ? (int)$g['range_b_min'] : null,
            isset($g['range_b_max']) ? (int)$g['range_b_max'] : null
        ];
    }

    // ========= Akcje =========
    public function index()
    {
        $bbM  = new \App\Models\BetBatchModel();
        $rows = $bbM->orderBy('id','DESC')->findAll(50);

        $games = [];
        foreach ((new \App\Models\GameModel())->orderBy('id','ASC')->findAll() as $g) {
            $games[(int)$g['id']] = $g;
        }

        $content = view('bets/index', [
            'rows'  => $rows,
            'games' => $games,
        ]);

        return view('layouts/adminlte', [
            'title'   => 'Zakłady — serie',
            'content' => $content,
        ]);
    }

    public function create()
    {
        $games = [];
        foreach ((new \App\Models\GameModel())->orderBy('id','ASC')->findAll() as $g) {
            $games[(int)$g['id']] = $g;
        }
        $schemas = [];
        foreach ((new \App\Models\StatSchemaModel())->orderBy('id','ASC')->findAll() as $s) {
            $schemas[(int)$s['id']] = $s;
        }

        $content = view('bets/create', [
            'games'   => $games,
            'schemas' => $schemas,
        ]);

        return view('layouts/adminlte', [
            'title'   => 'Zakłady — nowa seria',
            'content' => $content,
        ]);
    }

    public function store()
    {
        $req       = $this->request;
        $gameId    = (int)$req->getPost('game_id');
        $stype     = trim((string)$req->getPost('stype') ?: 'SIMPLE');
        $schemaId  = (int)$req->getPost('schema_id') ?: null;
        $fromId    = (int)$req->getPost('strategy_id_from') ?: null;
        $toId      = (int)$req->getPost('strategy_id_to')   ?: null;
        $lastN     = (int)$req->getPost('last_n') ?: null;
        $per       = max(1, (int)$req->getPost('per_strategy') ?: 1);
        $withRand = $req->getPost('include_random_baseline') == '1' ? 1 : 0;

        if (!$gameId) {
            return redirect()->back()->withInput()->with('error','Wybierz grę.');
        }

        // JEŚLI formularz trafił tu synchronicznie – przekieruj na bezpieczną ścieżkę (AJAX)
if (!$this->request->isAJAX()) {
    return redirect()->back()
        ->withInput()
        ->with('error', 'Użyj przycisku „Generuj” (wymaga JS) – teraz generowanie odbywa się etapami z paskiem postępu.');
}



        $batchM   = new \App\Models\BetBatchModel();
        $ticketM  = new \App\Models\BetTicketModel();
        $stratM   = new \App\Models\StrategyModel();
        $statResM = new \App\Models\StatResultModel();

        $batchId = $batchM->insert([
            'game_id'                 => $gameId,
            'stype'                   => $stype,
            'schema_id'               => $schemaId,
            'strategy_id_from'        => $fromId,
            'strategy_id_to'          => $toId,
            'last_n'                  => $lastN,
            'per_strategy'            => $per,
            'include_random_baseline' => $withRand,
            'status'                  => 'running',
            'created_at'              => date('Y-m-d H:i:s'),
            'started_at'              => date('Y-m-d H:i:s'),
        ]);

        // strategie do użycia
        $b = $stratM->where('game_id', $gameId)->where('stype', $stype);
        if ($schemaId) $b->where('schema_id', $schemaId);
        if ($fromId)   $b->where('id >=', $fromId);
        if ($toId)     $b->where('id <=', $toId);
        $b->orderBy('id','DESC');
        if ($lastN) $b->limit($lastN);
        $strategies = $b->findAll();

        $totalStrategies = count($strategies);
        $totalTickets    = 0;

        foreach ($strategies as $st) {
            $sid   = (int)$st['id'];
            $srid  = (int)($st['stat_result_id'] ?? 0);
            $next  = (int)($st['next_draw_system_id'] ?? 0);

            // rekomendacje SUM
            $recA = $st['recommend_a_json'] ? (json_decode($st['recommend_a_json'], true) ?: []) : [];
            $recB = $st['recommend_b_json'] ? (json_decode($st['recommend_b_json'], true) ?: []) : [];

            // wynik statystyki -> HOT
            $sr   = $srid ? $statResM->find($srid) : null;
            $hotA = $this->explodeNums($sr['hot_a'] ?? '');
            $hotB = $this->explodeNums($sr['hot_b'] ?? '');

            // COLD = zakres - HOT
            [$aMin,$aMax] = $this->rangeForGame($gameId,'A');
            $universeA    = range((int)$aMin,(int)$aMax);
            $coldA        = array_values(array_diff($universeA,$hotA));

            [$bMin,$bMax] = $this->rangeForGame($gameId,'B');
            $universeB    = ($bMin!==null && $bMax!==null) ? range((int)$bMin,(int)$bMax) : [];
            $coldB        = $universeB ? array_values(array_diff($universeB,$hotB)) : [];

            // normalizacja kluczy K
            $normalizeRec = function(array $rec): array {
                $out=[];
                foreach ($rec as $k=>$rc) {
                    $kk = (int)preg_replace('/\D+/', '', (string)$k);
                    $out[$kk] = [
                        'hot'  => (int)($rc['hot']  ?? 0),
                        'cold' => (int)($rc['cold'] ?? 0),
                    ];
                }
                krsort($out, SORT_NUMERIC);
                return $out;
            };
            $recA = $normalizeRec($recA);
            $recB = $normalizeRec($recB);

            foreach ($recA as $kA => $rcA) {
                $hA = (int)$rcA['hot']; $cA = (int)$rcA['cold'];
                if ($kA <= 0 || ($hA + $cA) != $kA) continue;

                // B (jeśli gra ma pulę B)
                $kB = null; $hB = null; $cB = null;
                if (!empty($universeB) && !empty($recB)) {
                    $firstKey = (int)array_key_first($recB);
                    if ($firstKey) {
                        $kB = $firstKey;
                        $hB = (int)($recB[$kB]['hot']  ?? 0);
                        $cB = (int)($recB[$kB]['cold'] ?? 0);
                        if ($hB + $cB !== $kB) { $kB = null; $hB = $cB = null; }
                    }
                }

                for ($i=0; $i<$per; $i++) {
                    // --- strategiczny (HOT/COLD) ---
                    $pickHotA  = $this->sampleUnique($hotA,  min($hA, count($hotA)));
                    $pickColdA = $this->sampleUnique(
                        array_values(array_diff($coldA, $pickHotA)),
                        max(0, $kA - count($pickHotA))
                    );
                    $numsA = array_values(array_unique(array_merge($pickHotA, $pickColdA)));
                    sort($numsA, SORT_NUMERIC);

                    $numsB = null;
                    if ($kB) {
                        $pickHotB  = $this->sampleUnique($hotB,  min($hB, count($hotB)));
                        $pickColdB = $this->sampleUnique(
                            array_values(array_diff($coldB, $pickHotB)),
                            max(0, $kB - count($pickHotB))
                        );
                        $nB = array_values(array_unique(array_merge($pickHotB, $pickColdB)));
                        sort($nB, SORT_NUMERIC);
                        $numsB = $nB;
                    }

                    $ticketM->insert([
                        'batch_id'            => $batchId,
                        'game_id'             => $gameId,
                        'strategy_id'         => $sid,
                        'k_a'                 => $kA,
                        'k_b'                 => $kB,
                        'hot_count_a'         => $hA,
                        'cold_count_a'        => $cA,
                        'hot_count_b'         => $hB,
                        'cold_count_b'        => $cB,
                        'is_baseline'         => 0,
                        'numbers_a'           => $this->implodeNums($numsA),
                        'numbers_b'           => $numsB ? $this->implodeNums($numsB) : null,
                        'next_draw_system_id' => $next,
                        'created_at'          => date('Y-m-d H:i:s'),
                    ]);
                    $totalTickets++;

                    if ($withRand) {
                        // --- baseline (losowy) ---
                        $randA = $this->sampleUnique($universeA, $kA);
                        $randB = $kB ? $this->sampleUnique($universeB, $kB) : null;

                        $ticketM->insert([
                            'batch_id'            => $batchId,
                            'game_id'             => $gameId,
                            'strategy_id'         => $sid,
                            'k_a'                 => $kA,
                            'k_b'                 => $kB,
                            'hot_count_a'         => null,
                            'cold_count_a'        => null,
                            'hot_count_b'         => null,
                            'cold_count_b'        => null,
                            'is_baseline'         => 1,
                            'numbers_a'           => $this->implodeNums($randA),
                            'numbers_b'           => $randB ? $this->implodeNums($randB) : null,
                            'next_draw_system_id' => $next,
                            'created_at'          => date('Y-m-d H:i:s'),
                        ]);
                        $totalTickets++;
                    }
                }
            }
        }

        $batchM->update($batchId, [
            'status'           => 'done',
            'finished_at'      => date('Y-m-d H:i:s'),
            'total_strategies' => $totalStrategies,
            'total_tickets'    => $totalTickets,
        ]);

        return redirect()->to('/zaklady/seria/'.$batchId)
            ->with('success', "Utworzono serię #$batchId. Strategie: $totalStrategies, kupony: $totalTickets.");
    }

    public function show(int $batchId)
    {
        $bb = (new \App\Models\BetBatchModel())->find($batchId);
        if (!$bb) {
            return redirect()->to('/zaklady')->with('error','Nie znaleziono serii.');
        }

        $tickets = (new \App\Models\BetTicketModel())
            ->where('batch_id', $batchId)
            ->orderBy('id','ASC')
            ->findAll(5000);

        $game = (new \App\Models\GameModel())->find((int)$bb['game_id']);

        $content = view('bets/show', [
            'batch'   => $bb,
            'game'    => $game,
            'tickets' => $tickets,
        ]);

        return view('layouts/adminlte', [
            'title'   => 'Zakłady — seria #'.$batchId,
            'content' => $content,
        ]);
    }

public function start()
{
    try {
        $req       = $this->request;
        $gameId    = (int)$req->getPost('game_id');
        $stype     = trim((string)$req->getPost('stype') ?: 'SIMPLE');
        $schemaId  = (int)$req->getPost('schema_id') ?: null;
        $fromId    = (int)$req->getPost('strategy_id_from') ?: null;
        $toId      = (int)$req->getPost('strategy_id_to')   ?: null;
        $lastN     = (int)$req->getPost('last_n') ?: null;
        $per       = max(1, (int)$req->getPost('per_strategy') ?: 1);
        $withRand  = $req->getPost('include_random_baseline') == '1' ? 1 : 0;

        if (!$gameId) return $this->response->setJSON(['ok'=>false,'msg'=>'Wybierz grę.']);

        $stratM = new \App\Models\StrategyModel();

        // Zbuduj ten sam filtr co w store()
        $qb = $stratM->where('game_id', $gameId)->where('stype', $stype);
        if ($schemaId) $qb->where('schema_id', $schemaId);
        if ($fromId)   $qb->where('id >=', $fromId);
        if ($toId)     $qb->where('id <=', $toId);
        $qb->orderBy('id','DESC');

        // Ile łącznie strategii?
        $totalPool = $qb->countAllResults(false); // bez resetu buildera
        $total     = $lastN ? min($totalPool, $lastN) : $totalPool;

        // Utwórz serię (jeszcze nic nie generujemy)
        $batchM = new \App\Models\BetBatchModel();
        $batchId = $batchM->insert([
            'game_id'                 => $gameId,
            'stype'                   => $stype,
            'schema_id'               => $schemaId,
            'strategy_id_from'        => $fromId,
            'strategy_id_to'          => $toId,
            'last_n'                  => $lastN,
            'per_strategy'            => $per,
            'include_random_baseline' => $withRand,
            'status'                  => 'running',
            'total_strategies'        => $total,
            'processed_strategies'    => 0,
            'processed_tickets'       => 0,
            'created_at'              => date('Y-m-d H:i:s'),
            'started_at'              => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON(['ok'=>true,'batchId'=>$batchId,'total'=>$total]);
    } catch (\Throwable $e) {
        log_message('error', 'bets.start: '.$e->getMessage());
        return $this->response->setJSON(['ok'=>false,'msg'=>'Błąd startu: '.$e->getMessage()]);
    }
}

public function step(int $batchId)
{
    $limit = (int)($this->request->getGet('limit') ?? 50);
    if ($limit < 1) $limit = 50;

    $batchM   = new \App\Models\BetBatchModel();
    $ticketM  = new \App\Models\BetTicketModel();
    $stratM   = new \App\Models\StrategyModel();
    $statResM = new \App\Models\StatResultModel();

    $bb = $batchM->find($batchId);
    if (!$bb) return $this->response->setJSON(['ok'=>false,'msg'=>'Brak serii.']);
    if ($bb['status'] !== 'running') {
        return $this->response->setJSON(['ok'=>true,'idle'=>true,'status'=>$bb['status'],'msg'=>'Seria nie jest w stanie "running".']);
    }

    $gameId   = (int)$bb['game_id'];
    $stype    = (string)$bb['stype'];
    $schemaId = (int)($bb['schema_id'] ?? 0);
    $fromId   = (int)($bb['strategy_id_from'] ?? 0);
    $toId     = (int)($bb['strategy_id_to'] ?? 0);
    $lastN    = (int)($bb['last_n'] ?? 0);
    $per      = (int)$bb['per_strategy'];
    $withRand = (int)$bb['include_random_baseline'];

    $processed = (int)$bb['processed_strategies'];
    $total     = (int)$bb['total_strategies'];
    $ticketsAcc= 0;

    try {
        // Zbuduj zapytanie jak w start(), ale z offsetem = processed
        $qb = $stratM->where('game_id', $gameId)->where('stype', $stype);
        if ($schemaId) $qb->where('schema_id', $schemaId);
        if ($fromId)   $qb->where('id >=', $fromId);
        if ($toId)     $qb->where('id <=', $toId);
        $qb->orderBy('id','DESC');

        // offset w ramach "limit lastN" – jeśli lastN>0, offset nie może przekroczyć lastN
        $offset = $processed;
        if ($lastN) {
            $qb->limit($lastN); // zawęź pulę do N najnowszych
            // Model::findAll($limit, $offset) dodatkowo przytnie
        }

        $strategies = $qb->findAll($limit, $offset);

        // pomocnicze funkcje z kontrolera
        $explodeNums = fn(?string $csv) => $this->explodeNums($csv);
        $implodeNums = fn(array $a)     => $this->implodeNums($a);
        [$aMin,$aMax] = $this->rangeForGame($gameId,'A');
        [$bMin,$bMax] = $this->rangeForGame($gameId,'B');
        $universeA = range((int)$aMin,(int)$aMax);
        $universeB = ($bMin!==null && $bMax!==null) ? range((int)$bMin,(int)$bMax) : [];

        $made = 0;
        foreach ($strategies as $st) {
            $sid   = (int)$st['id'];
            $srid  = (int)($st['stat_result_id'] ?? 0);
            $next  = (int)($st['next_draw_system_id'] ?? 0);

            $recA = $st['recommend_a_json'] ? (json_decode($st['recommend_a_json'], true) ?: []) : [];
            $recB = $st['recommend_b_json'] ? (json_decode($st['recommend_b_json'], true) ?: []) : [];

            $sr   = $srid ? $statResM->find($srid) : null;
            $hotA = $explodeNums($sr['hot_a'] ?? '');
            $hotB = $explodeNums($sr['hot_b'] ?? '');
            $coldA= array_values(array_diff($universeA, $hotA));
            $coldB= $universeB ? array_values(array_diff($universeB, $hotB)) : [];

            $normalizeRec = function(array $rec): array {
                $out=[];
                foreach ($rec as $k=>$rc) {
                    $kk = (int)preg_replace('/\D+/', '', (string)$k);
                    $out[$kk] = ['hot'=>(int)($rc['hot']??0), 'cold'=>(int)($rc['cold']??0)];
                }
                krsort($out, SORT_NUMERIC);
                return $out;
            };
            $recA = $normalizeRec($recA);
            $recB = $normalizeRec($recB);

            foreach ($recA as $kA=>$rcA) {
                $hA=(int)$rcA['hot']; $cA=(int)$rcA['cold'];
                if ($kA<=0 || $hA+$cA!=$kA) continue;

                $kB=$hB=$cB=null;
                if (!empty($universeB) && !empty($recB)) {
                    $firstKey = (int)array_key_first($recB);
                    if ($firstKey) {
                        $kB=$firstKey; $hB=(int)($recB[$kB]['hot']??0); $cB=(int)($recB[$kB]['cold']??0);
                        if ($hB+$cB!==$kB) { $kB=$hB=$cB=null; }
                    }
                }

                for ($i=0; $i<$per; $i++) {
                    // strategiczny
                    $pickHotA  = $this->sampleUnique($hotA,  min($hA, count($hotA)));
                    $pickColdA = $this->sampleUnique(array_values(array_diff($coldA, $pickHotA)), max(0, $kA-count($pickHotA)));
                    $numsA = array_values(array_unique(array_merge($pickHotA, $pickColdA)));
                    sort($numsA,SORT_NUMERIC);

                    $numsB = null;
                    if ($kB) {
                        $pickHotB  = $this->sampleUnique($hotB,  min($hB, count($hotB)));
                        $pickColdB = $this->sampleUnique(array_values(array_diff($coldB, $pickHotB)), max(0, $kB-count($pickHotB)));
                        $nB = array_values(array_unique(array_merge($pickHotB, $pickColdB)));
                        sort($nB,SORT_NUMERIC);
                        $numsB = $nB;
                    }

                    $ticketM->insert([
                        'batch_id'            => $batchId,
                        'game_id'             => $gameId,
                        'strategy_id'         => $sid,
                        'k_a'                 => $kA,
                        'k_b'                 => $kB,
                        'hot_count_a'         => $hA,
                        'cold_count_a'        => $cA,
                        'hot_count_b'         => $hB,
                        'cold_count_b'        => $cB,
                        'is_baseline'         => 0,
                        'numbers_a'           => $implodeNums($numsA),
                        'numbers_b'           => $numsB ? $implodeNums($numsB) : null,
                        'next_draw_system_id' => $next,
                        'created_at'          => date('Y-m-d H:i:s'),
                    ]);
                    $ticketsAcc++;

                    if ($withRand) {
                        $randA = $this->sampleUnique($universeA, $kA);
                        $randB = $kB ? $this->sampleUnique($universeB, $kB) : null;

                        $ticketM->insert([
                            'batch_id'            => $batchId,
                            'game_id'             => $gameId,
                            'strategy_id'         => $sid,
                            'k_a'                 => $kA,
                            'k_b'                 => $kB,
                            'is_baseline'         => 1,
                            'numbers_a'           => $implodeNums($randA),
                            'numbers_b'           => $randB ? $implodeNums($randB) : null,
                            'next_draw_system_id' => $next,
                            'created_at'          => date('Y-m-d H:i:s'),
                        ]);
                        $ticketsAcc++;
                    }
                }
            }
            $made = count($strategies);
        } // try body

        // aktualizacja progresu
        $processed += $made;
        $done = ($processed >= $total);
        $batchM->update($batchId, [
            'processed_strategies' => $processed,
            'processed_tickets'    => (int)$bb['processed_tickets'] + $ticketsAcc,
            'status'               => $done ? 'done' : 'running',
            'finished_at'          => $done ? date('Y-m-d H:i:s') : $bb['finished_at'],
            'total_tickets'        => (int)$bb['total_tickets'] + $ticketsAcc,
        ]);

        return $this->response->setJSON([
            'ok'       => true,
            'processed'=> $processed,
            'total'    => $total,
            'added'    => $ticketsAcc,
            'status'   => $done ? 'done' : 'running',
        ]);
    } catch (\Throwable $e) {
        $batchM->update($batchId, [
            'status'    => 'error',
            'error_msg' => $e->getMessage(),
        ]);
        log_message('error', 'bets.step: '.$e->getMessage());
        return $this->response->setJSON(['ok'=>false,'msg'=>'Błąd: '.$e->getMessage()]);
    }
}


    
}
