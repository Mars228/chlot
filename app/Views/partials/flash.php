<?php
$success = session()->getFlashdata('success');
$error   = session()->getFlashdata('error');
$errors  = session()->getFlashdata('errors'); // może być string lub array
$warning = session()->getFlashdata('warning');
$info    = session()->getFlashdata('info');

$js = [];
$js[] = "toastr.options = { closeButton:true, progressBar:true, positionClass:'toast-top-right', timeOut: 4000 };";

if ($success) $js[] = "toastr.success(".json_encode($success, JSON_UNESCAPED_UNICODE).");";
if ($error)   $js[] = "toastr.error(".json_encode($error,   JSON_UNESCAPED_UNICODE).");";
if ($warning) $js[] = "toastr.warning(".json_encode($warning, JSON_UNESCAPED_UNICODE).");";
if ($info)    $js[] = "toastr.info(".json_encode($info,    JSON_UNESCAPED_UNICODE).");";

if ($errors) {
    if (is_array($errors)) {
        foreach ($errors as $e) {
            if (!$e) continue;
            $js[] = "toastr.error(".json_encode($e, JSON_UNESCAPED_UNICODE).");";
        }
    } else {
        $js[] = "toastr.error(".json_encode($errors, JSON_UNESCAPED_UNICODE).");";
    }
}

if (!empty($js)): ?>
<script>
document.addEventListener('DOMContentLoaded', function(){
  <?php echo implode("\n  ", $js); ?>
});
</script>
<?php endif; ?>
