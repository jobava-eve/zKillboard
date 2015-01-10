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

class cli_help implements cliCommand
{
	public function getDescription()
	{
		return "Returns the description of a command. Usage: |g|help <command>";
	}

	public function getAvailMethods()
	{
		return self::listCommands(); // Space seperated list
	}

	public function execute($parameters, $db)
	{
		if (sizeof($parameters) == 0 || $parameters[0] == "") CLI::out("Usage: |g|help <command>|n| To see a list of commands, use: |g|list", true);
		$command = $parameters[0];
		$base = __DIR__;
		$fileName = "$base/cli_$command.php";
		if(!file_exists($fileName)) CLI::out("|r|Error running $command|n|. Please use list to show all commands, or help <command> to see information on how to use the command", true);

		require_once $fileName;
		$className = "cli_$command";
		$class = new $className();
		if(!is_a($class, "cliCommand")) CLI::out("|r| Module $command does not implement interface cliCommand", true);
		$descr = $class->getDescription();

		CLI::out("|g|$command:|n| $descr");
	}

	private static function listCommands()
	{
		$commands = array();
		$dir = __DIR__;

		if($handle = opendir($dir))
		{
			while(false !== ($entry = readdir($handle)))
			{
				if($entry != "." && $entry != ".." && $entry != "base.php")
				{
					$s1 = explode("cli_", $entry);
					$s2 = explode(".php", $s1[1]);
					if(sizeof($s2) == 2)
					{
						require_once "$dir/$entry";
						$command = $s2[0];
						$className = "cli_$command";
						$class = new $className();;
						if(is_a($class, "cliCommand"))
						{
							$commands[] = $command;
						}
					}
				}
			}
			closedir($handle);
		}
		sort($commands);
		return implode(" ", $commands);
	}
}
