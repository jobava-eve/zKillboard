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

class api_losses implements apiEndpoint
{
	public function getDescription()
	{
		return array("type" => "description", "message" =>
				"Shows losses for various entities, using various parameters. You must as minimum pass one of the requiredParameters."
			);
	}

	public function getAcceptedParameters()
	{
		return array("type" => "parameters",
			"parameters" => array(
				"characterID" => "list losses for a certain characterID.",
				"corporationID" => "list losses for a certain corporationID.",
				"allianceID" => "list losses for a certain allianceID.",
				"factionID" => "list losses for a certain factionID.",
				"shipTypeID" => "list losses where a certain shipTypeID is involved.",
				"groupID" => "list losses where a certain ship groupID is involved.",
				"solarSystemID" => "list losses that happened in a certain solarSystemID.",
				"regionID" => "list losses that happened in a certain regionID.",
				"w-space" => "Only list losses that has happened in wormhole space-",
				"solo" => "Only list losses where number of involved was 1.",
				"page" => "Pagination.",
				"orderDirection" => "ASC: Oldest to newest, DESC: newest to oldest (DESC is faster than ASC, by a factor 100).",
				"pastSeconds" => "only show losses that has happened in the past number of seconds.",
				"startTime" => "Show losses from a certain startTime. (Requires endTime).",
				"endTime" => "Show losses to a certain endTime. (Requires startTime).",
				"limit" => "Limit the amount of losses shown. Minimum 1, maximum 200.",
				"beforeKillID" => "Show killmails from before this killID.",
				"afterKillID" => "Show killmails after this killID.",
				"iskValue" => "Only show killmails with a total iskValue above this.",
				"no-attackers" => "Remove attackers from the killmail.",
				"no-items" => "Remove items from the killmail.",
				"finalblow-only" => "Only show killmails where the entity caused a finalBlow (Doesn't work with solarSystemID and regionID)."
			),
			"requiredParameters" => array(
				"characterID",
				"corporationID",
				"allianceID",
				"factionID",
				"shipTypeID",
				"groupID",
				"solarSystemID",
				"regionID",
				"solo",
				"w-space"
			)
		);
	}
	public function execute($parameters)
	{
		// Generate an accepted parameters array.
		$acceptedParameters = self::getAcceptedParameters();
		foreach($acceptedParameters["parameters"] as $key => $value)
			$acceptedParameters[] = $key;

		// Remove unwanted parameters
		foreach($parameters as $key => $value)
			if(!in_array($key, $acceptedParameters))
				unset($parameters[$key]);

		// It's the losses endpoint, so it has to be true..
		$parameters["losses"] = true;

		// If there aren't enough parameters being passed, throw an error.
		if(count($parameters) < 2)
			return array(
				"type" => "error",
				"message" => "Invalid request. Atleast two parameters are required."
			);

		// API is true
		$paramters["api"] = true;

		// At least one of these parameters is required
		$requiredM = array("characterID", "corporationID", "allianceID", "factionID", "shipTypeID", "groupID", "solarSystemID", "regionID", "solo", "w-space");
		$hasRequired = false;
		foreach($requiredM as $required)
			$hasRequired |= array_key_exists($required, $parameters);

		if (!isset($parameters["killID"]) && !$hasRequired)
			return array(
				"type" => "error",
				"message" => "Error, must pass atleast one required parameters."
			);

		$requestURI = $_SERVER["REQUEST_URI"];
		$key = md5("lossesApi:$requestURI");

		$data = Cache::get($key);
		if(empty($data))
		{
			$data = Feed::getKills($parameters);
			Cache::set($key, $data, 3600);
		}

		return $data;
	}
}
