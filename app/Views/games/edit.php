<section class="content-header"><h1 class="h3">Edytuj grę</h1></section>
<?= view('partials/flash') ?>
<div class="card card-outline card-primary mt-3">
  <div class="card-body">
    <form action="/gry/<?= $game['id'] ?>/aktualizuj" method="post" enctype="multipart/form-data" class="row g-3">
      <?= csrf_field() ?>
      <div class="col-md-6">
        <label class="form-label">Nazwa</label>
        <input name="name" class="form-control" required value="<?= esc($game['name']) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Slug</label>
        <input name="slug" class="form-control" required value="<?= esc($game['slug']) ?>">
      </div>
      <div class="col-12">
        <label class="form-label">Opis</label>
        <textarea name="description" class="form-control" rows="3"><?= esc($game['description']) ?></textarea>
      </div>
      <div class="col-md-4">
        <label class="form-label">Cena domyślna (PLN)</label>
        <input name="default_price" class="form-control" type="number" step="0.01" value="<?= esc($game['default_price']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Logo</label>
        <input name="logo" class="form-control" type="file" accept=".png,.jpg,.jpeg,.svg">
        <?php if (!empty($game['logo_path'])): ?>
          <small class="text-muted">Aktualne: <?= esc($game['logo_path']) ?></small>
        <?php endif; ?>
      </div>
      <div class="col-md-4 d-flex align-items-end">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" <?= $game['is_active'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="is_active">Aktywna</label>
        </div>
      </div>
      <div class="col-12">
        <button class="btn btn-primary">Zapisz</button>
        <a class="btn btn-secondary" href="/gry">Anuluj</a>
      </div>
    </form>
  </div>
</div>