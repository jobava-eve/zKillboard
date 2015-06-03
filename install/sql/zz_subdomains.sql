DROP TABLE IF EXISTS `zz_subdomains`;
CREATE TABLE `zz_subdomains` (
  `subdomainID` int(11) NOT NULL AUTO_INCREMENT,
  `subdomain` varchar(64) NOT NULL,
  `alias` varchar(64) NOT NULL,
  `adfreeUntil` timestamp NULL DEFAULT NULL,
  `banner` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`subdomainID`),
  UNIQUE KEY `subdomain` (`subdomain`),
  KEY `alias` (`alias`)
) ENGINE=InnoDB AUTO_INCREMENT=132 DEFAULT CHARSET=utf8;
