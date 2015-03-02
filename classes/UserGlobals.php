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

class UserGlobals extends Twig_Extension
{
	public function getName()
	{
		return "UserGlobals";
	}

	public function getGlobals()
	{
		$result = array();
		if (isset($_SESSION["loggedin"])) {
			$u = User::getUserInfo();

			$config = UserConfig::getAll();

			foreach($config as $key => $val) $this->addGlobal($result, $key, $val);

			$this->addGlobal($result, "sessionusername", $u["username"]);
			$this->addGlobal($result, "sessionuserid", $u["id"]);
			$this->addGlobal($result, "sessionadmin", (bool)$u["admin"]);
			$this->addGlobal($result, "sessionmoderator", (bool)$u["moderator"]);
    	}

		$this->addGlobal($result, "killsLastHour", Storage::retrieve("KillsLastHour", 0));
		return $result;
	}

	private function addGlobal(&$array, $key, $value, $defaultValue = null)
	{
		if ($value == null && $defaultValue == null) return;
		else if ($value == null) $array[$key] = $defaultValue;
		else $array[$key] = $value;
	}
}
