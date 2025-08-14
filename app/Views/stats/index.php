<section class="content-header d-flex justify-content-between align-items-center">
  <h1 class="h3 mb-0">Statystyki</h1>
  <a class="btn btn-primary" href="/statystyki/nowa">+ Nowa statystyka</a>
</section>

<?= view('partials/flash') ?>

<div class="card card-outline card-primary mt-3">
  <div class="card-body p-0">
    <table class="table table-striped mb-0">
      <thead><tr>
        <th>#</th><th>Gra</th><th>Schemat</th><th>Od los.</th>
        <th>Okno</th><th>HOT A/B</th><th class="text-end">Akcje</th>
      </tr></thead>
      <tbody>
      <?php foreach ($results as $r):
        $gName = ''; foreach ($games as $g) if ($g['id']==$r['game_id']) { $gName=$g['name']; break; }
      ?>
        <tr>
          <td><?= $r['id'] ?></td>
          <td><?= esc($gName ?: $r['game_id']) ?></td>
          <td><?= $r['scheme']==='scheme1'?'S1: x≥y':'S2: all≥y → topK' ?></td>
          <td><code><?= esc($r['from_draw_system_id']) ?></code></td>
          <td><?= (int)($r['window_draws'] ?? 0) ?></td>
          <td>
            A: <small><?= esc($r['hot_a'] ?? '—') ?></small>
            <?php if (!empty($r['hot_b'])): ?> / B: <small><?= esc($r['hot_b']) ?></small><?php endif; ?>
          </td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="/statystyki/wynik/<?= $r['id'] ?>">Podgląd</a>
            <form action="/statystyki/wynik/<?= $r['id'] ?>/delete" method="post" class="d-inline" onsubmit="return confirm('Usunąć wynik?')">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-danger">Usuń</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="card-footer"><?= isset($pager)&&$pager?$pager->links():'' ?></div>
</div>
