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

class Feed
{
	/**
	 * Returns kills in json format according to the specified parameters
	 *
	 * @static
	 * @param array $parameters
	 * @return array
	 */
	public static function getKills($parameters = array())
	{
		global $debug;
		$ip = IP::get();

		$userAgent = @$_SERVER["HTTP_USER_AGENT"];
		if(isset($parameters["limit"]) && $parameters["limit"] > 1000)
			$parameters["limit"] = 1000;
		if(isset($parameters["page"]))
			$parameters["limit"] = 1000;
		$kills = Kills::getKills($parameters, true, false);

		return self::getJSON($kills, $parameters);
	}

	/**
	 * Groups the kills together based on specified parameters
	 * @static
	 * @param array|null $kills
	 * @param array $parameters
	 * @return array
	 */
	public static function getJSON($kills, $parameters)
	{
		if ($kills == null) return array();
		$retValue = array();

		foreach ($kills as $kill) {
			$killID = $kill["killID"];
			$jsonText = Killmail::get($killID);
			$json = json_decode($jsonText, true);
			$involvedCount = count($json["attackers"]);

			if (array_key_exists("no-items", $parameters))
				unset($json["items"]);

			if (array_key_exists("finalblow-only", $parameters))
			{
				$data = $json["attackers"];
				unset($json["attackers"]);
				foreach($data as $attacker)
					if($attacker["finalBlow"] == "1")
						$json["attackers"][] = $attacker;
			}

			if (array_key_exists("no-attackers", $parameters))
				unset($json["attackers"]);

			if(isset($json["_stringValue"]))
				unset($json["_stringValue"]);

			$json["zkb"]["involved"] = count($involvedCount);
			if(!isset($json["zkb"]["totalValue"]))
				$json["zkb"]["totalValue"] = Db::queryField("SELECT total_price FROM zz_participants WHERE killID = :killID AND isVictim = 1", "total_price", array(":killID" => $killID));
			if(!isset($json["zkb"]["points"]))
				$json["zkb"]["points"] = Db::queryField("SELECT points FROM zz_participants WHERE killID = :killID AND isVictim = 1", "points", array(":killID" => $killID));
			if(!isset($json["zkb"]["source"]))
				$json["zkb"]["source"] = Db::queryField("SELECT source FROM zz_killmails WHERE killID = :killID", "source", array(":killID" => $killID));

			$retValue[] = json_encode($json);
		}
		return $retValue;
	}
}
