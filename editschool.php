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

if (! isset($_GET['schoolid'])) {
    header('Location: ' . $CFG->wwwroot . '/blocks/classmanager/admin.php');
}

$params = array ();
$PAGE->set_url('/my/index.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');
$context = get_context_instance(CONTEXT_USER, $USER->id);
$PAGE->set_context($context);
$header = get_string('editschooltitle', 'block_classmanager');
$PAGE->set_title($header);
$PAGE->set_heading($header);
$c = '';
$course = $PAGE->course;
$context = get_context_instance(CONTEXT_COURSE, $course->id);
if (has_capability('moodle/user:editprofile', $context)) {
    $newurl = new moodle_url($CFG->wwwroot . '/blocks/classmanager/admin.php');
    $PAGE->navbar->add(get_string('manageschools', 'block_classmanager'), $newurl);
    if (isset($_GET['name']) || isset($_GET['short']) || isset($_GET['manager'])) {
        if (isset($_GET['name']) && isset($_GET['short']) && isset($_GET['manager'])) {
            $school = new stdClass();
            $school->name = $_GET['name'];
            $school->short = $_GET['short'];
            $school->user = $_GET['manager'];
            if ($_GET['schoolid'] == 0) {
                $_GET['schoolid'] = $DB->insert_record('classmanager_schools', $school, $returnid = true, $bulk = false);
            } else {
                $school->id = $_GET['schoolid'];
                $DB->update_record('classmanager_schools', $school, $bulk = false);
            }
            $c .= "<b>" . get_string('saved', 'block_classmanager') . "</b>";
        } else {
            $c .= get_string('notalldata', 'block_classmanager') . "<br>";
        }
    }
    if ($_GET['schoolid'] > 0) {
        $school = $DB->get_record('classmanager_schools', array (
                'id' => $_GET['schoolid']
        ));
        $PAGE->navbar->add($school->name);
    } else {
        $school = new stdClass();
        $school->name = '';
        $school->short = '';
        $school->user = '';
        $school->id = '';
        $PAGE->navbar->add(get_string('thisisanewschool', 'block_classmanager'));
    }
    if (is_object($school)) {
        $c .= "<form action=\"editschool.php\"><input type=\"hidden\" name=\"schoolid\" value=\"" . $school->id . "\"><table>";
        $c .= "<tr><td>" . get_string('shortname', 'block_classmanager') . "</td><td><input type=\"text\" name=\"short\" value=\"";
        $c .= $school->short . "\"></td></tr>";
        $c .= "<tr><td>" . get_string('name', 'block_classmanager') . "</td><td><input type=\"text\" name=\"name\" value=\"";
        $c .= $school->name . "\"></td></tr>";
        $sqlstring = 'SELECT u.id, u.lastname, u.firstname ';
        $sqlstring .= 'FROM mdl_user u, mdl_role_assignments r ';
        $sqlstring .= "WHERE u.id = r.userid ";
        $sqlstring .= "AND r.roleid= ' . SCHOOLMANAGERROLE . ' ";
        $sqlstring .= "AND r.contextid= 1 ";
        $sqlstring .= "ORDER BY u.lastname, u.firstname";
        $users = $DB->get_records_sql($sqlstring);
        $c .= "<tr><td>" . get_string('manager', 'block_classmanager') . "</td><td>
				<select size=\"1\"  name=\"manager\" >";
        foreach ($users as $user) {
            if ($user->id == $school->user) {
                $c .= "<option value=\"" . $user->id . "\" selected=\"selected\">" . $user->lastname . " ";
                $c .= $user->firstname . "</option>";
            } else {
                $c .= "<option value=\"" . $user->id . "\">" . $user->lastname . " " . $user->firstname;
                $c .= "</option>";
            }
        }
        $c .= "</select></td></tr>";
        $c .= "<tr><td><a href=\"" . $CFG->wwwroot . "/blocks/classmanager/admin.php\">" . get_string('back');
        $c .= "</a></td><td><input type=\"submit\" value=\"" . get_string('submit') . "\"></td></tr>";
        $c .= "</table></form>";
    } else {
        $c .= get_string('schoolnotfound', 'block_classmanager');
    }
} else {
    $c .= get_string('rightsproblem', 'block_classmanager');
}
$PAGE->navbar->ignore_active();
echo $OUTPUT->header();
echo $OUTPUT->blocks_for_region('content');
echo $c;
echo $OUTPUT->footer();