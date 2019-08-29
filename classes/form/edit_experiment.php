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
        $mform->addElement('hidden', 'shortname', $this->_customdata['shortname']);
        $mform->setType('shortname', PARAM_TEXT);

        // Experiment data
        $name = $this->_customdata['name'];
        $shortname = $this->_customdata['shortname'];
        $scope = $this->_customdata['scope'];

        // Condition data
        $ipwhitelist = $this->_customdata['ipwhitelist'];
        $commands = $this->_customdata['commands'];
        $value = $this->_customdata['value'];

        // Display the basic experiment information
        $mform->addElement('header', 'experimentinfo', get_string('formexperimentinfo', 'tool_abconfig'));
        $mform->addElement('static', 'experimentname', get_string('name', 'tool_abconfig'), $name);
        $mform->addElement('static', 'experimentshortname', get_string('shortname', 'tool_abconfig'), $shortname);
        $mform->addElement('static', 'experimentscope', get_string('scope', 'tool_abconfig'), $scope);

        // Experiment conditions
        $mform->addElement('header', 'experimentconds', get_string('formexperimentconds', 'tool_abconfig'));
        // Ip Whitelist field
        $mform->addElement('textarea', 'experimentipwhitelist', get_string('formipwhitelist', 'tool_abconfig'));
        $mform->setType('ipwhitelist', PARAM_TEXT);
        $mform->setDefault('experimentipwhitelist', $ipwhitelist);
        // Commands field
        $mform->addElement('textarea', 'experimentcommands', get_string('formexperimentcommands', 'tool_abconfig'));
        $mform->setType('experimentcommands', PARAM_RAW);
        $mform->setDefault('experimentcommands', $commands);

        // Value field
        $mform->addElement('text', 'experimentvalue', get_string('formexperimentvalue', 'tool_abconfig'));
        $mform->setType('experimentvalue', PARAM_TEXT);
        $mform->addRule('experimentvalue', get_string('formexperimentvalueerror', 'tool_abconfig'), 'client');
        $mform->setDefault('experimentvalue', $value);

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $value = $data['experimentvalue'];

        // Check value is inside accepted range
        if ($value < 0 || $value > 100 || !is_numeric($value)) {
            $errors['experimentvalue'] = get_string('formexperimentvalueerror', 'tool_abconfig');
        }

        return $errors;
    }
}

