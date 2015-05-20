DROP TABLE IF EXISTS `zz_characters`;
CREATE TABLE `zz_characters` (
  `characterID` int(16) NOT NULL DEFAULT '0',
  `corporationID` int(16) NOT NULL DEFAULT '0',
  `allianceID` int(16) NOT NULL DEFAULT '0',
  `name` varchar(128) NOT NULL,
  `lastUpdated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `history` mediumtext,
  PRIMARY KEY (`characterID`),
  KEY `name` (`name`),
  KEY `lastUpdated` (`lastUpdated`),
  KEY `corporationID` (`corporationID`),
  KEY `allianceID` (`allianceID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED;
