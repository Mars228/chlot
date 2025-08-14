<section class="content-header d-flex justify-content-between align-items-center">
  <h1 class="h3 mb-0">Wyniki</h1>
  <form method="post" action="/wyniki/przelicz" class="d-flex align-items-center gap-2">
    <?= csrf_field() ?>
    <select name="game_id" class="form-select form-select-sm" required>
      <?php foreach ($games as $gid=>$g): ?>
        <option value="<?= (int)$gid ?>" <?= (!empty($gameId)&&$gameId==$gid?'selected':'') ?>><?= esc($g['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="batch_id" class="form-select form-select-sm">
      <option value="0">— bez serii —</option>
      <?php foreach ($batches as $b): ?>
        <option value="<?= (int)$b['id'] ?>" <?= (!empty($batchId)&&$batchId==$b['id']?'selected':'') ?>>#<?= (int)$b['id'] ?> (gra <?= (int)$b['game_id'] ?>)</option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-sm btn-primary">Przelicz teraz</button>
  </form>
</section>
<?= view('partials/flash') ?>

<div class="row mt-3">
  <!-- LEWA: szczegóły wybranej serii -->
  <div class="col-md-4">
    <div class="card card-outline card-primary">
      <div class="card-header">
        <strong>Szczegóły serii</strong>
        <?php if ($leftInfo): ?>
          <span class="text-muted">#<?= (int)$leftInfo['id'] ?>, gra <?= (int)$leftInfo['game_id'] ?></span>
        <?php endif; ?>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm table-striped mb-0">
          <thead>
            <tr>
              <th>#T</th>
              <th>check</th>
              <th>losowanie</th>
              <th>K(A)</th>
              <th>A</th>
              <th>K(B)</th>
              <th>B</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($tickets)): ?>
            <tr><td colspan="7" class="text-center text-muted">Wybierz serię i kliknij „Przelicz”.</td></tr>
          <?php else: foreach ($tickets as $t): ?>
            <tr>
              <td><?= (int)$t['id'] ?></td>
              <td><code><?= (int)$t['next_draw_system_id'] ?></code></td>
              <td><code><?= (int)$t['next_draw_system_id'] + 1 ?></code></td>
              <td><?= $t['k_a'] ?: '—' ?></td>
              <td><?= esc($t['numbers_a'] ?? '') ?></td>
              <td><?= $t['k_b'] ?: '—' ?></td>
              <td><?= esc($t['numbers_b'] ?? '') ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ŚRODEK: tygodnie -->
  <div class="col-md-4">
    <div class="card card-outline card-success">
      <div class="card-header"><strong>Podsumowanie tygodniowe</strong></div>
      <div class="card-body p-0">
        <table class="table table-sm table-striped mb-0">
          <thead>
            <tr>
              <th>Tydzień</th>
              <th>Od–Do</th>
              <th>Wygrane (PLN)</th>
              <th>Kupony</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($weekly)): ?>
            <tr><td colspan="4" class="text-center text-muted">Brak danych.</td></tr>
          <?php else: foreach ($weekly as $w): ?>
            <tr>
              <td><?= esc($w['yrwk']) ?></td>
              <td><small><?= esc($w['date_from']) ?>–<?= esc($w['date_to']) ?></small></td>
              <td><?= number_format((float)$w['win_sum'], 2, ',', ' ') ?></td>
              <td><?= (int)$w['tickets_evaluated'] ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- PRAWA: miesiące -->
  <div class="col-md-4">
    <div class="card card-outline card-info">
      <div class="card-header"><strong>Podsumowanie miesięczne</strong></div>
      <div class="card-body p-0">
        <table class="table table-sm table-striped mb-0">
          <thead>
            <tr>
              <th>Miesiąc</th>
              <th>Od–Do</th>
              <th>Wygrane (PLN)</th>
              <th>Kupony</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($monthly)): ?>
            <tr><td colspan="4" class="text-center text-muted">Brak danych.</td></tr>
          <?php else: foreach ($monthly as $m): ?>
            <tr>
              <td><?= esc($m['ym']) ?></td>
              <td><small><?= esc($m['date_from']) ?>–<?= esc($m['date_to']) ?></small></td>
              <td><?= number_format((float)$m['win_sum'], 2, ',', ' ') ?></td>
              <td><?= (int)$m['tickets_evaluated'] ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
