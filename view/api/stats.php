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

class api_stats implements apiEndpoint
{
	public function getDescription()
	{
		return array("type" => "description", "message" =>
				"Shows stats for an entity"
			);
	}

	public function getAcceptedParameters()
	{
		return array("type" => "parameters", "parameters" =>
			array(
				"characterID" => "Get stats for a certain characterID.",
				"corporationID" => "Get stats for a certain corporationID.",
				"allianceID" => "Get stats for a certain allianceID.",
				"factionID" => "Get stats for a certain factionID.",
				"shipTypeID" => "Get stats for a certain ship.",
				"groupID" => "Get stats for a certain group of ships.",
				"solarSystemID" => "Get stats for a certain solarsystem",
				"regionID" => "Get stats for a certain region.",
				"recent" => "Show recent or all time stats."
			)
		);
	}

	public function execute($parameters)
	{
		// Map calls to the internal names.
		$mapping = array(
			"factionID" => "faction", 
			"allianceID" => "alli", 
			"corporationID" => "corp",
			"characterID" => "pilot",
			"groupID" => "group",
			"shipTypeID" => "ship",
			"solarSystemID" => "system",
			"regionID" => "region"
		);

		$allowed_types = array(
			"factionID", 
			"allianceID", 
			"corporationID",
			"characterID",
			"groupID",
			"shipTypeID",
			"solarSystemID",
			"regionID"
		);

		$statsTable = isset($parameters["recent"]) ? "zz_stats_recent" : "zz_stats";

		foreach($parameters as $key => $value)
			if(!in_array($key, $allowed_types))
				unset($parameters[$key]);

		foreach($parameters as $key => $value)
		{
			$flag = $key;
			$id = $value[0];
		}

		$type = isset($flag) ? $mapping[$flag] : null;

		if(!$type)
			return array(
				"type" => "error",
				"message" => "Please use a valid type."
			);

		$stat_totals  = Db::queryRow('SELECT SUM(destroyed) AS countDestroyed, SUM(lost) AS countLost, SUM(pointsDestroyed) AS pointsDestroyed, SUM(pointsLost) AS pointsLost, SUM(iskDestroyed) AS iskDestroyed, SUM(iskLost) AS iskLost FROM ' . $statsTable . ' WHERE type = :type AND typeID = :id', array(':type' => $type, ':id' => $id));
		$stat_details = Db::query('SELECT groupID, destroyed AS countDestroyed, lost AS countLost, pointsDestroyed, pointsLost, iskDestroyed, iskLost FROM ' . $statsTable . ' WHERE type = :type AND typeID = :id', array(':type' => $type, ':id' => $id));

		$output = array();
		$output["totals"] = $stat_totals;
		foreach($stat_details as $detail)
			$output["groups"][array_shift($detail)] = $detail;

		$characterID = isset($parameters["characterID"][0]) ? $parameters["characterID"][0] : NULL;
		$corporationID = isset($parameters["corporationID"][0]) ? $parameters["corporationID"][0] : Info::getCharacterAffiliations($characterID)["corporationID"];
		$verified = (bool) Db::queryField("SELECT count(*) AS count FROM zz_api_characters WHERE ((characterID = :characterID AND isDirector = 'F') OR (corporationID = :corporationID AND isDirector = 'T')) AND errorCode = 0", "count", array(":characterID" => $characterID, ":corporationID" => $corporationID), 0);
		$output["apiVerified"] = $verified;

		return $output;
	}
}
