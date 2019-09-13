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

    public function test_request_admin_immunity() {
        $this->resetAfterTest(true);
        global $DB, $CFG;
        $_SERVER['REMOTE_ADDR'] = '123.123.123.123';

        $this->setAdminUser();

        // Set config control to be modified by the experiment
        $CFG->passwordpolicy = 0;

        // Setup a valid experiment, and some conditions
        $eid = $DB->insert_record('tool_abconfig_experiment', array('name' => 'Experiment', 'shortname' => 'experiment', 'scope' => 'request', 'enabled' => 1));
        $commandstring = 'CFG,passwordpolicy,1';
        $commands = json_encode(explode(PHP_EOL, $commandstring));
        $DB->insert_record('tool_abconfig_condition', array('experiment' => $eid, 'ipwhitelist' => '0.0.0.1', 'commands' => $commands, 'set' => 0, 'value' => 100));

        // Call the hook
        tool_abconfig_after_config();

        // Test that the configuration was NOT applied
        $this->assertEquals($CFG->passwordpolicy, 0);
    }

    public function test_request_core_experiment() {
        $this->resetAfterTest(true);
        global $DB, $CFG;
        $_SERVER['REMOTE_ADDR'] = '123.123.123.123';

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
        $this->assertEquals($CFG->passwordpolicy, 1);

        // Manually set config back to false, then call hook again, and test
        // (simulates next page load)
        $CFG->passwordpolicy = 0;
        tool_abconfig_after_config();
        $this->assertEquals($CFG->passwordpolicy, 1);
    }

    public function test_request_plugin_experiment() {
        $this->resetAfterTest(true);
        global $DB, $CFG;
        $_SERVER['REMOTE_ADDR'] = '123.123.123.123';

        // Setup a new user
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Set plugin config control to be modified by the experiment
        set_config('expiration', 'no', 'auth_manual');

        // Setup a valid experiment, and some conditions
        $eid = $DB->insert_record('tool_abconfig_experiment', array('name' => 'Experiment', 'shortname' => 'experiment', 'scope' => 'request', 'enabled' => 1));
        $commandstring = 'auth_manual,expiration,yes';
        $commands = json_encode(explode(PHP_EOL, $commandstring));
        $DB->insert_record('tool_abconfig_condition', array('experiment' => $eid, 'ipwhitelist' => '0.0.0.1', 'commands' => $commands, 'set' => 0, 'value' => 100));

        // Call the hook
        tool_abconfig_after_config();

        // Test that the configuration was applied
        $this->assertEquals(get_config('auth_manual', 'expiration'), 'yes');

        // Manually set config back to false, then call hook again, and test
        // (simulates next page load)
        set_config('expiration', 'no', 'auth_manual');
        tool_abconfig_after_config();
        $this->assertEquals(get_config('auth_manual', 'expiration'), 'yes');
    }
    
    public function test_request_multi_condition() {
        $this->resetAfterTest(true);
        global $DB, $CFG;
        $_SERVER['REMOTE_ADDR'] = '123.123.123.123';

        // Setup a new user
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Set plugin config control to be modified by the experiment
        $CFG->forced_plugin_settings['auth_manual']['expiration'] = 'no';
        // Set config control to be modified by the experiment
        $CFG->passwordpolicy = 0;

        // Setup a valid experiment, and multi conditions
        $eid = $DB->insert_record('tool_abconfig_experiment', array('name' => 'Experiment', 'shortname' => 'experiment', 'scope' => 'request', 'enabled' => 1));
        
        $commandstring = 'auth_manual,expiration,yes';
        $commands = json_encode(explode(PHP_EOL, $commandstring));
        $DB->insert_record('tool_abconfig_condition', array('experiment' => $eid, 'ipwhitelist' => '0.0.0.1', 'commands' => $commands, 'set' => 0, 'value' => 100));

        $commandstringcore = 'CFG,passwordpolicy,1';
        $commandscore = json_encode(explode(PHP_EOL, $commandstringcore));
        $DB->insert_record('tool_abconfig_condition', array('experiment' => $eid, 'ipwhitelist' => '0.0.0.1', 'commands' => $commandscore, 'set' => 1, 'value' => 0));

        // Now execute first hook, and check the plugin value
        tool_abconfig_after_config();
        $this->assertEquals(get_config('auth_manual', 'expiration'), 'yes');
        $this->assertEquals($CFG->passwordpolicy, 0);

        // Update value fields so that core hook executes
        $DB->set_field('tool_abconfig_condition', 'value', 0, array('experiment' => $eid, 'set' => 0));
        $DB->set_field('tool_abconfig_condition', 'value', 100, array('experiment' => $eid, 'set' => 1));

        // Reset configs
        $CFG->forced_plugin_settings['auth_manual']['expiration'] = 'no';
        $CFG->passwordpolicy = 0;

        // Now execute second hook, and check the plugin value
        tool_abconfig_after_config();
        $this->assertEquals(get_config('auth_manual', 'expiration'), 'no');
        $this->assertEquals($CFG->passwordpolicy, 1);

        // Reset configs
        $CFG->forced_plugin_settings['auth_manual']['expiration'] = 'no';
        $CFG->passwordpolicy = 0;

        // Update value fields so either one may fire equally
        $DB->set_field('tool_abconfig_condition', 'value', 50, array('experiment' => $eid, 'set' => 0));
        $DB->set_field('tool_abconfig_condition', 'value', 50, array('experiment' => $eid, 'set' => 1));

        // Now execute second hook, and check the plugin value
        tool_abconfig_after_config();
        $this->assertTrue((get_config('auth_manual', 'expiration') == 'yes') xor ($CFG->passwordpolicy == 1));
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