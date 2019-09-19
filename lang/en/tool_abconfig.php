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
 * Plugin Strings
 *
 * @package   tool_abconfig
 * @author    Brendan Heywood <brendan@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'A/B config';

// Page Name Strings
$string['abconfigsettings'] = 'A/B Config Settings';
$string['manageexperimentspagename'] = 'Manage Experiments';
$string['editexperimentpagename'] = 'Edit Experiment';

// Form Strings
$string['formaddexperiment'] = 'Add Experiment';
$string['formexperimentname'] = 'Experiment Name:';
$string['formexperimentnamereq'] = 'Experiment Name Required';
$string['formexperimentshortname'] = 'Short Experiment Name:';
$string['formexperimentshortnamereq'] = 'Short Experiment Name Required';
$string['formexperimentscopeselect'] = 'Scope:';
$string['formexperimentalreadyexists'] = 'Experiment shortname already exists';
$string['formexperimentinfo'] = 'Experiment Info';
$string['formexperimentconds'] = 'Experiment Conditions';
$string['formipwhitelist'] = 'IP Whitelist';
$string['formexperimentcommands'] = 'Experiment Commands';
$string['formexperimentvalue'] = ' % value of traffic targeted';
$string['formexperimentvalueerror'] = ' % value must be a number in range 0-100';
$string['formexperimentcondsset'] = 'Conditions set';
$string['formexperimentvalueexceed'] = 'Total of all condition set values must be <= 100. Currently {$a}';
$string['formexperimentenabled'] = 'Enable Experiment';
$string['formdeleterepeat'] = 'Delete';
$string['formaddrepeat'] = 'Add Condition Set';
$string['formheader'] = 'Condition Set {$a}';
$string['formnnewconditions'] = 'New Condition Set';
$string['formeditconditions'] = 'Edit Conditions';
$string['formnocommands'] = 'No Commands';
$string['formnoips'] = 'No IPs';
$string['formaddexperiment'] = 'Add Experiment';
$string['formdeleteexperiment'] = 'Delete Experiment';
$string['formexperimentforceurl'] = 'Force URL params';

// short Strings
$string['request'] = 'Request';
$string['session'] = 'Session';
$string['name'] = 'Experiment Name';
$string['shortname'] = 'Short Experiment Name';
$string['scope'] = 'Experiment Scope';
$string['edit'] = 'Edit';
$string['enabled'] = 'Enabled';
$string['yes'] = 'Yes';
$string['no'] = 'No';

// Setting Strings
$string['settingsenablename'] = 'Enable Plugin';
$string['settingsenabledesc'] = 'Enable the plugin. While this is unchecked, users will not interact with the plugin at all. Experiments can still be set.';
