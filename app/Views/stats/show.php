<section class="content-header"><h1 class="h3">Wynik #<?= $res['id'] ?> — <?= esc($game['name']) ?></h1></section>
<div class="card mt-3">
  <div class="card-body">
    <dl class="row">
      <dt class="col-sm-3">Schemat</dt><dd class="col-sm-9"><?= $res['scheme']==='scheme1'?'S1: x≥y':'S2: all≥y → topK' ?></dd>
      <dt class="col-sm-3">Od losowania</dt><dd class="col-sm-9"><code><?= esc($res['from_draw_system_id']) ?></code></dd>
      <dt class="col-sm-3">Okno (losowań)</dt><dd class="col-sm-9"><?= (int)($res['window_draws'] ?? 0) ?></dd>
      <?php if (!empty($res['met_at_draw_system_id'])): ?>
        <dt class="col-sm-3">Spełniono przy</dt><dd class="col-sm-9"><code><?= esc($res['met_at_draw_system_id']) ?></code></dd>
      <?php endif; ?>

      <dt class="col-sm-3">Liczby A</dt><dd class="col-sm-9"><code><?= esc($res['numbers_in_a'] ?? '—') ?></code></dd>
      <?php if (!empty($res['numbers_in_b'])): ?>
        <dt class="col-sm-3">Liczby B</dt><dd class="col-sm-9"><code><?= esc($res['numbers_in_b']) ?></code></dd>
      <?php endif; ?>

      <dt class="col-sm-3">HOT A</dt><dd class="col-sm-9"><code><?= esc($res['hot_a'] ?? '—') ?></code></dd>
      <?php if (!empty($res['hot_b'])): ?>
        <dt class="col-sm-3">HOT B</dt><dd class="col-sm-9"><code><?= esc($res['hot_b']) ?></code></dd>
      <?php endif; ?>

      <dt class="col-sm-3">COLD A</dt><dd class="col-sm-9"><code><?= esc($res['cold_a'] ?? '—') ?></code></dd>
      <?php if (!empty($res['cold_b'])): ?>
        <dt class="col-sm-3">COLD B</dt><dd class="col-sm-9"><code><?= esc($res['cold_b']) ?></code></dd>
      <?php endif; ?>

      <dt class="col-sm-3">Zakończono</dt><dd class="col-sm-9"><?= esc($res['finished_at']) ?></dd>
    </dl>

    <div class="d-flex gap-2">
      <a class="btn btn-secondary" href="/statystyki">Wróć</a>
      <form action="/statystyki/wynik/<?= $res['id'] ?>/delete" method="post" onsubmit="return confirm('Usunąć wynik?')">
        <?= csrf_field() ?>
        <button class="btn btn-outline-danger">Usuń</button>
      </form>
    </div>
  </div>
</div>
