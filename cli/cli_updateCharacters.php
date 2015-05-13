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

class cli_updateCharacters implements cliCommand
{
	public function getDescription()
	{
		return "Updates character Name and IDs. |g|Usage: updateCharacters <type>";
	}

	public function getAvailMethods()
	{
		return "";
	}

	public function getCronInfo()
	{
		return array(0 => "");
	}

	public function execute($parameters, $db)
	{
		self::updateCharacters($db);
	}

	private static function updateCharacters($db)
	{
		$timer = new Timer();
		while ($timer->stop() < 59000)
		{
			$result = $db->query("select characterID, name, corporationID, allianceID from zz_characters where lastUpdated < date_sub(now(), interval 7 day) order by lastUpdated limit 1000", array(), 0);
			foreach ($result as $row)
			{
				if (Util::is904Error())
					return;

				$id = $row["characterID"];

				$db->execute("update zz_characters set lastUpdated = now() where characterID = :id", array(":id" => $id));

				if ($id >= 2100000000 && $id < 2199999999)
					continue; // Dust Characters
				if ($id >= 30000000 && $id <= 31004590)
					continue; // NPC's
				if ($id >= 40000000 && $id <= 41004590)
					continue; // NPC's

				$isTypeID = (0 < $db->queryField("select count(*) count from ccp_invTypes where typeID = :id", "count", array(":id" => $id)));

				if ($isTypeID)
					continue;

				$pheal = Util::getPheal();
				$pheal->scope = "eve";
				$charInfo = $pheal->CharacterInfo(array("characterid" => $id));
				$name = $charInfo->characterName;
				$corpID = $charInfo->corporationID;
				$alliID = $charInfo->allianceID;
				$data = array();
				$data["characterID"] = $charInfo->characterID;
				$data["characterName"] = $charInfo->characterName;
				$data["corporationID"] = $charInfo->corporationID;
				$data["corporationName"] = $charInfo->corporation;
				$data["corporationDate"] = $charInfo->corporationDate;
				$data["allianceID"] = $charInfo->allianceID;
				$data["allianceName"] = $charInfo->alliance;
				$data["allianceDate"] = $charInfo->allianceDate;
				$data["bloodline"] = $charInfo->bloodline;
				$data["race"] = $charInfo->race;
				$data["securityStatus"] = $charInfo->securityStatus;

				foreach($charInfo->employmentHistory->toArray() as $empHistory)
					$data["employmentHistory"][] = array("recordID" => $empHistory["recordID"], "corporationID" => $empHistory["corporationID"], "corporationName" => $empHistory["corporationName"], "startDate" => $empHistory["startDate"]);

				$json = json_encode($data);

				Db::execute("UPDATE zz_characters SET history = :data WHERE characterID = :characterID", array(":data" => $json, ":characterID" => $data["characterID"]));
				Db::execute("UPDATE zz_characters SET lastUpdated = now() WHERE characterID = :characterID", array(":characterID" => $data["characterID"]));
				StatsD::increment("characters_history");

				if ($name != $row["name"] || ((int) $corpID) != $row["corporationID"] || ((int) $alliID) != $row["allianceID"])
					$db->execute("update zz_characters set name = :name, corporationID = :corpID, allianceID = :alliID where characterID = :id", array(":id" => $id, ":name" => $name, ":corpID" => $corpID, ":alliID" => $alliID));

				StatsD::increment("characters_Updated");
			}
		}
	}
}
