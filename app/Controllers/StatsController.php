<?php
namespace App\Controllers;

use App\Models\GameModel;
use App\Models\DrawResultModel;
use App\Models\StatSchemaModel;
use App\Models\StatResultModel;
use App\Models\StrategyModel;

class StatsController extends BaseController
{

    /**
     * Fallback nazwy schematu na bazie scheme + params_json.
     */

    // ====== UI: LISTA SCHEMATÓW ======
    public function schemasIndex()
    {
        helper('utils');
        $schemas = (new StatSchemaModel())->orderBy('id','DESC')->findAll();
        $games   = index_by_id((new GameModel())->findAll()); // helper niżej
        $content = view('stats/schemas_index', compact('schemas','games'));
        return view('layouts/adminlte', ['title'=>'Statystyki — schematy','content'=>$content]);
    }

    // ====== UI: NOWY SCHEMAT ======
    public function schemaCreateForm()
    {
        $games = (new GameModel())->orderBy('name','ASC')->findAll();
        $content = view('stats/schemas_create', compact('games'));
        return view('layouts/adminlte', ['title'=>'Nowy schemat statystyk','content'=>$content]);
    }

    public function schemaCreate()
    {
        $gameId = (int)$this->request->getPost('game_id');

        // Schemat z POST
$scheme = (string)($data['scheme'] ?? $this->request->getPost('scheme') ?? '');

// Zbierz parametry wg schematu
$params = [];
if ($scheme === 'scheme1' || strtoupper($scheme) === 'S1') {
    $params['x_a'] = (int)$this->request->getPost('x_a');
    $params['y_a'] = (int)$this->request->getPost('y_a');
    $xb = $this->request->getPost('x_b'); $yb = $this->request->getPost('y_b');
    if ($xb !== null && $yb !== null && $xb !== '' && $yb !== '') {
        $params['x_b'] = (int)$xb;
        $params['y_b'] = (int)$yb;
    }
} else { // scheme2
    $params['min_all_a'] = (int)$this->request->getPost('min_all_a');
    $params['top_k_a']   = (int)$this->request->getPost('top_k_a');
    $mb = $this->request->getPost('min_all_b'); $kb = $this->request->getPost('top_k_b');
    if ($mb !== null && $kb !== null && $mb !== '' && $kb !== '') {
        $params['min_all_b'] = (int)$mb;
        $params['top_k_b']   = (int)$kb;
    }
}
$data['params_json'] = json_encode($params, JSON_UNESCAPED_UNICODE);

// Nazwa własna lub fallback
$name = trim((string)$this->request->getPost('name'));
$data['name'] = ($name !== '') ? $name : $this->_schemaAutoNameFromParams($scheme, $params);



        $schemaId = (new StatSchemaModel())->insert([
            'game_id'   => $gameId,
            'scheme'    => $scheme,

            'params_json'=> json_encode($params, JSON_UNESCAPED_UNICODE),
            'status'    => 'idle',
            'first_met_from_draw' => null,
            'current_from_draw'   => $this->firstProcessableFrom($gameId), // pierwszy możliwy „from” (najstarszy mający przynajmniej 1 wcześniejsze)
            'processed_since_first'=> 0,
        ]);

        return redirect()->to('/statystyki')->with('success','Utworzono schemat. Możesz uruchomić obliczenia.');
    }







    // ====== UI: EDYCJA (tylko gdy idle) ======
    public function schemaEdit($id)
    {
        $m = new StatSchemaModel(); $s = $m->find($id);
        
        if (!$s) return redirect()->to('/statystyki')->with('errors',['Nie znaleziono schematu.']);
        
        if ($s['status']!=='idle') {
            return redirect()->to('/statystyki')->with('warning','Schemat już uruchomiony — edycja zablokowana.');
        }

        $game = (new GameModel())->find($s['game_id']);
        $params = json_decode($s['params_json'], true) ?: [];
        
        $content = view('stats/schemas_edit', compact('s','game','params'));
        return view('layouts/adminlte', ['title'=>'Edytuj schemat','content'=>$content]);
    }






    // ====== Sterowanie: start/pauza/usuwanie ======
    public function schemaStart($id)
    {
        $m = new StatSchemaModel();
        $s = $m->find($id);
        if (!$s) return redirect()->to('/statystyki')->with('errors',['Nie znaleziono schematu.']);
        $m->update($id, ['status'=>'running']);
        return redirect()->to('/statystyki')->with('info','Uruchomiono schemat. Przetwarzanie rozpocznie się za chwilę.');
    }

    public function schemaPause($id)
    {
        $m = new StatSchemaModel();
        $s = $m->find($id);
        if (!$s) return redirect()->to('/statystyki')->with('errors',['Nie znaleziono schematu.']);
        $m->update($id, ['status'=>'paused']);
        return redirect()->to('/statystyki')->with('info','Wstrzymano przetwarzanie schematu.');
    }

    public function schemaDelete($id)
    {
        $m = new StatSchemaModel();
        $s = $m->find($id);
        if (!$s) return redirect()->to('/statystyki')->with('errors',['Nie znaleziono schematu.']);

        // usuń powiązane wyniki
        $db = \Config\Database::connect();
        $db->table('stat_results')->where('schema_id', $id)->delete();

        $m->delete($id);
        return redirect()->to('/statystyki')->with('success','Usunięto schemat wraz z wynikami.');
    }

    // ====== Silnik — krok batch (AJAX) ======
    public function schemaStep($id)
    {
        $batch = max(1, (int)($this->request->getGet('batch') ?? 50));

        $schemas = new StatSchemaModel();
        $s = $schemas->find($id);
        if (!$s) return $this->response->setJSON(['ok'=>false,'msg'=>'Brak schematu']);
        if ($s['status'] !== 'running') {
            return $this->response->setJSON(['ok'=>true,'idle'=>true,'msg'=>'Schemat nie jest w stanie "running".']);
        }

        $game = (new GameModel())->find($s['game_id']);
        if (!$game) return $this->response->setJSON(['ok'=>false,'msg'=>'Brak gry.']);

        // Domena gry musi być poprawna
        [$amin,$amax] = $this->domainRange($game['id'],'A');
        if ($amin===null || $amax===null) {
            $schemas->update($id, ['status'=>'paused']);
            return $this->response->setJSON(['ok'=>false,'msg'=>'Błąd: gra nie ma zakresu A.']);
        }

        $done = 0; $saved = 0; $from = $s['current_from_draw'];
        if (!$from) $from = $this->firstProcessableFrom($game['id']);

        for ($i=0; $i<$batch; $i++) {
            if ($from===null) break;

            // policz minimalne okno dla "from" (wstecz)
            $resultId = $this->computeAndSaveForFrom($s, $game, $from);
            if ($resultId) {
                // zapamiętaj pierwszy „początek cyklu”
                if (!$s['first_met_from_draw']) {
                    $s['first_met_from_draw'] = $from;
                }
                $saved++;
            }

            // przejdź do następnego „from”
            $from = $this->nextDrawNo($game['id'], $from);
            $done++;

            if ($from===null) break;
        }

        // Zapis postępu
        $processed = (int)$s['processed_since_first'];
        if ($s['first_met_from_draw']) {
            // jeżeli mamy start, licz dalej
            $processed += $done;
        }
        $schemas->update($id, [
            'current_from_draw'     => $from,
            'processed_since_first' => $processed,
            'first_met_from_draw'   => $s['first_met_from_draw'] ?: null,
            'status'                => ($from===null ? 'done' : 'running'),
        ]);

        return $this->response->setJSON([
            'ok'=>true,
            'saved'=>$saved,
            'done'=>$done,
            'next'=>$from,
            'status'=>($from===null ? 'done' : 'running'),
            'msg'=> $from===null ? 'Zakończono: osiągnięto ostatnie losowanie.' : "Przetworzono batch={$done}, zapisano {$saved} wyników."
        ]);
    }

    // ====================== Silnik: oblicz i zapisz ======================
    private function computeAndSaveForFrom(array $schema, array $game, int $fromNo): ?int
    {
        $scheme = $schema['scheme'];
        $params = json_decode($schema['params_json'], true) ?: [];

        // Pobierz metadane „from”
        $drawM = new DrawResultModel();
        $from = $drawM->where('game_id',$game['id'])->where('draw_system_id',$fromNo)->first();
        if (!$from) return null;

        if ($scheme==='scheme1') {
            $res = $this->findWindow_scheme1($game['id'], $from['draw_date'], $from['draw_time'], $params);
            if (!$res) return null;
            // zapisz wynik
            return $this->saveResultS1_schema($schema['id'], $game['id'], $fromNo, $res, $params);
        } else {
            $res = $this->findWindow_scheme2($game['id'], $from['draw_date'], $from['draw_time'], $params);
            if (!$res) return null;
            return $this->saveResultS2_schema($schema['id'], $game['id'], $fromNo, $res, $params);
        }
    }

    // ===== S1: minimalne okno wstecz (x≥y, wspólne dla A/B) =====
    private function findWindow_scheme1(int $gameId, string $fromDate, string $fromTime, array $p): ?array
    {
        [$amin,$amax] = $this->domainRange($gameId,'A');
        [$bmin,$bmax] = $this->domainRange($gameId,'B');

        $cntA=[]; $cntB=[]; $w=0;
        $offset = 0; $page = 200;

        while (true) {
            $list = $this->queryBackwindowStrict($gameId, $fromDate, $fromTime, $page, $offset);
            if (!$list) break;

            foreach ($list as $row) {
                $w++;
                foreach ($this->explodeNums($row['numbers_a']) as $n) $cntA[$n]=($cntA[$n]??0)+1;
                foreach ($this->explodeNums($row['numbers_b']) as $n) $cntB[$n]=($cntB[$n]??0)+1;

                $okA = $this->metXY($cntA, (int)$p['x_a'], (int)$p['y_a']);
                $okB = ($bmin!==null && (int)$p['x_b']>0 && (int)$p['y_b']>0)
                     ? $this->metXY($cntB, (int)$p['x_b'], (int)$p['y_b'])
                     : true;

                if ($okA && $okB) {
                    $border = $this->oldestDrawNoInWindowStrict($gameId, $fromDate, $fromTime, $w);
                    return ['w'=>$w,'cntA'=>$cntA,'cntB'=>$cntB,'border'=>$border];
                }
            }
            $offset += $page;
        }
        return null;
    }

    // ===== S2: minimalne okno wstecz (all≥y, potem topK) =====
    private function findWindow_scheme2(int $gameId, string $fromDate, string $fromTime, array $p): ?array
    {
        [$amin,$amax] = $this->domainRange($gameId,'A');
        [$bmin,$bmax] = $this->domainRange($gameId,'B');

        $cntA=[]; $cntB=[]; $w=0;
        $offset = 0; $page = 200;

        while (true) {
            $list = $this->queryBackwindowStrict($gameId, $fromDate, $fromTime, $page, $offset);
            if (!$list) break;

            foreach ($list as $row) {
                $w++;
                foreach ($this->explodeNums($row['numbers_a']) as $n) $cntA[$n]=($cntA[$n]??0)+1;
                foreach ($this->explodeNums($row['numbers_b']) as $n) $cntB[$n]=($cntB[$n]??0)+1;

                $okA = $this->allAtLeast($cntA, (int)$p['min_all_a'], $amin, $amax);
                $okB = ($bmin!==null && (int)$p['min_all_b']>0)
                     ? $this->allAtLeast($cntB, (int)$p['min_all_b'], $bmin, $bmax)
                     : true;

                if ($okA && $okB) {
                    $border = $this->oldestDrawNoInWindowStrict($gameId, $fromDate, $fromTime, $w);
                    return ['w'=>$w,'cntA'=>$cntA,'cntB'=>$cntB,'border'=>$border];
                }
            }
            $offset += $page;
        }
        return null;
    }

    private function saveResultS1_schema(int $schemaId, int $gameId, int $fromNo, array $r, array $p): int
    {
        [$amin,$amax] = $this->domainRange($gameId,'A'); [$bmin,$bmax] = $this->domainRange($gameId,'B');

        // wybierz x_a / x_b z liczb mających ≥ y
        $okA = array_filter(range($amin,$amax), fn($n)=>($r['cntA'][$n]??0) >= (int)$p['y_a']);
        $selA = $this->pickTopK(array_intersect_key($r['cntA'], array_flip($okA)), (int)$p['x_a'], $amin, $amax);

        $selB=[]; 
        if ($bmin!==null && (int)$p['x_b']>0 && (int)$p['y_b']>0) {
            $okB = array_filter(range($bmin,$bmax), fn($n)=>($r['cntB'][$n]??0) >= (int)$p['y_b']);
            $selB = $this->pickTopK(array_intersect_key($r['cntB'], array_flip($okB)), (int)$p['x_b'], $bmin, $bmax);
        }

        $res = [
            'schema_id'             => $schemaId,
            'game_id'               => $gameId,
            'scheme'                => 'scheme1',
            'from_draw_system_id'   => $fromNo,
            'met_at_draw_system_id' => $r['border'],
            'window_draws'          => $r['w'],
            'window_draws_a'        => $r['w'],
            'window_draws_b'        => ($bmin!==null ? $r['w'] : null),
            'criteria_a'            => json_encode(['x'=>$p['x_a'],'y'=>$p['y_a']], JSON_UNESCAPED_UNICODE),
            'criteria_b'            => ($bmin!==null ? json_encode(['x'=>$p['x_b'],'y'=>$p['y_b']], JSON_UNESCAPED_UNICODE) : null),
            'numbers_in_a'          => $this->implodeNums($selA),
            'numbers_in_b'          => $this->implodeNums($selB),
            'counts_a'              => json_encode($r['cntA'], JSON_UNESCAPED_UNICODE),
            'counts_b'              => ($bmin!==null ? json_encode($r['cntB'], JSON_UNESCAPED_UNICODE) : null),
            'hot_a'                 => $this->implodeNums($selA),
            'cold_a'                => $this->implodeNums(array_values(array_diff(range($amin,$amax), $selA))),
            'hot_b'                 => $this->implodeNums($selB),
            'cold_b'                => $this->implodeNums($bmin!==null ? array_values(array_diff(range($bmin,$bmax), $selB)) : []),
            'finished_at'           => date('Y-m-d H:i:s'),
        ];
        $id = (new StatResultModel())->insert($res);
$this->generateStrategyForResultId($id);
return $id;
    }

    private function saveResultS2_schema(int $schemaId, int $gameId, int $fromNo, array $r, array $p): int
    {
        [$amin,$amax] = $this->domainRange($gameId,'A'); [$bmin,$bmax] = $this->domainRange($gameId,'B');

        $selA = $this->pickTopK($r['cntA'], (int)$p['top_k_a'], $amin, $amax);
        $selB = ($bmin!==null && (int)$p['top_k_b']>0) ? $this->pickTopK($r['cntB'], (int)$p['top_k_b'], $bmin, $bmax) : [];

        $res = [
            'schema_id'             => $schemaId,
            'game_id'               => $gameId,
            'scheme'                => 'scheme2',
            'from_draw_system_id'   => $fromNo,
            'met_at_draw_system_id' => $r['border'],
            'window_draws'          => $r['w'],
            'window_draws_a'        => $r['w'],
            'window_draws_b'        => ($bmin!==null ? $r['w'] : null),
            'criteria_a'            => json_encode(['min_all'=>$p['min_all_a'],'top_k'=>$p['top_k_a']], JSON_UNESCAPED_UNICODE),
            'criteria_b'            => ($bmin!==null ? json_encode(['min_all'=>$p['min_all_b'],'top_k'=>$p['top_k_b']], JSON_UNESCAPED_UNICODE) : null),
            'numbers_in_a'          => $this->implodeNums($selA),
            'numbers_in_b'          => $this->implodeNums($selB),
            'counts_a'              => json_encode($r['cntA'], JSON_UNESCAPED_UNICODE),
            'counts_b'              => ($bmin!==null ? json_encode($r['cntB'], JSON_UNESCAPED_UNICODE) : null),
            'hot_a'                 => $this->implodeNums($selA),
            'cold_a'                => $this->implodeNums(array_values(array_diff(range($amin,$amax), $selA))),
            'hot_b'                 => $this->implodeNums($selB),
            'cold_b'                => $this->implodeNums($bmin!==null ? array_values(array_diff(range($bmin,$bmax), $selB)) : []),
            'finished_at'           => date('Y-m-d H:i:s'),
        ];
        $id = (new StatResultModel())->insert($res);
$this->generateStrategyForResultId($id);
return $id;
    }






private function schemaParamsFromRow(array $row): array
{
    $p = $row['params_json'] ?? [];
    if (!is_array($p)) {
        $p = json_decode((string)$p, true) ?: [];
    }
    return $p;
}

private function schemaAutoNameFromRow(array $row): string
{
    $scheme = $row['scheme'] ?? '';
    $p = $this->schemaParamsFromRow($row);

    // tolerancja nazw kluczy (snake/camel)
    $xa = $p['x_a'] ?? $p['xA'] ?? null;  $ya = $p['y_a'] ?? $p['yA'] ?? null;
    $xb = $p['x_b'] ?? $p['xB'] ?? null;  $yb = $p['y_b'] ?? $p['yB'] ?? null;
    $ka = $p['k_a'] ?? $p['kA'] ?? null;  $kb = $p['k_b'] ?? $p['kB'] ?? null;

    if ($scheme === 'S1') {
        $a = ($xa!==null && $ya!==null) ? " A: x={$xa}, y={$ya}" : '';
        $b = ($xb!==null && $yb!==null) ? " B: x={$xb}, y={$yb}" : '';
        return trim('S1: x≥y'.$a.$b);
    }
    if ($scheme === 'S2') {
        $a = ($ka!==null) ? " A: k={$ka}" : '';
        $b = ($kb!==null) ? " B: k={$kb}" : '';
        return trim('S2: topK'.$a.$b);
    }
    return $scheme ?: 'Schemat';
}














    // ====================== helpery DB/algorytmy ======================
    private function firstProcessableFrom(int $gameId): ?int
    {
        // pierwszy „from” to najstarsze losowanie, które ma przynajmniej jedno wcześniejsze
        $db = \Config\Database::connect();
        $row = $db->query("
            SELECT t2.draw_system_id AS from_no
            FROM draw_results t1
            JOIN draw_results t2 ON t2.game_id=t1.game_id
            WHERE t1.game_id=? AND (t2.draw_date>t1.draw_date OR (t2.draw_date=t1.draw_date AND t2.draw_time>t1.draw_time))
            ORDER BY t2.draw_date ASC, t2.draw_time ASC
            LIMIT 1
        ", [$gameId])->getFirstRow('array');
        return $row['from_no'] ?? null;
    }

    private function nextDrawNo(int $gameId, int $fromNo): ?int
    {
        $db = \Config\Database::connect();
        $row = $db->query("SELECT draw_system_id FROM draw_results WHERE game_id=? AND draw_system_id>? ORDER BY draw_system_id ASC LIMIT 1",
            [$gameId, $fromNo])->getFirstRow('array');
        return $row['draw_system_id'] ?? null;
    }

    private function queryBackwindowStrict(int $gameId, string $fromDate, string $fromTime, int $limit, int $offset): array
    {
        $m = new DrawResultModel();
        return $m->where('game_id',$gameId)
            ->groupStart()
              ->where('draw_date <', $fromDate)
              ->orGroupStart()
                ->where('draw_date', $fromDate)
                ->where('draw_time <', $fromTime)
              ->groupEnd()
            ->groupEnd()
            ->orderBy('draw_date','DESC')->orderBy('draw_time','DESC')
            ->findAll($limit, $offset);
    }

    private function oldestDrawNoInWindowStrict(int $gameId, string $fromDate, string $fromTime, int $w): ?int
    {
        $rows = $this->queryBackwindowStrict($gameId, $fromDate, $fromTime, 1, $w-1);
        return $rows[0]['draw_system_id'] ?? null;
    }

    private function domainRange(int $gameId, string $pool): array
    {
        $db = \Config\Database::connect();
        $col = $pool==='A' ? ['range_a_min','range_a_max'] : ['range_b_min','range_b_max'];
        $row = $db->table('games')->select($col[0].' AS mn, '.$col[1].' AS mx')->where('id',$gameId)->get()->getFirstRow('array');
        if (!$row || $row['mn']===null || $row['mx']===null) return [null,null];
        return [(int)$row['mn'], (int)$row['mx']];
    }

    private function explodeNums(?string $csv): array
    {
        if (!$csv) return [];
        $arr = array_filter(array_map('trim', explode(',', $csv)), fn($x)=>$x!=='');
        return array_map('intval', $arr);
    }
    private function implodeNums(array $arr): ?string
    {
        $arr = array_values(array_unique(array_map('intval',$arr)));
        return empty($arr) ? null : implode(',', $arr);
    }
    private function metXY(array $counts, int $x, int $y): bool
    {
        if ($x<=0 || $y<=0) return true;
        $ok = 0; foreach ($counts as $c) if ($c >= $y) $ok++;
        return $ok >= $x;
    }
    private function allAtLeast(array $counts, int $y, ?int $min, ?int $max): bool
    {
        if ($min===null || $max===null) return false;
        if ($y<=0) return true;
        for ($n=$min; $n<=$max; $n++) if (($counts[$n] ?? 0) < $y) return false;
        return true;
    }

    

    // <<< DODANE >>>
    private function pickTopK(array $counts, int $k, ?int $min=null, ?int $max=null): array
    {
        if ($k <= 0) return [];

        $items = [];
        if ($min !== null && $max !== null) {
            for ($n = (int)$min; $n <= (int)$max; $n++) {
                $items[] = ['n' => $n, 'c' => (int)($counts[$n] ?? 0)];
            }
        } else {
            foreach ($counts as $n => $c) {
                $items[] = ['n' => (int)$n, 'c' => (int)$c];
            }
        }

        usort($items, function($a, $b) {
            if ($a['c'] === $b['c']) return $a['n'] <=> $b['n'];
            return $b['c'] <=> $a['c'];
        });

        $top = array_slice($items, 0, $k);
        return array_column($top, 'n');
    }



/**
 * Zwraca listę docelowych K (ile liczb typujemy) dla gry/puli.
 * - Lotto: A -> [6]
 * - EuroJackpot: A -> [5], B -> [2]
 * - Multi Multi: A -> [10,9,8] (zgodnie z Twoją preferencją)
 */
private function pickKsForGame(int $gameId, string $pool): array
{
    $row = (new \App\Models\GameModel())->find($gameId);
    if (!$row) return [];

    $slug = $row['slug'] ?? '';
    $min  = ($pool==='A') ? (int)($row['picks_a_min'] ?? 0) : (int)($row['picks_b_min'] ?? 0);
    $max  = ($pool==='A') ? (int)($row['picks_a_max'] ?? 0) : (int)($row['picks_b_max'] ?? 0);

    if ($slug === 'multi-multi' && $pool==='A') {
        return [10,9,8]; // Twoje założenie
    }
    if ($min>0 && $max>0) {
        // domyślnie pełny zakres (np. Lotto 6..6 → [6], EJ B 2..2 → [2])
        $out=[]; for($k=$min;$k<=$max;$k++) $out[]=$k; return $out;
    }
    return [];
}

/**
 * Wyznacz rekomendację HOT/COLD dla zestawu K
 * na podstawie proporcji z rzeczywistego losowania (hits vs drawn).
 * Zwraca mapę { K: {hot:int, cold:int}, ... }.
 */
private function recommendSplits(int $gameId, string $pool, int $hitsHot, int $hitsCold, int $drawn, int $hotSetSize, int $coldSetSize): array
{
    $Ks = $this->pickKsForGame($gameId, $pool);
    if ($drawn <= 0 || empty($Ks)) return [];

    $ratioHot = $hitsHot / max(1,$drawn);
    $out = [];
    foreach ($Ks as $K) {
        $hot = (int)round($ratioHot * $K);
        $hot = max(0, min($hot, $K));
        // nie przekraczaj dostępnych wielkości zbiorów
        $hot = min($hot, $hotSetSize);
        $cold = $K - $hot;
        $cold = min($cold, $coldSetSize);
        // jeśli zabrakło liczb w jednym zbiorze, dociągnij z drugiego
        if ($hot + $cold < $K) {
            $deficit = $K - ($hot+$cold);
            if ($hotSetSize - $hot >= $deficit) $hot += $deficit;
            elseif ($coldSetSize - $cold >= $deficit) $cold += $deficit;
        }
        $out[(string)$K] = ['hot'=>$hot, 'cold'=>$cold];
    }
    return $out;
}


    // <<< DODANE >>>
    private function generateStrategyForResultId(int $statResultId): void
    {
        $srM = new \App\Models\StatResultModel();
        $drM = new \App\Models\DrawResultModel();
        $stM = new \App\Models\StrategyModel();

        $sr = $srM->find($statResultId);
        if (!$sr) return;

        $gameId = (int)$sr['game_id'];
        $fromNo = (int)$sr['from_draw_system_id'];

        // znajdź losowanie x+1
        $next = $drM->where('game_id', $gameId)
                    ->where('draw_system_id >', $fromNo)
                    ->orderBy('draw_system_id','ASC')
                    ->first();
        if (!$next) return;

        // zbiory HOT z wyniku statystyki
        $hotA = $this->explodeNums($sr['hot_a'] ?? '');
        $hotB = $this->explodeNums($sr['hot_b'] ?? '');

        $hotSetA = array_flip($hotA);
        $hotSetB = array_flip($hotB);

        $numsA = $this->explodeNums($next['numbers_a'] ?? '');
        $numsB = $this->explodeNums($next['numbers_b'] ?? '');

        // A
        $hitsHotA  = [];
        $hitsColdA = [];
        foreach ($numsA as $n) {
            if (isset($hotSetA[$n])) $hitsHotA[] = $n; else $hitsColdA[] = $n;
        }
        $hotEvenA = count(array_filter($hitsHotA, fn($n)=>($n % 2)===0));
        $hotOddA  = count($hitsHotA) - $hotEvenA;
        $coldEvenA= count(array_filter($hitsColdA, fn($n)=>($n % 2)===0));
        $coldOddA = count($hitsColdA) - $coldEvenA;

        // B (jeśli gra ma pulę B)
        $hitsHotB  = [];
        $hitsColdB = [];
        $hotEvenB = $hotOddB = $coldEvenB = $coldOddB = null;

        [$bmin,$bmax] = $this->domainRange($gameId,'B');
        if ($bmin !== null && $bmax !== null && !empty($numsB)) {
            foreach ($numsB as $n) {
                if (isset($hotSetB[$n])) $hitsHotB[] = $n; else $hitsColdB[] = $n;
            }
            $hotEvenB = count(array_filter($hitsHotB, fn($n)=>($n % 2)===0));
            $hotOddB  = count($hitsHotB) - $hotEvenB;
            $coldEvenB= count(array_filter($hitsColdB, fn($n)=>($n % 2)===0));
            $coldOddB = count($hitsColdB) - $coldEvenB;
        }

        // idempotentnie
        if ($stM->where('stat_result_id', $statResultId)->first()) return;

// --- REKOMENDACJE HOT/COLD (A i B) ---
[$amin,$amax] = $this->domainRange($gameId,'A');
$domainSizeA  = ($amin!==null && $amax!==null) ? ($amax - $amin + 1) : 0;
$hotSetSizeA  = count($hotA);
$coldSetSizeA = max(0, $domainSizeA - $hotSetSizeA);

$recA = $this->recommendSplits(
    $gameId, 'A',
    count($hitsHotA), count($hitsColdA), count($numsA),
    $hotSetSizeA, $coldSetSizeA
);

$recB = null;
if ($bmin !== null && $bmax !== null) {
    $domainSizeB  = $bmax - $bmin + 1;
    $hotSetSizeB  = count($hotB);
    $coldSetSizeB = max(0, $domainSizeB - $hotSetSizeB);
    $recB = $this->recommendSplits(
        $gameId, 'B',
        count($hitsHotB), count($hitsColdB), count($numsB),
        $hotSetSizeB, $coldSetSizeB
    );
}


        $stM->insert([
            'game_id'             => $gameId,
            'schema_id'           => $sr['schema_id'] ?? null,
            'stat_result_id'      => $statResultId,
            'from_draw_system_id' => $fromNo,
            'next_draw_system_id' => (int)$next['draw_system_id'],
            'stype'               => 'SIMPLE', // <— DODANE

            'hot_count_a'         => count($hitsHotA),
            'cold_count_a'        => count($hitsColdA),
            'hot_even_a'          => $hotEvenA,
            'hot_odd_a'           => $hotOddA,
            'cold_even_a'         => $coldEvenA,
            'cold_odd_a'          => $coldOddA,
            'hits_hot_a'          => $this->implodeNums($hitsHotA),
            'hits_cold_a'         => $this->implodeNums($hitsColdA),

            'hot_count_b'         => ($bmin!==null ? count($hitsHotB) : null),
            'cold_count_b'        => ($bmin!==null ? count($hitsColdB) : null),
            'hot_even_b'          => $hotEvenB,
            'hot_odd_b'           => $hotOddB,
            'cold_even_b'         => $coldEvenB,
            'cold_odd_b'          => $coldOddB,
            'hits_hot_b'          => $this->implodeNums($hitsHotB),
            'hits_cold_b'         => $this->implodeNums($hitsColdB),

'recommend_a_json'    => !empty($recA) ? json_encode($recA, JSON_UNESCAPED_UNICODE) : null,
'recommend_b_json'    => !empty($recB) ? json_encode($recB, JSON_UNESCAPED_UNICODE) : null,


            'created_at'          => date('Y-m-d H:i:s'),
        ]);
    }

public function backfillStrategies()
{
    $limit  = max(1, (int)($this->request->getGet('limit') ?? 1000));
    $gameId = (int)($this->request->getGet('game_id') ?? 0);

    $db = \Config\Database::connect();
    $sql = "SELECT sr.id AS stat_result_id
            FROM stat_results sr
            LEFT JOIN strategies st ON st.stat_result_id = sr.id
            WHERE st.id IS NULL " . ($gameId ? "AND sr.game_id=?" : "") . "
            ORDER BY sr.id ASC
            LIMIT ?";
    $params = $gameId ? [$gameId, $limit] : [$limit];

    $rows = $db->query($sql, $params)->getResultArray();
    $created = 0;
    foreach ($rows as $r) {
        $this->generateStrategyForResultId((int)$r['stat_result_id']);
        $created++;
    }

    return $this->response->setJSON([
        'ok'      => true,
        'created' => $created,
        'left'    => $this->countMissingStrategies($gameId),
    ]);
}

private function countMissingStrategies(int $gameId=0): int
{
    $db = \Config\Database::connect();
    $sql = "SELECT COUNT(*) AS cnt
            FROM stat_results sr
            LEFT JOIN strategies st ON st.stat_result_id = sr.id
            WHERE st.id IS NULL " . ($gameId ? "AND sr.game_id=?" : "");
    $row = $db->query($sql, $gameId ? [$gameId] : [])->getFirstRow('array');
    return (int)($row['cnt'] ?? 0);
}





// ==== [DODAJ] generator etykiety, fallback gdy name puste ====
private function schemaAutoName(array $s): string
{
    $sch = $s['scheme'] ?? '';
    $xa = $s['x_a'] ?? null; $ya = $s['y_a'] ?? null;
    $xb = $s['x_b'] ?? null; $yb = $s['y_b'] ?? null;

    if ($sch === 'S1') {
        $a = ($xa!==null && $ya!==null) ? " A: x={$xa}, y={$ya}" : '';
        $b = ($xb!==null && $yb!==null) ? " B: x={$xb}, y={$yb}" : '';
        return trim('S1: x≥y'.$a.$b);
    }
    if ($sch === 'S2') {
        $a = ($xa!==null) ? " A: k={$xa}" : '';
        $b = ($xb!==null) ? " B: k={$xb}" : '';
        return trim('S2: topK'.$a.$b);
    }
    return $sch ?: 'Schemat';
}



// ==== [DODAJ] formularz nowego schematu ====
public function schemasCreate()
{
    $gm = new \App\Models\GameModel();
    $games = $gm->orderBy('id','ASC')->findAll();

    $content = view('stats/schemas_create', ['games'=>$games]);
    return view('layouts/adminlte', ['title'=>'Statystyki — nowy schemat', 'content'=>$content]);
}



// nowa metoda!
public function schemaStore()
{
    $sm = new \App\Models\StatSchemaModel();

    $gameId = (int)$this->request->getPost('game_id');
    if ($gameId <= 0) {
        return redirect()->back()->withInput()->with('error','Wybierz grę.');
    }

    $scheme = $this->normalizeScheme((string)($this->request->getPost('scheme') ?? 'scheme1'));
    $params = $this->collectSchemaParamsFromPost($scheme);

    $name   = trim((string)$this->request->getPost('name'));
    $now    = date('Y-m-d H:i:s');

    $data = [
        'game_id'               => $gameId,
        'scheme'                => $scheme,
        'name'                  => ($name !== '' ? $name : $this->_schemaAutoNameFromParams($scheme, $params)),
        'params_json'           => json_encode($params, JSON_UNESCAPED_UNICODE),
        'status'                => 'idle',
        'first_met_from_draw'   => null,
        'current_from_draw'     => null,   // nie ustawiaj „2” z powietrza
        'processed_since_first' => 0,
        'created_at'            => $now,
        'updated_at'            => $now,
    ];

    if (!$sm->insert($data)) {
        return redirect()->back()->withInput()->with('errors', $sm->errors() ?: ['Nie udało się zapisać schematu.']);
    }

    return redirect()->to('/statystyki/schematy')->with('success', 'Schemat utworzony.');
}











// ==== [DODAJ] edycja schematu ====
public function schemasEdit(int $id)
{
    $sm = new \App\Models\StatSchemaModel();
    $row = $sm->find($id);
    if (!$row) return redirect()->to('/statystyki/schematy')->with('error','Nie znaleziono schematu.');

    $gm = new \App\Models\GameModel();
    $games = $gm->orderBy('id','ASC')->findAll();

    $content = view('stats/schemas_edit', ['row'=>$row, 'games'=>$games]);
    return view('layouts/adminlte', ['title'=>'Statystyki — edycja schematu #'.$id, 'content'=>$content]);
}






public function schemaUpdate($id)
{
    $sm = new \App\Models\StatSchemaModel();
    $row = $sm->find((int)$id);
    if (!$row) {
        return redirect()->to('/statystyki/schematy')->with('error','Nie znaleziono schematu.');
    }
    if (($row['status'] ?? 'idle') !== 'idle') {
        return redirect()->to('/statystyki/schematy')->with('warning','Schemat uruchomiony — edycja zablokowana.');
    }

    $gameId = (int)$this->request->getPost('game_id');
    if ($gameId <= 0) $gameId = (int)$row['game_id'];

    $scheme = $this->normalizeScheme((string)($this->request->getPost('scheme') ?? $row['scheme'] ?? 'scheme1'));
    $params = $this->collectSchemaParamsFromPost($scheme);

    $name   = trim((string)$this->request->getPost('name'));
    $now    = date('Y-m-d H:i:s');

    $data = [
        'game_id'     => $gameId,
        'scheme'      => $scheme,
        'name'        => ($name !== '' ? $name : $this->_schemaAutoNameFromParams($scheme, $params)),
        'params_json' => json_encode($params, JSON_UNESCAPED_UNICODE),
        'updated_at'  => $now,
    ];

    if (!$sm->update((int)$id, $data)) {
        return redirect()->back()->withInput()->with('errors', $sm->errors() ?: ['Nie udało się zaktualizować schematu.']);
    }

    return redirect()->to('/statystyki/schematy')->with('success', 'Schemat zaktualizowany.');
}



/**
 * Ujednolicenie klucza schematu.
 */
private function normalizeScheme(string $raw): string
{
    $k = strtolower(trim($raw));
    if ($k === 's1') return 'scheme1';
    if ($k === 's2') return 'scheme2';
    return $k;
}

/**
 * Zbiera parametry z POST bez wpychania „0” za puste.
 * Zwraca TYLKO te klucze, które mają realne wartości liczbowe.
 */
private function collectSchemaParamsFromPost(string $scheme): array
{
    $toIntOrNull = function(string $name) {
        $v = $this->request->getPost($name);
        return ($v === '' || $v === null) ? null : (int)$v;
    };

    $params = [];
    if ($scheme === 'scheme1') {
        $xa = $toIntOrNull('x_a'); if ($xa !== null) $params['x_a'] = $xa;
        $ya = $toIntOrNull('y_a'); if ($ya !== null) $params['y_a'] = $ya;
        $xb = $toIntOrNull('x_b'); if ($xb !== null) $params['x_b'] = $xb;
        $yb = $toIntOrNull('y_b'); if ($yb !== null) $params['y_b'] = $yb;
    } else { // scheme2
        $maa = $toIntOrNull('min_all_a'); if ($maa !== null) $params['min_all_a'] = $maa;
        $tka = $toIntOrNull('top_k_a');   if ($tka !== null) $params['top_k_a']   = $tka;
        $mab = $toIntOrNull('min_all_b'); if ($mab !== null) $params['min_all_b'] = $mab;
        $tkb = $toIntOrNull('top_k_b');   if ($tkb !== null) $params['top_k_b']   = $tkb;
    }
    return $params;
}



    
} // <<< KONIEC KLASY >>>
