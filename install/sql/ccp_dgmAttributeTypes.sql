DROP TABLE IF EXISTS `ccp_dgmAttributeTypes`;
CREATE TABLE `ccp_dgmAttributeTypes` (
  `attributeID` smallint(6) NOT NULL,
  `attributeName` varchar(100) DEFAULT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `iconID` int(11) DEFAULT NULL,
  `defaultValue` double DEFAULT NULL,
  `published` tinyint(1) DEFAULT NULL,
  `displayName` varchar(100) DEFAULT NULL,
  `unitID` tinyint(3) unsigned DEFAULT NULL,
  `stackable` tinyint(1) DEFAULT NULL,
  `highIsGood` tinyint(1) DEFAULT NULL,
  `categoryID` tinyint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (`attributeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
