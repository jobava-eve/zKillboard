DROP TABLE IF EXISTS `zz_battle_report`;
CREATE TABLE `zz_battle_report` (
  `battleID` int(11) NOT NULL AUTO_INCREMENT,
  `solarSystemID` int(16) NOT NULL,
  `dttm` varchar(16) NOT NULL,
  `options` text NOT NULL,
  `checked` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`battleID`)
) ENGINE=InnoDB AUTO_INCREMENT=3435 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
