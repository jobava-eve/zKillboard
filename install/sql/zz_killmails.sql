DROP TABLE IF EXISTS `zz_killmails`;
CREATE TABLE `zz_killmails` (
  `killID` int(32) NOT NULL,
  `processed` int(6) NOT NULL DEFAULT '0',
  `hash` varchar(64) DEFAULT NULL,
  `source` varchar(64) DEFAULT NULL,
  `insertTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `kill_json` mediumtext NOT NULL,
  UNIQUE KEY `killID` (`killID`) KEY_BLOCK_SIZE=4096,
  KEY `processed` (`processed`) KEY_BLOCK_SIZE=4096,
  KEY `hash` (`hash`),
  KEY `source` (`source`),
  KEY `insertTime` (`insertTime`),
  KEY `processed_2` (`processed`,`killID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=4;
