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
 * @copyright  2019 Peter Burnett <peterburnett@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;


global $CFG;

if ($hassiteconfig) {

    // Create category for settings and external pages
    $ADMIN->add('tools', new admin_category('abconfig', get_string('pluginname', 'tool_abconfig')));

    // Add external page for managing experiments
    $ADMIN->add('abconfig', new admin_externalpage('tool_abconfig_manageexperiments',
    get_string('manageexperimentspagename', 'tool_abconfig'),
    new moodle_url('/admin/tool/abconfig/manage_experiments.php')));

    $settings = new admin_settingpage('abconfigsettings', get_string('abconfigsettings', 'tool_abconfig'));
    $ADMIN->add('abconfig', $settings);

    if (!during_initial_install()) {

        $settings->add(new admin_setting_configcheckbox('tool_abconfig/enable_plugin', get_string('settingsenablename', 'tool_abconfig'),
        get_string('settingsenabledesc', 'tool_abconfig'), 0));

    }
}