DROP TABLE IF EXISTS `zz_errors`;
CREATE TABLE `zz_errors` (
  `id` varchar(32) NOT NULL,
  `error` longtext NOT NULL,
  `message` text NOT NULL,
  `url` varchar(1024) NOT NULL,
  `ip` varchar(15) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC TRANSACTIONAL=0;
