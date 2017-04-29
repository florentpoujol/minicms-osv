<?php
if (isset($db) === false) exit();
if ($currentUser["role"] === "commenter")
  redirect(["section" => ""]);

$title = "Medias";
require_once "header.php";
?>

<h1>Medias</h1>

<?php
$uploadsFolder = "../uploads";
$namePattern = "[a-zA-Z0-9_-]{4,}";

// ===========================================================================
// ADD

if($action === "add") {
  $mediaName = "";

  if (isset($_FILES["file"])) {
    $file = $_FILES["file"];
    $tmpName = $file["tmp_name"]; // on windows with Wampserver the temp_name as a .tmp extension
    $fileName = basename($file["name"]);

    // Check extension
    $allowedExtensions = ["jpg", "jpeg", "png", "pdf", "zip"];
    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
    $validExtension = in_array($extension, $allowedExtensions, true);

    // check actual MIME Type
    $allowedMimeTypes = ["image/jpeg", "image/png", "application/pdf", "application/zip"];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($tmpName);
    $validMimeType = in_array($mimeType, $allowedMimeTypes, true);

    if ($validMimeType && $validExtension) {
      $mediaName = $_POST["name"];
      // check for name format
      if (checkPatterns("/$namePattern/", $mediaName) === false)
        $errorMsg .= "The media name has the wrong format. Minimum 2 letters, numbers, hyphens or underscores. \n";

      // check that the media name desn't already exists
      $query = $db->prepare('SELECT id FROM medias WHERE name = :name');
      $query->execute(["name" => $mediaName]);
      $media = $query->fetch();

      if ($media !== false)
        $errorMsg = "A media with the name '".htmlspecialchars($mediaName)."' already exist.";

      else {
        $creationDate = date("Y-m-d");
        $fileName = str_replace(" ", "-", $fileName);
        // add the creation date between the name of the file and the extension
        $fileName = preg_replace("/(\.[a-zA-Z]{3,4})$/i", "-$mediaName-$creationDate$1", $fileName);

        if (move_uploaded_file($tmpName, "$uploadsFolder/".$fileName)) {
          // file uploaded and moved successfully
          // save the media in the DB

          $query = $db->prepare('INSERT INTO medias(name, filename, creation_date, user_id) VALUES(:name, :filename, :creation_date, :user_id)');
          $success = $query->execute([
            "name" => $mediaName,
            "filename" => $fileName,
            "creation_date" => $creationDate,
            "user_id" => $currentUserId
          ]);

          if ($success)
            redirect(["action" => "show", "id" => $db->lastInsertId(), "info" => "mediaadded"]);
          else
            $errorMsg .= "There was an error saving the media in the database. \n";
        }
        else
          $errorMsg .= "There was an error moving the uploaded file. \n";
      }
    }
    else
      $errorMsg .= "The file's extension or MIME type is not accepted. \n";
  }
?>

<h2>Upload a new media</h2>

<?php require_once "messages-template.php"; ?>

<form action="?section=medias&action=add" method="post" enctype="multipart/form-data">
  <label>Name : <input type="text" name="name" placeholder="Name" required pattern="<?php echo $namePattern; ?>" value="<?php echo htmlspecialchars($mediaName); ?>"></label> <br>
  <br>

  <label>File to upload <?php createTooltip("Allowed extensions : .jpg, .jpeg, .png, .pdf or .zip"); ?> : <br>
  <input type="file" name="file" required accept=".jpeg, .jpg, image/jpeg, .png, image/png, .pdf, application/pdf, .zip, application/zip"></label> <br>
  <br>

  <input type="submit" value="Upload">
</form>

<?php
}



// ===========================================================================
// DELETE
// no edit section since, there is only the media's name that can be editted

elseif ($action === "delete") {
  $query = $db->prepare("SELECT user_id, filename FROM medias WHERE id=:id");
  $query->execute(["id" => $resourceId]);
  $media = $query->fetch();

  if ($media === false)
    redirect([ "action" => "show", "id" => $resourceId, "error" => "unknowmedia"]);

  if ($isUserAdmin === false && $media["user_id"] !== $currentUserId)
    redirect(["action" => "show", "id" => $resourceId, "error" => "mustbeadmin"]);

  $query = $db->prepare("DELETE FROM medias WHERE id=:id");
  $success = $query->execute(["id" => $resourceId]);

  if ($success) {
    unlink($uploadsFolder."/".$media["filename"]); // delete the actual file
    redirect(["action" => "show", "id" => $resourceId, "info" => "mediadeleted"]);
  }
  else
    redirect(["action" => "show", "id" => $resourceId, "error" => "deletemedia"]);
}



// ===========================================================================
// ALL ELSE

// if action == "show" or other actions are fobidden for that user
else {

  switch($errorMsg) {
    case "mustbeadmin":
      $errorMsg = "You must be an admin to do that !";
      break;
    case "unknowmedia":
      $errorMsg = "There is no media with id $resourceId !";
      break;
    case "deletemedia":
      $errorMsg = "There was an error deleting the media with id $resourceId !";
      break;
  }

  switch($infoMsg) {
    case "mediaadded":
      $infoMsg = "Media with id $resourceId has been successfully added.";
      break;
    case "mediadeleted":
      $infoMsg = "Media with id $resourceId has been successfully deleted.";
      break;
  }

  if ($orderByTable === "")
    $orderByTable = "medias";
?>

<?php require_once "messages-template.php"; ?>

<div>
  <a href="?section=medias&action=add">Add a medias</a>
</div>

<br>

<table>
  <tr>
    <th>Id <?php echo printTableSortButtons("medias", "id"); ?></th>
    <th>Name <?php echo printTableSortButtons("medias", "name"); ?></th>
    <th>Path/Preview</th>
    <th>Uploaded on <?php echo printTableSortButtons("medias", "creation_date"); ?></th>
    <th>Uploaded by <?php echo printTableSortButtons("users", "name"); ?></th>
  </tr>

<?php
  $query = $db->query("SELECT medias.*, users.name as user_name
    FROM medias LEFT JOIN users ON medias.user_id=users.id 
    ORDER BY $orderByTable.$orderByField $orderDir");

  while($media = $query->fetch()) {
?>
  <tr>
    <td><?php echo $media["id"]; ?></td>
    <td><?php echo htmlspecialchars($media["name"]); ?></td>
    <td>
<?php
    $fileName = $media["filename"];
    if (isImage($fileName)) { // does not seems to consider .jpeg as image ?
      echo $fileName."<br>";
      echo '<a href="'.$uploadsFolder.'/'.$fileName.'">';
      echo '<img src="'.$uploadsFolder.'/'.$fileName.'" alt="'.htmlspecialchars($media['name']).'" height="200px">';
      echo '</a>';
    }
    else
      echo '<a href="'.$uploadsFolder.'/'.$fileName.'">'.$fileName.'</a>';
?>
    </td>
    <td><?php echo $media["creation_date"]; ?></td>
    <td><?php echo $media["user_name"]; ?></td>

    <?php if($isUserAdmin || $media["user_id"] === $currentUserId): ?>
    <td><a href="?section=medias&action=delete&id=<?php echo $media["id"]; ?>">Delete</a></td>
    <?php endif; ?>
  </tr>
<?php
  } // end while medias from DB
?>
</table>
<?php
} // end if action = show
?>
