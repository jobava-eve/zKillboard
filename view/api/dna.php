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

class api_dna implements apiEndpoint
{
	public function getDescription()
	{
		return array("type" => "description", "message" =>
				"Outputs fitting DNA for all ships killed."
			);
	}

	public function getAcceptedParameters()
	{
		return array("type" => "parameters", "parameters" =>
			array(
				"page" => "Pagination."
			)
		);
	}
	public function execute($parameters)
	{
		//set the headers to cache the request properly
		$dna = array();

		$page = 1;
		if(isset($parameters["page"]))
			$page = $parameters["page"];

		$kills = Feed::getKills(array("limit" => 200, "cacheTime" => 3600, "page" => $page));
		foreach($kills as $kill)
		{
			$kill = json_decode($kill, true);
			$killdata = Kills::getKillDetails($kill["killID"]);
			$dna[] = array(
				"killtime" => $killdata["info"]["dttm"], 
				"SolarSystemName" => $killdata["info"]["solarSystemName"],
				"solarSystemID" => $killdata["info"]["solarSystemID"],
				"regionID" => $killdata["info"]["regionID"],
				"regionName" => $killdata["info"]["regionName"],
				"victimCharacterID" => (isset($killdata["victim"]["characterID"]) ? isset($killdata["victim"]["characterID"]) : null),
				"victimCharacterName" => (isset($killdata["victim"]["characterName"]) ? isset($killdata["victim"]["characterName"]) : null),
				"victimCorporationID" => (isset($killdata["victim"]["corporationID"]) ? isset($killdata["victim"]["corporationID"]) : null),
				"victimCorporationName" => (isset($killdata["victim"]["corporationName"]) ? isset($killdata["victim"]["corporationName"]) : null),
				"victimAllianceID" => (isset($killdata["victim"]["allianceID"]) ? isset($killdata["victim"]["allianceID"]) : null),
				"victimAllianceName" => (isset($killdata["victim"]["allianceName"]) ? isset($killdata["victim"]["allianceName"]) : null),
				"victimFactionID" => (isset($killdata["victim"]["factionID"]) ? isset($killdata["victim"]["factionID"]) : null),
				"victimFactionName" => (isset($killdata["victim"]["factionName"]) ? isset($killdata["victim"]["factionName"]) : null),
				"dna" => Fitting::DNA($killdata["items"],$killdata["victim"]["shipTypeID"]));
		}

		return $dna;
	}
}
