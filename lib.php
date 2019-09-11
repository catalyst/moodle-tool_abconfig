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
 * AB testing admin tool
 *
 * @package    tool_abconfig
 * @copyright  2019 Brendan Heywood <brendan@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function tool_abconfig_after_config() {

    // Initial Checks
    // Make admin immune
    if (is_siteadmin()) {
        //return null;
    }

    global $CFG, $DB;

    // Every experiment that is per request
    $compare = $DB->sql_compare_text('request', strlen('request'));
    $records = $DB->get_records_sql("SELECT * FROM {tool_abconfig_experiment} WHERE scope = ? AND enabled=1", array($compare));

    foreach ($records as $record) {
        // get condition sets for experiment
        $conditionrecords = $DB->get_records('tool_abconfig_condition', array('experiment' => $record->id));

        // Remove all conditions that contain the user ip in the whitelist
        $crecords = array();

        foreach ($conditionrecords as $conditionrecord) {
            $iplist = implode(PHP_EOL, json_decode($conditionrecord->ipwhitelist));
            if (!remoteip_in_list($iplist)) {
                array_push($crecords, $conditionrecord);
            }
        }

        // Increment through conditions until one is selected
        $condition = '';
        $num = rand(1, 100);
        $prevtotal = 0;
        foreach ($crecords as $crecord) {
            // If random number is within this range, set condition and break, else increment total
            if ($num > $prevtotal && $num <= ($prevtotal + $crecord->value)) {

                $commands = json_decode($crecord->commands);
                foreach ($commands as $command) {
                    // Evaluate the command to figure the type out
                    $commandarray = explode(',', $command);
                    // Protection form malformed commands
                    if (count($commandarray) != 3) {
                        return null;
                    }

                    if ($commandarray[0] == 'CFG') {
                        $CFG->{$commandarray[1]} = $commandarray[2];
                    } else {
                        $CFG->forced_plugin_settings[$commandarray[0]][$commandarray[1]] = $commandarray[2];
                    }
                }
                // Do not execute any more conditions
                break;
            } else {
                // Not this record, increment lower bound, and move on
                $prevtotal += $crecord->value;
            }
        }
    }
    /*# example of temp override
    $CFG->enableglobalsearch = 1;
    // example of what *looks* like a forced override
    $CFG->config_php_settings['enableglobalsearch'] = 1;
    # forced override of plugin
    $CFG->forced_plugin_settings['auth_saml2']['debug'] = 1;*/
}

function tool_abconfig_after_require_login() {
    global $CFG, $DB;
    $compare = $DB->sql_compare_text('session', strlen('session'));
    $records = $DB->get_records_sql("SELECT * FROM {tool_abconfig_experiment} WHERE scope = ? AND enabled=1", array($compare));

    foreach ($records as $record) {
        // get condition sets for experiment
        $conditionrecords = $DB->get_records('tool_abconfig_condition', array('experiment' => $record->id));

        // Remove all conditions that contain the user ip in the whitelist
        $crecords = array();

        foreach ($conditionrecords as $conditionrecord) {
            $iplist = implode(PHP_EOL, json_decode($conditionrecord->ipwhitelist));
            if (!remoteip_in_list($iplist)) {
                array_push($crecords, $conditionrecord);
            }
        }

        // Increment through conditions until one is selected
        $condition = '';
        $num = rand(1, 100);
        $prevtotal = 0;
        foreach ($crecords as $crecord) {
            // If random number is within this range, set condition and break, else increment total
            if ($num > $prevtotal && $num <= ($prevtotal + $crecord->value)) {
                // TEMP PHP EVAL TO TEST WHETHER INTERACTION IS WORKING
                $commands = json_decode($crecord->commands);
                foreach ($commands as $command) {
                    eval($command);
                }
            } else {
                // Not this record, increment lower bound, and move on
                $prevtotal += $crecord->value;
            }
        }
    }
}

