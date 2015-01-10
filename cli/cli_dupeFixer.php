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

class cli_dupeFixer implements cliCommand
{
	public function getDescription()
	{
		return "Finds duplicate mails and removes them. |g|Usage: dupeFixer";
	}

	public function getAvailMethods()
	{
		return ""; // Space seperated list
	}

	public function execute($parameters, $db)
	{
		$result = $db->query("select b.hash, a.killID from zz_killmails a left join (select hash, count(*) as count from zz_killmails where processed = 1 group by 1 having count(*) >= 2) as b on (a.hash = b.hash) where b.hash is not null and a.killID < 0", array(), 0);
		foreach ($result as $row) {
			$hash = $row["hash"];
			$mKillID = $row["killID"];
			$killID = $db->queryField("select killID from zz_killmails where hash = :hash and killID > 0 limit 1", "killID", array(":hash" => $hash), 0);
			Kills::cleanDupe($mKillID, $killID);
		}
		Log::log("Cleaned up " . sizeof($result) . " dupes");
	}
}
