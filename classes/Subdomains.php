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

/**
 * Parser for raw killmails from ingame EVE.
 */

class Subdomains
{
	public static function getSubdomainParameters($serverName)
	{
		global $app, $twig, $baseAddr, $fullAddr;

		// Are we looking at an aliased subdomain?
		$alias = Db::queryField("select alias from zz_subdomains where subdomain = :serverName", "alias", array(":serverName" => $serverName), 60);
		if ($alias)
		{
			header("Location: http://$alias") ;
			exit();
		}
		if ($serverName != $baseAddr && strlen(str_replace(".$baseAddr", "", $serverName)) > 5) 
		{
			$serverName = Db::queryField("select subdomain from zz_subdomains where alias = :serverName", "subdomain", array(":serverName" => $serverName));
			if (strlen($serverName) == 0)
			{
				header("Location: http://$baseAddr") ;
				exit();
			}
		}
		$adfree = Db::queryField("select count(*) count from zz_subdomains where adfreeUntil >= now() and subdomain = :serverName", "count", array(":serverName" => $serverName));

		$board = str_replace(".$baseAddr", "", $serverName);
		$board = str_replace("_", " ", $board);
		$board = preg_replace('/^dot\./i', '.', $board);
		$board = preg_replace('/\.dot$/i', '.', $board);
		try {
			if ($board == "www") $app->redirect($fullAddr, 302);
		} catch (Exception $e) {
			return;
		}
		$numDays = 7;

		$faction = Db::queryRow("select * from ccp_zfactions where ticker = :board", array(":board" => $board), 3600);
		$alli = Db::queryRow("select * from zz_alliances where ticker = :board order by memberCount desc limit 1", array(":board" => $board), 3600);
		$corp = Db::queryRow("select * from zz_corporations where ticker = :board and memberCount > 0 order by memberCount desc limit 1", array(":board" => $board), 3600);

		$columnName = null;
		$id = null;
		if ($faction) {
			$p = array("factionID" => $faction["factionID"]);
			$twig->addGlobal("statslink", "/faction/" . $faction["factionID"] . "/");
		} else if ($alli) {
			$p = array("allianceID" => $alli["allianceID"]);
			$twig->addGlobal("statslink", "/alliance/" . $alli["allianceID"] . "/");
		} else if ($corp) {
			$p = array("corporationID" => $corp["corporationID"]);
			$twig->addGlobal("statslink", "/corporation/" . $corp["corporationID"] . "/");
		} else $p = array();

		return $p;
	}

}
