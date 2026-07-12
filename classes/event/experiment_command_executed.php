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

namespace tool_abconfig\event;

use core\event\base;

/**
 * experiment_command_executed event class.
 *
 * Fired only for commands that mutate runtime configuration (CFG and
 * forced_plugin_setting), not for every command type, to avoid flooding the
 * log store on high-traffic pages where request/session scoped experiments
 * execute on every page load.
 *
 * @package   tool_abconfig
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class experiment_command_executed extends base {
    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->context = \context_system::instance();
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_experiment_command_executed', 'tool_abconfig');
    }

    /**
     * Returns non-localised event description with ids for admin use only.
     *
     * @return string
     */
    public function get_description() {
        $shortname = $this->other['shortname'] ?? '';
        $setting = $this->other['setting'] ?? '';
        return "Experiment '{$shortname}' executed a command that modified the runtime setting '{$setting}'.";
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/admin/tool/abconfig/index.php');
    }
}
