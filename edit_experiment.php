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

// Needs Require login admin thingy
require_login();

global $DB, $PAGE, $SESSION;
$prevurl = ($CFG->wwwroot.'/admin/tool/abconfig/manage_experiments.php');

$eid = optional_param('id', 0, PARAM_INT);

// store eid if set in params (for page submission and refresh)
if ($eid != 0) {
    $SESSION->eid = $eid;
}

// Check if eid is not set (from redirect)
if ($eid == 0) {
    if (property_exists($SESSION, 'eid')) {
        $eid = $SESSION->eid;
    } else {
        //Else if eid is still 0, someone directly got here with no params
        echo 'Do not come here directly';
        die;
    }
}

$experiment = $DB->get_record('tool_abconfig_experiments', array('id' => $eid));
if (empty($experiment)) {
    echo 'experiment not found';
    die;
}

$conditions = $DB->get_record('tool_abconfig_conditions', array('experiment' => $experiment->shortname));
if (!empty($conditions)) {
    $data = array('name' => $experiment->name, 'shortname' => $experiment->shortname, 'scope' => $experiment->scope,
    'ipwhitelist' => $conditions->ipwhitelist, 'commands' => $conditions->commands, 'value' => $conditions->value, 'id' => $eid);
} else {
    $data = array('name' => $experiment->name, 'shortname' => $experiment->shortname, 'scope' => $experiment->scope,
    'ipwhitelist' => '', 'commands' => '', 'value' => '', 'id' => $eid);
}

$form = new \tool_abconfig\form\edit_experiment(null, $data);
if ($form->is_cancelled()) {
    redirect($prevurl);
} else if ($fromform = $form->get_data()) {
    // Form validation means data is safe to go to DB
    global $DB;

    // Set vars for cleaner DB queries
    $shortname = $fromform->shortname;
    $iplist = $fromform->experimentipwhitelist;
    $commands = $fromform->experimentcommands;
    $value = $fromform->experimentvalue;

    $sqlconditions = $DB->sql_compare_text($shortname, strlen($shortname));
    $record = $DB->get_record_sql('SELECT * FROM {tool_abconfig_conditions} WHERE experiment = ?', array($sqlconditions));

    // If record doesnt exist, create record, else, update record
    if (empty($record)) {
        $DB->insert_record('tool_abconfig_conditions', array('experiment' => $shortname, 'ipwhitelist' => $iplist,
            'commands' => $commands, 'value' => $value));
    } else {
        $id = $record->id;
        $DB->update_record('tool_abconfig_conditions', array('id' => $id, 'experiment' => $shortname, 'ipwhitelist' => $iplist,
            'commands' => $commands, 'value' => $value));
    }
    // TODO TEMPORARY REDIRECT, FIX WHITESCREEN
    redirect($prevurl);
    
} else {

    // Build the page output
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('editexperimentpagename', 'tool_abconfig'));
    $form->display();
    echo $OUTPUT->footer();
}

