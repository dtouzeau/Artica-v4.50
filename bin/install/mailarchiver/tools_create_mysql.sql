CREATE TABLE IF NOT EXISTS `mailarchive` (
  `idrecord` int(11) NOT NULL AUTO_INCREMENT,
  `TimeLoad` datetime NOT NULL,
  `filename` varchar(100) NOT NULL,
  `msgsubject` varchar(200) NOT NULL,
  `mailsize` int(11) NOT NULL,
  `mailfrom` varchar(200) NOT NULL,
  `rcptto` varchar(1024) NOT NULL,
  `rcptto_count` int(11) NOT NULL,
  `SpamRating` int(11) NOT NULL,
  `MessageID` varchar(100) NOT NULL,
  `msgbody_removed` tinyint(4) NOT NULL DEFAULT '0',
  `msghead` longblob NOT NULL,
  `msgbody` longblob NOT NULL,
  `msginfo_storage` tinyint(4) NOT NULL,
  PRIMARY KEY (`idrecord`),
  KEY `TimeIndex` (`TimeLoad`),
  KEY `CleanKey` (`msgbody_removed`,`TimeLoad`,`mailsize`)
);

CREATE TABLE `mailarchive_msginfo` (
`MailID` BIGINT NOT NULL ,
`MsgBody` LONGBLOB NOT NULL 
);

CREATE TABLE IF NOT EXISTS `mailarchive_recipient` (
  `RecordID` bigint(20) NOT NULL AUTO_INCREMENT,
  `MailID` bigint(20) NOT NULL,
  `RcptTo` varchar(255) NOT NULL,
  `Flag_Internal` tinyint(4) NOT NULL,
  PRIMARY KEY (`RecordID`)
);

CREATE TABLE IF NOT EXISTS `mailerstatus` (
  `id_record` int(11) NOT NULL AUTO_INCREMENT,
  `day_groupped` tinyint(4) NOT NULL,
  `hostname` varchar(50) NOT NULL,
  `daterecord` datetime NOT NULL,
  `total_size` int(11) NOT NULL,
  `total_mails` int(11) NOT NULL,
  `total_rejected` int(11) NOT NULL,
  `total_spam` int(11) NOT NULL DEFAULT '0',
  `total_spam_rating` int(11) NOT NULL DEFAULT '0',
  `total_virus` int(11) NOT NULL DEFAULT '0',
  `total_rej_bl` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id_record`),
  KEY `daterecord` (`daterecord`)
);