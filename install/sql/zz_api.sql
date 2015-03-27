DROP TABLE IF EXISTS `zz_api`;
CREATE TABLE `zz_api` (
  `userID` int(6) NOT NULL DEFAULT '0',
  `keyID` int(16) NOT NULL,
  `vCode` varchar(128) NOT NULL,
  `label` varchar(256) DEFAULT NULL,
  `errorCode` int(4) NOT NULL DEFAULT '0',
  `lastValidation` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `dateAdded` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`keyID`,`vCode`),
  UNIQUE KEY `keyID` (`keyID`),
  KEY `lastValidation` (`lastValidation`),
  KEY `userID` (`userID`),
  KEY `errorCode` (`errorCode`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC TRANSACTIONAL=1;
