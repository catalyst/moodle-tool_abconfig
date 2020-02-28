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
 * Version information.
 *
 * @package   tool_abconfig
 * @author    Peter Burnett <peterburnett@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$manager = new tool_abconfig_experiment_manager();

defined('MOODLE_INTERNAL') || die();

admin_externalpage_setup('tool_abconfig_manageexperiments');

$prevurl = ($CFG->wwwroot.'/admin/category.php?category=abconfig');

$form = new \tool_abconfig\form\manage_experiments();
if ($form->is_cancelled()) {
    redirect($prevurl);
} else if ($fromform = $form->get_data()) {
    // Safe to insert new experiment, unique field validated in form validation.
    $name = $fromform->experimentname;
    $shortname = $fromform->experimentshortname;
    $scope = $fromform->scope;

    $manager->add_experiment($name, $shortname, $scope);
}

// Build the page output.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manageexperimentspagename', 'tool_abconfig'));
$form->display();
generate_table();
echo $OUTPUT->footer();

function generate_table() {
    global $CFG, $DB;

    $records = $DB->get_records('tool_abconfig_experiment');
    // Get header strings.
    $wantstrings = array('name', 'shortname', 'scope', 'edit', 'enabled', 'adminenabled');
    $strings = get_strings($wantstrings, 'tool_abconfig');
    // Generate table header.
    $table = new html_table();
    $table->head = array('ID', $strings->name, $strings->shortname, $strings->scope,
        $strings->enabled, $strings->adminenabled, $strings->edit);
    $table->colclasses = array('centeralign', 'centeralign', 'centeralign',
        'centeralign', 'centeralign', 'centeralign', 'centeralign');

    foreach ($records as $record) {
        // Setup edit link.
        $url = new moodle_url($CFG->wwwroot."/admin/tool/abconfig/edit_experiment.php?id=$record->id");
        if ($record->enabled == 0) {
            $enabled = get_string('no', 'tool_abconfig');
        } else {
            $enabled = get_string('yes', 'tool_abconfig');
        }

        if ($record->adminenabled == 0) {
            $adminenabled = get_string('no', 'tool_abconfig');
        } else {
            $adminenabled = get_string('yes', 'tool_abconfig');
        }

        // Add table row.
        $table->data[] = array($record->id, $record->name, $record->shortname,
            $record->scope, $enabled, $adminenabled, '<a href="'.$url.'">Edit</a>');
    }
    echo html_writer::table($table);
}
