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

$warData = War::getWarInfo($warID);
$warFinished = Db::queryField("select timeFinished < now() finished from zz_wars where warID = :warID", "finished", array(":warID" => $warID));

$p = array("war" => $warID);
$kills = Kills::getKills($p);

$topPods = array();
$topIsk = array();
$topPoints = array();
$topKillers = array();
$page = 1;
$pageTitle = "War $warID";

$p["kills"] = true;
if (!$warFinished) $p["pastSeconds"] = (7*86400);

$top = array();
$top[] = Info::doMakeCommon("Top Characters", "characterID", Stats::getTopPilots($p, $warFinished));
$top[] = Info::doMakeCommon("Top Corporations", "corporationID", Stats::getTopCorps($p, $warFinished));
$top[] = Info::doMakeCommon("Top Alliances", "allianceID", Stats::getTopAllis($p, $warFinished));
$top[] = Info::doMakeCommon("Top Ships", "shipTypeID", Stats::getTopShips($p, $warFinished));
$top[] = Info::doMakeCommon("Top Systems", "solarSystemID", Stats::getTopSystems($p, $warFinished));

$p["limit"] = 5;
$topIsk = array(); //Stats::getTopIsk($p);
unset($p["pastSeconds"]);
unset($p["kills"]);

// get latest kills
$killsLimit = 50;
$p["limit"] = $killsLimit;
$preKills = Kills::getKills($p);
$kills = array();
$agrID = $warData["aggressor"];
$dfdID = $warData["defender"];

foreach($preKills as $kill)
{
	$victim = $kill["victim"];
	if ($victim["corporationID"] == $dfdID || $victim["allianceID"] == $dfdID) $kill["displayAsKill"] = true;
	else $kill["displayAsLoss"] = true;
	$kills[] = $kill;
}

$app->render("index.html", array("war" => $warData, "wars" => array($warData), "topPods" => $topPods, "topIsk" => $topIsk, "topPoints" => $topPoints, "topKillers" => $top, "kills" => $kills, "page" => $page, "pageType" => "war", "pager" => false, "pageTitle" => $pageTitle));
