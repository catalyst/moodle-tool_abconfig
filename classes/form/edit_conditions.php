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
        global $DB;
        $eid = $this->_customdata['eid'];
        // Hidden element to track experiment id
        $mform->addElement('hidden', 'eid', $eid);
        $mform->setType('eid', PARAM_ALPHANUM);

        // Get Data for repeating elements
        $manager = new \tool_abconfig_experiment_manager();
        $records = $manager->get_conditions_for_experiment($eid);
        $setcount = 1;
        foreach ($records as $record) {
            // Hidden to track sets
            $id = $record->id;
            $mform->addElement('hidden', "hidden{$id}");
            $mform->setType("hidden{$id}", PARAM_ALPHANUM);

            // Hidden to track previous condset incase of change
            $mform->addElement('hidden', "prevshortname{$id}", $record->condset);
            $mform->setType("prevshortname{$id}", PARAM_ALPHANUM);

            // Section Header
            $mform->addElement('header', "header{$id}", get_string('formheader', 'tool_abconfig', $setcount));
            $mform->setExpanded("header{$id}");

            // Shortname
            $mform->addElement('text', "shortname{$id}", get_string('formexperimentcondsset', 'tool_abconfig'), array("size" => 20));
            $mform->setType("shortname{$id}", PARAM_ALPHANUM);
            $mform->setDefault("shortname{$id}", $record->condset);

            // IP Whitelist
            $mform->addElement('textarea', "iplist{$id}", get_string('formipwhitelist', 'tool_abconfig'), array('rows' => 3, 'cols' => 60));
            $mform->setType("iplist{$id}", PARAM_TEXT);
            $mform->setDefault("iplist{$id}", $record->ipwhitelist);

            // Commands
            $mform->addElement('textarea', "commands{$id}", get_string('formexperimentcommands', 'tool_abconfig'), array('rows' => 6, 'cols' => 60));
            $mform->setType("commands{$id}", PARAM_TEXT);
            if (!empty($record->commands)) {
                $mform->setDefault("commands{$id}", implode(PHP_EOL, json_decode($record->commands, true)));
            }

            // Value
            $mform->addElement('text', "value{$id}", get_string("formexperimentvalue", "tool_abconfig"), array("size" => 20));
            $mform->setType("value{$id}", PARAM_TEXT);
            $mform->setDefault("value{$id}", $record->value);

            // Delete
            $mform->addElement('advcheckbox', "delete{$id}", get_string("formdeleterepeat", "tool_abconfig"), '', array(), array(0, 1));
            $mform->setDefault("delete{$id}", 0);

            $setcount++;
        }

        // Initial elements count
        if (count($records) == 0) {
            $count = 1;
        } else {
            $count = 0;
        }

        // Setup repeating elements array
        $repeatarray = array();

        $repeatarray[] = $mform->createElement(
            "hidden",
            "repeatid"
        );

        $repeatarray[] = $mform->createElement(
            'header',
            'repeatheader',
            get_string('formnnewconditions', 'tool_abconfig')
        );

        $repeatarray[] = $mform->createElement(
            "text",
            "repeatshortname",
            get_string("formexperimentcondsset", "tool_abconfig"),
            array("size" => 20)
        );

        $repeatarray[] = $mform->createElement(
            "textarea",
            "repeatiplist",
            get_string("formipwhitelist", "tool_abconfig"),
            array(
                "placeholder" => '127.0.0.1',
                "rows" => 3,
                "cols" => 60
            )
        );

        $repeatarray[] = $mform->createElement(
            "textarea",
            "repeatcommands",
            get_string("formexperimentcommands", "tool_abconfig"),
            array(
                "placeholder" => 'CFG,passwordpolicy,true'.PHP_EOL.'forced_plugin_setting,auth_manual,expiration,yes'.PHP_EOL.'http_header,From,example@example.org'
                .PHP_EOL.'error_log,example error message'.PHP_EOL."js_header,console.log('example');".PHP_EOL."js_footer,console.log('example');",
                "rows" => 6,
                "cols" => 60
            )
        );

        $repeatarray[] = $mform->createElement(
            "text",
            "repeatvalue",
            get_string("formexperimentvalue", "tool_abconfig"),
            array(
                "size" => 20,
                "placeholder" => '50'
            )
        );

        $repeatarray[] = $mform->createElement(
            "advcheckbox",
            "repeatdelete",
            get_string("formdeleterepeat", "tool_abconfig"),
            '',
            array(),
            array(0, 1)
        );

        $repeatarray[] = $mform->addElement("html", "<hr>");

        $repeatoptions = array();
        $repeatoptions["repeatid"]["default"] = "{no}";
        $repeatoptions["repeatid"]["type"] = PARAM_INT;

        $repeatoptions["repeatheader"]["expanded"] = true;

        $repeatoptions["repeatshortname"]["type"] = PARAM_ALPHANUM;
        $repeatoptions["repeatiplist"]["type"] = PARAM_TEXT;
        $repeatoptions["repeatcommands"]["type"] = PARAM_TEXT;
        $repeatoptions["repeatvalue"]["type"] = PARAM_TEXT;

        $this->repeat_elements($repeatarray, $count, $repeatoptions, 'repeats', 'add_condition', 1, get_string('formaddrepeat', 'tool_abconfig'), false);

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        global $DB;
        $eid = $data['eid'];

        $total = 0;

        $manager = new \tool_abconfig_experiment_manager();
        $records = $manager->get_conditions_for_experiment($eid);

        // Validate edited form entries
        foreach ($records as $record) {
            // Check if record is being deleted, if so, ignore value
            $deletedkey = "delete{$record->id}";
            if ($data[$deletedkey]) {
                continue;
            }

            $key = "value{$record->id}";
            // Ensure value is numeric in correct range
            if ($data[$key] < 0 || $data[$key] > 100 || !is_numeric($data[$key])) {
                $errors[$key] = get_string('formexperimentvalueerror', 'tool_abconfig');
            }
            // Increment total and check value
            $total += $data[$key];
            if ($total > 100) {
                $errors[$key] = get_string('formexperimentvalueexceed', 'tool_abconfig', $total);
            }
        }

        // Validate added fields
        if (!empty($data['repeatid'])) {
            $repeats = array_keys($data['repeatid']);
            foreach ($repeats as $key => $value) {
                // Check if record is being deleted, if so, ignore value
                if ($data['repeatdelete'][$value]) {
                    continue;
                }
                // Ensure value is numeric in correct range
                if ($data['repeatvalue'][$value] < 0 || $data['repeatvalue'][$value] > 100 || !is_numeric($data['repeatvalue'][$value])) {
                    $errors["repeatvalue[$value]"] = get_string('formexperimentvalueerror', 'tool_abconfig');
                }
                // Increment total and check value
                $total += $data['repeatvalue'][$value];
                if ($total > 100) {
                    $errors["repeatvalue[$value]"] = get_string('formexperimentvalueexceed', 'tool_abconfig', $total);
                }
            }
        }

        return $errors;
    }
}

