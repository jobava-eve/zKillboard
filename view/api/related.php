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

class api_related implements apiEndpoint
{
	public function getDescription()
	{
		return array("type" => "description", "message" =>
				"Shows killmails that are related to a killmail done at a certain time, in a certain system."
			);
	}

	public function getAcceptedParameters()
	{
		return array("type" => "parameters", "parameters" =>
			array(
				"systemID" => "The solarSystemID that the kills happened in.",
				"timestamp" => "The timestamp for when the kills should've happened. (YYYYmmddHHii)",
				"exHours" => "Amount of hours to span the battle over. 1 to 12."
			)
		);
	}
	public function execute($parameters)
	{
		$systemID = isset($parameters["solarSystemID"]) ? $parameters["solarSystemID"] : NULL;
		$timestamp = isset($parameters["timestamp"]) ? (int) $parameters["timestamp"] : NULL;
		$exHours = isset($parameters["exHours"]) ? (int) $parameters["exHours"] : 1;

		if(!$systemID)
			return array(
					"type" => "error",
					"message" => "solarSystemID isn't set."
				);
		if(!$timestamp)
			return array(
					"type" => "error",
					"message" => "timestamp isn't set."
				);

		if($exHours < 1 || $exHours > 12)
			return array(
					"type" => "error",
					"message" => "exHours is below 1 or above 12."
				);

		$params = array("solarSystemID" => $systemID, "relatedTime" => $timestamp, "exHours" => $exHours);
		$md5 = md5(serialize($params));
		$cache = Cache::get($md5);
		if($cache)
			return $cache;

		$kills = Kills::getKills($params);
		$data = Related::buildSummary($kills, $params, array());
		Cache::set($md5, $data, 3600);
		return $data;
	}
}
