DROP TABLE IF EXISTS `zz_account_history`;
CREATE TABLE `zz_account_history` (
  `purchaseID` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(16) NOT NULL,
  `dttm` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `purchase` varchar(8) NOT NULL,
  `amount` decimal(16,2) NOT NULL,
  PRIMARY KEY (`purchaseID`),
  KEY `userID` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
