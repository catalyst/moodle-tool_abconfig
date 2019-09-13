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
 * Testing file for hooks in lib.php
 *
 * @package    tool_abconfig
 * @copyright  2019 Peter Burnett <peterburnett@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__.'/../lib.php');

class tool_securityquestions_locallib_testcase extends advanced_testcase {

    public function test_request_no_experiment() {
        $this->resetAfterTest(true);
        global $DB, $CFG;

        // Setup a new user
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $preconfig = $CFG;

        // Execute hook call
        tool_abconfig_after_config();

        // Check Config wasnt changed by hook (more for unintended side effect regression testing)
        $this->assertSame($preconfig, $CFG);
    }

    /*public function test_request_admin_immunity() {
        $this->resetAfterTest(true);
        global $DB, $CFG;

        // Setup a new user
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Set config control to be modified by the experiment
        $CFG->passwordpolicy = 0;

        // Setup a valid experiment, and some conditions
        $eid = $DB->insert_record('tool_abconfig_experiment', array('name' => 'Experiment', 'shortname' => 'experiment', 'scope' => 'request', 'enabled' => 1));
        $commandstring = 'CFG,passwordpolicy,1';
        $commands = json_encode(explode(PHP_EOL, $commandstring));
        $DB->insert_record('tool_abconfig_condition', array('experiment' => $eid, 'ipwhitelist' => '0.0.0.1', 'commands' => $commands, 'set' => 0, 'value' => 100));

        // Call the hook
        tool_abconfig_after_config();

        // Test that the configuration was applied
        $this->assertTrue($CFG->passwordpolicy);

        // Manually set config back to false, then call hook again, and test
        // (simulates next page load)
        $CFG->passwordpolicy = 0;
        tool_abconfig_after_config();
        $this->assertTrue($CFG->passwordpolicy);
    }*/

    public function test_request_core_experiment() {

    }

    public function test_request_plugin_experiment() {

    }
    
    public function test_request_multi_condition() {

    }

    public function test_request_ip_whitelist() {

    }

    public function test_session_no_execute() {

    }

    public function test_session_admin_immunity () {

    }

    public function test_session_no_experiment() {

    }

    public function test_session_core_experiment() {

    }

    public function test_session_plugin_experiment() {

    }

    public function test_session_multi_condition() {

    }

    public function test_session_ip_whitelist() {

    }

    public function test_request_no_execute() {

    }
}