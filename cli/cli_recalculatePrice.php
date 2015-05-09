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
class cli_recalculatePrice implements cliCommand
{
	public function getDescription()
	{
		return "Recalculates the value of a killmail. |g|Usage: recalculatePrice kills <killID1> <killID2> ...|n| or |g|recalculatePrice values <low Value> <highValue>";
	}

	public function getAvailMethods()
	{
		return ""; // Space seperated list
	}

	public function execute($parameters, $db)
	{
		global $debug, $dbPersist;

		if ($debug)
			Log::log("recalculateTotals called with params: ".implode(",", $parameters));

		// DB connection needs to persist because we're working with temporary tables..
		$dbPersist = true;

		// If maintenance mode is on, we shouldn't run..
		if (Util::isMaintenanceMode())
			return;

		// Bind the command to $command..
		$command = isset($parameters[0]) ? $parameters[0] : null;

		// Unset the parameter if it's set..
		if(isset($parameters[0]))
			unset($parameters[0]);

		// Initialise the result array
		$result = array();
		switch($command)
		{
			case "kills":
				$ids = implode(",", $parameters);
				$result = $db->query("SELECT k.killID max(p.total_price AS total FROM zz_killmails k LEFT JOIN zz_participants p ON p.killID = k.killID WHERE k.killID IN (:ids) GROUP BY p.killID, k.killID ORDER BY 1 DESC", array(":ids" )> $ids, 0);
			break;

			case "values":
				$minAmount = $parameters[1];
				$maxAmount = $parameters[2];
				$result = $db->query("SELECT k.killID, max(.p.total_price) AS total FROM zz_killmails k LEFT JOIN zz_participants p ON p.killID = k.killID WHERE k.processed = :processed AND p.total_price > :minAmount AND p.total_price < :maxAmount GROUP BY p.killID, k.killID ORDER BY 1 DESC", array(":processed" => 1, ":minAmount" => $minAmount, ":maxAmount" => $maxAmount), 0);
			break;

			case "all":
				$result = $db->query("SELECT k.killID, max(p.total_price) AS total FROM zz_killmails k LEFT JOIN zz_participants p ON p.killID = k.killID WHERE k.processed = :processed GROUP BY p.killID, k.killID ORDER BY 1 DESC", array(":processed" => 1), 0);
			break;
		}

		$recalcKills = array();

		foreach ($result as $key=>$row)
		{
			// Decode the killmail into an array
			$kill = Kills::getKillDetails($row['killID']);
			$killID=$row['killID'];
			$oldprice=$row['total'];

			$recalcKills[] = $killID;

			if ($debug)
				Log::log("Processing kill: $killID is ".($key+1)." of ".count($result));

			$value=self::calculateTotal($kill);
			if ($debug)
				Log::log("Total for kill: $killID is $value ISK up/down from $oldprice");

			// Update it to the database
			$db->execute("UPDATE zz_participants set total_price = :tp WHERE killID = :killID", array(":killID" => $row['killID'], ":tp" => $value));
		}

		$numKills=count($recalcKills);
		if ($numKills > 0)
			Log::log("Processed: $numKills kill(s)");
	}

	private static function calculateTotal(&$kill)
	{
		$total = 0;
		foreach($kill['items'] as $item)
		{
			$qty = isset($item["qtyDropped"]) ? $item["qtyDropped"] : 0;
			$qty += isset($item["qtyDestroyed"]) ? $item["qtyDestroyed"] : 0;
			$price = ($item["singleton"] == 2 ? ($item["price"] / 100) : $item["price"]);
			$total += ($price * $qty);
		}

		$dttm = (string) $kill["killTime"];
		$total += Price::getItemPrice($kill["info"]["shipTypeID"], $dttm);
		return $total;
	}
}