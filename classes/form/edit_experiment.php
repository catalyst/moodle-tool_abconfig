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
namespace tool_abconfig\form;

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/formslib.php");

class edit_experiment extends \moodleform {

    public function definition() {
        $mform = $this->_form;

        // Hidden form element for experiment id
        $mform->addElement('hidden', 'id', '');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'prevshortname', '');
        $mform->setType('prevshortname', PARAM_TEXT);

        // eid to pass to table generation
        $eid = $this->_customdata['eid'];

        // Display the basic experiment information
        $mform->addElement('header', 'experimentinfo', get_string('formexperimentinfo', 'tool_abconfig'));

        $mform->addElement('text', 'experimentname', get_string('name', 'tool_abconfig'), '');
        $mform->setType('experimentname', PARAM_TEXT);
        $mform->addRule('experimentname', get_string('formexperimentnamereq'), 'required');

        $mform->addElement('text', 'experimentshortname', get_string('shortname', 'tool_abconfig'), '');
        $mform->setType('experimentshortname', PARAM_TEXT);
        $mform->addRule('experimentshortname', get_string('formexperimentshortnamereq', 'tool_abconfig'), 'required');

        // Setup Data array for scopes
        $scopes = ['request' => get_string('request', 'tool_abconfig'), 'session' => get_string('session', 'tool_abconfig')];
        $mform->addElement('select', 'scope', get_string('formexperimentscopeselect', 'tool_abconfig'), $scopes);

        // Enabled checkbox
        $mform->addElement('advcheckbox', 'enabled', get_string('formexperimentenabled', 'tool_abconfig'));

        // Delete experiment checkbox
        $mform->addElement('advcheckbox', 'delete', get_string('formdeleteexperiment', 'tool_abconfig'));

        // Experiment conditions
        $mform->addElement('header', 'experimentconds', get_string('formexperimentconds', 'tool_abconfig'));

        $mform->addElement('html', $this->generate_table($eid));

        // Setup button group
        $buttonarray = array();
        $buttonarray[] =& $mform->createElement('submit', 'savechanges', get_string('save'));
        $buttonarray[] =& $mform->createElement('submit', 'conditions', get_string('formeditconditions', 'tool_abconfig'));
        $mform->registerNoSubmitButton('conditions');
        $mform->closeHeaderBefore('conditions');
        $buttonarray[] =& $mform->createElement('cancel', 'cancel', get_string('cancel'));

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }

    private function generate_table($eid) {
        global $DB;

        // Get all lang strings for table header
        $stringsreqd = array('formipwhitelist', 'formexperimentcommands', 'formexperimentvalue', 'formexperimentcondsset');
        $stringarr = get_strings($stringsreqd, 'tool_abconfig');

        // Setup table
        $table = new \html_table();
        $table->head = array($stringarr->formexperimentcondsset, $stringarr->formipwhitelist,
            $stringarr->formexperimentcommands, $stringarr->formexperimentvalue);

        // Get experiment conditions records
        $records = $DB->get_records('tool_abconfig_condition', array('experiment' => $eid), 'condset ASC');
        foreach ($records as $record) {
            // Check for empty commands
            if (empty($record->commands)) {
                $commands = get_string('formnocommands', 'tool_abconfig');
            } else {
                $commands = $record->commands;
            }

            // Check for empty IPs
            if (empty($record->ipwhitelist)) {
                $iplist = get_string('formnoips', 'tool_abconfig');
            } else {
                $iplist = $record->ipwhitelist;
            }

            $table->data[] = array($record->condset, $iplist, $commands, $record->value);
        }

        return \html_writer::table($table);
    }
}

