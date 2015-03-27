DROP TABLE IF EXISTS `zz_achievements`;
CREATE TABLE `zz_achievements` (
  `achievementID` int(11) NOT NULL AUTO_INCREMENT,
  `achievementName` varchar(255) DEFAULT NULL,
  `achievementDescription` text,
  UNIQUE KEY `achievementID` (`achievementID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
