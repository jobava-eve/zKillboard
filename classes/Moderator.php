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
 * Various Moderator Actions
 *
 */
class Moderator
{
	/**
	 * Gets the User info
	 *
	 * @static
	 * @param $userID the userid of the user to query
	 * @return The array with the userinfo in it
	 */
	public static function getUserInfo($userID){
		if (!User::isModerator() and !User::isAdmin()) throw new Exception("Invalid Access!");
		$info = Db::query("SELECT * FROM zz_users WHERE id = :id", array(":id" => $userID),0); // should this be star
		return $info;
	}

	public static function getUsers($page){
		if (!User::isModerator() and !User::isAdmin()) throw new Exception("Invalid Access!");
		$limit = 30;
		$offset = ($page - 1) * $limit;
		$users = Db::query("SELECT * FROM zz_users ORDER BY id LIMIT $offset, $limit", array(), 0);
		return $users;
	}

}
