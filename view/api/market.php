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

class api_market implements apiEndpoint
{
	public function getDescription()
	{
		return array("type" => "description", "message" =>
				"Dumps all the market data for a single day."
			);
	}

	public function getAcceptedParameters()
	{
		return array("type" => "parameters", "parameters" =>
			array(
				"date" => "The date in the format of YYYY-mm-dd"
			)
		);
	}
	public function execute($parameters)
	{
		$date = isset($parameters["date"]) ? date("Y-m-d", strtotime($parameters["date"])) : date("Y-m-d");

		$md5 = md5($date);
		$data = Cache::get($md5);

		if(!$data)
		{
			$data = Db::query("SELECT * FROM zz_item_price_lookup WHERE priceDate = :date", array(":date" => $date));
			Cache::set($md5, $data, 3600);
		}

		return $data;
	}
}
