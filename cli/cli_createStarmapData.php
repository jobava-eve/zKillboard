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

class cli_createStarmapData implements cliCommand
{
    /**
     * The zKillboard base path
     * @var string
     */
    private $base;

    /**
     * The zKillboard base path
     * @var string
     */
    private $meta_names = array('tech1', 'tech1', 'tech1', 'tech1', 'tech1', 'tech2', 'storyline', 'navy', 'faction', 'unique');

    /**
     * Return the help / description for this command
     * @return string
     */
    public function getDescription() {
        return 'Creates the assests needed for the starmap. Types available: |g|ships, systems, all|n|. Usage: |g|createStarmapData <type>';
    }

    /**
     * Returns a list of the available methods available
     * @return string
     */
    public function getAvailMethods() {
        return 'ships systems all';
    }

    /**
     * Runs the command with the passed methods
     * @param  array $parameters The paramater passed to the command
     * @return void
     */
    public function execute($parameters, $db) {
        global $base;

        //set the base path (this really should be injected)
        $this->base = $base;

        //execute the desired method
        if (is_array($parameters)) {
            switch(@$parameters[0]) {
                case 'ships':
                    $this->generateShipDataFile($db);
                break;

                case 'systems':
                    $this->generateSystemDataFile($db);
                break;

                case 'all':
                    $this->generateShipDataFile($db);
                    $this->generateSystemDataFile($db);
                break;

                default:
                    CLI::out('Please use |g|ships, systems or all|n| with this command', true);
                break;
            }
        }
    }

    /**
     * Creates the JSON file for the map or the known universe
     * @param string $file_name
     * @return void
     */
    public function writeStaticData($file_name, $data_array) {
        global $theme;
        $file_name = $this->base . '/themes/'. $theme .'/js/' . $file_name . '.json';

        CLI::out('Creating JSON string');
        $json = json_encode($data_array, JSON_NUMERIC_CHECK);

        if (file_exists($file_name)) {
            CLI::out('Unlinking old file');
            unlink($file_name);
        }

        CLI::out('Writing new file');
        file_put_contents($file_name, $json);

        CLI::out('Done');
    }

    /**
     * Creates a json file of systems to be used on the star map page
     * @return void
     */
    public function generateSystemDataFile($db) {
        CLI::out('|g|Loading ships|n|');

        //get a list of all non-wormhole systems
        $systems_result = $db->query('SELECT solarSystemID, solarSystemName, security, x, y, z FROM ccp_systems WHERE regionID < 11000000');
        $systems = array();

        foreach($systems_result as $system) {
            $systems[$system['solarSystemID']] = array('name' => $system['solarSystemName'], 'sec' => (($system['security'] < 0) ? 0 : (round($system['security'] , 1) * 10)), 'x' => $system['x'], 'y' => $system['y'], 'z' => ($system['z'] * -1));
        }

        //create the new file asset
        $this->writeStaticData('starmap-systems', $systems);
    }

    /**
     * Creates a json file of ships to be used on the star map page
     * @return [type] [description]
     */
    public function generateShipDataFile($db) {
        CLI::out('|g|Loading ships|n|');

        //get a list of all the published ships
        $ships_results = $db->query('SELECT ccp_invTypes.typeID, typeName, groupName, COALESCE(valueInt, valueFloat) AS metaLevel FROM ccp_invTypes INNER JOIN ccp_invGroups ON ccp_invGroups.groupID = ccp_invTypes.groupID INNER JOIN ccp_dgmTypeAttributes ON ccp_dgmTypeAttributes.typeID = ccp_invTypes.typeID AND ccp_dgmTypeAttributes.attributeID = 633 WHERE (categoryID IN (6, 23, 40) AND ccp_invTypes.published = 1 AND ccp_invGroups.published = 1 OR ccp_invTypes.groupID = 29)');
        $ships = array();

        foreach($ships_results as $ship) {
            $ships[$ship['typeID']] = array('name' => $ship['typeName'], 'group' => strtolower(str_replace(' ', '-', $ship['groupName'])), 'meta' => $this->meta_names[$ship['metaLevel']]);
        }

        //create the new file asset
        $this->writeStaticData('starmap-ships', $ships);
    }
}
