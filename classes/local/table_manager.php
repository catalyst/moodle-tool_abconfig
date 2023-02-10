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
 * Table management and generation class.
 *
 * @package   tool_abconfig
 * @author    Peter Burnett <peterburnett@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_abconfig\local;

defined('MOODLE_INTERNAL') || die;

/**
 * Table management and generation class.
 *
 * @package   tool_abconfig
 * @author    Peter Burnett <peterburnett@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class table_manager {
    /**
     * Function to generate the HTML for the experiments table.
     *
     * @return string The HTML for the table
     */
    public static function experiment_table() {
        global $DB;

        $records = $DB->get_records('tool_abconfig_experiment');
        // Get header strings.
        $wantstrings = array('name', 'shortname', 'scope', 'edit', 'enabled', 'adminenabled');
        $strings = get_strings($wantstrings, 'tool_abconfig');
        // Generate table header.
        $table = new \html_table();
        $table->head = array(get_string('idnumber'), $strings->name, $strings->shortname,
            $strings->scope, $strings->enabled, $strings->adminenabled, $strings->edit);
        $table->attributes['class'] = 'generaltable table table-bordered';
        $table->colclasses = array('centeralign', 'centeralign', 'centeralign',
            'centeralign', 'centeralign', 'centeralign', 'centeralign');

        foreach ($records as $record) {
            // Setup edit link.
            $url = new \moodle_url('/admin/tool/abconfig/edit_experiment.php', array('id' => $record->id));
            if ($record->enabled == 0) {
                $enabled = get_string('no');
            } else {
                $enabled = get_string('yes');
            }

            if ($record->adminenabled == 0) {
                $adminenabled = get_string('no');
            } else {
                $adminenabled = get_string('yes');
            }

            // Add table row.
            $table->data[] = array($record->id, $record->name, $record->shortname,
                $record->scope, $enabled, $adminenabled, \html_writer::link($url, get_string('edit')));
        }
        return \html_writer::table($table);
    }

    /**
     * Generates the HTML for a table of conditions for an experiments.
     *
     * @param int $eid the experiment ID to get conditions for.
     *
     * @return string the HTML for the table.
     */
    public static function conditions_table($eid) {
        global $DB;

        // Get all lang strings for table header.
        $stringsreqd = array(
            'formipwhitelist',
            'formexperimentcommands',
            'formexperimentvalue',
            'formexperimentcondsset',
            'formexperimentusers',
            'formexperimentforceurl',
        );
        $stringarr = get_strings($stringsreqd, 'tool_abconfig');

        // Setup table.
        $table = new \html_table();
        $table->head = array(
            $stringarr->formexperimentcondsset,
            $stringarr->formipwhitelist,
            $stringarr->formexperimentcommands,
            $stringarr->formexperimentvalue,
            $stringarr->formexperimentusers,
            $stringarr->formexperimentforceurl,
        );
        $table->attributes['class'] = 'generaltable table table-bordered';

        // Get experiment conditions records.
        $manager = new \tool_abconfig_experiment_manager();
        $records = $manager->get_conditions_for_experiment($eid);
        foreach ($records as $record) {
            // Check for empty commands.
            if (empty($record->commands)) {
                $commands = get_string('formnocommands', 'tool_abconfig');
            } else {
                $commands = $record->commands;
            }

            // Check for empty IPs.
            if (empty($record->ipwhitelist)) {
                $iplist = get_string('formnoips', 'tool_abconfig');
            } else {
                $iplist = $record->ipwhitelist;
            }

            // Check for empty users.
            if (empty(json_decode($record->users))) {
                $users = get_string('formallusers', 'tool_abconfig');
            } else {
                $users = $record->users;
            }

            // Construct URL for forcing condition.
            $paramstring = '?';
            // Get experiment shortname.
            $experiment = $DB->get_record('tool_abconfig_experiment', array('id' => $eid));
            $paramstring .= $experiment->shortname . '=';
            $paramstring .= $record->condset;

            // URL for redirecting to the dashboard with conditions active.
            $url = new \moodle_url('/my/', array($experiment->shortname => $record->condset));

            $table->data[] = array($record->condset, $iplist, $commands, $record->value, $users, \html_writer::link($url, $paramstring));
        }

        return \html_writer::table($table);
    }
}
