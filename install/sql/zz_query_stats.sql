DROP TABLE IF EXISTS `zz_query_stats`;
CREATE TABLE `zz_query_stats` (
  `hash` varchar(128) DEFAULT NULL,
  `query` text,
  `params` text,
  `selectType` varchar(256) DEFAULT NULL,
  `table` varchar(256) DEFAULT NULL,
  `queryType` varchar(256) DEFAULT NULL,
  `possibleKeys` varchar(256) DEFAULT NULL,
  `keyUsed` varchar(256) DEFAULT NULL,
  `keyLength` int(11) DEFAULT NULL,
  `ref` varchar(256) DEFAULT NULL,
  `rows` varchar(256) DEFAULT NULL,
  `extra` varchar(256) DEFAULT NULL,
  `queryTime` varchar(10) DEFAULT NULL,
  UNIQUE KEY `hash` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 DELAY_KEY_WRITE=1;
