DROP TABLE IF EXISTS `zz_users_crest`;
CREATE TABLE `zz_users_crest` (
  `userID` int(11) NOT NULL DEFAULT '0',
  `characterID` int(11) NOT NULL DEFAULT '0',
  `characterName` varchar(256) COLLATE utf8_bin DEFAULT NULL,
  `scopes` varchar(256) COLLATE utf8_bin DEFAULT NULL,
  `tokenType` varchar(256) COLLATE utf8_bin DEFAULT NULL,
  `characterOwnerHash` varchar(256) COLLATE utf8_bin DEFAULT NULL,
  `corporationID` int(11) DEFAULT NULL,
  `corporationName` varchar(256) COLLATE utf8_bin DEFAULT NULL,
  `corporationTicker` varchar(256) COLLATE utf8_bin DEFAULT NULL,
  `allianceID` int(11) DEFAULT NULL,
  `allianceName` varchar(256) COLLATE utf8_bin DEFAULT NULL,
  `allianceTicker` varchar(256) COLLATE utf8_bin DEFAULT NULL,
  `lastChange` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`characterID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
