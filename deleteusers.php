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
 * Classmanager delete users
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

$header = get_string('deleteuserspagetitle', 'block_classmanager');

if (! isset($_GET['category']) and ! isset($_POST['category'])) {
    $c .= get_string('missingparameter', 'block_classmanager');
} else {
    if (isset($_GET['category'])) {
        $categoryid = $_GET['category'];
    } else {
        $categoryid = $_POST['category'];
    }
    $context = context_coursecat::instance($categoryid);
    if (has_capability(PERMISSION, $context)) {
        $school = $DB->get_record('course_categories', array (
                'id' => $categoryid
        ));
        $newurl = new moodle_url($CFG->wwwroot . '/blocks/classmanager/admin.php?category=' . $categoryid);
        $PAGE->navbar->add(get_string('manage', 'block_classmanager') . ' ' . $school->name, $newurl);
        $PAGE->navbar->add(get_string('deleteusers', 'block_classmanager'));
        $c .= get_string('deleteusersdescription', 'block_classmanager');
        if (isset($_GET['action']) && $_GET['action'] == 'DELETE' && isset($_GET['filter'])) {
            $sqlstring = 'SELECT u.id as userid, u.username, u.auth, u.firstname , u.lastname, c.id as classe, ';
            $sqlstring .= 'c.idnumber as classname, u.email ';
            $sqlstring .= 'FROM ' . $CFG->prefix . 'user u, ' . $CFG->prefix . 'cohort_members m, ' . $CFG->prefix . 'cohort c ';
            $sqlstring .= 'WHERE u.id = m.userid ';
            $sqlstring .= 'AND m.cohortid = c.id ';
            $sqlstring .= 'AND c.id = ' . $_GET['filter'] . ' ';
            $sqlstring .= 'AND c.contextid=' . $context->id . ' ';
            $sqlstring .= 'GROUP BY u.id, c.id ';
            $sqlstring .= 'ORDER BY u.lastname, u.firstname';
            $usermatch = $DB->get_records_sql($sqlstring);
            if (is_array($usermatch) && count($usermatch) > 0) {
                require('../../user/lib.php');
                foreach ($usermatch as $user) {
                    $usertodelete = new stdClass();
                    $usertodelete->id = $user->userid;
                    $usertodelete->email = $user->email;
                    $usertodelete->username = $user->username;
                    $usertodelete->auth = $user->auth;
                    user_delete_user($usertodelete);
                    $c .= '<br><b>' . get_string("userdeleted", "block_classmanager") . ':' . $user->userid . ' ';
                    $c .= $user->firstname . ' ' . $user->lastname . ' ' . $user->username . '</b>';
                }
                $c .= '<br>';
            } else {
                $c = "berechtigungsfehler";
            }
        }
        $cohorts = $DB->get_records('cohort', array (
                'contextid' => $context->id
        ), 'name');
        if (is_array($cohorts)) {
            $c .= "<br>" . get_string('deleteuserschoosecohort', 'block_classmanager') . "<br>";
            foreach ($cohorts as $cohort) {
                if (isset($_GET['filter']) and $_GET['filter'] == $cohort->id) {
                    $c .= $cohort->name . ', ';
                } else {
                    $c .= '<a href="' . $CFG->wwwroot . '/blocks/classmanager/deleteusers.php?category=' . $categoryid;
                    $c .= '&filter=' . $cohort->id . '">' . $cohort->name . '</a>, ';
                }
            }
            $c .= "<br>";
        }
        if (isset($_GET['filter'])) {
            $sqlstring = 'SELECT u.id, u.firstname , u.lastname, c.id as classe, c.idnumber as classname ';
            $sqlstring .= 'FROM ' . $CFG->prefix . 'user u, ' . $CFG->prefix . 'cohort_members m, ' . $CFG->prefix . 'cohort c ';
            $sqlstring .= 'WHERE u.id = m.userid ';
            $sqlstring .= 'AND m.cohortid = c.id ';
            $sqlstring .= 'AND c.id = ' . $_GET['filter'] . ' ';
            $sqlstring .= 'AND c.contextid=' . $context->id;
            $sqlstring .= ' GROUP BY u.id, c.id ';
            $sqlstring .= 'ORDER BY u.lastname, u.firstname';
            $users = $DB->get_records_sql($sqlstring);
            $numofusers = count($users);
            if ($numofusers == 0) {
                $c .= get_string('nousers', 'block_classmanager');
            } else {
                $c .= "<a href=\"#\" onclick=\"var answer = confirm('" . get_string("areyousure", "block_classmanager") . "');";
                $c .= "if (answer){window.location = '";
                $c .= $CFG->wwwroot . "/blocks/classmanager/deleteusers.php?action=DELETE&category=" . $categoryid;
                $c .= "&filter=" . $_GET['filter'] . "';}\">" . get_string('deleteusersdeletelink', 'block_classmanager') . "</a>";
            }
            if (is_array($users)) {
                $c .= "<table>";
                $count = 0;
                foreach ($users as $user) {
                    if ($count > 0) {
                        $c .= "</tr>";
                    }
                    $c .= "<tr>";
                    $c .= "<td>" . $user->lastname . " " . $user->firstname . " " . $user->classname . "</td>";
                    $count ++;
                }
                $c .= "</table>";
            } else {
                $c .= get_string('nousercreated', 'block_classmanager');
            }
        } else {
            $c .= get_string('selectfirstclass', 'block_classmanager');
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
