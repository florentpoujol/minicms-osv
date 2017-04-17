<?php
if (isset($infoMsg) && $infoMsg !== "") {
?>
  <div class="info-msg"><?php echo nl2br(htmlspecialchars($infoMsg)); ?></div>
<?php
  $infoMsg = "";
}

if (isset($errorMsg) && $errorMsg !== "") {
?>
  <div class="error-msg"><?php echo nl2br(htmlspecialchars($errorMsg)); ?></div>
<?php
  $errorMsg = "";
}
?>