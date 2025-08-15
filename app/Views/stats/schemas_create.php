<section class="content-header">
	<h1 class="h3">Nowy schemat statystyk</h1>
</section>
<?= view('partials/flash') ?>

<div class="card card-outline card-primary mt-3">
	<div class="card-body">

		<form action="/statystyki/nowy" method="post" class="row g-3">
			
			<?= csrf_field() ?>
			
			<div class="col-md-4">
				<label class="form-label">Gra</label>
				<select class="form-select" name="game_id" id="game_id" required>
					<?php foreach ($games as $g): ?>
					<option value="<?= $g['id'] ?>" data-dual="<?= ($g['range_b_min']!==null && $g['range_b_max']!==null) ? '1' : '0' ?>">
						<?= esc($g['name']) ?>
					</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div class="col-md-4">
				<label class="form-label">Schemat</label>
				<select class="form-select" name="scheme" id="scheme">
					<option value="scheme1">S1: x liczb ≥ y powtórzeń</option>
					<option value="scheme2">S2: każda liczba ≥ y → topK</option>
				</select>
			</div>

			<div class="col-md-4">
				<label class="form-label">Nazwa schematu (opcjonalnie)</label>
				<input type="text" name="name" class="form-control" value="<?= esc(old('name','')) ?>" placeholder="np. Multi 28≥1 od #16000">
				<div class="form-text">Brak nazwy → zostanie nadana automatycznie z parametrów.</div>
			</div>

	  <!-- S1 -->
	  <div id="box-s1" class="row g-2">
		<div class="col-12"><h6 class="mb-1">Pula A</h6></div>
		<div class="col-md-3">
		  <label class="form-label">x (ile liczb)</label>
		  <input class="form-control" type="number" name="s1_x_a" value="<?= esc(old('x_a','')) ?>" min="1">
		</div>
		<div class="col-md-3">
		  <label class="form-label">y (min. powtórzeń)</label>
		  <input class="form-control" type="number" name="s1_y_a" value="<?= esc(old('y_a','')) ?>" min="1">
		</div>

		<div id="s1b" class="row g-2 mt-2 d-none">
		  <div class="col-12"><h6 class="mb-1">Pula B</h6></div>
		  <div class="col-md-3">
			<label class="form-label">x (ile liczb)</label>
			<input class="form-control" type="number" name="s1_x_b" value="<?= esc(old('x_b','')) ?>" min="0">
		  </div>
		  <div class="col-md-3">
			<label class="form-label">y (min. powtórzeń)</label>
			<input class="form-control" type="number" name="s1_y_b" value="<?= esc(old('y_b','')) ?>" min="0">
		  </div>
		</div>
	  </div>

	  <!-- S2 -->
	  <div id="box-s2" class="row g-2 d-none">
		<div class="col-12"><h6 class="mb-1">Pula A</h6></div>
		<div class="col-md-3">
		  <label class="form-label">y (min. powtórzeń każdej liczby)</label>
		  <input class="form-control" type="number" name="s2_min_all_a" value="<?= esc(old('min_all_a','')) ?>" min="1">
		</div>
		<div class="col-md-3">
		  <label class="form-label">top K (wybierz)</label>
		  <input class="form-control" type="number" name="s2_top_k_a" value="<?= esc(old('top_k_a','')) ?>" min="1">
		</div>

		<div id="s2b" class="row g-2 mt-2 d-none">
		  <div class="col-12"><h6 class="mb-1">Pula B</h6></div>
		  <div class="col-md-3">
			<label class="form-label">y (min. powtórzeń każdej liczby)</label>
			<input class="form-control" type="number" name="s2_min_all_b" value="<?= esc(old('min_all_b','')) ?>" min="0">
		  </div>
		  <div class="col-md-3">
			<label class="form-label">top K (wybierz)</label>
			<input class="form-control" type="number" name="s2_top_k_b" value="<?= esc(old('top_k_b','')) ?>" min="0">
		  </div>
		</div>
	  </div>

	  <div class="col-12 d-flex gap-2">
		<button class="btn btn-primary">Zapisz schemat</button>
		<a class="btn btn-secondary" href="/statystyki">Anuluj</a>
	  </div>
	</form>
  </div>
</div>

<script>
  const gameSel = document.getElementById('game_id');
  const scheme  = document.getElementById('scheme');
  const s1 = document.getElementById('box-s1');
  const s2 = document.getElementById('box-s2');
  const s1b = document.getElementById('s1b');
  const s2b = document.getElementById('s2b');

  function setupPools(){
  const opt = gameSel.options[gameSel.selectedIndex];
  const dual = (opt.getAttribute('data-dual') === '1');

  // pokaż/ukryj sekcje
  s1b.classList.toggle('d-none', !dual);
  s2b.classList.toggle('d-none', !dual);

  // wylacz/wyzeruj inputy B gdy brak puli B (żeby nie wysyłały domyślnych wartości)
  const bInputs = s1b.querySelectorAll('input, select');
  bInputs.forEach(el => { el.disabled = !dual; if (!dual) el.value = 0; });

  const bInputs2 = s2b.querySelectorAll('input, select');
  bInputs2.forEach(el => { el.disabled = !dual; if (!dual) el.value = 0; });
}

  function setupScheme(){
	const v = scheme.value;
	s1.classList.toggle('d-none', v!=='scheme1');
	s2.classList.toggle('d-none', v!=='scheme2');
  }
  gameSel.addEventListener('change', setupPools);
  scheme.addEventListener('change', setupScheme);
  setupPools(); setupScheme();
</script>
