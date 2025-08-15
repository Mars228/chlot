<section class="content-header">
	<h1 class="h3">Edytuj schemat #<?= $s['id'] ?> — <?= esc($game['name']) ?></h1>
</section>

<?= view('partials/flash') ?>

<?php $hasB = ($game['range_b_min'] !== null && $game['range_b_max'] !== null); ?>

<div class="card card-outline card-primary mt-3">

	<div class="card-body">
	
		<form action="/statystyki/schemat/<?= $s['id'] ?>/zapisz" method="post" class="row g-3">

			<?= csrf_field() ?>

			<div class="col-12">
				<label class="form-label">Nazwa schematu</label>
				<input type="text" name="name" class="form-control" value="<?= esc(old('name', $s['name'] ?? '')) ?>">

				<select name="scheme" class="form-select" required>
				<option value="scheme1" <?= (($s['scheme']??'')==='scheme1')?'selected':'' ?>>S1: x≥y</option>
				<option value="scheme2" <?= (($s['scheme']??'')==='scheme2')?'selected':'' ?>>S2: all≥y → topK</option>
			</select>
			</div>



			<?php if ($s['scheme']==='scheme1'): ?>
			<div class="col-12"><h6>S1: x liczb ≥ y powtórzeń</h6></div>
			<div class="col-md-3">
				<label class="form-label">x (A)</label>
				<input class="form-control" type="number" name="s1_x_a" value="<?= esc(old('x_a', $params['x_a'] ?? '')) ?>" min="1">
			</div>
			<div class="col-md-3">
				<label class="form-label">y (A)</label>
				<input class="form-control" type="number" name="s1_y_a" value="<?= esc(old('y_a', $params['y_a'] ?? '')) ?>" min="1">
			</div>

			<?php if ($hasB): ?>
			<div class="col-12"><h6 class="mb-1">Pula B</h6></div>
			<div class="col-md-3">
				<label class="form-label">x (B)</label>
				<input class="form-control" type="number" name="s1_x_b" value="<?= esc(old('x_b', $params['x_b'] ?? '')) ?>" min="0">
			</div>
			<div class="col-md-3">
				<label class="form-label">y (B)</label>
				<input class="form-control" type="number" name="s1_y_b" value="<?= esc(old('y_b', $params['y_b'] ?? '')) ?>" min="0">
			</div>

			<?php else: ?>
			<input type="hidden" name="s1_x_b" value="0">
			<input type="hidden" name="s1_y_b" value="0">

			<?php endif; ?>

			<?php else: ?>
			<div class="col-12"><h6>S2: każda liczba ≥ y → topK</h6></div>
			<div class="col-md-3">
				<label class="form-label">min_all (A)</label>
				<input class="form-control" type="number" name="s2_min_all_a" value="<?= esc(old('min_all_a', $params['min_all_a'] ?? '')) ?>" min="1">
			</div>
			<div class="col-md-3">
				<label class="form-label">top K (A)</label>
				<input class="form-control" type="number" name="s2_top_k_a" value="<?= esc(old('top_k_a', $params['top_k_a']   ?? '')) ?>" min="1">
			</div>

			<?php if ($hasB): ?>
			<div class="col-12"><h6 class="mb-1">Pula B</h6></div>\
				<div class="col-md-3"><label class="form-label">min_all (B)</label>
				<input class="form-control" type="number" name="s2_min_all_b" value="<?= esc(old('min_all_b', $params['min_all_b'] ?? '')) ?>>" min="0">
			</div>
			<div class="col-md-3">
				<label class="form-label">top K (B)</label>
				<input class="form-control" type="number" name="s2_top_k_b" value="<?= esc(old('top_k_b', $params['top_k_b']   ?? '')) ?>>" min="0">
			</div>

			<?php else: ?>
			<input type="hidden" name="s2_min_all_b" value="0">
			<input type="hidden" name="s2_top_k_b" value="0">
			<?php endif; ?>
			<?php endif; ?>

			<div class="col-12 d-flex gap-2">
				<button class="btn btn-primary">Zapisz</button>
				<a class="btn btn-secondary" href="/statystyki">Anuluj</a>
			</div>
		</form>
		
	</div>
</div>
