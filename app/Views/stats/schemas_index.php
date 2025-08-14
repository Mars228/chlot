<section class="content-header d-flex justify-content-between align-items-center">
  <h1 class="h3 mb-0">Statystyki — schematy</h1>
  <a class="btn btn-primary" href="/statystyki/nowy">+ Nowy schemat</a>
</section>

<?= view('partials/flash') ?>

<?php
if (!function_exists('schema_label_view')) {
  function schema_label_view(array $r): string {
    $scheme = $r['scheme'] ?? '';
    $p = $r['params_json'] ?? [];
    if (!is_array($p)) $p = json_decode((string)$p, true) ?: [];
    $xa = $p['x_a'] ?? $p['xA'] ?? null;  $ya = $p['y_a'] ?? $p['yA'] ?? null;
    $xb = $p['x_b'] ?? $p['xB'] ?? null;  $yb = $p['y_b'] ?? $p['yB'] ?? null;
    $ka = $p['k_a'] ?? $p['kA'] ?? null;  $kb = $p['k_b'] ?? $p['kB'] ?? null;

    if ($scheme==='S1') {
      $a = ($xa!==null && $ya!==null) ? " A: x={$xa}, y={$ya}" : '';
      $b = ($xb!==null && $yb!==null) ? " B: x={$xb}, y={$yb}" : '';
      return trim('S1: x≥y'.$a.$b);
    }
    if ($scheme==='S2') {
      $a = ($ka!==null) ? " A: k={$ka}" : '';
      $b = ($kb!==null) ? " B: k={$kb}" : '';
      return trim('S2: topK'.$a.$b);
    }
    return $scheme ?: 'Schemat';
  }
}
?>



<div class="card card-outline card-primary mt-3">
  <div class="card-body p-0">
    <table class="table table-striped mb-0 align-middle">
      <thead>
        <tr>
          <th>#</th>
          <th>Gra</th>
          <th>Schemat</th>
          <th>Parametry</th>
          <th>Start cyklu</th>
          <th>Następny from</th>
          <th>Przetworzono</th>
          <th>Status</th>
          <th class="text-end">Akcje</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($schemas as $s):
          $g = $games[$s['game_id']] ?? null;
          $params = json_decode($s['params_json'], true) ?: [];
$hasB = ($g && $g['range_b_min'] !== null && $g['range_b_max'] !== null);

if ($s['scheme']==='scheme1') {
  $pTxt = "A: x=".($params['x_a']??'?').", y=".($params['y_a']??'?');
  if ($hasB && !empty($params['x_b']) && !empty($params['y_b'])) {
    $pTxt .= "; B: x=".($params['x_b']).", y=".($params['y_b']);
  }
} else {
  $pTxt = "A: all≥".($params['min_all_a']??'?').", topK=".($params['top_k_a']??'?');
  if ($hasB && (!empty($params['min_all_b']) || !empty($params['top_k_b']))) {
    $pTxt .= "; B: all≥".($params['min_all_b']??0).", topK=".($params['top_k_b']??0);
  }
}

        ?>
        <tr data-id="<?= $s['id'] ?>" data-status="<?= esc($s['status']) ?>" data-next="<?= esc($s['current_from_draw'] ?? '') ?>">
          <td><?= $s['id'] ?></td>
          <td><?= esc($g['name'] ?? $s['game_id']) ?></td>
          <td><?= $s['scheme']==='scheme1' ? 'S1: x≥y' : 'S2: all≥y → topK' ?></td>
          <td><small><?= esc($pTxt) ?></small></td>
          <td><?= $s['first_met_from_draw'] ? '<code>'.$s['first_met_from_draw'].'</code>' : '—' ?></td>
          <td><?= $s['current_from_draw'] ? '<code>'.$s['current_from_draw'].'</code>' : '—' ?></td>
          <td><?= (int)$s['processed_since_first'] ?></td>
          <td>
            <?php if ($s['status']==='running'): ?>
              <span class="badge bg-success">running</span>
            <?php elseif ($s['status']==='paused'): ?>
              <span class="badge bg-warning text-dark">paused</span>
            <?php elseif ($s['status']==='done'): ?>
              <span class="badge bg-secondary">done</span>
            <?php else: ?>
              <span class="badge bg-light text-dark">idle</span>
            <?php endif; ?>
          </td>
          <td class="text-end">
            <?php if ($s['status']==='idle'): ?>
              <a class="btn btn-sm btn-outline-primary" href="/statystyki/schemat/<?= $s['id'] ?>/edytuj">Edytuj</a>
              <form action="/statystyki/schemat/<?= $s['id'] ?>/start" method="post" class="d-inline start-form">
                <?= csrf_field() ?><button class="btn btn-sm btn-primary">Start</button>
              </form>
              <form action="/statystyki/schemat/<?= $s['id'] ?>/usun" method="post" class="d-inline" onsubmit="return confirm('Usunąć schemat i wszystkie jego wyniki?')">
                <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Usuń</button>
              </form>
            <?php elseif ($s['status']==='running'): ?>
  <button type="button" class="btn btn-sm btn-outline-primary js-step" data-id="<?= $s['id'] ?>">Krok</button>
  <form action="/statystyki/schemat/<?= $s['id'] ?>/pauza" method="post" class="d-inline pause-form ms-1">
    <?= csrf_field() ?><button class="btn btn-sm btn-outline-warning">Pauza</button>
  </form>
            <?php else: ?>
              <form action="/statystyki/schemat/<?= $s['id'] ?>/start" method="post" class="d-inline start-form">
                <?= csrf_field() ?><button class="btn btn-sm btn-primary">Wznów</button>
              </form>
              <form action="/statystyki/schemat/<?= $s['id'] ?>/usun" method="post" class="d-inline" onsubmit="return confirm('Usunąć schemat i wszystkie jego wyniki?')">
                <?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Usuń</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>


<script>
(function(){
  // uruchom automatycznie „step” dla każdego schematu w stanie running
  function stepRow(tr) {
    const id = tr.dataset.id;
    fetch(`/statystyki/schemat/${id}/step?batch=200`, { headers: { 'Accept': 'application/json' }})
      .then(r => r.json())
      .then(r => {
        if (!r.ok) {
          toastr.error(r.msg || `Schemat #${id}: błąd`);
          return;
        }
        if (r.saved && r.saved > 0) {
          toastr.info(`Schemat #${id}: zapisano ${r.saved} (batch ${r.done})`, { timeOut: 1200 });
        }
        if (r.status === 'done') {
          tr.dataset.status = 'done';
          toastr.success(`Schemat #${id}: zakończono.`);
          // szybkie odświeżenie tabeli, żeby zobaczyć finalny stan
          setTimeout(()=>location.reload(), 700);
          return;
        }
        // kontynuuj pętlę, jeśli nadal running
        if (tr.dataset.status === 'running') {
          setTimeout(()=>stepRow(tr), 300);
        }
      })
      .catch(e => toastr.error(`Schemat #${id}: ${e.message}`));
  }

  // Autostart dla wszystkich running
  document.querySelectorAll('tr[data-id][data-status="running"]').forEach(tr => {
    setTimeout(()=>stepRow(tr), 300);
  });

  // „Krok” ręczny
  document.querySelectorAll('.js-step').forEach(btn => {
    btn.addEventListener('click', function(){
      const id = this.dataset.id;
      const tr = document.querySelector(`tr[data-id="${id}"]`);
      if (!tr) return;
      toastr.info(`Schemat #${id}: wykonuję krok…`, { timeOut: 800 });
      stepRow(tr);
    });
  });
})();
</script>
