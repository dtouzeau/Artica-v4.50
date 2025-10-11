SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
CREATE TABLE IF NOT EXISTS `cryptokeys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain_id` int(11) NOT NULL,
  `flags` int(11) NOT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `content` text,
  PRIMARY KEY (`id`),
  KEY `domainidindex` (`domain_id`)
) ENGINE=MyISAM DEFAULT AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `domainmetadata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain_id` int(11) NOT NULL,
  `kind` varchar(16) DEFAULT NULL,
  `content` text,
  PRIMARY KEY (`id`),
  KEY `domainmetaidindex` (`domain_id`)
) ENGINE=MyISAM DEFAULT AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `domains` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `master` varchar(128) DEFAULT NULL,
  `last_check` int(11) DEFAULT NULL,
  `type` varchar(6) NOT NULL,
  `notified_serial` int(11) DEFAULT NULL,
  `account` varchar(40) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_index` (`name`)
) ENGINE=InnoDB  DEFAULT AUTO_INCREMENT=2 ;



CREATE TABLE IF NOT EXISTS `perm_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `descr` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT AUTO_INCREMENT=62 ;


INSERT INTO `perm_items` (`id`, `name`, `descr`) VALUES
(41, 'zone_master_add', 'User is allowed to add new master zones.'),
(42, 'zone_slave_add', 'User is allowed to add new slave zones.'),
(43, 'zone_content_view_own', 'User is allowed to see the content and meta data of zones he owns.'),
(44, 'zone_content_edit_own', 'User is allowed to edit the content of zones he owns.'),
(45, 'zone_meta_edit_own', 'User is allowed to edit the meta data of zones he owns.'),
(46, 'zone_content_view_others', 'User is allowed to see the content and meta data of zones he does not own.'),
(47, 'zone_content_edit_others', 'User is allowed to edit the content of zones he does not own.'),
(48, 'zone_meta_edit_others', 'User is allowed to edit the meta data of zones he does not own.'),
(49, 'search', 'User is allowed to perform searches.'),
(50, 'supermaster_view', 'User is allowed to view supermasters.'),
(51, 'supermaster_add', 'User is allowed to add new supermasters.'),
(52, 'supermaster_edit', 'User is allowed to edit supermasters.'),
(53, 'user_is_ueberuser', 'User has full access. God-like. Redeemer.'),
(54, 'user_view_others', 'User is allowed to see other users and their details.'),
(55, 'user_add_new', 'User is allowed to add new users.'),
(56, 'user_edit_own', 'User is allowed to edit their own details.'),
(57, 'user_edit_others', 'User is allowed to edit other users.'),
(58, 'user_passwd_edit_others', 'User is allowed to edit the password of other users.'),
(59, 'user_edit_templ_perm', 'User is allowed to change the permission template that is assigned to a user.'),
(60, 'templ_perm_add', 'User is allowed to add new permission templates.'),
(61, 'templ_perm_edit', 'User is allowed to edit existing permission templates.');


CREATE TABLE IF NOT EXISTS `perm_templ` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `descr` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT AUTO_INCREMENT=2 ;


INSERT INTO `perm_templ` (`id`, `name`, `descr`) VALUES
(1, 'Administrator', 'Administrator template with full rights.');


CREATE TABLE IF NOT EXISTS `perm_templ_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `templ_id` int(11) NOT NULL,
  `perm_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT AUTO_INCREMENT=2 ;


INSERT INTO `perm_templ_items` (`id`, `templ_id`, `perm_id`) VALUES
(1, 1, 53);

CREATE TABLE IF NOT EXISTS `records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `type` varchar(10) DEFAULT NULL,
  `content` varchar(64000) DEFAULT NULL,
  `ttl` int(11) DEFAULT NULL,
  `prio` int(11) DEFAULT NULL,
  `change_date` int(11) DEFAULT NULL,
  `articasrv` varchar(128) NOT NULL,
  `ordername` varchar(255) DEFAULT NULL,
  `auth` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rec_name_index` (`name`),
  KEY `nametype_index` (`name`,`type`),
  KEY `domain_id` (`domain_id`),
  KEY `articasrv` (`articasrv`),
  KEY `orderindex` (`ordername`)
) ENGINE=InnoDB  DEFAULT AUTO_INCREMENT=3 ;


CREATE TABLE IF NOT EXISTS `supermasters` (
  `ip` varchar(25) NOT NULL,
  `nameserver` varchar(255) NOT NULL,
  `account` varchar(40) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


CREATE TABLE IF NOT EXISTS `tsigkeys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `algorithm` varchar(255) DEFAULT NULL,
  `secret` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `namealgoindex` (`name`,`algorithm`)
) ENGINE=MyISAM DEFAULT AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL DEFAULT '0',
  `password` varchar(128) NOT NULL DEFAULT '0',
  `fullname` varchar(255) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `description` text NOT NULL,
  `perm_templ` tinyint(11) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `zones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `domain_id` int(11) NOT NULL DEFAULT '0',
  `owner` int(11) NOT NULL DEFAULT '0',
  `comment` text,
  `zone_templ_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `owner` (`owner`)
) ENGINE=InnoDB DEFAULT AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `zone_templ` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(128) NOT NULL,
  `descr` text NOT NULL,
  `owner` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `zone_templ_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `zone_templ_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(6) NOT NULL,
  `content` varchar(255) NOT NULL,
  `ttl` int(11) NOT NULL,
  `prio` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT AUTO_INCREMENT=1 ;

