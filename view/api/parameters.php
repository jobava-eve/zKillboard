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

class api_parameters implements apiEndpoint
{
	public function getDescription()
	{
		return array("type" => "description", "message" =>
				"Lists the parameters accepted by an endpoint."
			);
	}

	public function getAcceptedParameters()
	{
		return array("type" => "parameters", "parameters" =>
			array(
				"/api/parameters/<endPoint>/" => "Lists the parameters accepted by an endpoint."
			)
		);
	}
	public function execute($parameters)
	{
		$endPoint = isset($parameters["parameters"]) ? $parameters["parameters"] : NULL;

		if(!$endPoint)
			return array(
				"type" => "error",
				"message" => "No endpoint selected"
			);

		try
		{
			$fileName = __DIR__ . "/$endPoint.php";
			if(!file_exists($fileName))
				throw new Exception();

			require_once $fileName;
			$className = "api_$endPoint";
			$class = new $className();

			if(!is_a($class, "apiEndpoint"))
			{
				$data = array(
					"type" => "error",
					"message" => "Endpoint does not implement apiEndpoint"
				);
			}

			$data = $class->getAcceptedParameters();
		}
		catch (Exception $e)
		{
			$data = array(
				"type" => "error",
				"message" => "$endpoint ended with error: " . $e->getMessage()
			);
		}

		return $data;
	}
}
