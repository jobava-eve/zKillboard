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

class api_charInfo implements apiEndpoint
{
	public function getDescription()
	{
		return array("type" => "description", "message" =>
				"Gives you a list of characters, with their corporation history, and some basic kill stats"
			);
	}

	public function getAcceptedParameters()
	{
		return array("type" => "parameters", "parameters" =>
			array(
				"characterID" => "The characterID of the character you need information on"
			)
		);
	}
	public function execute($parameters)
	{
		$data = array();
		if(isset($parameters["characterID"]))
		{
			$characterID = (int) $parameters["characterID"][0];
			$data = json_decode(Db::queryField("SELECT history FROM zz_characters WHERE characterID = :charID", "history", array(":charID" => $characterID)), true);
		}

		$corporationID = $data["corporationID"];
		$allianceID = $data["allianceID"];
		$lastSeenSystemID = Db::queryField("SELECT solarSystemID FROM zz_participants WHERE characterID = :charID ORDER BY dttm DESC LIMIT 1", "solarSystemID", array(":charID" => $characterID));
		$corpActiveSystemID = Db::queryField("SELECT solarSystemID, count(killID) AS hits FROM zz_participants WHERE characterID = :charID AND corporationID = :corpID AND dttm >= date_sub(now(), interval 30 day) GROUP BY solarSystemID ORDER BY hits DESC LIMIT 10000", "solarSystemID", array(":charID" => $characterID, ":corpID" => $corporationID));
		$allianceActiveSystemID = Db::queryField("SELECT solarSystemID, count(killID) AS hits FROM zz_participants WHERE characterID = :charID AND allianceID = :alliID AND dttm >= date_sub(now(), interval 30 day) GROUP BY solarSystemID ORDER BY hits DESC LIMIT 10000", "solarSystemID", array(":charID" => $characterID, ":alliID" => $allianceID));
		$data["allianceTicker"] = Db::queryField("SELECT ticker FROM zz_alliances WHERE allianceID = :allianceID", "ticker", array(":allianceID" => $allianceID));
		$data["corporationTicker"] = Db::queryField("SELECT ticker FROM zz_corporations WHERE corporationID = :corporationID", "ticker", array(":corporationID" => $corporationID));
		$data["lastSeenSystem"] = Info::getSystemName($lastSeenSystemID);
		$data["lastSeenRegion"] = Info::getRegionName(Info::getRegionIDFromSystemID($lastSeenSystemID));
		$data["lastSeenDate"] = Db::queryField("SELECT dttm FROM zz_participants WHERE characterID = :charID ORDER BY dttm DESC LIMIT 1", "dttm", array(":charID" => $characterID));
		$data["lastSeenShip"] = Info::getShipName(Db::queryField("SELECT shipTypeID FROM zz_participants WHERE characterID = :charID ORDER BY dttm DESC LIMIT 1", "shipTypeID", array(":charID" => $characterID)));
		$data["corporationActiveArea"] = Info::getRegionName(Info::getRegionIDFromSystemID($corpActiveSystemID));
		$data["allianceActiveArea"] = isset($allianceID) ? Info::getRegionName(Info::getRegionIDFromSystemID($allianceActiveSystemID)) : "";
		$data["lifeTimeKills"] = Db::queryField("SELECT SUM(destroyed) AS kills FROM zz_stats WHERE typeID = :charID", "kills", array(":charID" => $characterID), 3600);
		$data["lifeTimeLosses"] = Db::queryField("SELECT SUM(lost) AS losses FROM zz_stats WHERE typeID = :charID", "losses", array(":charID" => $characterID), 3600);
		$data["top10FlownShips"] = Stats::getTopShips(array("characterID" => $characterID, "kills" => true, "month" => date("m"), "year" => date("y"), "limit" => 10), true);
		$data["top10ActiveSystems"] = Stats::getTopSystems(array("characterID" => $characterID, "kills" => true, "month" => date("m"), "year" => date("y"), "limit" => 10), true);
		$data["lastUpdatedOnBackend"] = Db::queryField("SELECT lastUpdated FROM zz_characters WHERE characterID = :charID", "lastUpdated", array(":charID" => $characterID));
		$data["soloKills"] = Db::queryField("SELECT count(*) as kills FROM zz_participants WHERE characterID = :charID AND isVictim = 0 AND number_involved = 1", "kills", array(":charID" => $characterID));
		$data["blobKills"] = Db::queryField("SELECT count(*) as kills FROM zz_participants WHERE characterID = :charID AND isVictim = 0 AND number_involved >= 50", "kills", array(":charID" => $characterID));

		if($data["soloKills"] > 0 && $data["blobKills"] > 0)
			$data["percentageSoloPVPer"] = $data["soloKills"] / $data["blobKills"] * 100;

		$penis = "(..)==";
		$cnt = log($data["lifeTimeKills"]) * 3;
		$i = 0;
		while($i < $cnt)
		{
			$penis .= "=";
			$i++;
		}
		$data["ePeenSize"] = $penis . "D";

		$facepalmLosses = Db::queryField("SELECT count(*) AS losses FROM zz_participants WHERE characterID = :charID AND shipPrice >= 200000000 AND isVictim = 1", "losses", array(":charID" => $characterID));
		$facepalms = "(>ლ)";
		$cnt = log($facepalmLosses);
		$i = 0;
		while($i < $cnt)
		{
			$facepalms .= "(>ლ)";
			$i++;
		}

		$data["facepalms"] = $facepalms;
		return $data;

		// Do the same with corporation and alliance apis?
		// Full character list for corporations / alliances ? :D could be usefull for spymasters and shit
	}
}
