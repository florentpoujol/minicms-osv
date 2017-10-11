<?php
declare(strict_types=1);

if ($user["role"] === "commenter") {
    setHTTPHeader(403);
    redirect("admin");
}

$action = $query['action'];
$userId = $user['id'];
$queryId = $query['id'] === '' ? null : $query['id'];

$title = "Medias";
require_once "header.php";
?>

<h1>Medias</h1>

<?php
$uploadsFolder = "uploads";

if($action === "create") {
    $mediaSlug = "";

    if (isset($_FILES["upload_file"]) && verifyCSRFToken($_POST["csrf_token"], "uploadmedia")) {
        $file = $_FILES["upload_file"];
        $tmpName = $file["tmp_name"]; // on windows with Wampserver the temp_name as a .tmp extension
        $fileName = basename($file["slug"]);

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
            $mediaSlug = $_POST["upload_slug"];

            if (checkSlugFormat($mediaSlug)) {
                // check that the media slug desn't already exists
                $media = queryDB("SELECT id FROM medias WHERE slug = ?", $mediaSlug)->fetch();

                if ($media === false) {
                    $creationDate = date("Y-m-d");
                    $fileName = str_replace(" ", "-", $fileName);
                    // add the creation date between the slug of the file and the extension
                    $fileName = preg_replace("/(\.[a-zA-Z]{3,4})$/i", "-$mediaSlug-$creationDate$1", $fileName);

                    if (move_uploaded_file($tmpName, "$uploadsFolder/$fileName")) {
                        // file uploaded and moved successfully
                        // save the media in the DB

                        $success = queryDB(
                            "INSERT INTO medias(slug, filename, creation_date, user_id) VALUES(:slug, :filename, :creation_date, :user_id)",
                            [
                                "slug" => $mediaSlug,
                                "filename" => $fileName,
                                "creation_date" => $creationDate,
                                "user_id" => $userId
                            ]
                        );

                        if ($success) {
                            addSuccess("File uploaded successfully");
                            redirect("admin:medias");
                        } else {
                            addError("There was an error saving the media in the database.");
                        }
                    } else {
                        addError("There was an error moving the uploaded file.");
                    }
                } else {
                    addError("A media with the slug '".htmlspecialchars($mediaSlug)."' already exist.");
                }
            }
        } else {
            addError("The file's extension or MIME type is not accepted.");
        }
    }
?>

<h2>Upload a new media</h2>

<?php require_once "../app/messages.php"; ?>

<form action="<?= buildUrl("admin:medias", "create"); ?>" method="post" enctype="multipart/form-data">
    <label>Slug : <input type="text" name="upload_slug" placeholder="Slug" required value="<?= $mediaSlug; ?>"></label> <br>
    <br>

    <label>File to upload <?php createTooltip("Allowed extensions : .jpg, .jpeg, .png, .pdf or .zip"); ?> : <br>
        <input type="file" name="upload_file" required accept=".jpeg, .jpg, image/jpeg, .png, image/png, .pdf, application/pdf, .zip, application/zip">
    </label> <br>
    <br>

    <?php addCSRFFormField("uploadmedia"); ?>

    <input type="submit" value="Upload">
</form>

<?php
} // end action === "create"


// --------------------------------------------------
// no edit section since, there is only the media's name that can be editted

elseif ($action === "delete") {
    if (verifyCSRFToken($query['csrftoken'], "mediadelete")) {
        $media = queryDB("SELECT user_id, filename FROM medias WHERE id = ?", $queryId)->fetch();

        if (is_array($media)) {
            if (! $user['isAdmin'] && $media["user_id"] !== $userId) {
                addError("Can only delete your own medias.");
            } else {
                $success = queryDB("DELETE FROM medias WHERE id = ?", $queryId, true);

                if ($success) {
                    unlink($uploadsFolder."/".$media["filename"]); // delete the actual file
                    addSuccess("Media delete with success");
                } else {
                    addError("There was an error deleting the media");
                }
            }
        } else {
            addError("Unkonw medias with id $queryId");
        }
    }

    redirect('admin:medias');
}

// --------------------------------------------------
// if action == "show" or other actions are fobidden for that user

else {
?>

<?php require_once "../app/messages.php"; ?>

<div>
    <a href="<?= buildUrl("admin:medias", "create"); ?>">Add a media</a>
</div>

<br>

<table>
    <tr>
        <th>Id <?= getTableSortButtons("medias", "id"); ?></th>
        <th>Slug <?= getTableSortButtons("medias", "slug"); ?></th>
        <th>Path/Preview</th>
        <th>Uploaded on <?= getTableSortButtons("medias", "creation_date"); ?></th>
        <th>Uploaded by <?= getTableSortButtons("users", "name"); ?></th>
    </tr>

<?php
    $tables = ["medias", "users"];
    if (! in_array($query['orderByTable'], $tables)) {
        $query['orderByTable'] = "medias";
    }

    $fields = ["id", "slug", "creation_date"];
    if (! in_array($query['orderByField'], $fields)) {
        $query['orderByField'] = "id";
    }

    $medias = queryDB(
        "SELECT medias.*, users.name as user_name
        FROM medias
        LEFT JOIN users ON medias.user_id = users.id
        ORDER BY $query[orderByTable].$query[orderByField] $query[orderDir]
        LIMIT ".$adminMaxTableRows * ($query['page'] - 1).", $adminMaxTableRows"
    );

    $deleteToken = setCSRFTokens("mediadelete");

    while($media = $medias->fetch()) {
?>

    <tr>
        <td><?= $media["id"]; ?></td>
        <td><?php safeEcho($media["slug"]); ?></td>

        <td>
<?php
        $fileName = htmlspecialchars($media["filename"]);
        $path = $uploadsFolder.'/'.$fileName;
        if (isImage($fileName)): // does not seems to consider .jpeg as image ?
?>
            <?php safeEcho($fileName); ?> <br>
            <a href="<?= $path; ?>">
                <img src="<?= $path; ?>" alt="<?php safeEcho($media["slug"]); ?>" height="200px">';
            </a>;
<?php
        else:
?>
            <a href="<?= $path; ?>"><?= $fileName; ?></a>';
<?php
        endif;
?>
        </td>

        <td><?= $media["creation_date"]; ?></td>
        <td><?php safeEcho($media["user_name"]); ?></td>

        <?php if($user['isAdmin'] || $media["user_id"] === $userId): ?>
            <td><a href="<?= buildUrl("admin:medias", "delete", $media["id"], $deleteToken); ?>">Delete</a></td>
        <?php endif; ?>
    </tr>

<?php
    } // end while medias from DB
?>

</table>

<?php
    $table = "medias";
    require_once "pagination.php";
} // end if action = show
