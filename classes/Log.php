<?php
/* zLibrary
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

class Log
{

	public function __construct()
	{
		trigger_error('The class "log" may only be invoked statically.', E_USER_ERROR);
	}

	public static function log($text)
	{
		global $logfile;
		if (!file_exists($logfile) && !is_writable(dirname($logfile)))
			return; // Can't create the file

		if (is_writable($logfile))
			error_log(date("Ymd H:i:s") . " $text \n", 3, $logfile);
	}

	/*
	   Mapped by Eggdrop to log into #esc
	 */
	public static function irc($text)
	{
		global $ircLogFile, $ircLogFrom;

		$from = isset($ircLogFrom) ? $ircLogFrom : "";

		if (!isset($ircLogFile) || $ircLogFile == "")
			return;

		$text = self::addIRCColors($text);
		if (!is_writable($ircLogFile) && !is_writable(dirname($ircLogFile)))
			return;

		error_log("\n${from}$text\n", 3, $ircLogFile);
	}


	public static function ircAdmin($text)
	{
		global $ircAdminLogFile, $ircLogFrom;

		$from = isset($ircLogFrom) ? $ircLogFrom : "";

		if (!isset($ircAdminLogFile) || $ircAdminLogFile == "")
			return;

		$text = self::addIRCColors($text);

		if (!is_writable($ircAdminLogFile) && !is_writable(dirname($ircAdminLogFile)))
			return; // Can't create the file

		error_log("\n${from}$text\n", 3, $ircAdminLogFile);
	}

	public static $colors = array(
		"|r|" => "\x0305", // red
		"|g|" => "\x0303", // green
		"|w|" => "\x0300", // white
		"|b|" => "\x0302", // blue
		"|blk|" => "\x0301", // black
		"|c|" => "\x0310", // cyan
		"|y|" => "\x0308", // yellow
		"|o|" => "\x0307", // orange
		"|n|" => "\x03", // reset
	);

	/**
	 * @param string $msg
	 * @return string
	**/
	public static function addIRCColors($msg)
	{
		foreach (self::$colors as $color => $value)
			$msg = str_replace($color, $value, $msg);

		return $msg;
	}

	/**
	 * @param string $msg
	 * @return string
	**/
	public static function stripIRCColors($msg)
	{
		foreach (self::$colors as $color => $value)
			$msg = str_replace($color, "", $msg);

		return $msg;
	}

}