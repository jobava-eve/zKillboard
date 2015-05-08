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

class api_alliInfo implements apiEndpoint
{
	public function getDescription()
	{
		return array("type" => "description", "message" =>
				"Gives you informatiton on a corporation, including it's members"
			);
	}

	public function getAcceptedParameters()
	{
		return array("type" => "parameters", "parameters" =>
			array(
				"allianceID" => "The allianceID of the corporation you need information on"
			)
		);
	}
	public function execute($parameters)
	{
		$data = array();
		if(isset($parameters["allianceID"]))
		{
			$allianceID = (int) $parameters["allianceID"][0];
			//$data = json_decode(Db::queryField("SELECT information FROM zz_corporations WHERE allianceID = :alliID", "information", array(":alliID" => $allianceID)), true);
		}

		$allianceID = $parameters["allianceID"][0];
		$allianceActiveSystemID = Db::queryField("SELECT solarSystemID, count(*) AS hits FROM zz_participants WHERE allianceID = :alliID AND dttm >= date_sub(now(), interval 30 day) GROUP BY solarSystemID ORDER BY hits DESC LIMIT 10000", "solarSystemID", array(":alliID" => $allianceID));
		$data["allianceActiveArea"] = isset($allianceID) ? Info::getRegionName(Info::getRegionIDFromSystemID($allianceActiveSystemID)) : "";
		$data["lifeTimeKills"] = Db::queryField("SELECT SUM(destroyed) AS kills FROM zz_stats WHERE typeID = :alliID", "kills", array(":alliID" => $allianceID), 3600);
		$data["lifeTimeLosses"] = Db::queryField("SELECT SUM(lost) AS losses FROM zz_stats WHERE typeID = :alliID", "losses", array(":alliID" => $allianceID), 3600);
		$data["top100FlownShips"] = Stats::getTopShips(array("allianceID" => $allianceID, "kills" => true, "month" => date("m"), "year" => date("y"), "limit" => 100), true);
		$data["top100ActiveSystems"] = Stats::getTopSystems(array("allianceID" => $allianceID, "kills" => true, "month" => date("m"), "year" => date("y"), "limit" => 100), true);
		$data["lastUpdatedOnBackend"] = Db::queryField("SELECT lastUpdated FROM zz_alliances WHERE allianceID = :alliID", "lastUpdated", array(":alliID" => $allianceID));
		$members = Db::query("SELECT characterID, name FROM zz_characters WHERE allianceID = :alliID", array(":alliID" => $allianceID));
		$data["memberArrayCount"] = count($members);
		$data["members"] = $members;
		$supers = Db::query("SELECT a.characterID AS characterID, b.name AS name, a.shipTypeID AS shipTypeID, MAX(a.dttm) AS lastSeenDate FROM zz_participants a, zz_characters b WHERE a.characterID = b.characterID AND a.groupID IN (30, 659) AND a.allianceID = :alliID GROUP BY name ORDER BY characterID", array(":alliID" => $allianceID), 3600);
		$data["superCaps"] = $supers;

		$penis = "(..)==";
		$cnt = log($data["lifeTimeKills"]) * 3;
		$i = 0;
		while($i < $cnt)
		{
			$penis .= "=";
			$i++;
		}
		$data["ePeenSize"] = $penis . "D";
		return $data;
	}
}
