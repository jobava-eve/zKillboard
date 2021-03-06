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

// Find the corporationID
if(!is_numeric($corporation))
	$corporationID = (int) Db::queryField("SELECT corporationID FROM zz_corporations WHERE name = :name", "corporationID", array(":name" => $corporation), 3600);
else // Verify it exists
	$corporationID = (int) Db::queryField("SELECT corporationID FROM zz_corporations WHERE corporationID = :corporationID", "corporationID", array(":corporationID" => (int) $corporation), 3600);

// If the corporationID we get from above is zero, don't even bother anymore.....
if($corporationID == 0)
	$app->redirect("/");
elseif(!is_numeric($corporation)) // if corporation isn't numeric, we redirect TO the corporationID!
	$app->redirect("/corporation/{$corporationID}/");

// Now we figure out all the parameters
$parameters = Util::convertUriToParameters();

// Unset the corporation => id, and make it corporationID => id
unset($parameters["corporation"]);
$parameters["corporationID"] = $corporationID;

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
$detail = Info::getCorpDetails($corporationID, $parameters);

// Define the page information and scope etc.
$pageName = isset($detail["corporationName"]) ? $detail["corporationName"] : "???";
$columnName = "corporationID";
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
$solo = Kills::mergeKillArrays($soloKills, array(), $limit, $columnName, $corporationID);


$topLists = array();
$topKills = array();
if ($pageType == "top" || $pageType == "topalltime") {
	$topParameters = $parameters; // array("limit" => 10, "kills" => true, "$columnName" => $corporationID);
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
	$topLists[] = Info::doMakeCommon("Top Ships", "shipTypeID", Stats::getTopShips($p));
	$topLists[] = Info::doMakeCommon("Top Systems", "solarSystemID", Stats::getTopSystems($p));

	$p["limit"] = 5;
	$topKills = Stats::getTopIsk($p);
}

// Fix the history data!
$detail["history"] = $pageType == "stats" ? Summary::getMonthlyHistory($columnName, $corporationID) : array();

// Figure out if the corporation is API verified or not
$count = Db::queryField("select count(1) count from zz_api_characters where isDirector = 'T' and corporationID = :corpID", "count", array(":corpID" => $corporationID));
$apiVerified = $count > 0 ? 1 : 0;

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
	$kills = Kills::mergeKillArrays($mixed, array(), $limit, $columnName, $corporationID);

// Find the next and previous corporationID
$prevID = Db::queryField("select corporationID from zz_corporations where corporationID < :id order by corporationID desc limit 1", "corporationID", array(":id" => $corporationID), 300);
$nextID = Db::queryField("select corporationID from zz_corporations where corporationID > :id order by corporationID asc limit 1", "corporationID", array(":id" => $corporationID), 300);

// Wars
$warID = (int) $corporationID;
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
	$hasSupers = Db::queryField("select killID from zz_participants where isVictim = 0 and groupID in (30, 659) and corporationID = :id and killID > $minKillID limit 1", "killID", array(":id" => $corporationID));
} else {
	$hasSupers = 0;
}

$extra["hasSupers"] = $hasSupers > 0;
$extra["supers"] = array();
if ($pageType == "supers" && $hasSupers)
{
	$months = 3;
	$data = array();
	$data["titans"]["data"] = Db::query("SELECT distinct characterID, count(distinct killID) AS kills, shipTypeID FROM zz_participants WHERE killID >= $minKillID AND isVictim = 0 AND groupID = 30 AND corporationID = :id GROUP BY characterID ORDER BY 2 DESC", array(":id" => $corporationID), 900);
	$data["titans"]["title"] = "Titans";

	$data["moms"]["data"] = Db::query("SELECT distinct characterID, count(distinct killID) AS kills, shipTypeID FROM zz_participants WHERE killID >= $minKillID AND isVictim = 0 AND groupID = 659 AND corporationID = :id GROUP BY characterID ORDER BY 2 DESC", array(":id" => $corporationID), 900);
	$data["moms"]["title"] = "Supercarriers";

	Info::addInfo($data);
	$extra["supers"] = $data;
	$extra["hasSupers"] = sizeof($data["titans"]["data"]) || sizeof($data["moms"]["data"]);
}
if($pageType == "members")
{
	$memberLimit = 100;
	$offset = ($page - 1) * $memberLimit;
	$extra["memberList"] = Db::query("SELECT * FROM zz_characters WHERE corporationID = :corporationID ORDER BY name LIMIT $offset, $memberLimit", array(":corporationID" => $corporationID));
	$extra["memberCount"] = Db::queryField("SELECT count(*) AS count FROM zz_characters WHERE corporationID = :corporationID", "count", array(":corporationID" => $corporationID));
	foreach($extra["memberList"] as $key => $data)
	{
		$characterID = $data["characterID"];
		$allianceID = $data["allianceID"];
		$lastSeenSystemID = Db::queryField("SELECT solarSystemID FROM zz_participants WHERE characterID = :charID ORDER BY dttm DESC LIMIT 1", "solarSystemID", array(":charID" => $characterID));
		$extra["memberList"][$key]["lastSeenSystem"] = $lastSeenSystemID > 0 ? Info::getSystemName($lastSeenSystemID) : "Not Seen";
		$extra["memberList"][$key]["lastSeenRegion"] = $lastSeenSystemID > 0 ? Info::getRegionName(Info::getRegionIDFromSystemID($lastSeenSystemID)) : "Not Seen";
		$extra["memberList"][$key]["lastSeenDate"] = Db::queryField("SELECT dttm FROM zz_participants WHERE characterID = :charID ORDER BY dttm DESC LIMIT 1", "dttm", array(":charID" => $characterID));
		$extra["memberList"][$key]["lastSeenShip"] = Info::getShipName(Db::queryField("SELECT shipTypeID FROM zz_participants WHERE characterID = :charID AND shipTypeID != 0 ORDER BY dttm DESC LIMIT 1", "shipTypeID", array(":charID" => $characterID)));
		$extra["memberList"][$key]["lifeTimeKills"] = Db::queryField("SELECT SUM(destroyed) AS kills FROM zz_stats WHERE typeID = :charID", "kills", array(":charID" => $characterID), 3600);
		$extra["memberList"][$key]["lifeTimeLosses"] = Db::queryField("SELECT SUM(lost) AS losses FROM zz_stats WHERE typeID = :charID", "losses", array(":charID" => $characterID), 3600);
	}
}

$renderParams = array(
	"pageName" => $pageName,
	"kills" => $kills,
	"losses" => $losses,
	"detail" => $detail,
	"page" => $page,
	"topKills" => $topKills,
	"mixed" => $mixedKills,
	"key" => "corporation",
	"id" => $corporationID,
	"pageType" => $pageType,
	"solo" => $solo,
	"topLists" => $topLists,
	"summaryTable" => $stats,
	"pager" => (sizeof($kills) + sizeof($losses) >= $limit),
	"datepicker" => true,
	"apiVerified" => $apiVerified,
	"prevID" => $prevID,
	"nextID" => $nextID,
	"extra" => $extra
);

$app->etag(md5(serialize($renderParams)));
$app->expires("+5 minutes");
$app->render("overview.html", $renderParams);
