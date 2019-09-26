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
 * Data source for experiments cache
 *
 * @package   tool_abconfig
 * @author    Peter Burnett <peterburnett@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_abconfig;

class experiment_cache implements \cache_data_source {

    /** @var question_finder the singleton instance of this class. */
    protected static $experimentcache = null;

    public static function get_instance_for_cache(\cache_definition $definition) {
        if (is_null(self::$experimentcache)) {
            self::$experimentcache = new experiment_cache();
        }
        return self::$experimentcache;
    }

    /**
     * Returns an array of data for given key
     *
     * @param string $key the key to get the
     * @return mixed A data array of all data for key or false if key not found
     */
    public function load_for_cache($key) {
        global $DB;
        $data = array();

        // All experiments
        if ($key == 'allexperiment') {
            $records = $DB->get_records('tool_abconfig_experiment');
            foreach ($records as $record) {
                $data[$record->shortname] = self::experiment_data_array($record);
            }
            return $data;
        }

        return false;
    }

    /**
     * Returns an array of data for all given keys
     *
     * @param array $keys the keys of the datasets to be loaded
     * @return mixed A data array of all datasets
     */
    public function load_many_for_cache(array $keys) {
        // return array of all data items
        $data = array();
        foreach ($keys as $key) {
            $data[$key] = self::load_for_cache($key);
        }
        return $data;
    }

    /**
     * Constructs a formatted data array of an experiment and all conditions for experiment
     *
     * @param array $experimentrecord the experiment record to construct data array for
     * @return mixed A data array representing the experiment, or false if it can't be loaded.
     */
    private function experiment_data_array($experimentrecord) {
        global $DB;

        // Prepare array for storing at shortname
        $experimentdata = array (
            'name' => $experimentrecord->name,
            'shortname' => $experimentrecord->shortname,
            'scope' => $experimentrecord->scope,
            'enabled' => $experimentrecord->enabled,
            'adminenabled' => $experimentrecord->adminenabled,
        );

        // Get all the conditions for the experiment
        $records = $DB->get_records('tool_abconfig_condition', array('experiment' => $experimentrecord->id));
        $data = array();
        foreach ($records as $record) {
            $data[$record->condset] = self::condition_data_array($record);
        }

        // Append condition data onto the experiment array and return
        $experimentdata['conditions'] = $data;
        return $experimentdata;
    }

    /**
     * Constructs a formatted data array of a conditionset
     *
     * @param array $conditionrecord the experiment record to construct data array for
     * @return mixed A data array representing the condition, or false if it can't be loaded.
     */
    private function condition_data_array($conditionrecord) {
        $conditiondata = array (
            'condset' => $conditionrecord->condset,
            'experiment' => $conditionrecord->experiment,
            'ipwhitelist' => $conditionrecord->ipwhitelist,
            'commands' => $conditionrecord->commands,
            'value' => $conditionrecord->value
        );
        return $conditiondata;
    }
}
