DROP TABLE IF EXISTS `ccp_invFlags`;
CREATE TABLE `ccp_invFlags` (
  `flagID` smallint(6) NOT NULL,
  `flagName` varchar(200) DEFAULT NULL,
  `flagText` varchar(100) DEFAULT NULL,
  `orderID` int(11) DEFAULT NULL,
  PRIMARY KEY (`flagID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
