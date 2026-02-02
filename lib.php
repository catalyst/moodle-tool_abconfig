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

/**
 * After config, handles param, request and session experiments.
 *
 * This is a legacy callback that is used for compatibility with older Moodle versions.
 * Moodle 4.4+ will use tool_abconfig\hook_callbacks::after_config instead.
 *
 * @return void|null
 */
function tool_abconfig_after_config() {

    global $SESSION, $USER, $CFG;

    try {
        // Setup experiment manager.
        $manager = new tool_abconfig_experiment_manager();

        // Check if the param to disable ABconfig is present, if so, exit.
        if (!optional_param('abconfig', true, PARAM_BOOL)) {
            if (is_siteadmin()) {
                return null;
            }
        }

        // Get all after congig experiments and check params.
        $experiments = $manager->get_after_config_experiments();
        foreach ($experiments as $experiment => $contents) {
            if (defined('CLI_SCRIPT') && CLI_SCRIPT) {
                // Check ENV vars set on the cli.
                $condition = getenv('ABCONFIG_' . strtoupper($experiment));
            } else {
                // Check URL params, and fire any experiments in the params.
                $condition = optional_param($experiment, null, PARAM_TEXT);

                // Only admins can fire additional experiments.
                if (!is_siteadmin()) {
                    break;
                }
            }

            if (empty($condition)) {
                continue;
            }

            // Ensure condition set exists before executing.
            if (array_key_exists($condition, $contents['conditions'])) {
                tool_abconfig_execute_command_array(
                    $contents['conditions'][$condition]['commands'],
                    $contents['shortname']
                );
            }
        }

        $commandarray = [];

        // First, Build a list of all commands that need to be executed.

        // Start with request scope.
        $requestexperiments = $manager->get_active_request();
        if (!empty($requestexperiments)) {
            foreach ($requestexperiments as $record) {
                // Make admin immune unless enabled for admin.
                if (is_siteadmin()) {
                    if ($record['adminenabled'] == 0) {
                        continue;
                    }
                }

                $conditionrecords = $record['conditions'];

                // Remove all conditions that contain the user ip in the whitelist.
                $crecords = [];

                foreach ($conditionrecords as $conditionrecord) {
                    $iplist = $conditionrecord['ipwhitelist'];
                    $users = !empty($conditionrecord['users']) ? json_decode($conditionrecord['users']) : [];
                    if (empty($users) || in_array($USER->id, $users)) {
                        if (!remoteip_in_list($iplist)) {
                            array_push($crecords, $conditionrecord);
                        }
                    }
                }

                // Increment through conditions until one is selected.
                $condition = '';
                $num = rand(1, 100);
                $prevtotal = 0;
                foreach ($crecords as $crecord) {
                    // If random number is within this range, set condition and break, else increment total.
                    if ($num > $prevtotal && $num <= ($prevtotal + $crecord['value'])) {
                        $commandarray[$record['shortname']] = $crecord['commands'];
                        // Do not select any more conditions.
                        break;
                    } else {
                        // Not this record, increment lower bound, and move on.
                        $prevtotal += $crecord['value'];
                    }
                }
            }
        }

        // Now session scope.
        $sessionexperiments = $manager->get_active_session();
        if (!empty($sessionexperiments)) {
            foreach ($sessionexperiments as $record) {
                // Check if a session var has been set for this experiment, only care if has been set.
                $unique = 'abconfig_' . $record['shortname'];
                if (property_exists($SESSION, $unique) && $SESSION->$unique != '') {
                    $commandarray[$record['shortname']] = $record['conditions'][$SESSION->$unique]['commands'];
                }
            }
        }

        // Now, execute all commands in the arrays.
        foreach ($commandarray as $shortname => $command) {
            tool_abconfig_execute_command_array($command, $shortname);
        }
    } catch (Exception $e) {        // @codingStandardsIgnoreStart
        // Catch exceptions from stuff not existing during installation process, fail silently
    }                               // @codingStandardsIgnoreEnd
}

/**
 * Before session, handles device experiments.
 *
 * At some point this will also get converted to hook in core.
 *
 * @return void|null
 */
function tool_abconfig_before_session_start() {

    global $CFG;

    try {
        // Device experiments require IP and user agent, so can't be used for CLI scripts.
        if (!isset($_SERVER['REMOTE_ADDR']) || !isset($_SERVER['HTTP_USER_AGENT'])) {
            return null;
        }

        // Check if the param to disable ABconfig is present, if so, exit.
        if (!optional_param('abconfig', true, PARAM_BOOL)) {
            // We don't have $USER so this isn't locked behind admin.
            return null;
        }

        // Setup experiment manager.
        $manager = new tool_abconfig_experiment_manager();

        // Get all before session experiments and check params.
        $experiments = $manager->get_before_session_experiments();
        foreach ($experiments as $experiment => $contents) {
            // Check URL params, and fire any experiments in the params.
            $condition = optional_param($experiment, null, PARAM_TEXT);
            if (empty($condition)) {
                continue;
            }

            // Ensure condition set exists before executing.
            if (array_key_exists($condition, $contents['conditions'])) {
                tool_abconfig_execute_command_array(
                    $contents['conditions'][$condition]['commands'],
                    $contents['shortname']
                );
            }
        }

        // First, Build a list of all commands that need to be executed.
        $commandarray = [];

        // Device scope.
        $deviceexperiments = $manager->get_active_device();
        if (!empty($deviceexperiments)) {
            // Create a hash using IP and useragent, and convert it to a number.
            $hash = md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
            $basenum = hexdec(substr($hash, 0, 8)) % 100;
            foreach ($deviceexperiments as $record) {
                // Admins are not immune from device experiments by default.
                $conditionrecords = $record['conditions'];

                // Remove all conditions that contain the user ip in the allow list.
                $crecords = [];

                foreach ($conditionrecords as $conditionrecord) {
                    $iplist = $conditionrecord['ipwhitelist'];
                    if (!remoteip_in_list($iplist)) {
                        array_push($crecords, $conditionrecord);
                    }
                }

                // Device logic.
                // Add offset to the base to rotate hashes between experiments.
                $num = ($basenum + $record['numoffset'] ?? 0) % 100;
                $prevtotal = 0;
                foreach ($crecords as $crecord) {
                    // If random hash is within this range, set condition and break, else increment total.
                    if ($num >= $prevtotal && $num < ($prevtotal + $crecord['value'])) {
                        $commandarray[$record['shortname']] = $crecord['commands'];
                        // Do not select any more conditions.
                        break;
                    } else {
                        // Not this record, increment lower bound, and move on.
                        $prevtotal += $crecord['value'];
                    }
                }
            }
        }

        // Now, execute all commands in the arrays.
        foreach ($commandarray as $shortname => $command) {
            tool_abconfig_execute_command_array($command, $shortname);
        }
    } catch (Exception $e) {        // @codingStandardsIgnoreStart
        // Catch exceptions from stuff not existing during installation process, fail silently
    }                               // @codingStandardsIgnoreEnd
}

/**
 * After require login
 * @return void|null
 * @throws coding_exception
 */
function tool_abconfig_after_require_login() {
    global $SESSION, $USER;

    // Create experiment manager.
    $manager = new tool_abconfig_experiment_manager();

    // Check if the param to disable ABconfig is present, if so, exit.
    if (optional_param('abconfig', null, PARAM_TEXT) == 'off') {
        if (is_siteadmin()) {
            return null;
        }
    }
    // Get active session records.
    $records = $manager->get_active_session();
    if (!empty($records)) {
        foreach ($records as $record) {
            // Make admin immune unless enabled for admin.
            if (is_siteadmin()) {
                if ($record['adminenabled'] == 0) {
                    continue;
                }
            }

            // Create experiment session var identifier.
            $unique = 'abconfig_' . $record['shortname'];
            // Get condition sets for experiment.
            $conditionrecords = $record['conditions'];
            // Remove all conditions that contain the user ip in the whitelist.
            $crecords = [];

            foreach ($conditionrecords as $conditionrecord) {
                $iplist = $conditionrecord['ipwhitelist'];
                $users = !empty($conditionrecord['users']) ? json_decode($conditionrecord['users']) : [];
                if (empty($users) || in_array($USER->id, $users)) {
                    if (!remoteip_in_list($iplist)) {
                        array_push($crecords, $conditionrecord);
                    }
                }
            }

            // If condition set hasnt been selected, select a condition set, or none.
            if (!property_exists($SESSION, $unique)) {
                // Increment through conditions until one is selected.
                $num = rand(1, 100);
                $prevtotal = 0;
                foreach ($crecords as $crecord) {
                    // If random number is within this range, set condition and break, else increment total.
                    if ($num > $prevtotal && $num <= ($prevtotal + $crecord['value'])) {
                        tool_abconfig_execute_command_array($crecord['commands'], $record['shortname']);

                        // Set a session var for this command, so it is not executed again this session.
                        $SESSION->{$unique} = $crecord['condset'];

                        // Do not execute any more conditions.
                        break;
                    } else {
                        // Not this record, increment lower bound, and move on.
                        $prevtotal += $crecord['value'];
                    }
                }

                // If session var is not set, no set selected, update var.
                if (!property_exists($SESSION, $unique)) {
                    $SESSION->$unique = '';
                }

                // Now exit condition loop, this call is finished.
                break;
            }
        }
    }
}

/**
 * Before footer
 *
 * This is a legacy callback that is used for compatibility with older Moodle versions.
 * Moodle 4.4+ will use tool_abconfig\hook_callbacks::before_footer_html_generation instead.
 *
 * @return void
 */
function tool_abconfig_before_footer() {
    tool_abconfig_execute_js('footer');
}

/**
 * Before http headers
 *
 * This is a legacy callback that is used for compatibility with older Moodle versions.
 * Moodle 4.4+ will use tool_abconfig\hook_callbacks::before_http_headers instead.
 *
 * @return void
 */
function tool_abconfig_before_http_headers() {
    tool_abconfig_execute_js('header');
}

/**
 * Execute command array
 * @param string $commandsencoded
 * @param string $shortname
 * @param bool $js
 * @param string|null $string
 * @return void
 */
function tool_abconfig_execute_command_array($commandsencoded, $shortname, $js = false, ?string $string = null) {
    global $CFG;

    // Execute any commands passed in.
    $manager = new tool_abconfig_experiment_manager();
    $commands = json_decode($commandsencoded);
    $experimenturl = new moodle_url('/admin/tool/abconfig/edit_experiment.php', ['shortname' => $shortname]);
    $link = \html_writer::link($experimenturl, $shortname);
    foreach ($commands as $commandstring) {
        $command = strtok($commandstring, ',');

        // Check for core commands.
        if ($command == 'CFG') {
            $commandarray = explode(',', $commandstring, 3);

            // Allow override if set in config.php already.
            $allow = isset($CFG->{$commandarray[1] . '_allow_abconfig'});

            // Ensure that command hasn't already been set in config.php.
            if ($allow || !array_key_exists($commandarray[1], $CFG->config_php_settings)) {
                $CFG->{$commandarray[1]} = $commandarray[2];
                $CFG->config_php_settings[$commandarray[1]] = $commandarray[2];
                $CFG->tool_abconfig_message[$commandarray[1]] = get_string('settingcustommessage', 'tool_abconfig', $link);
            } else {
                // Debugging shouldn't be used before sessions are loaded.
                // @codingStandardsIgnoreLine
                error_log("abconfig: Can't override \$CFG->{$commandarray[1]} because already set in config.php!");
            }
        }
        if ($command == 'forced_plugin_setting') {
            // Check for plugin commands.
            $commandarray = explode(',', $commandstring, 4);

            // Ensure that command hasnt already been forced in config.php or that overriding is allowed.
            // If plugin settings array doesnt exist, or the actual config key doesnt exist.
            if (
                !array_key_exists($commandarray[1], $CFG->forced_plugin_settings) ||
                    !array_key_exists($commandarray[2], $CFG->forced_plugin_settings[$commandarray[1]]) ||
                    array_key_exists($commandarray[2] . '_allow_abconfig', $CFG->forced_plugin_settings[$commandarray[1]])
            ) {
                $CFG->forced_plugin_settings[$commandarray[1]][$commandarray[2]] = $commandarray[3];
                $CFG->tool_abconfig_message[$commandarray[1]][$commandarray[2]] =
                get_string('settingcustommessage', 'tool_abconfig', $link);
            } else {
                // Debugging shouldn't be used before sessions are loaded.
                // @codingStandardsIgnoreLine
                error_log("abconfig: Can't override \$CFG->forced_plugin_settings['$commandarray[1]']['$commandarray[2]'] " .
                    "because already set in config.php!");
            }
        }
        if ($command == 'http_header') {
            // Check for http header commands.
            $commandarray = explode(',', $commandstring, 3);
            header("$commandarray[1]: $commandarray[2]");
        }
        if ($command == 'error_log') {
            // Check for error logs.
            $commandarray = explode(',', $commandstring, 2);
            // Must ignore coding standards as typically error_log is not allowed.
            error_log($commandarray[1]); // @codingStandardsIgnoreLine
        }
        if ($command == 'js_header') {
            // Check for JS header scripts.
            $commandarray = explode(',', $commandstring, 2);
            // Set a unique manager variable to be picked up by renderer hooks, to emit JS in the right areas.
            $jsheaderunique = 'abconfig_js_header_' . $shortname;

            // Store the unique in the manager to be picked up by the header render hook.
            $manager->set_render_js($jsheaderunique, $commandarray[1]);
        }
        if ($command == 'js_footer') {
            // Check for JS footer scripts.
            $commandarray = explode(',', $commandstring, 2);
            // Set a unique manager variable to be picked up by renderer hooks, to emit JS in the right areas.
            $jsfooterunique = 'abconfig_js_footer_' . $shortname;
            // Store the javascript in the manager unique to be picked up by the footer render hook.
            $manager->set_render_js($jsfooterunique, $commandarray[1]);
        }
    }
}

/**
 * Execute JS
 * @param string $type
 * @return void|null
 */
function tool_abconfig_execute_js(string $type) {
    // Check if the param to disable ABconfig is present, if so, exit.
    if (optional_param('abconfig', null, PARAM_TEXT) == 'off') {
        if (is_siteadmin()) {
            return null;
        }
    }

    try {
        // Get all experiments.
        $manager = new tool_abconfig_experiment_manager();
        $records = $manager->get_experiments();
        $renderjs = $manager->get_render_js();

        foreach ($records as $record) {
            // If called from header.
            if ($type == 'header') {
                $unique = 'abconfig_js_header_' . $record['shortname'];
            } else if ($type == 'footer') {
                $unique = 'abconfig_js_footer_' . $record['shortname'];
            }

            if (array_key_exists($unique, $renderjs)) {
                // Found JS to be executed.
                echo "<script type='text/javascript'>{$renderjs[$unique]}</script>";
            }

            // If experiment is request scope, unset var so it doesnt fire again.
            if ($record['scope'] == 'request' || $record['enabled'] == 0) {
                $manager->remove_render_js($unique);
            }
        }

    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
    } catch (Exception $e) {
        // // phpcs:ignore
        // Do nothing for edge cases like install and upgrade.
    }
}
