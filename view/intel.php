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

$months = 3;

$data = array();
$data["titans"]["data"] = Db::query("select distinct characterID, count(distinct killID) kills, shipTypeID from zz_participants where dttm >= date_sub(now(), interval $months month) and isVictim = 0 and groupID = 30 group by characterID order by 2 desc");
$data["titans"]["title"] = "Titans";

$data["moms"]["data"] = Db::query("select distinct characterID, count(distinct killID) kills, shipTypeID from zz_participants where dttm >= date_sub(now(), interval $months month) and isVictim = 0 and groupID = 659 group by characterID order by 2 desc");
$data["moms"]["title"] = "Supercarriers";	

Info::addInfo($data);

$app->render("intel.html", array("data" => $data));
