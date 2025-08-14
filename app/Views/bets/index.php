<section class="content-header d-flex justify-content-between align-items-center">
  <h1 class="h3 mb-0">Zakłady — serie</h1>
  <a href="/zaklady/nowa" class="btn btn-primary">Nowa seria</a>
</section>
<?= view('partials/flash') ?>

<div class="card card-outline card-primary mt-3">
  <div class="card-body p-0">
    <table class="table table-striped mb-0 align-middle">
      <thead>
        <tr>
          <th>#</th>
          <th>Gra</th>
          <th>Strategia</th>
          <th>Schemat</th>
          <th>Filtr strategii</th>
          <th>Per strategię</th>
          <th>Baseline</th>
          <th>Kupony</th>
          <th>Status</th>
          <th>Data</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="11" class="text-center text-muted">Brak serii.</td></tr>
      <?php else: foreach ($rows as $r):
        $g = $games[$r['game_id']] ?? null;
      ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= esc($g['name'] ?? $r['game_id']) ?></td>
          <td><?= esc($r['stype']) ?></td>
          <td><?= $r['schema_id'] ? '#'.(int)$r['schema_id'] : '—' ?></td>
          <td>
            <?php
              $f=[]; if ($r['strategy_id_from']) $f[]='id≥'.$r['strategy_id_from'];
              if ($r['strategy_id_to']) $f[]='id≤'.$r['strategy_id_to'];
              if ($r['last_n']) $f[]='ostatnie '.$r['last_n'];
              echo $f? esc(implode(', ', $f)) : '—';
            ?>
          </td>
          <td><?= (int)$r['per_strategy'] ?></td>
          <td><?= $r['include_random_baseline'] ? 'tak' : 'nie' ?></td>
          <td><?= (int)$r['total_tickets'] ?></td>
          <td><span class="badge bg-<?= $r['status']==='done'?'success':($r['status']==='running'?'warning':'secondary') ?>">
            <?= esc($r['status']) ?></span></td>
          <td><small><?= esc($r['created_at']) ?></small></td>
          <td><a class="btn btn-sm btn-outline-primary" href="/zaklady/seria/<?= (int)$r['id'] ?>">Pokaż</a></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
