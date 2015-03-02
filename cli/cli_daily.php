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

class cli_daily implements cliCommand
{
	public function getDescription()
	{
		return "Tasks that needs to run every day. |g|Usage: daily";
	}

	public function getAvailMethods()
	{
		return "";
	}

	public function getCronInfo()
	{
		return array(
			86400 => ""
		);
	}

	public function execute($parameters, $db)
	{
		// Allow non-published items to be searchable if they show up on a killmail
		$nonPublishedItems = $db->query("select * from (select distinct shipTypeID, typeName, published from ccp_invTypes i left join zz_participants p on (i.typeID = p.shipTypeID) where published = 0) as foo where shipTypeID is not null"); // This query sucks but returns magnitudes quicker than the proper way to write it
		foreach($nonPublishedItems as $row) {
			$typeID = $row["shipTypeID"];
			$db->execute("update ccp_invTypes set published = 1 where typeID = :typeID", array(":typeID" => $typeID));
		}

		// Clear out old ranks from progress table
		$db->execute("delete from zz_ranks_progress where dttm < date(date_sub(now(), interval 30 day))");
	}
}
