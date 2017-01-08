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
// ADD OR EDIT

if ($action === "add" || $action === "edit") {

  $isEdit = false;
  if ($action === "edit")
    $isEdit = true;

  $isPost = false;

  $pageData = [
    "id" => $resourceId,
    "title" => "",
    "url_name" => "",
    "content" => "",
    "menu_priority" => 0,
    "parent_page_id" => 0,
    "editable_by_all" => 0,
    "published" => 0,
    "user_id" => $currentUserId,
  ];

  if ($isEdit) {
    if (isset($_POST["edited_page_id"])) { // POST request
      $pageData["id"] = (int)$_POST["edited_page_id"];
      $isPost = true;
    }
    // else GET request

    $pageFromDB = queryDB('SELECT * FROM pages WHERE id = ?', $pageData["id"])->fetch();

    if ($pageFromDB === false)
      redirect(["action" => "show", "id" => $pageData["id"], "error" => "unknowpage"]);

    elseif ($isUserAdmin === false && $pageFromDB["user_id"] !== $currentUserId && $pageFromDB["editable_by_all"] === 0) {
      // user is a writer that tries to edit a page he didn't created and that is not editable by all
      redirect(["action" => "show", "id" => $pageData["id"], "error" => "editforbidden"]);
    }

    if ($isPost === false)
      $pageData = $pageFromDB;
  }

  if (isset($_POST["add_page"]) || isset($_POST["edited_page_id"])) {
    // for both actions, check fields when form is submitted
    $isPost = true;

    // fill $pageData with content from the form
    foreach($pageData as $key => $value) {
      if (isset($_POST[$key])) {
        if ($value === 0) {
          if ($key === "editable_by_all")
            $_POST[$key] === "on" ? $pageData[$key] = 1 : null;
          else
            $pageData[$key] = (int)$_POST[$key];
        }
        else
          $pageData[$key] = $_POST[$key];
      }
    }

    // check for title format
    if (strlen($pageData["title"]) < $minTitleLength)
      $errorMsg .= "The title must be at least $minTitleLength characters long. \n";


    // check for url name format
    if (checkPatterns("/$urlNamePattern/", $pageData["url_name"]) === false)
      $errorMsg .= "The URL name has the wrong format. Minimum 2 letters, numbers, hyphens or underscores. \n";

    // check that the url name doesn't already exist
    $strQuery = 'SELECT id, title FROM pages WHERE url_name = :url_name';
    $dbData = ["url_name" => $pageData["url_name"]];
    if ($isEdit) {
      $strQuery .= ' AND id <> :own_id';
      $dbData["own_id"] = $pageData["id"];
    }

    $page = queryDB($strQuery, $dbData)->fetch();
    if ($page !== false)
      $errorMsg .= "The page with id ".$pageData["id"]." and title '".htmlspecialchars($pageData["title"])."' already has the URL name '".htmlspecialchars($pageData["url_name"])."' . \n";


    // no check on format of numerical fields since they are already converted to int. If the posted value wasn't numerical, it is now 0
    if ($pageData["parent_page_id"] !== 0) {
      // check the id of the parent page, that it's indeed a parent page (a page that isn't a child of another page)

      if ($isEdit && $pageData["parent_page_id"] === $pageData["id"])
        $errorMsg .= "The page can not be parented to itself. \n";

      else {
        // check that the parent page exists and that it is not itself a child
        $page = queryDB('SELECT id, parent_page_id FROM pages WHERE id = ?', $pageData["parent_page_id"])->fetch();

        if ($page === false) {
          $errorMsg .= "The parent page with id '".$pageData["parent_page_id"]."' does not exist . \n";
          $pageData["parent_page_id"] = 0;
        }
        elseif ($page["parent_page_id"] !== null) {
          $errorMsg .= "The selected parent page (with id '".$page["id"]."') is actually a children of another page (with id '".$page["parent_page_id"]."'), so it can't be a parent page itself. \n";
          $pageData["parent_page_id"] = 0;
        }
      }
    }

    if ($pageData["menu_priority"] < 0) {
      $errorMsg .= "The menu priority must be a positiv number \n";
      $pageData["menu_priority"] = 0;
    }

    if ($isEdit) {
      // check that user actually exists
      $user = queryDB('SELECT id FROM users WHERE id = ?', $pageData["user_id"])->fetch();

      if ($user === false) {
        $errorMsg .= "User with id '".$pageData["user_id"]."' doesn't exists. \n";
        $pageData["user_id"] = $currentUserId;
      }
    }

    // no check on content

    if ($errorMsg === "") { // OK no error, let's add/edit the page in DB
      $strQuery = 'INSERT INTO pages(title, url_name, content, menu_priority, parent_page_id, editable_by_all, published, user_id, creation_date)
        VALUES(:title, :url_name, :content, :menu_priority, :parent_page_id, :editable_by_all, :published, :user_id, :creation_date)';

      if ($isEdit) {
        $strQuery = 'UPDATE pages SET title=:title, url_name=:url_name, content=:content, menu_priority=:menu_priority, parent_page_id=:parent_page_id, editable_by_all=:editable_by_all, published=:published, user_id=:user_id
        WHERE id=:id';
      }

      $query = $db->prepare($strQuery);

      $dbData = $pageData;
      if ($dbData["parent_page_id"] === 0)
        $dbData["parent_page_id"] = null; // do not use unset() because the number of entries in the data will not match the number of parameters in the request (plus you actually wants the value to be updated to NULL)

      if ($isEdit === false) {
        unset($dbData["id"]);
        $dbData["user_id"] = $currentUserId;
        $dbData["creation_date"] = date("Y-m-d");
      }

      $success = $query->execute($dbData);

      if ($success) {
        if ($isEdit)
          $infoMsg .= "Page edited with success.";
        else
          redirect(["action" => "show", "id" => $db->lastInsertId(), "info" => "pageadded"]);
      }
      elseif ($isEdit)
        $errorMsg .= "There was an error editing the page";
      else
        $errorMsg .= "There was an error adding the page";
    }
  }
?>

<?php if ($isEdit): ?>
<h2>Edit page with id <?php echo $pageData["id"]; ?></h2>
<?php else: ?>
<h2>Add a new page</h2>
<?php endif; ?>

<?php require_once "messages-template.php"; ?>

<form action="?section=pages&action=<?php echo ($isEdit ? "edit" : "add"); ?>" method="post">

  <label>Title : <input type="text" name="title" required pattern=".{<?php echo $minTitleLength; ?>,}" value="<?php echo htmlspecialchars($pageData["title"]); ?>"></label> <br>
  <br>

  <label>URL name : <input type="text" name="url_name" required pattern="<?php echo $urlNamePattern; ?>" value="<?php echo htmlspecialchars($pageData["url_name"]); ?>"></label> <?php createTooltip("The 'beautiful' URL of the page. Can only contains letters, numbers, hyphens and underscores."); ?> <br>
  <br>

  <label>Content : <br>
  <textarea name="content" cols="60" rows="15"><?php echo $pageData["content"]; ?></textarea><br>
  <br>

  <label>Menu Priority : <input type="number" name="menu_priority" required pattern="[0-9]{1,}" value="<?php echo $pageData["menu_priority"]; ?>"></label> <?php createTooltip("Determines the order in which the pages are shown in the menu. Lower priority = first. Only positiv number."); ?> <br>
  <br>

  <label>Parent page :
    <select name="parent_page_id">
      <option value="0">None</option>
<?php
$topLevelPages = queryDB('SELECT id, title FROM pages WHERE parent_page_id IS NULL AND id <> ? ORDER BY title ASC', $pageData["id"]);
?>
      <?php while($page = $topLevelPages->fetch()): ?>
      <option value="<?php echo $page["id"]; ?>" <?php echo ($pageData["parent_page_id"] === $page["id"]) ? "selected" : null; ?>><?php echo $page["title"]; ?></option>
      <?php endwhile; ?>
    </select>
  </label> <br>
  <br>

  <label>Can be edited by any user : <input type="checkbox" name="editable_by_all" <?php echo ($pageData["editable_by_all"] === 1) ? "checked" : null; ?>> <br>
  <br>

  <label>Publication status :
    <select name="published">
      <option value="0" <?php echo ($pageData["published"] === 0) ? "selected" : null; ?>>Draft</option>
      <option value="1" <?php echo ($pageData["published"] === 1) ? "selected" : null; ?>>Published</option>
    </select>
  </label> <br>
  <br>

<?php if ($isUserAdmin): ?>
  <label>Owner :
    <select name="user_id">
      <?php $users = queryDB('SELECT id, name FROM users ORDER BY name ASC'); ?>
      <?php while($user = $users->fetch()): ?>
      <option value="<?php echo $user["id"]; ?>" <?php echo ($pageData["user_id"] === $user["id"]) ? "selected" : null; ?>><?php echo $user["name"]; ?></option>
      <?php endwhile; ?>
    </select>
  </label> <br>
  <br>
<?php endif; ?>

<?php if ($isEdit): ?>
  <input type="hidden" name="edited_page_id" value="<?php echo $pageData["id"]; ?>">
  <input type="submit" name="edit_page" value="Edit">
<?php else: ?>
  <input type="submit" name="add_page" value="Add">
<?php endif; ?>
</form>

<?php
} // end if action = add or edit



// ===========================================================================
// DELETE

elseif ($action === "delete") {
  $page = queryDB('SELECT id, user_id FROM pages WHERE id = ?', $resourceId)->fetch();
  $redirect = ["action" => "show", "id" => $resourceId];

  if ($page === false)
    $redirect["error"] = "unknownpage";

  elseif ($isUserAdmin == false && $page["user_id"] != $currentUserId)
    $redirect["error"] = "mustbeadmin";

  else {
    $success = queryDB('DELETE FROM pages WHERE id = ?', $resourceId, true);

    if ($success) {
      // unparent all pages that are a child of the one deleted
      queryDB('UPDATE pages SET parent_page_id = NULL WHERE parent_page_id = ?', $resourceId);
      $redirect["info"] = "pagedeleted";
    }
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

  if ($orderByTable === "")
    $orderByTable = "pages";
?>

<h2>List of all pages</h2>

<?php require_once "messages-template.php"; ?>

<div>
  <a href="?section=pages&action=add">Add a page</a>
</div>

<br>

<table>
  <tr>
    <th>id <?php echo printTableSortButtons("pages"); ?></th>
    <th>title <?php echo printTableSortButtons("pages", "title"); ?></th>
    <th>URL name <?php echo printTableSortButtons("pages", "url_name"); ?></th>
    <th>Parent page <?php echo printTableSortButtons("parent_pages", "title"); ?></th>
    <th>Menu priority <?php echo printTableSortButtons("pages", "menu_priority"); ?></th>
    <th>creator <?php echo printTableSortButtons("users", "name"); ?></th>
    <th>creation date <?php echo printTableSortButtons("pages", "creation_date"); ?></th>
    <th>editable by all <?php echo printTableSortButtons("pages", "editable_by_all"); ?></th>
    <th>Status <?php echo printTableSortButtons("pages", "published"); ?></th>
  </tr>

<?php
  $pages = queryDB(
    "SELECT pages.*,
    users.name as user_name, 
    parent_pages.title as parent_page_title, 
    parent_pages.menu_priority as parent_page_priority 
    FROM pages
    LEFT JOIN users ON pages.user_id=users.id 
    LEFT JOIN pages as parent_pages ON pages.parent_page_id=parent_pages.id 
    ORDER BY $orderByTable.$orderByField $orderDir"
  );

  while($page = $pages->fetch()) {
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

    <?php if ($page["parent_page_id"] !== null): ?>
    <td><?php echo $page["parent_page_priority"].".".$page["menu_priority"]; ?></td>
    <?php else: ?>
    <td><?php echo $page["menu_priority"]; ?></td>
    <?php endif; ?>

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
