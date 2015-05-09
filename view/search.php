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

$entities = array();

if($_POST)
	$app->redirect("/search/".urlencode($_POST["searchbox"])."/");

if($search)
{
	$entities = Info::findEntity($search);

	// if there is only one result, we redirect.
	if(count($entities) == 1) {
		$type = $entities[0]["type"];
		$values = array_values($entities[0]);
		$id = $values[0];
		$app->redirect("/$type/$id/");
	}
}

$app->render("search.html", array("data" => $entities));
