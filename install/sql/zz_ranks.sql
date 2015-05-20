DROP TABLE IF EXISTS `zz_ranks`;
CREATE TABLE `zz_ranks` (
  `type` varchar(16) NOT NULL,
  `typeID` int(16) NOT NULL,
  `shipsDestroyed` int(16) NOT NULL,
  `sdRank` mediumint(16) NOT NULL,
  `shipsLost` int(16) NOT NULL,
  `slRank` mediumint(16) NOT NULL,
  `shipEff` decimal(3,1) NOT NULL,
  `pointsDestroyed` int(16) NOT NULL,
  `pdRank` mediumint(16) NOT NULL,
  `pointsLost` int(16) NOT NULL,
  `plRank` mediumint(16) NOT NULL,
  `pointsEff` decimal(3,1) NOT NULL,
  `iskDestroyed` decimal(32,2) NOT NULL,
  `idRank` mediumint(16) NOT NULL,
  `iskLost` decimal(32,2) NOT NULL,
  `ilRank` mediumint(16) NOT NULL,
  `iskEff` decimal(3,1) NOT NULL,
  `overallRank` mediumint(16) NOT NULL,
  PRIMARY KEY (`type`,`typeID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=COMPRESSED TRANSACTIONAL=0;
