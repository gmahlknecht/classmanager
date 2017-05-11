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
 * List all classes
 *
 * @package contrib
 * @subpackage block_classmanager
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

$context = context_user::instance($USER->id);
$PAGE->set_context($context);

$c = '';

$header = get_string('classespagetitle', 'block_classmanager');

if (! filter_has_var(INPUT_GET, 'category') and ! filter_has_var(INPUT_POST, 'category')) {
    $c .= get_string('missingparameter', 'block_classmanager');
} else {
    if (filter_has_var(INPUT_GET, 'category')) {
        $categoryid = filter_input(INPUT_GET, 'category', FILTER_SANITIZE_NUMBER_INT);
    } else {
        $categoryid = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_NUMBER_INT);
    }
    $context = context_coursecat::instance($categoryid);
    if (has_capability(PERMISSION, $context)) {
        $school = $DB->get_record('course_categories', array (
                'id' => $categoryid
        ));
        $PAGE->navbar->add(get_string('manage', 'block_classmanager') . ' ' . $school->name,
                new moodle_url($CFG->wwwroot . '/blocks/classmanager/admin.php?category=' . $categoryid));
        $PAGE->navbar->add(get_string('classes', 'block_classmanager'));
        $c .= get_string('classdescription', 'block_classmanager');
        $context = context_coursecat::instance($categoryid);
        if (filter_has_var(INPUT_GET, 'action') && filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING) == 'DELETE') {
            $classid = filter_input(INPUT_GET, 'classid', FILTER_SANITIZE_NUMBER_INT);
            $classesmatch = $DB->get_records('cohort', array (
                    'id' => $classid,
                    'contextid' => $context->id
            ));
            if (is_array($classesmatch) && count($classesmatch) > 0) {
                $DB->delete_records('cohort', array (
                        'id' => $classid
                ));
                $c .= "<br><b>" . get_string("groupdeleted", "block_classmanager") . "</b><br>";
            } else {
                $c .= get_string('rightsproblem', 'block_classmanager');
            }
        }
        $c .= "<br><a href=\"" . $CFG->wwwroot . "/blocks/classmanager/editclass.php?category=" . $categoryid . "&classid=0\">";
        $c .= get_string('newclass', 'block_classmanager') . "</a><br>";
        $classes = $DB->get_records('cohort', array (
                'contextid' => $context->id
        ), 'name');
        if (is_array($classes)) {
            $c .= "<table>";
            foreach ($classes as $class) {
                $studentsnum = $DB->count_records('cohort_members', array (
                        'cohortid' => $class->id
                ));
                if ($studentsnum == 0) {
                    $studentstable = "";
                } else {
                    $studentstable = "<a href=\"" . $CFG->wwwroot . "/blocks/classmanager/students.php?category=";
                    $studentstable .= $categoryid . "&filter=" . $class->id . "\">" . $studentsnum . " ";
                    $studentstable .= get_string("enroledusers", "block_classmanager") . "</a>";
                }
                $c .= "<tr>";
                $c .= "<td>" . $class->name . "</td>";
                $c .= "<td><a href=\"" . $CFG->wwwroot . "/blocks/classmanager/editclass.php?category=" . $categoryid;
                $c .= "&classid=" . $class->id . "\">" . get_string('edit', 'block_classmanager') . "</a></td>";
                $c .= "<td>" . $studentstable . "</td>";
                $c .= "</tr>";
            }
            $c .= "</table>";
        } else {
            $c .= get_string('noclassescreated', 'block_classmanager');
        }
    } else {
        $c .= get_string('rightsproblem', 'block_classmanager');
    }
}

$PAGE->set_title($header);
$PAGE->set_heading($header);
$PAGE->navbar->ignore_active();
echo $OUTPUT->header();
echo $OUTPUT->blocks_for_region('content');
echo $c;
echo $OUTPUT->footer();
