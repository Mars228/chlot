<section class="content-header">
  <div class="d-flex justify-content-between align-items-center">
    <h1 class="h3 mb-0">Gry</h1>
    <a href="/gry/nowa" class="btn btn-primary">Dodaj grę</a>
  </div>
</section>

<?= view('partials/flash') ?>

<div class="card card-outline card-primary mt-3">
  <div class="card-body p-0">
    <table class="table table-striped table-hover mb-0">
      <thead>
        <tr>
          <th>Logo</th>
          <th>Nazwa</th>
          <th>Slug</th>
          <th>Status</th>
          <th class="text-end">Akcje</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($games as $g): ?>
        <tr>
          <td style="width:60px;">
            <?php if (!empty($g['logo_path'])): ?>
              <img class="logo-img" src="<?= esc($g['logo_path']) ?>" alt="logo">
            <?php endif; ?>
          </td>
          <td><?= esc($g['name']) ?></td>
          <td><code><?= esc($g['slug']) ?></code></td>
          <td><?= $g['is_active'] ? '<span class="badge bg-success">aktywna</span>' : '<span class="badge bg-secondary">wyłączona</span>' ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="/gry/<?= $g['id'] ?>">Szczegóły</a>
            <a class="btn btn-sm btn-outline-secondary" href="/gry/<?= $g['id'] ?>/edytuj">Edytuj</a>
            <form action="/gry/<?= $g['id'] ?>/usun" method="post" class="d-inline" onsubmit="return confirm('Usunąć grę?');">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-danger">Usuń</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="card-footer">
    <?= $pager->links() ?>
  </div>
</div>