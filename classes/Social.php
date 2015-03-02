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

class Social
{
	public static function beSocial($killID)
	{
		global $beSocial;
		if (!isset($beSocial))
			$beSocial = false;

		if ($beSocial == false)
			return;

		if ($killID < 0)
			return;

		$ircMin = 5000000000;
		$twitMin = 10000000000;

		$count = Db::queryField("select count(*) count from zz_social where killID = :killID", "count", array(":killID" => $killID), 0);
		if ($count != 0)
			return;

		// Get victim info
		$victimInfo = Db::queryRow("select * from zz_participants where dttm >= date_sub(now(), interval 1 day) and killID = :killID and isVictim = 1", array(":killID" => $killID));
		if ($victimInfo == null)
			return;

		$totalPrice = $victimInfo["total_price"];

		Info::addInfo($victimInfo);

		// Reduce spam of freighters and jump freighters
		$shipGroupID = $victimInfo["groupID"];
		if (in_array($shipGroupID, array(513, 902)))
		{
			$shipPrice = Price::getItemPrice($victimInfo["shipTypeID"], $victimInfo["dttm"]);
			$ircMin += $shipPrice;
			$twitMin += $shipPrice;
		}

		$worthIt = false;
		$worthIt |= $totalPrice >= $ircMin;
		if (!$worthIt)
			return;

		$tweetIt = false;
		$tweetIt |= $totalPrice >= $twitMin;

		global $fullAddr, $twitterName;
		$url = "$fullAddr/kill/$killID/";

		if ($url == "")
			$url = "$fullAddr/kill/$killID/";

		$message = "|g|" . $victimInfo["shipName"] . "|n| worth |r|" . Util::formatIsk($totalPrice) . " ISK|n| was destroyed! $url";

		if (!isset($victimInfo["characterName"]))
			$victimInfo["characterName"] = $victimInfo["corporationName"];

		if (strlen($victimInfo["characterName"]) < 25)
		{
			$name = $victimInfo["characterName"];
			if (Util::endsWith($name, "s"))
				$name .= "'";
			else
				$name .= "'s";

			$message = "$name $message";
		}

		Db::execute("insert into zz_social (killID) values (:killID)", array(":killID" => $killID));

		Log::irc("$message");
		$message = Log::stripIRCColors($message);

		$message .= " #tweetfleet #eveonline";
		if (strlen($message) > 120)
			$message = str_replace(" worth ", ": ", $message);

		if (strlen($message) > 120)
			$message = str_replace(" was destroyed!", "", $message);

		if ($tweetIt && strlen($message) <= 120)
		{
			$ret = Twit::sendMessage($message);
			$twit = "https://twitter.com/{$twitterName}/status/" . $ret->id;
			Log::irc("Message was also tweeted: |g|$twit");
		}
	}
}
