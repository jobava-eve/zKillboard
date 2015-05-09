DROP TABLE IF EXISTS `ccp_dgmAttributeCategories`;
CREATE TABLE `ccp_dgmAttributeCategories` (
  `categoryID` tinyint(3) unsigned NOT NULL,
  `categoryName` varchar(50) DEFAULT NULL,
  `categoryDescription` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`categoryID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
