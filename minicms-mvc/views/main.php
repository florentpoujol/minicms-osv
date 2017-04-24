<?php 
// form some reason, can't be called "index.php"
?>
<h1><?php echo $pageTitle; ?></h1>

<?php if ($user !== false): ?>
<form>
  <input type="submit" name="logout" value="logout">
</form>
<?php endif; ?>