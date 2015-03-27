DROP TABLE IF EXISTS `zz_feeds`;
CREATE TABLE `zz_feeds` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(2048) NOT NULL,
  `lastFetchTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `edkStyle` tinyint(1) NOT NULL DEFAULT '0',
  `lastKillTime` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `edkStyle` (`edkStyle`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;
