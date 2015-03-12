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

class cli_wsreceive implements cliCommand
{
	public function getDescription()
	{
		return "Received killmails from the WebSocket server, currently it receives every killmail";
	}

	public function getAvailMethods()
	{
		return ""; // Space seperated list
	}

	public function execute($parameters, $db)
	{
		try
		{
			$wsCount = 0;
			$timer = new Timer();
			$client = new WebSocket\Client("wss://ws.eve-kill.net/kills/");
			while($timer->stop() < 65000)
			{
				$killmail = $client->receive();
				$killData = json_decode($killmail, true);
				$killID = $killData["killID"];

				$count = $db->queryField("SELECT count(1) AS count FROM zz_killmails WHERE killID = :killID LIMIT 1", "count", array(":killID" => $killID), 0);
				if($count == 0 && $killID > 0)
				{
					$hash = Util::getKillHash(null, json_decode($killmail));
					$inserted = $db->execute("INSERT IGNORE INTO zz_killmails (killID, hash, source, kill_json) VALUES (:killID, :hash, :source, :json)",
						array(":killID" => $killID, ":hash" => $hash, ":source" => "webSocket", ":json" => json_encode($killData)));

					$wsCount++;
					if($inserted)
						StatsD::increment("wsKills");
				}
			}
			if($wsCount > 0)
				Log::log("wsReceive Ended - Received {$wsCount} kills");
		}
		catch(Exception $ex)
		{
			$e = print_r($ex, true);
			Log::log("wsReceived ended with an error: \n$e\n");
		}
	}
}
