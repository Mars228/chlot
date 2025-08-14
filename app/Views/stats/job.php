<section class="content-header"><h1 class="h3">Zadanie #<?= $job['id'] ?> — <?= esc($game['name']) ?></h1></section>
<?= view('partials/flash') ?>

<div class="card card-outline card-primary mt-3">
  <div class="card-body">
    <div class="mb-2">Schemat: <strong><?= $job['scheme']==='scheme1'?'S1: x≥y':'S2: all≥y → topK' ?></strong></div>
    <div class="progress" style="height:24px;">
      <div id="bar" class="progress-bar" style="width: 0%">0%</div>
    </div>
    <div class="mt-2" id="msg"><?= esc($job['message']) ?></div>
    <div class="mt-3 d-flex gap-2">
      <button id="btnStart" class="btn btn-primary">Start/Kontynuuj</button>
      <a class="btn btn-outline-secondary" href="/statystyki">Wróć</a>
      <a class="btn btn-outline-primary" href="/statystyki/job/<?= $job['id'] ?>/edit">Edytuj parametry</a>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
  function step() {
    $.getJSON('/statystyki/job/<?= $job['id'] ?>/step?batch=300', function(r){
      if (!r.ok) { $('#msg').text(r.msg||'Błąd'); return; }
      $('#bar').css('width', r.progress+'%').text(r.progress+'%');
      if (r.msg) $('#msg').text(r.msg);
      if (r.done) {
        if (r.result_id) {
          // przejdź do wyniku
          window.location.href='/statystyki/wynik/'+r.result_id;
          return;
        }
        // jeśli jest kolejny job w serii – przejdź i autostart
        if (r.next_job_id) {
          window.location.href='/statystyki/job/'+r.next_job_id;
          return;
        }
        return;
      }
      setTimeout(step, 400);
    }).fail(function(xhr){
      $('#msg').text('Błąd: '+xhr.status+' '+xhr.responseText);
    });
  }
  $('#btnStart').on('click', step);
  // auto-start po załadowaniu widoku
$(function(){ setTimeout(step, 300); });
</script>
