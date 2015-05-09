DROP TABLE IF EXISTS `zz_account_wallet`;
CREATE TABLE `zz_account_wallet` (
  `characterID` int(16) NOT NULL,
  `dttm` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `refID` bigint(16) NOT NULL,
  `refTypeID` int(16) NOT NULL,
  `ownerName1` varchar(128) NOT NULL,
  `ownerID1` int(16) NOT NULL,
  `ownerName2` varchar(128) NOT NULL,
  `ownerID2` int(16) NOT NULL,
  `argName1` int(16) NOT NULL,
  `argID1` int(16) NOT NULL,
  `amount` decimal(32,2) NOT NULL,
  `balance` decimal(32,2) NOT NULL,
  `reason` varchar(512) NOT NULL,
  `taxReceiverID` int(16) NOT NULL,
  `taxAmount` decimal(32,2) NOT NULL,
  `paymentApplied` tinyint(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `refID` (`refID`),
  KEY `characterID` (`characterID`),
  KEY `dttm` (`dttm`),
  KEY `characterID_2` (`characterID`,`dttm`),
  KEY `paymentApplied` (`paymentApplied`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
