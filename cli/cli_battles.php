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

class cli_battles implements cliCommand
{
	public function getDescription()
	{
		return "";
	}

	public function getAvailMethods()
	{
		return ""; // Space seperated list
	}

	public function getCronInfo()
	{
		return array(0 => ""); // Always run
	}

	public function execute($parameters, $db)
	{
		$battles = Db::query("SELECT * FROM zz_battle_report");

		foreach($battles as $battle)
		{
			if($battle["checked"] == 0)
			{
				$battleID = $battle["battleID"];
				$systemID = $battle["solarSystemID"];
				$time = (int) $battle["dttm"];
				$options = $battle["options"];
				$showBattleOptions = false;

				$json_options = json_decode($options, true);
				if (!isset($json_options["A"])) $json_options["A"] = array();
				if (!isset($json_options["B"])) $json_options["B"] = array();

				$params = array("solarSystemID" => $systemID, "relatedTime" => $time, "exHours" => 1);
				$kills = Kills::getKills($params);
				$summary = Related::buildSummary($kills, $params, $json_options);

				if(!empty($kills))
				{
					// System and region name
					$systemName = Info::getSystemName($systemID);
					$regionID = Info::getRegionIDFromSystemID($systemID);
					$regionName = Info::getRegionName($regionID);

					$unixTime = strtotime($time);
					$timestamp = date("Y-m-d H:i", $unixTime);

					// Define all the kill/loss statistics including who was involved on which side..
					$killsA = $summary["teamA"]["totals"]["totalShips"];
					$killsB = $summary["teamB"]["totals"]["totalShips"];

					$teamAinvolved = $summary["teamA"]["totals"]["pilotCount"];
					$teamBinvolved = $summary["teamB"]["totals"]["pilotCount"];

					$teamApoints = $summary["teamA"]["totals"]["total_points"];
					$teamBpoints = $summary["teamB"]["totals"]["total_points"];

					$teamAvs = json_encode($summary["teamA"]["entities"]);
					$teamBvs = json_encode($summary["teamB"]["entities"]);

					$teamAkillIDs = $summary["teamA"]["killIDs"];
					$teamBkillIDs = $summary["teamB"]["killIDs"];

					foreach($teamAkillIDs as $killID)
						$teamAkillData[] = Killmail::get($killID);

					foreach($teamBkillIDs as $killID)
						$teamBkillData[] = Killmail::get($killID);

					$teamAkillDataJson = json_encode($teamAkillData);
					$teamBkillDataJson = json_encode($teamBkillData);

					Db::execute("INSERT INTO zz_battles (battleID, solarSystemID, solarSystemName, regionID, regionName, dttm, teamAkills, teamApilotCount, teamApoints, teamAinvolved, teamAJson, teamBkills, teamBpilotCount, teamBpoints, teamBinvolved, teamBJson)
						VALUES (:battleID, :solarSystemID, :solarSystemName, :regionID, :regionName, :dttm, :teamAkills, :teamApilotCount, :teamApoints, :teamAinvolved, :teamAJson, :teamBkills, :teamBpilotCount, :teamBpoints, :teamBinvolved, :teamBJson)",
						array(
							":battleID" => $battleID,
							":solarSystemID" => $systemID,
							":solarSystemName" => $systemName,
							":regionID" => $regionID,
							":regionName" => $regionName,
							":dttm" => $timestamp,
							":teamAkills" => $killsA,
							":teamApilotCount" => $teamAinvolved,
							":teamApoints" => $teamApoints,
							":teamAinvolved" => $teamAvs,
							":teamAJson" => $teamAkillDataJson,
							":teamBkills" => $killsB,
							":teamBpilotCount" => $teamBinvolved,
							":teamBpoints" => $teamBpoints,
							":teamBinvolved" => $teamBvs,
							":teamBJson" => $teamBkillDataJson
						)
					);

					Db::execute("UPDATE zz_battle_report SET checked = 1 WHERE battleID = :battleID", array(":battleID" => $battleID));
				}
			}
		}
	}
}
