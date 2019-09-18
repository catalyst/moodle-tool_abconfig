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

class tool_abconfig_experiment_manager_testcase extends advanced_testcase {

    public function test_add_experiment() {
        $this->resetAfterTest(true);
        global $DB;
        $manager = new tool_abconfig_experiment_manager();

        // Add an experiment
        $manager->add_experiment('name', 'shortname', 'request');

        // Get record and verify fields
        $sqlexperiment = $DB->sql_compare_text('shortname', strlen('shortname'));
        $record = $DB->get_record_sql('SELECT * FROM {tool_abconfig_experiment} WHERE shortname = ?', array($sqlexperiment));

        $this->assertEquals($record->name, 'name');
        $this->assertEquals($record->shortname, 'shortname');
        $this->assertEquals($record->scope, 'request');
    }

    public function test_experiment_exists() {
        $this->resetAfterTest(true);
        global $DB;
        $manager = new tool_abconfig_experiment_manager();

        // Test response for non existent experiment
        $this->assertFalse($manager->experiment_exists('shortname'));

        // Manually add experiment
        $DB->insert_record('tool_abconfig_experiment', array('name' => 'name', 'shortname' => 'shortname', 'scope' => 'request', 'enabled' => 0));

        // Verify that experiment is found
        $this->assertTrue($manager->experiment_exists('shortname'));

        //Now delete this record, and add a new one
        $sqlcompare = $DB->sql_compare_text('shortname', strlen('shortname'));
        $record = $DB->execute('DELETE FROM {tool_abconfig_experiment} WHERE shortname = ?', array($sqlcompare));
        $DB->insert_record('tool_abconfig_experiment', array('name' => 'name2', 'shortname' => 'shortname2', 'scope' => 'request', 'enabled' => 0));

        //Verify the first record still isnt found
        $this->assertFalse($manager->experiment_exists('shortname'));
    }

    public function test_update_experiment() {
        $this->resetAfterTest(true);
        global $DB;
        $manager = new tool_abconfig_experiment_manager();

        // Manually add experiment
        $DB->insert_record('tool_abconfig_experiment', array('name' => 'name', 'shortname' => 'shortname', 'scope' => 'request', 'enabled' => 0));

        // Update all the values of the experiment
        $manager->update_experiment('shortname','name2', 'shortname2', 'session', 1);

        // Get record and verify fields
        $sqlexperiment = $DB->sql_compare_text('shortname2', strlen('shortname2'));
        $record = $DB->get_record_sql('SELECT * FROM {tool_abconfig_experiment} WHERE shortname = ?', array($sqlexperiment));

        $this->assertEquals($record->name, 'name2');
        $this->assertEquals($record->shortname, 'shortname2');
        $this->assertEquals($record->scope, 'session');
        $this->assertEquals($record->enabled, 1);
    }

    public function test_delete_experiment() {
        $this->resetAfterTest(true);
        global $DB;
        $manager = new tool_abconfig_experiment_manager();

        // Manually add experiment
        $DB->insert_record('tool_abconfig_experiment', array('name' => 'name', 'shortname' => 'shortname', 'scope' => 'request', 'enabled' => 0));
        $DB->insert_record('tool_abconfig_experiment', array('name' => 'name', 'shortname' => 'shortname2', 'scope' => 'request', 'enabled' => 0));

        $manager->delete_experiment('shortname');
        
        // Check records to ensure only correct record deleted
        $records = $DB->get_records('tool_abconfig_experiment');
        $this->assertEquals(1, count($records));
        $this->assertEquals('shortname2', reset($records)->shortname);
    }

    public function test_condition_exists() {

    }

    public function test_add_condition() {

    }

    public function test_update_condition() {

    }

    public function test_delete_condition() {

    }

    public function test_delete_all_conditions() {

    }

}