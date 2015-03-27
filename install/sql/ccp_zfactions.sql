DROP TABLE IF EXISTS `ccp_zfactions`;
CREATE TABLE `ccp_zfactions` (
  `factionID` int(16) NOT NULL DEFAULT '0',
  `name` varchar(64) NOT NULL,
  `ticker` varchar(16) DEFAULT NULL,
  PRIMARY KEY (`factionID`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
LOCK TABLES `ccp_zfactions` WRITE;
INSERT INTO `ccp_zfactions` VALUES ('500001', 'Caldari State', 'caldari');
INSERT INTO `ccp_zfactions` VALUES ('500002', 'Minmatar Republic', 'minmatar');
INSERT INTO `ccp_zfactions` VALUES ('500003', 'Amarr Empire', 'amarr');
INSERT INTO `ccp_zfactions` VALUES ('500004', 'Gallente Federation', 'gallente');
INSERT INTO `ccp_zfactions` VALUES ('500007', 'Ammatar Mandate', '');
INSERT INTO `ccp_zfactions` VALUES ('500010', 'Guristas Pirates', '');
INSERT INTO `ccp_zfactions` VALUES ('500011', 'Angel Cartel', '');
INSERT INTO `ccp_zfactions` VALUES ('500012', 'Blood Raider Covenant', '');
INSERT INTO `ccp_zfactions` VALUES ('500018', 'Mordu's Legion Command', '');
INSERT INTO `ccp_zfactions` VALUES ('500019', 'Sansha's Nation', '');
INSERT INTO `ccp_zfactions` VALUES ('500020', 'Serpentis', '');
INSERT INTO `ccp_zfactions` VALUES ('500021', 'Unknown', '');

UNLOCK TABLES;
