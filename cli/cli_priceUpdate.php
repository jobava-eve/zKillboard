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

class cli_priceUpdate implements cliCommand
{
	public function getDescription()
	{
		return "Updates the price data directly from eve-kill";
	}

	public function getAvailMethods()
	{
		return ""; // Space seperated list
	}

	public function getCronInfo()
	{
		return array(43200 => ""); // Run twice a day
	}

	public function execute($parameters, $db)
	{
		// This should really be user defined in the config, but whatever, this works for now
		$date = date("Y-m-d");
		$url = "https://beta.eve-kill.net/api/market/date/{$date}/";
		$data = json_decode(Util::getData($url), true);

		foreach($data as $item)
		{
			$typeID = $item["typeID"];
			$priceDate = $item["priceDate"];
			$avgPrice = $item["avgPrice"];
			$lowPrice = $item["lowPrice"];
			$highPrice = $item["highPrice"];

			// Check typeID is in database
			$exists = Db::queryField("SELECT typeID FROM ccp_invTypes WHERE typeID = :typeID", "typeID", array(":typeID" => $typeID), 0);

			if($exists == $typeID)
			{
				Db::execute("INSERT IGNORE INTO zz_item_price_lookup (typeID, priceDate, avgPrice, lowPrice, highPrice) VALUES (:typeID, :priceDate, :avgPrice, :lowPrice, :highPrice)", 
					array(
						":typeID" => $typeID, ":priceDate" => $priceDate, ":avgPrice" => $avgPrice, ":lowPrice" => $lowPrice, ":highPrice" => $highPrice
					)
				);
			}
		}
	}
}
