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

class cli_apiErrorList implements cliCommand
{
	public function getDescription()
	{
		return "Gets a list of errorcodes and error texts associated with the CCP API. |g|Usage: apiErrorList";
	}

	public function getAvailMethods()
	{
		return ""; // Space seperated list
	}

	public function execute($parameters, $db)
	{
		try {
			$pheal = Util::getPheal();
			$pheal->scope = "eve";
			$errors = $pheal->ErrorList();
			foreach($errors->errors as $error) {
				$errorCode = $error["errorCode"];
				$errorText = $error["errorText"];
				$key = "api_error:$errorCode";
				$db->execute("replace into zz_storage (locker, contents) values (:c, :t)", array(":c" => $key, ":t" => $errorText));
			}
		} catch (Exception $ex) {
			print_r($ex);
		}
	}
}
