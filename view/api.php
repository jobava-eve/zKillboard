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

// Load globals
global $apiWhiteList, $maxRequestsPerHour, $debug;

// Endpoints
$endpoints = endPoints();

// Endpoint
$endpoint = isset($flags[0]) ? $flags[0] : NULL;

// Parameters
$parameters = Util::convertUriToParameters();

// client IP
$ip = IP::get();

if(in_array($endpoint, $endpoints))
{
	try
	{
		$fileName = __DIR__ . "/api/$endpoint.php";
		if(!file_exists($fileName))
			throw new Exception();

		require_once $fileName;
		$className = "api_$endpoint";
		$class = new $className();

		if(!is_a($class, "apiEndpoint"))
		{
			$data = array(
				"type" => "error",
				"message" => "Endpoint does not implement apiEndpoint"
			);
		}

		$data = $class->execute($parameters);
	}
	catch (Exception $e)
	{
		$data = array(
			"type" => "error",
			"message" => "$endpoint ended with error: " . $e->getMessage()
		);
	}
}
else
{
	$data = array(
		"type" => "error",
		"message" => "No endpoint selected.",
		"endpoints" => array(
			"/api/list/",
			"/api/help/<endPoint>/",
			"/api/parameters/<endPoint>/"
		)
	);
}

// Scrape Checker If type isn't set, scrapecheck, otherwise don't..
$type = isset($data["type"]) ? "error" : NULL;
if($type == NULL)
	if(!in_array($ip, $apiWhiteList))
		scrapeCheck();

// Output the data
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
$uri = substr($_SERVER["REQUEST_URI"], 0, 256);
$ip = substr(IP::get(), 0, 64);
$count = Db::queryField("SELECT count(*) AS count FROM zz_scrape_prevention WHERE ip = :ip AND dttm >= date_sub(now(), interval 1 hour)", "count", array(":ip" => $ip), 0);
header("X-Bin-Request-Count: ". $count);
header("X-Bin-Max-Requests: ". $maxRequestsPerHour);
$app->etag(md5(serialize($data)));
$app->expires("+1 hour");
$userAgent = @$_SERVER["HTTP_USER_AGENT"];
if($debug)
	Log::log("API Fetch: " . $_SERVER["REQUEST_URI"] . " (" . $ip . " / " . $userAgent . ")");

if(isset($_GET["callback"]) && isValidCallback($_GET["callback"]))
{
	$app->contentType("application/javascript; charset=utf-8");
	header("X-JSONP: true");
	echo $_GET["callback"] . "(" . json_encode($data) . ")";
}
else
{
	$app->contentType("application/json; charset=utf-8");
	echo json_encode($data, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES);
}

interface apiEndpoint
{
	public function getDescription();
	public function getAcceptedParameters();
	public function execute($parameters);
}

function endPoints()
{
	$endPoints = array();
	$dir = __DIR__ . "/api/";
	$data = scandir($dir);

	foreach($data as $e)
		if(!in_array($e, array(".", "..")))
			$endPoints[] = str_replace(".php", "", $e);

	return $endPoints;
}

function scrapeCheck()
{
	global $apiWhiteList, $maxRequestsPerHour;
	$maxRequestsPerHour = isset($maxRequestsPerHour) ? $maxRequestsPerHour : 360;

	$uri = substr($_SERVER["REQUEST_URI"], 0, 256);
	$ip = substr(IP::get(), 0, 64);
	Db::execute("INSERT INTO zz_scrape_prevention (ip, uri, dttm) VALUES (:ip, :uri, now())", array(":ip" => $ip, ":uri" => $uri));

	if(!in_array($ip, $apiWhiteList))
	{
		$count = Db::queryField("SELECT count(*) AS count FROM zz_scrape_prevention WHERE ip = :ip AND dttm >= date_sub(now(), interval 1 hour)", "count", array(":ip" => $ip), 0);
		if($count > $maxRequestsPerHour)
		{
			$date = date("Y-m-d H:i:s");
			$cachedUntil = date("Y-m-d H:i:s", time() + 3600);
			header("Content-type: application/json; charset=utf-8");
			header("Retry-After: " . $cachedUntil . " GMT");
			header("HTTP/1.1 429 Too Many Requests");
			header("Etag: ".(md5(serialize($data))));
			$data = json_encode(
				array(
					"Error" => "You have too many API requests in the last hour. You are allowed a maximum of $maxRequestsPerHour requests.",
					"cachedUntil" => $cachedUntil
				)
			);
			echo $data;
			die();
		}
	}
}

function isValidCallback($subject)
{
	$identifier_syntax = '/^[$_\p{L}][$_\p{L}\p{Mn}\p{Mc}\p{Nd}\p{Pc}\x{200C}\x{200D}]*+$/u';

	$reserved_words = array('break', 'do', 'instanceof', 'typeof', 'case',
		'else', 'new', 'var', 'catch', 'finally', 'return', 'void', 'continue', 
		'for', 'switch', 'while', 'debugger', 'function', 'this', 'with', 
		'default', 'if', 'throw', 'delete', 'in', 'try', 'class', 'enum', 
		'extends', 'super', 'const', 'export', 'import', 'implements', 'let', 
		'private', 'public', 'yield', 'interface', 'package', 'protected', 
		'static', 'null', 'true', 'false'
	);

	return preg_match($identifier_syntax, $subject) && ! in_array(mb_strtolower($subject, 'UTF-8'), $reserved_words);
}