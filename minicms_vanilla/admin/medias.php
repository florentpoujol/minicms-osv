<?php if (isset($db) === false) exit(); ?>

<h1>Medias</h1>

<?php

// ===========================================================================
// ADD

if($action == "add") {
  var_dump($_FILES);
  var_dump($_POST);
  if (isset($_FILES["file"])) {
    $file = $_FILES["file"];
    $tmpName = $file["tmp_name"]; // on windows at least the temp_name as a .tmp extension
    $fileName = basename($file["name"]);

    // Check extension
    $allowedExtensions = ["jpg", "jpeg", "png", "pdf", "zip"];
    $ext = pathinfo($fileName, PATHINFO_EXTENSION);
    $validExtension = in_array($ext, $allowedExtensions, true);

    // check actual MIME Type
    $allowedMimeTypes = ["image/jpeg", "image/png", "application/pdf", "application/zip"];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($tmpName);
    $validMimeType = in_array($mimeType, $allowedMimeTypes, true);

    if ($validMimeType && $validExtension) {
      // check that the media name desn't already exists
      $mediaName = $_POST["name"];
      $query = $db->prepare('SELECT id FROM medias WHERE name = :name');
      $query->execute(["name" => $mediaName]);
      $media = $query->fetch();

      if ($media !== false) // media name already exists
        $errorMsg = "A media with the name '$mediaName' already exist.";

      elseif (move_uploaded_file($tmpName, "../uploads/".$fileName)) {
        // file uploaded and moved successfully
        // save the media in the DB

        $query = $db->prepare('INSERT INTO medias(name, path, creation_date, user_id) VALUES(:name, :path, :creation_date, :user_id)');
        $success = $query->execute([
          "name" => $mediaName,
          "path" => $fileName,
          "creation_date" => date("Y-m-d"),
          "user_id" => $currentUserId
        ]);

        if ($success)
          redirect(["action" => "show", "id" => $db->lastInsertId(), "info" => "mediaadded"]);
        else
          $errorMsg .= "There was an error saving the media in the database.";
      }
      else
        $errorMsg .= "There was an error moving the uploaded file";
    }
    else
      $errorMsg .= "The file's extension or MIME type is not accepted";
  }
  else {
    echo "no file submitted <br>";
  }
?>

<h2>Upload a new media</h2>

<?php require_once "messages-template.php"; ?>

<form action="?section=medias&action=add&test" method="post" enctype="multipart/form-data">
  <label>Name : <input type="text" name="name" placeholder="Media name" required></label> <br>
  <br>
  <label>File to upload (.jpg, .jpeg, .png, .pdf or .zip) : <br>
  <input type="file" name="file" accept=".jpeg, .jpg, image/jpeg, .png, image/png, .pdf, application/pdf, .zip, application/zip" required></label> <br>
  <br>
  <input type="submit" value="Upload">
</form>

<?php
}



// ===========================================================================
// DELETE
// no edit section since, there is only the media's name that can be editted

elseif ($action == "delete") {
  $query = $db->prepare("SELECT user_id FROM medias WHERE id=:id");
  $query->execute(["id"=>$resourceId]);
  $media = $query->fetch();

  if ($media === false)
    redirect([ "action" => "show", "id" => $resourceId, "error" => "unknownmedia"]);

  if ($isUserAdmin == false && $media["user_id"] != $currentUserId)
    redirect(["action" => "show", "id" => $resourceId, "error" => "mustbeadmin"]);

  $query = $db->prepare("DELETE FROM medias WHERE id=:id");
  $success = $query->execute(["id" => $resourceId]);
  if ($success)
    redirect(["action" => "show", "id" => $resourceId, "info" => "mediadeleted"]);
  else
    redirect(["action" => "show", "id" => $resourceId, "error" => "errordeletemedia"]);
}



// ===========================================================================
// ALL ELSE

// if action == "show" or other actions are fobidden for that user
else {

  switch($errorMsg) {
    case "mustbeadmin":
      $errorMsg = "You must be an admin to do that !";
      break;
    case "unknownmedia":
      $errorMsg = "There is no media with id $resourceId !";
      break;
    case "errordeletemedia":
      $errorMsg = "There was an error deleting the media with id $resourceId !";
      break;
  }

  switch($infoMsg) {
    case "mediadeleted":
      $infoMsg = "Media with id $resourceId has been successfully deleted.";
      break;
    case "mediaadded":
      $infoMsg = "Media with id $resourceId has been successfully added.";
      break;
  }
?>

<?php require_once "messages-template.php"; ?>

<div>
  <a href="?section=medias&action=add">Add a medias</a> <br>
</div>

<table>
  <tr>
    <th>id</th>
    <th>name</th>
    <th>path/preview</th>
    <th>creation date</th>
    <th>uploaded by</th>
  </tr>

<?php
  $query = $db->query('SELECT *, medias.id as media_id, medias.name as media_name, users.name as user_name
    FROM medias LEFT JOIN users ON medias.user_id=users.id ORDER BY medias.id');

  while($media = $query->fetch()) {
    // $users = $db->query('SELECT id, name FROM medias WHERE id='.$media["user_id"]);
?>
  <tr>
    <td><?php echo $media["media_id"]; ?></td>
    <td><?php echo htmlspecialchars($media["media_name"]); ?></td>
    <td>
<?php
    if (isImage($media["path"])) {
      echo $media["path"]."<br>";
      echo '<a href="../uploads/'.$media["path"].'">';
      echo '<img src="../uploads/'.$media['path'].'" alt="'.$media['media_name'].'" height="200px">';
      echo '</a>';
    }
    else
      echo '<a href="../uploads/'.$media["path"].'">'.$media["path"].'</a>'
?>
    </td>
    <td><?php echo $media["creation_date"]; ?></td>
    <td><?php echo $media["user_name"]; ?></td>

    <?php if($isUserAdmin || $media["user_id"] == $currentUserId): ?>
    <td><a href="?section=medias&action=delete&id=<?php echo $media["media_id"]; ?>">Delete</a></td>
    <?php endif; ?>
  </tr>
<?php
  } // end while medias from DB
?>
</table>
<?php
} // end if action = show
?>
