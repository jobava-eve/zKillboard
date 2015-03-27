DROP TABLE IF EXISTS `ccp_dgmTypeEffects`;
CREATE TABLE `ccp_dgmTypeEffects` (
  `typeID` int(11) NOT NULL,
  `effectID` smallint(6) NOT NULL,
  `isDefault` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`typeID`,`effectID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
