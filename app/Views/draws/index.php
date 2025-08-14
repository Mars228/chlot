<?php
if (!function_exists('fmt_nums_sorted')) {
    function fmt_nums_sorted(?string $csv): string {
        if (!$csv) return '—';
        $arr = array_map('trim', explode(',', $csv));
        $arr = array_filter($arr, fn($v)=>$v!=='' );
        $arr = array_map('intval', $arr);   // usuwa wiodące zera, „01” → 1
        sort($arr, SORT_NUMERIC);           // rosnąco
        return $arr ? implode(', ', $arr) : '—';
    }
}
?>
<section class="content-header">
  <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between">
    <h1 class="h3 mb-0">Losowania — <?= esc($game['name']) ?></h1>
    <div class="btn-group">
  <a href="/losowania/import" class="btn btn-outline-primary">Import CSV</a>
  <a href="/losowania/pobierz" class="btn btn-outline-secondary">Pobierz z API</a>

  <?php if (($game['slug'] ?? '') === 'multi-multi'): ?>
    <form action="/losowania/sync-mm-ids" method="post" class="d-inline">
      <?= csrf_field() ?>
      <input type="hidden" name="days" value="14">
      <button class="btn btn-outline-info" title="Uzupełnij brakujące numery losowań (ostatnie 14 dni)">Synchronizuj numery MM</button>
    </form>
  <?php endif; ?>
</div>
  </div>
</section>

<?= view('partials/flash') ?>

<?php if (!empty($range['min_no']) || !empty($range['max_no'])): ?>
<div class="alert alert-info mt-3">
  <div><strong>Zakres w bazie (<?= esc($game['name']) ?>):</strong></div>
  <div>
    min: <code><?= esc($range['min_no'] ?? '—') ?></code>
    <?php if (!empty($range['min_dt'])): ?> (<?= esc(substr($range['min_dt'],0,16)) ?>)<?php endif; ?>
    &nbsp;|&nbsp;
    max: <code><?= esc($range['max_no'] ?? '—') ?></code>
    <?php if (!empty($range['max_dt'])): ?> (<?= esc(substr($range['max_dt'],0,16)) ?>)<?php endif; ?>
  </div>
</div>
<?php endif; ?>

<div class="card mt-3">
  <div class="card-body">
    <form class="row g-2" method="get" action="/losowania">
      <div class="col-md-4">
        <label class="form-label">Gra</label>
        <select class="form-select" name="game" onchange="this.form.submit()">
          <?php foreach ($games as $g): ?>
            <option value="<?= esc($g['slug']) ?>" <?= $g['id']==$game['id']?'selected':'' ?>><?= esc($g['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Miesiąc</label>
        <input class="form-control" type="month" name="month" value="<?= esc($month) ?>" onchange="this.form.submit()">
      </div>
      <div class="col-md-5 d-flex align-items-end justify-content-end gap-2">
        <a class="btn btn-outline-secondary" href="/losowania?game=<?= esc($gameSlug) ?>&month=<?= esc($prev) ?>">← <?= esc($prev) ?></a>
        <a class="btn btn-outline-secondary" href="/losowania?game=<?= esc($gameSlug) ?>&month=<?= esc($next) ?>"><?= esc($next) ?> →</a>
      </div>
    </form>
  </div>
</div>

<div class="card card-outline card-primary">
  <div class="card-body p-0">
    <table class="table table-striped table-hover mb-0">
      <thead>
        <tr>
          <th style="width:120px">Nr losowania</th>
          <th style="width:120px">Data</th>
          <th style="width:100px">Godzina</th>
          <th>Liczby A</th>
          <th>Liczby B</th>
          <th style="width:80px">Źródło</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="6" class="text-center text-muted">Brak danych w tym miesiącu.</td></tr>
      <?php else: foreach ($rows as $r): ?>
        <tr>
          <td><code><?= esc($r['draw_system_id']) ?></code></td>
          <td><?= !empty($r['draw_date']) ? date('d/m/Y', strtotime($r['draw_date'])) : '' ?></td>
          <td><?= esc(substr($r['draw_time'],0,5)) ?></td>
          <td><?= esc(pretty_numbers($r['numbers_a'])) ?></td>
          <td><?= esc(pretty_numbers($r['numbers_b'])) ?></td>
          <td><span class="badge bg-<?= $r['source']==='api'?'info':'secondary' ?>"><?= esc(strtoupper($r['source'])) ?></span></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
