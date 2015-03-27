DROP TABLE IF EXISTS `zz_warmails`;
CREATE TABLE `zz_warmails` (
  `killID` int(11) NOT NULL,
  `warID` int(11) NOT NULL,
  PRIMARY KEY (`killID`),
  KEY `warID` (`warID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
