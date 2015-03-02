<?php

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
global $apiWhiteList;

// Parameters
$parameters = Util::convertUriToParameters();

// Endpoints
$endpoints = array("battles");

// Endpoint
$endpoint = isset($flags[0]) ? $flags[0] : NULL;

// XML
$xml = isset($parameters["xml"]) ? true : false;

// Scrape Checker
scrapeCheck($xml);


if(in_array($endpoint, $endpoints))
{
	include(__DIR__ . "/{$endpoint}.php");
}
else
{
	echo "blabla endpoints available blabla";
}

function scrapeCheck($xml)
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
			if($xml)
			{
				$data = "<?xml version=\"1.0\" encoding=\"UTF-8\"?" . ">"; // separating the ? and > allows vi to still color format code nicely
				$data .= "<eveapi version=\"2\" zkbapi=\"1\">";
				$data .= "<currentTime>$date</currentTime>";
				$data .= "<result>";
				$data .= "<error>You have too many API requests in the last hour.  You are allowed a maximum of $maxRequestsPerHour requests.</error>";
				$data .= "</result>";
				$data .= "<cachedUntil>$cachedUntil</cachedUntil>";
				$data .= "</eveapi>";
				header("Content-type: text/xml; charset=utf-8");
			}
			else
			{
				header("Content-type: application/json; charset=utf-8");
				$data = json_encode(array("Error" => "You have too many API requests in the last hour.  You are allowed a maximum of $maxRequestsPerHour requests.", "cachedUntil" => $cachedUntil));
			}
			header("X-Bin-Request-Count: ". $count);
			header("X-Bin-Max-Requests: ". $maxRequestsPerHour);
			header("Retry-After: " . $cachedUntil . " GMT");
			header("HTTP/1.1 429 Too Many Requests");
			header("Etag: ".(md5(serialize($data))));
			echo $data;
			die();
		}
		header("X-Bin-Request-Count: ". $count);
		header("X-Bin-Max-Requests: ". $maxRequestsPerHour);
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
