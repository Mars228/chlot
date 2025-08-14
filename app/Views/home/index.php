<?php
// lokalny helper – sortowane liczby bez zer wiodących
if (!function_exists('pretty_numbers')) {
    function pretty_numbers(?string $csv): string {
        if (!$csv) return '';
        $arr = array_map('trim', explode(',', $csv));
        $arr = array_filter($arr, static fn($v)=>$v!=='');
        $arr = array_map('intval', $arr);
        sort($arr, SORT_NUMERIC);
        return $arr ? implode(', ', $arr) : '';
    }
}
?>
<section class="content-header d-flex justify-content-between align-items-center">
  <h1 class="h3 mb-0">Pulpit</h1>
  <div class="d-flex gap-2">
    <a href="/losowania" class="btn btn-outline-secondary">Losowania</a>
    <a href="/wyniki" class="btn btn-primary">Wyniki</a>
  </div>
</section>

<div class="row mt-3">
  <!-- LEWA: Ostatnie losowania -->
  <div class="col-lg-4">
    <div class="card card-outline card-primary">
      <div class="card-header">
        <strong>Ostatnie losowania</strong>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm table-striped mb-0 align-middle">
          <thead>
            <tr>
              <th>Gra</th>
              <th>Nr</th>
              <th>Data</th>
              <th>Godz</th>
              <th>A</th>
              <th>B</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($latest)): ?>
            <tr><td colspan="6" class="text-center text-muted">Brak danych losowań.</td></tr>
          <?php else: foreach ($latest as $gid=>$r):
            $g = $games[$gid] ?? null;
          ?>
            <tr>
              <td><?= esc($g['name'] ?? ('#'.$gid)) ?></td>
              <td><code><?= (int)$r['draw_system_id'] ?></code></td>
              <td><?= !empty($r['draw_date']) ? date('d/m/Y', strtotime($r['draw_date'])) : '' ?></td>
              <td><?= esc(substr($r['draw_time'] ?? '', 0, 5)) ?></td>
              <td><?= esc(pretty_numbers($r['numbers_a'] ?? '')) ?></td>
              <td><?= esc(pretty_numbers($r['numbers_b'] ?? '')) ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ŚRODEK: tygodniowo -->
  <div class="col-lg-4">
    <div class="card card-outline card-success">
      <div class="card-header">
        <strong>Wygrane – ostatnie tygodnie</strong>
        <span class="text-muted ms-1">(max 4 / gra)</span>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm table-striped mb-0 align-middle">
          <thead>
            <tr>
              <th>Gra</th>
              <th>Tydzień</th>
              <th>Od–Do</th>
              <th>Wygrane (PLN)</th>
              <th>Winners</th>
              <th>Kupony</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $any = false;
          foreach ($games as $gid=>$g):
            $rows = $weekly[$gid] ?? [];
            if (!$rows) continue;
            $any = true;
            foreach ($rows as $w):
          ?>
            <tr>
              <td><?= esc($g['name']) ?></td>
              <td><?= esc($w['yrwk']) ?></td>
              <td><small><?= esc($w['date_from']) ?>–<?= esc($w['date_to']) ?></small></td>
              <td><?= number_format((float)$w['win_sum'], 2, ',', ' ') ?></td>
              <td><?= (int)$w['winners'] ?></td>
              <td><?= (int)$w['tickets'] ?></td>
            </tr>
          <?php
            endforeach;
          endforeach;
          if (!$any): ?>
            <tr><td colspan="6" class="text-center text-muted">Brak danych z bet_results.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- PRAWA: miesięcznie -->
  <div class="col-lg-4">
    <div class="card card-outline card-info">
      <div class="card-header">
        <strong>Wygrane – ostatnie miesiące</strong>
        <span class="text-muted ms-1">(max 3 / gra)</span>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm table-striped mb-0 align-middle">
          <thead>
            <tr>
              <th>Gra</th>
              <th>Miesiąc</th>
              <th>Od–Do</th>
              <th>Wygrane (PLN)</th>
              <th>Winners</th>
              <th>Kupony</th>
            </tr>
          </thead>
          <tbody>
          <?php
          $any = false;
          foreach ($games as $gid=>$g):
            $rows = $monthly[$gid] ?? [];
            if (!$rows) continue;
            $any = true;
            foreach ($rows as $m):
          ?>
            <tr>
              <td><?= esc($g['name']) ?></td>
              <td><?= esc($m['ym']) ?></td>
              <td><small><?= esc($m['date_from']) ?>–<?= esc($m['date_to']) ?></small></td>
              <td><?= number_format((float)$m['win_sum'], 2, ',', ' ') ?></td>
              <td><?= (int)$m['winners'] ?></td>
              <td><?= (int)$m['tickets'] ?></td>
            </tr>
          <?php
            endforeach;
          endforeach;
          if (!$any): ?>
            <tr><td colspan="6" class="text-center text-muted">Brak danych z bet_results.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
