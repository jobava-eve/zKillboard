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

class cli_fixGroupIDs implements cliCommand
{
	public function getDescription()
	{
		return "Fixes unknown groupIDs. |g|Usage: fixGroupIDs";
	}

	public function getAvailMethods()
	{
		return ""; // Space seperated list
	}

	public function execute($parameters, $db)
	{
		// Fix unknown group ID's
		$result = $db->query("select distinct killID from zz_participants where groupID != vGroupID and isVictim = 1 limit 1", array(), 0);
		foreach ($result as $row) {
			$killID = $row["killID"];
			$shipTypeID = $db->queryField("select shipTypeID from zz_participants where killID = $killID and isVictim = 1", "shipTypeID");
			if ($shipTypeID == 0) continue;
			$groupID = Info::getGroupID($shipTypeID);
			echo "Updating $killID to $groupID\n";
			$db->execute("update zz_participants set vGroupID = $groupID where killID = $killID");
		}
		CLI::out(sizeof($result) . " done!", true);
	}
}
