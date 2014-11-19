<?php
/* zKillboard
 * Copyright (C) 2012-2013 EVE-KILL Team and EVSCO.
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
		global $debug, $parseAscending, $dbPersist;
		// DB connection needs to persist because we're working with temporary tables..
		$dbPersist = true;

		if (Util::isMaintenanceMode())
			return;
		if (!isset($parseAscending))
			$parseAscending = true;

		$timer = new Timer();

		$maxTime = 65 * 1000;

		$db->execute("SET SESSION wait_timeout = 120000");
		$db->execute("CREATE TEMPORARY TABLE IF NOT EXISTS zz_participants_temporary SELECT * FROM zz_participants WHERE 1 = 0");

		$numKills = 0;

		if ($debug)
			Log::log("Fetching kills for processing...");

		while ($timer->stop() < $maxTime)
		{
			if (Util::isMaintenanceMode())
			{
				self::removeTempTables();
				return;
			}
			$db->execute("DELETE FROM zz_participants_temporary");

			$minMax = $parseAscending ? "min" : "max";
			if (date("Gi") < 105)
				$minMax = "min"; // Override during CREST cache interval

			$id = $db->queryField("SELECT $minMax(killID) killID FROM zz_killmails WHERE processed = 0 AND killID > 0", "killID", array(), 0);

			if ($id === null) $id = $db->queryField("SELECT min(killID) killID FROM zz_killmails WHERE processed = 0", "killID", array(), 0);
			if ($id === null)
			{
				sleep(1);
				continue;
			}

			$result = array();
			$result[] = $db->queryRow("SELECT * FROM zz_killmails WHERE killID = :killID", array(":killID" => $id), 0);

			$processedKills = array();
			$cleanupKills = array();
			foreach ($result as $row)
			{
				$kill = json_decode(Killmail::get($row["killID"]), true);
				if (!isset($kill["killID"]))
				{
					if ($debug) Log::log("Problem with kill " . $row["killID"]);
					$db->execute("UPDATE zz_killmails set processed = 2 WHERE killid = :killid", array(":killid" => $row["killID"]));
					continue;
				}
				$killID = $kill["killID"];
				$hash = $db->queryField("SELECT hash FROM zz_killmails WHERE killID = :killID", "hash", array(":killID" => $killID));

				// Because of CREST caching AND the want for accurate prices, don't process the first hour
				// of kills until after 01:05 each day
				if (date("Gi") < 105 && $kill["killTime"] >= date("Y-m-d 00:00:00"))
				{
					sleep(1);
					continue;
				}

				// Cleanup if we're reparsing
				$cleanupKills[] = $killID;
				$numKills++;
				if ($debug)
					Log::log("Processing kill $killID");

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

				// Do some validation on the kill
				if (!self::validKill($kill))
				{
					$db->execute("UPDATE zz_killmails set processed = 3 WHERE killid = :killid", array(":killid" => $row["killID"]));
					continue;
				}

				$totalCost = 0;
				$itemInsertOrder = 0;

				$totalCost += self::processItems($kill, $killID, $kill["items"], $itemInsertOrder);
				$totalCost += self::processVictim($kill, $killID, $kill["victim"], false);
				foreach ($kill["attackers"] as $attacker)
					self::processAttacker($kill, $killID, $attacker, $kill["victim"]["shipTypeID"], $totalCost);
				$points = Points::calculatePoints($killID, true);
				$db->execute("UPDATE zz_participants_temporary set points = :points, number_involved = :numI, total_price = :tp WHERE killID = :killID", array(":killID" => $killID, ":points" => $points, ":numI" => sizeof($kill["attackers"]), ":tp" => $totalCost));

				$processedKills[] = $killID;
			}

			if (sizeof($cleanupKills))
				$db->execute("delete FROM zz_participants WHERE killID in (" . implode(",", $cleanupKills) . ")");

			$db->execute("INSERT INTO zz_participants SELECT * FROM zz_participants_temporary");
			$numProcessed = sizeof($processedKills);
			if ($numProcessed)
			{
				$db->execute("INSERT IGNORE INTO zz_stats_queue values (" . implode("), (", $processedKills) . ")");
				$db->execute("UPDATE zz_killmails set processed = 1 WHERE killID in (" . implode(",", $processedKills) . ")");
				$db->execute("INSERT INTO zz_storage (locker, contents) values ('KillsAdded', :num) on duplicate key UPDATE contents = contents + :num", array(":num" => $numProcessed));
			}
		}
		if ($numKills > 0)
			Log::log("Processed $numKills kills");

		self::removeTempTables();
	}

	private static function removeTempTables()
	{
		global $db;
		$db->execute("DROP TABLE IF EXISTS zz_participants_temporary");
	}

	private static function validKill(&$kill)
	{
		global $db;
		$victimCorp = $kill["victim"]["corporationID"] < 1000999 ? 0 : $kill["victim"]["corporationID"];
		$victimAlli = $kill["victim"]["allianceID"];

		$npcOnly = true;
		$blueOnBlue = true;
		foreach ($kill["attackers"] as $attacker)
		{
			$attackerGroupID = Info::getGroupID($attacker["shipTypeID"]);
			// A tower is involved
			if ($attackerGroupID == 365)
				return true;

			// Don't process the kill if it's NPC only
			$npcOnly &= $attacker["characterID"] == 0 && ($attacker["corporationID"] < 1999999 && $attacker["corporationID"] != 1000125);

			// Check for blue on blue
			if ($attacker["characterID"] != 0) $blueOnBlue &= $victimCorp == $attacker["corporationID"] && $victimAlli == $attacker["allianceID"];
		}
		if ($npcOnly /*|| $blueOnBlue*/)
			return false;

		return true;
	}

	/**
	 * @param boolean $isNpcVictim
	 */
	private static function processVictim(&$kill, $killID, &$victim, $isNpcVictim)
	{
		global $db;
		$dttm = (string) $kill["killTime"];

		$shipPrice = Price::getItemPrice($victim["shipTypeID"], $dttm, true);
		$groupID = Info::getGroupID($victim["shipTypeID"]);
		$regionID = Info::getRegionIDFromSystemID($kill["solarSystemID"]);

		if (!$isNpcVictim) $db->execute("
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
				      ));

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
				      ));
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
		global $itemNames, $db;

		$dttm = (string) $kill["killTime"];

		if ($itemNames == null )
		{
			$itemNames = array();
			$results = $db->query("SELECT typeID, typeName FROM ccp_invTypes", array(), 3600);
			foreach ($results as $row) {
				$itemNames[$row["typeID"]] = $row["typeName"];
			}
		}
		$typeID = $item["typeID"];
		if (isset($item["typeID"]) && isset($itemNames[$item["typeID"]]))
			$itemName = $itemNames[$item["typeID"]];
		else
			$itemName = "TypeID $typeID";

		if ($item["typeID"] == 33329 && $item["flag"] == 89)
			$price = 0.01; // Golden pod implant can't be destroyed
		else
			$price = Price::getItemPrice($typeID, $dttm, true);

		if ($isCargo && strpos($itemName, "Blueprint") !== false)
			$item["singleton"] = 2;

		if ($item["singleton"] == 2)
			$price = $price / 100;

		return ($price * ($item["qtyDropped"] + $item["qtyDestroyed"]));
	}

}
