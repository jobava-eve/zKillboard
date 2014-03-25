
DROP TABLE IF EXISTS `zz_participants`;
CREATE TABLE `zz_participants` (
  `killID` int(32) NOT NULL,
  `solarSystemID` int(16) NOT NULL,
  `regionID` int(16) NOT NULL DEFAULT '0',
  `dttm` datetime NOT NULL,
  `total_price` decimal(16,2) NOT NULL DEFAULT '0.00',
  `points` mediumint(4) NOT NULL,
  `number_involved` smallint(4) NOT NULL,
  `isVictim` tinyint(1) NOT NULL,
  `shipTypeID` mediumint(8) unsigned NOT NULL,
  `groupID` mediumint(8) unsigned NOT NULL,
  `vGroupID` mediumint(8) unsigned NOT NULL,
  `weaponTypeID` mediumint(8) unsigned NOT NULL,
  `shipPrice` decimal(16,2) NOT NULL,
  `damage` int(8) NOT NULL,
  `factionID` int(16) NOT NULL,
  `allianceID` int(16) NOT NULL,
  `corporationID` int(16) NOT NULL,
  `characterID` int(16) NOT NULL,
  `finalBlow` tinyint(1) NOT NULL,
  KEY `number_involved` (`number_involved`),
  KEY `shipTypeID_index` (`shipTypeID`),
  KEY `killID` (`killID`,`dttm`),
  KEY `killID_isVictim` (`killID`,`isVictim`),
  KEY `total_price_killID` (`killID`,`total_price`),
  KEY `dttm` (`dttm`),
  KEY `allianceID` (`allianceID`,`isVictim`),
  KEY `characterID` (`characterID`,`isVictim`),
  KEY `corporationID` (`corporationID`,`isVictim`),
  KEY `regionID` (`regionID`,`isVictim`),
  KEY `solarSystemID` (`solarSystemID`,`isVictim`),
  KEY `shipTypeID` (`shipTypeID`,`isVictim`),
  KEY `groupID` (`groupID`,`isVictim`),
  KEY `vGroupID` (`vGroupID`,`isVictim`),
  KEY `weaponTypeID` (`weaponTypeID`,`isVictim`),
  KEY `allianceID_dttm` (`allianceID`,`dttm`),
  KEY `characterID_dttm` (`characterID`,`dttm`),
  KEY `corporationID_dttm` (`corporationID`,`dttm`),
  KEY `regionID_dttm` (`regionID`,`dttm`),
  KEY `solarSystemID_dttm` (`solarSystemID`,`dttm`),
  KEY `shipTypeID_dttm` (`shipTypeID`,`dttm`),
  KEY `groupID_dttm` (`groupID`,`dttm`),
  KEY `vGroupID_dttm` (`vGroupID`,`dttm`),
  KEY `weaponTypeID_dttm` (`weaponTypeID`,`dttm`),
  KEY `allianceID_number_involved` (`allianceID`,`number_involved`),
  KEY `characterID_number_involved` (`characterID`,`number_involved`),
  KEY `corporationID_number_involved` (`corporationID`,`number_involved`),
  KEY `regionID_number_involved` (`regionID`,`number_involved`),
  KEY `solarSystemID_number_involved` (`solarSystemID`,`number_involved`),
  KEY `shipTypeID_number_involved` (`shipTypeID`,`number_involved`),
  KEY `groupID_number_involved` (`groupID`,`number_involved`),
  KEY `vGroupID_number_involved` (`vGroupID`,`number_involved`),
  KEY `weaponTypeID_number_involved` (`weaponTypeID`,`number_involved`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC
/*!50100 PARTITION BY RANGE (year(dttm))
(PARTITION y2007 VALUES LESS THAN (2008) ENGINE = InnoDB,
 PARTITION y2008 VALUES LESS THAN (2009) ENGINE = InnoDB,
 PARTITION y2009 VALUES LESS THAN (2010) ENGINE = InnoDB,
 PARTITION y2010 VALUES LESS THAN (2011) ENGINE = InnoDB,
 PARTITION y2011 VALUES LESS THAN (2012) ENGINE = InnoDB,
 PARTITION y2012 VALUES LESS THAN (2013) ENGINE = InnoDB,
 PARTITION y2013 VALUES LESS THAN (2014) ENGINE = InnoDB,
 PARTITION y2014 VALUES LESS THAN MAXVALUE ENGINE = InnoDB) */;

