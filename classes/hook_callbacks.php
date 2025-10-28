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

require_once(__DIR__ . '/../lib.php');

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

        \tool_abconfig_execute_js('header');
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

        \tool_abconfig_execute_js('footer');
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

        \tool_abconfig_after_config();
    }
}
