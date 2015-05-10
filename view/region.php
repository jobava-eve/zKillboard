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

// Find the regionID
if(!is_numeric($region))
	$regionID = (int) Db::queryField("SELECT regionID FROM ccp_systems WHERE regionName = :regionName", "regionID", array(":regionName" => $region), 3600);
else // Verify it exists
	$regionID = (int) Db::queryField("SELECT regionID FROM ccp_systems WHERE regionID = :regionID", "regionID", array(":regionID" => (int) $region), 3600);

// If the regionID we get from above is zero, don't even bother anymore.....
if($regionID == 0)
	$app->redirect("/");
elseif(!is_numeric($region)) // if region isn't numeric, we redirect TO the regionID!
	$app->redirect("/region/{$regionID}/");

// Now we figure out all the parameters
$parameters = Util::convertUriToParameters();

// Unset the region => id, and make it regionID => id
unset($parameters["region"]);
$parameters["regionID"] = $regionID;

// Make sure that the pageType is correct..
$subPageTypes = array("page", "groupID", "month", "year", "shipTypeID");
if(in_array($pageType, $subPageTypes))
	$pageType = "overview";

// Some defaults
@$page = max(1, $parameters["page"]);
$limit = 50;
$parameters["limit"] = $limit;
$parameters["page"] = $page;

// and now we fetch the info!
$detail = Info::getRegionDetails($regionID, $parameters);

// Define the page information and scope etc.
$pageName = isset($detail["regionName"]) ? $detail["regionName"] : "???";
$columnName = "regionID";
$mixedKills = $pageType == "overview" && UserConfig::get("mixKillsWithLosses", true);

// Load kills for the various pages.
$mixed = $pageType == "overview" ? Kills::getKills($parameters) : array();
$kills = $pageType ==  "kills" ? Kills::getKills($parameters) : array();

$topLists = array();
$topKills = array();
if ($pageType == "top") {
	$topParameters = $parameters; // array("limit" => 10, "kills" => true, "$columnName" => $regionID);
	$topParameters["limit"] = 10;
	$topParameters["year"] = date("Y");
	$topParameters["month"] = date("m");
	$topParameters["kills"] = true;

	$topLists[] = array("type" => "character", "data" => Stats::getTopPilots($topParameters, true));
	$topLists[] = array("type" => "corporation", "data" => Stats::getTopCorps($topParameters, true));
	$topLists[] = array("type" => "alliance", "data" => Stats::getTopAllis($topParameters, true));
	$topLists[] = array("type" => "ship", "data" => Stats::getTopShips($topParameters, true));
	$topLists[] = array("type" => "system", "data" => Stats::getTopSystems($topParameters, true));
	$topLists[] = array("type" => "weapon", "data" => Stats::getTopWeapons($topParameters, true));
}
else
{
	$p = $parameters;
	$numDays = 7;
	$p["limit"] = 10;
	$p["pastSeconds"] = $numDays * 86400;
	$p["kills"] = $pageType != "losses";

	$topLists[] = Info::doMakeCommon("Top Characters", "characterID", Stats::getTopPilots($p));
	$topLists[] = Info::doMakeCommon("Top Corporations", "corporationID", Stats::getTopCorps($p));
	$topLists[] = Info::doMakeCommon("Top Ships", "shipTypeID", Stats::getTopShips($p));
	$topLists[] = Info::doMakeCommon("Top Systems", "solarSystemID", Stats::getTopSystems($p));

	$p["limit"] = 5;
	$topKills = Stats::getTopIsk($p);
}

// Fix the history data!
$detail["history"] = $pageType == "stats" ? Summary::getMonthlyHistory($columnName, $regionID) : array();

// Stats
$cnt = 0;
$cnid = 0;
$stats = array();
$totalcount = ceil(count($detail["stats"]) / 4);
foreach ($detail["stats"] as $q) {
	if ($cnt == $totalcount) {
		$cnid++;
		$cnt = 0;
	}
	$stats[$cnid][] = $q;
	$cnt++;
}

// Mixed kills yo!
if ($mixedKills)
	$kills = Kills::mergeKillArrays($mixed, array(), $limit, $columnName, $regionID);

// Find the next and previous regionID
$prevID = Db::queryField("select regionID from ccp_systems where regionID < :id order by regionID desc limit 1", "regionID", array(":id" => $regionID), 300);
$nextID = Db::queryField("select regionID from ccp_systems where regionID > :id order by regionID asc limit 1", "regionID", array(":id" => $regionID), 300);

$renderParams = array(
	"pageName" => $pageName,
	"kills" => $kills,
	"detail" => $detail,
	"page" => $page,
	"topKills" => $topKills,
	"mixed" => $mixedKills,
	"key" => "region",
	"id" => $regionID,
	"pageType" => $pageType,
	"topLists" => $topLists,
	"summaryTable" => $stats,
	"pager" => (sizeof($kills) >= $limit),
	"datepicker" => true,
	"prevID" => $prevID,
	"nextID" => $nextID
);

$app->etag(md5(serialize($renderParams)));
$app->expires("+5 minutes");
$app->render("overview.html", $renderParams);
