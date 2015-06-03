DROP TABLE IF EXISTS `zz_storage`;
CREATE TABLE `zz_storage` (
  `locker` varchar(64) COLLATE utf8_bin NOT NULL,
  `contents` text COLLATE utf8_bin,
  PRIMARY KEY (`locker`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin ROW_FORMAT=DYNAMIC;
