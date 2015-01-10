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

if (!in_array($pageType, array("recent", "alltime"))) $app->notFound();
if (!in_array($subType, array("killers", "losers"))) $app->notFound();

$table = $pageType == "recent" ? "zz_ranks_recent" : "zz_ranks";
$pageTitle = $pageType == "recent" ? "Ranks - Recent (Past 90 Days)" : "Alltime Ranks";
$tableTitle = $pageType == "recent" ? "Recent Rank" : "Alltime Rank";

$rankColumns = $subType == "killers" ? "(sdRank <= 10 or pdRank <= 10 or idRank <= 10 or overallRank <= 10)" : "(slRank <= 10 or plRank <= 10 or ilRank <= 10)";

$types = array("pilot" => "characterID", "corp" => "corporationID", "alli" => "allianceID", "faction" => "factionID");
$names = array("character" => "Characters", "corp" => "Corporations", "alli" => "Alliances", "faction" => "Factions");
$ranks = array();
foreach ($types as $type=>$column) {
	$result = Db::query("select distinct typeID $column, r.* from $table r where type = '$type' and $rankColumns order by overallRank");
	if ($type == "pilot") $type = "character";
	$ranks[] = array("type" => $type, "data" => $result, "name" => $names[$type]);
}

Info::addInfo($ranks);

$app->render("ranks.html", array("ranks" => $ranks, "pageTitle" => $pageTitle, "tableTitle" => $tableTitle, "pageType" => $pageType, "subType" => $subType));
