DROP TABLE IF EXISTS `zz_crest_killmail`;
CREATE TABLE `zz_crest_killmail` (
  `killID` int(16) NOT NULL,
  `hash` varchar(64) NOT NULL,
  `processed` smallint(1) NOT NULL DEFAULT '0',
  `dttm` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`killID`,`hash`),
  KEY `killID` (`killID`),
  KEY `hash` (`hash`),
  KEY `processed` (`processed`),
  KEY `timestamp` (`dttm`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;
