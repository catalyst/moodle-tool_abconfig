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
namespace tool_abconfig;

defined('MOODLE_INTERNAL') || die();
class experiment_manager {

    // ========================================EXPERIMENT FUNCTIONS================================================================

    public function add_experiment($name, $shortname, $scope) {
        global $DB;
        // Check whether experiment already exists, if not return false
        if ($this->experiment_exists($shortname)) {
            return false;
        } else {
            $DB->insert_record('tool_abconfig_experiment', array('name' => $name, 'shortname' => $shortname, $scope => 'scope'));
        }
    }

    public function experiment_exists($shortname){ 
        global $DB;
        return $DB->record_exists('tool_abconfig_experiment', array('shortname' => $shortname));
    }

    public function update_experiment($name, $shortname, $scope) {
        //Check whether the experiment exists to be updated
        if (!$this->experiment_exists($shortname)) {
            return false;
        } else {
            $DB->update_record('tool_abconfig_experiment', array('name' => $name, 'shortname' => $shortname, 'scope' => $scope));
        }
    }

    public function delete_experiment($shortname) {
        global $DB;
        // Check whether experiment exists to be deleted
        if (!$this->experiment_exists($shortname)) {
            return false;
        } else {
            $DB->delete_records('tool_abconfig_experiment', array('shortname' => $shortname));
        }
    }

    // ===============================================CONDITION FUNCTIONS==================================================

    public function condition_exists($eid, $condset) {
        global $DB;
        return $DB->record_exists('tool_abconfig_condition', array('experiment' => $eid, 'condset' => $condset));
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

    public function update_condition($eid, $condset, $iplist, $commands, $value) {
        global $DB;
        if (!$this->condition_exists($eid, $condset)) {
            return false;
        } else {
            return $DB->update_record('tool_abconfig_condition', array('experiment' => $eid, 'condset' => $condset, 'ipwhitelist' => $iplist,
            'commands' => $commands, 'value' => $value));
        }
    }

    public function delete_condition($eid, $condset) {
        global $DB;
        if (!$this->condition_exists($eid, $condset)) {
            return false;
        } else {
            return $DB->delete_records('tool_abconfig_condition', array('experiment' => $eid, 'condset' => $condset));
        } 
    }

}

