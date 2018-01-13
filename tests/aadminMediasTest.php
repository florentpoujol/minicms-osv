<?php
$uploadTmpDir = __dir__ . "/medias_tmp";

function test_cleanup_files_start()
{
    // delete files beginning by "test" in the upload folder
    $path = __dir__ . "/../public/uploads";
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

function est_admin_medias_create_already_exists()
{
    $_POST["name"] = "Media1";
    $_POST["structure"] = [];
    $_POST["in_use"] = 1;
    setTestCSRFToken("uploadmedia");

    $user = getUser("admin");
    $content = loadSite("section=admin:medias&action=create", $user["id"]);
    assertStringContains($content, "The media with id 1 already has the name 'Media1'.");
}

// UPDATE
// note: no tests on the structure is done
function est_admin_medias_update_no_id()
{
    $user = getUser("writer");
    loadSite("section=admin:medias&action=update", $user["id"]);
    assertMessageSaved("You must select a media to update.");
    assertRedirect(buildUrl("admin:medias", "read"));
}

function est_admin_medias_update_unknow_id()
{
    $user = getUser("writer");
    loadSite("section=admin:medias&action=update&id=987", $user["id"]);
    assertMessageSaved("Unknown media with id 987.");
    assertRedirect(buildUrl("admin:medias", "read"));
}

function est_admin_medias_update_read()
{
    $user = getUser("writer");
    $media = queryTestDB("SELECT * FROM medias WHERE slug='Media1'")->fetch();

    $content = loadSite("section=admin:medias&action=update&id=$media[id]", $user["id"]);

    assertStringContains($content, '<form action="'.buildUrl("admin:medias", "update", $media["id"]).'"');
    assertStringContains($content, "Edit media with id $media[id]");
    assertStringContainsRegex($content, "/Name:.+$media[name]/");
    $checked = "";
    if ($media["in_use"] === 1) {
        $checked = "checked";
    }
    assertStringContainsRegex($content, '/Use this media:.+name="in_use" '.$checked.'>/');
}

function est_admin_medias_update_name_exists()
{
    queryTestDB("INSERT INTO medias(name, structure, in_use) VALUES('Media2', '[]', 0)");
    $media = queryTestDB("SELECT * FROM medias WHERE slug='Media1'")->fetch();
    assertDifferent($media, false);
    $media2 = queryTestDB("SELECT * FROM medias WHERE slug='Media2'")->fetch();
    assertDifferent($media2, false);

    $_POST["name"] = "Media2";
    $_POST["structure"] = [];
    $_POST["in_use"] = 1;
    setTestCSRFToken("mediaupdate");

    $user = getUser("writer");
    $content = loadSite("section=admin:medias&action=update&id=$media[id]", $user["id"]);

    assertStringContains($content, "The media with id $media2[id] already has the name 'Media2'.");
}

function est_admin_medias_update_success()
{
    $_POST["name"] = "Media3";
    $_POST["structure"] = [];
    unset($_POST["in_use"]);
    setTestCSRFToken("mediaupdate");

    $user = getUser("writer");
    $media = queryTestDB("SELECT * FROM medias WHERE slug='Media1'")->fetch();
    assertDifferent($media, false);
    loadSite("section=admin:medias&action=update&id=$media[id]", $user["id"]);

    assertMessageSaved("Media added or edited successfully.");
    assertRedirect(buildUrl("admin:medias", "update", $media["id"]));
    $media = queryTestDB("SELECT * FROM medias WHERE id = $media[id]")->fetch();
    assertDifferent($media, false);
    assertIdentical("Media3", $media["name"]);
    assertIdentical(0, $media["in_use"]);

    queryTestDB("UPDATE medias SET slug = 'Media1', in_use = 1 WHERE id = $media[id]");
}

// DELETE

function est_admin_medias_delete_not_for_writers()
{
    $user = getUser("writer");
    loadSite("section=admin:medias&action=delete", $user["id"]);
    assertMessageSaved("Must be admin.");
    assertHTTPResponseCode(403);
    assertRedirect(buildUrl("admin:medias", "read"));
}

function est_admin_medias_delete_wrong_csrf()
{
    $admin = getUser("admin");
    $media2 = queryTestDB("SELECT * FROM medias WHERE slug='Media2'")->fetch();
    assertDifferent($media2, false);
    $token = setTestCSRFToken("wrongtoken");

    loadSite("section=admin:medias&action=delete&id=$media2[id]&csrftoken=$token", $admin["id"]);

    assertMessageSaved("Wrong CSRF token for request 'mediadelete'");
    assertRedirect(buildUrl("admin:medias", "read"));
}

function est_admin_medias_delete_unknown_id()
{
    $admin = getUser("admin");
    $token = setTestCSRFToken("mediadelete");

    loadSite("section=admin:medias&action=delete&id=987&csrftoken=$token", $admin["id"]);

    assertMessageSaved("Unknown media with id 987.");
    assertRedirect(buildUrl("admin:medias", "read"));
}

function est_admin_medias_delete_success()
{
    $admin = getUser("admin");
    $media2 = queryTestDB("SELECT * FROM medias WHERE slug='Media2'")->fetch();
    assertDifferent($media2, false);
    $token = setTestCSRFToken("mediadelete");

    loadSite("section=admin:medias&action=delete&id=$media2[id]&csrftoken=$token", $admin["id"]);

    assertMessageSaved("Media deleted successfully.");
    assertRedirect(buildUrl("admin:medias", "read"));
    $media2 = queryTestDB("SELECT * FROM medias WHERE slug='Media2'")->fetch();
    assertIdentical(false, $media2);
}

// READ

function est_admin_medias_read_writer()
{
    $user = getUser("writer");
    $content = loadSite("section=admin:medias", $user["id"]);

    assertStringContains($content, "List of all medias");
    assertStringContains($content, "Media1");
    assertStringContains($content, "<td>1</td>");
    assertStringContains($content, "Edit</a>");
    assertStringNotContains($content, "Delete</a>");
}

function est_admin_medias_read_admin()
{
    $user = getUser("admin");
    $content = loadSite("section=admin:medias", $user["id"]);

    assertStringContains($content, "List of all medias");
    assertStringContains($content, "Media1");
    assertStringContains($content, "<td>1</td>");
    assertStringContains($content, "Edit</a>");
    assertStringContains($content, "Delete</a>");
}

function test_cleanup_files_end()
{
    test_cleanup_files_start();
}
