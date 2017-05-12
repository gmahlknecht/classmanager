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
 * Classmanager edit classes
 *
 * @package block_classmanager
 * @copyright 2017 Stefan Raffeiner, Giovanni Mahlknecht
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("../../config.php");
require_login();
require_once("config.php");
$params = array ();
$PAGE->set_url('/my/index.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');

$context = context_user::instance($USER->id);
$PAGE->set_context($context);
$c = '';
$header = get_string('classespagetitle', 'block_classmanager');
if (! isset($_GET['category']) and ! isset($_POST['category'])) {
    $c .= get_string('missingparameter', 'block_classmanager');
} else {
    if (isset($_GET['category'])) {
        $categoryid = $_GET['category'];
    } else {
        $categoryid = $_POST['category'];
    }
    if (isset($_GET['classid'])) {
        $classid = $_GET['classid'];
    } else {
        $classid = $_POST['classid'];
    }
    $context = context_coursecat::instance($categoryid);
    if (has_capability(PERMISSION, $context)) {
        $school = $DB->get_record('course_categories', array (
                'id' => $categoryid
        ));
        if (isset($classid)) {
            if ($classid > 0) {
                $class = $DB->get_record('cohort', array (
                        'id' => $classid,
                        'contextid' => $context->id
                ));
            } else {
                $class = new stdClass();
                $class->id = 0;
                $class->name = '';
            }
            if (isset($_POST['name']) && $_POST['name'] != '') {
                require('../../cohort/lib.php');
                if ($class->id == 0) {
                    $newclass = new stdClass();
                    $newclass->name = $_POST['name'];
                    $newclass->idnumber = strtoupper($_POST['idnumber']);
                    $newclass->contextid = $context->id;
                    $newid = cohort_add_cohort($newclass);
                    $class = $DB->get_record('cohort', array (
                            'id' => $newid
                    ));
                } else {
                    $class->name = $_POST['name'];
                    $class->idnumber = strtoupper($_POST['idnumber']);
                    $DB->update_record('cohort', $class, $bulk = false);
                }
                $c .= "<b><font color=\"green\">" . get_string('saved', 'block_classmanager') . "</font></b>";
            }
            if (is_object($class)) {
                $navbarmanageschool= new moodle_url($CFG->wwwroot . '/blocks/classmanager/admin.php?category=' . $categoryid);
                $PAGE->navbar->add(get_string('manage', 'block_classmanager') . ' ' . $school->name, $navbarmanageschool);
                $navbarmanageclasses = new moodle_url($CFG->wwwroot . '/blocks/classmanager/classes.php?category=' . $categoryid);
                $PAGE->navbar->add(get_string('classespagetitle', 'block_classmanager'), $navbarmanageclasses);
                $PAGE->navbar->add($class->name != '' ? $class->name : get_string('new'));
                if (! (preg_match("/^([1-5])([A-Z]{1,4})/", $class->idnumber) ||
                        $class->idnumber == 'TE' || $class->idnumber == 'PE') &&
                        $class->id != 0) {
                    $c .= "<br><b><font color=\"red\">";
                    $c .= get_string('classnamenotconform', 'block_classmanager') . "</font></b><br>";
                }
                $c .= "<form action=\"" . $CFG->wwwroot . "/blocks/classmanager/editclass.php\" method=\"post\">";
                $c .= "<input type=\"hidden\" name=\"classid\" value=\"" . $class->id . "\">";
                $c .= "<input type=\"hidden\" name=\"category\" value=\"" . $categoryid . "\">";
                $c .= "<table>";
                $c .= "<tr>";
                $c .= "<td>" . get_string('name', 'block_classmanager') . "</td>";
                $c .= "<td><input type=\"text\" name=\"name\" value=\"" . $class->name . "\"></td>";
                $c .= "</tr>";
                $c .= "<tr>";
                $c .= "<td>" . get_string('shortname', 'block_classmanager') . "</td>";
                $c .= "<td><input type=\"text\" name=\"idnumber\" value=\"";
                $c .= $class->idnumber . "\"></td></tr>";
                $c .= "<tr>";
                $c .= "<td></td>";
                $c .= "<td><input type=\"submit\" value=\"" . get_string('submit') . "\"></td>";
                $c .= "</tr>";
                $c .= "<tr>";
                $c .= "<td></td>";
                $c .= "<td><a href=\"" . $CFG->wwwroot . "/blocks/classmanager/classes.php?category=" . $categoryid . "\">";
                $c .= get_string('back') . "</a></td>";
                $c .= "</tr>";
                $c .= "</table>";
                $c .= "</form>";
            }
        } else {
            $c .= get_string('error');
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
