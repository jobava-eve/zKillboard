<?php
/* zKillboard
 * Copyright (C) 2012-2015 EVE-KILL Team and EVSCO.
 *
 * This program is free software: you can redistribute it and/or modify
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

class cli_parseKills implements cliCommand
{
	public function getDescription()
	{
		return "Parses killmails which have not yet been parsed. |w|Beware, this is a semi-persistent script.|n| |g|Usage: parseKills";
	}

	public function getAvailMethods()
	{
		return ""; // Space seperated list
	}

	public function getCronInfo()
	{
		return array(0 => ""); // Always run
	}

	public function execute($parameters, $db)
	{
		if (Util::isMaintenanceMode()) return;
		global $debug, $parseAscending, $dbPersist;
		// DB connection needs to persist because we're working with temporary tables..
		$dbPersist = true;

		// If maintenance mode is on, we shouldn't run..
		if (Util::isMaintenanceMode())
			return;

		// Set the way to parse kills, newest to oldest, or oldest to newest.
		if (!isset($parseAscending))
			$parseAscending = true;

		// Start the timer and set the maximum runtime.. (65 seconds)
		$timer = new Timer();
		$maxTime = 65 * 1000;

		// Set the sessions wait timeout high so it doesn't kill the connection, and create the temporary table
		$db->execute("SET SESSION wait_timeout = 120000");
		$db->execute("CREATE TEMPORARY TABLE IF NOT EXISTS zz_participants_temporary SELECT * FROM zz_participants WHERE 1 = 0");

		// Number of kills that has been processed
		$numKills = 0;

		// Tell the logfile that we're running..
		if ($debug)
			Log::log("Killmail parser running...");

		// Start the main loop
		while ($timer->stop() < $maxTime)
		{
			// If maintenance mode is on, drop the temporary table and forget the entire thing..
			if (Util::isMaintenanceMode())
			{
				self::removeTempTables();
				return;
			}
			// Make sure the temporary table is empty before we go!
			$db->execute("DELETE FROM zz_participants_temporary");

			// Parse order
			$minMax = $parseAscending ? "min" : "max";

			// Select killIDs to process
			$id = $db->queryField("SELECT $minMax(killID) killID FROM zz_killmails WHERE processed = 0 AND killID > 0", "killID", array(), 0);

			// If $id wasn't set above, we'll select the minimum killID, and go from there..
			if ($id === null)
				$id = $db->queryField("SELECT min(killID) killID FROM zz_killmails WHERE processed = 0", "killID", array(), 0);

			// If there was no killIDs set in $id, we'll just sleep for a second and continue on afterwards..
			if ($id === null)
			{
				sleep(1);
				continue;
			}

			// Fire up the results array and fetch the killmail from zz_killmails!
			$result = array();
			$result[] = $db->queryRow("SELECT * FROM zz_killmails WHERE killID = :killID", array(":killID" => $id), 0);

			$processedKills = array();
			$npcProcess = array();
			$cleanupKills = array();
			// There is actually only one result, so no need for a foreach, but whatever..
			foreach ($result as $row)
			{
				// Decode the killmail into an array
				$kill = json_decode(Killmail::get($row["killID"]), true);

				// If the killID isn't set in the killmail, it has an issue, a serious issue, so we shouldn't parse it..
				if (!isset($kill["killID"]))
				{
					if ($debug)
						Log::log("Problem with kill: " . $row["killID"]);

					$db->execute("UPDATE zz_killmails set processed = 2 WHERE killid = :killid", array(":killid" => $row["killID"]));
					continue;
				}

				// Map the killID, and get the hash from the killmail!
				$killID = $kill["killID"];

				// Hash is already in the $row array, no need to fetch it from the DB unless we have to..
				$hash = $row["hash"];

				// Lets just be sure it actually has a system id in the first place..
				$regionID = Info::getRegionIDFromSystemID($kill["solarSystemID"]);

				if($regionID == NULL)
				{
					// The killmail is faulty as hell
					$db->execute("UPDATE zz_killmails set processed = 2 WHERE killid = :killid", array(":killid" => $row["killID"]));
					continue;
				}

				// Cleanup if we're reparsing
				$cleanupKills[] = $killID;
				$numKills++;

				if ($debug)
					Log::log("Processing kill: $killID");

				// Manual mail, make sure we aren't duping an api verified mail
				if ($killID < 0)
				{
					$apiVerified= $db->queryField("SELECT count(1) count FROM zz_killmails WHERE hash = :hash AND killID > 0", "count", array(":hash" => $hash), 0);
					if ($apiVerified)
					{
						Stats::calcStats($killID, false);
						$db->execute("delete FROM zz_killmails WHERE killID = :killID", array(":killID" => $killID));
						continue;
					}
				}

				// Check for manual mails to remove
				if ($killID > 0)
				{
					$manualMailIDs = $db->query("SELECT killID FROM zz_killmails WHERE hash = :hash AND killID < 0", array(":hash" => $hash), 0);
					foreach($manualMailIDs as $row) {
						$manualMailID = $row["killID"];
						Stats::calcStats($manualMailID, false);
						$db->execute("delete FROM zz_killmails WHERE killID = :killID", array(":killID" => $manualMailID));
					}

				}

				// Lets define if it's an NPC or not
				$isNPC = self::isNPC($kill);

				// Calculate costs
				$totalCost = 0;
				$itemInsertOrder = 0;
				$totalCost += self::processItems($kill, $killID, $kill["items"], $itemInsertOrder);
				$totalCost += self::processVictim($kill, $killID, $kill["victim"]);

				// Process the attacker
				foreach ($kill["attackers"] as $attacker)
					self::processAttacker($kill, $killID, $attacker, $kill["victim"]["shipTypeID"], $totalCost);

				if($isNPC)
					$db->execute("UPDATE zz_participants_temporary SET isNPC = 1 WHERE killID = :killID", array(":killID" => $killID));

				// Calculate the points that the kill is worth
				$points = Points::calculatePoints($killID, true);

				// Insert it to the database
				$db->execute("UPDATE zz_participants_temporary set points = :points, number_involved = :numI, total_price = :tp WHERE killID = :killID", array(":killID" => $killID, ":points" => $points, ":numI" => count($kill["attackers"]), ":tp" => $totalCost));

				// Pass the killID to the $processedKills array, so we can show how many kills we've done this cycle..
				if(!$isNPC)
					$processedKills[] = $killID;
				else
					$npcProcess[] = $killID;
			}

			// If there are kills to clean up, we'll get rid of them here.. This should only be old manual mails that are now api verified tho
			if (count($cleanupKills) > 0)
				$db->execute("delete FROM zz_participants WHERE killID in (" . implode(",", $cleanupKills) . ")");

			// Insert all the data from the temporary table to the primary table, so people are happy!
			$db->execute("INSERT IGNORE INTO zz_participants SELECT * FROM zz_participants_temporary");

			// Insert data into various tables, tell the stats queue it needs to update some kills and set mails as processed
			$numProcessed = count($processedKills);
			if ($numProcessed > 0)
			{
				$db->execute("INSERT IGNORE INTO zz_stats_queue values (" . implode("), (", $processedKills) . ")");
				$db->execute("UPDATE zz_killmails set processed = 1 WHERE killID in (" . implode(",", $processedKills) . ")");
			}
			$numNPC = count($npcProcess);
			if($numNPC > 0)
				$db->execute("UPDATE zz_killmails set processed = 1 WHERE killID in (" . implode(",", $npcProcess) . ")");
		}
		if ($numKills > 0)
		{
			StatsD::gauge("kills_processed", $numKills);
			Log::log("Processed: $numKills kill(s)");
			$db->execute("INSERT INTO zz_storage (locker, contents) VALUES ('KillsAdded', :num) ON DUPLICATE KEY UPDATE contents = contents + :num", array(":num" => $numKills));
		}
		self::removeTempTables();
	}

	private static function removeTempTables()
	{
		global $db;
		$db->execute("DROP TABLE IF EXISTS zz_participants_temporary");
	}

	private static function isNPC(&$kill)
	{
		$npc = false;
		foreach ($kill["attackers"] as $attacker)
			$npc = $attacker["characterID"] == 0 && ($attacker["corporationID"] < 1999999 && $attacker["corporationID"] != 1000125) ? true : false;

		return $npc;
	}

	/**
	 * @param boolean $isNpcVictim
	 */
	private static function processVictim(&$kill, $killID, &$victim)
	{
		global $db;
		$dttm = (string) $kill["killTime"];

		$shipPrice = Price::getItemPrice($victim["shipTypeID"], $dttm, true);
		$groupID = Info::getGroupID($victim["shipTypeID"]);
		$regionID = Info::getRegionIDFromSystemID($kill["solarSystemID"]);

		$db->execute("
			INSERT INTO zz_participants_temporary
			(killID, solarSystemID, regionID, isVictim, shipTypeID, groupID, shipPrice, damage, factionID, allianceID,
			 corporationID, characterID, dttm, vGroupID)
			values
			(:killID, :solarSystemID, :regionID, 1, :shipTypeID, :groupID, :shipPrice, :damageTaken, :factionID, :allianceID,
			 :corporationID, :characterID, :dttm, :vGroupID)",
			array(
				":killID" => $killID,
				":solarSystemID" => $kill["solarSystemID"],
				":regionID" => $regionID,
				":shipTypeID" => $victim["shipTypeID"],
				":groupID" => $groupID,
				":vGroupID" => $groupID,
				":shipPrice" => $shipPrice,
				":damageTaken" => $victim["damageTaken"],
				":factionID" => $victim["factionID"],
				":allianceID" => $victim["allianceID"],
				":corporationID" => $victim["corporationID"],
				":characterID" => $victim["characterID"],
				":dttm" => $dttm,
			)
		);

		Info::addChar($victim["characterID"], $victim["characterName"]);
		Info::addCorp($victim["corporationID"], $victim["corporationName"]);
		Info::addAlli($victim["allianceID"], $victim["allianceName"]);

		return $shipPrice;
	}

	private static function processAttacker(&$kill, &$killID, &$attacker, $victimShipTypeID, $totalCost)
	{
		global $db;
		$victimGroupID = Info::getGroupID($victimShipTypeID);
		$attackerGroupID = Info::getGroupID($attacker["shipTypeID"]);
		$regionID = Info::getRegionIDFromSystemID($kill["solarSystemID"]);

		$dttm = (string) $kill["killTime"];

		$db->execute("
			INSERT INTO zz_participants_temporary
			(killID, solarSystemID, regionID, isVictim, characterID, corporationID, allianceID, total_price, vGroupID,
			factionID, damage, finalBlow, weaponTypeID, shipTypeID, groupID, dttm)
			values
			(:killID, :solarSystemID, :regionID, 0, :characterID, :corporationID, :allianceID, :total, :vGroupID,
			:factionID, :damageDone, :finalBlow, :weaponTypeID, :shipTypeID, :groupID, :dttm)",
			array(
				":killID" => $killID,
				":solarSystemID" => $kill["solarSystemID"],
				":regionID" => $regionID,
				":characterID" => $attacker["characterID"],
				":corporationID" => $attacker["corporationID"],
				":allianceID" => $attacker["allianceID"],
				":factionID" => $attacker["factionID"],
				":damageDone" => $attacker["damageDone"],
				":finalBlow" => $attacker["finalBlow"],
				":weaponTypeID" => $attacker["weaponTypeID"],
				":shipTypeID" => $attacker["shipTypeID"],
				":groupID" => $attackerGroupID,
				":dttm" => $dttm,
				":total" => $totalCost,
				":vGroupID" => $victimGroupID,
			)
		);
		Info::addChar($attacker["characterID"], $attacker["characterName"]);
		Info::addCorp($attacker["corporationID"], $attacker["corporationName"]);
		Info::addAlli($attacker["allianceID"], $attacker["allianceName"]);
	}

	/**
	 * @param integer $itemInsertOrder
	 */
	private static function processItems(&$kill, &$killID, &$items, &$itemInsertOrder, $isCargo = false, $parentFlag = 0)
	{
		global $db;
		$totalCost = 0;
		foreach ($items as $item)
		{
			$totalCost += self::processItem($kill, $killID, $item, $itemInsertOrder++, $isCargo, $parentFlag);
			if (@is_array($item["items"]))
			{
				$itemContainerFlag = $item["flag"];
				$totalCost += self::processItems($kill, $killID, $item["items"], $itemInsertOrder, true, $itemContainerFlag);
			}
		}
		return $totalCost;
	}

	/**
	 * @param integer $itemInsertOrder
	 */
	private static function processItem(&$kill, &$killID, &$item, $itemInsertOrder, $isCargo = false, $parentContainerFlag = -1)
	{
		global $db;

		$dttm = (string) $kill["killTime"];
		$typeID = $item["typeID"];
		$itemName = Db::queryField("select typeName from ccp_invTypes where typeID = :typeID", "typeName", array(":typeID" => $typeID));
		if ($itemName == null)
			$itemName = "TypeID $typeID";


		if ($item["typeID"] == 33329 && $item["flag"] == 89)
			$price = 0.01; // Golden pod implant can't be destroyed
		else
			$price = Price::getItemPrice($typeID, $dttm);

		if ($isCargo && strpos($itemName, "Blueprint") !== false)
			$item["singleton"] = 2;

		if ($item["singleton"] == 2)
			$price = $price / 100;

		return ($price * ($item["qtyDropped"] + $item["qtyDestroyed"]));
	}

}
