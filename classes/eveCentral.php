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

class eveCentral
{
	protected static $address = "http://api.eve-central.com/api/marketstat";

	public static function getPrice($typeID, $regionID = 10000002)
	{
		$data = Util::getData(self::$address . "?typeid={$typeID}&regionlimit={$regionID}");
		$data = self::parseXML($data);

		return array("date" => date("Y-m-d"), "buy" => array("min" => $data["marketstat"]["type"]["buy"]["min"], "avg" => $data["marketstat"]["type"]["buy"]["avg"], "max" => $data["marketstat"]["type"]["buy"]["max"]), "sell" => array("min" => $data["marketstat"]["type"]["sell"]["min"], "avg" => $data["marketstat"]["type"]["sell"]["avg"], "max" => $data["marketstat"]["type"]["sell"]["max"]));
	}

	public static function getPrices($typeID = array(), $regionID = 10000002)
	{
		$url = self::$address . "?regionlimit={$regionID}";

		foreach ($typeID as $id)
			$url .= "&typeid={$id}";

		$data = Util::getData($url);
		$data = self::parseXML($data);

		$items = $data["marketstat"]["type"];

		$prices = array();
		foreach($items as $item)
			$prices[$item["@attributes"]["id"]] = array("date" => date("Y-m-d"), "buy" => array("min" => $item["buy"]["min"], "avg" => $item["buy"]["avg"], "max" => $item["buy"]["max"]), "sell" => array("min" => $item["sell"]["min"], "avg" => $item["sell"]["avg"], "max" => $item["sell"]["max"]));

		return $prices;
	}

	private static function parseXML($xml)
	{
		StatsD::increment("eveCentral_Price_Fetches");
		return json_decode(json_encode(simplexml_load_string($xml)), true);
	}
}
