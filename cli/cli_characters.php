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

class cli_characters implements cliCommand
{
	public function getDescription()
	{
		return "Populates the character list, and also updates their member history. (Including the list of corporations)";
	}

	public function getAvailMethods()
	{
		return ""; // Space seperated list
	}

	public function getCronInfo()
	{
		return array(0 => "");
	}

	public function execute($parameters, $db)
	{
		global $enableCharacterFetcher;

		if(!$enableCharacterFetcher)
			return;

		if (Util::is904Error())
			return;

		$oldMax = Storage::retrieve("oldMax", 0);
		$charIDs = array();
		$maxID = $db->queryField("SELECT MAX(characterID) AS max FROM zz_characters", "max", array(), 0);
		if($oldMax >= $maxID) // If oldMax is the same as, or larger than maxID, then start over!
		{
			Storage::store("oldMax", 0);
			$oldMax = 0;
		}
		$characterIDs = $db->query("SELECT characterID FROM zz_characters WHERE characterID >= :oldMax AND characterID > 90000000 ORDER BY characterID LIMIT 1000", array(":oldMax" => $oldMax), 0);
		foreach($characterIDs as $characterID)
			$charIDs[] = $characterID["characterID"];

		$min = 0;
		$max = 0;
		$count = 0;
		$added = 0;
		foreach($charIDs as $key => $charID)
		{
			if (Util::is904Error())
				return;
			$min = $charID;
			if($key >= 999)
				$max = $charIDs[$key];
			else
				$max = $charIDs[$key+1];
			$difference = $max - $min;

			if($difference > 2)
			{
				// Lets fetch the differences
				$curr = $min;
				while($curr <= $max)
				{
					if (Util::is904Error())
						return;

					$pheal = Util::getPheal();
					$pheal->scope = "eve";
					try
					{
						$exists = $db->queryField("SELECT characterID FROM zz_characters WHERE characterID = :characterID", "characterID", array(":characterID" => $curr), 0);
						if(!$exists)
						{
							CLI::out("|g|Trying characterID:|n| $curr");
							$charInfo = $pheal->CharacterInfo(array("characterid" => $curr));

							if(!$charInfo->characterID)
							{
								CLI::out("|r|Error:|n| characterID isn't set, sleeping for 5 seconds to not overwhelm the API");
								usleep(5000000); // Sleep for 5 seconds between each error
							}
							else
							{
								CLI::out("|g|Adding Character:|n| {$charInfo->characterName}");
								$characterID = $charInfo->characterID;
								$characterName = $charInfo->characterName;
								$corporationID = $charInfo->corporationID;
								$corporationName = $charInfo->corporation;
								Info::addCorp($corporationID, $corporationName);
								Info::addChar($characterID, $characterName);
								$date = date("Y-m-d H:i:s", time() - 259200);
								$db->execute("UPDATE zz_characters SET lastUpdated = :date WHERE characterID = :characterID", array(":date" => $date, ":characterID" => $characterID));
								$added++;
								usleep(333333); // Sleep for 333ms (3 a second)
							}
						}
					}
					catch (Exception $ex)
					{
						CLI::out("|r|Error:|n| " . $ex->getMessage() . " / Sleeping for 5 seconds to not overwhelm the API");
						usleep(5000000); // Sleep for 5 seconds between each error
					}
					$curr++;
					Storage::store("oldMax", $curr);
				}
			}
		}
		Log::log("Added {$added} new Characters to the database");
	}
}
