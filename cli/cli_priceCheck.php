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

/* Price check aisle 5! */
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
		return array(0 => ""); // Always (somewhat) run
	}

	public function execute($parameters, $db)
	{
		if (date("Gi") != 105 && !in_array('-f', $parameters)) return; // Only execute at 01:05
		global $debug;
		$typeIDs = $db->query("select typeID from ccp_invTypes where published = 1 and marketGroupID != 0", array(), 0);
		$size = count($typeIDs);
		$count = 0;
		foreach ($typeIDs as $row)
		{
			$typeID = $row["typeID"];
			$today = date("Y-m-d");
			$price = Price::getItemPrice($typeID, $today, true);
			$name = Info::getItemName($typeID);
			$count++;
			$price = Util::formatIsk($price);
			if ($debug) Log::log("$count/$size\t$typeID\t$price\t$name");
		}
	}
}
