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

class cli_characters_information implements cliCommand
{
	public function getDescription()
	{
		return "Collects the data from the API and stores it in the database.";
	}

	public function getAvailMethods()
	{
		return ""; // Space seperated list
	}

	public function execute($parameters, $db)
	{
		//https://api.eveonline.com/eve/CharacterInfo.xml.aspx?characterID=268946627

		$characters = Db::query("SELECT * FROM zz_characters WHERE lastUpdated < date_sub(now(), interval 2 day) LIMIT 1000");

		foreach($characters as $data)
		{
			$characterID = $data["characterID"];
			$pheal = Util::getPheal();
			$pheal->scope = "eve";

			$charInfo = $pheal->CharacterInfo(array("characterid" => $characterID));
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
			usleep(333333); // Sleep for 333ms (3 a second)
		}
	}
}
