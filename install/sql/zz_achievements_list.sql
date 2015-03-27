DROP TABLE IF EXISTS `zz_achievements_list`;
CREATE TABLE `zz_achievements_list` (
  `characterID` int(11) DEFAULT NULL,
  `achievementID` int(11) DEFAULT NULL,
  UNIQUE KEY `characterID` (`characterID`),
  KEY `achievementID` (`achievementID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
