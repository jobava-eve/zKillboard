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

class cli_updateAlliances implements cliCommand
{
	public function getDescription()
	{
		return "Updates the alliance information pages";
	}

	public function getAvailMethods()
	{
		return "";
	}

	public function getCronInfo()
	{
		return array(0 => "");
	}

	public function execute($parameters, $db)
    {
        $timer = new Timer();
        while ($timer->stop() < 59000) {
            $alliances = $db->query("SELECT * FROM zz_alliances LIMIT 1");

            foreach ($alliances as $alliance) {
                $allianceID = $alliance["allianceID"];

                // Crest URL: http://public-crest.eveonline.com/alliances/99000006/
                $data = Util::getCrest("http://public-crest.eveonline.com/alliances/{$allianceID}/");
                $info = array();

                $info["startDate"] = $data->startDate;
                $info["corporationsCount"] = $data->corporationsCount;
                $info["description"] = $data->description;
                $info["url"] = $data->url;

                $db->query("UPDATE zz_alliances SET information = :information", array(":information" => $info));
            }
        }
    }
}
