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

global $DB, $PAGE, $SESSION;
$prevurl = ($CFG->wwwroot.'/admin/tool/abconfig/manage_experiments.php');

$eid = optional_param('id', 0, PARAM_INT);

$experiment = $DB->get_record('tool_abconfig_experiment', array('id' => $eid));

// Set default displays to first condition set found
$conditions = $DB->get_records('tool_abconfig_condition', array('experiment' => $experiment->id));

if (!empty($conditions)) {
    // Unserialise data for display
    $commands = implode(PHP_EOL, json_decode(reset($conditions)->commands));
    $iplist = implode(PHP_EOL, json_decode(reset($conditions)->ipwhitelist));

    $data = array('experimentname' => $experiment->name, 'experimentshortname' => $experiment->shortname, 'shortname' => $experiment->shortname,
    'experimentscope' => $experiment->scope, 'experimentipwhitelist' => $iplist,
    'experimentcommands' =>  $commands, 'experimentvalue' =>  reset($conditions)->value, 'id' => $eid, 'set' => reset($conditions)->set,
    'enabled' => $experiment->enabled);
} else {
    $data = array('experimentname' => $experiment->name, 'experimentshortname' => $experiment->shortname,  'shortname' => $experiment->shortname,
    'experimentscope' => $experiment->scope, 'experimentipwhitelist' => '', 'experimentcommands' => '', 'experimentvalue' => '', 'id' => $eid, 'set' => 0,
    'enabled' => 0);
}

$customarray = array('eid' => $experiment->id);

$form = new \tool_abconfig\form\edit_experiment(null, $customarray);
$form->set_data($data);
if ($form->is_cancelled()) {
    redirect($prevurl);
} else if ($fromform = $form->get_data()) {
    // If eid is empty, do nothing
    // Form validation means data is safe to go to DB
    global $DB;

    // Set vars for cleaner DB queries
    $shortname = $fromform->shortname;
    $iplist = $fromform->experimentipwhitelist;
    $commands = $fromform->experimentcommands;
    $value = $fromform->experimentvalue;
    $set = $fromform->set;
    $eid = $fromform->id;

    // JSON Serialise commands and IP whitelist for storage
    $commands = json_encode(explode(PHP_EOL, $commands));
    $iplist = json_encode(explode(PHP_EOL, $iplist));

    if ($eid == 0) {
        redirect($prevurl);
    }

    $record = $DB->get_record('tool_abconfig_condition', array('experiment' => $eid, 'set' => $set));

    // If record doesnt exist, create record, else, update record
    if (empty($record)) {
        $DB->insert_record('tool_abconfig_condition', array('experiment' => $eid, 'ipwhitelist' => $iplist,
            'commands' => $commands, 'value' => $value, 'set' => $set));
    } else {
        $id = $record->id;
        $DB->update_record('tool_abconfig_condition', array('id' => $id, 'experiment' => $eid, 'ipwhitelist' => $iplist,
            'commands' => $commands, 'value' => $value, 'set' => $set));
    }

    // Enable or disable experiment based on checkbox
    if ($fromform->enabled) {
        $DB->set_field('tool_abconfig_experiment', 'enabled', 1, array('id' => $eid));
    } else {
        $DB->set_field('tool_abconfig_experiment', 'enabled', 0, array('id' => $eid));
    }

    redirect($prevurl);

} else {

    // Build the page output
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('editexperimentpagename', 'tool_abconfig'));
    $form->display();
    echo $OUTPUT->footer();
}

