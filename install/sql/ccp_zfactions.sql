DROP TABLE IF EXISTS `ccp_zfactions`
CREATE TABLE `ccp_zfactions` (
  `factionID` int(16) NOT NULL DEFAULT '0',
  `name` varchar(64) NOT NULL,
  `ticker` varchar(16) DEFAULT NULL,
  PRIMARY KEY (`factionID`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC