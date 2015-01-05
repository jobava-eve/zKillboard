DROP TABLE IF EXISTS `zz_crest_killmail`;
CREATE TABLE `zz_crest_killmail` (
	`killID` INT(16) NOT NULL,
	`hash` VARCHAR(64) NOT NULL,
	`processed` SMALLINT(1) NOT NULL DEFAULT '0',
	`dttm` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`killID`, `hash`),
	INDEX `killID` (`killID`),
	INDEX `hash` (`hash`),
	INDEX `processed` (`processed`),
	INDEX `dttm` (`dttm`)
)
COLLATE='latin1_swedish_ci'
ENGINE=InnoDB
;