-- phpMyAdmin SQL Dump
-- version 4.1.14
-- http://www.phpmyadmin.net
--
-- Client :  127.0.0.1
-- Généré le :  Lun 17 Avril 2017 à 19:04
-- Version du serveur :  5.6.17
-- Version de PHP :  5.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Base de données :  `minicms_vanilla`
--

-- --------------------------------------------------------

--
-- Structure de la table `comments`
--

CREATE TABLE IF NOT EXISTS `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `text` text NOT NULL,
  `creation_time` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=4 ;

--
-- Contenu de la table `comments`
--

INSERT INTO `comments` (`id`, `page_id`, `user_id`, `text`, `creation_time`) VALUES
(1, 5, 6, 'test comment\r\n1', 0),
(2, 2, 2, 'comment 2', 1492440914);

-- --------------------------------------------------------

--
-- Structure de la table `config`
--

CREATE TABLE IF NOT EXISTS `config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=4 ;

--
-- Contenu de la table `config`
--

INSERT INTO `config` (`id`, `name`, `value`) VALUES
(1, 'site_title', 'MySuper Websit'),
(2, 'site_directory', 'webdev-exercises/minicms-vanilla/'),
(3, 'use_url_rewrite', '1');

-- --------------------------------------------------------

--
-- Structure de la table `medias`
--

CREATE TABLE IF NOT EXISTS `medias` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `creation_date` date NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=23 ;

--
-- Contenu de la table `medias`
--

INSERT INTO `medias` (`id`, `name`, `filename`, `creation_date`, `user_id`) VALUES
(17, 'media-jpg', 'test800x600-media-jpg-2016-12-09.jpg', '2016-12-09', 2),
(18, 'media-pdf', 'test800x600-media-pdf-2016-12-09.pdf', '2016-12-09', 2),
(19, 'media-png', 'test800x600-media-png-2016-12-09.png', '2016-12-09', 2),
(20, 'media-zip', 'test800x600-media-zip-2016-12-09.zip', '2016-12-09', 6),
(22, 'media-jpeg', 'test800x600-media-jpeg-2016-12-09.jpeg', '2016-12-09', 6);

-- --------------------------------------------------------

--
-- Structure de la table `pages`
--

CREATE TABLE IF NOT EXISTS `pages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `url_name` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `parent_page_id` int(10) unsigned DEFAULT NULL,
  `menu_priority` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `creation_date` date NOT NULL,
  `editable_by_all` tinyint(3) unsigned NOT NULL,
  `published` tinyint(4) NOT NULL,
  `allow_comments` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=13 ;

--
-- Contenu de la table `pages`
--

INSERT INTO `pages` (`id`, `url_name`, `title`, `content`, `parent_page_id`, `menu_priority`, `user_id`, `creation_date`, `editable_by_all`, `published`, `allow_comments`) VALUES
(2, 'sub-1', 'sub 1', 'sub 1\r\n\r\n[manifesto media-jpg Vestibulum vestibulum, est viverra sagittis imperdiet, metus turpis finibus tellus, at aliquam est arcu id dui. Proin felis felis, ultrices nec turpis nec, lacinia tristique libero. <br>\r\nCurabitur ornare euismod pretium. Pellentesque commodo accumsan mi. Nunc gravida laoreet ligula, ac porta elit blandit quis. Nulla lorem urna, maximus eget interdum in, imperdiet et turpis. Mauris sed lectus vehicula nisl porttitor fringilla. Proin suscipit varius libero. Nam iaculis purus tempor orci vulputate aliquam. Nulla vitae justo faucibus, rhoncus justo tincidunt, ullamcorper turpis. Maecenas et lacus dignissim, condimentum est eu, ullamcorper sem. Nulla accumsan pulvinar diam, id viverra elit placerat sed. Aenean nec vulputate ligula, sed molestie risus.]', 5, 1, 6, '0000-00-00', 1, 1, 0),
(5, 'parent-1', 'parent 1', 'voici la liste de nos <strong>produits</strong>.\r\n<br>\r\n[img media-pnfgg 200]\r\n<br>\r\n[img media-jpg blabla]\r\n<br>\r\n[img media-jpeg bli bli]\r\n<br>\r\n[img media-jpg]\r\n<br>\r\n[img media-jpg title="blabla" alt="blibli" height="100px" width="300px"]', NULL, 0, 6, '2016-12-05', 1, 1, 1),
(6, 'carousel', 'Carousel', 'sub 2 <br>\r\n[carousel media-jpg media-jpeg media-png]', 5, 2, 6, '2016-12-05', 1, 1, 0),
(7, 'parent-2', 'parent 2', 'parent 2', NULL, 1, 2, '2016-12-10', 0, 1, 0),
(8, 'sub-3', 'sub 3', 'test', 7, 3, 2, '2016-12-10', 0, 1, 0),
(9, 'sub-4', 'Sub 4', 'sub 4', 7, 0, 2, '2016-12-10', 0, 0, 0),
(10, 'parent-darft', 'parent darft', 'parent darft', NULL, 2, 2, '2016-12-10', 0, 0, 0),
(11, 'sub-6', 'sub 6', 'sub 6', 10, 0, 2, '2016-12-10', 1, 1, 0),
(12, 'parent-4', 'parent 4', 'parent 4', NULL, 4, 6, '2016-12-10', 1, 1, 0);

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_token` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `password_token` varchar(255) NOT NULL,
  `password_change_time` int(11) unsigned NOT NULL,
  `role` varchar(255) NOT NULL,
  `creation_date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=9 ;

--
-- Contenu de la table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_token`, `password_hash`, `password_token`, `password_change_time`, `role`, `creation_date`) VALUES
(2, 'Admin', 'florentpoujol@fastmail.com', '', '$2y$10$zfbhDR0GZWF5J10p8KToveJFMwWoXnxtaLNcfS/qXfqc/yBvE/HS6', '', 0, 'admin', '2016-11-27'),
(6, 'Writer', 'florent.poujol@gmail.com', '', '$2y$10$1ROHZaitjexiZYB5A67UyeIiD4sMw9U1HmKcX5GrY6uxQYluep0fG', '3d7a8e3bb802e39b92ec4f3f7df46cc3', 1492362261, 'writer', '2016-11-30'),
(8, 'commenter', 'poujol.florent@wanadoo.fr', '', '$2y$10$djTjWe3XMYGyid0PftMhKOuWlZ.p8k/AWKUbt3HwF3qNINdh7kL/G', '', 0, 'commenter', '2017-04-17');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
