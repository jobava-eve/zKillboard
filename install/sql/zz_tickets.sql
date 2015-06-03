DROP TABLE IF EXISTS `zz_tickets`;
CREATE TABLE `zz_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `name` varchar(512) NOT NULL,
  `email` varchar(512) NOT NULL,
  `tags` varchar(512) NOT NULL,
  `ticket` mediumtext NOT NULL,
  `killID` int(11) NOT NULL,
  `status` int(11) NOT NULL DEFAULT '1',
  `datePosted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;
