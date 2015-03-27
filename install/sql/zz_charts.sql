DROP TABLE IF EXISTS `zz_charts`;
CREATE TABLE `zz_charts` (
  `chartID` int(16) NOT NULL AUTO_INCREMENT,
  `title` varchar(64) NOT NULL,
  `type` varchar(16) NOT NULL,
  `dataset` varchar(4096) NOT NULL,
  PRIMARY KEY (`chartID`),
  KEY `dataset` (`dataset`(767))
) ENGINE=InnoDB DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;
