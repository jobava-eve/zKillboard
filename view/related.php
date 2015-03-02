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

global $baseDir;

$systemID = (int) $system;
$relatedTime = (int) $time;

$json_options = json_decode($options, true);
if (!isset($json_options["A"])) $json_options["A"] = array();
if (!isset($json_options["B"])) $json_options["B"] = array();

$redirect = false;
if (isset($_GET["left"])) 
{
	$entity = $_GET["left"];
	if (!isset($json_options["A"])) $json_options["A"] = array();
	if (($key = array_search($entity, $json_options["B"])) !== false) unset($json_options["B"][$key]);
	if (!in_array($entity, $json_options["A"])) $json_options["A"][] = $entity;
	$redirect = true;
}
if (isset($_GET["right"]))
{
        $entity = $_GET["right"];
        if (!isset($json_options["B"])) $json_options["B"] = array();
	if (($key = array_search($entity, $json_options["A"])) !== false) unset($json_options["A"][$key]);
        if (!in_array($entity, $json_options["B"])) $json_options["B"][] = $entity;
        $redirect = true;
}
if ($redirect)
{
	$json = urlencode(json_encode($json_options));
	$url = "/related/$systemID/$relatedTime/o/$json/";
	$app->redirect($url, 302);
	die();
}

$systemName = Info::getSystemName($systemID);
$regionName = Info::getRegionName(Info::getRegionIDFromSystemID($systemID));
$unixTime = strtotime($relatedTime);
$time = date("Y-m-d H:i", $unixTime);

$exHours = 1;
if (((int) $exHours) < 1 || ((int) $exHours > 12)) $exHours = 1;

$key = "$systemID:$relatedTime:$exHours:" . json_encode($json_options);
$data = Cache::get($key);
if (!$data)
{
	$parameters = array("solarSystemID" => $systemID, "relatedTime" => $relatedTime, "exHours" => $exHours);
	$kills = Kills::getKills($parameters);
	$summary = Related::buildSummary($kills, $parameters, $json_options);
	$data = array("summary" => $summary, "systemName" => $systemName, "regionName" => $regionName, "time" => $time, "exHours" => $exHours, "solarSystemID" => $systemID, "relatedTime" => $relatedTime, "options" => json_encode($json_options));
	Cache::set($key, $data, 600);
}

$app->render("related.html", $data);
