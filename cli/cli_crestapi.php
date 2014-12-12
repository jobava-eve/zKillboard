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

class cli_crestapi implements cliCommand
{
	public function getDescription()
	{
		return "Processes and converts external killmail links";
	}

	/**
	 * @return string|array
	 */
	public function getAvailMethods()
	{
		return ""; // Space seperated list
	}

	public function getCronInfo()
	{
		return array(0 => "");
	}

	/**
	 * @param array $parameters
	 * @param Database $db
	 */
	public function execute($parameters, $db)
	{
		global $debug, $baseAddr;
		$count = 0;
		$timer = new Timer();

		$db->execute("update zz_crest_killmail set processed = 0 where processed < -500", array(), false, false);
		Log::log("Starting CREST API killmail parsing");
		while ($timer->stop() < 59000)
		{
			// Get the killmail data
			$data = $db->queryRow("SELECT * FROM zz_crest_killmail WHERE processed = 0 ORDER BY timestamp DESC LIMIT 1", array(), 0);
			try
			{
				// Bind the data to variables
				$killID = $data["killID"];
				$hash = trim($data["hash"]);
				if($debug)
					Log::log("CREST API: Processing kill $killID");

				// Get the data from CREST
				$url = "http://public-crest.eveonline.com/killmails/$killID/$hash/";
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_USERAGENT, "API Fetcher for http://$baseAddr");
				$body = curl_exec($ch);
				$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

				// If the server is rejecting us, bail
				if ($httpCode > 500)
					return;

				// If we get an error code, it's probably because the server either doesn't work, or because the kill is wrong, so wrong..
				if ($httpCode != 200)
				{
					Log::log("Crestapi Error: $killID / $httpCode");
					$db->execute("update zz_crest_killmail set processed = :i where killID = :killID", array(":i" => (-1 * $httpCode), ":killID" => $killID));
					usleep(250000);
					continue;
				}

				// Decode the killmail data
				$perrymail = json_decode($body, false);

				// Generate the killmail
				$killmail = array();
				$killmail["killID"] = (int) $killID;
				$killmail["solarSystemID"] = (int) $perrymail->solarSystem->id;
				$killmail["killTime"] = str_replace(".", "-", $perrymail->killTime);
				$killmail["moonID"] = (int) @$perrymail->moon->id;

				$victim = array();
				$killmail["victim"] = self::getVictim($perrymail->victim);
				$killmail["attackers"] = self::getAttackers($perrymail->attackers);
				$killmail["items"] = self::getItems($perrymail->victim->items);

				// Encode the killmail data into json
				$json = json_encode($killmail);
				$killmailHash = Util::getKillHash(null, json_decode($json));

				// Insert the killmail into zz_killmails
				$db->execute("INSERT IGNORE INTO zz_killmails (killID, hash, source, kill_json) VALUES (:killID, :hash, :source, :json)", array(":killID" => $killID, ":hash" => $killmailHash, ":source" => "crest:$killID", ":json" => $json));
				$db->execute("UPDATE zz_crest_killmail SET processed = 1 WHERE killID = :killID", array(":killID" => $killID));
			}
			catch (Exception $ex)
			{
				Log::log("CREST exception: $killID - " . $ex->getMessage());
				$db->execute("UPDATE zz_crest_killmail SET processed = -1 WHERE killID = :killID", array(":killID" => $killID));
			}
		}
	}

	/**
	 * @param object $perrymail
	 * @return array
	 */
	private static function getVictim($pvictim)
	{
		$victim = array();
		$victim["shipTypeID"] = (int) $pvictim->shipType->id;
		$victim["characterID"] = (int) @$pvictim->character->id;
		$victim["characterName"] = (string) @$pvictim->character->name;
		$victim["corporationID"] = (int) $pvictim->corporation->id;
		$victim["corporationName"] = (string) @$pvictim->corporation->name;
		$victim["allianceID"] = (int) @$pvictim->alliance->id;
		$victim["allianceName"] = (string) @$pvictim->alliance->name;
		$victim["factionID"] = (int) @$pvictim->faction->id;
		$victim["factionName"] = (string) @$pvictim->faction->name;
		$victim["damageTaken"] = (int) @$pvictim->damageTaken;
		return $victim;
	}

	/**
	 * @param array $attackers
	 * @return array
	 */
	private static function getAttackers($attackers)
	{
		$aggressors = array();
		foreach($attackers as $attacker) {
			$aggressor = array();
			$aggressor["characterID"] = (int) @$attacker->character->id;
			$aggressor["characterName"] = (string) @$attacker->character->name;
			$aggressor["corporationID"] = (int) @$attacker->corporation->id;
			$aggressor["corporationName"] = (string) @$attacker->corporation->name;
			$aggressor["allianceID"] = (int) @$attacker->alliance->id;
			$aggressor["allianceName"] = (string) @$attacker->alliance->name;
			$aggressor["factionID"] = (int) @$attacker->faction->id;
			$aggressor["factionName"] = (string) @$attacker->faction->name;
			$aggressor["securityStatus"] = $attacker->securityStatus;
			$aggressor["damageDone"] = (int) @$attacker->damageDone;
			$aggressor["finalBlow"] = (int) @$attacker->finalBlow;
			$aggressor["weaponTypeID"] = (int) @$attacker->weaponType->id;
			$aggressor["shipTypeID"] = (int) @$attacker->shipType->id;
			$aggressors[] = $aggressor;
		}
		return $aggressors;
	}

	/**
	 * @param array $items
	 * @return array
	 */
	private static function getItems($items)
	{
		$retArray = array();
		foreach($items as $item)
		{
			$i = array();
			$i["typeID"] = (int) @$item->itemType->id;
			$i["flag"] = (int) @$item->flag;
			$i["qtyDropped"] = (int) @$item->quantityDropped;
			$i["qtyDestroyed"] = (int) @$item->quantityDestroyed;
			$i["singleton"] = (int) @$item->singleton;
			if (isset($item->items))
				$i["items"] = self::getItems($item->items);
			$retArray[] = $i;
		}
		return $retArray;
	}
}
