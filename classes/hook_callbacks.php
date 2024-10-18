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

namespace tool_abconfig;

/**
 * Hook callbacks for tool_abconfig.
 *
 * @package   tool_abconfig
 * @author    Benjamin Walker (benjaminwalker@catalyst-au.net)
 * @copyright 2024 Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * Runs before HTTP headers.
     *
     * @param \core\hook\output\before_http_headers $hook
     */
    public static function before_http_headers(\core\hook\output\before_http_headers $hook): void {
        if (!get_config('tool_abconfig', 'version')) {
            // Do nothing if plugin install not completed.
            return;
        }

        tool_abconfig_execute_js('header');
    }

    /**
     * Runs before HTTP footers.
     *
     * @param \core\hook\output\before_footer_html_generation $hook
     */
    public static function before_footer_html_generation(\core\hook\output\before_footer_html_generation $hook): void {
        if (!get_config('tool_abconfig', 'version')) {
            // Do nothing if plugin install not completed.
            return;
        }

        tool_abconfig_execute_js('footer');
    }

    /**
     * Runs after config has been set.
     *
     * @param \core\hook\before_http_headers $hook
     * @return void|null
     */
    public static function after_config(\core\hook\after_config $hook) {
        if (!get_config('tool_abconfig', 'version')) {
            // Do nothing if plugin install not completed.
            return;
        }

        try {
            global $SESSION, $USER;

            // Setup experiment manager.
            $manager = new \tool_abconfig_experiment_manager();

            // Check if the param to disable ABconfig is present, if so, exit.
            if (!optional_param('abconfig', true, PARAM_BOOL)) {
                if (is_siteadmin()) {
                    return null;
                }
            }

            // Get all experiments.
            $experiments = $manager->get_experiments();
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
                    tool_abconfig_execute_command_array($contents['conditions'][$condition]['commands'],
                        $contents['shortname']);
                }
            }

            $commandarray = array();

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
                    $crecords = array();

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
                    $unique = 'abconfig_'.$record['shortname'];
                    if (property_exists($SESSION, $unique) && $SESSION->$unique != '') {
                        $commandarray[$record['shortname']] = $record['conditions'][$SESSION->$unique]['commands'];
                    }
                }
            }

            // Now, execute all commands in the arrays.
            foreach ($commandarray as $shortname => $command) {
                tool_abconfig_execute_command_array($command, $shortname);
            }
        } catch (\Exception $e) {        // @codingStandardsIgnoreStart
            // Catch exceptions from stuff not existing during installation process, fail silently
        }                               // @codingStandardsIgnoreEnd
    }
}
