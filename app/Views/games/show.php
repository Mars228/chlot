<?php
/** @var array $game */
helper('format'); // jeśli chcesz użyć pretty_numbers gdzieś indziej
?>
<section class="content-header">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
    <h1 class="h3 mb-0"><?= esc($game['name']) ?></h1>
    <div class="btn-group">
      <a class="btn btn-outline-primary" href="/gry/edytuj/<?= $game['id'] ?>">Edytuj</a>
      <a class="btn btn-outline-secondary" href="/gry">Wróć do listy</a>
    </div>
  </div>
</section>

<div class="row mt-3">
  <div class="col-md-4">
    <div class="card h-100">
      <div class="card-body d-flex align-items-center justify-content-center" style="min-height:220px">
        <?php
          $logo = $game['logo_path'] ?? '';
          $logoUrl = $logo ? base_url($logo) : base_url('assets/img/no-logo.svg');
        ?>
        <img src="<?= esc($logoUrl) ?>" alt="Logo <?= esc($game['name']) ?>" style="max-width:100%; max-height:180px; object-fit:contain;">
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card">
      <div class="card-body">
        <?php if (!empty($game['description'])): ?>
          <p class="mb-3"><?= nl2br(esc($game['description'])) ?></p>
        <?php endif; ?>

        <dl class="row mb-0">
          <dt class="col-sm-4">Cena 1 zakładu</dt>
          <dd class="col-sm-8">
            <?= number_format((float)($game['default_price'] ?? 0), 2, ',', ' ') ?> PLN
          </dd>

          <?php if (!empty($game['range_a_min']) && !empty($game['range_a_max'])): ?>
            <dt class="col-sm-4">Zakres A / wybór</dt>
            <dd class="col-sm-8">
              <?= (int)$game['range_a_min'] ?>–<?= (int)$game['range_a_max'] ?>
              <?php if (!empty($game['picks_a_min']) || !empty($game['picks_a_max'])): ?>
                &nbsp;•&nbsp; typujesz:
                <?php
                  $paMin = (int)($game['picks_a_min'] ?? 0);
                  $paMax = (int)($game['picks_a_max'] ?? 0);
                  echo $paMin === $paMax ? $paMin : ($paMin.'–'.$paMax);
                ?>
              <?php endif; ?>
            </dd>
          <?php endif; ?>

          <?php if (!empty($game['range_b_min']) && !empty($game['range_b_max'])): ?>
            <dt class="col-sm-4">Zakres B / wybór</dt>
            <dd class="col-sm-8">
              <?= (int)$game['range_b_min'] ?>–<?= (int)$game['range_b_max'] ?>
              <?php if (!empty($game['picks_b_min']) || !empty($game['picks_b_max'])): ?>
                &nbsp;•&nbsp; typujesz:
                <?php
                  $pbMin = (int)($game['picks_b_min'] ?? 0);
                  $pbMax = (int)($game['picks_b_max'] ?? 0);
                  echo $pbMin === $pbMax ? $pbMin : ($pbMin.'–'.$pbMax);
                ?>
              <?php endif; ?>
            </dd>
          <?php endif; ?>

          <!-- UWAGA: status/is_active celowo NIE wyświetlamy (Twoja prośba) -->
          <?php if (!empty($game['draw_no_transform_json'])): 
  $t = json_decode((string)$game['draw_no_transform_json'], true);
  if (is_array($t) && ($t['type'] ?? '') === 'offset'): ?>
    <dt class="col-sm-4">Numeracja (offset)</dt>
    <dd class="col-sm-8">external + <?= (int)($t['b'] ?? 0) ?></dd>
<?php endif; endif; ?>
        </dl>
      </div>
    </div>
  </div>
</div>

<?php
// ===== WYSOKOŚĆ WYGRANYCH =====
// Oczekujemy JSON w polu games.payout_schema_json,
// opis formatu masz pod widokiem (sekcja 2 poniżej).
$schema = null;
if (!empty($game['payout_schema_json'])) {
    $schema = json_decode((string)$game['payout_schema_json'], true);
}
?>

<div class="card card-outline card-primary mt-3">
  <div class="card-header">
    <h3 class="card-title">Wysokość wygranych</h3>
  </div>
  <div class="card-body">
    <?php if (!$schema): ?>
      <div class="alert alert-warning mb-0">
        Brak zdefiniowanych zasad wypłat dla tej gry.
        <br>Możesz je uzupełnić w edycji gry (pole JSON) lub seederem – patrz instrukcja poniżej.
      </div>
    <?php else: ?>
      <?php
        $unit = $schema['unit'] ?? 'PLN'; // 'PLN' albo 'coef' (mnożnik stawki)
        $unitLabel = $unit === 'coef' ? '× stawka' : 'PLN';
      ?>

      <?php if (($schema['type'] ?? '') === 'single'): ?>
        <?php
          // tables: { "K": { "hits" : payout, ... }, ... }
          $tables = $schema['tables'] ?? [];
          ksort($tables, SORT_NUMERIC);
        ?>
        <?php foreach ($tables as $typedK => $map): ?>
          <h6 class="mt-2 mb-2">Typujesz <?= (int)$typedK ?> liczb</h6>
          <div class="table-responsive mb-3">
            <table class="table table-sm table-bordered align-middle">
              <thead>
                <tr>
                  <th style="width:150px">Trafienia</th>
                  <th>Wygrana (<?= esc($unitLabel) ?>)</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  krsort($map, SORT_NUMERIC); // zaczynaj od największych trafień
                  foreach ($map as $hits => $payout):
                ?>
                  <tr>
                    <td><?= (int)$hits ?></td>
                    <td><?= is_numeric($payout) ? number_format((float)$payout, ($unit==='PLN'?2:2), ',', ' ') : esc($payout) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endforeach; ?>

      <?php elseif (($schema['type'] ?? '') === 'dual_fixed'): ?>
        <?php
          // dual_fixed: picks_a, picks_b, table: { "ha,hb": payout }
          $pA = (int)($schema['picks_a'] ?? 0);
          $pB = (int)($schema['picks_b'] ?? 0);
          $table = $schema['table'] ?? [];
          // posortuj: najpierw większe trafienia A, potem B
          uksort($table, function($ka, $kb){
              [$a1,$a2] = array_map('intval', explode(',', $ka));
              [$b1,$b2] = array_map('intval', explode(',', $kb));
              return ($b1 <=> $a1) ?: ($b2 <=> $a2);
          });
        ?>
        <div class="mb-2 text-muted">Typujesz A/B: <?= $pA ?>/<?= $pB ?></div>
        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle">
            <thead>
              <tr>
                <th style="width:160px">Trafienia A (z <?= $pA ?>)</th>
                <th style="width:160px">Trafienia B (z <?= $pB ?>)</th>
                <th>Wygrana (<?= esc($unitLabel) ?>)</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($table as $key => $payout):
                [$ha,$hb] = array_map('intval', explode(',', $key));
              ?>
                <tr>
                  <td><?= $ha ?></td>
                  <td><?= $hb ?></td>
                  <td><?= is_numeric($payout) ? number_format((float)$payout, ($unit==='PLN'?2:2), ',', ' ') : esc($payout) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      <?php else: ?>
        <div class="alert alert-warning mb-0">
          Nieznany typ schematu wypłat: <code><?= esc($schema['type'] ?? '—') ?></code>.
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Podpowiedź formatu JSON (do uzupełnienia w edycji gry / seederem) -->
<div class="alert alert-secondary mt-3">
  <details>
    <summary><strong>Instrukcja formatu "Wysokość wygranych" (JSON)</strong></summary>
    <div class="mt-2">
      <p class="mb-1"><strong>Jedna pula (Lotto / Multi Multi):</strong></p>
      <pre class="mb-3" style="white-space:pre-wrap"><?= esc(json_encode([
        "type" => "single",
        "unit" => "PLN", // lub "coef"
        "tables" => [
          "6" => ["3" => 24.00, "4" => 100.00, "5" => 3500.00, "6" => 3000000.00], // Lotto: typujesz 6 liczb
          "8" => ["4" => 10.00, "5" => 25.00], // Multi Multi: przykładowe wartości dla K=8
          "9" => ["4" => 8.00, "5" => 18.00],
          "10"=> ["5" => 5.00, "6" => 12.00]
        ]
      ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>

      <p class="mb-1"><strong>Dwie pule (EuroJackpot):</strong></p>
      <pre style="white-space:pre-wrap"><?= esc(json_encode([
        "type" => "dual_fixed",
        "unit" => "PLN", // lub "coef"
        "picks_a" => 5,
        "picks_b" => 2,
        "table" => [
          "5,2" => 100000000.00,
          "5,1" => 2000000.00,
          "5,0" => 800000.00,
          "4,2" => 5000.00,
          "4,1" => 400.00,
          "3,2" => 100.00
        ]
      ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>

      <p class="mb-0 small text-muted">
        <em>Uwaga:</em> wartości są <strong>przykładowe</strong>. Możesz wpisywać kwoty w PLN lub mnożnik stawki (ustaw <code>unit</code> na <code>coef</code> i podawaj np. <code>1.5</code> = 1.5× stawki).
      </p>
    </div>
  </details>
</div>
