<section class="content-header"><h1 class="h3">Pobierz z Lotto OpenAPI</h1></section>
<?= view('partials/flash') ?>

<div class="card card-outline card-primary mt-3">
  <div class="card-body">
    <form action="/losowania/pobierz" method="post" class="row g-3" id="fetchForm">
      <?= csrf_field() ?>

      <div class="col-md-4">
        <label class="form-label">Gra</label>
        <select class="form-select" name="game_slug" id="game_slug" required>
          <?php foreach ($games as $g): ?>
            <option value="<?= esc($g['slug']) ?>" <?= (!empty($prefillSlug) && $prefillSlug==$g['slug'])?'selected':'' ?>><?= esc($g['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
  <label class="form-label mb-1">Data (dd/mm/yyyy)</label>
  <input
    class="form-control"
    type="text"
    name="drawDate"
    id="draw_date"
    inputmode="numeric"
    placeholder="dd/mm/yyyy"
    data-mask-date
    value="<?= esc(old('drawDate', '')) ?>"
  >

  <label class="form-label mt-3 mb-1">Godzina (hh:mm)</label>
  <input
    class="form-control"
    type="text"
    name="drawTime"
    id="draw_time"
    inputmode="numeric"
    placeholder="hh:mm"
    data-mask-time
    value="<?= esc(old('drawTime', '20:00')) ?>"
  >

  <!-- Ukryte pole kompatybilne ze starym backendem (YYYY-MM-DDTHH:mm) -->
  <input type="hidden" name="draw_datetime" id="draw_datetime" value="<?= esc($prefillDt ?? '') ?>">

  <div class="form-text">Czas lokalny (Europe/Warsaw). Zostanie przeliczone do UTC w kontrolerze.</div>
</div>

      <div class="col-md-4 d-flex align-items-end">
        <button class="btn btn-primary">Pobierz i zapisz</button>
        <a class="btn btn-secondary ms-2" href="/losowania">Wróć</a>
      </div>
    </form>
  </div>
</div>

<div class="card mt-3">
  <div class="card-body">
    <div class="d-flex gap-2 align-items-center">
      <button class="btn btn-outline-secondary" id="btnTest">Sprawdź ostatnie (tester)</button>
      <span id="testInfo" class="text-muted"></span>
    </div>
  </div>
</div>

<script>
$(function () {
  // ——— Inicjalizacja masek (proste, pewne) ———
  $('[data-mask-date]').inputmask('99/99/9999', {
    placeholder: 'dd/mm/yyyy',
    showMaskOnHover: false,
    clearIncomplete: true
  });
  $('[data-mask-time]').inputmask('99:99', {
    placeholder: 'hh:mm',
    showMaskOnHover: false,
    clearIncomplete: true
  });

  // Helpery
  function ymdToDmy(ymd) {
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(ymd);
    return m ? `${m[3]}/${m[2]}/${m[1]}` : '';
  }
  function rebuildHiddenIfComplete() {
    const d = $('#draw_date').val().trim();
    const t = $('#draw_time').val().trim();
    if (/^\d{2}\/\d{2}\/\d{4}$/.test(d) && /^\d{2}:\d{2}$/.test(t)) {
      const dm = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(d);
      $('#draw_datetime').val(`${dm[3]}-${dm[2]}-${dm[1]}T${t}`); // YYYY-MM-DDTHH:mm
    }
  }

  // Aktualizuj hidden po uzupełnieniu pól (inputmask emituje 'complete')
  $('#draw_date').on('complete input change blur', rebuildHiddenIfComplete);
  $('#draw_time').on('complete input change blur', rebuildHiddenIfComplete);

  // Prefill z ukrytego pola (np. po „testerze” lub starym prefillu)
  (function prefillFromHidden() {
    const isoLocal = $('#draw_datetime').val();
    if (!isoLocal) return;
    const parts = isoLocal.split('T');
    if (parts.length === 2) {
      $('#draw_date').val( ymdToDmy(parts[0]) ).trigger('input');
      $('#draw_time').val( parts[1].slice(0,5) ).trigger('input');
    }
  })();

  // „Sprawdź ostatnie (tester)” — jQuery
  $('#btnTest').on('click', function (e) {
    e.preventDefault();
    const slug = $('#game_slug').val();
    $('#testInfo').text('Sprawdzam...');
    $.getJSON('/losowania/test-latest', { game_slug: slug })
      .done(function (r) {
        if (!r || !r.ok) {
          $('#testInfo').text((r && r.msg) ? r.msg : 'Błąd');
          return;
        }
        let s = 'Ostatni: nr ' + r.drawSystemId;
        if (r.drawDateLocal) s += ' | ' + r.drawDateLocal.replace('T',' ');
        $('#testInfo').text(s);

        // wypełnij widoczne pola i hidden (maski same to ogarną)
        if (r.drawDateLocal) {
          const p = r.drawDateLocal.split('T');
          $('#draw_date').val( ymdToDmy(p[0]) ).trigger('input');
          $('#draw_time').val( p[1].slice(0,5) ).trigger('input');
          $('#draw_datetime').val(r.drawDateLocal);
        }
      })
      .fail(function (xhr) {
        $('#testInfo').text('Błąd: ' + xhr.status + ' ' + (xhr.responseText || ''));
      });
  });
});
</script>
