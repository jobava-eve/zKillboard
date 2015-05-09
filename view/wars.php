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

$wars = array();
$wars[] = War::getNamedWars("Recent Active Wars by Kills", "select warID from zz_wars where timeStarted > date_sub(now(), interval 90 day) and timeFinished is null order by (agrShipsKilled + dfdShipsKilled) desc limit 10");
$wars[] = War::getNamedWars("Alltime Active Wars by Kills", "select warID from zz_wars where timeFinished is null order by (agrShipsKilled + dfdShipsKilled) desc limit 10");
$wars[] = War::getNamedWars("Recent Active Wars by ISK", "select warID from zz_wars where timeStarted > date_sub(now(), interval 90 day) and timeFinished is null order by (agrIskKilled + dfdIskKilled) desc limit 10");
$wars[] = War::getNamedWars("Alltime Active Wars by ISK", "select warID from zz_wars where timeFinished is null order by (agrIskKilled + dfdIskKilled) desc limit 10");
$wars[] = War::getNamedWars("Recent Closed Wars by Kills", "select warID from zz_wars where timeStarted > date_sub(now(), interval 90 day) and timeFinished is not null order by (agrShipsKilled + dfdShipsKilled) desc limit 10");
$wars[] = War::getNamedWars("Alltime Closed Wars by Kills", "select warID from zz_wars where timeFinished is not null order by (agrShipsKilled + dfdShipsKilled) desc limit 10");
$wars[] = War::getNamedWars("Recent Closed Wars by ISK", "select warID from zz_wars where timeStarted > date_sub(now(), interval 90 day) and timeFinished is not null order by (agrIskKilled + dfdIskKilled) desc limit 10");
$wars[] = War::getNamedWars("Alltime Closed Wars by ISK", "select warID from zz_wars where timeFinished is not null order by (agrIskKilled + dfdIskKilled) desc limit 10");

Info::addInfo($wars);

$app->render("wars.html", array("warTables" => $wars));
