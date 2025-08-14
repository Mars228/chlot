<section class="content-header d-flex justify-content-between align-items-center">
  <h1 class="h3 mb-0">Zakłady — nowa seria</h1>
</section>
<?= view('partials/flash') ?>

<div class="card card-outline card-primary mt-3">
  <div class="card-body">
    <form method="post" action="/zaklady" class="row g-3" id="betForm">
      <?= csrf_field() ?>

      <div class="col-md-3">
        <label class="form-label">Strategia</label>
        <select name="stype" class="form-select">
          <option value="SIMPLE">SIMPLE</option>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label">Gra</label>
        <select name="game_id" id="game_id" class="form-select" required>
          <?php foreach ($games as $gid=>$g): ?>
            <option value="<?= (int)$gid ?>"><?= esc($g['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label">Schemat</label>
        <select name="schema_id" id="schema_id" class="form-select">
          <option value="0">— dowolny —</option>
          <?php foreach ($schemas as $sid=>$s): ?>
            <option value="<?= (int)$sid ?>" data-game="<?= (int)$s['game_id'] ?>">#<?= (int)$sid ?> <?= esc($s['scheme']) ?></option>
          <?php endforeach; ?>
        </select>
        <div class="form-text">Lista filtrowana po wybranej grze.</div>
      </div>

      <div class="col-md-3">
        <label class="form-label">Kuponów na (strategię × K)</label>
        <input type="number" name="per_strategy" class="form-control" min="1" value="1">
      </div>

      <div class="col-12"><hr></div>

      <div class="col-md-3">
        <label class="form-label">Zakres ID strategii (od)</label>
        <input type="number" name="strategy_id_from" class="form-control" placeholder="np. 1001">
      </div>
      <div class="col-md-3">
        <label class="form-label">Zakres ID strategii (do)</label>
        <input type="number" name="strategy_id_to" class="form-control" placeholder="np. 1200">
      </div>
      <div class="col-md-3">
        <label class="form-label">Albo ostatnie N strategii</label>
        <input type="number" name="last_n" class="form-control" placeholder="np. 200">
      </div>
      <div class="col-md-3 d-flex align-items-end">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="include_random_baseline" id="baseline" value="1" checked>
          <label class="form-check-label" for="baseline">Dodaj porównawcze (losowe)</label>
        </div>
      </div>

      <div class="col-12 d-flex justify-content-end">
        <a href="/zaklady" class="btn btn-secondary me-2">Anuluj</a>
        <button class="btn btn-primary" id="btnGenerate" type="submit">Generuj</button>
      </div>
    </form>
    
<!-- Modal progresu sterowany jQuery (bez Bootstrap JS) -->
<div id="betProgressModal" class="jlte-modal" aria-hidden="true" style="display:none;">
  <div class="jlte-dialog">
    <div class="jlte-header">
      <div class="jlte-title">Generowanie zakładów…</div>
      <button type="button" class="btn btn-sm btn-outline-secondary" id="betCloseX">×</button>
    </div>
    <div class="jlte-body">
      <div class="progress">
        <div id="betProgressBar" class="progress-bar progress-bar-striped progress-bar-animated"
             role="progressbar" style="width:0%">0%</div>
      </div>
      <div class="mt-2 small text-muted" id="betProgressMsg">Przygotowanie…</div>
    </div>
    <div class="jlte-footer">
      <button type="button" class="btn btn-outline-secondary" id="betCancelBtn">Anuluj</button>
    </div>
  </div>
</div>


  </div>
</div>

<script>
// zależny select schematów (jak w Strategiach)
(function(){
  const g = document.getElementById('game_id');
  const s = document.getElementById('schema_id');
  if (!g || !s) return;
  const base = s.querySelector('option[value="0"]');
  const all  = Array.from(s.querySelectorAll('option')).slice(1);
  function rebuild(){
    const gid = parseInt(g.value||'0',10);
    const current = s.value;
    s.innerHTML = '';
    s.appendChild(base.cloneNode(true));
    const opts = all.filter(o => gid===0 || parseInt(o.getAttribute('data-game')||'0',10)===gid);
    opts.forEach(o => s.appendChild(o.cloneNode(true)));
    if (!Array.from(s.options).some(o=>o.value===current)) s.value='0';
  }
  rebuild();
  g.addEventListener('change', rebuild);
})();
</script>

<script>
$(function(){

  // --- elementy
  var $form = $('#betForm');
  var $modal = $('#betProgressModal');
  var $bar = $('#betProgressBar');
  var $msg = $('#betProgressMsg');
  var $btnCancel = $('#betCancelBtn');
  var $btnCloseX = $('#betCloseX');

  var cancelled = false;

  function showModal(){
    cancelled = false;
    $bar.css('width','0%').text('0%');
    $msg.text('Przygotowanie…');
    $modal.fadeIn(120);
  }
  function hideModal(){
    $modal.fadeOut(120);
  }
  function setProgress(pct, text){
    pct = Math.max(0, Math.min(100, Math.round(pct)));
    $bar.css('width', pct+'%').text(pct+'%');
    if (text) $msg.text(text);
  }

  function step(batchId, total){
    if (cancelled) return;
    $.ajax({
      url: '/zaklady/step/'+batchId,
      data: { limit: 50 },
      method: 'GET',
      dataType: 'json'
    }).done(function(r){
      if (!r || !r.ok) {
        hideModal();
        if (window.toastr) toastr.error((r && r.msg) ? r.msg : 'Błąd step()');
        return;
      }
      var proc = r.processed || 0;
      var pct  = total ? (proc/total*100.0) : 0;
      setProgress(pct, 'Przetworzono '+proc+' / '+total+' | dodano kuponów: '+(r.added||0));
      if (r.status === 'done' || proc >= total) {
        setProgress(100, 'Zakończono. Przekierowanie…');
        setTimeout(function(){ window.location.href = '/zaklady/seria/'+batchId; }, 600);
      } else {
        setTimeout(function(){ step(batchId, total); }, 200);
      }
    }).fail(function(xhr){
      hideModal();
      if (window.toastr) toastr.error('HTTP '+xhr.status+' '+(xhr.responseText||''));
    });
  }

  // przechwycenie submitu (delegacja + pewniak)
  $(document).on('submit', '#betForm', function(ev){
    ev.preventDefault(); // KLUCZ: nie pozwól iść do /zaklady (store)
    showModal();

    // Wyślij start AJAXem
    var payload = $form.serialize();
    $.ajax({
      url: '/zaklady/start',
      method: 'POST',
      data: payload,
      headers: { 'X-Requested-With':'XMLHttpRequest' }, // gwarant dla isAJAX()
      dataType: 'json'
    }).done(function(r){
      if (!r || !r.ok) {
        hideModal();
        if (window.toastr) toastr.error((r && r.msg) ? r.msg : 'Błąd start()');
        return;
      }
      step(r.batchId, r.total || 0);
    }).fail(function(xhr){
      hideModal();
      if (window.toastr) toastr.error('HTTP '+xhr.status+' '+(xhr.responseText||''));
    });

    return false; // dodatkowy bezpiecznik
  });

  // Anulowanie
  $btnCancel.on('click', function(){ cancelled = true; hideModal(); });
  $btnCloseX.on('click', function(){ cancelled = true; hideModal(); });

});
</script>
