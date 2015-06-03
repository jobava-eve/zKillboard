DROP TABLE IF EXISTS `zz_alliances`;
CREATE TABLE `zz_alliances` (
  `allianceID` int(16) NOT NULL DEFAULT '0',
  `ticker` varchar(8) DEFAULT NULL,
  `name` varchar(128) NOT NULL,
  `executorCorpID` int(16) NOT NULL,
  `memberCount` int(8) NOT NULL,
  `lastUpdated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `information` mediumtext,
  PRIMARY KEY (`allianceID`),
  KEY `shortName` (`ticker`),
  KEY `name` (`name`),
  KEY `executorCorpID` (`executorCorpID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED;
