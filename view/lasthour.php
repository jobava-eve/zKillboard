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

$parameters = array("limit" => 10, "kills" => true, "pastSeconds" => 3600, "cacheTime" => 30);
$alltime = false;

$topKillers[] = array("type" => "character", "data" => Stats::getTopPilots($parameters, $alltime));
$topKillers[] = array("type" => "corporation", "data" => Stats::getTopCorps($parameters, $alltime));
$topKillers[] = array("type" => "alliance", "data" => Stats::getTopAllis($parameters, $alltime));

$topKillers[] = array("type" => "faction", "data" => Stats::getTopFactions($parameters, $alltime));
$topKillers[] = array("type" => "system", "data" => Stats::getTopSystems($parameters, $alltime));
$topKillers[] = array("type" => "region", "data" => Stats::getTopRegions($parameters, $alltime));

$topKillers[] = array("type" => "ship", "data" => Stats::getTopShips($parameters, $alltime));
$topKillers[] = array("type" => "group", "data" => Stats::getTopGroups($parameters, $alltime));
$topKillers[] = array("type" => "weapon", "data" => Stats::getTopWeapons($parameters, $alltime));

unset($parameters["kills"]);
$parameters["losses"] = true;
$topLosers[] = array("type" => "character", "ranked" => "Losses", "data" => Stats::getTopPilots($parameters, $alltime));
$topLosers[] = array("type" => "corporation", "ranked" => "Losses", "data" => Stats::getTopCorps($parameters, $alltime));
$topLosers[] = array("type" => "alliance", "ranked" => "Losses", "data" => Stats::getTopAllis($parameters, $alltime));

$topLosers[] = array("type" => "faction", "ranked" => "Losses", "data" => Stats::getTopFactions($parameters, $alltime));
$topLosers[] = array("type" => "ship", "ranked" => "Losses", "data" => Stats::getTopShips($parameters, $alltime));
$topLosers[] = array("type" => "group", "ranked" => "Losses", "data" => Stats::getTopGroups($parameters, $alltime));

$app->render("lasthour.html", array("topKillers" => $topKillers, "topLosers" => $topLosers, "time" => date("H:i")));
