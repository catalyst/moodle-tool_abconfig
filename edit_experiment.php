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

$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('editexperimentpagename', 'tool_abconfig'));

require_login();
require_capability('moodle/site:config', context_system::instance());

$eid = optional_param('id', 0, PARAM_INT);
$PAGE->set_url(new moodle_url('/admin/tool/abconfig/edit_experiment', array ('id' => $eid)));

if ($node = $PAGE->settingsnav->find('root', \navigation_node::TYPE_SITE_ADMIN)) {
    $PAGE->navbar->add($node->get_content(), $node->action());
}
foreach (array('tools', 'abconfig', 'tool_abconfig_manageexperiments') as $label) {
    if ($node = $PAGE->settingsnav->find($label, \navigation_node::TYPE_SETTING)) {
        $PAGE->navbar->add($node->get_content(), $node->action());
    }
}
$PAGE->navbar->add(get_string('editexperimentpagename', 'tool_abconfig'));

$manager = new tool_abconfig_experiment_manager();
$experiment = $DB->get_record('tool_abconfig_experiment', array('id' => $eid));
$data = array('experimentname' => $experiment->name, 'experimentshortname' => $experiment->shortname,
    'prevshortname' => $experiment->shortname, 'scope' => $experiment->scope,
    'id' => $experiment->id, 'enabled' => $experiment->enabled, 'adminenabled' => $experiment->adminenabled);

$customarray = array('eid' => $experiment->id);

$prevurl = ($CFG->wwwroot.'/admin/tool/abconfig/manage_experiments.php');
$form = new \tool_abconfig\form\edit_experiment(null, $customarray);
$form->set_data($data);
if ($form->is_cancelled()) {
    redirect($prevurl);
} else if ($form->no_submit_button_pressed()) {
    // Conditions button action.
    redirect(new moodle_url($CFG->wwwroot."/admin/tool/abconfig/edit_conditions.php?id=$experiment->id"));
} else if ($fromform = $form->get_data()) {
    // Form validation means data is safe to go to DB.

    // Set vars for cleaner DB queries.
    $name = $fromform->experimentname;
    $shortname = $fromform->experimentshortname;
    $scope = $fromform->scope;
    $enabled = $fromform->enabled;
    $eid = $fromform->id;
    $prevshortname = $fromform->prevshortname;
    $adminenabled = $fromform->adminenabled;

    // If eid is empty, do nothing.
    if ($eid == 0) {
        redirect($prevurl);
    }

    if ($fromform->delete) {
        // Delete experiment, and all orphaned experiment conditions.
        $manager->delete_experiment($shortname);
        $manager->delete_all_conditions($eid);
    } else {
        $manager->update_experiment($prevshortname, $name, $shortname, $scope, $enabled, $adminenabled);
    }

    redirect($prevurl);

} else {

    // Build the page output.
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('editexperimentpagename', 'tool_abconfig'));
    $form->display();
    echo $OUTPUT->footer();
}
