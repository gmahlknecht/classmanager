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
 * Class manager visualize users
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
$header = get_string('studentpagetitle', 'block_classmanager');

if (! filter_has_var(INPUT_GET, 'category') and ! filter_has_var(INPUT_POST, 'category')) {
    // Not passed any category parameter.
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
        $navbardesturl = new moodle_url($CFG->wwwroot . '/blocks/classmanager/admin.php?category=' . $categoryid);
        $PAGE->navbar->add(get_string('manage', 'block_classmanager') . ' ' . $school->name, $navbardesturl);
        $PAGE->navbar->add(get_string('managestudents', 'block_classmanager'));
        $c .= "<h5><a href=\"" . $CFG->wwwroot . "/blocks/classmanager/editstudent.php?category=";
        $c .= $categoryid . "&userid=0\">" . get_string('createnewuser', 'block_classmanager') . "</a></h5>";
        if (filter_has_var(INPUT_GET, 'action') && filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING) == 'DELETE') {
            // TODO: check if needed, think that we removed delete capability from students list!
            $userid = filter_input(INPUT_GET, 'userid', FILTER_SANITIZE_NUMBER_INT);
            $sqlstring = 'SELECT u.id as userid, u.username, u.auth, u.firstname , u.lastname, u.id, c.id as classe, ';
            $sqlstring .= 'c.idnumber as classname, u.email, u.currentlogin ';
            $sqlstring .= 'FROM ' . $CFG->prefix . 'user u, ' . $CFG->prefix . 'cohort_members m, ' . $CFG->prefix . 'cohort c ';
            $sqlstring .= 'WHERE u.id = m.userid ';
            $sqlstring .= 'AND m.cohortid = c.id ';
            $sqlstring .= 'AND c.contextid=? ';
            $sqlstring .= 'AND u.id = ? ';
            $sqlstring .= 'ORDER BY u.lastname, u.firstname';
            $usermatch = $DB->get_records_sql($sqlstring, array (
                    $context->id,
                    $userid
            ));
            if (is_array($usermatch) && count($usermatch) > 0) {
                require('../../user/lib.php');
                $usertodelete = new stdClass();
                $usertodelete->id = $userid;
                $usertodelete->email = $usermatch[$userid]->email;
                $usertodelete->username = $usermatch[$userid]->username;
                $usertodelete->auth = $usermatch[$userid]->auth;
                user_delete_user($usertodelete);
                $c .= "<br><b>" . get_string("userdeleted", "block_classmanager") . "</b><br>";
            } else {
                $c .= get_string('rightsproblem', 'block_classmanager');
            }
        }
        $cohorts = $DB->get_records('cohort', array (
                'contextid' => $context->id
        ), 'name');
        if (is_array($cohorts)) {
            $c .= "<h4>" . get_string('choosecohort', 'block_classmanager').": </h4>";
            foreach ($cohorts as $cohort) {
                if (filter_has_var(INPUT_GET, 'filter') and
                        filter_input(INPUT_GET, 'filter', FILTER_SANITIZE_NUMBER_INT) == $cohort->id) {
                    $c .= $cohort->name . ', ';
                } else {
                    $c .= '<a href="' . $CFG->wwwroot . '/blocks/classmanager/students.php?category=';
                    $c .= $categoryid . '&filter=' . $cohort->id . '">' . $cohort->name . '</a>, ';
                }
            }
        }
        if (filter_has_var(INPUT_GET, 'filter')) {
            // Filter by class id!
            $sqlstring = 'SELECT u.id, u.firstname , u.lastname, c.id as classe, c.idnumber as classname, ';
            $sqlstring .= ' u.currentlogin ';
            $sqlstring .= 'FROM ' . $CFG->prefix . 'user u, ' . $CFG->prefix . 'cohort_members m, ';
            $sqlstring .= $CFG->prefix . 'cohort c ';
            $sqlstring .= 'WHERE u.id = m.userid AND m.cohortid = c.id ';
            $sqlstring .= 'AND c.id = ' . filter_input(INPUT_GET, 'filter', FILTER_SANITIZE_NUMBER_INT);
            $sqlstring .= ' AND c.contextid=' . $context->id . ' GROUP BY u.id, c.id ';
            $sqlstring .= 'ORDER BY u.lastname, u.firstname';
            $users = $DB->get_records_sql($sqlstring);
        } else {
            // Without Class id filter!
            $sqlstring = 'SELECT u.id, u.firstname , u.lastname, c.id as classe, c.idnumber as classname, ';
            $sqlstring .= ' u.currentlogin ';
            $sqlstring .= 'FROM ' . $CFG->prefix . 'user u, ' . $CFG->prefix . 'cohort_members m, ';
            $sqlstring .= $CFG->prefix . 'cohort c ';
            $sqlstring .= 'WHERE u.id = m.userid AND m.cohortid = c.id AND c.contextid=? ';
            $sqlstring .= 'GROUP BY u.id, c.id ORDER BY u.lastname, u.firstname';
            $users = $DB->get_records_sql($sqlstring, array (
                    $context->id
            ));
        }
        $numofusers = count($users);
        if ($numofusers == 0) {
            $c .= "<h4>".get_string('nousers', 'block_classmanager')."</h4>";
        } else if (is_array($users)) {
            // TODO: localize!
            $c .= "<table><tr><th>User</th><th>Group</th><th>Last login</th></tr>";
            $count = 0;
            foreach ($users as $user) {
                if ($count > 0) {
                    $c .= "</tr>";
                }
                $c .= "<tr>";
                $c .= "<td>";
                $linkedituser = $CFG->wwwroot . "/blocks/classmanager/editstudent.php?category=" . $categoryid;
                $linkedituser .= "&userid=" . $user->id;
                $c .= "<a href=\"" .$linkedituser . "\">";
                $c .= $user->lastname;
                $c .= " ".$user->firstname;
                $c .= "</a> </td>";
                $c .= "<td>" . $user->classname . "</td>";
                $lastlog = "never logged in";
                if ($user->currentlogin != 0) {
                    $lastlog = date("Y-m-d", $user->currentlogin);
                }
                $c .= "<td>" . $lastlog . "</td>";
                $count ++;
            }
            $c .= "</table>";
        } else {
            $c .= print_error('nousercreated', 'block_classmanager');
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
