DROP TABLE IF EXISTS `zz_battles`;
CREATE TABLE `zz_battles` (
  `battleID` int(11) NOT NULL DEFAULT '0',
  `solarSystemID` int(11) DEFAULT NULL,
  `solarSystemName` varchar(128) DEFAULT NULL,
  `regionID` int(11) DEFAULT NULL,
  `regionName` varchar(128) DEFAULT NULL,
  `dttm` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `teamAkills` int(11) DEFAULT NULL,
  `teamApilotCount` int(11) DEFAULT NULL,
  `teamApoints` int(11) DEFAULT NULL,
  `teamAinvolved` text,
  `teamAJson` longtext,
  `teamBkills` int(11) DEFAULT NULL,
  `teamBpilotCount` int(11) DEFAULT NULL,
  `teamBpoints` int(11) DEFAULT NULL,
  `teamBinvolved` text,
  `teamBJson` longtext,
  PRIMARY KEY (`battleID`),
  KEY `solarSystemID` (`solarSystemID`),
  KEY `dttm` (`dttm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPRESSED;
