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

class api_battles implements apiEndpoint
{
	public function getDescription()
	{
		return array("type" => "description", "message" =>
			"Battles lists the battles that has been sorted by users. You can list all the battles that are available, and you can call up data for a single battle."
		);
	}

	public function getAcceptedParameters()
	{
		return array("type" => "parameters", "parameters" =>
			array(
				"list" => "Lists all the battles available to peruse.",
				"battle/#/" => "Lists data for a single battle. Replace # with a battleID."
			)
		);
	}
	public function execute($parameters)
	{
		$command = isset($parameters["battles"]) ? $parameters["battles"] : NULL;
		$battleID = isset($parameters["battle"]) ? $parameters["battle"] : 0;

		if($command == "list")
		{
			$data = array();
			$battles = Db::query("SELECT * FROM zz_battles", array(), 360);
			foreach($battles as $key => $value)
			{
				unset($battles[$key]["teamAJson"]);
				unset($battles[$key]["teamBJson"]);
			}
			return $battles;
		}
		elseif($command == "battle")
		{
			$getData = Db::queryRow("SELECT * FROM zz_battles WHERE battleID = :battleID", array(":battleID" => $battleID), 360);

			foreach($getData as $key => $value)
			{
				switch($key)
				{
					case "teamAinvolved":
					case "teamBinvolved":
						$data[$key] = json_decode($value, true);
					break;

					case "teamAJson":
					case "teamBJson":
						$subJson = json_decode($value, true);
						foreach($subJson as $d)
							$data[$key][] = json_decode($d, true);
					break;

					default:
							$data[$key] = $value;
					break;
				}
			}
			return $data;
		}
		else
			return array(
				"type" => "error",
				"message" => "No valid parameter passed."
			);
	}
}
