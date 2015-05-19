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

class Price
{
	/**
	 * Obtain the price of an item.
	 *
	 * @static
	 * @param $typeID int The typeID of the item
	 * @param $date date The date of the item price value
     * @param $doPopulate bool If set, retrieve the market values from CCP
	 * @return double The price of the item.
	 */
	public static function getItemPrice($typeID, $date)
	{
		// Pods and noobships
		if(in_array($typeID, array(588, 596, 601, 670, 606, 33328)))
			return 10000;
		// Male Corpse, Female Corpse, Bookmarks, Plastic Wrap
		if(in_array($typeID, array(25, 51, 29148, 3468)))
			return 1;

		$date = isset($date) ? $date : date("Y-m-d");
		$price = Db::queryField("SELECT lowPrice FROM zz_item_price_lookup WHERE typeID = :typeID AND priceDate = :priceDate", "lowPrice", array(":typeID" => $typeID, ":priceDate" => $date), 0);

		// If the price is zero, fetch the last available price, since that's probably right.. i guess.. fml
		if($price == 0 || $price == NULL)
			$price = Db::queryField("SELECT lowPrice FROM zz_item_price_lookup WHERE typeID = :typeID ORDER BY priceDate DESC LIMIT 1", "lowPrice", array(":typeID" => $typeID), 0);
		// If price is zero, get the base price
		if ($price == 0)
			$price = self::getItemBasePrice($typeID);
		// If price is still zero, just set it to 1 cent
		if ($price == 0)
			$price = 0.01;

		return $price;
	}

	/**
	 * @static
	 * @param $typeID int
	 * @return double
	 */
	private static function getItemBasePrice($typeID)
	{
		// Market failed - do we have a basePrice in the database?
		$price = Db::queryField("select basePrice from ccp_invTypes where typeID = :typeID", "basePrice", array(":typeID" => $typeID));
		return isset($price) ? $price : 0;
	}
}
