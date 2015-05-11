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


if ($killID > 0 && strlen($hash) == 40)
{
	global $debug;
	$i = Db::execute("insert ignore into zz_crest_killmail (killID, hash) values (:killID, :hash)", array(":killID" => $killID, ":hash" => $hash));
	if($debug)
	{
		$ip = $_SERVER["REMOTE_ADDR"];
		Log::log("Remote CREST Submission: {$killID} ($ip)");
	}
//	if ($i)
//	{
//		// Do we already have this mail? If not, announce it
//		$count = Db::queryField("select count(*) count from zz_killmails where killID = :killID", "count", array(":killID" => $killID), 0);
//		if ($count == 0)
//		{
//			$ip = $_SERVER["REMOTE_ADDR"];
//			Log::log("Remote CREST Submission: $killID ($ip)");
//		}
//	}
}
