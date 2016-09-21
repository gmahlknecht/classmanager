<?php


require_once("../../config.php"); //for Moodle integration
require_once("config.php"); //for Classmanager Configuration
require_once($CFG->dirroot."/course/lib.php");
require_once($CFG->dirroot."/lib/formslib.php");
require_once($CFG->dirroot.'/enrol/locallib.php');
require_once($CFG->dirroot.'/enrol/cohort/locallib.php');
require_once($CFG->dirroot.'/group/lib.php');
require_once($CFG->dirroot.'/lib/coursecatlib.php');

$error = false;
if(isset($_REQUEST['name'])) {
	print_r($_REQUEST);
	if($_REQUEST['name'] != "") {
		$context = get_context_instance(CONTEXT_COURSECAT, $_REQUEST['category']);
		if(has_capability("moodle/course:create", $context)) {
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
	 		if ($_REQUEST['class']!=0){
				//Klasse wurde ausgewaehlt - einschreiben als cohorte
				$manager = new course_enrolment_manager($PAGE, $course);
 				$enrol = enrol_get_plugin('cohort');
 		   	    	$enrol->add_instance($manager->get_course(), array('customint1' => $_REQUEST['class'], 'roleid' => 5));
 	    	   		enrol_cohort_sync($manager->get_course()->id);
				header("Location: ".$CFG->wwwroot."/course/view.php?id=".$course->id);
	 		} else {
				//keine Klasse gewaehlt - springe zur Nutzereinschreibung
				header("Location: ".$CFG->wwwroot."/enrol/users.php?id=".$course->id);
	 		}
	 	}
	} else {
		$error = true;
	}
}


$params = array();
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


$categorieslist = array();
$parents = array();
$classes = array();
$classes[0] = get_string('none', 'block_classmanager');


$edit_categories = array();
$categories = $DB->get_records('course_categories', array('parent' => CORE_CATEGORY), "name");
foreach($categories as $category) {
	$context = context_coursecat::instance($category->id);
	if(has_capability("moodle/course:create", $context)) {
		$classes_match = $DB->get_records('cohort', array('contextid'=>$context->id), "name");
		foreach($classes_match as $class) {
			if(count($categories)>1)
				$classes[$class->id] = $class->name.'  ('.$category->name.')';
			else
				$classes[$class->id] = $class->name;
		}
	}
}





#make_categories_list($categorieslist, $parents, 'moodle/course:create');
$categorieslist  = coursecat::make_categories_list('moodle/course:create');

class createcourse_form extends moodleform {
 
    function definition() {
        global $CFG;
 
        $mform =& $this->_form;  
        
		$mform->addElement('header', 'coursename', get_string('coursename', 'block_classmanager'));
		$mform->addElement('text', 'name', get_string('coursename', 'block_classmanager'), 'maxlength="100" size="25" ');
		$mform->addElement('html', '<div class="fitem"><div class="fititemtitle"></div><div><div class="felement">'
			.get_string('coursenamedescription', 'block_classmanager')
			.'</div></div>');
		global $categorieslist;
		$mform->addElement('select', 'category', get_string('category', 'block_classmanager'), $categorieslist);
		$mform->addElement('html', '<div class="fitem"><div class="fititemtitle"></div><div><div class="felement">'
			.get_string('createcoursedescription', 'block_classmanager')
			.'</div></div>');

		global $classes;
//		$mform->addElement('select', 'class', get_string('chooseclass', 'block_classmanager'), $classes);
//		$mform->addElement('html', '<div class="fitem"><div class="fititemtitle"></div><div><div class="felement">'
//			.get_string('chooseclassdescription', 'block_classmanager')
//			.'</div></div>');

		$mform->addElement('submit', 'intro', get_string("submit"));
     } 
}              


$form = new createcourse_form($CFG->wwwroot.'/blocks/classmanager/createcourse.php');
$form->display();


echo $c;


echo $OUTPUT->footer();

