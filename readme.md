# Mini CMS - Old-School - Vanilla

The point of this project is to practice back-end web development with PHP, by creating a basic CMS with some constraints :
- _old-school_: use only procedural code (no OOP) and no specific organization of files or code design pattern (like MVC),
- _vanilla_: no framework or non-native libraries.

Exceptions for file organization: use a single public `index.php` as a front-controller while all other PHP files are outside of the web root.  
Exceptions for OOP/non-native libraries: PDO, Markdown, PHP Mailer.

Efforts must still be done to keep the code reasonably clean, clear and secure.  

## General features

### Users

- 3 roles: admin, writer, commenter
- registering of new users via a public form or by admins
  - emails of new users must be validated via a link sent to their address
  - registering can be turned off globally
- Standard login via username and password
  - forgot password function that sends an email to the user allowing him to access the form to reset the password within 48h
- users can edit their infos, admins can edit everyone
- users can't delete themselves
- admins can ban users. A banned user con't logging, their content (page, post, comments) can't be displayed anymore.
- deleting a user deletes all its comments, reaffects its resources to the user that deleted it

### Medias

- upload and deletion of media (images, zip, pdf)

### Posts and categories

- standard posts linked to categories
- content is markdown
- only created by admins or writers
- can have comments (comments can be turned of on a per-post basis)
- the blog page show the last posts with a list of the categories in a sidebar

### Pages

- content is markdown
- only created by admin or writers
- can have comments (comments can be turned of on a per-page basis)
- can be children of another page (if it isn't itself a child)

### Comments

- comments can be added by any registered users on pages and posts where it's allowed
- comments can be turned off globally or on a perpage/post basis
- users can edit and delete their comments in the admin section
- writer can also see and update all their comments + the ones in their pages and posts (admins can update/delete all comments)

## Miscellaneous

- secure forms, requests to database and display of data
- full validation of data on the backend side (writers or commenters can't do anything they aren't supposed to do, even when modifying the HTML of a form through the browser's dev tools)
- nice handling of all possible kinds of errors and success messages
- emails can be sent via the local email software or SMTP
- global configuration saved as JSON can be edited via the file or by admins via a form
- must work with PHP7.0+ MySQL5.6+ not use any deprecated stuff
- works as a subfolder or the root of a domain name
- the name of the "admin folder" can be changed
- links to pages, posts, categories and medias can be added in the content via wordpress-like shortcodes. Ie: [link:mdedia:the-media-slug]
- works with or without SSL. All internals links adapt automatically to the protocol used (+ url rewrite or not).
- optionnal use of recaptcha on all public forms (set via the secret key in config)
- easy install via a script once put up on an FTP

## Install

Require PHP7.0+ and MySQL5.6+.

- Clone the repo or upload and extract the .zip from github's download.
- Set the root of the virtual host to the `public` folder.
- Make sure the `app` and `public/uploads` folders are writable
- Access the install script, fill out the required information, especially the database access, then if there is no error, you are good to go.

If you have any unexpected errors, or something doesn't seem to go right during the installation, make sure to delete the `app/config.json` file before trying again.

You will be redirected to the login page once the installation is complete.

If you need to update the configuration, you can either access the Config page via the admin menu (only admin users can do that), or directly edit the `app/config.json` file.

To run the tests, copy the `tests\config.sample.json` file into `tests\config.json` and edit the database information in it, then run `php tests.php` from the `tests` folder.

An online demo version is available at [minicms-osv.florentnpoujol.fr](http://minicms-osv.florentpoujol.fr). 
