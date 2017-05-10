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
 * Classmanager config file
 *
 * @package block_classmanager
 * @copyright 2017 Stefan Raffeiner, Giovanni Mahlknecht
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("../../config.php");
require_login();
require_once("config.php");
require_once($CFG->dirroot . "/course/lib.php");
require_once($CFG->dirroot . "/lib/formslib.php");
require_once($CFG->dirroot . '/enrol/locallib.php');
require_once($CFG->dirroot . '/enrol/cohort/locallib.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/lib/coursecatlib.php');

$error = false;
if (isset($_REQUEST['name'])) {
    if ($_REQUEST['name'] != "") {
        $context = get_context_instance(CONTEXT_COURSECAT, $_REQUEST['category']);
        if (has_capability("moodle/course:create", $context)) {
            $data = new stdClass();
            $data->fullname = strip_tags($_REQUEST['name']);
            $data->shortname = preg_replace('/\s\s+/', ' ', ucwords($data->fullname));
            $data->idnumber = "";
            $data->summary = "";
            $data->summaryformat = 1;
            $data->format = "topics";
            $data->numsections = 5;
            $data->category = $_REQUEST['category'];
            $course = create_course($data);
            $context = get_context_instance(CONTEXT_COURSE, $course->id);
            enrol_try_internal_enrol($course->id, $USER->id, $CFG->creatornewroleid);
            if ($_REQUEST['class'] != 0) {
                // Klasse wurde ausgewaehlt - einschreiben als cohorte!
                $manager = new course_enrolment_manager($PAGE, $course);
                $enrol = enrol_get_plugin('cohort');
                $enrol->add_instance($manager->get_course(), array (
                        'customint1' => $_REQUEST['class'],
                        'roleid' => 5
                ));
                enrol_cohort_sync($manager->get_course()->id);
                header("Location: " . $CFG->wwwroot . "/course/view.php?id=" . $course->id);
            } else {
                // Keine Klasse gewaehlt - springe zur Nutzereinschreibung!
                header("Location: " . $CFG->wwwroot . "/enrol/users.php?id=" . $course->id);
            }
        }
    } else {
        $error = true;
    }
}

$params = array ();
$PAGE->set_url('/my/index.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');

$context = get_context_instance(CONTEXT_USER, $USER->id);
$PAGE->set_context($context);
$c = '';
$header = get_string('createcoursepagetitle', 'block_classmanager');
$PAGE->set_title($header);
$PAGE->set_heading($header);
$PAGE->navbar->ignore_active();
echo $OUTPUT->header();
echo $OUTPUT->blocks_for_region('content');

$parents = array ();
$classes = array ();
$classes[0] = get_string('none', 'block_classmanager');

$editcategories = array ();
$categories = $DB->get_records('course_categories', array (
        'parent' => CORE_CATEGORY
), "name");
foreach ($categories as $category) {
    $context = context_coursecat::instance($category->id);
    if (has_capability("moodle/course:create", $context)) {
        $classesmatch = $DB->get_records('cohort', array (
                'contextid' => $context->id
        ), "name");
        foreach ($classesmatch as $class) {
            if (count($categories) > 1) {
                $classes[$class->id] = $class->name . '  (' . $category->name . ')';
            } else {
                $classes[$class->id] = $class->name;
            }
        }
    }
}

$categorieslist = coursecat::make_categories_list('moodle/course:create');
class createcourse_form extends moodleform{
    protected function definition() {
        $mform = & $this->_form;
        $mform->addElement('header', 'coursename', get_string('coursename', 'block_classmanager'));
        $mform->addElement('text', 'name', get_string('coursename', 'block_classmanager'), 'maxlength="100" size="25" ');
        $element = '<div class="fitem"><div class="fititemtitle"></div><div><div class="felement">';
        $element .= get_string('coursenamedescription', 'block_classmanager') . '</div></div>';
        $mform->addElement('html', $element);
        global $categorieslist;
        $mform->addElement('select', 'category', get_string('category', 'block_classmanager'), $categorieslist);
        $element = '<div class="fitem"><div class="fititemtitle"></div><div><div class="felement">';
        $element .= get_string('createcoursedescription', 'block_classmanager') . '</div></div>';
        $mform->addElement('html', $element);
        $mform->addElement('submit', 'intro', get_string("submit"));
    }
}

$form = new createcourse_form($CFG->wwwroot . '/blocks/classmanager/createcourse.php');
$form->display();
echo $c;
echo $OUTPUT->footer();
