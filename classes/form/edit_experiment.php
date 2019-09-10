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
        $mform->addElement('hidden', 'shortname', '');
        $mform->setType('shortname', PARAM_TEXT);
        $mform->addElement('hidden', 'id', '');
        $mform->setType('id', PARAM_INT);

        $eid = $this->_customdata['eid'];

        // Display the basic experiment information
        $mform->addElement('header', 'experimentinfo', get_string('formexperimentinfo', 'tool_abconfig'));
        $mform->addElement('static', 'experimentname', get_string('name', 'tool_abconfig'), '');
        $mform->addElement('static', 'experimentshortname', get_string('shortname', 'tool_abconfig'), '');
        $mform->addElement('static', 'experimentscope', get_string('scope', 'tool_abconfig'), '');
        
        // Enabled checkbox
        $mform->addElement('advcheckbox', 'enabled', get_string('formexperimentenabled', 'tool_abconfig'));

        // Experiment conditions
        $mform->addElement('header', 'experimentconds', get_string('formexperimentconds', 'tool_abconfig'));

        // Condition set to edit
        $mform->addElement('text', 'set', get_string('formexperimentcondsset', 'tool_abconfig'));
        $mform->setType('set', PARAM_INT);

        // Ip Whitelist field
        $mform->addElement('textarea', 'experimentipwhitelist', get_string('formipwhitelist', 'tool_abconfig'));
        $mform->setType('experimentipwhitelist', PARAM_TEXT);

        // Commands field
        $mform->addElement('textarea', 'experimentcommands', get_string('formexperimentcommands', 'tool_abconfig'));
        $mform->setType('experimentcommands', PARAM_RAW);

        // Value field
        $mform->addElement('text', 'experimentvalue', get_string('formexperimentvalue', 'tool_abconfig'));
        $mform->setType('experimentvalue', PARAM_TEXT);
        $mform->addRule('experimentvalue', get_string('formexperimentvalueerror', 'tool_abconfig'), 'numeric');

        $mform->addElement('html', $this->generate_table($eid));

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $value = $data['experimentvalue'];
        $shortname = $data['shortname'];
        $eid = $data['id'];

        global $DB;
        
        // ==================================================VALUE VALIDATION=======================================================

        // Check value is inside accepted range
        if ($value < 0 || $value > 100 || !is_numeric($value)) {
            $errors['experimentvalue'] = get_string('formexperimentvalueerror', 'tool_abconfig');
        }

        $records = $DB->get_records('tool_abconfig_condition', array('experiment' => $eid), 'set ASC');
        
        $total = 0;
        foreach ($records as $record) {
            // If record is already present for form 'set', ignore value, update rather than addition
            if ($record->set != $data['set']) {
                $total += $record->value;
            }
        }

        if (($total + $value) > 100) {
            $errors['experimentvalue'] = get_string('formexperimentvalueexceed', 'tool_abconfig', ($total + $value));
        }

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
        $records = $DB->get_records('tool_abconfig_condition', array('experiment' => $eid), 'set ASC');
        foreach ($records as $record) {
            $table->data[] = array($record->set, $record->ipwhitelist, $record->commands, $record->value);
        }
        
        return \html_writer::table($table);
    }
}

