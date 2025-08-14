<section class="content-header"><h1 class="h3">Edytuj zadanie #<?= $job['id'] ?> — <?= esc($game['name']) ?></h1></section>
<?= view('partials/flash') ?>

<?php 
  $payload = json_decode($job['params_json'], true) ?: [];
  $p = $payload['params'] ?? [];
  $hasB = ($game['range_b_min'] !== null && $game['range_b_max'] !== null);
?>

<div class="card card-outline card-primary mt-3">
  <div class="card-body">
    <form action="/statystyki/job/<?= $job['id'] ?>/update" method="post" class="row g-3">
      <?= csrf_field() ?>

      <?php if ($job['scheme']==='scheme1'): ?>
        <div class="col-12"><h6>S1: x liczb ≥ y powtórzeń</h6></div>
        <div class="col-md-3"><label class="form-label">x (A)</label><input class="form-control" type="number" name="s1_x_a" value="<?= (int)($p['x_a']??28) ?>" min="1"></div>
        <div class="col-md-3"><label class="form-label">y (A)</label><input class="form-control" type="number" name="s1_y_a" value="<?= (int)($p['y_a']??1) ?>" min="1"></div>

        <?php if ($hasB): ?>
          <div class="col-12"><h6 class="mb-1">Pula B</h6></div>
          <div class="col-md-3"><label class="form-label">x (B)</label><input class="form-control" type="number" name="s1_x_b" value="<?= (int)($p['x_b']??3) ?>" min="0"></div>
          <div class="col-md-3"><label class="form-label">y (B)</label><input class="form-control" type="number" name="s1_y_b" value="<?= (int)($p['y_b']??1) ?>" min="0"></div>
        <?php else: ?>
          <!-- wymuś 0 dla B w grach jednokoszykowych -->
          <input type="hidden" name="s1_x_b" value="0">
          <input type="hidden" name="s1_y_b" value="0">
        <?php endif; ?>

      <?php else: ?>
        <div class="col-12"><h6>S2: każda liczba ≥ y → topK</h6></div>
        <div class="col-md-3"><label class="form-label">min_all (A)</label><input class="form-control" type="number" name="s2_min_all_a" value="<?= (int)($p['min_all_a']??1) ?>" min="1"></div>
        <div class="col-md-3"><label class="form-label">top K (A)</label><input class="form-control" type="number" name="s2_top_k_a" value="<?= (int)($p['top_k_a']??8) ?>" min="1"></div>

        <?php if ($hasB): ?>
          <div class="col-12"><h6 class="mb-1">Pula B</h6></div>
          <div class="col-md-3"><label class="form-label">min_all (B)</label><input class="form-control" type="number" name="s2_min_all_b" value="<?= (int)($p['min_all_b']??1) ?>" min="0"></div>
          <div class="col-md-3"><label class="form-label">top K (B)</label><input class="form-control" type="number" name="s2_top_k_b" value="<?= (int)($p['top_k_b']??3) ?>" min="0"></div>
        <?php else: ?>
          <input type="hidden" name="s2_min_all_b" value="0">
          <input type="hidden" name="s2_top_k_b" value="0">
        <?php endif; ?>
      <?php endif; ?>

      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary">Zapisz</button>
        <a class="btn btn-secondary" href="/statystyki/job/<?= $job['id'] ?>">Anuluj</a>
      </div>
    </form>
  </div>
</div>
