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
 * Version information.
 *
 * @package   tool_abconfig
 * @author    Peter Burnett <peterburnett@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once(__DIR__.'/locallib.php');

defined('MOODLE_INTERNAL') || die();

admin_externalpage_setup('tool_abconfig_manageexperiments');

$prevurl = ($CFG->wwwroot.'/admin/category.php?category=abconfig');

$form = new \tool_abconfig\form\manage_experiments();
if ($form->is_cancelled()) {
    redirect($prevurl);
} else if ($fromform = $form->get_data()) {
    // Safe to insert new experiment, unique field validated in form validation
    
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manageexperimentspagename', 'tool_abconfig'));
$form->display();
echo $OUTPUT->footer();




