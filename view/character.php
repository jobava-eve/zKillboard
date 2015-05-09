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

// Find the characterID
if(!is_numeric($character))
	$characterID = (int) Db::queryField("SELECT characterID FROM zz_characters WHERE name = :name", "characterID", array(":name" => $character), 3600);
else // Verify it exists
	$characterID = (int) Db::queryField("SELECT characterID FROM zz_characters WHERE characterID = :characterID", "characterID", array(":characterID" => (int) $character), 3600);

// If the characterID we get from above is zero, don't even bother anymore.....
if($characterID == 0)
	$app->redirect("/");
elseif(!is_numeric($character)) // if character isn't numeric, we redirect TO the characterID!
	$app->redirect("/character/{$characterID}/");

// Now we figure out all the parameters
$parameters = Util::convertUriToParameters();

// Unset the character => id, and make it characterID => id
unset($parameters["character"]);
$parameters["characterID"] = $characterID;

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
$detail = Info::getPilotDetails($characterID, $parameters);

// Define the page information and scope etc.
$pageName = isset($detail["characterName"]) ? $detail["characterName"] : "???";
$columnName = "characterID";
$mixedKills = $pageType == "overview" && UserConfig::get("mixKillsWithLosses", true);

// Load kills for the various pages.
$mixed = $pageType == "overview" ? Kills::getKills($parameters) : array();
$kills = $pageType ==  "kills" ? Kills::getKills($parameters) : array();
$losses = $pageType == "losses" ? Kills::getKills($parameters) : array();

// Solo parameters
$soloParams = $parameters;
if (!isset($parameters["kills"]) || !isset($parameters["losses"])) {
	$soloParams["mixed"] = true;
}

// Solo kills
$soloKills = Kills::getKills($soloParams);
$solo = Kills::mergeKillArrays($soloKills, array(), $limit, $columnName, $characterID);

$topLists = array();
$topKills = array();
// Top list on the top/topalltime page
if ($pageType == "top" || $pageType == "topalltime")
{
	$topParameters = $parameters;
	$topParameters["limit"] = 10;

	if ($pageType != "topalltime")
	{
		if (!isset($topParameters["year"]))
			$topParameters["year"] = date("Y");

		if (!isset($topParameters["month"]))
			$topParameters["month"] = date("m");
	}

	if (!array_key_exists("kills", $topParameters) && !array_key_exists("losses", $topParameters))
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
	// Top lists on the pages themselves.
	$p = $parameters;
	$numDays = 7;
	$p["limit"] = 10;
	$p["pastSeconds"] = $numDays * 86400;
	$p["kills"] = $pageType != "losses";

	$topLists[] = Info::doMakeCommon("Top Ships", "shipTypeID", Stats::getTopShips($p));
	$topLists[] = Info::doMakeCommon("Top Systems", "solarSystemID", Stats::getTopSystems($p));

	$p["limit"] = 5;
	$topKills = Stats::getTopIsk($p);
}

// Fix the history data!
$detail["history"] = $pageType == "stats" ? Summary::getMonthlyHistory($columnName, $characterID) : array();

// Figure out if the character is API verified or not
$count = Db::queryField("SELECT count(1) count FROM zz_api_characters WHERE characterID = :characterID", "count", array(":characterID" => $characterID));
$apiVerified = $count > 0 ? 1 : 0;

// Stats..
$cnt = 0;
$cnid = 0;
$stats = array();
$totalcount = ceil(count($detail["stats"]) / 4);
foreach ($detail["stats"] as $q)
{
	if ($cnt == $totalcount)
	{
		$cnid++;
		$cnt = 0;
	}
	$stats[$cnid][] = $q;
	$cnt++;
}

// Mixed kills yo!
if ($mixedKills)
	$kills = Kills::mergeKillArrays($mixed, array(), $limit, $columnName, $characterID);

// Find the next and previous characterID
$prevID = Db::queryField("select characterID from zz_characters where characterID < :id order by characterID desc limit 1", "characterID", array(":id" => $characterID), 300);
$nextID = Db::queryField("select characterID from zz_characters where characterID > :id order by characterID asc limit 1", "characterID", array(":id" => $characterID), 300);

$renderParams = array(
	"pageName" => $pageName,
	"kills" => $kills,
	"losses" => $losses,
	"detail" => $detail,
	"page" => $page,
	"topKills" => $topKills,
	"mixed" => $mixedKills,
	"key" => "character",
	"id" => $characterID,
	"pageType" => $pageType,
	"solo" => $solo,
	"topLists" => $topLists,
	"summaryTable" => $stats,
	"pager" => (sizeof($kills) + sizeof($losses) >= $limit),
	"datepicker" => true,
	"apiVerified" => $apiVerified,
	"prevID" => $prevID,
	"nextID" => $nextID
);

//$app->etag(md5(serialize($renderParams)));
//$app->expires("+5 minutes");
$app->render("overview.html", $renderParams);