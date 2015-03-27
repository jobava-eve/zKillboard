DROP TABLE IF EXISTS `ccp_zwormhole_info`;
CREATE TABLE `ccp_zwormhole_info` (
  `solarSystemID` int(11) NOT NULL DEFAULT '0',
  `class` tinyint(3) unsigned DEFAULT NULL,
  `effectID` int(11) DEFAULT NULL,
  `effectName` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`solarSystemID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;