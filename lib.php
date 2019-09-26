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
    global $CFG, $DB, $SESSION;

    // Create cache
    $cache = cache::make('tool_abconfig', 'experiments');

    // Check if the param to disable ABconfig is present, if so, exit
    if (array_key_exists('abconfig', $_GET) && $_GET['abconfig'] == 'off') {
        if (is_siteadmin()) {
            return null;
        }
    }

    // Check URL params, and fire any experiments in the params
    foreach ($_GET as $experiment => $condition) {

        // Get all experiments from cache
        $experiments = $cache->get('allexperiment');

        // Check if experiment exists
        if (array_key_exists($experiment, $experiments)) {
            // If so, check if condition exists
            if (array_key_exists($condition, $experiments[$experiment]['conditions'])) {
                // execute commands stored at experiment->condition->commands
                tool_abconfig_execute_command_array($experiments[$experiment]['conditions'][$condition]['commands'], $experiments[$experiment]['shortname']);
            }
        }
    }

    $commandarray = array();

    // First, Build a list of all commands that need to be executed
    // Every experiment that is per request
    $compare = $DB->sql_compare_text('request', strlen('request'));
    $records = $DB->get_records_sql("SELECT * FROM {tool_abconfig_experiment} WHERE scope = ? AND enabled=1", array($compare));

    foreach ($records as $record) {

        // Make admin immune unless enabled for admin
        if (is_siteadmin()) {
            if ($record->adminenabled == 0) {
                continue;
            }
        }

        $conditionrecords = $DB->get_records('tool_abconfig_condition', array('experiment' => $record->id));

        // Remove all conditions that contain the user ip in the whitelist
        $crecords = array();

        foreach ($conditionrecords as $conditionrecord) {
            $iplist = $conditionrecord->ipwhitelist;
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
                $commandarray[$record->shortname] = $crecord->commands;

                // Do not select any more conditions
                break;
            } else {
                // Not this record, increment lower bound, and move on
                $prevtotal += $crecord->value;
            }
        }
    }

    // Now we must check for session level requests, that require the config to be the same, but applied every request
    $sessioncompare = $DB->sql_compare_text('session', strlen('session'));
    $sessionrecords = $DB->get_records_sql("SELECT * FROM {tool_abconfig_experiment} WHERE scope = ? AND enabled=1", array($sessioncompare));

    foreach ($sessionrecords as $record) {
        // Check if a session var has been set for this experiment, only care if has been set
        $unique = 'abconfig_'.$record->shortname;
        if (property_exists($SESSION, $unique) && $SESSION->$unique != '') {
            // If set, execute commands
            $sqlcondition = $DB->sql_compare_text($SESSION->$unique, strlen($SESSION->$unique));
            $condition = $DB->get_record_select('tool_abconfig_condition', 'experiment = ? AND condset = ?', array($record->id, $SESSION->$unique));
            $commands = json_decode($condition->commands);

            $commandarray[$record->shortname] = $condition->commands;
        }
    }

    // Now, execute all commands in the arrays
    foreach ($commandarray as $shortname => $command) {
        tool_abconfig_execute_command_array($command, $shortname);
    }
}

function tool_abconfig_after_require_login() {

    // Check if the param to disable ABconfig is present, if so, exit
    if (array_key_exists('abconfig', $_GET) && $_GET['abconfig'] == 'off') {
        if (is_siteadmin()) {
            return null;
        }
    }

    global $CFG, $DB, $SESSION;
    $compare = $DB->sql_compare_text('session', strlen('session'));
    try {
        $records = $DB->get_records_sql("SELECT * FROM {tool_abconfig_experiment} WHERE scope = ? AND enabled=1", array($compare));
    } catch (Exception $e) {
        // Always fail cleanly and don't block a working moodle.
        return;
    }

    foreach ($records as $record) {
        // Make admin immune unless enabled for admin
        if (is_siteadmin()) {
            if ($record->adminenabled == 0) {
                continue;
            }
        }

        // Create experiment session var identifier
        $unique = 'abconfig_'.$record->shortname;
        // get condition sets for experiment
        $conditionrecords = $DB->get_records('tool_abconfig_condition', array('experiment' => $record->id));
        // Remove all conditions that contain the user ip in the whitelist
        $crecords = array();

        foreach ($conditionrecords as $conditionrecord) {
            $iplist = $conditionrecord->ipwhitelist;
            if (!remoteip_in_list($iplist)) {
                array_push($crecords, $conditionrecord);
            }
        }

        // If condition set hasnt been selected, select a condition set, or none
        if (!property_exists($SESSION, $unique)) {
            // Increment through conditions until one is selected
            $condition = '';
            $num = rand(1, 100);
            $prevtotal = 0;
            foreach ($crecords as $crecord) {
                // If random number is within this range, set condition and break, else increment total
                if ($num > $prevtotal && $num <= ($prevtotal + $crecord->value)) {
                    $commands = json_decode($crecord->commands);
                    tool_abconfig_execute_command_array($crecord->commands, $record->shortname);

                    // Set a session var for this command, so it is not executed again this session
                    $SESSION->{$unique} = $crecord->condset;

                    // Do not execute any more conditions
                    break;

                } else {
                    // Not this record, increment lower bound, and move on
                    $prevtotal += $crecord->value;
                }
            }

            // If session var is not set, no set selected, update var
            if (!property_exists($SESSION, $unique)) {
                $SESSION->$unique = '';
            }

            // Now exit condition loop, this call is finished
            break;
        }
    }
}

function tool_abconfig_before_footer() {
    tool_abconfig_execute_js('footer');
}

function tool_abconfig_before_http_headers() {
    tool_abconfig_execute_js('header');
}

function tool_abconfig_execute_command_array($commandsencoded, $shortname, $js = false, $string = null) {
    global $CFG, $SESSION;

    // execute any commands passed in
    $commands = json_decode($commandsencoded);
    foreach ($commands as $commandstring) {

        $command = strtok($commandstring, ',');

        // Check for core commands
        if ($command == 'CFG') {
            $commandarray = explode(',', $commandstring, 3);

            // ensure that command hasnt already been set in config.php
            if (!array_key_exists($commandarray[1], $CFG->config_php_settings)) {
                $CFG->{$commandarray[1]} = $commandarray[2];
                $CFG->config_php_settings[$commandarray[1]] = $commandarray[2];
            }
        }
        if ($command == 'forced_plugin_setting') {
            // Check for plugin commands
            $commandarray = explode(',', $commandstring, 4);

            // Ensure that command hasnt already been forced in config.php
            // If plugin settings array doesnt exist, or the actualy config key doesnt exist
            if (!array_key_exists($commandarray[1], $CFG->forced_plugin_settings) ||
                !array_key_exists($commandarray[2], $CFG->forced_plugin_settings[$commandarray[1]])) {

                $CFG->forced_plugin_settings[$commandarray[1]][$commandarray[2]] = $commandarray[3];
            }
        }
        if ($command == 'http_header') {
            // Check for http header commands
            $commandarray = explode(',', $commandstring, 3);
            header("$commandarray[1]: $commandarray[2]");
        }
        if ($command == 'error_log') {
            // Check for error logs
            $commandarray = explode(',', $commandstring, 2);
            // Must ignore coding standards as typically error_log is not allowed
            error_log($commandarray[1]); // @codingStandardsIgnoreLine

        }
        if ($command == 'js_header') {
            // Check for JS header scripts
            $commandarray = explode(',', $commandstring, 2);
            // Set a unique session variable to be picked up by renderer hooks, to emit JS in the right areas
            $jsheaderunique = 'abconfig_js_header_'.$shortname;

            // Store the unique in the session to be picked up by the header render hook
            $SESSION->$jsheaderunique = $commandarray[1];

        }
        if ($command == 'js_footer') {
            // Check for JS footer scripts
            $commandarray = explode(',', $commandstring, 2);
            // Set a unique session variable to be picked up by renderer hooks, to emit JS in the right areas
            $jsfooterunique = 'abconfig_js_footer_'.$shortname;
            // Store the javascript in the session unique to be picked up by the footer render hook
            $SESSION->$jsfooterunique = $commandarray[1];
        }
    }
}

function tool_abconfig_execute_js($type) {
    // Check if the param to disable ABconfig is present, if so, exit
    if (array_key_exists('abconfig', $_GET) && $_GET['abconfig'] == 'off') {
        if (is_siteadmin()) {
            return null;
        }
    }

    global $DB, $SESSION;
    // Get all active experiments
    try {
        $records = $DB->get_records('tool_abconfig_experiment');
    } catch (Exception $e) {
        // Always fail cleanly and don't block a working moodle.
        return;
    }

    foreach ($records as $record) {
        // If called from header
        if ($type == 'header') {
            $unique = 'abconfig_js_header_'.$record->shortname;
        } else if ($type == 'footer') {
            $unique = 'abconfig_js_footer_'.$record->shortname;
        }

        if (property_exists($SESSION, $unique)) {
            // Found JS to be executed
            echo "<script type='text/javascript'>{$SESSION->$unique}</script>";
        }

        // If experiment is request scope, unset var so it doesnt fire again
        if ($record->scope == 'request' || $record->enabled == 0) {
            unset($SESSION->$unique);
        }
    }
}

