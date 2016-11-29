<?php

require_once("../../config.php"); //for Moodle integration
require_once("./config.php");
$params = array();
$PAGE->set_url('/my/index.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');
$context = context_user::instance($USER->id);
$PAGE->set_context($context);
$c = '';
$header = get_string('studentpagetitle', 'block_classmanager');

if (!filter_has_var(INPUT_GET, 'category') and ! filter_has_var(INPUT_POST, 'category')) {
    $c .= "no category";
} else {
    if (filter_has_var(INPUT_GET, 'category')) {
        $categoryid = filter_input(INPUT_GET, 'category', FILTER_SANITIZE_NUMBER_INT);
    } else {
        $categoryid = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_NUMBER_INT);
    }
    $context = context_coursecat::instance($categoryid);
    if (has_capability(PERMISSION, $context)) {
        $header = get_string('classespagetitle', 'block_classmanager');
        $school = $DB->get_record('course_categories', array('id' => $categoryid));
        $PAGE->navbar->add(get_string('manage', 'block_classmanager') . ' ' . $school->name, new moodle_url($CFG->wwwroot . '/blocks/classmanager/admin.php?category=' . $categoryid));
        $PAGE->navbar->add(get_string('students', 'block_classmanager')); //, new moodle_url($CFG->wwwroot.'/blocks/classmanager/students.php'));
        $c .= get_string('studentsdescription', 'block_classmanager');
        if (filter_has_var(INPUT_GET, 'action') && filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING) == 'DELETE') {
            $userid = filter_input(INPUT_GET, 'userid', FILTER_SANITIZE_NUMBER_INT);
            $user_match = $DB->get_records_sql('SELECT u.id as userid, u.username, u.auth, u.firstname , u.lastname, u.id, c.id as classe, c.idnumber as classname, u.email
					FROM ' . $CFG->prefix . 'user u, ' . $CFG->prefix . 'cohort_members m, ' . $CFG->prefix . 'cohort c 
					WHERE u.id = m.userid
						AND m.cohortid = c.id
						AND c.contextid=?
						AND u.id = ?
						ORDER BY u.lastname, u.firstname', array($context->id, $userid));
            if (is_array($user_match) && count($user_match) > 0) {
                require('../../user/lib.php');
                $del_user = new stdClass();
                $del_user->id = $userid;
                $del_user->email = $user_match[$userid]->email;
                $del_user->username = $user_match[$userid]->username;
                $del_user->auth = $user_match[$userid]->auth;
                user_delete_user($del_user);
                $c .= "<br><b>" . get_string("userdeleted", "block_classmanager") . "</b><br>";
            } else {
                $c = "berechtigungsfehler";
            }
        }
        $cohorts = $DB->get_records('cohort', array('contextid' => $context->id), 'name');
        if (is_array($cohorts)) {
            $c .= ".<br>" . get_string('choosecohort', 'block_classmanager') . "<br>";
            foreach ($cohorts as $cohort) {
                if (filter_has_var(INPUT_GET, 'filter') and filter_input(INPUT_GET, 'filter', FILTER_SANITIZE_NUMBER_INT) == $cohort->id) {
//                if (isset($_GET['filter']) and $_GET['filter'] == $cohort->id) {
                    $c .= $cohort->name . ', ';
                } else {
                    $c .= '<a href="' . $CFG->wwwroot . '/blocks/classmanager/students.php?category=' . $categoryid . '&filter=' . $cohort->id . '">' .
                            $cohort->name . '</a>, ';
                }
            }
            $c .= "<br>";
        }

        if (filter_has_var(INPUT_GET, 'filter')) {
            $users = $DB->get_records_sql('SELECT u.id, u.firstname , u.lastname, c.id as classe, c.idnumber as classname
					FROM ' . $CFG->prefix . 'user u, ' . $CFG->prefix . 'cohort_members m, ' . $CFG->prefix . 'cohort c 
					WHERE u.id = m.userid
						AND m.cohortid = c.id
						AND c.id = ' . filter_input(INPUT_GET, 'filter', FILTER_SANITIZE_NUMBER_INT) . '
						AND c.contextid=' . $context->id . '
						GROUP BY u.id, c.id
						ORDER BY u.lastname, u.firstname');
        } 
        else {
            $users = $DB->get_records_sql('SELECT u.id, u.firstname , u.lastname, c.id as classe, c.idnumber as classname
					FROM ' . $CFG->prefix . 'user u, ' . $CFG->prefix . 'cohort_members m, ' . $CFG->prefix . 'cohort c 
					WHERE u.id = m.userid
						AND m.cohortid = c.id
						AND c.contextid=?
						GROUP BY u.id, c.id
						ORDER BY u.lastname, u.firstname', array($context->id));
        }
        $numofusers = count($users);
        if ($numofusers == 0) {
            $c .= get_string('nousers', 'block_classmanager');
        }
        $c .= "<br><a href=\"" . $CFG->wwwroot . "/blocks/classmanager/editstudent.php?category=" . $categoryid . "&userid=0\">" . get_string('createnewuser', 'block_classmanager') . "</a>";

        if (is_array($users)) {
            $c .= "<table>";
            $count = 0;
            foreach ($users as $user) {
                if ($count > 0) {
                    $c .= "</tr>";
                }
                $c .= "<tr>";
                $c .= "<td><a href=\"" . $CFG->wwwroot . "/blocks/classmanager/editstudent.php?category=" . $categoryid . "&userid=" . $user->id . "\">"
                        . $user->lastname . " " . $user->firstname . "</a> " . $user->classname . "</td>";
                $count++;
            }
            $c .= "</table>";
        } else {
            $c .= get_string('nousercreated', 'block_classmanager');
        }
    } else {
        $c .= 'Ich root - du nix';
    }
}

$PAGE->set_title($header);
$PAGE->set_heading($header);
$PAGE->navbar->ignore_active();
echo $OUTPUT->header();
echo $OUTPUT->blocks_for_region('content');
echo $c;
echo $OUTPUT->footer();
