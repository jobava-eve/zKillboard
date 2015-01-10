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

$alltime = false;
$parameters = array();
// $time is an array
if(!isset($time))
	$parameters = array("limit" => 10, "kills" => true);
	switch ($page) {
		case "monthly":
			$parameters["year"] = date("Y");
			$parameters["month"] = date("n");
			break;
		case "weekly":
			$parameters["year"] = date("Y");
			$parameters["week"] = date("W");
			break;
		default:
			die("Not supported yet.");
	}

$topLists = array();
if($type == "kills")
{
	$topLists[] = array("type" => "character", "data" => Stats::getTopPilots($parameters, $alltime));
	$topLists[] = array("type" => "corporation", "data" => Stats::getTopCorps($parameters, $alltime));
	$topLists[] = array("type" => "alliance", "data" => Stats::getTopAllis($parameters, $alltime));
	$topLists[] = array("type" => "ship", "data" => Stats::getTopShips($parameters, $alltime));
	$topLists[] = array("type" => "system", "data" => Stats::getTopSystems($parameters, $alltime));
	$topLists[] = array("type" => "weapon", "data" => Stats::getTopWeapons($parameters, $alltime));
	$parameters["!factionID"] = 0;
	$topLists[] = array("name" => "Top Faction Characters", "type" => "character", "data" => Stats::getTopPilots($parameters, $alltime));
	$topLists[] = array("name" => "Top Faction Corporations", "type" => "corporation", "data" => Stats::getTopCorps($parameters, $alltime));
	$topLists[] = array("name" => "Top Faction Alliances", "type" => "alliance", "data" => Stats::getTopAllis($parameters, $alltime));

}
elseif($type == "points")
{
	$topLists[] = array("name" => "Top Character Points", "ranked" => "Points", "type" => "character", "data" => Stats::getTopPointsPilot($parameters));
	$topLists[] = array("name" => "Top Corporation Points", "ranked" => "Points", "type" => "corporation", "data" => Stats::getTopPointsCorp($parameters));
	$topLists[] = array("name" => "Top Alliance Points", "ranked" => "Points", "type" => "alliance", "data" => Stats::getTopPointsAlli($parameters));
	$parameters["!factionID"] = 0;
	$topLists[] = array("name" => "Top Faction Character Points", "ranked" => "Points", "type" => "character", "data" => Stats::getTopPointsPilot($parameters));
	$topLists[] = array("name" => "Top Faction Corporation Points", "ranked" => "Points", "type" => "corporation", "data" => Stats::getTopPointsCorp($parameters));
	$topLists[] = array("name" => "Top Faction Alliance Points", "ranked" => "Points", "type" => "alliance", "data" => Stats::getTopPointsAlli($parameters));

}

$app->render("top.html", array("topLists" => $topLists, "page" => $page, "type" => $type));
