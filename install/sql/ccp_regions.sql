DROP TABLE IF EXISTS `ccp_regions`;
CREATE TABLE `ccp_regions` (
  `regionID` int(11) NOT NULL,
  `regionName` longtext,
  `x` double DEFAULT NULL,
  `y` double DEFAULT NULL,
  `z` double DEFAULT NULL,
  `xMin` double DEFAULT NULL,
  `xMax` double DEFAULT NULL,
  `yMin` double DEFAULT NULL,
  `yMax` double DEFAULT NULL,
  `zMin` double DEFAULT NULL,
  `zMax` double DEFAULT NULL,
  `factionID` int(11) DEFAULT NULL,
  `radius` double DEFAULT NULL,
  PRIMARY KEY (`regionID`),
  KEY `ccp_regions_IX_region` (`regionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
