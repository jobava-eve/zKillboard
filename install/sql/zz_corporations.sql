DROP TABLE IF EXISTS `zz_corporations`;
CREATE TABLE `zz_corporations` (
  `corporationID` int(16) NOT NULL DEFAULT '0',
  `allianceID` int(16) NOT NULL DEFAULT '0',
  `name` varchar(128) NOT NULL,
  `ceoID` int(16) NOT NULL DEFAULT '0',
  `ticker` varchar(6) NOT NULL,
  `memberCount` int(4) DEFAULT NULL,
  `lastUpdated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `information` mediumtext CHARACTER SET utf16 COLLATE utf16_unicode_ci NOT NULL,
  PRIMARY KEY (`corporationID`),
  KEY `name` (`name`),
  KEY `ticker` (`ticker`),
  KEY `lastUpdated` (`lastUpdated`),
  KEY `allianceID` (`allianceID`),
  KEY `ceoID` (`ceoID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED;
