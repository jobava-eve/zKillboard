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

$numDays = 7;

$campaign = Db::queryRow("select * from zz_campaigns where uri = :uri", array(":uri" => $uri), 1);
if ($campaign == null) $app->redirect("/", 302);

$title = "Campaign: " . $campaign["title"];
$subTitle = $campaign["subTitle"];
$p = json_decode($campaign["definition"], true);

$summary = Summary::getSummary("system", "solarSystemID", $p, 30000142, $p, true);

$topPoints = array();
$topPods = array();

$top = array();
$top[] = Info::doMakeCommon("Top Characters", "characterID", Stats::getTopPilots($p, true));
$top[] = Info::doMakeCommon("Top Corporations", "corporationID", Stats::getTopCorps($p, true));
$top[] = Info::doMakeCommon("Top Alliances", "allianceID", Stats::getTopAllis($p, true));
$top[] = Info::doMakeCommon("Top Ships", "shipTypeID", Stats::getTopShips($p, true));
$top[] = Info::doMakeCommon("Top Systems", "solarSystemID", Stats::getTopSystems($p, true));

$p["pastSeconds"] = ($numDays*86400);
$p["limit"] = 5;
$topIsk = Stats::getTopIsk($p, true);
$topIsk["title"] = "Most Valuable Kills";
unset($p["pastSeconds"]);

// get latest kills
$killsLimit = 50;
$p["limit"] = $killsLimit;
if (isset($page) && $page > 0 && $page < 100) $p["page"] = $page;
else $page = 1;
$kills = Kills::getKills($p);

$app->render("campaign.html", array("topPods" => $topPods, "topIsk" => $topIsk, "topPoints" => $topPoints, "topKillers" => $top, "kills" => $kills, "page" => 1, "pageType" => "kills", "pager" => true, "requesturi" => "/campaign/burnjita3/", "page" => $page, "detail" => $summary, "pageTitle" => $title, "subTitle" => $subTitle));
