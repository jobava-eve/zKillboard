<?php
/* zKillboard
 * Copyright (C) 2012-2013 EVE-KILL Team AND EVSCO.
 *
 * This program is free software: you can redistribute it AND/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

class cli_calculateRecentStatsANDRanks implements cliCommAND
{
	public function getDescription()
	{
		return "Calculates the recent stats AND ranks for all the types on the board. |g|Usage: recentStatsANDRanks <type>";
	}

	public function getAvailMethods()
	{
		return "ranks stats all"; // Space seperated list
	}

	public function getCronInfo()
	{
		return array(0 => "all");
	}

	public function execute($parameters, $db)
	{
		if (date("Gi") != 5 && !in_array('-f', $parameters)) return; // Run at 00:05
		if (sizeof($parameters) == 0 || $parameters[0] == "") CLI::out("Usage: |g|recentStatsANDRanks <type>|n| To see a list of commANDs, use: |g|methods recentStatsANDRanks", true);
		$commAND = $parameters[0];

		switch($commAND)
		{
			case "all":
				self::stats($db);
				self::ranks($db);
				break;

			case "ranks":
				self::ranks($db);
				break;

			case "stats":
				self::stats($db);
				break;

		}
	}

	private static function ranks($db)
	{
		Log::irc("Recent ranks calculation started");
		$db->execute("drop table if exists zz_ranks_temporary");
		$db->execute("create table if not exists zz_ranks_temporary like zz_ranks_recent");
		$db->execute("truncate zz_ranks_temporary");

		$types = array("faction", "alli", "corp", "pilot", "ship", "group", "system", "region");
		$indexed = array();

		foreach($types as $type) {
			Log::log("Calcing ranks for $type");
			$db->execute("truncate zz_ranks_temporary");
			$exclude = $type == "corp" ? "AND typeID > 1100000" : "";
			$db->execute("INSERT INTO zz_ranks_temporary (SELECT * FROM (SELECT type, typeID, sum(destroyed) shipsDestroyed, null sdRank, sum(lost) shipsLost, null slRank, null shipEff, sum(pointsDestroyed) pointsDestroyed, null pdRank, sum(pointsLost) pointsLost, null plRank, null pointsEff, sum(iskDestroyed) iskDestroyed, null idRank, sum(iskLost) iskLost, null ilRank, null iskEff, null overallRank FROM zz_stats_recent WHERE type = '$type' $exclude group by type, typeID) as f)");

			if ($type == "system" or $type == "region") {
				$db->execute("UPDATE zz_ranks_temporary SET shipsDestroyed = shipsLost, pointsDestroyed = pointsLost, iskDestroyed = iskLost");
				$db->execute("UPDATE zz_ranks_temporary SET shipsLost = 0, pointsLost = 0, iskLost = 0");
			}

			// Calculate efficiences
			$db->execute("UPDATE zz_ranks_temporary SET shipEff = (100*(shipsDestroyed / (shipsDestroyed + shipsLost))), pointsEff = (100*(pointsDestroyed / (pointsDestroyed + pointsLost))), iskEff = (100*(iskDestroyed / (iskDestroyed + iskLost)))");

			// Calculate Ranks for each type
			$rankColumns = array();
			$rankColumns[] = array("shipsDestroyed", "sdRank", "desc");
			$rankColumns[] = array("shipsLost", "slRank", "asc");
			$rankColumns[] = array("pointsDestroyed", "pdRank", "desc");
			$rankColumns[] = array("pointsLost", "plRank", "asc");
			$rankColumns[] = array("iskDestroyed", "idRank", "desc");
			$rankColumns[] = array("iskLost", "ilRank", "asc");
			foreach($rankColumns as $rankColumn) {
				$typeColumn = $rankColumn[0];
				$rank = $rankColumn[1];

				if (!in_array($typeColumn, $indexed)) {
					$indexed[] = $typeColumn;
					$db->execute("alter table zz_ranks_temporary add index($typeColumn, $rank)");
				}

				$db->execute("INSERT INTO zz_ranks_temporary (type, typeID, $rank) (SELECT type, typeID, @rownum:=@rownum+1 AS $rank FROM (SELECT type, typeID FROM zz_ranks_temporary ORDER BY $typeColumn desc, typeID ) u, (SELECT @rownum:=0) r) on duplicate key UPDATE $rank = values($rank)");

				$dupRanks = $db->query("SELECT * FROM (SELECT $typeColumn n, min($rank) r, count(*) c FROM zz_ranks_temporary WHERE type = '$type' group by 1) f WHERE c > 1");
				foreach($dupRanks as $dupRank) {
					$num = $dupRank["n"];
					$newRank = $dupRank["r"];
					//CLI::out("|g|$type |r|$typeColumn |g|$num $rank |n|->|g| $newRank");
					$db->execute("UPDATE zz_ranks_temporary SET $rank = $newRank WHERE $typeColumn = $num AND type = '$type'");
				}
			}

			// Overall ranking
			$db->execute("UPDATE zz_ranks_temporary SET shipEff = 0 WHERE shipEff is null");
			$db->execute("UPDATE zz_ranks_temporary SET pointsEff = 0 WHERE pointsEff is null");
			$db->execute("UPDATE zz_ranks_temporary SET iskEff = 0 WHERE iskEff is null");
			$db->execute("INSERT INTO zz_ranks_temporary (type, typeID, overallRank) (SELECT type, typeID, @rownum:=@rownum+1 AS overallRanking FROM (SELECT type, typeID, (if (shipsDestroyed = 0, 10000000000000, ((shipsDestroyed / (pointsDestroyed + 1)) * (sdRank + idRank + pdRank))) * (1 + (1 - ((shipEff + pointsEff + iskEff) / 300)))) k, (slRank + ilRank + plRank) l FROM zz_ranks_temporary order by 3, 4 desc, typeID) u, (SELECT @rownum:=0) r) on duplicate key UPDATE overallRank = values(overallRank)");
			$db->execute("delete FROM zz_ranks_recent WHERE type = '$type'");
			$db->execute("INSERT INTO zz_ranks_recent SELECT * FROM zz_ranks_temporary");
		}
		$db->execute("drop table zz_ranks_temporary");
		$db->execute("INSERT INTO zz_ranks_progress SELECT date(now()), type, typeID, overallRank, 0 FROM zz_ranks_recent r WHERE overallRank <= 100000 on duplicate key UPDATE recentRank = r.overallRank");
		Log::irc("Recent ranks calculation finished");
	}

	private static function stats($db)
	{
		$db->execute("SET session wait_timeout = 600");

		// Fix unknown group ID's

		$result = $db->query("SELECT distinct shipTypeID FROM zz_participants WHERE groupID = 0 AND shipTypeID != 0");
		foreach ($result as $row) {
			$shipTypeID = $row["shipTypeID"];
			$groupID = Info::getGroupID($shipTypeID);
			if ($groupID == null) $groupID = 0;
			if ($groupID == 0) continue;
			$db->execute("UPDATE zz_participants SET groupID = $groupID WHERE groupID = 0 AND shipTypeID = $shipTypeID");
		}

		Log::irc("Recent stats calculation started");
		$db->execute("create table if not exists zz_stats_recent like zz_stats");
		$db->execute("truncate zz_stats_recent");

		try {
			self::recalc('faction', 'factionID', true, $db);
			self::recalc('alli', 'allianceID', true, $db);
			self::recalc('corp', 'corporationID', true, $db);
			self::recalc('pilot', 'characterID', true, $db);
			self::recalc('group', 'groupID', true, $db);
			self::recalc('ship', 'shipTypeID', true, $db);
			self::recalc('system', 'solarSystemID', false, $db);
			self::recalc('region', 'regionID', false, $db);
		} catch (Exception $e) {
			print_r($e);
		}
		Log::irc("Finished recent stat calculations");
	}

	/**
	 * @param string $type
	 * @param string $column
	 */
	private static function recalc($type, $column, $calcKills = true, $db)
	{
		$db->execute("DROP TABLE IF EXISTS zz_stats_temporary");
		$db->execute("
				CREATE TABLE `zz_stats_temporary` (
					`killID` int(16) NOT NULL,
					`groupName` varchar(16) NOT NULL,
					`groupNum` int(16) NOT NULL,
					`groupID` int(16) NOT NULL,
					`points` int(16) NOT NULL,
					`price` decimal(16,2) NOT NULL,
					PRIMARY KEY (`killID`,`groupName`,`groupNum`,`groupID`)
					) ENGINE=InnoDB");

		$exclude = "$column != 0";

		// Skip kills that are set to processed 9
		$db->execute("INSERT IGNORE INTO zz_stats_temporary
			(
				SELECT killID, '$type', $column, groupID, points, total_price
				FROM zz_participants
				WHERE $exclude
				AND isVictim = 1
				AND (vGroupID NOT IN (31, 237, 29))
				AND dttm > date_sub(now(), interval 90 day)
				AND characterID != 0
				AND isNPC = 0
			)
		");
		$db->execute("INSERT INTO zz_stats_recent (type, typeID, groupID, lost, pointsLost, iskLost)
			(
				SELECT groupName, groupNum, groupID, count(killID), sum(points), sum(price) FROM zz_stats_temporary group by 1, 2, 3
			)
		");

		if ($calcKills) {
			$db->execute("truncate table zz_stats_temporary");
			$db->execute("INSERT IGNORE INTO zz_stats_temporary
				(
					SELECT killID, '$type', $column, vGroupID, points, total_price
					FROM zz_participants
					WHERE $exclude
					AND isVictim = 0
					AND (vGroupID NOT IN (31, 237, 29))
					AND dttm > date_sub(now(), interval 90 day)
					AND characterID != 0
					AND isNPC = 0
				)
			");
			$db->execute("INSERT INTO zz_stats_recent (type, typeID, groupID, destroyed, pointsDestroyed, iskDestroyed) (SELECT groupName, groupNum, groupID, count(killID), sum(points), sum(price) FROM zz_stats_temporary group by 1, 2, 3) on duplicate key UPDATE destroyed = values(destroyed), pointsDestroyed = values(pointsDestroyed), iskDestroyed = values(iskDestroyed)");
		}

		$db->execute("DROP TABLE IF EXISTS zz_stats_temporary");
	}
}