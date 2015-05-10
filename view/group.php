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

// Find the groupID (GroupID can only be numeric...)
$groupID = (int) Db::queryField("SELECT groupID FROM ccp_invTypes WHERE groupID = :groupID", "groupID", array(":groupID" => (int) $group), 3600);

// If the groupID we get from above is zero, don't even bother anymore.....
if($groupID == 0)
	$app->redirect("/");

// Now we figure out all the parameters
$parameters = Util::convertUriToParameters();

// Unset the group => id, and make it groupID => id
unset($parameters["group"]);
$parameters["groupID"] = $groupID;

// Make sure that the pageType is correct..
$subPageTypes = array("page", "group", "month", "year", "ship");
if(in_array($pageType, $subPageTypes))
	$pageType = "overview";

// Some defaults
@$page = max(1, $parameters["page"]);
$limit = 50;
$parameters["limit"] = $limit;
$parameters["page"] = $page;

// and now we fetch the info!
$detail = Info::getGroupDetails($groupID, $parameters);

// Define the page information and scope etc.
$pageName = isset($detail["groupName"]) ? $detail["groupName"] : "???";
$columnName = "groupID";
$mixedKills = $pageType == "overview" && UserConfig::get("mixKillsWithLosses", true);

// Load kills for the various pages.
$mixed = $pageType == "overview" ? Kills::getKills($parameters) : array();
$kills = $pageType ==  "kills" ? Kills::getKills($parameters) : array();

// Solo parameters
$soloParams = $parameters;
if (!isset($parameters["kills"])) {
	$soloParams["mixed"] = true;
}

// Solo kills
$soloKills = Kills::getKills($soloParams);
$solo = Kills::mergeKillArrays($soloKills, array(), $limit, $columnName, $groupID);

$topLists = array();
$topKills = array();
if ($pageType == "top" || $pageType == "topalltime") {
	$topParameters = $parameters; // array("limit" => 10, "kills" => true, "$columnName" => $groupID);
	$topParameters["limit"] = 10;

	if ($pageType != "topalltime") {
		if (!isset($topParameters["year"])) {
			$topParameters["year"] = date("Y");
		}

		if (!isset($topParameters["month"])) {
			$topParameters["month"] = date("m");
		}

	}
	if (!array_key_exists("kills", $topParameters) && !array_key_exists("losses", $topParameters)) {
		$topParameters["kills"] = true;
	}

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
	$topLists[] = Info::doMakeCommon("Top Systems", "solarSystemID", Stats::getTopSystems($p));

	$p["limit"] = 5;
	$topKills = Stats::getTopIsk($p);
}

// Mixed kills yo!
if ($mixedKills)
	$kills = Kills::mergeKillArrays($mixed, array(), $limit, $columnName, $groupID);

// Find the next and previous groupID
$prevID = Db::queryField("select groupID from ccp_invTypes where groupID < :id order by groupID desc limit 1", "groupID", array(":id" => $groupID), 300);
$nextID = Db::queryField("select groupID from ccp_invTypes where groupID > :id order by groupID asc limit 1", "groupID", array(":id" => $groupID), 300);

$renderParams = array(
	"pageName" => $pageName,
	"kills" => $kills,
	"detail" => $detail,
	"page" => $page,
	"topKills" => $topKills,
	"mixed" => $mixedKills,
	"key" => "group",
	"id" => $groupID,
	"pageType" => $pageType,
	"solo" => $solo,
	"topLists" => $topLists,
	"pager" => (sizeof($kills) >= $limit),
	"datepicker" => true,
	"prevID" => $prevID,
	"nextID" => $nextID
);

//$app->etag(md5(serialize($renderParams)));
//$app->expires("+5 minutes");
$app->render("overview.html", $renderParams);
