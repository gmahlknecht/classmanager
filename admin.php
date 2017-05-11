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
 * Class Manager school administration
 *
 * @package block_classmanager
 * @copyright 2017 Stefan Raffeiner, Giovanni Mahlknecht
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("../../config.php");
require_login();
require_once("./config.php");
$params = array ();
$PAGE->set_url('/my/index.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');

$header = get_string('adminpagetitle', 'block_classmanager');
$context = context_user::instance($USER->id);
$PAGE->set_context($context);

$c = '';
// TODO: also for post 
if (! isset($_GET['category'])) {
    $c .= get_string('missingparameter', 'block_classmanager');
} else {
    $context = context_coursecat::instance($_GET['category']);
    if (has_capability(PERMISSION, $context)) {
        $school = $DB->get_record('course_categories', array (
                'id' => $_GET['category']
        ));
        $PAGE->navbar->add(get_string('manage', 'block_classmanager') . ' ' . $school->name);
        $numberofclasses = $DB->count_records('cohort', array (
                'contextid' => $context->id
        ));
        $numberofstudents = 0;
        foreach ($DB->get_records('cohort', array (
                'contextid' => $context->id
        )) as $class) {
            $numberofstudents += $DB->count_records('cohort_members', array (
                    'cohortid' => $class->id
            ));
        }
        $c .= get_string('managerpagedescription', 'block_classmanager');
        $c .= "<table>";
        $c .= "<tr>";
        $c .= "<td><a href=\"" . $CFG->wwwroot . "/blocks/classmanager/import.php?category=" . $_GET['category'] . "\">";
        $c .= get_string('importstudents', 'block_classmanager') . "</a></td>";
        $c .= "<td>" . get_string('theeaysiestway', 'block_classmanager') . "</td>";
        $c .= "</tr>";
        $c .= "<tr>";
        $c .= "<td><a href=\"" . $CFG->wwwroot . "/blocks/classmanager/classes.php?category=" . $_GET['category'] . "\">";
        $c .= get_string('manageclasses', 'block_classmanager') . "</a></td>";
        $c .= "<td>" . get_string('youhave', 'block_classmanager') . " " . $numberofclasses . " ";
        $c .= get_string('classes', 'block_classmanager') . "</td>";
        $c .= "</tr>";
        $c .= "<tr>";
        $c .= "<td><a href=\"" . $CFG->wwwroot . "/blocks/classmanager/students.php?category=" . $_GET['category'] . "\">";
        $c .= get_string('managestudents', 'block_classmanager') . "</a></td>";
        $c .= "<td>" . get_string('youhave', 'block_classmanager') . " " . $numberofstudents . " ";
        $c .= get_string('students', 'block_classmanager') . "</td>";
        $c .= "</tr>";
        $c .= "<tr>";
        $c .= "<td><a href=\"" . $CFG->wwwroot . "/blocks/classmanager/deleteusers.php?category=" . $_GET['category'] . "\">";
        $c .= get_string('deleteusers', 'block_classmanager') . "</a></td>";
        $c .= "<td>" . get_string('deleteusersmanydescription', 'block_classmanager') . "</td>";
        $c .= "</tr>";
        $c .= "<tr>";
        $c .= "<td><a href=\"" . $CFG->wwwroot . "/blocks/classmanager/connections.php?category=" . $_GET['category'] . "\">";
        $c .= get_string('manageconnections', 'block_classmanager') . "</a></td>";
        $c .= "<td>" . get_string('toworktogether', 'block_classmanager') . "</td>";
        $c .= "</tr>";
        $c .= "</table>";
    } else {
        $c .= get_string('rightsproblem', 'block_classmanager');
    }
}
$c .= '<br><center><small>Class Manager</small></center>';

$PAGE->set_title($header);
$PAGE->set_heading($header);
$PAGE->navbar->ignore_active();
echo $OUTPUT->header();
echo $OUTPUT->blocks_for_region('content');
echo $c;
echo $OUTPUT->footer();
