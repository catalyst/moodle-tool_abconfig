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

/**
 * Form class for managing experiments
 *
 * @package   tool_abconfig
 * @author    Peter Burnett <peterburnett@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage_experiments extends \moodleform {

    /**
     * Form definition
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        // Setup Data array for scopes.
        $scopes = ['request' => get_string('request', 'tool_abconfig'), 'session' => get_string('session', 'tool_abconfig')];

        // Add section for adding experiments.
        $mform->addElement('header', 'addexperiment', get_string('formaddexperiment', 'tool_abconfig'));

        // Name.
        $mform->addElement('text', 'experimentname',
            get_string('formexperimentname', 'tool_abconfig'), array('placeholder' => 'Experiment'));
        $mform->setType('experimentname', PARAM_TEXT);
        $mform->addRule('experimentname', get_string('formexperimentnamereq', 'tool_abconfig'), 'required', null, 'client');

        // Short Name.
        $mform->addElement('text', 'experimentshortname',
            get_string('formexperimentshortname', 'tool_abconfig'), array('placeholder' => 'experiment'));
        $mform->setType('experimentshortname', PARAM_ALPHANUM);
        $mform->addRule('experimentshortname',
            get_string('formexperimentshortnamereq', 'tool_abconfig'), 'required', null, 'client');

        // Select Scope.
        $mform->addElement('select', 'scope', get_string('formexperimentscopeselect', 'tool_abconfig'), $scopes);

        $this->add_action_buttons(true, get_string('formaddexperiment', 'tool_abconfig'));
    }

    /**
     * Form validation
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $manager = new \tool_abconfig_experiment_manager();

        $shortname = $data['experimentshortname'];
        if ($manager->experiment_exists($shortname)) {
            $errors['experimentshortname'] = get_string('formexperimentalreadyexists', 'tool_abconfig');
        }

        return $errors;
    }
}
