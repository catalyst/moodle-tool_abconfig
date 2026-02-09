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

use PHPUnit\Framework\Attributes\DataProvider;

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../lib.php');

/**
 * Testing class for hooks in lib.php
 *
 * @covers \tool_abconfig\classes\experiment_manager
 * @package    tool_abconfig
 * @copyright  2019 Peter Burnett <peterburnett@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class experiment_manager_test extends advanced_testcase {
    public function test_add_experiment(): void {
        $this->resetAfterTest(true);
        global $DB;
        $manager = new tool_abconfig_experiment_manager();

        // Add an experiment.
        $manager->add_experiment('name', 'shortname', 'request');

        // Get record and verify fields.
        $sql = 'SELECT *
                  FROM {tool_abconfig_experiment}
                 WHERE ' . $DB->sql_compare_text('shortname') . ' = ' . $DB->sql_compare_text(':shortname');
        $record = $DB->get_record_sql($sql, ['shortname' => 'shortname']);

        $this->assertEquals($record->name, 'name');
        $this->assertEquals($record->shortname, 'shortname');
        $this->assertEquals($record->scope, 'request');
    }

    public function test_experiment_exists(): void {
        $this->resetAfterTest(true);
        global $DB;
        $manager = new tool_abconfig_experiment_manager();

        // Test response for non existent experiment.
        $this->assertFalse($manager->experiment_exists('shortname'));

        // Manually add experiment.
        $DB->insert_record(
            'tool_abconfig_experiment',
            ['name' => 'name', 'shortname' => 'shortname', 'scope' => 'request', 'enabled' => 0]
        );

        // Verify that experiment is found.
        $this->assertTrue($manager->experiment_exists('shortname'));

        // Now delete this record, and add a new one.
        $sql = 'DELETE FROM {tool_abconfig_experiment}
                 WHERE ' . $DB->sql_compare_text('shortname') . ' = ' . $DB->sql_compare_text(':shortname');
        $DB->execute($sql, ['shortname' => 'shortname']);

        $DB->insert_record(
            'tool_abconfig_experiment',
            ['name' => 'name2', 'shortname' => 'shortname2', 'scope' => 'request', 'enabled' => 0]
        );

        // Verify the first record still isnt found.
        $this->assertFalse($manager->experiment_exists('shortname'));
    }

    public function test_update_experiment(): void {
        $this->resetAfterTest(true);
        global $DB;
        $manager = new tool_abconfig_experiment_manager();

        // Manually add experiment.
        $DB->insert_record(
            'tool_abconfig_experiment',
            ['name' => 'name', 'shortname' => 'shortname', 'scope' => 'request', 'enabled' => 0]
        );

        // Update all the values of the experiment.
        $manager->update_experiment('shortname', 'name2', 'shortname2', 'session', 1, 1, 33);

        // Get record and verify fields.
        $sql = 'SELECT *
                  FROM {tool_abconfig_experiment}
                 WHERE ' . $DB->sql_compare_text('shortname') . ' = ' . $DB->sql_compare_text(':shortname');
        $record = $DB->get_record_sql($sql, ['shortname' => 'shortname2']);

        $this->assertEquals($record->name, 'name2');
        $this->assertEquals($record->shortname, 'shortname2');
        $this->assertEquals($record->scope, 'session');
        $this->assertEquals($record->enabled, 1);
        $this->assertEquals($record->adminenabled, 1);
        $this->assertEquals($record->numoffset, 33);
    }

    public function test_delete_experiment(): void {
        $this->resetAfterTest(true);
        global $DB;
        $manager = new tool_abconfig_experiment_manager();

        // Manually add experiment.
        $DB->insert_record(
            'tool_abconfig_experiment',
            ['name' => 'name', 'shortname' => 'shortname', 'scope' => 'request', 'enabled' => 0]
        );
        $DB->insert_record(
            'tool_abconfig_experiment',
            ['name' => 'name', 'shortname' => 'shortname2', 'scope' => 'request', 'enabled' => 0]
        );

        $manager->delete_experiment('shortname');

        // Check records to ensure only correct record deleted.
        $records = $DB->get_records('tool_abconfig_experiment');
        $this->assertEquals(1, count($records));
        $this->assertEquals('shortname2', reset($records)->shortname);
    }

    public function test_condition_exists(): void {
        $this->resetAfterTest(true);
        global $DB;
        $manager = new tool_abconfig_experiment_manager();

        // Manually add experiment.
        $eid = $DB->insert_record(
            'tool_abconfig_experiment',
            ['name' => 'name', 'shortname' => 'shortname', 'scope' => 'request', 'enabled' => 0]
        );

        // Check returns false for unfound condition set.
        $this->assertFalse($manager->condition_exists($eid, 'condset1'));

        // Manually add condition set.
        $DB->insert_record(
            'tool_abconfig_condition',
            ['experiment' => $eid, 'condset' => 'condset1', 'ipwhitelist' => '', 'commands' => '', 'value' => 50]
        );

        // Verify now found.
        $this->assertTrue($manager->condition_exists($eid, 'condset1'));
    }

    public function test_add_condition(): void {
        $this->resetAfterTest(true);
        global $DB;
        $manager = new tool_abconfig_experiment_manager();

        // Manually add experiment.
        $eid = $DB->insert_record(
            'tool_abconfig_experiment',
            ['name' => 'name', 'shortname' => 'shortname', 'scope' => 'request', 'enabled' => 0]
        );

        // Add condition for experiment.
        $manager->add_condition($eid, 'condset1', '', '', 50, '');

        $records = $DB->get_records(
            'tool_abconfig_condition',
            ['experiment' => $eid]
        );

        // Verify fields of inserted record.
        $this->assertEquals(count($records), 1);
        $this->assertEquals(reset($records)->condset, 'condset1');
        $this->assertEquals(reset($records)->ipwhitelist, '');
        $this->assertEquals(reset($records)->commands, '');
        $this->assertEquals(reset($records)->value, 50);
    }

    public function test_update_condition(): void {
        $this->resetAfterTest(true);
        global $DB;
        $manager = new tool_abconfig_experiment_manager();

        // Manually add experiment and condition.
        $eid = $DB->insert_record(
            'tool_abconfig_experiment',
            ['name' => 'name', 'shortname' => 'shortname', 'scope' => 'request', 'enabled' => 0]
        );
        $id = $DB->insert_record(
            'tool_abconfig_condition',
            ['experiment' => $eid, 'condset' => 'condset1', 'ipwhitelist' => '', 'commands' => '', 'value' => 50]
        );

        // Update condition.
        $manager->update_condition($eid, $id, 'condset1', 'condset2', '123.123.123.123', 'command', 51, '');

        $record = $DB->get_record('tool_abconfig_condition', ['experiment' => $eid]);
        $this->assertEquals($record->condset, 'condset2');
        $this->assertEquals($record->ipwhitelist, '123.123.123.123');
        $this->assertEquals($record->commands, '["command"]');
        $this->assertEquals($record->value, 51);
    }

    public function test_delete_condition(): void {
        $this->resetAfterTest(true);
        global $DB;
        $manager = new tool_abconfig_experiment_manager();

        // Manually add experiment and condition.
        $eid = $DB->insert_record(
            'tool_abconfig_experiment',
            ['name' => 'name', 'shortname' => 'shortname', 'scope' => 'request', 'enabled' => 0]
        );
        $DB->insert_record(
            'tool_abconfig_condition',
            ['experiment' => $eid, 'condset' => 'condset1', 'ipwhitelist' => '', 'commands' => '', 'value' => 50]
        );
        $DB->insert_record(
            'tool_abconfig_condition',
            ['experiment' => $eid, 'condset' => 'condset2', 'ipwhitelist' => '', 'commands' => '', 'value' => 50]
        );

        $manager->delete_condition($eid, 'condset1');

        // Check that only 1 was deleted, and that the remaining condition is not the deleted one.
        $records = $DB->get_records('tool_abconfig_condition', ['experiment' => $eid]);

        $this->assertEquals(count($records), 1);
        $this->assertEquals(reset($records)->condset, 'condset2');
    }

    public function test_delete_all_conditions(): void {
        $this->resetAfterTest(true);
        global $DB;
        $manager = new tool_abconfig_experiment_manager();

        // Manually add experiment and condition.
        $eid = $DB->insert_record(
            'tool_abconfig_experiment',
            ['name' => 'name', 'shortname' => 'shortname', 'scope' => 'request', 'enabled' => 0]
        );
        $DB->insert_record(
            'tool_abconfig_condition',
            ['experiment' => $eid, 'condset' => 'condset1', 'ipwhitelist' => '', 'commands' => '', 'value' => 50]
        );
        $DB->insert_record(
            'tool_abconfig_condition',
            ['experiment' => $eid, 'condset' => 'condset2', 'ipwhitelist' => '', 'commands' => '', 'value' => 50]
        );

        $manager->delete_all_conditions($eid);

        // Check that only 1 was deleted, and that the remaining condition is not the deleted one.
        $records = $DB->get_records('tool_abconfig_condition', ['experiment' => $eid]);

        $this->assertEquals(count($records), 0);
    }

    /**
     * Data provider for test_trim_condition_commands
     *
     * @return array
     */
    public static function trim_condition_commands_provider(): array {
        return [
            ['CFG,passwordpolicy,1', '["CFG,passwordpolicy,1"]'],
            ['forced_plugin_setting,auth_manual,expiration,yes', '["forced_plugin_setting,auth_manual,expiration,yes"]'],
            ["CFG,debug,32767\n\rCFG,debugdisplay,1", '["CFG,debug,32767","CFG,debugdisplay,1"]'],
            ["  CFG,debug,32767\n\rCFG,debugdisplay,1\r", '["CFG,debug,32767","CFG,debugdisplay,1"]'],
            ["\nCFG,debug,32767\nCFG,debugdisplay,1\n", '["","CFG,debug,32767","CFG,debugdisplay,1",""]'],
            ["  CFG,debug,32767\nCFG,debugdisplay,1\t", '["CFG,debug,32767","CFG,debugdisplay,1"]'],
        ];
    }

    /**
     * Test that condition commands get properly trimmed and converted into a JSON string on save.
     *
     * @param string $actual Actual string that needs to be stored in DB
     * @param string $expected Stored string
     */
    #[DataProvider('trim_condition_commands_provider')]
    public function test_trim_condition_commands(string $actual, string $expected): void {
        global $DB;
        $this->resetAfterTest();
        $manager = new tool_abconfig_experiment_manager();
        $experiment = $manager->add_experiment('name', 'shortname', 'request');
        $condition = $manager->add_condition($experiment, 'condset1', '', $actual, 50, '');

        $stored = $DB->get_field('tool_abconfig_condition', 'commands', ['id' => $condition]);
        $this->assertEquals($expected, $stored);
    }
}
