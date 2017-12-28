CREATE TABLE `categories` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  `slug` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL
);

CREATE TABLE `comments` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  `page_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `text` text NOT NULL,
  `creation_time` int(11) NOT NULL
);

CREATE TABLE `medias` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  `slug` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `creation_date` date NOT NULL,
  `user_id` int(11) NOT NULL
);

CREATE TABLE `messages` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  `type` varchar(255) NOT NULL,
  `text` text NOT NULL,
  `session_id` varchar(255) NOT NULL
);

CREATE TABLE `pages` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  `slug` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `parent_page_id` int(10) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `user_id` int(10) NOT NULL,
  `creation_date` date NOT NULL,
  `published` tinyint(4) NOT NULL,
  `allow_comments` int(10) NOT NULL
);

CREATE TABLE `users` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_token` varchar(255),
  `password_hash` varchar(255),
  `password_token` varchar(255),
  `password_change_time` int(11),
  `role` varchar(255) NOT NULL,
  `creation_date` date NOT NULL,
  `is_banned` tinyint(4) DEFAULT 0 NOT NULL
);

CREATE TABLE `menus` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `structure` text,
  `in_use` tinyint(4) DEFAULT NULL
);
