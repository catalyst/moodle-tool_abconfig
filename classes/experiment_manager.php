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

/**
 * Local Library class
 *
 * @package   tool_abconfig
 * @author    Peter Burnett <peterburnett@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_abconfig_experiment_manager {
    // Experiment functions.

    /** @var array Experiment js that needs to be rendered. */
    private static $renderjs = [];

    /**
     * Gets an experiment by id.
     * @param int $eid experiment id
     * @return mixed
     */
    public function get_experiment(int $eid) {
        global $DB;
        return $DB->get_record('tool_abconfig_experiment', ['id' => $eid]);
    }

    /**
     * Add an experiment
     * @param string $name
     * @param string $shortname
     * @param string $scope
     * @return bool|int
     */
    public function add_experiment($name, $shortname, $scope) {
        global $DB;

        // Check whether experiment already exists, if not return false.
        if ($this->experiment_exists($shortname)) {
            $return = false;
        } else {
            $return = $DB->insert_record('tool_abconfig_experiment', [
                'name' => $name,
                'shortname' => $shortname,
                'scope' => $scope,
                'enabled' => 0,
                'adminenabled' => 0,
                'numoffset' => rand(0, 99),
            ]);
        }
        self::invalidate_experiment_cache();
        return $return;
    }

    /**
     * Check if an experiment exists
     * @param string $shortname
     * @return bool
     */
    public function experiment_exists($shortname) {
        global $DB;
        $sql = 'SELECT *
                  FROM {tool_abconfig_experiment}
                 WHERE ' . $DB->sql_compare_text('shortname') . ' = ' . $DB->sql_compare_text(':shortname');
        $record = $DB->get_record_sql($sql, ['shortname' => $shortname]);
        return !empty($record);
    }

    /**
     * Update an experiment
     * @param string $prevshortname
     * @param string $name
     * @param string $shortname
     * @param string $scope
     * @param int $enabled
     * @param int $adminenabled
     * @param int $numoffset
     * @return bool
     */
    public function update_experiment($prevshortname, $name, $shortname, $scope, $enabled, $adminenabled, $numoffset) {
        global $DB;
        // Check whether the experiment exists to be updated.
        if (!$this->experiment_exists($prevshortname)) {
            $return = false;
        } else {
            // Get id of record.
            $sql = 'SELECT *
                      FROM {tool_abconfig_experiment}
                     WHERE ' . $DB->sql_compare_text('shortname') . ' = ' . $DB->sql_compare_text(':shortname');
            $record = $DB->get_record_sql($sql, ['shortname' => $prevshortname]);
            $return = $DB->update_record('tool_abconfig_experiment', (object) [
                'id' => $record->id,
                'name' => $name,
                'shortname' => $shortname,
                'scope' => $scope,
                'enabled' => $enabled,
                'adminenabled' => $adminenabled,
                'numoffset' => $numoffset,
            ]);
        }
        self::invalidate_experiment_cache();
        return $return;
    }

    /**
     * Delete an experiment
     * @param string $shortname
     * @return bool
     */
    public function delete_experiment($shortname) {
        global $DB;

        // Check whether experiment exists to be deleted.
        if (!$this->experiment_exists($shortname)) {
            $return = false;
        } else {
            $sql = 'DELETE FROM {tool_abconfig_experiment}
                     WHERE ' . $DB->sql_compare_text('shortname') . ' = ' . $DB->sql_compare_text(':shortname');
            $return = $DB->execute($sql, ['shortname' => $shortname]);
        }
        self::invalidate_experiment_cache();
        return $return;
    }

    // Condition functions.

    /**
     * Check condition exists
     * @param int $eid
     * @param string $condset
     * @return bool
     */
    public function condition_exists($eid, $condset) {
        global $DB;
        $sql = 'SELECT *
                  FROM {tool_abconfig_condition}
                 WHERE experiment = :experiment
                   AND ' . $DB->sql_compare_text('condset') . ' = ' . $DB->sql_compare_text(':condset');
        return $DB->record_exists_sql($sql, [
            'experiment' => $eid,
            'condset' => $condset,
        ]);
    }

    /**
     * Add a condition
     * @param int $eid
     * @param string $condset
     * @param string $iplist
     * @param string $commands
     * @param int $value
     * @param array $users
     * @return bool|int
     */
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

    /**
     * Update a condition
     * @param int $eid
     * @param int $id
     * @param string $prevcondset
     * @param string $condset
     * @param string $iplist
     * @param string $commands
     * @param int $value
     * @param array $users
     * @return bool
     */
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

    /**
     * Delete a condition
     * @param int $eid
     * @param string $condset
     * @return bool
     */
    public function delete_condition($eid, $condset) {
        global $DB;
        if (!$this->condition_exists($eid, $condset)) {
            $return = false;
        } else {
            $sql = 'DELETE FROM {tool_abconfig_condition}
                     WHERE experiment = :experiment
                       AND ' . $DB->sql_compare_text('condset') . ' = ' . $DB->sql_compare_text(':condset');
            $return = $DB->execute($sql, [
                'experiment' => $eid,
                'condset' => $condset,
            ]);
        }
        self::invalidate_experiment_cache();
        return $return;
    }

    /**
     * Delete all conditions
     * @param int $eid
     * @return void
     */
    public function delete_all_conditions($eid) {
        global $DB;
        $DB->delete_records('tool_abconfig_condition', ['experiment' => $eid]);
        self::invalidate_experiment_cache();
    }

    /**
     * Get experiment conditions
     * @param int $eid
     * @return array
     */
    public function get_conditions_for_experiment($eid) {
        global $DB;
        return $DB->get_records('tool_abconfig_condition', ['experiment' => $eid], 'condset ASC');
    }

    // Caching functions.

    /**
     * Invalidate the experiment cache
     * @return void
     */
    private function invalidate_experiment_cache() {
        \cache_helper::invalidate_by_definition('tool_abconfig', 'experiments', [], ['allexperiment']);
    }

    /**
     * Get experiments
     * @return mixed
     */
    public function get_experiments() {
        $cache = cache::make('tool_abconfig', 'experiments');
        $experiments = $cache->get('allexperiment');
        // Return empty array if cache->get fails.
        return ($experiments != false) ? $experiments : [];
    }

    /**
     * Get all experiments that should be run before session
     * @return mixed
     */
    public function get_before_session_experiments() {
        $experiments = self::get_experiments();

        // Filter array for all before session experiments.
        return array_filter($experiments, function ($experiment) {
            if ($experiment['scope'] == 'device') {
                return true;
            } else {
                return false;
            }
        });
    }

    /**
     * Get all experiments that should be run after config
     * @return mixed
     */
    public function get_after_config_experiments() {
        $experiments = self::get_experiments();

        // Filter array for all after config experiments.
        return array_filter($experiments, function ($experiment) {
            if (in_array($experiment['scope'], ['request', 'session'])) {
                return true;
            } else {
                return false;
            }
        });
    }

    /**
     * Get active requests
     * @return mixed
     */
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

    /**
     * Get the active session
     * @return mixed
     */
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

    /**
     * Get active device experiments
     * @return mixed
     */
    public function get_active_device() {
        $experiments = self::get_experiments();

        // Filter array for only enabled device experiments.
        return array_filter($experiments, function ($experiment) {
            if ($experiment['enabled'] == 1 && $experiment['scope'] == 'device') {
                return true;
            } else {
                return false;
            }
        });
    }

    /**
     * Get active experiments
     * @return mixed
     */
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

    /**
     * Sets experiment JS to be rendered.
     * @param string $key
     * @param string $value
     * @return void
     */
    public function set_render_js(string $key, string $value): void {
        // If there is existing render js queued up, that should take priority.
        if (!isset(self::$renderjs[$key])) {
            self::$renderjs[$key] = $value;
        }
    }

    /**
     * Gets experiment JS to be rendered.
     * @return array
     */
    public function get_render_js(): array {
        return self::$renderjs;
    }

    /**
     * Unsets experiment JS.
     * @param string $key
     * @return void
     */
    public function remove_render_js(string $key): void {
        unset(self::$renderjs[$key]);
    }

    /**
     * Log commands
     * @param string $commands
     * @param int $value
     * @return void
     */
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
                    $plugin = 'core-experiment:' . $value;
                    $name = $command[1];
                    $setting = $command[2];
                    add_to_config_log($name, '', $setting, $plugin);
                    break;

                case 'forced_plugin_setting':
                    $plugin = $command[1] . '-experiment:' . $value;
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
}
