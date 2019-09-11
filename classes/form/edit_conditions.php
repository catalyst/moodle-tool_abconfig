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

class edit_conditions extends \moodleform {
    public function definition() {
        $mform = $this->_form;

        // Setup repeating elements array
        $repeatarray = array();

        $repeatarray[] = $mform->createElement(
            "hidden",
            "repeatid"
        );

        $repeatarray[] = $mform->createElement(
            "text",
            "repeatshortname",
            get_string("formexperimentcondsset", "tool_abconfig"),
            array("size" => 40)
        );

        $repeatarray[] = $mform->createElement(
            "textarea",
            "repeatcommands",
            get_string("formexperimentcommands", "tool_abconfig"),
            array("size" => 40)
        );

        $repeatarray[] = $mform->createElement(
            "text",
            "repeatvalue",
            get_string("formexperimentvalue", "tool_abconfig"),
            array(
                "size" => 40
            )
        );

        $repeatarray[] = $mform->createElement(
            "advcheckbox",
            "repeatdelete",
            get_string("setdeleted", "local_envbar"),
            '',
            array(),
            array(0, 1)
        );

        $repeatarray[] = $mform->addElement("html", "<hr>");
        $repeatoptions = array();

        $this->repeat_elements($repeatarray, 1, $repeatoptions, 'repeats', 'add_condition', 1, 'add', false);

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }
}