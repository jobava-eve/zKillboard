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

class cli_updateCorporations implements cliCommand
{
	public function getDescription()
	{
		return "Updates the corporation Name and IDs. |g|Usage: updateCorporations <type>";
	}

	public function getAvailMethods()
	{
		return "";
	}

	public function getCronInfo()
	{
		return array(0 => "");
	}

	public function execute($parameters, $db)
	{
		self::updateCorporations($db);
	}

	private static function updateCorporations($db)
	{
		$db->execute("delete from zz_corporations where corporationID = 0");
		$result = $db->query("select corporationID, name, memberCount, ticker from zz_corporations where lastUpdated < date_sub(now(), interval 3 day) and corporationID >= 1000001 order by lastUpdated limit 1000", array(), 0);
		foreach($result as $row) {
			if (Util::is904Error()) return;
			$id = $row["corporationID"];

			$pheal = Util::getPheal();
			$pheal->scope = "corp";
			try {
				$corpInfo = $pheal->CorporationSheet(array("corporationID" => $id));
				$name = $corpInfo->corporationName;
				$ticker = $corpInfo->ticker;
				$memberCount = $corpInfo->memberCount;
				$ceoID = $corpInfo->ceoID;
				if ($ceoID == 1) $ceoID = 0;
				$json = json_encode(
					array(
						"corporationID" => $corpInfo->corporationID,
						"corporationName" => $corpInfo->corporationName,
						"allianceID" => $corpInfo->allianceID,
						"allianceName" => $corpInfo->allianceName,
						"factionID" => $corpInfo->factionID,
						"factionName" => $corpInfo->factionName,
						"ticker" => $corpInfo->ticker,
						"ceoID" => $corpInfo->ceoID,
						"ceoName" => $corpInfo->ceoName,
						"stationID" => $corpInfo->stationID,
						"stationName" => $corpInfo->stationName,
						"description" => $corpInfo->description,
						"url" => $corpInfo->url,
						"taxRate" => $corpInfo->taxRate,
						"memberCount" => $corpInfo->memberCount
					)
				);
				StatsD::increment("corporations_Updated");
				if ($name != "")
				{
					$db->execute("UPDATE zz_corporations SET information = :information WHERE corporationID = :corporationID", 
						array(":information" => $json, ":corporationID" => $corpInfo->corporationID));
					$db->execute("update zz_corporations set name = :name, ticker = :ticker, memberCount = :memberCount, ceoID = :ceoID, lastUpdated = now() where corporationID = :id", array(":id" => $id, ":name" => $name, ":ticker" => $ticker, ":memberCount" => $memberCount, ":ceoID" => $ceoID));
				}
			} catch (Exception $ex)
			{
				$db->execute("update zz_corporations set lastUpdated = now() where corporationID = :id", array(":id" => $id));
				$db->execute("update zz_corporations set name = :name where corporationID = :id and name = ''", array(":id" => $id, ":name" => "Corporation $id"));
				if ($ex->getCode() != 503)
					Log::log("ERROR Validating Corp $id: " . $ex->getMessage());
			}
			usleep(333333); // Pause for 333ms between each request (3 req/s)
		}
	}
}
