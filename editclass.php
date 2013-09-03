<?php


require_once("../../config.php"); //for Moodle integration
require_once("./config.php");


$params = array();
$PAGE->set_url('/my/index.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');


$context = get_context_instance(CONTEXT_USER, $USER->id);
$PAGE->set_context($context);

$c = '';

$header = get_string('classespagetitle', 'block_classmanager');
if(!isset($_GET['category']) and !isset($_POST['category'])) {
		$c .= "no category";
	
} else {
	if(isset($_GET['category']))
		$categoryid = $_GET['category'];
	else
		$categoryid = $_POST['category'];
	if(isset($_GET['classid']))
		$classid = $_GET['classid'];
	else
		$classid = $_POST['classid'];
	
	$context = get_context_instance(CONTEXT_COURSECAT, $categoryid);
	if(has_capability(PERMISSION, $context)) {
		$school = $DB->get_record('course_categories', array('id' => $categoryid));
		$header = get_string('classespagetitle', 'block_classmanager');
		$c .= get_string('editclassdescription', 'block_classmanager')."<br>";
		if(isset($classid)) {
			if($classid>0) {
				$class = $DB->get_record('cohort', array('id' => $classid, 'contextid' => $context->id));
			}
			else {
				$class = new stdClass;
				$class->id = 0;
				$class->name = '';				
			}
			if(isset($_POST['name']) && $_POST['name']!='') {
				require '../../cohort/lib.php';
				if($class->id == 0) {
					$newclass = new stdClass;
					$newclass->name = $_POST['name'];
					$newclass->idnumber = strtoupper($_POST['idnumber']);
					$newclass->contextid = $context->id;
					$newid = cohort_add_cohort($newclass);
					$class = $DB->get_record('cohort', array('id' => $newid));		
				}
				else {
					$class->name = $_POST['name'];
					$class->idnumber = strtoupper($_POST['idnumber']);
					$DB->update_record('cohort', $class, $bulk=false);
				}
				$c .= "<b><font color=\"green\">".get_string('saved', 'block_classmanager')."</font></b>";
				
				
				
			}
			
			if(is_object($class)) {
			
				$PAGE->navbar->add(get_string('manage', 'block_classmanager').' '.$school->name, new moodle_url($CFG->wwwroot.'/blocks/classmanager/admin.php?category='.$categoryid));	
				$PAGE->navbar->add(get_string('classes', 'block_classmanager'), new moodle_url($CFG->wwwroot.'/blocks/classmanager/classes.php?category='.$categoryid));
				$PAGE->navbar->add($class->name != '' ? $class->name : get_string('new'));
				
				if(!(preg_match("/^([1-5])([A-Z]{1,4})/", $class->idnumber) || $class->idnumber == 'TE' || $class->idnumber == 'PE' ) && $class->id != 0) {
					$c .= "<br><b><font color=\"red\">".get_string('classnamenotconform', 'block_classmanager')."</font></b><br>";					
				}
				
				$c .= "<form action=\"".$CFG->wwwroot."/blocks/classmanager/editclass.php\" method=\"post\">
					<input type=\"hidden\" name=\"classid\" value=\"".$class->id."\">
					<input type=\"hidden\" name=\"category\" value=\"".$categoryid."\">
					<table>
						<tr><td>".get_string('name', 'block_classmanager')."</td>
						<td><input type=\"text\" name=\"name\" value=\"".$class->name."\"></td></tr>
						<tr><td>".get_string('shortname', 'block_classmanager')."</td>
						<td><input type=\"text\" name=\"idnumber\" value=\"".$class->idnumber."\"></td></tr>
						<tr><td><a href=\"".$CFG->wwwroot."/blocks/classmanager/classes.php?category=".$categoryid."\">".get_string('back')."</a></td>
						<td><input type=\"submit\" value=\"".get_string('submit')."\"></td></tr>
						
					</table>
				
				</form>";
			
			}
			
			
			
			
		}
		else
			$c .= get_string('error');
			
	}
	else
		$c .= "berechtigungsfehler";
}

$PAGE->set_title($header);
$PAGE->set_heading($header);

$PAGE->navbar->ignore_active();

echo $OUTPUT->header();
echo $OUTPUT->blocks_for_region('content');

    
    
echo $c;


echo $OUTPUT->footer();


