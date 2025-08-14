<?php
// ====== DOMYŚLNE WARTOŚCI / SHIMY (na wypadek braków z kontrolera) ======
$stype    = $stype    ?? 'SIMPLE';
$games    = $games    ?? [];
$schemas  = $schemas  ?? [];
$list     = $list     ?? [];
$total    = isset($total) ? (int)$total : (is_array($list) ? count($list) : 0);

// domyślnie Multi Multi
if (!isset($gameId)) {
    $gameId = 0;
    foreach ($games as $gid => $g) {
        $row = is_array($g) ? $g : [];
        if (($row['slug'] ?? '') === 'multi-multi') { $gameId = (int)($row['id'] ?? $gid); break; }
    }
}
$schemaId = $schemaId ?? (count($schemas) ? (int)array_key_first($schemas) : 0);

// pager domyślny (jeśli kontroler nie podał)
$pager = $pager ?? [
    'page'    => (int)($_GET['page']      ?? 1),
    'perPage' => (int)($_GET['per_page']  ?? 50),
    'pages'   => 1,
    'query'   => http_build_query([
        'stype' => $stype, 'game_id' => $gameId, 'schema_id' => $schemaId,
        'per_page' => (int)($_GET['per_page'] ?? 50),
    ]),
];

// ==== Lokalny helper: CSV -> inty (posortowane) ====
if (!function_exists('csv_to_ints')) {
    function csv_to_ints(?string $s): array {
        if (!$s) return [];
        $parts = array_map('trim', explode(',', $s));
        $out = [];
        foreach ($parts as $p) if ($p!=='') $out[] = (int)$p;
        sort($out, SORT_NUMERIC);
        return $out;
    }
}

// ==== Lokalny helper: render liczb jako "chipów" (tylko kolor tekstu) ====
if (!function_exists('chips_plain')) {
    function chips_plain(array $nums, string $kind): string {
        if (empty($nums)) return '<span class="text-muted">—</span>';
        sort($nums, SORT_NUMERIC);
        $cls = ($kind==='hot' ? 'chip chip-hot-text' : 'chip chip-cold-text'); // tylko kolor tekstu
        $out=[];
        foreach ($nums as $n) $out[] = '<span class="'.$cls.'">'.$n.'</span>';
        return implode(' ', $out);
    }
}

// ==== Lokalny helper: SUM (recommend_*_json) → tekst "H/C" (dla wielu K: "K10 6/4; K9 5/4; ...") ====
if (!function_exists('render_sum_rec')) {
    function render_sum_rec(?string $json): string {
        if (!$json) return '—';
        $arr = json_decode($json, true);
        if (!$arr || !is_array($arr)) return '—';
        // jeżeli pojedynczy K → tylko "H/C"; jeżeli wiele → "Kx H/C; Ky H/C ..."
        if (count($arr) === 1) {
            $rc = reset($arr);
            $h = (int)($rc['hot'] ?? 0); $c = (int)($rc['cold'] ?? 0);
            return $h.'/'.$c;
        }
        $out = [];
        foreach ($arr as $k => $rc) {
            $h = (int)($rc['hot'] ?? 0); $c = (int)($rc['cold'] ?? 0);
            $out[] = 'K'.htmlspecialchars((string)$k).' '.$h.'/'.$c;
        }
        return '<small>'.implode('; ', $out).'</small>';
    }
}
?>
<section class="content-header d-flex justify-content-between align-items-center">
  <h1 class="h3 mb-0">Strategie</h1>
</section>
<?= view('partials/flash') ?>

<!-- FILTRY -->
<div class="card card-outline card-primary mt-3">
  <div class="card-body">
    <form method="get" class="row g-2 align-items-end">
      <div class="col-sm-3">
        <label class="form-label">Strategia</label>
        <select name="stype" class="form-select">
          <option value="SIMPLE" <?= ($stype==='SIMPLE'?'selected':'') ?>>SIMPLE</option>
        </select>
      </div>
      <div class="col-sm-3">
        <label class="form-label">Gra</label>
        <select name="game_id" id="game_id" class="form-select">
          <option value="0">— Wszystkie —</option>
          <?php foreach ($games as $gid=>$g):
            $row = is_array($g) ? $g : []; $id = (int)($row['id'] ?? $gid);
            $nm = $row['name'] ?? ($row['slug'] ?? ('#'.$id));
          ?>
            <option value="<?= $id ?>" <?= ($gameId==$id?'selected':'') ?>><?= esc($nm) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-3">
        <label class="form-label">Schemat</label>
        <select name="schema_id" id="schema_id" class="form-select">
    <option value="0">— Wszystkie —</option>
    <?php foreach ($schemas as $sid=>$s): ?>
      <?php
        $sc = is_array($s) ? $s : [];
        $scheme = $sc['scheme'] ?? '';
        $sgGameId = (int)($sc['game_id'] ?? 0);
      ?>
      <option value="<?= (int)$sid ?>"
              data-game="<?= $sgGameId ?>"
              <?= ($schemaId==(int)$sid?'selected':'') ?>>
        #<?= (int)$sid ?> <?= esc($scheme) ?>
      </option>
    <?php endforeach; ?>
  </select>
      </div>
      <div class="col-sm-2">
        <label class="form-label">Na stronę</label>
        <select name="per_page" class="form-select">
          <?php foreach ([25,50,100,200] as $pp): ?>
            <option value="<?= $pp ?>" <?= (($pager['perPage']??50)==$pp?'selected':'') ?>><?= $pp ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-sm-1">
        <button class="btn btn-primary w-100">Filtruj</button>
      </div>
    </form>
  </div>
</div>

<!-- TABELA -->
<div class="card card-outline card-primary mt-3">
  <div class="card-body p-0">
    <table class="table table-striped mb-0 align-middle">
      <thead>
        <tr>
          <th rowspan="2">#</th>
          <th rowspan="2">okno</th>
          <th rowspan="2">range<br><small>(do–od)</small></th>
          <th rowspan="2">check</th>

          <th rowspan="2">[a:H]</th>
          <th rowspan="2">[a:C]</th>
          <th rowspan="2">[SUM:a]</th>
          <th class="th-bg-hot-even"  rowspan="2">[a:EH]</th>
          <th class="th-bg-hot-odd"   rowspan="2">[a:OH]</th>
          <th class="th-bg-cold-even" rowspan="2">[a:EC]</th>
          <th class="th-bg-cold-odd"  rowspan="2">[a:OC]</th>
          <th colspan="2">[PAR:a]</th>

          <th rowspan="2">[b:H]</th>
          <th rowspan="2">[b:C]</th>
          <th rowspan="2">[SUM:b]</th>
          <th class="th-bg-hot-even"  rowspan="2">[b:EH]</th>
          <th class="th-bg-hot-odd"   rowspan="2">[b:OH]</th>
          <th class="th-bg-cold-even" rowspan="2">[b:EC]</th>
          <th class="th-bg-cold-odd"  rowspan="2">[b:OC]</th>
          <th colspan="2">[PAR:b]</th>

          
        </tr>
        <tr>
          <th>hot e/o</th>
          <th>cold e/o</th>
          <th>hot e/o</th>
          <th>cold e/o</th>
        </tr>
      </thead>
      <tbody>
<?php if (!empty($list)): ?>
  <?php foreach ($list as $r):
    // Bezpieczne wyciąganie pól
    $from = (int)($r['from_draw_system_id'] ?? 0);
    $start= (int)($r['met_at_draw_system_id'] ?? 0); // „od” wg Twojej definicji
    $next = (int)($r['next_draw_system_id'] ?? 0);

    // okno — preferuj A/B, inaczej window_draws
    $wA = (int)($r['window_draws_a'] ?? 0);
    $wB = (int)($r['window_draws_b'] ?? 0);
    $w  = (int)($r['window_draws'] ?? max($wA, $wB, 0));

    // A: trafione liczby w x+1
    $aH = csv_to_ints($r['hits_hot_a']  ?? '');
    $aC = csv_to_ints($r['hits_cold_a'] ?? '');

    // A: even/odd breakdown
    $aEH = array_values(array_filter($aH, fn($n)=>$n%2===0));
    $aOH = array_values(array_filter($aH, fn($n)=>$n%2===1));
    $aEC = array_values(array_filter($aC, fn($n)=>$n%2===0));
    $aOC = array_values(array_filter($aC, fn($n)=>$n%2===1));

    // B (może nie istnieć)
    $bH = csv_to_ints($r['hits_hot_b']  ?? '');
    $bC = csv_to_ints($r['hits_cold_b'] ?? '');

    $bEH = array_values(array_filter($bH, fn($n)=>$n%2===0));
    $bOH = array_values(array_filter($bH, fn($n)=>$n%2===1));
    $bEC = array_values(array_filter($bC, fn($n)=>$n%2===0));
    $bOC = array_values(array_filter($bC, fn($n)=>$n%2===1));

    // PAR (hot/cold) = e/o w x+1 (nie skalujemy do K, pokazujemy realne  "e/o")
    $parA_hot  = (int)($r['hot_even_a']  ?? 0) . '/' . (int)($r['hot_odd_a']  ?? 0);
    $parA_cold = (int)($r['cold_even_a'] ?? 0) . '/' . (int)($r['cold_odd_a'] ?? 0);
    $parB_hot  = ($r['hot_even_b']  !== null ? ((int)$r['hot_even_b']).'/'.((int)$r['hot_odd_b']) : '—');
    $parB_cold = ($r['cold_even_b'] !== null ? ((int)$r['cold_even_b']).'/'.((int)$r['cold_odd_b']) : '—');

    // SUM (recommendations hot/cold)
    $sumA = render_sum_rec($r['recommend_a_json'] ?? null);
    $sumB = render_sum_rec($r['recommend_b_json'] ?? null);
  ?>
  <tr>
    <td><?= (int)($r['id'] ?? 0) ?></td>
    <td><?= $w ?></td>
    <td><?= $start ?: '—' ?>–<?= $from ?></td> <!-- do–od -->
    <td><?= $next ?></td>

    <td><?= chips_plain($aH,'hot')  ?></td>
    <td><?= chips_plain($aC,'cold') ?></td>
    <td><?= $sumA ?></td>

    <td class="td-bg-hot-even"><?= empty($aEH)? '—' : implode(', ', $aEH) ?></td>
    <td class="td-bg-hot-odd"><?=  empty($aOH)? '—' : implode(', ', $aOH) ?></td>
    <td class="td-bg-cold-even"><?=empty($aEC)? '—' : implode(', ', $aEC) ?></td>
    <td class="td-bg-cold-odd"><?= empty($aOC)? '—' : implode(', ', $aOC) ?></td>
    <td><?= $parA_hot  ?></td>
    <td><?= $parA_cold ?></td>

    <td><?= !empty($bH) ? chips_plain($bH,'hot') : '—' ?></td>
    <td><?= !empty($bC) ? chips_plain($bC,'cold'): '—' ?></td>
    <td><?= $sumB ?></td>

    <td class="td-bg-hot-even"><?= empty($bEH)? '—' : implode(', ', $bEH) ?></td>
    <td class="td-bg-hot-odd"><?=  empty($bOH)? '—' : implode(', ', $bOH) ?></td>
    <td class="td-bg-cold-even"><?=empty($bEC)? '—' : implode(', ', $bEC) ?></td>
    <td class="td-bg-cold-odd"><?= empty($bOC)? '—' : implode(', ', $bOC) ?></td>
    <td><?= $parB_hot  ?></td>
    <td><?= $parB_cold ?></td>

    
  </tr>
  <?php endforeach; ?>
<?php else: ?>
  <tr>
    <td colspan="21" class="text-center py-4">
      Brak danych do wyświetlenia (total: <?= (int)$total ?>).
    </td>
  </tr>
<?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (!empty($pager) && ($pager['pages'] ?? 1) > 1): ?>
<nav class="mt-3">
  <ul class="pagination">
    <?php for ($p=1; $p<=($pager['pages'] ?? 1); $p++): ?>
      <?php $url = '?'.($pager['query'] ?? '').'&page='.$p; ?>
      <li class="page-item <?= ($p==($pager['page'] ?? 1)?'active':'') ?>">
        <a class="page-link" href="<?= $url ?>"><?= $p ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>



<script>
(function(){
  const $game   = document.getElementById('game_id');
  const $schema = document.getElementById('schema_id');
  if (!$game || !$schema) return;

  // Zachowaj oryginalne opcje schematów (poza pierwszą „— Wszystkie —”)
  const baseOption = $schema.querySelector('option[value="0"]');
  const allOptions = Array.from($schema.querySelectorAll('option')).slice(1); // bez value="0"

  function rebuildSchemas(){
    const gid = parseInt($game.value || '0', 10);
    const current = $schema.value; // spróbujemy zachować wybór

    // wyczyść i wstaw „Wszystkie”
    $schema.innerHTML = '';
    if (baseOption) $schema.appendChild(baseOption.cloneNode(true));

    // jeżeli wybrana gra = 0 → pokaż wszystkie schematy
    const opts = allOptions.filter(opt => {
      const og = parseInt(opt.getAttribute('data-game') || '0', 10);
      return gid === 0 ? true : (og === gid);
    });

    // wstaw przefiltrowane opcje
    opts.forEach(opt => $schema.appendChild(opt.cloneNode(true)));

    // jeśli poprzednio wybrany schemat nadal pasuje → zaznacz go,
    // w przeciwnym razie ustaw na „0”
    const stillExists = Array.from($schema.options).some(o => o.value === current);
    $schema.value = stillExists ? current : '0';
  }

  // Pierwsze zbudowanie po załadowaniu
  rebuildSchemas();

  // Reaguj na zmianę gry (bez submitu)
  $game.addEventListener('change', rebuildSchemas);
})();
</script>
