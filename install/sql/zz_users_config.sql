DROP TABLE IF EXISTS `zz_users_config`;
CREATE TABLE `zz_users_config` (
  `id` int(3) NOT NULL,
  `locker` varchar(64) NOT NULL,
  `content` text NOT NULL,
  UNIQUE KEY `id` (`id`,`locker`),
  KEY `id_2` (`id`),
  KEY `locker` (`locker`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
