<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Local Library
 *
 * @package   tool_abconfig
 * @author    Peter Burnett <peterburnett@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
class tool_abconfig_experiment_manager {

    // Experiment functions.

    public function add_experiment($name, $shortname, $scope) {
        global $DB;

        // Check whether experiment already exists, if not return false.
        if ($this->experiment_exists($shortname)) {
            $return = false;
        } else {
            $return = $DB->insert_record('tool_abconfig_experiment',
                array('name' => $name, 'shortname' => $shortname, 'scope' => $scope, 'enabled' => 0, 'adminenabled' => 0));
        }
        self::invalidate_experiment_cache();
        return $return;
    }

    public function experiment_exists($shortname) {
        global $DB;
        $sqlexperiment = $DB->sql_compare_text($shortname, strlen($shortname));
        $record = $DB->get_record_sql('SELECT * FROM {tool_abconfig_experiment} WHERE shortname = ?', array($sqlexperiment));
        if (empty($record)) {
            return false;
        } else {
            return true;
        }
    }

    public function update_experiment($prevshortname, $name, $shortname, $scope, $enabled, $adminenabled) {
        global $DB;
        // Check whether the experiment exists to be updated.
        if (!$this->experiment_exists($prevshortname)) {
            $return = false;
        } else {
            // Get id of record.
            $sqlexperiment = $DB->sql_compare_text($prevshortname, strlen($prevshortname));
            $record = $DB->get_record_sql('SELECT * FROM {tool_abconfig_experiment} WHERE shortname = ?', array($sqlexperiment));

            $return = $DB->update_record('tool_abconfig_experiment', array('id' => $record->id, 'name' => $name,
                'shortname' => $shortname, 'scope' => $scope, 'enabled' => $enabled, 'adminenabled' => $adminenabled));
        }
        self::invalidate_experiment_cache();
        return $return;
    }

    public function delete_experiment($shortname) {
        global $DB;

        // Check whether experiment exists to be deleted.
        if (!$this->experiment_exists($shortname)) {
            $return = false;
        } else {
            $sqlexperiment = $DB->sql_compare_text($shortname, strlen($shortname));
            $return = $DB->execute('DELETE FROM {tool_abconfig_experiment} WHERE shortname = ?', array($sqlexperiment));
        }
        self::invalidate_experiment_cache();
        return $return;
    }

    // Condition functions.

    public function condition_exists($eid, $condset) {
        global $DB;
        $condsetsql = $DB->sql_compare_text($condset, strlen($condset));
        $sql = 'SELECT * FROM {tool_abconfig_condition} WHERE experiment = ? AND condset = ?';
        return $DB->record_exists_sql($sql, array($eid, $condsetsql));
    }

    public function add_condition($eid, $condset, $iplist, $commands, $value, $users) {
        global $DB;
        if ($this->condition_exists($eid, $condset)) {
            $return = false;
        } else {
            $commands = $this->json_string($commands);
            $users = json_encode($users);
            $record = (object) [
                'experiment' => $eid,
                'condset' => $condset,
                'ipwhitelist' => $iplist,
                'commands' => $commands,
                'value' => $value,
                'users' => $users,
            ];
            $return = $DB->insert_record('tool_abconfig_condition', $record);

            $this->log_commands($commands, $value);
        }
        self::invalidate_experiment_cache();
        return $return;
    }

    public function update_condition($eid, $id, $prevcondset, $condset, $iplist, $commands, $value, $users) {
        global $DB;

        if (!$this->condition_exists($eid, $prevcondset)) {
            $return = false;
        } else {
            $commands = $this->json_string($commands);
            $users = json_encode($users);
            $record = (object) [
                'id' => $id,
                'experiment' => $eid,
                'condset' => $condset,
                'ipwhitelist' => $iplist,
                'commands' => $commands,
                'value' => $value,
                'users' => $users,
            ];
            $return = $DB->update_record('tool_abconfig_condition', $record);

            $this->log_commands($commands, $value);
        }
        self::invalidate_experiment_cache();
        return $return;
    }

    public function delete_condition($eid, $condset) {
        global $DB;
        if (!$this->condition_exists($eid, $condset)) {
            $return = false;
        } else {
            $sqlcondition = $DB->sql_compare_text($condset, strlen($condset));
            $return = $DB->execute('DELETE FROM {tool_abconfig_condition} WHERE experiment = ? AND condset = ?',
                array($eid, $sqlcondition));
        }
        self::invalidate_experiment_cache();
        return $return;
    }

    public function delete_all_conditions($eid) {
        global $DB;
        $DB->delete_records('tool_abconfig_condition', array('experiment' => $eid));
        self::invalidate_experiment_cache();
    }

    public function get_conditions_for_experiment($eid) {
        global $DB;
        return $DB->get_records('tool_abconfig_condition', array('experiment' => $eid), 'condset ASC');
    }

    // Caching functions.
    private function invalidate_experiment_cache() {
        \cache_helper::invalidate_by_definition('tool_abconfig', 'experiments', array(), array('allexperiment'));
    }

    public function get_experiments() {
        $cache = cache::make('tool_abconfig', 'experiments');
        $experiments = $cache->get('allexperiment');
        // Return empty array if cache->get fails.
        return ($experiments != false) ? $experiments : array();
    }

    public function get_active_request() {
        $experiments = self::get_experiments();

        // Filter array for only enabled session experiments.
        return array_filter($experiments, function ($experiment) {
            if ($experiment['enabled'] == 1 && $experiment['scope'] == 'request') {
                return true;
            } else {
                return false;
            }
        });
    }

    public function get_active_session() {
        $experiments = self::get_experiments();

        // Filter array for only enabled session experiments.
        return array_filter($experiments, function ($experiment) {
            if ($experiment['enabled'] == 1 && $experiment['scope'] == 'session') {
                return true;
            } else {
                return false;
            }
        });
    }

    public function get_active_experiments() {
        $experiments = self::get_experiments();

        // Filter array for only enabled experiments.
        return array_filter($experiments, function ($experiment) {
            if ($experiment['enabled'] == 1) {
                return true;
            } else {
                return false;
            }
        });
    }

    private function log_commands($commands, $value) {
        // Unpack commands, and log if they are CFG or plugin config.
        $commands = json_decode($commands);
        if (empty($commands)) {
            return;
        }
        foreach ($commands as $commandstring) {
            $command = explode(',', $commandstring);
            switch ($command[0]) {
                case 'CFG':
                    $plugin = 'core-experiment:'.$value;
                    $name = $command[1];
                    $setting = $command[2];
                    add_to_config_log($name, '', $setting, $plugin);
                    break;

                case 'forced_plugin_setting':
                    $plugin = $command[1].'-experiment:'.$value;
                    $name = $command[2];
                    $setting = $command[3];
                    add_to_config_log($name, '', $setting, $plugin);
                    break;

                default:
                    break;
            }
        }
    }

    /**
     * Trims and formats string into a JSON string.
     *
     * @param string $strings Original string to be converted into JSON string.
     * @return string JSON formatted string.
     */
    private function json_string($strings) {
        if (!empty($strings)) {
            $array = explode(PHP_EOL, $strings);
            $arraytrimmed = array_map('trim', $array);
            $strings = json_encode($arraytrimmed);
        }
        return $strings;
    }

    /**
     * Either condition is met or not based on current user and their IP.
     *
     * @param array $conditionrecord Condition record
     * @return bool
     */
    public function is_condition_met(array $conditionrecord) {
        global $USER;

        $users = json_decode($conditionrecord['users']);
        $blacklist = $conditionrecord['ipwhitelist'];

        if (empty($users) || in_array($USER->id, $users)) {
            $blacklisted = remoteip_in_list($blacklist);

            // We can't check IP via CLI so CLI should just work if other conditions are met. However, calls
            // from unit tests should be treated as web calls to make it possible to test various web scenarios.
            $cli = defined('CLI_SCRIPT') && CLI_SCRIPT;
            $phpunit = defined('PHPUNIT_TEST') && PHPUNIT_TEST;

            if (!$blacklisted || ($cli && !$phpunit)) {
                return true;
            }
        }

        return false;
    }
}
