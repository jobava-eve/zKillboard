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

$data = array();

$data["map"] = array(
	"Realtime Map" => "https://map.eve-kill.net",
	);

$data["kills"] = array(
	"All Kills" => "",
	"Big Kills" => "bigkills",
	"Awox" => "awox",
	"W-space" => "w-space",
	"Solo" => "solo",
	"5b+" => "5b",
	"10b+" => "10b",
	"Capitals" => "capitals",
	"Freighters" => "freighters",
	"Supers" => "supers",
	"Dust - All Kills" => "dust",
	"Dust - Vehicles" => "dust_vehicles",
	);

$data["intel"] = array(
	"Supers" => "supers",
	);

$data["top"] = array(
	"Last Hour" => "lasthour",
	);

$data["ranks"] = array(
	"Recent Kills" => "recent/killers",
	"Recent Losers" => "recent/losers",
	"Alltime Killers" => "alltime/killers",
	"Alltime Losers" => "alltime/losers",
	);

$data["post"] = array(
	"Post Kills" => "",
	);

$data["support"] = array(
	"Tickets" => "/tickets/",
	"Live Chat" => "/livechat",
	);

$data["information"] = array(
	"About" => "about",
	"Killmails" => "killmails",
	"Legal" => "legal",
	"Payments" => "payments",
	"API" => "https://neweden-dev.com/ZKillboard_API",
	);

$app->render("sitemap.html", array("data" => $data));
