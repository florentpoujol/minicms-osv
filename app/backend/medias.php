<?php
if ($user["role"] === "commenter") {
    redirect($folder);
}

$title = "Medias";
require_once "header.php";
?>

<h1>Medias</h1>

<?php
$uploadsFolder = "uploads";

if($action === "add") {
    $mediaName = "";

    if (isset($_FILES["upload_file"])) {
        $file = $_FILES["upload_file"];
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
            $mediaName = $_POST["upload_name"];

            if (checkNameFormat($mediaName)) {
                // check that the media name desn't already exists
                $media = queryDB("SELECT id FROM medias WHERE name=?", $mediaName)->fetch();

                if ($media === false) {
                    $creationDate = date("Y-m-d");
                    $fileName = str_replace(" ", "-", $fileName);
                    // add the creation date between the name of the file and the extension
                    $fileName = preg_replace("/(\.[a-zA-Z]{3,4})$/i", "-$mediaName-$creationDate$1", $fileName);

                    if (move_uploaded_file($tmpName, "$uploadsFolder/$fileName")) {
                        // file uploaded and moved successfully
                        // save the media in the DB

                        $success = queryDB(
                            "INSERT INTO medias(name, filename, creation_date, user_id) VALUES(:name, :filename, :creation_date, :user_id)",
                            [
                                "name" => $mediaName,
                                "filename" => $fileName,
                                "creation_date" => $creationDate,
                                "user_id" => $userId
                            ]
                        );

                        if ($success) {
                            addSuccess("File uploaded successfully");
                            redirect($folder, "medias");
                        }
                        else {
                            addError("There was an error saving the media in the database.");
                        }
                    }
                    else {
                        addError("There was an error moving the uploaded file.");
                    }
                }
                else {
                    addError("A media with the name '".htmlspecialchars($mediaName)."' already exist.");
                }
            }
        }
        else {
            addError("The file's extension or MIME type is not accepted.");
        }
    }
?>

<h2>Upload a new media</h2>

<?php require_once "../app/messages.php"; ?>

<form action="<?php echo buildLink($folder, "medias", "add"); ?>" method="post" enctype="multipart/form-data">
    <label>Name : <input type="text" name="upload_name" placeholder="Name" required value="<?php echo $mediaName; ?>"></label> <br>
    <br>

    <label>File to upload <?php createTooltip("Allowed extensions : .jpg, .jpeg, .png, .pdf or .zip"); ?> : <br>
        <input type="file" name="upload_file" required accept=".jpeg, .jpg, image/jpeg, .png, image/png, .pdf, application/pdf, .zip, application/zip">
    </label> <br>
    <br>

    <input type="submit" value="Upload">
</form>

<?php
} // end action === "add"


// --------------------------------------------------
// no edit section since, there is only the media's name that can be editted

elseif ($action === "delete") {
    $media = queryDB("SELECT user_id, filename FROM medias WHERE id=?", $resourceId)->fetch();

    if (is_array($media)) {
        if (! $isUserAdmin && $media["user_id"] !== $userId) {
            addError("Can only delete your own medias.");
        }
        else {
            $success = queryDB("DELETE FROM medias WHERE id=?", $resourceId, true);

            if ($success) {
                unlink($uploadsFolder."/".$media["filename"]); // delete the actual file
                addSuccess("Media delete with success");
            }
            else {
                addError("There was an error deleting the media");
            }
        }
    }
    else {
        addError("Unkonw medias with id $resourceId");
    }

    redirect($folder, "medias");
}

// --------------------------------------------------
// if action == "show" or other actions are fobidden for that user

else {
?>

<?php require_once "../app/messages.php"; ?>

<div>
    <a href="<?php echo buildLink($folder, "medias", "add"); ?>">Add a media</a>
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
    $tables = ["medias", "users"];
    if (! in_array($orderByTable, $tables)) {
        $orderByTable = "medias";
    }

    $fields = ["id", "name", "creation_date"];
    if (! in_array($orderByField, $fields)) {
        $orderByField = "id";
    }

    $query = queryDB(
        "SELECT medias.*, users.name as user_name
        FROM medias
        LEFT JOIN users ON medias.user_id=users.id
        ORDER BY $orderByTable.$orderByField $orderDir"
    );

    while($media = $query->fetch()) {
?>
    <tr>
        <td><?php echo $media["id"]; ?></td>
        <td><?php echo $media["name"]; ?></td>
        <td>
<?php
        $fileName = $media["filename"];
        if (isImage($fileName)) { // does not seems to consider .jpeg as image ?
            echo $fileName."<br>";
            echo '<a href="'.$uploadsFolder.'/'.$fileName.'">';
            echo '<img src="'.$uploadsFolder.'/'.$fileName.'" alt="'.$media["name"].'" height="200px">';
            echo '</a>';
        }
        else {
            echo '<a href="'.$uploadsFolder.'/'.$fileName.'">'.$fileName.'</a>';
        }
?>
        </td>
        <td><?php echo $media["creation_date"]; ?></td>
        <td><?php echo $media["user_name"]; ?></td>

        <?php if($isUserAdmin || $media["user_id"] === $userId): ?>
        <td><a href="<?php echo buildLink($folder, "medias", "delete", $media["id"]); ?>">Delete</a></td>
        <?php endif; ?>
    </tr>
<?php
    } // end while medias from DB
?>
</table>
<?php
} // end if action = show
