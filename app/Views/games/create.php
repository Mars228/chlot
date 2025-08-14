<section class="content-header"><h1 class="h3">Nowa gra</h1></section>
<?= view('partials/flash') ?>
<div class="card card-outline card-primary mt-3">
  <div class="card-body">
    <form action="/gry/zapisz" method="post" enctype="multipart/form-data" class="row g-3">
      <?= csrf_field() ?>
      <div class="col-md-6">
        <label class="form-label">Nazwa</label>
        <input name="name" class="form-control" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Slug</label>
        <input name="slug" class="form-control" placeholder="np. multi-multi" required>
      </div>
      <div class="col-12">
        <label class="form-label">Opis</label>
        <textarea name="description" class="form-control" rows="3"></textarea>
      </div>
      <div class="col-md-4">
        <label class="form-label">Cena domyślna (PLN)</label>
        <input name="default_price" class="form-control" type="number" step="0.01">
      </div>
      <div class="col-md-4">
        <label class="form-label">Logo</label>
        <input name="logo" class="form-control" type="file" accept=".png,.jpg,.jpeg,.svg">
      </div>
      <div class="col-md-4 d-flex align-items-end">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="is_active">
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