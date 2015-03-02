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
class Password
{
	public static function genPassword($password)
	{
		return password_hash($password, PASSWORD_BCRYPT);
	}

	public static function updatePassword($password)
	{
		$userID = user::getUserID();
		$password = self::genPassword($password);
		Db::execute("UPDATE zz_users SET password = :password WHERE id = :userID", array(":password" => $password, ":userID" => $userID));
		return "Updated password";
	}

	/**
	 * @param string $plainTextPassword
	 */
	public static function checkPassword($plainTextPassword, $storedPassword = NULL)
	{
		if($plainTextPassword && $storedPassword)
			return self::pwCheck($plainTextPassword, $storedPassword);
		else
		{
			$userID = user::getUserID();
			if($userID)
			{
				$storedPw = Db::queryField("SELECT password FROM zz_users WHERE id = :userID", "password", array(":userID" => $userID), 0);
				return self::pwCheck($plainTextPassword, $storedPw);
			}
		}
	}

	private static function pwCheck($plainTextPassword, $storedPassword)
	{
		if (!password_verify($plainTextPassword, $storedPassword))
			return false;
		return true;
	}
}