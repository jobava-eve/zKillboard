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

class cli_statsQueue implements cliCommand
{
	public function getDescription()
	{
		return "Runs statistics against processed kills in the queue.";
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
		if (Util::isMaintenanceMode()) return;
		$timer = new Timer();
		while ($timer->stop() < 59000)
		{
			$processedKills = $db->query("SELECT killID FROM zz_stats_queue", array(), 0);
			foreach($processedKills as $row)
			{
				if($timer->stop() > 65000) exit();

				$killID = $row["killID"];
				Stats::calcStats($killID, true);
				// Add points and total value to the json stored in the database
				$raw = Killmail::get($killID);
				$json = json_decode($raw, true);
				unset($json["_stringValue"]);
				unset($json["zkb"]);
				$stuff = $db->queryRow("select * from zz_participants where killID = :killID and isVictim = 1", array(":killID" => $killID), 0);
				if ($stuff != null)
				{
					StatsD::increment("statsQueue_processed");

					$zkb = array();
					$zkb["totalValue"] = $stuff["total_price"];
					$zkb["points"] = $stuff["points"];
					$hash = Db::queryField("select hash from zz_crest_killmail where killID = :killID and processed = 1", "hash", array(":killID" => $killID));
					$zkb["source"] = $hash ? "CREST" : "API";

					if ($hash)
						$zkb["hash"] = $hash;

					$json["zkb"] = $zkb;

					$raw = json_encode($json);
					$db->execute("update zz_killmails set kill_json = :raw where killID = :killID", array(":killID" => $killID, ":raw" => $raw));
				}

				$db->execute("delete from zz_stats_queue where killID = :killID", array(":killID" => $killID));
				$db->execute("insert ignore into zz_crest_queue values (:killID)", array(":killID" => $killID));

				// Be social
				Social::beSocial($killID);

				if (class_exists("Stomp"))
					StompUtil::sendKill($killID);
			}
			sleep(1);
		}
	}
}
