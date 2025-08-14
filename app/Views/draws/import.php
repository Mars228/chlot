<section class="content-header"><h1 class="h3">Import losowań (CSV)</h1></section>
<?= view('partials/flash') ?>
<div class="card card-outline card-primary mt-3">
  <div class="card-body">
    <form action="/losowania/import" method="post" enctype="multipart/form-data" class="row g-3">
      <?= csrf_field() ?>
      <div class="col-md-4">
        <label class="form-label">Gra</label>
        <select class="form-select" name="game_id" required>
          <?php foreach ($games as $g): ?>
            <option value="<?= $g['id'] ?>"><?= esc($g['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-8">
        <label class="form-label">Plik CSV</label>
        <input class="form-control" type="file" name="csv" accept=".csv,text/csv" required>
        <div class="form-text">Format wiersza: <code>numer;data;godzina;liczby_a;liczby_b</code>. Separator: <code>;</code> lub <code>,</code>. Przykład: <code>1234;2025-05-29;20:00;1,2,3,4,5,6;</code></div>
      </div>
      <div class="col-12">
        <button class="btn btn-primary">Importuj</button>
        <a class="btn btn-secondary" href="/losowania">Anuluj</a>
      </div>
    </form>
  </div>
</div>