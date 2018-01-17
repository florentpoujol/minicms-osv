--
-- Base de données :  `demo_minicms_osv`
--

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

--
-- Contenu de la table `categories`
--

INSERT INTO `categories` (`id`, `slug`, `title`) VALUES
(1, 'category-1', 'The Category 1');

-- --------------------------------------------------------

--
-- Structure de la table `comments`
--

CREATE TABLE IF NOT EXISTS `comments` (
  `id` int(11) NOT NULL,
  `page_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `text` text NOT NULL,
  `creation_time` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

--
-- Contenu de la table `comments`
--

INSERT INTO `comments` (`id`, `page_id`, `user_id`, `text`, `creation_time`) VALUES
(1, 2, 3, 'A great post, keep-up !', 1516203802);

-- --------------------------------------------------------

--
-- Structure de la table `medias`
--

CREATE TABLE IF NOT EXISTS `medias` (
  `id` int(10) unsigned NOT NULL,
  `slug` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `creation_date` date NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

--
-- Contenu de la table `medias`
--

INSERT INTO `medias` (`id`, `slug`, `filename`, `creation_date`, `user_id`) VALUES
(1, 'the-image', 'media-jpeg-the-image-2018-01-17.jpeg', '2018-01-17', 1),
(2, 'the-pdf', 'media-pdf-the-pdf-2018-01-17.pdf', '2018-01-17', 1);

-- --------------------------------------------------------

--
-- Structure de la table `menus`
--

CREATE TABLE IF NOT EXISTS `menus` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `structure` text,
  `in_use` tinyint(4) DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

--
-- Contenu de la table `menus`
--

INSERT INTO `menus` (`id`, `name`, `structure`, `in_use`) VALUES
(1, 'DefaultMenu', '[\n    {\n        "type": "external",\n        "name": "Admin",\n        "target": "?section=admin:users",\n        "children": [\n            {\n                "type": "external",\n                "name": "Login",\n                "target": "?section=login",\n                "children": []\n            },\n            {\n                "type": "external",\n                "name": "Logout",\n                "target": "?section=logout",\n                "children": []\n            }\n        ]\n    },\n    {\n        "type": "external",\n        "name": "Home",\n        "target": "?section=blog",\n        "children": []\n    },\n    {\n        "type": "page",\n        "name": "A totally interesting page",\n        "target": "1",\n        "children": []\n    }\n]', 1);

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `text` text NOT NULL,
  `session_id` varchar(255) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Structure de la table `pages`
--

CREATE TABLE IF NOT EXISTS `pages` (
  `id` int(10) unsigned NOT NULL,
  `slug` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `parent_page_id` int(10) unsigned DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `creation_date` date NOT NULL,
  `published` tinyint(4) NOT NULL,
  `allow_comments` int(10) unsigned NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

--
-- Contenu de la table `pages`
--

INSERT INTO `pages` (`id`, `slug`, `title`, `content`, `parent_page_id`, `category_id`, `user_id`, `creation_date`, `published`, `allow_comments`) VALUES
(1, 'sample-page', 'A sample page', '## Hello\r\n\r\nThis is a sample page that links to the uploaded PDF file ( [click click]([link:media:the-pdf])) and display a beautifull image:\r\n\r\n<img src="[link:media:the-image]">\r\n\r\nIf you are lost, you may which to read [the first post]([link:post:2]).', NULL, NULL, 1, '2018-01-17', 1, 1),
(2, 'demo-site', 'MiniCMS Demo Site', '## Welcome !\r\n\r\nYou reached the demo site for my project __MiniCMS OSV__ _(old-school - vanilla)_.\r\n\r\nThis was a self-teaching project to build a simple CMS in PHP, completely procedurally, and without any modern practices or organization.\r\n\r\nYou can [find the sources on GitHub](https://github.com/florentpoujol/minicms-osv).\r\n\r\nYou can also check out the admin section. Click the link in the "menu" above to the login page and login with any of the three users:\r\n\r\n- admin\r\n- writer\r\n- commenter\r\n\r\nTheir password is always _Qw1_', NULL, 1, 2, '2018-01-17', 1, 1);

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_token` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `password_token` varchar(255) NOT NULL,
  `password_change_time` int(11) unsigned NOT NULL,
  `role` varchar(255) NOT NULL,
  `creation_date` date NOT NULL,
  `is_banned` tinyint(4) DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4;

--
-- Contenu de la table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_token`, `password_hash`, `password_token`, `password_change_time`, `role`, `creation_date`, `is_banned`) VALUES
(1, 'admin', 'admin@minicms.osv', '', '$2y$10$8UrhBcwsNBtjHuUQPkmeR.0OFYOQjK1ep6IMM9y7jkvr1.yh1/Czq', '', 0, 'admin', '2018-01-17', 0),
(2, 'writer', 'writer@minicms.osv', '', '$2y$10$DOZpzq9tYToaifjap0e7aefX6zfLIAV/3ZWHO4PCNQMA487Jgz0hm', '', 0, 'writer', '2018-01-17', 0),
(3, 'commenter', 'commenter@minicms.osv', '', '$2y$10$.CJX8ZjcXHZWJhce.D4M.eAjPjP5PmkDDNWzn.jzLfxTc8sDgtdTe', '', 0, 'commenter', '2018-01-17', 0);

--
-- Index pour les tables exportées
--

--
-- Index pour la table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `medias`
--
ALTER TABLE `medias`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables exportées
--

--
-- AUTO_INCREMENT pour la table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT pour la table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT pour la table `medias`
--
ALTER TABLE `medias`
  MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT pour la table `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT pour la table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=30;
--
-- AUTO_INCREMENT pour la table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
