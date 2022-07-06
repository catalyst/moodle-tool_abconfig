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

// Page Name Strings.
$string['abconfigsettings'] = 'A/B config settings';
$string['manageexperimentspagename'] = 'Manage experiments';
$string['editexperimentpagename'] = 'Edit experiment';
$string['editexperimentconds'] = 'Edit experiment conditions';

// Form Strings.
$string['formaddexperiment'] = 'Add experiment';
$string['formexperimentname'] = 'Experiment name:';
$string['formexperimentnamereq'] = 'Experiment name required';
$string['formexperimentshortname'] = 'Short experiment name:';
$string['formexperimentshortnamereq'] = 'Short experiment name required';
$string['formexperimentscopeselect'] = 'Scope:';
$string['formexperimentalreadyexists'] = 'Experiment shortname already exists';
$string['formexperimentinfo'] = 'Experiment info';
$string['formexperimentconds'] = 'Experiment conditions';
$string['formipwhitelist'] = 'IP whitelist';
$string['formexperimentcommands'] = 'Experiment commands';
$string['formexperimentvalue'] = ' % value of traffic targeted';
$string['formexperimentvalueerror'] = ' % value must be a number in range 0-100';
$string['formexperimentusers'] = 'Usernames or id numbers';
$string['formexperimentusers_help'] = 'When this field is empty the condition experiment will be executed on all users';
$string['formexperimentcondsset'] = 'Conditions set';
$string['formexperimentvalueexceed'] = 'Total of all condition set values must be <= 100. Currently {$a}';
$string['formexperimentenabled'] = 'Enable experiment';
$string['formdeleterepeat'] = 'Delete';
$string['formaddrepeat'] = 'Add condition set';
$string['formheader'] = 'Condition set {$a}';
$string['formnnewconditions'] = 'New condition set';
$string['formeditconditions'] = 'Edit conditions';
$string['formnocommands'] = 'No commands';
$string['formnoips'] = 'No IPs';
$string['formallusers'] = 'All users';
$string['formaddexperiment'] = 'Add experiment';
$string['formdeleteexperiment'] = 'Delete experiment';
$string['formexperimentforceurl'] = 'Force URL params';
$string['formexperimentadminenable'] = 'Enable this experiment for site admins.';

// Short Strings.
$string['request'] = 'Request';
$string['session'] = 'Session';
$string['name'] = 'Experiment name';
$string['shortname'] = 'Short experiment name';
$string['scope'] = 'Experiment scope';
$string['edit'] = 'Edit';
$string['enabled'] = 'Enabled';
$string['yes'] = 'Yes';
$string['no'] = 'No';
$string['adminenabled'] = 'Enabled for admin';

// Setting Strings.
$string['settingsenablename'] = 'Enable plugin';
$string['settingsenabledesc'] = 'Enable the plugin. While this is unchecked, users will not interact with the plugin at all. Experiments can still be set.';

// Cache Strings.
$string['cachedef_experiments'] = 'Cache to store experiments and conditions in.';

// Privacy Strings.
$string['privacy:metadata'] = 'This plugin does not collect or store any user information.';
