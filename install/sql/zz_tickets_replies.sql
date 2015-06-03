DROP TABLE IF EXISTS `zz_tickets_replies`;
CREATE TABLE `zz_tickets_replies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `belongsTo` int(11) NOT NULL,
  `name` varchar(512) NOT NULL,
  `reply` mediumtext NOT NULL,
  `moderator` int(11) NOT NULL DEFAULT '0',
  `datePosted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;
