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
 * Classmanager edit a single student
 *
 * @package block_classmanager
 * @copyright 2013, 2017 Stefan Raffeiner, Giovanni Mahlknecht
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
$pagetitle = get_string('studentpagetitle', 'block_classmanager');

if (! filter_has_var(INPUT_GET, 'category') and ! filter_has_var(INPUT_POST, 'category')) {
    $c .= get_string('missingparameter', 'block_classmanager');
} else {
    if (filter_has_var(INPUT_GET, 'category')) {
        $categoryid = filter_input(INPUT_GET, 'category', FILTER_SANITIZE_NUMBER_INT);
    } else {
        $categoryid = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_NUMBER_INT);
    }
    $context = context_coursecat::instance($categoryid);
    if (filter_has_var(INPUT_GET, 'userid')) {
        $userid = filter_input(INPUT_GET, 'userid', FILTER_SANITIZE_NUMBER_INT);
    } else {
        $userid = filter_input(INPUT_POST, 'userid', FILTER_SANITIZE_NUMBER_INT);
    }
    if ($userid > 0) {
        $sqlstring = 'SELECT u.id, u.username, u.lastname, u.firstname, u.email, c.id as classe ';
        $sqlstring .= 'FROM ' . $CFG->prefix . 'user u, ' . $CFG->prefix . 'cohort c, ';
        $sqlstring .= $CFG->prefix . 'cohort_members m ';
        $sqlstring .= 'WHERE u.id = m.userid ';
        $sqlstring .= 'AND m.cohortid = c.id ';
        $sqlstring .= 'AND c.contextid=? ';
        $sqlstring .= 'AND u.id=? ';
        $filtervalues = array (
                $context->id,
                $userid
        );
        $user = $DB->get_record_sql($sqlstring, $filtervalues);
    } else {
        $user = new stdClass();
    }
    if (has_capability(PERMISSION, $context) and (count($user) > 0 or $userid == 0)) {
        $school = $DB->get_record('course_categories', array (
                'id' => $categoryid
        ));
        require('../../user/lib.php');
        require('../../cohort/lib.php');
        $issetlastname = filter_has_var(INPUT_POST, 'lastname');
        $lastname = filter_input(INPUT_POST, 'lastname', FILTER_SANITIZE_STRING);
        $issetfirstname = filter_has_var(INPUT_POST, 'firstname');
        $firstname = filter_input(INPUT_POST, 'firstname', FILTER_SANITIZE_STRING);
        $issetemail = filter_has_var(INPUT_POST, 'email');
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $issetusername = filter_has_var(INPUT_POST, 'username');
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_EMAIL);
        $issetpassword = filter_has_var(INPUT_POST, 'password');
        $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
        $issetclass = filter_has_var(INPUT_POST, 'class');
        $classid = filter_input(INPUT_POST, 'class', FILTER_SANITIZE_STRING);
        if ($issetlastname) {
            if ($issetlastname && $lastname != '' && $issetfirstname && $firstname != '' &&
                    $issetclass && $classid != '' && $issetusername && $username != '' &&
                    ($userid > 0 || ($issetpassword && $password != ''))) {
                $cohort = $DB->get_record('cohort', array (
                        'id' => $classid,
                        'contextid' => $context->id
                ));
                if (isset($cohort->id)) {
                    $user->lastname = $lastname;
                    $user->firstname = $firstname;
                    $user->email = $email;
                    $user->username = $username;
                    $user->auth = 'manual';
                    $user->confirmed = 1;
                    $user->policyagreed = 0;
                    $user->mnethostid = 1;
                    $user->lang = 'de';
                    if ($issetpassword && $password != '') {
                        $user->password = hash_internal_user_password($password);
                    }
                    if ($userid > 0) {
                        $user->id = $userid;
                        $user->timemodified = time();
                        $DB->update_record('user', $user);
                        if ($user->classe != $classid) {
                            cohort_remove_member($user->classe, $userid);
                            cohort_add_member($classid, $userid);
                            switch ($cohort->idnumber) {
                                case 'TE' :
                                    $role = TEACHERROLE;
                                    break;
                                case 'PE' :
                                    $role = NOTTEACHINGROLE;
                                    break;
                                default :
                                    $role = STUDENTROLE;
                                    break;
                            }
                            $data = new stdClass();
                            $data->roleid = $role;
                            $data->userid = $user->id;
                            $data->contextid = $context->id;
                            $x = $DB->get_records('role_assignments', array (
                                    'roleid' => $data->roleid,
                                    'userid' => $data->userid,
                                    'contextid' => $context->id
                            ));
                            if (! is_array($x) or count($x) < 1) {
                                $data->timemodified = time();
                                $DB->delete_records('role_assignments', array (
                                        'userid' => $data->userid,
                                        'contextid' => $context->id
                                ));
                                $roleassing = $DB->insert_record('role_assignments', $data);
                            }
                        }
                    } else {
                        $user->password = $password;
                        $userid = user_create_user($user);
                        $user->id = $userid;
                        cohort_add_member($classid, $userid);
                        $cohort = $DB->get_record('cohort', array (
                                'id' => $userid
                        ));
                        switch ($cohort->idnumber) {
                            case 'TE' :
                                $role = TEACHERROLE;
                                break;
                            case 'PE' :
                                $role = NOTTEACHINGROLE;
                                break;
                            default :
                                $role = STUDENTROLE;
                                break;
                        }
                        $data = new stdClass();
                        $data->roleid = $role;
                        $data->userid = $userid;
                        $data->timemodified = time();
                        $data->contextid = $context->id;
                        $roleassing = $DB->insert_record('role_assignments', $data);
                    }
                    $user->classe = $classid;
                    $c .= "<b><font color=\"green\">" . get_string('saved', 'block_classmanager') . "</font></b>";
                } else {
                    $c .= "<b><font color=\"red\">1" . get_string('notalldata', 'block_classmanager') . "</font></b>";
                }
            } else {
                $c .= "<b><font color=\"red\">2" . get_string('notalldata', 'block_classmanager') . "</font></b>";
            }
        }
        if (isset($userid)) {
            if ($userid == 0) {
                $user = new stdClass();
                $user->id = 0;
                $user->classe = 0;
                $user->lastname = '';
                $user->firstname = '';
                $user->email = '';
                $user->id = 0;
            }
            if (is_object($user)) {
                if ($userid > 0) {
                    $navbarmanageschool = new moodle_url($CFG->wwwroot . '/blocks/classmanager/admin.php?category=' . $categoryid);
                    $PAGE->navbar->add(get_string('manage', 'block_classmanager') . ' ' . $school->name, $navbarmanageschool);
                }
                $navbarmanageusers = new moodle_url($CFG->wwwroot . '/blocks/classmanager/students.php?category=' . $categoryid);
                $PAGE->navbar->add(get_string('studentpagetitle', 'block_classmanager'), $navbarmanageusers);
                $PAGE->navbar->add($user->lastname . " " . $user->firstname);
                $classes = $DB->get_records('cohort', array (
                        'contextid' => $context->id
                ), 'name ASC');
                $c .= "<form method=\"post\" action=\"" . $CFG->wwwroot . "/blocks/classmanager/editstudent.php\">";
                $c .= "<input type=\"hidden\" name=\"userid\" value=\"" . $userid . "\" >";
                $c .= "<input type=\"hidden\" name=\"category\" value=\"" . $categoryid . "\" >";
                $c .= "<table>";
                $c .= "<tr>";
                $c .= "  <td>" . get_string('username') . "</td>";
                $c .= "  <td><input type=\"text\" name=\"username\" value=\"" . $user->username . "\"  size=\"45\"></td>";
                $c .= "</tr>";
                $c .= "<tr>";
                $c .= "  <td>" . get_string('firstname') . "</td>";
                $c .= "  <td><input type=\"text\" name=\"firstname\" value=\"" . $user->firstname . "\"></td>";
                $c .= "</tr>";
                $c .= "<tr>";
                $c .= "  <td>" . get_string('lastname') . "</td>";
                $c .= "  <td><input type=\"text\" name=\"lastname\" value=\"" . $user->lastname . "\"></td>";
                $c .= "</tr>";
                $c .= "<tr>";
                $c .= "  <td>" . get_string('newpassword') . "</td>";
                $c .= "  <td><input type=\"password\" name=\"password\" value=\"\"></td>";
                $c .= "</tr>";
                $c .= "<tr>";
                $c .= "  <td>" . get_string('email') . "</td>";
                $c .= "  <td><input type=\"text\" name=\"email\" value=\"".$user->email . "\" size=\"45\" readonly=\"readonly\">(";
                $c .= get_string('emailchangenotice', 'block_classmanager') . ")</td>";
                $c .= "</tr>";
                $c .= "<tr>";
                $c .= "<td>" . get_string('class', 'block_classmanager') . "</td>";
                $c .= "<td><select size=\"1\"  name=\"class\" >";
                if (is_array($classes)) {
                    foreach ($classes as $class) {
                        if ($class->id == $user->classe) {
                            $c .= "<option value=\"" . $class->id . "\" selected=\"selected\">" . $class->name . "</option>";
                        } else {
                            $c .= "<option value=\"" . $class->id . "\">" . $class->name . "</option>";
                        }
                    }
                }
                $c .= "	</select></td>";
                $c .= "</tr>";
                $c .= "<tr><td></td>";
                $c .= "<td><input type=\"submit\" value=\"" . get_string('submit') . "\"</td>";
                $c .= "</tr>";
                $deletelink = "<a href=\"#\" onclick=\"var answer = confirm('";
                $deletelink .= get_string("areyousure", "block_classmanager") . "');";
                $deletelink .= "if (answer){";
                $deletelink .= "window.location = '" . $CFG->wwwroot . "/blocks/classmanager/students.php?action=DELETE&userid=";
                $deletelink .= $userid . "&category=" . $categoryid . "';}\">" . get_string('delete') . "</a>";
                $backlink = "<a href=\"" . $CFG->wwwroot . "/blocks/classmanager/students.php?category=";
                $backlink .= $categoryid . "\">" . get_string('back') . "</a>";
                $c .= "<tr><td></td><td>".$deletelink."</td></tr>";
                $c .= "<tr><td></td><td>".$backlink."</td></tr>";
                $c .= "</table></form>";
            } else {
                // TODO: better error messages: user is not a valid user object!
                $c .= "1" . get_string('error');
            }
        } else {
            // TODO: better error messages: userid is not set - missing parameter?
            $c .= "2" . get_string('error');
        }
    } else {
        $c .= get_string('rightsproblem', 'block_classmanager');
    }
}
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);
$PAGE->navbar->ignore_active();
echo $OUTPUT->header();
echo $OUTPUT->blocks_for_region('content');
echo $c;
echo $OUTPUT->footer();
