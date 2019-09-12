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
 * Form for editing experiments
 *
 * @package   tool_abconfig
 * @author    Peter Burnett <peterburnett@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__) . '/../../../config.php');
//require_once($CFG->libdir . '/adminlib.php');

defined('MOODLE_INTERNAL') || die();

$PAGE->set_context(context_system::instance());
$PAGE->set_title('Edit Experiment Conditions');

// Needs Require login admin thingy
require_login();

global $DB, $PAGE, $SESSION;
$prevurl = ($CFG->wwwroot.'/admin/tool/abconfig/manage_experiments.php');

$eid = optional_param('id', null, PARAM_INT);
//$eid = required_param('id', PARAM_INT);

$url = new moodle_url('/admin/tool/abconfig/edit_conditions.php');
$url->param('id', $eid);
$PAGE->set_url($url);

if (empty($eid)) {

}

$customdata = array('eid' => $eid);

$experiment = $DB->get_record('tool_abconfig_experiment', array('id' => $eid));

$form = new \tool_abconfig\form\edit_conditions($url, $customdata);

if ($form->is_cancelled()) {
    redirect($prevurl);
} else if ($fromform = $form->get_data()) {
    global $DB;
    $eid = $fromform->eid;


    //Updating old data
    $records = $DB->get_records('tool_abconfig_condition', array('experiment' => $eid), 'id ASC');
    foreach ($records as $record) {
        $shortname = "shortname{$record->id}";
        $commands = "commands{$record->id}";
        $value = "value{$record->id}";

        $DB->update_record('tool_abconfig_condition', array(
            'id' => $record->id,
            'experiment' => $record->experiment,
            'set' => $fromform->$shortname,
            'commands' => json_encode(explode(PHP_EOL, $fromform->$commands)),
            'value' => $fromform->$value
        ));
    }

    // Adding new data
    $repeats = array_keys($fromform->repeatid);
    foreach ($repeats as $key => $value) {
        $DB->insert_record('tool_abconfig_condition', array (
            'experiment' => $eid,
            'set' => $fromform->repeatshortname[$value],
            'commands' => json_encode(explode(PHP_EOL, $fromform->repeatcommands[$value])),
            'value' => $fromform->repeatvalue[$value]
        ));
    }
    redirect($prevurl);

} else {

    // Build the page output
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('editexperimentpagename', 'tool_abconfig'));
    $form->display();
    echo $OUTPUT->footer();
}