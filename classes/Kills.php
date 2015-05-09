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

/**
 * General stuff for getting kills and manipulating them
 */
class Kills
{
	/**
	 * Gets killmails
	 *
	 * @param $parameters an array of parameters to fetch mails for
	 * @param $allTime gets all mails from the beginning of time or not
	 * @return array
	 */
	public static function getKills($parameters = array(), $allTime = true, $includeKillDetails = true)
	{
		$tables = array();
		$orWhereClauses = array();
		$andWhereClauses = array();
		Filters::buildFilters($tables, $orWhereClauses, $andWhereClauses, $parameters, $allTime);

		$tables = array_unique($tables);
		if (sizeof($tables) == 0) $tables[] = "zz_participants p";
		if (sizeof($tables) > 2) throw new Exception("More than 2 tables not supported yet");

		$tables = array_values($tables);
		$tablePrefix = substr($tables[0], strlen($tables[0]) - 1, 1);
		$tablePrefixOther = sizeof($tables) == 2 ? substr($tables[1], strlen($tables[1]) - 1, 1) : "p";

		$query = "select distinct ${tablePrefix}.killID from ";
		$query .= implode(" left join ", array_unique($tables));
		if (sizeof($tables) == 2) $query .= " on ($tablePrefix.killID = ${tablePrefixOther}.killID) ";
		if (sizeof($andWhereClauses) || sizeof($orWhereClauses)) {
			$query .= " where ";
			if (sizeof($orWhereClauses) > 0) {
				$andOr = array_key_exists("combined", $parameters) && $parameters["combined"] == true ? " or " : " and ";
				$query .= " ( " . implode($andOr, $orWhereClauses) . " ) ";
				if (sizeof($andWhereClauses)) $query .= " and ";
			}
			if (sizeof($andWhereClauses)) $query .= implode(" and ", $andWhereClauses);
		}

		$limit = array_key_exists("limit", $parameters) ? (int)$parameters["limit"] : 100;
		$page = array_key_exists("page", $parameters) ? (int)$parameters["page"] : 1;
		$offset = ($page - 1) * $limit;

		if ($tablePrefix == "w") $orderBy = "w.killID";
		else $orderBy = array_key_exists("orderBy", $parameters) ? $parameters["orderBy"] : "${tablePrefix}.dttm";
		$orderDirection = array_key_exists("orderDirection", $parameters) ? $parameters["orderDirection"] : "desc";
		$query .= " order by $orderBy $orderDirection limit $offset, $limit";

		$cacheTime = array_key_exists("cacheTime", $parameters) ? (int)$parameters["cacheTime"] : 120;
		$cacheTime = max(120, $cacheTime);
		if (array_key_exists("log", $parameters)) Db::log($query, array());
		$kills = Db::query($query, array(), $cacheTime);
		if ($includeKillDetails == false) return $kills;
		$merged = self::getKillsDetails($kills);
		return $merged;
	}

	/**
	 * Gets details for kills
	 *
	 * @param $kills
	 * @return array
	 */
	public static function getKillsDetails($kills)
	{
		$merged = array();
		$killIDS = array();

		foreach ($kills as $kill) {
			$killIDS[] = $kill["killID"];
			$merged[$kill["killID"]] = array();
		}

		if (sizeof($killIDS)) {
			$imploded = implode(",", $killIDS);
			$victims = Db::query("select * from zz_participants where killID in ($imploded) and isVictim = 1", array(), 300);
			$finalBlows = Db::query("select * from zz_participants where killID in ($imploded) and finalBlow = 1", array(), 300);
			$info = $victims;
			$merged = self::killMerge($merged, "victim", $victims);
			$merged = self::killMerge($merged, "finalBlow", $finalBlows);
			$merged = self::killMerge($merged, "info", $info);
		}
		return $merged;
	}

	/**
	 * Merges killmail arrays
	 *
	 * @param $array1
	 * @param string $type
	 * @param $array2
	 * @return array
	 */
	private static function killMerge($array1, $type, $array2)
	{
		foreach ($array2 as $element) {
			$killid = $element["killID"];
			Info::addInfo($element);
			if (!isset($array1[$killid])) $array1[$killid] = array();
			$array1[$killid][$type] = $element;
			$array1[$killid][$type]["commentID"] = $killid;
		}
		return $array1;
	}

	/**
	 * Gets details for a kill
	 *
	 * @param $killID the killID of the kill you want details for
	 * @return array
	 */
	public static function getKillDetails($killID)
	{
		$victim = Db::queryRow("select * from zz_participants where killID = :killID and isVictim = 1", array(":killID" => $killID));
		$kill = $victim;
		$involved = Db::query("select * from zz_participants where killID = :killID and isVictim = 0 order by damage desc", array(":killID" => $killID));
		$items = self::getItems($killID);

		Info::addInfo($kill);
		Info::addInfo($victim);
		$infoInvolved = array();
		$infoItems = array();
		foreach ($involved as $i) $infoInvolved[] = Info::addInfo($i);
		unset($involved);
		foreach ($items as $i) $infoItems[] = Info::addInfo($i);
		unset($items);

		return array("info" => $kill, "victim" => $victim, "involved" => $infoInvolved, "items" => $infoItems);
	}

	public static function getItems($killID)
	{
		$json = Killmail::get($killID);
		$killArray = json_decode($json, true);
		$killTime = $killArray["killTime"];
		$items = array();
		self::addItems($items, $killArray["items"], $killTime);
		return $items;
	}

	public static function addItems(&$itemArray, $items, $killTime, $inContainer = 0, $parentFlag = 0) {
		if (is_array($items)) foreach ($items as $item) {
			$typeID = $item["typeID"];
			$item["price"] = Price::getItemPrice($typeID, $killTime);
			$item["inContainer"] = $inContainer;
			if ($inContainer) $item["flag"] = $parentFlag;
			if ($inContainer && strpos(Info::getItemName($typeID), "Blueprint")) $item["singleton"] = 2;
			unset($item["_stringValue"]);
			$itemArray[] = $item;
			$subItems = isset($item["items"]) ? $item["items"] : null;
			unset($item["items"]);
			if ($subItems != null) self::addItems($itemArray, $subItems, $killTime, 1, $item["flag"]);
		}
	}

	/**
	 * Merges two kill arrays together
	 *
	 * @param $array1
	 * @param $array2
	 * @param $maxSize
	 * @param $key
	 * @param $id
	 * @return array
	 */
	public static function mergeKillArrays($array1, $array2, $maxSize, $key, $id)
	{
		$maxSize = max(0, $maxSize);
		$resultArray = array_diff_key($array1, $array2) + $array2;
		while (sizeof($resultArray) > $maxSize) array_pop($resultArray);
		foreach ($resultArray as $killID => $kill) {
			if (!isset($kill["victim"])) continue;
			$victim = $kill["victim"];
			if ($victim[$key] == $id) $kill["displayAsLoss"] = true;
			else $kill["displayAsKill"] = true;
			$resultArray[$killID] = $kill;
		}
		return $resultArray;
	}

	/**
	 * Returns an array of the kill
	 *
	 * @param $killID the ID of the kill
	 * @return array
	 */
	public static function getArray($killID)
	{
		$jsonRaw = Killmail::get($killID);
		$decode = json_decode($jsonRaw, true);
		$killarray = Info::addInfo($decode);
		return $killarray;
	}

	/**
	 * Returns json of the kill
	 *
	 * @param $killID the ID of the kill
	 * @return string
	 */
	public static function getJson($killID)
	{
		$jsonRaw = Killmail::get($killID);
		$decoded = json_decode($jsonRaw, true);
		$killarray = Info::addInfo($decoded);
		return json_encode($killarray);
	}

	/**
	 * Returns a raw mail, that it gets from the getArray function
	 *
	 * @static
	 * @param $killID the ID of the kill
	 * @return string
	 */
	public static function getRawMail($killID, $array = array(), $edk = true)
	{
		$cacheName = $killID;
		$sem = Semaphore::fetch($killID);
		if($edk)
			$cacheName = $killID."EDK";

		// Check if the mail has already been generated, then return it from the cache..
		$Cache = Cache::get($cacheName);
		if($Cache) {
			Semaphore::release($sem);
			return $Cache;
		}

		// Find all groupIDs where they contain Deadspace
		$deadspaceIDs = array();
		$dIDs = Db::query("SELECT groupID FROM ccp_invGroups WHERE groupName LIKE '%deadspace%' OR groupName LIKE 'FW%' OR groupName LIKE 'Asteroid%'");
		foreach($dIDs as $dd)
			$deadspaceIDs[] = $dd["groupID"];

		// ADD ALL THE FLAGS!!!!!!!!!!!
		//$flags = array("(Cargo)" => 5, "(Drone Bay)" => 87, "(Implant)" => 89);
		$dbFlags = Db::query("SELECT flagText, flagID FROM ccp_invFlags", array(), 3600);
		$flags = array();
		foreach($dbFlags as $f)
			$flags[(int) $f["flagID"]] = $f["flagText"];

		if(!$array)
			$k = self::getArray($killID);
		else
			$k = $array;

		$mail = $k["killTime"] . "\n";
		$mail .= "\n";
		$mail .= "Victim: " . $k["victim"]["characterName"] . "\n";
		$mail .= "Corp: " . $k["victim"]["corporationName"] . "\n";
		if (!isset($k["victim"]["allianceName"]) || $k["victim"]["allianceName"] == "")
			$k["victim"]["allianceName"] = "None";
		$mail .= "Alliance: " . $k["victim"]["allianceName"] . "\n";
		if (!isset($k["victim"]["factionName"]) || $k["victim"]["factionName"] == "")
			$k["victim"]["factionName"] = "None";
		$mail .= "Faction: " . $k["victim"]["factionName"] . "\n";
		if (!isset($k["victim"]["shipName"]) || $k["victim"]["shipName"] == "")
			$k["victim"]["shipName"] = "None";
		$mail .= "Destroyed: " . $k["victim"]["shipName"] . "\n";
		if (!isset($k["solarSystemName"]) || $k["solarSystemName"] == "")
			$k["solarSystemName"] = "None";
		$mail .= "System: " . $k["solarSystemName"] . "\n";
		if (!isset($k["solarSystemSecurity"]) || $k["solarSystemSecurity"] == "")
			$k["solarSystemSecurity"] = (int) 0;
		$mail .= "Security: " . $k["solarSystemSecurity"] . "\n";
		if (!isset($k["victim"]["damageTaken"]) || $k["victim"]["damageTaken"] == "")
			$k["victim"]["damageTaken"] = (int) 0;
		$mail .= "Damage Taken: " . $k["victim"]["damageTaken"] . "\n\n";
		if(isset($k["attackers"]))
		{
			$mail .= "Involved parties:\n\n";
			foreach ($k["attackers"] as $inv)
			{
				// find groupID for the ship
				if(!isset($inv["shipName"])) $inv["shipName"] = "Unknown";
				$groupID = Db::queryField("SELECT groupID FROM ccp_invTypes WHERE typeName LIKE :shipName", "groupID", array(":shipName" => $inv["shipName"]));
				if(in_array($groupID, $deadspaceIDs))
				{
					// shipName isn't set, but it's an npc.. fml..
					if ($inv["finalBlow"] == 1)
						$mail .= "Name: ". $inv["shipName"] . " / " . $inv["corporationName"] . " (laid the final blow)\n";
					else
						$mail .= "Name: ". $inv["shipName"] . " / " . $inv["corporationName"] . "\n";
					$mail .= "Damage Done: " . $inv["damageDone"] . "\n\n";
				}
				else
				{
					if ($inv["finalBlow"] == 1)
						$mail .= "Name: " . $inv["characterName"] . " (laid the final blow)\n";
					else if (strlen($inv["characterName"]))
						$mail .= "Name: " . $inv["characterName"] . "\n";
					if (strlen($inv["characterName"])) $mail .= "Security: " . $inv["securityStatus"] . "\n";
					$mail .= "Corp: " . $inv["corporationName"] . "\n";
					if (!isset($inv["allianceName"]) || $inv["allianceName"] == "")
						$inv["allianceName"] = "None";
					$mail .= "Alliance: " . $inv["allianceName"] . "\n";
					if (!isset($inv["factionName"]) || $inv["factionName"] == "")
						$inv["factionName"] = "None";
					$mail .= "Faction: " . $inv["factionName"] . "\n";
					if (!isset($inv["shipName"]) || $inv["shipName"] == "")
						$inv["shipName"] = "None";
					$mail .= "Ship: " . $inv["shipName"] . "\n";
					if (!isset($inv["weaponName"]) || $inv["weaponName"] == "")
						$inv["weaponName"] = $inv["shipName"];
					$mail .= "Weapon: " . $inv["weaponName"] . "\n";
					$mail .= "Damage Done: " . $inv["damageDone"] . "\n\n";
				}
			}
		}

		$mail .= "\n";
		$dropped = array();
		$destroyed = array();
		if(isset($k["items"]))
		{
			foreach($k["items"] as $itm)
			{
				// Take the flags we get from $itemToSlot and replace it with the proper flag from the database
				if (!(isset($itm["flag"]) && isset($flags[$itm["flag"]]))) continue;
				$itm["flagName"] = $flags[$itm["flag"]];

				// create the flag!
				$copy = null;
				if($itm["singleton"] == 2)
					$copy = " (Copy)";

				$edkValidFlags = array("Cargo", "Drone Bay");
				if($edk && !in_array($itm["flagName"], $edkValidFlags))
					$flagName = null;
				else
					$flagName = " (". $itm["flagName"] . ")";


				if($itm["qtyDropped"]) // go to dropped list
				{
					$line = $itm["typeName"] . ", Qty: " . $itm["qtyDropped"] . $flagName . ($copy ? $copy : null);
					$dropped[] = $line;
				}

				if($itm["qtyDestroyed"]) // go to destroyed list
				{
					$line = $itm["typeName"] . ", Qty: " . $itm["qtyDestroyed"] . $flagName . ($copy ? $copy : null);
					$destroyed[] = $line;
				}

				if(isset($itm["items"]))
					foreach($itm["items"] as $key => $sub)
					{
						if($sub["qtyDropped"]) // go to dropped list
						{
							$line = $sub["typeName"] . ", Qty: " . $sub["qtyDropped"] . $flagName . ($copy ? $copy : null) . " (In Container)";
							$dropped[] = $line;
						}
						if($sub["qtyDestroyed"]) // go to destroyed list
						{
							$line = $sub["typeName"] . ", Qty: " . $sub["qtyDestroyed"] . $flagName . ($copy ? $copy : null) . " (In Container)";
							$destroyed[] = $line;
						}
					}
			}
		}

		if ($destroyed) {
			$mail .= "Destroyed items:\n\n";
			foreach ($destroyed as $items)
				$mail .= $items . "\n";
		}
		$mail .= "\n";
		if ($dropped) {
			$mail .= "Dropped items:\n\n";
			foreach ($dropped as $items)
				$mail .= $items . "\n";
		}

		// Store the generated mail in cache
		Cache::set($cacheName, $mail, 604800);

		Semaphore::release($sem);
		return $mail;
	}

	public static function cleanDupe($mKillID, $killID)
	{
		Db::execute("update zz_killmails set processed = 2 where killID = :mKillID", array(":mKillID" => $mKillID));
		Stats::calcStats($mKillID, false); // remove manual version from stats
	}
}
