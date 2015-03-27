DROP TABLE IF EXISTS `ccp_dgmTypeAttributes`;
CREATE TABLE `ccp_dgmTypeAttributes` (
  `typeID` int(11) NOT NULL,
  `attributeID` smallint(6) NOT NULL,
  `valueInt` int(11) DEFAULT NULL,
  `valueFloat` double DEFAULT NULL,
  PRIMARY KEY (`typeID`,`attributeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
