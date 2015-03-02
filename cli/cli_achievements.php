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

class cli_achievements implements cliCommand
{
	public function getDescription()
	{
		return "Runs through all the characters in the database, and figure out which achievements they are up for.";
	}

	public function getAvailMethods()
	{
		return ""; // Space seperated list
	}

	public function execute($parameters, $db)
	{

		$characters = Db::query("SELECT * FROM zz_characters");

		foreach($characters as $character)
		{
			if($character["characterID"] == 0 || $character["characterID"] == NULL)
				continue;

			$characterID = $character["characterID"];
			$characterName = $character["characterName"];

			// Babys first kill
			$firstKill = Db::queryField("SELECT count(*) AS count FROM zz_participants WHERE isVictim = 0 AND characterID = :characterID", "count", array(":characterID" => $characterID));

			var_dump($firstKill);
			echo "\n";
		}
	}
}
