<?php
if (count($successes) > 0) {
?>

<ul class="success-msg">
    <?php foreach ($successes as $msg): ?>
    <li><?= $msg; ?></li>
    <?php endforeach; ?>
</ul>

<?php
  $successes = [];
}

if (count($errors) > 0) {
?>

<ul class="error-msg">
    <?php foreach ($errors as $msg): ?>
    <li><?= $msg; ?></li>
    <?php endforeach; ?>
</ul>

<?php
  $errors = [];
}
