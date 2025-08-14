<section class="content-header"><h1 class="h3">Nowa statystyka</h1></section>
<?= view('partials/flash') ?>

<div class="card card-outline card-primary mt-3">
  <div class="card-body">
    <form action="/statystyki/job" method="post" class="row g-3">
      <?= csrf_field() ?>

      <div class="col-md-4">
        <label class="form-label">Gra</label>
        <select class="form-select" name="game_id" id="game_id" required>
          <?php foreach ($games as $g): ?>
            <option 
  value="<?= $g['id'] ?>"
  data-latest="<?= esc($latestByGame[$g['id']] ?? '') ?>"
  data-dual="<?= ($g['range_b_min']!==null && $g['range_b_max']!==null) ? '1' : '0' ?>"
>
  <?= esc($g['name']) ?>
</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
  <label class="form-label">Start</label>
  <div class="form-control-plaintext" id="from_hint">(domyślnie: od ostatniego losowania)</div>
  <button type="button" class="btn btn-sm btn-outline-secondary mt-1" id="advToggle">Zaawansowane</button>
  <div id="advBox" class="mt-2 d-none">
    <label class="form-label">Od numeru losowania (opcjonalnie)</label>
    <input class="form-control" name="from_draw_system_id" id="from_no" placeholder="">
    <div class="form-text">Zostaw puste, aby zacząć od ostatniego losowania.</div>
  </div>
</div>

      <div class="col-md-4">
        <label class="form-label">Schemat</label>
        <select class="form-select" name="scheme" id="scheme">
          <option value="scheme1">S1: x liczb ≥ y powtórzeń (minimalne okno)</option>
          <option value="scheme2">S2: każda liczba ≥ y, potem top-K</option>
        </select>
      </div>

      <!-- S1 -->
      <div id="box-s1" class="row g-2">
        <div class="col-12"><h6 class="mb-1">Pula A</h6></div>
        <div class="col-md-3">
          <label class="form-label">x (ile liczb)</label>
          <input class="form-control" type="number" name="s1_x_a" value="28" min="1">
        </div>
        <div class="col-md-3">
          <label class="form-label">y (min. powtórzeń)</label>
          <input class="form-control" type="number" name="s1_y_a" value="1" min="1">
        </div>

        <div id="s1b" class="row g-2 mt-2 d-none">
          <div class="col-12"><h6 class="mb-1">Pula B</h6></div>
          <div class="col-md-3">
            <label class="form-label">x (ile liczb)</label>
            <input class="form-control" type="number" name="s1_x_b" value="3" min="0">
          </div>
          <div class="col-md-3">
            <label class="form-label">y (min. powtórzeń)</label>
            <input class="form-control" type="number" name="s1_y_b" value="1" min="0">
          </div>
        </div>
      </div>

      <!-- S2 -->
      <div id="box-s2" class="row g-2 d-none">
        <div class="col-12"><h6 class="mb-1">Pula A</h6></div>
        <div class="col-md-3">
          <label class="form-label">y (min. powtórzeń każdej liczby)</label>
          <input class="form-control" type="number" name="s2_min_all_a" value="1" min="1">
        </div>
        <div class="col-md-3">
          <label class="form-label">top K (wybierz)</label>
          <input class="form-control" type="number" name="s2_top_k_a" value="8" min="1">
        </div>

        <div id="s2b" class="row g-2 mt-2 d-none">
          <div class="col-12"><h6 class="mb-1">Pula B</h6></div>
          <div class="col-md-3">
            <label class="form-label">y (min. powtórzeń każdej liczby)</label>
            <input class="form-control" type="number" name="s2_min_all_b" value="1" min="0">
          </div>
          <div class="col-md-3">
            <label class="form-label">top K (wybierz)</label>
            <input class="form-control" type="number" name="s2_top_k_b" value="3" min="0">
          </div>
        </div>
      </div>

      <!-- SERIA -->
      <div class="col-12"><h6 class="mb-1">Seria</h6></div>
      <div class="col-md-4">
        <select class="form-select" name="series_mode" id="series_mode">
          <option value="one">Tylko to losowanie</option>
<option value="count">N kolejnych losowań</option>
<option value="all" selected>Cały zakres (aż do najstarszego w bazie)</option>
        </select>
      </div>
      <div class="col-md-2" id="series_count_box">
        <label class="form-label">N (liczba)</label>
        <input class="form-control" type="number" name="series_count" value="10" min="1">
      </div>

      <div class="col-12 d-flex gap-2">
        <button class="btn btn-primary">Utwórz zadanie</button>
        <a class="btn btn-secondary" href="/statystyki">Anuluj</a>
      </div>
    </form>
  </div>
</div>

<script>
  const gameSel = document.getElementById('game_id');
  const fromNo  = document.getElementById('from_no');
  const scheme  = document.getElementById('scheme');
  const s1 = document.getElementById('box-s1');
  const s2 = document.getElementById('box-s2');
  const s1b = document.getElementById('s1b');
  const s2b = document.getElementById('s2b');
  const seriesMode = document.getElementById('series_mode');
  const seriesCountBox = document.getElementById('series_count_box');

  function setupPools(){
  const opt = gameSel.options[gameSel.selectedIndex];
  const dual = (opt.getAttribute('data-dual') === '1');
  s1b.classList.toggle('d-none', !dual);
  s2b.classList.toggle('d-none', !dual);
}
  function setupScheme(){
    const v = scheme.value;
    s1.classList.toggle('d-none', v!=='scheme1');
    s2.classList.toggle('d-none', v!=='scheme2');
  }
  function setupLatest(){
    const opt = gameSel.options[gameSel.selectedIndex];
    const latest = opt.getAttribute('data-latest');
    if (latest) fromNo.placeholder = '(ostatni: '+latest+')';
  }
  function setupSeries(){
    const v = seriesMode.value;
    seriesCountBox.classList.toggle('d-none', v!=='count');
  }
  gameSel.addEventListener('change', ()=>{setupPools(); setupLatest();});
  scheme.addEventListener('change', setupScheme);
  seriesMode.addEventListener('change', setupSeries);
  setupPools(); setupScheme(); setupLatest(); setupSeries();

const advToggle = document.getElementById('advToggle');
const advBox = document.getElementById('advBox');
advToggle.addEventListener('click', ()=> advBox.classList.toggle('d-none'));


  
</script>
