<?php

require_once("../../config.php");
require_once("./config.php");

$params = array();
$PAGE->set_url('/my/index.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');

$context = context_user::instance($USER->id);
$PAGE->set_context($context);

$c = '';

$header = get_string('classespagetitle', 'block_classmanager');

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
        $school = $DB->get_record('course_categories', array('id' => $categoryid));
        $PAGE->navbar->add(get_string('manage', 'block_classmanager') . ' ' . $school->name, new moodle_url($CFG->wwwroot . '/blocks/classmanager/admin.php?category=' . $categoryid));
        $PAGE->navbar->add(get_string('classes', 'block_classmanager'));

        $c .= get_string('classdescription', 'block_classmanager');
        $context = context_coursecat::instance($categoryid);

        if (filter_has_var(INPUT_GET, 'action') && filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING) == 'DELETE') {
            $classid = filter_input(INPUT_GET, 'classid', FILTER_SANITIZE_NUMBER_INT);
            $classes_match = $DB->get_records('cohort', array('id' => $classid, 'contextid' => $context->id));
            if (is_array($classes_match) && count($classes_match) > 0) {
                $DB->delete_records('cohort', array('id' => $classid));
                $c .= "<br><b>" . get_string("groupdeleted", "block_classmanager") . "</b><br>";
            } else {
                $c = "berechtigungsfehler";
            }
        }
        $c .= "<br><a href=\"" . $CFG->wwwroot . "/blocks/classmanager/editclass.php?category=" . $categoryid . "&classid=0\">" . get_string('newclass', 'block_classmanager') . "</a><br>";
        $classes = $DB->get_records('cohort', array('contextid' => $context->id), 'name');
        if (is_array($classes)) {
            $c .= "<table>";
            foreach ($classes as $class) {
                $students = $DB->count_records('cohort_members', array('cohortid' => $class->id));
                if ($students == 0) {
                    $students_str = "";
                } else {
                    $students_str = "   <a href=\"" . $CFG->wwwroot . "/blocks/classmanager/students.php?category=" . $categoryid . "&filter=" . $class->id . "\">" . $students . " " . get_string("enroledusers", "block_classmanager") . "</a>";
                }
                $c .= "<tr><td>" . $class->name . "</td>
					<td><a href=\"" . $CFG->wwwroot . "/blocks/classmanager/editclass.php?category=" . $categoryid . "&classid=" . $class->id . "\">" . get_string('edit', 'block_classmanager') . "</a></td>
                                        <td>" . $students_str . "</td></tr>";
            }
            $c .= "</table>";
        } else {
            $c .= get_string('noclassescreated', 'block_classmanager');
        }
    } else {
        $c = 'berechtigungsfehler';
    }
}

$PAGE->set_title($header);
$PAGE->set_heading($header);
$PAGE->navbar->ignore_active();
echo $OUTPUT->header();
echo $OUTPUT->blocks_for_region('content');
echo $c;
echo $OUTPUT->footer();

