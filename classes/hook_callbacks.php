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
        global $CFG;

        if (during_initial_install() || isset($CFG->upgraderunning)) {
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
        global $CFG;

        if (during_initial_install() || isset($CFG->upgraderunning)) {
            return;
        }

        tool_abconfig_execute_js('footer');
    }

    /**
     * Runs after config has been set.
     *
     * @param \core\hook\after_config $hook
     * @return void|null
     */
    public static function after_config(\core\hook\after_config $hook) {
        global $CFG;

        if (during_initial_install() || isset($CFG->upgraderunning) || !get_config('tool_abconfig', 'version')) {
            return;
        }

        // Handles edge case during upgrade & install where this callback doesn't have the lib loaded.
        if (!function_exists('tool_abconfig_after_config')) {
            require_once($CFG->dirroot . '/admin/tool/abconfig/lib.php');
        }

        tool_abconfig_after_config();
    }

    /**
     * Provides a custom locked setting message for admin settings locked by experiments.
     *
     * @param \core\hook\admin_setting_notification $hook
     * @return void
     */
    public static function admin_setting_notification(\core\hook\admin_setting_notification $hook) {
        global $CFG;

        $name = $hook->setting->name;
        $plugin = $hook->setting->plugin;

        // Checking if the setting is for a plugin.
        if (!empty($plugin)) {
            // Check if there is a message set for this plugin setting.
            if (
                isset($CFG->tool_abconfig_message[$plugin]) &&
                is_array($CFG->tool_abconfig_message[$plugin]) &&
                array_key_exists($name, $CFG->tool_abconfig_message[$plugin])
            ) {
                $hook->add_notification($CFG->tool_abconfig_message[$plugin][$name], \core\output\notification::NOTIFY_INFO);
            }
        } else {
            // Check if there is a message set for this core setting.
            if (isset($CFG->tool_abconfig_message[$name])) {
                $hook->add_notification($CFG->tool_abconfig_message[$name], \core\output\notification::NOTIFY_INFO);
            }
        }
    }
}
