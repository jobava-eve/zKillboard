DROP TABLE IF EXISTS `zz_campaigns`;
CREATE TABLE `zz_campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(11) NOT NULL,
  `name` varchar(256) NOT NULL,
  `start_dttm` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `end_dttm` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `is_active` int(11) NOT NULL DEFAULT '1',
  `creation_dttm` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `definition` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
