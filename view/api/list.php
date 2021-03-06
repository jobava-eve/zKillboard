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

class api_list implements apiEndpoint
{
	public function getDescription()
	{
		return array("type" => "description", "message" =>
			"Lists all the endpoints available."
		);
	}

	public function getAcceptedParameters()
	{
		return array("type" => "parameters", "parameters" =>
			array(
			)
		);
	}
	public function execute($parameters)
	{
		return array("type" => "list", "endpoints" => self::endPoints());
	}

	private static function endPoints()
	{
		$endPoints = array();
		$dir = __DIR__;
		$data = scandir($dir);

		foreach($data as $e)
			if(!in_array($e, array(".", "..", "help.php", "docs.php", "list.php", "parameters.php", "base.txt")))
				$endPoints[str_replace(".php", "", $e)] = self::description(str_replace(".php", "", $e));

		return $endPoints;
	}

	private static function description($endpoint)
	{
		try
		{
			$fileName = __DIR__ . "/$endpoint.php";

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

			$data = $class->getDescription();
		}
		catch (Exception $e)
		{
			$data = array(
				"type" => "error",
				"message" => "$endpoint ended with error: " . $e->getMessage()
			);
		}

		return $data["message"];
	}
}
