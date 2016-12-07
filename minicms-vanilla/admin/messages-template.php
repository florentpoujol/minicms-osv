<?php if (isset($infoMsg) === true && $infoMsg !== ""): ?>
<div class="info-msg"><?php echo htmlspecialchars($infoMsg); ?></div>
<?php endif; ?>
<?php if (isset($errorMsg) === true && $errorMsg !== ""): ?>
<div class="error-msg"><?php echo htmlspecialchars($errorMsg); ?></div>
<?php endif; ?>