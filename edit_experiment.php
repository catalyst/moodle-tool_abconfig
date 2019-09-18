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
require_once($CFG->libdir . '/adminlib.php');

defined('MOODLE_INTERNAL') || die();

$PAGE->set_context(context_system::instance());
$PAGE->set_title('Edit Experiment Conditions');

// Needs Require login admin thingy
require_login();

$manager = new tool_abconfig_experiment_manager();

global $DB, $PAGE, $SESSION;
$prevurl = ($CFG->wwwroot.'/admin/tool/abconfig/manage_experiments.php');

$eid = optional_param('id', 0, PARAM_INT);

$experiment = $DB->get_record('tool_abconfig_experiment', array('id' => $eid));

$data = array('experimentname' => $experiment->name, 'experimentshortname' => $experiment->shortname,
    'scope' => $experiment->scope, 'id' => $experiment->id, 'enabled' => $experiment->enabled);
$customarray = array('eid' => $experiment->id);

$form = new \tool_abconfig\form\edit_experiment(null, $customarray);
$form->set_data($data);
if ($form->is_cancelled()) {
    redirect($prevurl);
} else if ($form->no_submit_button_pressed()) {
    // Conditions button action
    redirect(new moodle_url($CFG->wwwroot."/admin/tool/abconfig/edit_conditions.php?id=$experiment->id"));
} else if ($fromform = $form->get_data()) {
    // If eid is empty, do nothing
    // Form validation means data is safe to go to DB
    global $DB;

    // Set vars for cleaner DB queries
    $name = $fromform->experimentname;
    $shortname = $fromform->experimentshortname;
    $scope = $fromform->scope;
    $enabled = $fromform->enabled;
    $eid = $fromform->id;

    if ($eid == 0) {
        redirect($prevurl);
    }

    if ($fromform->delete) {
        // Delete experiment, and all orphaned experiment conditions
        $manager->delete_experiment($shortname);
        $manager->delete_all_conditions($eid);
    } else {
        $manager->update_experiment($name, $shortname, $scope, $enabled);
    }

    redirect($prevurl);

} else {

    // Build the page output
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('editexperimentpagename', 'tool_abconfig'));
    $form->display();
    echo $OUTPUT->footer();
}

