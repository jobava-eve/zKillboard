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

// Ensure PHP 5.4 or higher
if (version_compare(phpversion(), '5.4.1', '<'))
{
	die("PHP 5.4 or higher is required");
}

// config load
require_once( "config.php" );

if($debug)
{
	ini_set('display_errors', 1);
	error_reporting(E_ALL);
}

// vendor autoload
require( "vendor/autoload.php" );

// zkb class autoloader
spl_autoload_register("zkbautoload");

function zkbautoload($class_name)
{
	$baseDir = dirname(__FILE__);
	$fileName = "$baseDir/classes/$class_name.php";
	if (file_exists($fileName))
	{
		require_once $fileName;
		return;
	}
}
