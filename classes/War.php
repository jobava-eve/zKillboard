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

/**
 * Parser for raw killmails from ingame EVE.
 */

class War
{
	public static function getWars($id, $active = true, $combined = false)
	{
		if (!self::isAlliance($id))
		{
			$alliID = Db::queryField("select allianceID from zz_corporations where corporationID = :id", "allianceID", array(":id" => $id));
			if ($alliID != 0) $id = $alliID;
		}
		$active = $active ? "" : "not";
		$aggressing = Db::query("select * from zz_wars where aggressor = :id and timeFinished is $active null", array(":id" => $id));
		$defending = Db::query("select * from zz_wars where defender = :id and timeFinished is $active null", array(":id" => $id));
		if ($combined) return array_merge($aggressing, $defending);
		return array("agr" => $aggressing, "dfd" => $defending);
	}

	public static function getKillIDWarInfo($killID)
	{
		$warID = Db::queryField("select warID from zz_warmails where killID = :killID", "warID", array(":killID" => $killID));
		return self::getWarInfo($warID);
	}

	public static function getWarInfo($warID)
	{
		$warInfo = array();
		if ($warID == null) return $warInfo;
		$warInfo = Db::queryRow("select * from zz_wars where warID = :warID", array(":warID" => $warID));

		$agr = $warInfo["aggressor"];
		$agrIsAlliance = self::isAlliance($agr);
		$agrName = $agrIsAlliance ? Info::getAlliName($agr) : Info::getCorpName($agr);
		$warInfo["agrName"] = $agrName;
		$warInfo["agrLink"] = ($agrIsAlliance ? "/alliance/" : "/corporation/") . "$agr/";

		$dfd = $warInfo["defender"];
		$dfdIsAlliance = self::isAlliance($dfd);
		$dfdName = $dfdIsAlliance ? Info::getAlliName($dfd) : Info::getCorpName($dfd);
		$warInfo["dfdName"] = $dfdName;
		$warInfo["dfdLink"] = ($dfdIsAlliance ? "/alliance/" : "/corporation/") . "$dfd/";

		$warInfo["dscr"] = "$agrName vs $dfdName";
		return $warInfo;
	}

	public static function isAlliance($entityID)
	{
		return null != Db::queryField("select allianceID from zz_alliances where allianceID = :id", "allianceID", array(":id" => $entityID));
	}

	public static function getNamedWars($name, $query)
	{
		$warIDs = Db::query($query);
		$wars = array();
		foreach($warIDs as $row)
		{
			$wars[] = War::getWarInfo($row["warID"]);
		}
		return array("name" => $name, "wars" => $wars);
	}
}
