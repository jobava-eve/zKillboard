DROP TABLE IF EXISTS `zz_account_balance`;
CREATE TABLE `zz_account_balance` (
  `userID` int(12) NOT NULL,
  `balance` decimal(32,2) NOT NULL,
  PRIMARY KEY (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
