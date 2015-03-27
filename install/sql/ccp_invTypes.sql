DROP TABLE IF EXISTS `ccp_invTypes`;
CREATE TABLE `ccp_invTypes` (
  `typeID` int(11) NOT NULL,
  `groupID` int(11) DEFAULT NULL,
  `typeName` varchar(100) DEFAULT NULL,
  `description` varchar(3000) DEFAULT NULL,
  `mass` double DEFAULT NULL,
  `volume` double DEFAULT NULL,
  `capacity` double DEFAULT NULL,
  `portionSize` int(11) DEFAULT NULL,
  `raceID` tinyint(3) unsigned DEFAULT NULL,
  `basePrice` decimal(19,4) DEFAULT NULL,
  `published` tinyint(1) DEFAULT NULL,
  `marketGroupID` int(11) DEFAULT NULL,
  `chanceOfDuplicating` double DEFAULT NULL,
  PRIMARY KEY (`typeID`),
  KEY `ccp_invTypes_IX_Group` (`groupID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
