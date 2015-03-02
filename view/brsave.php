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

$sID = $_GET["sID"];
$dttm = $_GET["dttm"];
$options = $_GET["options"];

$battleID = Db::queryField("select battleID from zz_battle_report where solarSystemID = :sID and dttm = :dttm and options = :options limit 1", "battleID", array(":sID" => $sID, ":dttm" => $dttm, ":options" => $options), 0);
if ($battleID === null) $battleID = Db::execute("insert into zz_battle_report (solarSystemID, dttm, options) values (:sID, :dttm, :options)", array(":sID" => $sID, ":dttm" => $dttm, ":options" => $options));
$battleID = Db::queryField("select battleID from zz_battle_report where solarSystemID = :sID and dttm = :dttm and options = :options limit 1", "battleID", array(":sID" => $sID, ":dttm" => $dttm, ":options" => $options), 0);

$app->redirect("/br/$battleID/", 302);
