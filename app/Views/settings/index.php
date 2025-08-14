<section class="content-header"><h1 class="h3">Ustawienia</h1></section>
<?= view('partials/flash') ?>

<div class="card card-outline card-primary mt-3">
  <div class="card-body">
    <form action="/ustawienia/zapisz" method="post" class="row g-3">
      <?= csrf_field() ?>
      <div class="col-md-8">
        <label class="form-label">LOTTO OpenAPI – Secret</label>
        <input class="form-control" name="lotto_api_secret" value="<?= esc($secret) ?>" placeholder="wklej klucz API" required>
        <div class="form-text">Nagłówek: <code>secret: &lt;twój_klucz&gt;</code></div>
      </div>
      <div class="col-md-4 d-flex align-items-end gap-2">
        <button class="btn btn-primary">Zapisz</button>
      </div>
    </form>
  </div>
</div>

<div class="card mt-3">
  <div class="card-body">
    <form action="/ustawienia/test-api" method="post" class="row g-2">
      <?= csrf_field() ?>
      <div class="col-md-4">
        <label class="form-label">Przetestuj połączenie dla gry</label>
        <select class="form-select" name="game_slug">
          <option value="lotto">Lotto</option>
          <option value="eurojackpot">EuroJackpot</option>
          <option value="multi-multi">Multi Multi</option>
        </select>
      </div>
      <div class="col-md-8 d-flex align-items-end gap-2">
        <button class="btn btn-outline-secondary">Przetestuj połączenie</button>
      </div>
    </form>

    <?php if (!empty($last)): ?>
      <hr>
      <div class="alert alert-success">
        <div><strong>Ostatni wynik (<?= esc($last['gameType']) ?>):</strong></div>
        <div>Numer losowania: <code><?= esc($last['drawSystemId']) ?></code></div>
        <div>Data/godz. (PL): <code><?= esc($last['drawDateLocal']) ?></code></div>
        <?php if (!empty($last['drawDateLocal'])): ?>
          <a class="btn btn-sm btn-primary mt-2" href="/losowania/pobierz?game_slug=<?= esc($last['slug']) ?>&draw_datetime=<?= esc($last['drawDateLocal']) ?>">Użyj tej daty w formularzu pobierania</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>