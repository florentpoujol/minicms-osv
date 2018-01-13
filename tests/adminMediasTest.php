<?php
$uploadTmpDir = __dir__ . "/medias_tmp";

function test_cleanup_files_start()
{
    // delete files beginning by "test" in the upload folder
    $path = __dir__ . "/../public/uploads";
    if (!file_exists($path)) {
        mkdir($path);
    }
    $dir = opendir($path);
    while (($file = readdir($dir)) !== false) {
        if ($file !== "." && $file !== ".." && !is_dir($file)) {
            if (strpos($file, "test") === 0) {
                unlink(__dir__ . "/../public/uploads/$file");
            }
        }
    }
    closedir($dir);
}

function test_admin_medias_not_for_commenters()
{
    $user = getUser("commenter");
    loadSite("section=admin:medias", $user["id"]);
    assertRedirect(buildUrl("admin:users", "update", $user["id"]));
    assertHTTPResponseCode(403);
}

// CREATE

function test_admin_medias_create_wrong_csrf()
{
    $_FILES["upload_file"] = [];
    setTestCSRFToken("wrongtoken");

    $user = getUser("admin");
    $content = loadSite("section=admin:medias&action=create", $user["id"]);
    assertStringContains($content, "Upload a new media");
    assertStringContains($content, "Wrong CSRF token for request 'uploadmedia'");
}

function test_admin_medias_create_wrong_type()
{
    global $uploadTmpDir;
    $_FILES["upload_file"] = [
        "tmp_name" => $uploadTmpDir . "/test_wrong_file_type.txt",
        "name" => "test_wrong_file_type.txt",
    ];
    setTestCSRFToken("uploadmedia");

    $user = getUser("admin");
    $content = loadSite("section=admin:medias&action=create", $user["id"]);

    assertStringContains($content, "The file's extension or MIME type is not accepted.");
}

function test_admin_medias_create_wrong_extension()
{
    global $uploadTmpDir;
    $_FILES["upload_file"] = [
        "tmp_name" => $uploadTmpDir . "/test-media-wrong-extension.wrongextension",
        "name" => "test-media-wrong-extension.wrongextension",
        // this file actually a jpeg image
    ];
    setTestCSRFToken("uploadmedia");

    $user = getUser("admin");
    $content = loadSite("section=admin:medias&action=create", $user["id"]);

    assertStringContains($content, "The file's extension or MIME type is not accepted.");
}

function test_admin_medias_create_wrong_slug_format()
{
    global $uploadTmpDir;
    $_FILES["upload_file"] = [
        "tmp_name" => $uploadTmpDir . "/test-media-jpeg.jpeg",
        "name" => "test-media-jpeg.jpeg",
    ];
    $_POST["upload_slug"] = "media 1";
    setTestCSRFToken("uploadmedia");

    $user = getUser("admin");
    $content = loadSite("section=admin:medias&action=create", $user["id"]);

    assertStringContains($content, "The slug has the wrong format.");
}

// cannot test the success since I can't
function upload_file(string $fileName, string $slug, string $userName)
{
    global $uploadTmpDir;
    $_FILES["upload_file"] = [
        "tmp_name" => $uploadTmpDir . "/$fileName",
        "name" => $fileName,
    ];
    $_POST["upload_slug"] = $slug;
    setTestCSRFToken("uploadmedia");

    $user = getUser($userName);
    loadSite("section=admin:medias&action=create", $user["id"]);

    assertMessageSaved("File uploaded successfully.");
    assertRedirect(buildUrl("admin:medias", "read"));

    $media = queryTestDB("SELECT * FROM medias WHERE slug=?", $slug)->fetch();
    assertDifferent($media, false);
    assertIdentical($slug, $media["slug"]);
    $date = date("Y-m-d");
    $fileName = preg_replace("/(\.[a-z0-9]+)$/i", "-$slug-$date$1", $fileName);
    assertIdentical($fileName, $media["filename"]);
    assertIdentical($user["id"], $media["user_id"]);

    assertIdentical(true, file_exists(__dir__ . "/../public/uploads/$fileName"));
}

function test_admin_medias_create_success()
{
    upload_file("test-media-jpeg.jpeg", "media-jpeg", "admin");
}

function test_admin_medias_create_jpg_success()
{
    upload_file("test-media-jpg.jpg", "media-jpg", "writer");
}

function test_admin_medias_create_pdf_success()
{
    upload_file("test-media-pdf.pdf", "media-pdf", "admin");
}

function test_admin_medias_create_png_success()
{
    upload_file("test-media-png.png", "media-png", "writer");
}

function test_admin_medias_create_zip_success()
{
    upload_file("test-media-zip.zip", "media-zip", "admin");
}

function test_admin_medias_create_slug_already_exists()
{
    global $uploadTmpDir;
    $_FILES["upload_file"] = [
        "tmp_name" => $uploadTmpDir . "/test-media-jpeg.jpeg",
        "name" => "test-media-jpeg.jpeg",
    ];
    $_POST["upload_slug"] = "media-jpeg";
    setTestCSRFToken("uploadmedia");

    $user = getUser("admin");
    $content = loadSite("section=admin:medias&action=create", $user["id"]);
    assertStringContains($content, "A media with the slug 'media-jpeg' already exist.");
}

// DELETE

function test_admin_medias_delete_wrong_csrf()
{
    $admin = getUser("admin");
    $media = queryTestDB("SELECT * FROM medias WHERE slug='media-jpeg'")->fetch();
    assertDifferent($media, false);
    $token = setTestCSRFToken("wrongtoken");

    loadSite("section=admin:medias&action=delete&id=$media[id]&csrftoken=$token", $admin["id"]);

    assertMessageSaved("Wrong CSRF token for request 'mediadelete'");
    assertRedirect(buildUrl("admin:medias", "read"));
}

function test_admin_medias_writers_can_only_delete_their_own_media()
{
    $admin = getUser("admin");
    $media = queryTestDB("SELECT * FROM medias WHERE user_id=?", $admin["id"])->fetch();
    assertDifferent($media, false);
    $token = setTestCSRFToken("mediadelete");

    $user = getUser("writer");
    loadSite("section=admin:medias&action=delete&id=$media[id]&csrftoken=$token", $user["id"]);

    assertMessageSaved("Can only delete your own medias.");
    assertRedirect(buildUrl("admin:medias", "read"));
}

function test_admin_medias_delete_no_id()
{
    $admin = getUser("admin");
    $token = setTestCSRFToken("mediadelete");

    loadSite("section=admin:medias&action=delete&csrftoken=$token", $admin["id"]);

    assertMessageSaved("You must choose a media to delete.");
    assertRedirect(buildUrl("admin:medias", "read"));
}

function test_admin_medias_delete_unknown_id()
{
    $admin = getUser("admin");
    $token = setTestCSRFToken("mediadelete");

    loadSite("section=admin:medias&action=delete&id=987&csrftoken=$token", $admin["id"]);

    assertMessageSaved("Unknown media with id 987.");
    assertRedirect(buildUrl("admin:medias", "read"));
}

function test_admin_medias_delete_success()
{
    $media = queryTestDB("SELECT * FROM medias WHERE slug='media-jpeg'")->fetch();
    assertDifferent($media, false);
    $path = __dir__ . "/../public/uploads/$media[filename]";
    assertIdentical(true, file_exists($path));

    $admin = getUser("admin");
    $token = setTestCSRFToken("mediadelete");
    loadSite("section=admin:medias&action=delete&id=$media[id]&csrftoken=$token", $admin["id"]);

    assertMessageSaved("Media deleted with success.");
    assertRedirect(buildUrl("admin:medias", "read"));

    $media = queryTestDB("SELECT * FROM medias WHERE slug='media-jpeg'")->fetch();
    assertIdentical(false, $media);
    assertIdentical(false, file_exists($path));
}

// READ

function test_admin_medias_read()
{
    $user = getUser("writer");
    $content = loadSite("section=admin:medias", $user["id"]);

    assertStringContains($content, "List of all medias");
    assertStringContains($content, "media-jpg");
    $date = date("Y-m-d");
    assertStringContains($content, "test-media-jpg-media-jpg-$date.jpg");
    assertStringContains($content, "media-pdf");
    assertStringContains($content, "test-media-pdf-media-pdf-$date.pdf");
    assertStringContains($content, "Delete</a>");
}

function test_cleanup_files_end()
{
    test_cleanup_files_start();
}
