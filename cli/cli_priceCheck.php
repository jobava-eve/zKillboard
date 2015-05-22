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

class cli_priceCheck implements cliCommand
{
	public function getDescription()
	{
		return "Updates prices for all published items";
	}

	public function getAvailMethods()
	{
		return "";
	}

	public function getCronInfo()
	{
		return array(3600 => ""); // Once an hour
	}

	public function execute($parameters, $db)
	{
		$typeIDs = $db->query("SELECT typeID FROM ccp_invTypes WHERE published = 1 AND marketGroupID != 0", array(), 0);
		$cnt = 0;
		$fetches = array();
		foreach($typeIDs as $row)
		{
			$typeID = $row["typeID"];
			$fetches[] = $typeID;
			$cnt++;
			if($cnt == 10)
			{
				self::updatePrice(eveCentral::getPrices($fetches));
				$fetches = array();
				$cnt = 0;
			}
		}
	}

	private function updatePrice($array)
	{
		// Have a bunch of catches here for the various prices
		foreach($array as $typeID => $data)
		{
			$avgPrice = 0;
			$lowPrice = 0;
			$highPrice = 0;
			$avgBuy = 0;
			$lowBuy = 0;
			$highBuy = 0;
			switch($typeID)
			{
				// Customs Office
				case 2233:
					// Fetch the price for gantry, nodes, modules, mainframes, cores and sum it up, that's the price for a customs office
					$gantry = eveCentral::getPrice(3962)["sell"]["min"];
					$nodes  = eveCentral::getPrice(2867)["sell"]["min"];
					$modules  = eveCentral::getPrice(2871)["sell"]["min"];
					$mainframes  = eveCentral::getPrice(2876)["sell"]["min"];
					$cores  = eveCentral::getPrice(2872)["sell"]["min"];
					$avgPrice = $gantry + (($nodes + $modules + $mainframes + $cores) * 8);
				break;

				// Motherships
				case 3628:
				case 22852:
				case 23913:
				case 23917:
				case 23919:
					$avgPrice = 20000000000; // 20b
				break;

				// Revenant
				case 3514:
					$avgPrice = 100000000000; // 100b
				break;

				// Titans
				case 671:
				case 3764:
				case 11567:
				case 23773:
					$avgPrice = 100000000000; // 100b
				break;

				// Turney frigs
				case 2834:
				case 3516:
				case 11375:
					$avgPrice = 80000000000; // 80b
				break;

				// Chremoas
				case 33397:
					$avgPrice = 120000000000; // 120b
				break;
				// Cambion
				case 32788:
					$avgPrice = 100000000000; // 100b
				break;

				// Adrestia
				case 2836:
					$avgPrice = 150000000000; // 150b
				break;
				// Vangel
				case 3518:
					$avgPrice = 90000000000; // 90b
				break;
				// Etana
				case 32790:
					$avgPrice = 100000000000; // 100b
				break;
				// Moracha
				case 33395:
					$avgPrice = 125000000000; // 125b
				break;
				// Mimir
				case 32209:
					$avgPrice = 100000000000; // 100b
				break;

				// Chameleon
				case 33675:
					$avgPrice = 120000000000; // 120b
				break;
				// Whiptail
				case 33673:
					$avgPrice = 100000000000; // 100b
				break;

				// Polaris
				case 9860:
					$avgPrice = 1000000000000; // 1t
				break;
				// Cockroach
				case 11019:
					$avgPrice = 1000000000000; // 1t
				break;

				// Gold Magnate
				case 11940:
				// Silver Magnate
				case 11942:
				// Opux Luxury Yacht
				case 635:
				// Guardian-Vexor
				case 11011:
				// Opux Dragoon Yacht
					$avgPrice = 500000000000; // 500b
				break;

				// Megathron Federate Issue
				case 13202:
				// Raven State Issue
				case 26840:
				// Apocalypse Imperial Issue
				case 11936:
				// Armageddon Imperial Issue
				case 11938:
				// Tempest Imperial Issue
				case 26842:
					$avgPrice = 750000000000; // 750b
				break;

				// Default fallthrough
				default:
					$avgPrice = $data["sell"]["avg"];
					$lowPrice = $data["sell"]["min"];
					$highPrice = $data["sell"]["max"];
					$avgBuy = $data["buy"]["avg"];
					$lowBuy = $data["buy"]["min"];
					$highBuy = $data["buy"]["max"];

					// If the sellince price is under 0.05% different from the buying price then we swap them..
					if($highBuy > 0 && $lowPrice > 0)
					{
						if((($highBuy / $lowPrice) * 100) < 0.05)
						{
							// Make sure it has no chance of being duplicated
							$duplicationChance = Db::queryField("SELECT chanceOfDuplicating FROM ccp_invTypes WHERE typeID = :typeID", "chanceOfDuplicating", array(":typeID" => $typeID));
							if($duplicationChance == 0)
							{
								$avgPrice = $data["buy"]["avg"];
								$lowPrice = $data["buy"]["min"];
								$highPrice = $data["buy"]["max"];
								$avgBuy = $data["buy"]["avg"];
								$lowBuy = $data["buy"]["min"];
								$highBuy = $data["buy"]["max"];
							}
						}
					}
				break;
			}

			$date = $data["date"];
			// a fallthrough for pre-defined prices
			if($lowPrice == 0)
				$lowPrice = $avgPrice;
			if($highPrice == 0)
				$highPrice = $avgPrice;

			// Now we just insert it into the db with an update!
			Db::execute("INSERT INTO zz_item_price_lookup (typeID, priceDate, avgPrice, lowPrice, highPrice, avgBuy, lowBuy, highBuy) VALUES (:typeID, :priceDate, :avgPrice, :lowPrice, :highPrice, :avgBuy, :lowBuy, :highBuy)
				ON DUPLICATE KEY UPDATE avgPrice = :avgPrice, lowPrice = :lowPrice, highPrice = :highPrice, avgBuy = :avgBuy, lowBuy = :lowBuy, highBuy = :highBuy",
					array(":typeID" => $typeID, ":priceDate" => $date, ":avgPrice" => $avgPrice, ":lowPrice" => $lowPrice, ":highPrice" => $highPrice, ":avgBuy" => $avgBuy, ":lowBuy" => $lowBuy, ":highBuy" => $highBuy)
				);

			StatsD::increment("price_updates");
		}
	}
}
