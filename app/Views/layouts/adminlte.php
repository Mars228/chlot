<?php /** @var string $content */ ?>
<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($title ?? 'LottoApp') ?></title>

  <!-- Bootstrap 5 (wymagany przez AdminLTE 4) -->
  <link rel="stylesheet" href="/assets/vendor/bootstrap/5.3.7/css/bootstrap.min.css" />
  <!-- AdminLTE 4 rc4 CSS -->
  <link rel="stylesheet" href="/assets/vendor/admin-lte/4.0.0-rc4/css/adminlte.min.css" />
  <link rel="stylesheet" href="/assets/vendor/toastr/2.1.4/toastr.min.css" />
  <link rel="stylesheet" href="/assets/css/site.css" />

  <!-- jQuery 3.7.x -->
<script src="/assets/vendor/jquery/jquery-3.7.1.min.js"></script>

<?= $this->renderSection('styles') ?>

</head>
<body class="layout-navbar-fixed">
<div class="wrapper">
  <!-- Top Navbar jako główna nawigacja sekcji -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom">
    <div class="container-fluid">
      <a href="/" class="navbar-brand"><strong>LottoApp</strong></a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav" aria-controls="topNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="topNav">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link" href="/">HOME</a></li>
          <li class="nav-item"><a class="nav-link" href="/gry">GRY</a></li>
          <li class="nav-item"><a class="nav-link" href="/losowania">LOSOWANIA</a></li>
          <li class="nav-item"><a class="nav-link" href="/statystyki">STATYSTYKI</a></li>
          <li class="nav-item"><a class="nav-link" href="/strategie">STRATEGIA</a></li>
          <li class="nav-item"><a class="nav-link" href="/zaklady">ZAKŁADY</a></li>
          <li class="nav-item"><a class="nav-link" href="/wyniki">WYNIKI</a></li>
          <li class="nav-item"><a class="nav-link" href="/ustawienia">USTAWIENIA</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <section class="content pt-3">
      <div class="container-fluid">
        <!-- < ?//= $content ?? '' ? >-->
        <?php if (isset($content)) : ?>
  <?= $content ?>
<?php else: ?>
  <?= $this->renderSection('content') ?>
<?php endif; ?>

      </div>
    </section>
  </div>

  <footer class="main-footer text-center small">
    <div class="float-end d-none d-sm-inline">Etap 1: GRY</div>
    <strong>&copy; <?= date('Y') ?> LottoApp.</strong>
  </footer>
</div>


<!-- Bootstrap 5 JS -->
<script src="/assets/vendor/bootstrap/5.3.7/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE 4 rc4 JS -->
<script src="/assets/vendor/admin-lte/4.0.0-rc4/js/adminlte.min.js"></script>

<!-- Inputmask (jak w AdminLTE) -->
<script src="/assets/vendor/inputmask/jquery.inputmask.min.js"></script>

<script src="/assets/vendor/toastr/2.1.4/toastr.min.js"></script>


<?= $this->renderSection('scripts') ?>

<?= view('partials/flash') ?>

</body>
</html>