<?php if (isset($db) === false) exit(); ?>

<h1>Pages</h1>

<?php
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

    // check that the url name doesn't already exist
    $query = $db->prepare('SELECT id FROM pages WHERE url_name = :url_name');
    $query->execute(["url_name" => $postedPage["url_name"]]);
    $page = $query->fetch();

    if ($page !== false)
      $errorMsg .= "A page with the URL name '".htmlspecialchars($postedPage["url_name"])."' already exists <br>";

    // check the id of the parent page, that it's indeed a parent page (a page that isn't a child)
    if ($postedPage["parent_page_id"] !== 0) {
      $query = $db->prepare('SELECT parent_page_id FROM pages WHERE id = :id');
      $query->execute(["id" => $postedPage["parent_page_id"]]);
      $page = $query->fetch();

      if ($page !== false)
        $errorMsg .= "The parent page does not exist ! <br>";
      elseif ($page["parent_page_id"] != null)
        $errorMsg .= "The page is actually a children of another page, it can't be a parent page ! <br>";
    }

    // no check on content
    // other data have already been converted to int

    if ($errorMsg === "") {
      // OK no error, let's add the page
      $query = $db->prepare(
        'INSERT INTO pages(title, url_name, content, menu_priority, parent_page_id, editable_by_all, published, user_id, creation_date)
        VALUES(:title, :url_name, :content, :menu_priority, :parent_page_id, :editable_by_all, :published, :user_id, :creation_date)'
      );

      $dbData = $postedPage;
      if ($dbData["parent_page_id"] == 0)
        $dbData["parent_page_id"] = null;
      $dbData["user_id"] = $currentUserId;
      $dbData["creation_date"] = date("Y-m-d");
      $success = $query->execute($dbData);

      if ($success)
        redirect(["action" => "show", "id" => $db->lastInsertId(), "info" => "pageadded"]);
      else
        $errorMsg .= "There was an error registering the page";
    }
  }

  $topLevelPages = $db->query('SELECT id, title FROM pages WHERE parent_page_id IS NULL');
?>

<h2>Add a new page</h2>

<?php require_once "messages-template.php"; ?>

<form action="?section=pages&action=add" method="post">
  <label>Title : <input type="text" name="title" required value ="<?php echo htmlspecialchars($postedPage["title"]); ?>"></label> <br>
  <br>

  <label>URL name : <input type="text" name="url_name" required pattern="^[A-Za-z0-9-]{1,}$" value ="<?php echo htmlspecialchars($postedPage["url_name"]); ?>"></label> <br>
  <br>

  <label>Content : <br>
  <textarea name="content" required cols="60" rows="15"><?php echo $postedPage["content"]; ?></textarea><br>
  <br>

  <label>Menu Priority : <input type="number" name="menu_priority" required value="<?php echo $postedPage["menu_priority"]; ?>"></label> <br>
  <br>

  <label>Parent page :
    <select name="parent_page_id">
      <option value="0">None</option>
      <?php while($page = $topLevelPages->fetch()): ?>
      <option value="<?php echo $page["id"]; ?>" <?php echo ($postedPage["parent_page_id"] == $page["id"]) ? "selected" : null; ?>><?php echo $page["title"]; ?></option>
      <?php endwhile; ?>
    </select>
  </label> <br>
  <br>

  <label>Can be edited by any user : <input type="checkbox" name="editable_by_all" <?php echo ($postedPage["editable_by_all"] == 1) ? "checked" : null; ?>> <br>
  <br>

  <label>Publication status :
    <select name="published">
      <option value="0" <?php echo ($postedPage["published"] == 0) ? "selected" : null; ?>>Draft</option>
      <option value="1" <?php echo ($postedPage["published"] == 1) ? "selected" : null; ?>>Published</option>
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
    "id" => $resourceId, // will be 0 when the post request is received
    "title" => "",
    "url_name" => "",
    "content" => "",
    "menu_priority" => 0,
    "parent_page_id" => 0,
    "editable_by_all" => 0,
    "published" => 0
  ];

  if (isset($_POST["edited_page_id"]))
    $editedPage["id"] = (int)$_POST["edited_page_id"];

  $query = $db->prepare('SELECT id, user_id, editable_by_all FROM pages WHERE id = :id');
  $query->execute(["id" => $editedPage["id"]]);
  $currentPage = $query->fetch();

  if ($currentPage === false)
    redirect(["action" => "show", "id" => $editedPage["id"], "error" => "unknowpage"]);
  elseif ($isUserAdmin === false && $currentPage["user_id"] !== $currentUserId && $currentPage["editable_by_all"] === 0) {
    // user is a writer that tries to edit a page he didn't created and that is not editable by all
    redirect(["action" => "show", "id" => $editedPage["id"], "error" => "norighttoedit"]);
  }
  // first check that the user can edit the page

  if (isset($_POST["edited_page_id"])) {
    foreach($editedPage as $key => $value) {
      if (isset($_POST[$key])) {
        if ($value === 0)
          $editedPage[$key] = (int)$_POST[$key];
        else
          $editedPage[$key] = $_POST[$key];
      }
    }

    // check that the url name doesn't already exist
    $query = $db->prepare('SELECT id, title FROM pages WHERE url_name = :url_name AND id <> :own_id');
    $query->execute(["url_name" => $editedPage["url_name"], "own_id" => $editedPage["id"]]);
    $page = $query->fetch();

    if ($page !== false)
      $errorMsg .= "The page with id ".$page["id"]." and title '".htmlspecialchars($page["title"])."' already has the URL name '".htmlspecialchars($editedPage["url_name"])."' ! <br>";

    // check the id of the parent page, that it's indeed a parent page (a page that isn't a child)
    if ($editedPage["parent_page_id"] !== 0) {
      if ($editedPage["parent_page_id"] === $editedPage["id"])
        $errorMsg .= "The page can not be parented to itself  ! <br>";
      else {
        $query = $db->prepare('SELECT parent_page_id FROM pages WHERE id = :id AND id <> :own_id');
        $query->execute(["id" => $editedPage["parent_page_id"]]);
        $page = $query->fetch();

        if ($page !== false)
          $errorMsg .= "The parent page does not exist ! <br>";
        elseif ($page["parent_page_id"] !== null)
          $errorMsg .= "The page is actually a children of another page, it can't be a parent page ! <br>";
      }
    }

    // no check on content
    // other data have already been converted to int

    if ($errorMsg === "") {
      // OK no error, let's add the page
      $query = $db->prepare(
        'UPDATE pages SET title=:title, url_name=:url_name, content=:content, menu_priority=:menu_priority, parent_page_id=:parent_page_id, editable_by_all=:editable_by_all, published=:published, user_id=:user_id
        WHERE idd=:id'
      );

      $dbData = $editedPage;
      if ($dbData["parent_page_id"] === 0)
        $dbData["parent_page_id"] = null;
      // $dbData["user_id"] = $currentUserId;
      // $dbData["creation_date"] = date("Y-m-d");
      $success = $query->execute($dbData);

      if ($success)
        redirect(["action" => "show", "id" => $editedPage["id"], "info" => "pageedited"]);
      else
        $errorMsg .= "There was an error editing the page !";
    }
  }
  else
    $editedPage = $page;

  $query = $db->prepare('SELECT id, title FROM pages WHERE parent_page_id IS NULL AND id <> :id ORDER BY title ASC');
  $query->execute(["id" => $editedPage["id"]]);
  $topLevelPages = $query->fetch();

  $users = $db->query('SELECT id, name FROM users ORDER BY name ASC');
?>

<h2>Edit a new page</h2>

<?php require_once "messages-template.php"; ?>

<form action="?section=pages&action=edit" method="post">
  <label>Title : <input type="text" name="title" required value="<?php echo htmlspecialchars($editedPage["title"]); ?>"></label> <br>
  <br>

  <label>URL name : <input type="text" name="url_name" required pattern="^[A-Za-z0-9-]{1,}$" value="<?php echo htmlspecialchars($editedPage["url_name"]); ?>"></label> <br>
  <br>

  <label>Content : <br>
  <textarea name="content" required cols="60" rows="15"><?php echo $editedPage["content"]; ?></textarea><br>
  <br>

  <label>Menu Priority : <input type="number" name="menu_priority" required value="<?php echo $editedPage["menu_priority"]; ?>"></label> <br>
  <br>

  <label>Parent page :
    <select name="parent_page_id">
      <option value="0">None</option>
      <?php while($page = $topLevelPages->fetch()): ?>
      <option value="<?php echo $page["id"]; ?>" <?php echo ($editedPage["parent_page_id"] == $page["id"]) ? "selected" : null; ?>><?php echo $page["title"]; ?></option>
      <?php endwhile; ?>
    </select>
  </label> <br>
  <br>

  <label>Can be edited by any user : <input type="checkbox" name="editable_by_all" <?php echo ($editedPage["editable_by_all"] == 1) ? "checked" : null; ?>> <br>
  <br>

  <label>Publication status :
    <select name="published">
      <option value="0" <?php echo ($editedPage["published"] == 0) ? "selected" : null; ?>>Draft</option>
      <option value="1" <?php echo ($editedPage["published"] == 1) ? "selected" : null; ?>>Published</option>
    </select>
  </label> <br>
  <br>

  <label>Creator :
    <select name="user_id">
      <option value="0">None</option> <!-- this can happen if the user was deleted -->
      <?php while($user = $users->fetch()): ?>
      <option value="<?php echo $user["id"]; ?>" <?php echo ($editedPage["user_id"] == $user["id"]) ? "selected" : null; ?>><?php echo $user["name"]; ?></option>
      <?php endwhile; ?>
    </select>
  </label> <br>
  <br>

  <input type="hidden" name="edited_page_id" value="<?php echo $editedPage["id"]; ?>">
  <input type="submit" name="edit_page" value="Create this page">
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
