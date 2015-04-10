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

/**
 * Parser for raw killmails from ingame EVE.
 */

class Parser
{

	/**
	 * Parses a raw killmail, and spits out a nice pretty array.
	 * 
	 * @static
	 * @param $rawMail - the raw killmail
	 * @param $userID - the ID of the user who posted the raw mail
	 * @return array
	 */
	public static function parseRaw($rawMail, $userID)
	{
		$errors = array();
		$mail = trim(str_replace("\r", "", $rawMail));

		// Translate the killmail from whatever language it is to english.. ~CCP~
		$mail = self::Translate($mail);

		// If the site is in maintenance mode, the parser shouldn't even run..
		if (Util::isMaintenanceMode()) {
			$errors[] = "The site is currently in maintenance mode.  Manual posts are not accepted at the moment.";
		}

		// Fix a faction name that was previously set as an alliance
		if (Bin::get("FixFaction", false)) {
			$mail = str_ireplace("Alliance: Federation Navy", "Faction: Gallente Federation", $mail);
		}

		// Fix an error that has happened a few times, where Involved parties: and Name: are on the same line
		if(stristr($mail, "Involved parties:Name:"))
			$mail = str_replace("Involved parties:Name:", "Involved parties:\n\nName:", $mail);

		// Fix unicode and random CCP localization problems.
		$mail = utf8_decode($mail);
		$mail = preg_replace('/: (\d+),00/', ': $1', $mail);
		$mail = preg_replace('/(\d),(\d)/', '$1.$2', $mail);
		$mail = str_replace('?', '-', $mail);

		// Remove (Other) - because CCP fails at life.. Refer to https://github.com/EVE-KILL/zKillboard/issues/174
		$mail = str_replace(" (Other)", "", $mail);

		// Find the timestamp
		$timestamp = substr($mail, 0, 16);
		$timestamp = str_replace(".", "-", $timestamp);
		$timestamp .= ":00";

		// Make sure there is a timestamp
		if (!$timestamp)
			$errors[] = "No timestamp, probably not even a killmail";

		// Validate timestamp
		$time = DateTime::createFromFormat("Y-m-d H:i:s", $timestamp);
		if($time != false)
			$timestamp = $time->format("Y-m-d H:i:s");
		else
			$errors[] = "Error with the timestamp..";

		// Make sure there is an involved party
		if (stripos($mail, "Involved parties:") === false)
			$errors[] = "No involved parties..";

		// If there was an error now, we might aswell return it and give up
		if (sizeof($errors)) {
			return array("error" => $errors);
		}

		// Initialize the main array that we will be inserting stuff to.
		$killMail = array(
			"killID" => 0,
			"solarSystemID" => 0,
			"killTime" => $timestamp,
			"moonID" => 0,
			"victim" => array(
				"shipTypeID" => 0,
				"damageTaken" => 0,
				"factionName" => "",
				"factionID" => 0,
				"allianceName" => "",
				"allianceID" => 0,
				"corporationName" => "",
				"corporationID" => 0,
				"characterName" => "",
				"characterID" => 0,
			),
			"attackers" => array(),
			"items" => array()
		);

		$mail = trim(substr($mail, 16));
		if (Util::startsWith(":00", $mail)) $mail = substr($mail, 3);

		$exploded = explode("\n", $mail);

		$inVictim = true;
		$inAttackers = false;
		$inDestroyedItems = false;
		$inDroppedItems = false;
		$currentAttacker = null;
		$killValue = 0;
		$currentLowSlot = 11;
		$currentMidSlot = 19;
		$currentHighSlot = 27;
		$currentRigSlot = 92;
		$currentSubSlot = 125;

		foreach($exploded as $line) {
			$split = explode(":", $line);
			$key = $split[0] . ":";
			$value = isset($split[1]) ? trim($split[1]) : $line;
			switch($key) {
				case "Victim:":
					$killMail["victim"]["characterName"] = (string) $value;
					$killMail["victim"]["characterID"] = (int) Info::getCharID($value);
				break;
				case "Corp:":
					if ($inVictim) {
						$killMail["victim"]["corporationName"] = (string) $value;
						$killMail["victim"]["corporationID"] = (int) Info::getCorpID($value);
					} else if ($inAttackers) {
						$currentAttacker["corporationName"] = (string) $value;
						$currentAttacker["corporationID"] = (int) Info::getCorpID($value);
					}
				break;
				case "Alliance:":
					if (strcasecmp($value, "Unknown") == 0 || strcasecmp($value, "None") == 0) $value = "None";
					if ($inVictim) {
						$killMail["victim"]["allianceName"] = (string) $value;
						if ($value == "None") $killMail["victim"]["allianceID"] = 0;
						else $killMail["victim"]["allianceID"] = (int) Info::getAlliID($value);
					} else if ($inAttackers) {
						$currentAttacker["allianceName"] = (string) $value;
						if ($value == "None") $currentAttacker["allianceID"] = 0;
						else $currentAttacker["allianceID"] = (int) Info::getAlliID($value);
					}
				break;
				case "Faction:":
					if (strcasecmp($value, "Unknown") == 0 || strcasecmp($value, "None") == 0) $value = "None";
					if ($inVictim) {
						$killMail["victim"]["factionName"] = (string) $value;
						if ($value == "None") $killMail["victim"]["factionID"] = 0;
						else $killMail["victim"]["factionID"] = (int) Info::getFactionID($value);
					} else if ($inAttackers) {
						$currentAttacker["factionName"] = (string) $value;
						if ($value == "None") $currentAttacker["factionID"] = 0;
						else $currentAttacker["factionID"] = (int) Info::getFactionID($value);
					}
				break;
				case "Destroyed:":
					$killMail["victim"]["shipTypeID"] = (int) Info::getItemID($value);
					$killValue =+ Price::getItemPrice($killMail["victim"]["shipTypeID"]);
				break;
				case "Damage Taken:":
					$killMail["victim"]["damageTaken"] = (int) $value;
				break;
				case "System:":
					$killMail["solarSystemID"] = (int) Info::getSystemID($value);
				break;
				case "Moon:":
					if (strcasecmp($value, "Unknown") == 0 || strcasecmp($value, "None") == 0) $value = "None";
					$split = explode("-", $value);
					$value = "";
					$size = sizeof($split) - 1;
					for ($a = $size; $a >= 0; $a--) {
						$value = $split[$a] . ($a == $size ? "" : "-") . $value;
						$moonID = (int) Db::queryField("select itemID from ccp_mapDenormalize where itemName = :name", "itemID", array(":name" => trim($value)));
						if ($moonID) break;
					}
					$value = trim($value);
					if ($value == "None") $killMail["moonID"] = 0;
					else {
						$moonID = (int) Db::queryField("select itemID from ccp_mapDenormalize where itemName = :name", "itemID", array(":name" => $value));
						if ($moonID > 0) $killMail["moonID"] = $moonID;
						else $errors[] = "Invalid Moon: $value";
					}
				break;
				case "Name:":
					if($inAttackers) $currentAttacker = self::createAttacker();
					if (stripos($value, "(laid the final blow)") !== false) {
						$currentAttacker["finalBlow"] = 1;
						$value = trim(str_ireplace("(laid the final blow)", "", $value));
					}
					$id = 0;
					if ($value != "" && strpos($value, "/") === false) $id = (int) Info::getCharID($value);
					if ($id != 0) {
						$currentAttacker["characterName"] = (string) $value;
						$currentAttacker["characterID"] = $id;
					}
					if ($id == 0) {
						// Might be an NPC?
						$npcSplit = explode("/", $value);
						$npc = trim($npcSplit[0]);
						$id = (int) Db::queryField("select typeID from ccp_invTypes where typeName = :name", "typeID", 
								array(":name" => $npc));
						$currentAttacker["weaponTypeID"] = $id;
						if (sizeof($npcSplit) > 1 && trim($npcSplit[1]) != "Unknown") {
							// Look up the corp
							$corpID = Info::getCorpID(trim($npcSplit[1]));
							if ($corpID > 0) {
								$currentAttacker["corporationID"] = $corpID;
								$currentAttacker["corporationName"] = trim($npcSplit[1]);
							} else {
								$errors[] = "Unable to determine item information: $value";
							}
						}
					}
				break;
				case "Security:":
					if ($inAttackers) $currentAttacker["securityStatus"] = (float) $value;
				break;
				case "Ship:":
					if ($inAttackers) $currentAttacker["shipTypeID"] = (int) Info::getItemID($value);
				break;
				case "Weapon:":
					if ($inAttackers) $currentAttacker["weaponTypeID"] = (int) Info::getItemID($value);
				break;
				case "Damage Done:":
					if ($inAttackers) $currentAttacker["damageDone"] = (int) $value;
					if ($currentAttacker != null) $killMail["attackers"][] = $currentAttacker;
				break;

                case "Involved parties:":
                    $inVictim = false;
                    $inAttackers = true;
        			$inDestroyedItems = false;
        			$inDroppedItems = false;
                break;
				case "Destroyed items:":
                    $inVictim = false;
                    $inAttackers = false;
        			$inDestroyedItems = true;
        			$inDroppedItems = false;
				break;
				case "Dropped items:":
                    $inVictim = false;
                    $inAttackers = false;
        			$inDestroyedItems = false;
        			$inDroppedItems = true;
				break;
				case "":
				case ":":
					continue;
				default:
					if ($inVictim || $inAttackers) throw new Exception("Unhandled prefix: $key");
					// We have an item!
					$value = $line;
					$flag = null;
					$qty = 1;

					// ADD ALL THE FLAGS!!!!!!!!!!!
					$dbFlags = Db::query("SELECT flagText, flagID FROM ccp_invFlags", array(), 3600);
					$flags = array();
					foreach($dbFlags as $f)
						$flags["(".$f["flagText"].")"] = (int) $f["flagID"];

					foreach($flags as $flagType => $flagID) {
						if (strpos($value, $flagType) !== false) {
							$flag = $flagID;
							$value = trim(str_replace($flagType, "", $value));
						}
					}

					$isBlueprintCopy = false;
					$bpc = "(Copy)";
					if (Util::endsWith($value, $bpc)) {
						$isBlueprintCopy = true;
						$value = trim(substr($value, 0, strlen($value) - strlen($bpc)));
					}
					$container = "(In Container)";
					$inContainer = Util::endsWith($value, $container);
					if ($inContainer) {
						$value = trim(substr($value, 0, strlen($value) - strlen($container)));
					}
					$qtyPos = stripos($value, ", Qty: ");
					if ($qtyPos !== false) {
						$qtyEx = explode(", Qty: ", $value);
						$qty = (int) $qtyEx[1];
						$value = $qtyEx[0] . str_replace("$qty", "", $qtyEx[1]);
					}
					$typeID = Info::getItemID($value);
					if ($typeID == 0) { 
						$errors[] = "Unknown Item: $value"; 
						continue;
					}
					if ($flag === null) {
						// Ok, we need to figure out which slot this is in...
						$flagSlot = Db::query("select e.effectID effectID from ccp_invTypes i left join ccp_dgmTypeEffects d on (d.typeID = i.typeID) left join ccp_dgmEffects e on (d.effectID = e.effectID) where i.typeID = :typeID", array(":typeID" => $typeID));
						foreach($flagSlot as $f)
						{
							$flagSlot = $f["effectID"];
							switch($flagSlot) {
								case 11:
									$flag = $currentLowSlot;
									$currentLowSlot++;
								break;
								case 12:
									$flag = $currentHighSlot;
									$currentHighSlot++;
								break;
								case 13:
									$flag = $currentMidSlot;
									$currentMidSlot++;
								break;
								case 2663:
									$flag = $currentRigSlot;
									$currentRigSlot++;
								break;
								case 3772:
									$flag = $currentSubSlot;
									$currentSubSlot++;
								break;
							}
						}
					}

					if ($flag == null || $flag == 0) $flag = 5;
					$item = self::createItem();
					$item["typeID"] = $typeID;
					$item["flag"] = $flag;
					$item["qtyDropped"] = $inDroppedItems ? $qty : 0;
					$item["qtyDestroyed"] = $inDestroyedItems ? $qty : 0;
					$item["singleton"] = $isBlueprintCopy ? 2 : 0;
					if ($inContainer) {
						$lastItem = $killMail["items"][sizeof($killMail["items"]) - 1];
						$lastItem["items"][] = $item;
						$killMail["items"][sizeof($killMail["items"]) - 1] = $lastItem;
					}
					else $killMail["items"][] = $item;
					$killValue += ($qty * Price::getItemPrice($typeID));
			}
		}

		// Check that stuff is actually sane, and not some made up shit..
		// Victim must have a valid characterID and corporationID
		if ($killMail["victim"]["shipTypeID"] == 0) $errors[] = "Invalid destroyed ship.";
		else 
		{
			$victimGroupID = Info::getGroupID($killMail["victim"]["shipTypeID"]);
			$noCharGroups = array(
				311, // Refining Arrays
				363, // [Capital] Ship Maintenance Array
				365, // POS's
				397, // Assembly Arrays
				404, // Silos
				413, // Mobile POS Labs
				416, // Moon Harvester
				413, // Mobile POS Labs
				417, // Missile and Torpedo Batteries
				426, // Artillery Batteries
				430, // Laser Batteries
				438, // Reactor Arrays
				439, // ECM Batteries
				440, // Dampening Arrays
				441, // Web Batteries
				443, // Warp scrambling arrays
				444, // POS damage arrays (ballistic, explosion, heat, photon)
				449, // Blaster & Railgun Batteries
				471, // Corporation Hangar Array, ShipYard
				473, // Tracking Array
				707, // Jump Bridges
				709, // Scanning Arrays
				837, // Neut Batteries
				838, // Cynosural Generator Array
				839, // Cynosural System Jammer
				1003, // Territorial Claim Unit
				1003, // QA Territorial Claim Unit
				1005, // Sovereignty Blockade Unit
				1005, // QA Sovereignty Blockade Unit
				1012, // QA Infrastructure Hub
				1012, // Infrastructure Hub
				1025, // Customs Office
				1025, // Orbital Command Center
				1025, // Interbus Customs Office
				1106, // Customs Office Gantry
				1012, // IHUBS
			);

			// Allow POS's, POS modules, ihub's and poco's, TCU, SBU
			if (in_array($victimGroupID, $noCharGroups))  {  } // noop() - do nothing
			else if ($killMail["victim"]["characterName"] == "" || $killMail["victim"]["characterID"] == 0)
				$errors[] = "Invalid victim name: " . $killMail["victim"]["characterName"];
		}

		if ($killMail["victim"]["corporationName"] == "" || $killMail["victim"]["corporationID"] == 0)
			$errors[] = "Invalid victim corporation: " . $killMail["victim"]["corporationName"];

		if ($killMail["victim"]["allianceName"] != "" && strcasecmp($killMail["victim"]["allianceName"], "None") != 0 && $killMail["victim"]["allianceID"] == 0)
			$errors[] = "Unknown victim alliance: " . $killMail["victim"]["allianceName"];

		if ($killMail["victim"]["factionName"] != "" && strcasecmp($killMail["victim"]["factionName"], "None") != 0 && $killMail["victim"]["factionID"] == 0)
			$errors[] = "Invalid victim faction: " . $killMail["victim"]["factionName"];

		if (Bin::get("BreakOnInvalidDamage", true) && $killMail["victim"]["damageTaken"] == 0)
			$errors[] = "Invalid damage amount.";

		if ($killMail["solarSystemID"] == 0)
			$errors[] = "Invalid solar system.";

		// Verified the victim, lets forget the rest to see if there's a dupe!
        $stdMail = json_decode(json_encode($killMail), false);
        $hash = Util::getKillHash(null, $stdMail);
        $dupeKillID = Db::queryField("select killID from zz_killmails where hash = :hash limit 1", "killID", array(":hash" => $hash), 0);
        if ($dupeKillID > 0) {
            return array("dupe" => $dupeKillID);
        }

		// There can be only one final blow
		$finalBlowCount = 0;

		// All attackers must have a valid characterID and corporationID (unless NPC)
		// check for and deny NPC only mails
		// check for and deny friendly corp mails
		$npcOnly = true;
		$victimCorpOnly = true;
		$victim = $killMail["victim"];
		foreach ($killMail["attackers"] as $attacker) {
			$npcOnly &= $attacker["characterID"] == 0 && $attacker["corporationID"] < 9999999;
			$victimCorpOnly &= $attacker["corporationID"] == $victim["corporationID"] && $victim["corporationID"] > 9999999;
			if ($attacker["characterName"] != "" && $attacker["characterID"] == 0) $errors[] = "Invalid attacker name: " . $attacker["characterName"];
			if ($attacker["corporationName"] != "" && $attacker["corporationID"] == 0) $errors[] = "Invalid attacker corporation: " . $attacker["corporationName"];
			if ($attacker["allianceName"] != "" && strcasecmp($attacker["allianceName"], "None") != 0 && $attacker["allianceID"] == 0) $errors[] = "Invalid attacker alliance: " . $attacker["allianceName"];
			if ($attacker["factionName"] != "" && strcasecmp($attacker["factionName"], "None") != 0 && $attacker["factionID"] == 0) $errors[] = "Invalid attacker faction: " . $attacker["factionName"];
		}
		if ($npcOnly) $errors[] = "This is an NPC only mail.  Mails must contain other characters.";
		//if ($victimCorpOnly) $errors[] = "Corp friendly killmail...  sorry, can't post those!";
		if ($finalBlowCount > 1) $errors[] = "Too many attackers have the final blow.";

		// If the kill is worth more than 5b isk, time to throw errors!
		if ($killValue > 5000000000 && Bin::get("Disallow5bKills", true)) {
			$errors[] = "Kills worth more than 5 billion ISK require API verification.";
		}

		// Determine if ship has bays, if it does, complain
		$victimGroupID = Info::getGroupID($killMail["victim"]["shipTypeID"]);
		// Ships with specialized bays
		$bayShips = array(
			28, // Industrials
			30, // Titans
			659, // Supercarriers
			485, // Dreads
			547, // Carriers
			902, // Jump Freighters
			543, // Exhumers
			463, // Mining Barges
			898, // Black Ops
			941, // Industrial Command Ships
			883, // Captial Industiral Ships
		);
		if (in_array($victimGroupID, $bayShips)) $errors[] = "The victim ship has bays which are not displayed properly on manual killmails, please use API to post the kill";

		// We're done with sanity checks, if we have any errors return them
		if (sizeof($errors)) {
			return array("error" => $errors);
		}

		// Insert ignore allows us to "pretend" to insert dupes
		Db::execute("insert ignore into zz_manual_mails (hash) values (:hash)", array(":hash" => $hash));
		// Look up the manualKillID from the hash (good for those dupe inserts)
		$mKillID = Db::queryField("select mKillID from zz_manual_mails where hash = :hash order by mKillID desc limit 1", "mKillID", array(":hash" => $hash), 0);

		// yes, manual mails have a negative numbers, cuz they're BAD
		$mKillID = -1 * $mKillID;

		$killMail["killID"] = $mKillID;

		Db::execute("insert ignore into zz_killmails (killID, hash, source, kill_json) values (:killID, :hash, :source, :json)",
				array(":killID" => $mKillID, ":hash" => $hash, ":source" => "userID:$userID", ":json" => json_encode($killMail)));

		if ($userID != "EveKill") Log::log("Manual mail post from: $userID");
		while (true) {
			if (Bin::get("WaitForProcessing", true) == true) {
				sleep(1);
				$processed = Db::queryField("select processed from zz_killmails where killID = :killID", "processed", array(":killID" => $mKillID), 0);
			} else $processed = 1;
			if ($processed > 0) {
				return array("success" => $mKillID);
			}
		}

	}

	/**
	 * Inititates the item array
	 * @return array
	 */
	private static function createItem() {
		return array(
				"typeID" => 0,
				"flag" => 0,
				"qtyDropped" => 0,
				"qtyDestroyed" => 0,
				"singleton" => 0,
				);
	}

	/**
	 * Initiates the attacker array
	 * @return string
	 */
	private static function createAttacker() {
		return array(
				"characterID" => 0,	
				"characterName" => "",
				"corporationID" => 0,
				"corporationName" => "",
				"allianceID" => 0,
				"allianceName" => "",
				"factionID" => 0,
				"factionName" => "",
				"securityStatus" => 0,
				"damageDone" => 0,
				"finalBlow" => 0,
				"weaponTypeID" => 0,
				"shipTypeID" => 0,
				);
	}

	/**
	 * Translates a killmail
	 * @param string $mail the killmail that needs translation
	 * @return text
	 */
	private static function Translate($mail)
	{
		// German!
		if (strpos($mail, "Beteiligte Parteien:"))
		{
			$mail = str_replace(array(chr(195) . chr(182), chr(195) . chr(164)), array(chr(246), chr(228)), $mail);
			$translation = array(
					'Opfer:' => 'Victim:',
					'Ziel:' => 'Victim:',
					'Allianz: KEINE' => 'Alliance: None',
					'Allianz: NICHTS' => 'Alliance: None',
					'Allianz:' => 'Alliance:',
					'Fraktion: KEINE' => 'Faction: None',
					'Fraktion: NICHTS' => 'Faction: None',
					'Fraktion:' => 'Faction:',
					'Zerst' . chr(246) . 'rte Gegenst' . chr(228) . 'nde' => 'Destroyed items',
					'Zerst' . chr(246) . 'rt:' => 'Destroyed:',
					'Sicherheit:' => 'Security:',
					'Beteiligte Parteien:' => 'Involved parties:',
					'Anz:' => 'Qty:',
					'Anz.:' => 'Qty:',
					'Corporation:' => 'Corp:',
					'(Fracht)' => '(Cargo)',
					'Schiff:' => 'Ship:',
					'Waffe:' => 'Weapon:',
					'(Im Container)' => '(In Container)',
					'Verursachter Schaden:' => 'Damage Done:',
					'Erlittener Schaden:' => 'Damage Taken:',
					'(gab den letzten Schuss ab)' => '(laid the final blow)',
					'Hinterlassene Gegenst' . chr(228) . 'nde:' => 'Dropped items:',
					': Unbekannt' => ': None',
					'(Dronenhangar)' => '(Drone Bay)',
					'(Drohnenhangar)' => '(Drone Bay)',
					'(Drohnenbucht)' => '(Drone Bay)',
					'Mond:' => 'Moon:',
					'Kapsel' => 'Capsule',
					'Menge:' => 'Qty:'
						);

			foreach ($translation as $w => $t) {
				$mail = str_ireplace($w, $t, $mail);
			}
		}

		// Russian!
		if (strpos($mail, "Корпорация") || strpos($mail, "Неизвестно"))
		{
			$translation = array(
					'Жертва:' => 'Victim:',
					'Альянс: НЕТ' => 'Alliance: None',
					'Альянс: нет' => 'Alliance: None',
					'Альянс: Нет' => 'Alliance: None',
					': НЕТ' => ": None",
					': нет' => ": None",
					': Нет' => ": None",
					'Альянс:' => 'Alliance:',
					'Имя:' => 'Name:',
					'Фракция: Неизвестно' => 'Faction: None',
					'Фракция: НЕТ' => 'Faction: None',
					'Фракция: нет' => 'Faction: None',
					'Фракция: Нет' => 'Faction: None',
					'Фракция:' => 'Faction:',
					'Уничтоженные предметы:' => 'Destroyed items:',
					'Уничтожено:' => 'Destroyed:',
					'Уровень безопасности:' => 'Security:',
					'Система:' => 'System:',
					'Участники:' => 'Involved parties:',
					'кол-во:' => 'Qty:',
					'Корпорация:' => 'Corp:',
					'(Груз)' => '(Cargo)',
					'(Имплантат)' => '(Implant)',
					'Корабль:' => 'Ship:',
					'Оружие:' => 'Weapon:',
					'(В контейнере)' => '(In Container)',
					'Нанесенный ущерб:' => 'Damage Done:',
					'Полученный ущерб:' => 'Damage Taken:',
					'(нанес последний удар)' => '(laid the final blow)',
					'Сброшенные предметы:' => 'Dropped items:',
					'кол-во:' => 'Qty:',
					'Неизвестно' => 'None',
					'Отсек дронов' => 'Drone Bay',
					'Луна:' => 'Moon:',

				);
			foreach ($translation as $w => $t) {
				$mail = str_ireplace($w, $t, $mail);
			}
		}

		// Chinese
		if (strpos($mail, "军团") || strpos($mail, "受害者")) {
			// Just incase that weird : is standard on all chinese mails
			$mail = str_replace("：", ":", $mail);

			$translation = array(
					'受害者:' => 'Victim:',
					'联盟:' => 'Alliance:',
					'名称:' => 'Name:',
					'势力:' => 'Faction:',
					'被摧毁物:' => 'Destroyed:',
					'安全等级:' => 'Security:',
					'星系:' => 'System:',
					'参与者:' => 'Involved parties:',
					'军团:' => 'Corp:',
					'舰船:' => 'Ship:',
					'武器:' => 'Weapon:',
					'造成损伤:' => 'Damage Done:',
					'所受损伤:' => 'Damage Taken:',
					'(给予最后一击)' => '(laid the final blow)',
					'Сброшенные предметы:' => 'Dropped items:',
					'кол-во:' => 'Qty:',
					'Неизвестно' => 'None',
					'Отсек дронов' => 'Drone Bay',
					'Луна:' => 'Moon:',
					'(Груз)' => '(Cargo)',
					'(植入体)' => '(Implant)',
					'кол-во:' => 'Qty:',
					'(В контейнере)' => '(In Container)',
					'被毁物品:' => 'Destroyed items:',
				);
			foreach ($translation as $w => $t) {
				$mail = str_ireplace($w, $t, $mail);
			}
		}
		return $mail;
	}
}