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
 * Upgrade for abconfig plugin.
 *
 * @package     tool_abconfig
 * @copyright   2022 Catalyst IT
 * @author      Mikhail Golenkov <mikhailgolenkov@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade the plugin.
 *
 * @param int $oldversion
 * @return bool always true
 */
function xmldb_tool_abconfig_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2022070600) {
        $table = new xmldb_table('tool_abconfig_condition');
        $field = new xmldb_field('users', XMLDB_TYPE_TEXT, null, null, null, null, null, 'value');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2022070600, 'tool', 'abconfig');
    }

    if ($oldversion < 2025011300) {
        // Define field numoffset to be added to tool_abconfig_experiment.
        $table = new xmldb_table('tool_abconfig_experiment');
        $field = new xmldb_field('numoffset', XMLDB_TYPE_INTEGER, '5', null, null, null, null, 'adminenabled');

        // Conditionally launch add field numoffset.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Abconfig savepoint reached.
        upgrade_plugin_savepoint(true, 2025011300, 'tool', 'abconfig');
    }

    if ($oldversion < 2026071300) {
        // Retype shortname from text to char(255) so it can be indexed, and add a unique index on it.
        $table = new xmldb_table('tool_abconfig_experiment');
        $field = new xmldb_field('shortname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_type($table, $field);
        }

        $index = new xmldb_index('shortname', XMLDB_INDEX_UNIQUE, ['shortname']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Add an index on the foreign key column used to look up conditions for an experiment.
        $table = new xmldb_table('tool_abconfig_condition');
        $index = new xmldb_index('experiment', XMLDB_INDEX_NOTUNIQUE, ['experiment']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Abconfig savepoint reached.
        upgrade_plugin_savepoint(true, 2026071300, 'tool', 'abconfig');
    }

    return true;
}
