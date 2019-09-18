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

    // ========================================EXPERIMENT FUNCTIONS================================================================

    public function add_experiment($name, $shortname, $scope) {
        global $DB;
        // Check whether experiment already exists, if not return false
        if ($this->experiment_exists($shortname)) {
            return false;
        } else {
            $DB->insert_record('tool_abconfig_experiment', array('name' => $name, 'shortname' => $shortname, 'scope' => $scope, 'enabled' => 0));
        }
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

    public function update_experiment($prevshortname, $name, $shortname, $scope, $enabled) {
        global $DB;
        // Check whether the experiment exists to be updated
        if (!$this->experiment_exists($prevshortname)) {
            return false;
        } else {
            // Get id of record
            $sqlexperiment = $DB->sql_compare_text($prevshortname, strlen($prevshortname));
            $record = $DB->get_record_sql('SELECT * FROM {tool_abconfig_experiment} WHERE shortname = ?', array($sqlexperiment));

            $DB->update_record('tool_abconfig_experiment', array('id' => $record->id, 'name' => $name, 'shortname' => $shortname, 'scope' => $scope, 'enabled' => $enabled));
        }
    }

    public function delete_experiment($shortname) {
        global $DB;
        // Check whether experiment exists to be deleted
        if (!$this->experiment_exists($shortname)) {
            return false;
        } else {
            $sqlexperiment = $DB->sql_compare_text($shortname, strlen($shortname));
            $DB->execute('DELETE FROM {tool_abconfig_experiment} WHERE shortname = ?', array($sqlexperiment));
        }
    }

    // ===============================================CONDITION FUNCTIONS==================================================

    public function condition_exists($eid, $condset) {
        global $DB;
        $condsetsql = $DB->sql_compare_text($condset, strlen($condset));
        $sql = 'SELECT * FROM {tool_abconfig_condition} WHERE experiment = ? AND condset = ?';
        return $DB->record_exists_sql($sql, array($eid, $condsetsql));
    }

    public function add_condition($eid, $condset, $iplist, $commands, $value) {
        global $DB;
        if ($this->condition_exists($eid, $condset)) {
            return false;
        } else {
            return $DB->insert_record('tool_abconfig_condition', array('experiment' => $eid, 'condset' => $condset, 'ipwhitelist' => $iplist,
                'commands' => $commands, 'value' => $value));
        }
    }

    public function update_condition($eid, $id, $prevcondset, $condset, $iplist, $commands, $value) {
        global $DB;
        if (!$this->condition_exists($eid, $prevcondset)) {
            return false;
        } else {
            return $DB->update_record('tool_abconfig_condition', array('id' => $id, 'experiment' => $eid, 'condset' => $condset, 'ipwhitelist' => $iplist,
            'commands' => $commands, 'value' => $value));
        }
    }

    public function delete_condition($eid, $condset) {
        global $DB;
        if (!$this->condition_exists($eid, $condset)) {
            return false;
        } else {
            $sqlcondition = $DB->sql_compare_text($condset, strlen($condset));
            $DB->execute('DELETE FROM {tool_abconfig_condition} WHERE experiment = ? AND condset = ?', array($eid, $sqlcondition));
        }
    }

    public function delete_all_conditions($eid) {
        global $DB;
        $DB->delete_records('tool_abconfig_condition', array('experiment' => $eid));
    }
}

