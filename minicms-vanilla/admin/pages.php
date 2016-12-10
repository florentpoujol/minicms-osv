<?php
if (isset($db) === false) exit();

$title = "Pages";
require_once "header.php";
?>

<h1>Pages</h1>

<?php
$minTitleLength = 2;
$urlNamePattern = "[a-zA-Z0-9_-]{2,}";
$numberPattern = "[0-9]{1,}";

// ===========================================================================
// ADD

if ($action === "add") {

  $postedPage = [
    "title" => "",
    "url_name" => "",
    "content" => "",
    "menu_priority" => 0,
    "parent_page_id" => 0,
    "editable_by_all" => 0,
    "published" => 0
  ];

  if (isset($_POST["add_page"])) {
    foreach($postedPage as $key => $value) {
      if (isset($_POST[$key])) {
        if ($value === 0)
          $postedPage[$key] = (int)$_POST[$key];
        else
          $postedPage[$key] = $_POST[$key];
      }
    }

    // check for title format
    if (strlen($postedPage["title"]) < $minTitleLength)
      $errorMsg .= "The title must be at least $minTitleLength characters long. <br>";


    // check for url name format
    if (checkPatterns("/$urlNamePattern/", $postedPage["url_name"]) === false)
      $errorMsg .= "The URL name has the wrong format. Minimum 2 letters, numbers, hyphens or underscores. <br>";

    // check that the url name doesn't already exist
    $query = $db->prepare('SELECT id FROM pages WHERE url_name = :url_name');
    $query->execute(["url_name" => $postedPage["url_name"]]);
    $page = $query->fetch();

    if ($page !== false)
      $errorMsg .= "The page with id ".$page["id"]." and title '".htmlspecialchars($page["title"])."' already has the URL name '".htmlspecialchars($postedPage["url_name"])."' . <br>";


    // no check on format of numerical fields since they are already converted to int. If the posted value wasn't numerical, it is now 0
    if ($postedPage["parent_page_id"] !== 0) {
      // check the id of the parent page, that it's indeed a parent page (a page that isn't a child of another page)
      $query = $db->prepare('SELECT parent_page_id, parents.title as parent_title FROM pages LEFT JOIN pages as parents ON pages.parent_page_id=parents.id WHERE pages.id = :id');
      $query->execute(["id" => $postedPage["parent_page_id"]]);
      $page = $query->fetch();

      if ($page !== false) {
        $errorMsg .= "The parent page with id '".$postedPage["parent_page_id"]."' does not exist . <br>";
        $postedPage["parent_page_id"] = 0;
      }
      elseif ($page["parent_page_id"] !== null) {
        $errorMsg .= "The selected parent page (with title '".htmlspecialchars($page["title"])."'is actually a children of another page (with title '".htmlspecialchars($page["parent_title"])."', so it can't be a parent page itself. <br>";
        $postedPage["parent_page_id"] = 0;
      }
    }

    // no check on content

    if ($errorMsg === "") {
      // OK no error, let's add the page
      $query = $db->prepare(
        'INSERT INTO pages(title, url_name, content, menu_priority, parent_page_id, editable_by_all, published, user_id, creation_date)
        VALUES(:title, :url_name, :content, :menu_priority, :parent_page_id, :editable_by_all, :published, :user_id, :creation_date)'
      );

      $dbData = $postedPage;
      if ($dbData["parent_page_id"] === 0)
        unset($dbData["parent_page_id"]);
      $dbData["user_id"] = $currentUserId;
      $dbData["creation_date"] = date("Y-m-d");
      $success = $query->execute($dbData);

      if ($success)
        redirect(["action" => "show", "id" => $db->lastInsertId(), "info" => "pageadded"]);
      else
        $errorMsg .= "There was an error registering the page";
    }
  }
?>

<h2>Add a new page</h2>

<?php require_once "messages-template.php"; ?>

<form action="?section=pages&action=add" method="post">
  <label>Title : <input type="text" name="title" required pattern=".{<?php echo $minTitleLength; ?>,}" placehlder="Title" value="<?php echo htmlspecialchars($postedPage["title"]); ?>"></label> <br>
  <br>

  <label>URL name : <input type="text" name="url_name" required pattern="<?php echo $urlNamePattern; ?>" placeholder="URL Name" value ="<?php echo htmlspecialchars($postedPage["url_name"]); ?>"></label> <?php createTooltip("The 'beautiful' URL of the page. Can only contains letters, numbers, hyphens and underscores."); ?> <br>
  <br>

  <label>Content : <br>
  <textarea name="content" placeholder="Your text here" cols="60" rows="15"><?php echo $postedPage["content"]; ?></textarea><br>
  <br>

  <label>Menu Priority : <input type="number" name="menu_priority" required pattern="[-]?[0-9]{1,}" value="<?php echo $postedPage["menu_priority"]; ?>"></label> <?php createTooltip("Determines the order in which the pages are shown in the menu. Lower priority = first."); ?> <br>
  <br>

  <label>Parent page :
    <select name="parent_page_id">
      <option value="0">None</option>
      <?php $topLevelPages = $db->query('SELECT id, title FROM pages WHERE parent_page_id IS NULL'); ?>
      <?php while($page = $topLevelPages->fetch()): ?>
      <option value="<?php echo $page["id"]; ?>" <?php echo ($postedPage["parent_page_id"] === $page["id"]) ? "selected" : null; ?>><?php echo htmlspecialchars($page["title"]); ?></option>
      <?php endwhile; ?>
    </select>
  </label> <br>
  <br>

  <label>Can be edited by any user : <input type="checkbox" name="editable_by_all" <?php echo ($postedPage["editable_by_all"] === 1) ? "checked" : null; ?>> <br>
  <br>

  <label>Publication status :
    <select name="published">
      <option value="0" <?php echo ($postedPage["published"] === 0) ? "selected" : null; ?>>Draft</option>
      <option value="1" <?php echo ($postedPage["published"] === 1) ? "selected" : null; ?>>Published</option>
    </select>
  </label> <br>
  <br>

  <input type="submit" name="add_page" value="Create this page">
</form>

<?php
} // end if action = add



// ===========================================================================
// EDIT

elseif ($action === "edit") {

  $editedPage = [
    "id" => $resourceId, // is 0 when the post request is received and no id params exist in the url
    "title" => "",
    "url_name" => "",
    "content" => "",
    "menu_priority" => 0,
    "parent_page_id" => 0,
    "editable_by_all" => 0,
    "published" => 0,
    "user_id" => 0
  ];

  if (isset($_POST["edited_page_id"]))
    $editedPage["id"] = (int)$_POST["edited_page_id"];

  $query = $db->prepare('SELECT * FROM pages WHERE id = :id');
  $query->execute(["id" => $editedPage["id"]]);
  $currentPage = $query->fetch();

  if ($currentPage === false)
    redirect(["action" => "show", "id" => $editedPage["id"], "error" => "unknowpage"]);

  elseif ($isUserAdmin === false && $currentPage["user_id"] !== $currentUserId && $currentPage["editable_by_all"] === 0) {
    // user is a writer that tries to edit a page he didn't created and that is not editable by all
    redirect(["action" => "show", "id" => $editedPage["id"], "error" => "editforbidden"]);
  }


  if (isset($_POST["edited_page_id"])) {
    foreach($editedPage as $key => $value) {
      if (isset($_POST[$key])) {
        if ($value === 0) {
          if ($key === "editable_by_all")
            $_POST[$key] === "on" ? $editedPage[$key] = 1 : null;
          else
            $editedPage[$key] = (int)$_POST[$key];
        }
        else
          $editedPage[$key] = $_POST[$key];
      }
    }

    // check for title format
    if (strlen($editedPage["title"]) < $minTitleLength)
      $errorMsg .= "The title must be at least $minTitleLength characters long. <br>";


    // check for url name format
    if (checkPatterns("/$urlNamePattern/", $editedPage["url_name"]) === false)
      $errorMsg .= "The URL name has the wrong format. Minimum 2 letters, numbers, hyphens or underscores. <br>";

    // check that the url name doesn't already exist
    $query = $db->prepare('SELECT id, title FROM pages WHERE url_name = :url_name AND id <> :own_id');
    $query->execute(["url_name" => $editedPage["url_name"], "own_id" => $editedPage["id"]]);
    $page = $query->fetch();

    if ($page !== false)
      $errorMsg .= "The page with id ".$page["id"]." and title '".htmlspecialchars($page["title"])."' already has the URL name '".htmlspecialchars($editedPage["url_name"])."' . <br>";


    if ($editedPage["parent_page_id"] !== 0) {
      // check the id of the parent page, that it's indeed a parent page (a page that isn't a child)
      if ($editedPage["parent_page_id"] === $editedPage["id"])
        $errorMsg .= "The page can not be parented to itself. <br>";

      else {
        $query = $db->prepare('SELECT id, parent_page_id FROM pages WHERE id = :parent_id AND id <> :own_id');
        $query->execute(["parent_id" => $editedPage["parent_page_id"], "own_id" => $editedPage["id"]]);
        $page = $query->fetch();

        if ($page === false) {
          $errorMsg .= "The parent page with id '".$editedPage["parent_page_id"]."' does not exist . <br>";
          $editedPage["parent_page_id"] = 0;
        }
        elseif ($page["parent_page_id"] !== null) {
          $errorMsg .= "The selected parent page (with id '".$page["id"]."') is actually a children of another page (with id '".$page["parent_page_id"]."'), so it can't be a parent page itself. <br>";
          $editedPage["parent_page_id"] = 0;
        }
      }
    }


    // check that user actually exists
    $query = $db->prepare('SELECT id FROM users WHERE id = :id');
    $query->execute(["id" => $editedPage["user_id"]]);
    $user = $query->fetch();

    if ($user === false) {
      $errorMsg .= "User with id '".$editedPage["user_id"]."' doesn't exists. <br>";
      $editedPage["user_id"] = $currentUserId;
    }

    // no check on content
    // other data have already been converted to int

    if ($errorMsg === "") {
      // OK no error, let's add the page
      $query = $db->prepare(
        'UPDATE pages SET title=:title, url_name=:url_name, content=:content, menu_priority=:menu_priority, parent_page_id=:parent_page_id, editable_by_all=:editable_by_all, published=:published, user_id=:user_id
        WHERE id=:id'
      );

      $dbData = $editedPage;
      if ($dbData["parent_page_id"] === 0)
        unset($dbData["parent_page_id"]);
      $success = $query->execute($dbData);

      if ($success)
        $infoMsg .= "Page edited with success. <br>";
      else
        $errorMsg .= "There was an error editing the page !";
    }
  }
  else
    $editedPage = $currentPage;
?>

<h2>Edit page with id <?php echo $editedPage["id"]; ?></h2>

<?php require_once "messages-template.php"; ?>

<form action="?section=pages&action=edit" method="post">
  <label>Title : <input type="text" name="title" required pattern=".{<?php echo $minTitleLength; ?>,}" value="<?php echo htmlspecialchars($editedPage["title"]); ?>"></label> <br>
  <br>

  <label>URL name : <input type="text" name="url_name" required pattern="<?php echo $urlNamePattern; ?>" value="<?php echo htmlspecialchars($editedPage["url_name"]); ?>"></label> <?php createTooltip("The 'beautiful' URL of the page. Can only contains letters, numbers, hyphens and underscores."); ?> <br>
  <br>

  <label>Content : <br>
  <textarea name="content" cols="60" rows="15"><?php echo $editedPage["content"]; ?></textarea><br>
  <br>

  <label>Menu Priority : <input type="number" name="menu_priority" required pattern="[-]?[0-9]{1,}" value="<?php echo $editedPage["menu_priority"]; ?>"></label> <?php createTooltip("Determines the order in which the pages are shown in the menu. Lower priority = first."); ?> <br>
  <br>

  <label>Parent page :
    <select name="parent_page_id">
      <option value="0">None</option>
<?php
$topLevelPages = $db->prepare('SELECT id, title FROM pages WHERE parent_page_id IS NULL AND id <> :id ORDER BY title ASC');
$topLevelPages->execute(["id" => $editedPage["id"]]);
// $topLevelPages = $query->fetch();
?>
      <?php while($page = $topLevelPages->fetch()): ?>
      <option value="<?php echo $page["id"]; ?>" <?php echo ($editedPage["parent_page_id"] === $page["id"]) ? "selected" : null; ?>><?php echo $page["title"]; ?></option>
      <?php endwhile; ?>
    </select>
  </label> <br>
  <br>

  <label>Can be edited by any user : <input type="checkbox" name="editable_by_all" <?php echo ($editedPage["editable_by_all"] === 1) ? "checked" : null; ?>> <br>
  <br>

  <label>Publication status :
    <select name="published">
      <option value="0" <?php echo ($editedPage["published"] === 0) ? "selected" : null; ?>>Draft</option>
      <option value="1" <?php echo ($editedPage["published"] === 1) ? "selected" : null; ?>>Published</option>
    </select>
  </label> <br>
  <br>

  <label>Owner :
    <select name="user_id">
      <?php $users = $db->query('SELECT id, name FROM users ORDER BY name ASC'); ?>
      <?php while($user = $users->fetch()): ?>
      <option value="<?php echo $user["id"]; ?>" <?php echo ($editedPage["user_id"] === $user["id"]) ? "selected" : null; ?>><?php echo $user["name"]; ?></option>
      <?php endwhile; ?>
    </select>
  </label> <br>
  <br>

  <input type="hidden" name="edited_page_id" value="<?php echo $editedPage["id"]; ?>">
  <input type="submit" name="edit_page" value="Edit">
</form>

<?php
}



// ===========================================================================
// DELETE

elseif ($action === "delete") {
  $query = $db->prepare("SELECT id, user_id FROM pages WHERE id=:id");
  $query->execute(["id"=>$resourceId]);
  $page = $query->fetch();

  $redirect = ["action" => "show", "id" => $resourceId];

  if ($page === false)
    $redirect["error"] = "unknownpage";

  elseif ($isUserAdmin == false && $page["user_id"] != $currentUserId)
    $redirect["error"] = "mustbeadmin";

  else {
    $query = $db->prepare("DELETE FROM pages WHERE id=:id");
    $success = $query->execute(["id" => $resourceId]);
    if ($success)
      $redirect["info"] = "pagedeleted";
    else
      $redirect["error"] = "errordeletepage";
  }

  redirect($redirect);
}



// ===========================================================================
// ALL ELSE

// if action == "show" or other actions are fobidden for that page
else {

  switch($errorMsg) {
    case "mustbeadmin":
      $errorMsg = "You must be an admin to do that !";
      break;
    case "unknownpage":
      $errorMsg = "Page with id $resourceId is unknow !";
      break;
    case "errordeletepage":
      $errorMsg = "There has been an error while deleting page with id $resourceId";
      break;
    // default:
    //   $errorMsg = "";
  }

  switch($infoMsg) {
    case "pagedeleted":
      $infoMsg = "Page with id $resourceId has been deleted.";
      break;
    case "pageadded":
      $infoMsg = "Page with id $resourceId has been successfully added.";
      break;
    // default:
    //   $infoMsg = "";
  }
?>

<h2>List of all pages</h2>

<?php require_once "messages-template.php"; ?>

<div>
  <a href="?section=pages&action=add">Add a page</a> <br>
</div>

<table>
  <tr>
    <th>id</th>
    <th>title</th>
    <th>URL name</th>
    <th>Parent page</th>
    <th>Menu priority</th>
    <th>creator</th>
    <th>creation date</th>
    <th>editable by all</th>
    <th>Status</th>
  </tr>

<?php
  $query = $db->query(
    'SELECT pages.*,
    users.name as user_name, 
    parent_pages.title as parent_page_title 
    FROM pages
    LEFT JOIN users ON pages.user_id=users.id 
    LEFT JOIN pages as parent_pages ON pages.parent_page_id=parent_pages.id 
    ORDER BY pages.id');

  /*$pagesById = [];
  while($page = $query->fetch())
    $pagesById[$page["page_id"]] = $page;*/

  while($page = $query->fetch()) {
?>
  <tr>
    <td><?php echo $page["id"]; ?></td>
    <td><?php echo htmlspecialchars($page["title"]); ?></td>
    <td><?php echo htmlspecialchars($page["url_name"]); ?></td>
    <td>
    <?php
    if ($page["parent_page_id"] != null)
      echo $page["parent_page_title"]." (".$page["parent_page_id"].")";
    ?>
    </td>
    <td><?php echo $page["menu_priority"]; ?></td>
    <td><?php echo htmlspecialchars($page["user_name"]); ?></td>
    <td><?php echo $page["creation_date"]; ?></td>
    <td><?php echo $page["editable_by_all"] == 1 ? "Yes": "No"; ?></td>
    <td><?php echo $page["published"] ? "Published" : "Draft"; ?></td>

    <?php if($isUserAdmin || $page["user_id"] == $currentUserId || $page["editable_by_all"] == 1): ?>
    <td><a href="?section=pages&action=edit&id=<?php echo $page["id"]; ?>">Edit</a></td>
    <?php endif; ?>

    <?php if($isUserAdmin || $page["user_id"] == $currentUserId): ?>
    <td><a href="?section=pages&action=delete&id=<?php echo $page["id"]; ?>">Delete</a></td>
    <?php endif; ?>
  </tr>
<?php
  } // end while pages from DB
?>
</table>
<?php
} // end if action = show
?>
