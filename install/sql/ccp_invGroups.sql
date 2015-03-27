DROP TABLE IF EXISTS `ccp_invGroups`;
CREATE TABLE `ccp_invGroups` (
  `groupID` int(11) NOT NULL,
  `categoryID` int(11) DEFAULT NULL,
  `groupName` varchar(100) DEFAULT NULL,
  `description` varchar(3000) DEFAULT NULL,
  `iconID` int(11) DEFAULT NULL,
  `useBasePrice` tinyint(1) DEFAULT NULL,
  `allowManufacture` tinyint(1) DEFAULT NULL,
  `allowRecycler` tinyint(1) DEFAULT NULL,
  `anchored` tinyint(1) DEFAULT NULL,
  `anchorable` tinyint(1) DEFAULT NULL,
  `fittableNonSingleton` tinyint(1) DEFAULT NULL,
  `published` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`groupID`),
  KEY `ccp_invGroups_IX_category` (`categoryID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
