# Mini CMS - Handmade

The point of this project is to practice web development by creating a mini-CMS and a small website with it without using any frameworks or non-native libraries.

It must use only the bases technologies : HTML5, CSS3, PHP7, MysQL5.6, Javascript/ES6.


## Specifications

### Front-end features

- Use of semantic HTML5, responsive and accessible
- Readable by humans on "large" screens without any CSS or JS
- Carousel : several images that cycles automatically base on time or by user input
- Manifesto : a rounded image followed by some text
- Carousel and manifesto can be added in pages via Wordpress-like shortcodes
- Main menu with two levels that is auto filled with all the pages created through the back-end
- The main menu change to a "hamburger menu" on mobile phones
- "Back to top" button with smooth scrolling


### Back-end features

- Standard login with "forgot password" feature that sends an email to the user allowing him to reset it with 48h
- Login via Twitter account

- creation, modification and deletion of users
  - user have a name, an email, a password and one of two role : admin and writer
  - admins have all rights, writer have limited rights

- creation, modification and deletion of pages
  - pages have HTML content
  - images, carousel and manifesto can be added via shortcodes
  - writers can only add pages, and modify and delete the pages they have created
  - writers can only edit the pages created by other users if it's allowed by them on a per page basis
  - pages can be a children of another page
  - pages have a "priority" which define where they are displayed in the menu
  - pages have a "nice name", used for URL rewriting

- upload and deletion of media (images)
  - they are uploaded in a particular folder and given a name which must be used in shortcodes
  - the media page show a preview with an edit and delete button (writers can only edit/delete medias they uploaded)


### Miscellaneous

- No specific organization of files or code design pattern (like MVC) should be used
- secure forms, requests to database and display of data
- writers can't do anything they aren't supposed to do, even when modifying the HTML of a form through the browser's dev tools
- check forms data with HTML, JS and PHP
- nice handling of all possible kinds of errors and success messages
- documented code
- must work with PHP7/MySQL5.6 not use any deprecated stuff
- works with or without URL rewriting, with Apache or Nginx (config files must be supplied)
- works as a subfolder or the root of a domain name
- works with or without SSL. All links to resources must adapt automatically to the protocol used.
- Once completed a backup of the database must be supplied with structure and some actual content