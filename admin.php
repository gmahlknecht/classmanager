<?php
require_once("../../config.php");
require_once("./config.php");
$params = array();
$PAGE->set_url('/my/index.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');

$header = get_string('adminpagetitle', 'block_classmanager');
$context = context_user::instance($USER->id);
$PAGE->set_context($context);

$c = '';
if (!isset($_GET['category'])) {
    $c .= "no category";
} else {
    $context = context_coursecat::instance($_GET['category']);
    if (has_capability(PERMISSION, $context)) {
        $school = $DB->get_record('course_categories', array('id' => $_GET['category']));
        $PAGE->navbar->add(get_string('manage', 'block_classmanager') . ' ' . $school->name); //, new moodle_url('/a/link/if/you/want/one.php'));
        $numberofclasses = $DB->count_records('cohort', array('contextid' => $context->id));
        $numberofstudents = 0;
        foreach ($DB->get_records('cohort', array('contextid' => $context->id)) as $class) {
            $numberofstudents += $DB->count_records('cohort_members', array('cohortid' => $class->id));
        }
        $c .= get_string('managerpagedescription', 'block_classmanager');
        $c .= "<table>
			<tr><td><a href=\"" . $CFG->wwwroot . "/blocks/classmanager/import.php?category=" . $_GET['category'] . "\">" . get_string('importstudents', 'block_classmanager') . "</a></td>
				<td>" . get_string('theeaysiestway', 'block_classmanager') . "</td></tr>
			<tr><td><a href=\"" . $CFG->wwwroot . "/blocks/classmanager/classes.php?category=" . $_GET['category'] . "\">" . get_string('manageclasses', 'block_classmanager') . "</a></td>
				<td>" . get_string('youhave', 'block_classmanager') . " " . $numberofclasses . " " . get_string('classes', 'block_classmanager') . "</td></tr>
			<tr><td><a href=\"" . $CFG->wwwroot . "/blocks/classmanager/students.php?category=" . $_GET['category'] . "\">" . get_string('managestudents', 'block_classmanager') . "</a></td>
				<td>" . get_string('youhave', 'block_classmanager') . " " . $numberofstudents . " " . get_string('students', 'block_classmanager') . "</td></tr>
			<tr><td><a href=\"" . $CFG->wwwroot . "/blocks/classmanager/deleteusers.php?category=" . $_GET['category'] . "\">" . get_string('deleteusers', 'block_classmanager') . "</a></td>
				<td>" . get_string('deleteusersmanydescription', 'block_classmanager') . "</td></tr>
			<tr><td><a href=\"" . $CFG->wwwroot . "/blocks/classmanager/connections.php?category=" . $_GET['category'] . "\">" . get_string('manageconnections', 'block_classmanager') . "</a></td>
				<td>" . get_string('toworktogether', 'block_classmanager') . "</td></tr>";
        $c .= "</table>";
    } else
        $c .= 'Ich root - du nix';
}
$c .= '<br><center><small>Class Manager by Stefan Raffeiner</small></center>';

$PAGE->set_title($header);
$PAGE->set_heading($header);
$PAGE->navbar->ignore_active();
echo $OUTPUT->header();
echo $OUTPUT->blocks_for_region('content');
echo $c;
echo $OUTPUT->footer();
