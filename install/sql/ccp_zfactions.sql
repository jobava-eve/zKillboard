DROP TABLE IF EXISTS `ccp_zfactions`;
CREATE TABLE `ccp_zfactions` (
  `factionID` int(16) NOT NULL DEFAULT '0',
  `name` varchar(64) NOT NULL,
  `ticker` varchar(16) DEFAULT NULL,
  PRIMARY KEY (`factionID`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
LOCK TABLES `ccp_zfactions` WRITE;
INSERT INTO `ccp_zfactions` VALUES ("500001","Caldari State","caldari"),("500002","Minmatar Republic","minmatar"),("500003","Amarr Empire","amarr"),("500004","Gallente Federation","gallente"),("500007","Ammatar Mandate",""),("500010","Guristas Pirates",""),("500011","Angel Cartel",""),("500012","Blood Raider Covenant",""),("500018","Mordu's Legion Command",""),("500019","Sansha's Nation",""),("500020","Serpentis",""),("500021","Unknown",""),("500024","Drifters","");
UNLOCK TABLES;
