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

class tool_abconfig_lib_testcase extends advanced_testcase {

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
        set_config('passwordpolicy', 0);

        // Setup a valid experiment, and some conditions
        $eid = $DB->insert_record('tool_abconfig_experiment', array('name' => 'Experiment', 'shortname' => 'experiment', 'scope' => 'request', 'enabled' => 1));
        $commandstring = 'CFG,passwordpolicy,1';
        $commands = json_encode(explode(PHP_EOL, $commandstring));
        $DB->insert_record('tool_abconfig_condition', array('experiment' => $eid, 'ipwhitelist' => '0.0.0.1', 'commands' => $commands, 'condset' => 'set1', 'value' => 100));

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
        set_config('passwordpolicy', 0);

        // Setup a valid experiment, and some conditions
        $eid = $DB->insert_record('tool_abconfig_experiment', array('name' => 'Experiment', 'shortname' => 'experiment', 'scope' => 'request', 'enabled' => 1));
        $commandstring = 'CFG,passwordpolicy,1';
        $commands = json_encode(explode(PHP_EOL, $commandstring));
        $DB->insert_record('tool_abconfig_condition', array('experiment' => $eid, 'ipwhitelist' => '0.0.0.1', 'commands' => $commands, 'condset' => 'set1', 'value' => 100));

        // Call the hook
        tool_abconfig_after_config();

        // Test that the configuration was applied
        $this->assertEquals($CFG->passwordpolicy, 1);

        // Manually set config back to false, then call hook again, and test
        // (simulates next page load)
        unset($CFG->config_php_settings['passwordpolicy']);
        set_config('passwordpolicy', 0);

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
        $commandstring = 'forced_plugin_setting,auth_manual,expiration,yes';
        $commands = json_encode(explode(PHP_EOL, $commandstring));
        $DB->insert_record('tool_abconfig_condition', array('experiment' => $eid, 'ipwhitelist' => '0.0.0.1', 'commands' => $commands, 'condset' => 'set1', 'value' => 100));

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
        set_config('expiration', 'no', 'auth_manual');

        // Set config control to be modified by the experiment
        set_config('passwordpolicy', 0);

        // Setup a valid experiment, and multi conditions
        $eid = $DB->insert_record('tool_abconfig_experiment', array('name' => 'Experiment', 'shortname' => 'experiment', 'scope' => 'request', 'enabled' => 1));

        $commandstring = 'forced_plugin_setting,auth_manual,expiration,yes';
        $commands = json_encode(explode(PHP_EOL, $commandstring));
        $DB->insert_record('tool_abconfig_condition', array('experiment' => $eid, 'ipwhitelist' => '0.0.0.1', 'commands' => $commands, 'condset' => 'set1', 'value' => 100));

        $commandstringcore = 'CFG,passwordpolicy,1';
        $commandscore = json_encode(explode(PHP_EOL, $commandstringcore));
        $DB->insert_record('tool_abconfig_condition', array('experiment' => $eid, 'ipwhitelist' => '0.0.0.1', 'commands' => $commandscore, 'condset' => 'set2', 'value' => 0));

        // Now execute first hook, and check the plugin value
        tool_abconfig_after_config();
        $this->assertEquals(get_config('auth_manual', 'expiration'), 'yes');
        $this->assertEquals($CFG->passwordpolicy, 0);

        // Update value fields so that core hook executes
        $sqlcondition1 = $DB->sql_compare_text('set1', strlen('set1'));
        $DB->set_field_select('tool_abconfig_condition', 'value', 0, 'condset = ? AND experiment = ?', array($sqlcondition1, $eid));

        $sqlcondition2 = $DB->sql_compare_text('set2', strlen('set2'));
        $DB->set_field_select('tool_abconfig_condition', 'value', 100, 'condset = ? AND experiment = ?', array($sqlcondition2, $eid));

        // Reset configs
        // unset forced_plugin_settings so it can be forced again by the plugin
        unset($CFG->forced_plugin_settings['auth_manual']['expiration']);
        set_config('expiration', 'no', 'auth_manual');
        unset($CFG->config_php_settings['passwordpolicy']);
        set_config('passwordpolicy', 0);

        // Purge caches to avoid caching issues with changing experiments
        purge_all_caches();
        // Now execute second hook, and check the plugin value
        tool_abconfig_after_config();
        $this->assertEquals(get_config('auth_manual', 'expiration'), 'no');
        $this->assertEquals($CFG->passwordpolicy, 1);

        // Reset configs
        unset($CFG->forced_plugin_settings['auth_manual']['expiration']);
        set_config('expiration', 'no', 'auth_manual');
        unset($CFG->config_php_settings['passwordpolicy']);
        set_config('passwordpolicy', 0);

        // Update value fields so either one may fire equally
        $sqlcondition3 = $DB->sql_compare_text('set1', strlen('set1'));
        $DB->set_field_select('tool_abconfig_condition', 'value', 50, 'condset = ? AND experiment = ?', array($sqlcondition3, $eid));

        $sqlcondition4 = $DB->sql_compare_text('set2', strlen('set2'));
        $DB->set_field_select('tool_abconfig_condition', 'value', 50, 'condset = ? AND experiment = ?', array($sqlcondition4, $eid));

        // Now execute second hook, and check the plugin value
        tool_abconfig_after_config();
        $this->assertTrue((get_config('auth_manual', 'expiration') == 'yes') xor ($CFG->passwordpolicy == 1));
    }

    public function test_request_multi_command() {
        $this->resetAfterTest(true);
        global $DB, $CFG;
        $_SERVER['REMOTE_ADDR'] = '123.123.123.123';

        // Setup a new user
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Set plugin config control to be modified by the experiment
        set_config('expiration', 'no', 'auth_manual');
        // Set config control to be modified by the experiment
        set_config('passwordpolicy', 0);

        // Setup a valid experiment, and multi conditions
        $eid = $DB->insert_record('tool_abconfig_experiment', array('name' => 'Experiment', 'shortname' => 'experiment', 'scope' => 'request', 'enabled' => 1));

        $commandstring = 'forced_plugin_setting,auth_manual,expiration,yes'.PHP_EOL.'CFG,passwordpolicy,1';
        $commands = json_encode(explode(PHP_EOL, $commandstring));
        $DB->insert_record('tool_abconfig_condition', array('experiment' => $eid, 'ipwhitelist' => '0.0.0.1', 'commands' => $commands, 'condset' => 'set1', 'value' => 100));

        // Now execute first hook, and check the plugin value
        tool_abconfig_after_config();
        $this->assertEquals(get_config('auth_manual', 'expiration'), 'yes');
        $this->assertEquals($CFG->passwordpolicy, 1);

        // Reset configs
        unset($CFG->forced_plugin_settings['auth_manual']['expiration']);
        set_config('expiration', 'no', 'auth_manual');
        unset($CFG->config_php_settings['passwordpolicy']);
        set_config('passwordpolicy', 0);

        // Now execute second hook, and check the plugin value
        tool_abconfig_after_config();
        $this->assertEquals(get_config('auth_manual', 'expiration'), 'yes');
        $this->assertEquals($CFG->passwordpolicy, 1);
    }

    public function test_request_ip_whitelist() {
        $this->resetAfterTest(true);
        global $DB, $CFG;
        $_SERVER['REMOTE_ADDR'] = '123.123.123.123';

        // Setup a new user
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Set config to test against
        set_config('passwordpolicy', 0);

        // Setup a valid experiment, and multi conditions
        $eid = $DB->insert_record('tool_abconfig_experiment', array('name' => 'Experiment', 'shortname' => 'experiment', 'scope' => 'request', 'enabled' => 1));

        $commandstring = 'CFG,passwordpolicy,1';
        $commands = json_encode(explode(PHP_EOL, $commandstring));
        $DB->insert_record('tool_abconfig_condition', array('experiment' => $eid, 'ipwhitelist' => '123.123.123.123',
            'commands' => $commands, 'condset' => 'set1', 'value' => 100));

        // Now execute first hook, and check core value hasnt changed
        tool_abconfig_after_config();

        $this->assertEquals($CFG->passwordpolicy, 0);

        // Now update condition field to remove ip whitelist, and check that value is updated
        $sqlcondition = $DB->sql_compare_text('set1', strlen('set1'));
        $DB->set_field_select('tool_abconfig_condition', 'ipwhitelist', '', 'condset = ? AND experiment = ?', array($sqlcondition, $eid));

        // Purge caches to avoid caching issues with changing experiments
        purge_all_caches();
        tool_abconfig_after_config();
        $this->assertEquals($CFG->passwordpolicy, 1);
    }

    public function test_session_no_execute() {
        $this->resetAfterTest(true);
        global $DB, $CFG;
        $_SERVER['REMOTE_ADDR'] = '123.123.123.123';

        // Setup a new user
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $preconfig = $CFG;

        // Execute hook call
        tool_abconfig_after_require_login();

        // Check Config wasnt changed by hook (more for unintended side effect regression testing)
        $this->assertSame($preconfig, $CFG);
    }

    public function test_session_admin_immunity () {
        $this->resetAfterTest(true);
        global $DB, $CFG;
        $_SERVER['REMOTE_ADDR'] = '123.123.123.123';

        $this->setAdminUser();

        // Set config control to be modified by the experiment
        set_config('passwordpolicy', 0);

        // Setup a valid experiment, and some conditions
        $eid = $DB->insert_record('tool_abconfig_experiment', array('name' => 'Experiment', 'shortname' => 'experiment', 'scope' => 'session', 'enabled' => 1));
        $commandstring = 'CFG,passwordpolicy,1';
        $commands = json_encode(explode(PHP_EOL, $commandstring));
        $DB->insert_record('tool_abconfig_condition', array('experiment' => $eid, 'ipwhitelist' => '0.0.0.1', 'commands' => $commands, 'condset' => 'set1', 'value' => 100));

        // Call the hook
        tool_abconfig_after_require_login();

        // Test that the configuration was NOT applied
        $this->assertEquals($CFG->passwordpolicy, 0);
    }

    public function test_session_core_experiment() {
        $this->resetAfterTest(true);
        global $DB, $CFG;
        $_SERVER['REMOTE_ADDR'] = '123.123.123.123';

        // Setup a new user
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Set config control to be modified by the experiment
        set_config('passwordpolicy', 0);

        // Setup a valid experiment, and some conditions
        $eid = $DB->insert_record('tool_abconfig_experiment', array('name' => 'Experiment', 'shortname' => 'experiment', 'scope' => 'session', 'enabled' => 1));
        $commandstring = 'CFG,passwordpolicy,1';
        $commands = json_encode(explode(PHP_EOL, $commandstring));
        $DB->insert_record('tool_abconfig_condition', array('experiment' => $eid, 'ipwhitelist' => '0.0.0.1', 'commands' => $commands, 'condset' => 'set1', 'value' => 100));

        $commandstring2 = 'CFG,passwordpolicy,0';
        $commands2 = json_encode(explode(PHP_EOL, $commandstring));
        $DB->insert_record('tool_abconfig_condition', array('experiment' => $eid, 'ipwhitelist' => '0.0.0.1', 'commands' => $commands2, 'condset' => 'set2', 'value' => 0));

        // Call the hook and verify result
        tool_abconfig_after_require_login();
        $this->assertEquals($CFG->passwordpolicy, 1);

        // Now update the values to force the opposite control, and ensure actual config doesnt change
        $sqlcondition1 = $DB->sql_compare_text('set1', strlen('set1'));
        $DB->set_field_select('tool_abconfig_condition', 'value', 0, 'condset = ? AND experiment = ?', array($sqlcondition1, $eid));

        $sqlcondition2 = $DB->sql_compare_text('set2', strlen('set2'));
        $DB->set_field_select('tool_abconfig_condition', 'value', 100, 'condset = ? AND experiment = ?', array($sqlcondition2, $eid));

        // Call the hook and test for no change
        tool_abconfig_after_require_login();
        $this->assertEquals($CFG->passwordpolicy, 1);

        // Now set control manually to incorrect state, as if another page load performed, and test correct behaviour is set in the after_config hook
        unset($CFG->config_php_settings['passwordpolicy']);
        set_config('passwordpolicy', 0);
        tool_abconfig_after_config();
        $this->assertEquals($CFG->passwordpolicy, 1);
    }

    public function test_session_plugin_experiment() {
        $this->resetAfterTest(true);
        global $DB, $CFG;
        $_SERVER['REMOTE_ADDR'] = '123.123.123.123';

        // Setup a new user
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Set plugin config control to be modified by the experiment
        set_config('expiration', 'no', 'auth_manual');

        // Setup a valid experiment, and some conditions
        $eid = $DB->insert_record('tool_abconfig_experiment', array('name' => 'Experiment', 'shortname' => 'experiment', 'scope' => 'session', 'enabled' => 1));
        $commandstring = 'forced_plugin_setting,auth_manual,expiration,yes';
        $commands = json_encode(explode(PHP_EOL, $commandstring));
        $DB->insert_record('tool_abconfig_condition', array('experiment' => $eid, 'ipwhitelist' => '0.0.0.1', 'commands' => $commands, 'condset' => 'set1', 'value' => 100));

        $commandstring2 = 'forced_plugin_setting,auth_manual,expiration,no';
        $commands2 = json_encode(explode(PHP_EOL, $commandstring));
        $DB->insert_record('tool_abconfig_condition', array('experiment' => $eid, 'ipwhitelist' => '0.0.0.1', 'commands' => $commands2, 'condset' => 'set2', 'value' => 0));

        // Call the hook and verify result
        tool_abconfig_after_require_login();
        $this->assertEquals(get_config('auth_manual', 'expiration'), 'yes');

        // Change experiment conditions so the other set always fires
        $sqlcondition1 = $DB->sql_compare_text('set1', strlen('set1'));
        $DB->set_field_select('tool_abconfig_condition', 'value', 0, 'condset = ? AND experiment = ?', array($sqlcondition1, $eid));

        $sqlcondition2 = $DB->sql_compare_text('set2', strlen('set2'));
        $DB->set_field_select('tool_abconfig_condition', 'value', 100, 'condset = ? AND experiment = ?', array($sqlcondition2, $eid));

        // Now execute hook again and check that it remains the same as first call
        tool_abconfig_after_require_login();
        $this->assertEquals(get_config('auth_manual', 'expiration'), 'yes');

        // Reset config (simulates page load)
        unset($CFG->forced_plugin_settings['auth_manual']['expiration']);
        set_config('expiration', 'no', 'auth_manual');

        // Now test that the after_config hook sets to the correct session conditions
        tool_abconfig_after_config();
        $this->assertEquals(get_config('auth_manual', 'expiration'), 'yes');
    }

    public function test_session_multi_command() {
        $this->resetAfterTest(true);
        global $DB, $CFG;
        $_SERVER['REMOTE_ADDR'] = '123.123.123.123';

        // Setup a new user
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Set config control to be modified by the experiment
        set_config('passwordpolicy', 0);

        // Set plugin config control to be modified by the experiment
        set_config('expiration', 'no', 'auth_manual');

        // Setup a valid experiment, and some conditions
        $eid = $DB->insert_record('tool_abconfig_experiment', array('name' => 'Experiment', 'shortname' => 'experiment', 'scope' => 'session', 'enabled' => 1));
        $commandstring = 'CFG,passwordpolicy,1'.PHP_EOL.'forced_plugin_setting,auth_manual,expiration,yes';
        $commands = json_encode(explode(PHP_EOL, $commandstring));
        $DB->insert_record('tool_abconfig_condition', array('experiment' => $eid, 'ipwhitelist' => '0.0.0.1', 'commands' => $commands, 'condset' => 'set1', 'value' => 100));

        $commandstring2 = 'forced_plugin_setting,auth_manual,expiration,yes';
        $commands2 = json_encode(explode(PHP_EOL, $commandstring));
        $DB->insert_record('tool_abconfig_condition', array('experiment' => $eid, 'ipwhitelist' => '0.0.0.1', 'commands' => $commands2, 'condset' => 'set2', 'value' => 0));

        // Execute the hook and test that the session config applied
        tool_abconfig_after_require_login();
        $this->assertEquals(get_config('auth_manual', 'expiration'), 'yes');
        $this->assertEquals($CFG->passwordpolicy, 1);

        // Manually reset settings (simulates new page load)
        unset($CFG->forced_plugin_settings['auth_manual']['expiration']);
        set_config('expiration', 'no', 'auth_manual');
        unset($CFG->config_php_settings['passwordpolicy']);
        set_config('passwordpolicy', 0);

        // Test that after_config correctly applies both settings
        tool_abconfig_after_config();
        $this->assertEquals(get_config('auth_manual', 'expiration'), 'yes');
        $this->assertEquals($CFG->passwordpolicy, 1);
    }

    public function test_session_multi_condition() {
        $this->resetAfterTest(true);
        global $DB, $CFG;
        $_SERVER['REMOTE_ADDR'] = '123.123.123.123';

        // Setup a new user
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Set config control to be modified by the experiment
        set_config('passwordpolicy', 0);

        // Set plugin config control to be modified by the experiment
        set_config('expiration', 'no', 'auth_manual');

        // Setup a valid experiment, and some conditions
        $eid = $DB->insert_record('tool_abconfig_experiment', array('name' => 'Experiment', 'shortname' => 'experiment', 'scope' => 'session', 'enabled' => 1));
        $commandstring = 'CFG,passwordpolicy,1';
        $commands = json_encode(explode(PHP_EOL, $commandstring));
        $DB->insert_record('tool_abconfig_condition', array('experiment' => $eid, 'ipwhitelist' => '0.0.0.1', 'commands' => $commands, 'condset' => 0, 'value' => 100));

        $commandstring2 = 'forced_plugin_setting,auth_manual,expiration,yes';
        $commands2 = json_encode(explode(PHP_EOL, $commandstring));
        $DB->insert_record('tool_abconfig_condition', array('experiment' => $eid, 'ipwhitelist' => '0.0.0.1', 'commands' => $commands2, 'condset' => 1, 'value' => 0));

        // Execute the hook, test only one condition set executed
        tool_abconfig_after_require_login();
        $this->assertEquals(get_config('auth_manual', 'expiration'), 'no');
        $this->assertEquals($CFG->passwordpolicy, 1);

        // Manually reset settings (simulates new page load)
        unset($CFG->forced_plugin_settings['auth_manual']['expiration']);
        $CFG->forced_plugin_settings['auth_manual']['expiration'] = 'no';
        unset($CFG->config_php_settings['passwordpolicy']);
        set_config('passwordpolicy', 0);

        // Test that after_config only applies one setting
        tool_abconfig_after_config();
        $this->assertEquals(get_config('auth_manual', 'expiration'), 'no');
        $this->assertEquals($CFG->passwordpolicy, 1);
    }

    public function test_session_ip_whitelist() {
        $this->resetAfterTest(true);
        global $DB, $CFG;
        $_SERVER['REMOTE_ADDR'] = '123.123.123.123';

        // Setup a new user
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Set config control to be modified by the experiment
        set_config('passwordpolicy', 0);

        // Setup a valid experiment, and some conditions
        $eid = $DB->insert_record('tool_abconfig_experiment', array('name' => 'Experiment', 'shortname' => 'experiment', 'scope' => 'session', 'enabled' => 1));
        $commandstring = 'CFG,passwordpolicy,1';
        $commands = json_encode(explode(PHP_EOL, $commandstring));
        $DB->insert_record('tool_abconfig_condition', array('experiment' => $eid, 'ipwhitelist' => '123.123.123.123',
            'commands' => $commands, 'condset' => 'set1', 'value' => 100));

        // Execute the hook and check that nothing was changed
        tool_abconfig_after_require_login();
        $this->assertEquals($CFG->passwordpolicy, 0);

        // Test that the after_config hook also doesnt execute
        tool_abconfig_after_config();
        $this->assertEquals($CFG->passwordpolicy, 0);
    }
}