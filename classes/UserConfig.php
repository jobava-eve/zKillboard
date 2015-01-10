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

class UserConfig
{
	private static $userConfig = null;

	/**
	 * @param integer|null $id
	 */
	private static function loadUserConfig($id)
	{
		if (self::$userConfig != null) return;
		self::$userConfig = array();
		$result = Db::query("select * from zz_users_config where id = :id", array(":id" => $id), 0);
		foreach ($result as $row) {
			self::$userConfig[$row["locker"]] = $row["content"];
		}
	}

	public static function get($key, $defaultValue = null)
	{
		if (!User::isLoggedIn()) return $defaultValue;
		$id = User::getUserID();
		self::loadUserConfig($id);

		$value = isset(self::$userConfig["$key"]) ? self::$userConfig["$key"] : null;
		if ($value === null) return $defaultValue;
		$value = json_decode($value, true);
		return $value;
	}

	public static function getAll()
	{
		if (!user::isLoggedIn()) return null;

		$id = User::getUserID();
		self::loadUserConfig($id);

		foreach(self::$userConfig as $key => $value)
			self::$userConfig[$key] = json_decode($value, true);

		return self::$userConfig;
	}

	public static function set($key, $value)
	{
		if (!User::isLoggedIn()) throw new Exception("User is not logged in.");
		$id = User::getUserID();
		self::$userConfig = null;

		if (is_null($value) || (is_string($value) && strlen(trim($value)) == 0)) {
			// Just remove the row and let the defaults take over
			return Db::execute("delete from zz_users_config where id = :id and locker = :key", array(":id" => $id, ":key" => $key));
		}

		$value = json_encode($value);
		return Db::execute("insert into zz_users_config (id, locker, content) values (:id, :key, :value)
                                on duplicate key update content = :value", array(":id" => $id, ":key" => $key, ":value" => $value));
	}
}
