DROP TABLE IF EXISTS `zz_stats`;
CREATE TABLE `zz_stats` (
  `type` varchar(16) NOT NULL,
  `typeID` int(11) NOT NULL,
  `groupID` int(16) NOT NULL,
  `destroyed` int(11) NOT NULL,
  `lost` int(11) NOT NULL,
  `pointsDestroyed` int(11) NOT NULL,
  `pointsLost` int(11) NOT NULL,
  `iskDestroyed` decimal(32,2) NOT NULL,
  `iskLost` decimal(32,2) NOT NULL,
  UNIQUE KEY `type` (`type`,`typeID`,`groupID`),
  KEY `typeID_2` (`typeID`),
  KEY `groupID` (`groupID`),
  KEY `typeID` (`typeID`,`groupID`),
  KEY `type_2` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED TRANSACTIONAL=0;
