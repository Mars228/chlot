<section class="content-header d-flex justify-content-between align-items-center">
  <div>
    <h1 class="h3 mb-0">Zakłady — seria #<?= (int)$batch['id'] ?></h1>
    <div class="text-muted">
      Gra: <?= esc($game['name'] ?? $batch['game_id']) ?>,
      Strategia: <?= esc($batch['stype']) ?>,
      Schemat: <?= $batch['schema_id'] ? '#'.(int)$batch['schema_id'] : '—' ?>,
      Kuponów: <?= (int)$batch['total_tickets'] ?>
    </div>
  </div>
  <a href="/zaklady" class="btn btn-secondary">Wróć</a>
</section>
<?= view('partials/flash') ?>

<div class="card card-outline card-primary mt-3">
  <div class="card-body p-0">
    <table class="table table-striped table-hover mb-0">
     <thead>
  <tr>
    <th>#</th>
    <th>Strategy</th>
    <th>check</th>
    <th>losowanie</th> <!-- = check + 1 -->
    <th>K(A)</th>
    <th>Liczby A</th>
    <th>K(B)</th>
    <th>Liczby B</th>
    <th>Typ</th>
  </tr>
</thead>
      <tbody>
      <?php if (empty($tickets)): ?>
        <tr><td colspan="8" class="text-center text-muted">Brak kuponów w serii.</td></tr>
      <?php else: foreach ($tickets as $t): ?>
        <tr>
          <td><?= (int)$t['id'] ?></td>
          <td>#<?= (int)$t['strategy_id'] ?></td>
          <td><code><?= (int)$t['next_draw_system_id'] ?></code></td>
<td><code><?= (int)$t['next_draw_system_id'] + 1 ?></code></td> <!-- nowa kolumna: docelowe losowanie -->
<td><?= $t['k_a'] ? (int)$t['k_a'] : '—' ?></td>
          
          <td><?= $t['k_a'] ? (int)$t['k_a'] : '—' ?></td>
          <td><?= esc($t['numbers_a'] ?? '') ?></td>
          <td><?= $t['k_b'] ? (int)$t['k_b'] : '—' ?></td>
          <td><?= esc($t['numbers_b'] ?? '') ?></td>
          <td>
            <span class="badge <?= $t['is_baseline'] ? 'bg-secondary' : 'bg-primary' ?>">
              <?= $t['is_baseline'] ? 'random' : 'SIMPLE' ?>
            </span>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
