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

class cli_pinger implements cliCommand
{
	public function getDescription()
	{
		return "Pings the database, to make sure it's alive. |w| Beware! This requires an IRC bot to function.|n| |g|Usage: pinger";
	}

	public function getAvailMethods()
	{
		return ""; // Space seperated list
	}

	public function execute($parameters, $db)
	{
		try
		{
			$db->query("select now()", array(), 0);
		} catch (Exception $ex)
		{
			Log::irc("|r|Unable to connect to the database: " . $ex->getMessage());
		}
	}
}
