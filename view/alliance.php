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

// Find the allianceID
$allianceID = is_numeric($alliance) ? (int) $alliance : (int) Db::queryField("SELECT allianceID FROM zz_alliances WHERE name = :name", "allianceID", array(":name" => $alliance));

// If the allianceID we get from above is zero, don't even bother anymore.....
if($allianceID == 0)
	$app->redirect("/");
elseif(!is_numeric($alliance)) // if alliance isn't numeric, we redirect TO the allianceID!
	$app->redirect("/alliance/{$allianceID}/");

// Now we figure out all the parameters
$parameters = Util::convertUriToParameters();

// Unset the alliance => id, and make it allianceID => id
unset($parameters["alliance"]);
$parameters["allianceID"] = $allianceID;

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
$detail = Info::getAlliDetails($allianceID, $parameters);

// Define the page information and scope etc.
$pageName = isset($detail["allianceName"]) ? $detail["allianceName"] : "???";
$columnName = "allianceID";
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
$solo = Kills::mergeKillArrays($soloKills, array(), $limit, $columnName, $allianceID);


$topLists = array();
$topKills = array();
if ($pageType == "top" || $pageType == "topalltime") {
	$topParameters = $parameters; // array("limit" => 10, "kills" => true, "$columnName" => $allianceID);
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
	$topLists[] = Info::doMakeCommon("Top Ships", "shipTypeID", Stats::getTopShips($p));
	$topLists[] = Info::doMakeCommon("Top Systems", "solarSystemID", Stats::getTopSystems($p));

	$p["limit"] = 5;
	$topKills = Stats::getTopIsk($p);
}

// Load the list of corporations with API information
$corpList = array();
if ($pageType == "api")
	$corpList = Info::getCorps($allianceID);

// Load the corporation stats!
$corpStats = array();
if ($pageType == "corpstats")
	$corpStats = Info::getCorpStats($allianceID, $parameters);

// Fix the history data!
$detail["history"] = $pageType == "stats" ? Summary::getMonthlyHistory($columnName, $allianceID) : array();

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
	$kills = Kills::mergeKillArrays($mixed, array(), $limit, $columnName, $allianceID);

// Find the next and previous allianceID
$prevID = Db::queryField("select allianceID from zz_alliances where allianceID < :id order by allianceID desc limit 1", "allianceID", array(":id" => $allianceID), 300);
$nextID = Db::queryField("select allianceID from zz_alliances where allianceID > :id order by allianceID asc limit 1", "allianceID", array(":id" => $allianceID), 300);

// Wars
$warID = (int) $allianceID;
$extra = array();
$extra["hasWars"] = Db::queryField("select count(distinct warID) count from zz_wars where aggressor = $warID or defender = $warID", "count");
$extra["wars"] = array();
if ($pageType == "wars" && $extra["hasWars"]) {
	$extra["wars"][] = War::getNamedWars("Active Wars - Aggressor", "select * from zz_wars where aggressor = $warID and timeFinished is null order by timeStarted desc");
	$extra["wars"][] = War::getNamedWars("Active Wars - Defending", "select * from zz_wars where defender = $warID and timeFinished is null order by timeStarted desc");
	$extra["wars"][] = War::getNamedWars("Closed Wars - Aggressor", "select * from zz_wars where aggressor = $warID and timeFinished is not null order by timeFinished desc");
	$extra["wars"][] = War::getNamedWars("Closed Wars - Defending", "select * from zz_wars where defender = $warID and timeFinished is not null order by timeFinished desc");
}

$minKillID = Db::queryField("select min(killID) killID from zz_participants where dttm >= date_sub(now(), interval 90 day) and dttm < date_sub(now(), interval 89 day)", "killID", array(), 900);
if ($minKillID > 0) {
	$hasSupers = Db::queryField("select killID from zz_participants where isVictim = 0 and groupID in (30, 659) and allianceID and killID > $minKillID limit 1", "killID", array(":id" => $allianceID));
} else {
	$hasSupers = 0;
}

$extra["hasSupers"] = $hasSupers > 0;
$extra["supers"] = array();
if ($pageType == "supers" && $hasSupers)
{
	$months = 3;
	$data = array();
	$data["titans"]["data"] = Db::query("SELECT distinct characterID, count(distinct killID) kills, shipTypeID FROM zz_participants WHERE killID >= $minKillID AND isVictim = 0 AND groupID = 30 AND allianceID = :id GROUP BY allianceID ORDER BY 2 DESC", array(":id" => $allianceID), 900);
	$data["titans"]["title"] = "Titans";

	$data["moms"]["data"] = Db::query("SELECT distinct characterID, count(distinct killID) kills, shipTypeID FROM zz_participants WHERE killID >= $minKillID AND isVictim = 0 AND groupID = 659 AND allianceID = :id GROUP BY allianceID ORDER BY 2 DESC", array(":id" => $allianceID), 900);
	$data["moms"]["title"] = "Supercarriers";

	Info::addInfo($data);
	$extra["supers"] = $data;
	$extra["hasSupers"] = sizeof($data["titans"]["data"]) || sizeof($data["moms"]["data"]);
}

$renderParams = array(
	"pageName" => $pageName,
	"kills" => $kills,
	"losses" => $losses,
	"detail" => $detail,
	"page" => $page,
	"topKills" => $topKills,
	"mixed" => $mixedKills,
	"key" => "alliance",
	"id" => $allianceID,
	"pageType" => $pageType,
	"solo" => $solo,
	"topLists" => $topLists,
	"corps" => $corpList,
	"corpStats" => $corpStats,
	"summaryTable" => $stats,
	"pager" => (sizeof($kills) + sizeof($losses) >= $limit),
	"datepicker" => true,
	"prevID" => $prevID,
	"nextID" => $nextID,
	"extra" => $extra
);

//$app->etag(md5(serialize($renderParams)));
//$app->expires("+5 minutes");
$app->render("overview.html", $renderParams);
