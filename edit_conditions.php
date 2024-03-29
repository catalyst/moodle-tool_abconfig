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

$PAGE->set_context(context_system::instance());
$title = get_string('editexperimentconds', 'tool_abconfig');
$PAGE->set_title($title);

require_login();
require_capability('moodle/site:config', context_system::instance());

$eid = optional_param('id', null, PARAM_INT);

$url = new moodle_url('/admin/tool/abconfig/edit_conditions.php');
$url->param('id', $eid);
$PAGE->set_url($url);

$manager = new tool_abconfig_experiment_manager();

$prevurl = ($CFG->wwwroot."/admin/tool/abconfig/edit_experiment.php?id=$eid");

$customdata = array('eid' => $eid);

$experiment = $DB->get_record('tool_abconfig_experiment', array('id' => $eid));

$form = new \tool_abconfig\form\edit_conditions($url, $customdata);

if ($form->is_cancelled()) {
    redirect($prevurl);
} else if ($fromform = $form->get_data()) {

    $eid = $fromform->eid;
    // Page doesnt have an experiment, do nothing.
    if (empty($eid)) {
        redirect($prevurl);
    }

    // Updating old data.
    $records = $DB->get_records('tool_abconfig_condition', array('experiment' => $eid), 'id ASC');
    foreach ($records as $record) {
        $prevshortname = "prevshortname{$record->id}";
        $shortname = "shortname{$record->id}";
        $iplist = "iplist{$record->id}";
        $commandskey = "commands{$record->id}";
        $value = "value{$record->id}";
        $users = "users{$record->id}";
        $delete = "delete{$record->id}";

        if ($fromform->$delete) {
            // Delete record if delete checkbox enabled.
            $manager->delete_condition($eid, $fromform->$shortname);
        } else {
            // Else write data back to DB.
            $manager->update_condition($eid, $record->id, $fromform->$prevshortname,
                $fromform->$shortname, $fromform->$iplist, $fromform->$commandskey, $fromform->$value, $fromform->$users);
        }
    }

    // Adding new data.
    if (!empty($fromform->repeatid)) {
        $repeats = array_keys($fromform->repeatid);
        foreach ($repeats as $key => $value) {

            // Protect from empty data.
            if (empty($fromform->repeatshortname[$value])) {
                continue;
            }

            if ($fromform->repeatdelete[$value]) {
                // If accidentally added condition set and wishes to delete.
                continue;
            } else {
                $manager->add_condition($eid, $fromform->repeatshortname[$value], $fromform->repeatiplist[$value],
                    $fromform->repeatcommands[$value], $fromform->repeatvalue[$value], $fromform->repeatusers[$value]);
            }
        }
    }

    // Back to experiment.
    redirect($prevurl);

} else {

    // Build the page output.
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('editexperimentpagename', 'tool_abconfig'));
    $form->display();
    echo $OUTPUT->footer();
}
