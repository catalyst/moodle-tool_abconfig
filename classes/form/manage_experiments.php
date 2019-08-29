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
 * Form for managing experiments
 *
 * @package   tool_abconfig
 * @author    Peter Burnett <peterburnett@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_abconfig\form;

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/formslib.php");

class manage_experiments extends \moodleform {

    public function definition() {
        $mform = $this->_form;

        // Setup Data array for scopes
        $scopes = ['request' => get_string('request', 'tool_abconfig'), 'session' => get_string('session', 'tool_abconfig')];

        // Add section for adding experiments
        $mform->addElement('header', 'addexperiment', get_string('formaddexperiment', 'tool_abconfig'));

        // Name
        $mform->addElement('text', 'experimentname', get_string('formexperimentname', 'tool_abconfig'));
        $mform->setType('experimentname', PARAM_TEXT);
        $mform->setDefault('experimentname', 'Experiment');
        $mform->addRule('experimentname', get_string('formexperimentnamereq', 'tool_abconfig'), 'required', null, 'client');

        // Short Name
        $mform->addElement('text', 'experimentshortname', get_string('formexperimentshortname', 'tool_abconfig'));
        $mform->setType('experimentshortname', PARAM_TEXT);
        $mform->setDefault('experimentshortname', 'experiment');
        $mform->addRule('experimentshortname', get_string('formexperimentshortnamereq', 'tool_abconfig'), 'required', null, 'client');

        // Select Scope
        $mform->addElement('select', 'scope', get_string('formexperimentscopeselect', 'tool_abconfig'), $scopes);

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        global $DB;

        $shortname = $data['experimentshortname'];
        $sqlexperiment = $DB->sql_compare_text($shortname, strlen($shortname));
        $record = $DB->get_record_sql('SELECT * FROM {tool_abconfig_experiments} WHERE shortname = ?', array($sqlexperiment));

        if (!empty($record)) {
            $errors['experimentshortname'] = get_string('formexperimentalreadyexists', 'tool_abconfig');
        }

        $experiments = $DB->get_records('tool_abconfig_experiments');

        return $errors;
    }
}

