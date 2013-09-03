<?php

require_once("../../config.php"); //for Moodle integration
require_once("config.php"); //for Classmanager Configuration

if(!isset($_GET['schoolid']))
	header('Location: '.$CFG->wwwroot.'/blocks/classmanager/admin.php');

$params = array();
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
	if(has_capability('moodle/user:editprofile', $context)) {
		//SCHOOLS
		$PAGE->navbar->add(get_string('manageschools', 'block_classmanager'), new moodle_url($CFG->wwwroot.'/blocks/classmanager/admin.php'));
		
		if(isset($_GET['name']) || isset($_GET['short']) || isset($_GET['manager'])) {
			if(isset($_GET['name']) && isset($_GET['short']) && isset($_GET['manager'])) {
				$school = new stdClass();
				$school->name = $_GET['name'];
				$school->short = $_GET['short'];
				$school->user = $_GET['manager'];
				if($_GET['schoolid'] == 0) {
					$_GET['schoolid'] = $DB->insert_record('classmanager_schools', $school, $returnid=true, $bulk=false);
				}
				else {
					$school->id = $_GET['schoolid'];
					$DB->update_record('classmanager_schools', $school, $bulk=false);
				}
				$c .= "<b>".get_string('saved', 'block_classmanager')."</b>";
			}
			else
				$c .= get_string('notalldata', 'block_classmanager')."<br>";
		}
		
		
		if($_GET['schoolid']>0) {
			$school = $DB->get_record('classmanager_schools', array('id' => $_GET['schoolid']));			
			$PAGE->navbar->add($school->name);
		}
		else {
			$school = new stdClass();
			$school->name = '';
			$school->short = '';
			$school->user = '';
			$school->id = '';				
			$PAGE->navbar->add(get_string('thisisanewschool', 'block_classmanager'));		
		}
		if(is_object($school)) {
		
			
			$c .= "<form action=\"editschool.php\"><input type=\"hidden\" name=\"schoolid\" value=\"".$school->id."\"><table>";
			$c .= "<tr><td>".get_string('shortname', 'block_classmanager')."</td><td><input type=\"text\" name=\"short\" value=\"".$school->short."\"></td></tr>";
			$c .= "<tr><td>".get_string('name', 'block_classmanager')."</td><td><input type=\"text\" name=\"name\" value=\"".$school->name."\"></td></tr>";
			
			$users = $DB->get_records_sql('SELECT u.id, u.lastname, u.firstname 
				FROM mdl_user u, mdl_role_assignments r
				WHERE u.id = r.userid
					AND r.roleid= '.SCHOOLMANAGERROLE.'
					AND r.contextid= 1
				ORDER BY u.lastname, u.firstname');
			
			$c .= "<tr><td>".get_string('manager', 'block_classmanager')."</td><td>
				<select size=\"1\"  name=\"manager\" >";				
			foreach($users as $user) {
				if($user->id == $school->user)
					$c .= "<option value=\"".$user->id."\" selected=\"selected\">".$user->lastname." ".$user->firstname."</option>";	
				else			
					$c .= "<option value=\"".$user->id."\">".$user->lastname." ".$user->firstname."</option>";				
			}				
			$c .= "</select></td></tr>";
			$c .= "<tr><td><a href=\"".$CFG->wwwroot."/blocks/classmanager/admin.php\">".get_string('back')."</a></td><td><input type=\"submit\" value=\"".get_string('submit')."\"></td></tr>";
			$c .= "</table></form>";		
			
			
		}
		else {
			$c .= get_string('schoolnotfound', 'block_classmanager');
		}
	
	}	
	else
		$c .=  'Ich root - du nix';
	$c .=  '<br><center><small>Class Manager by Stefan Raffeiner</small></center>';

$PAGE->navbar->ignore_active();

echo $OUTPUT->header();
echo $OUTPUT->blocks_for_region('content');

    
    
echo $c;

echo $OUTPUT->footer();


?>
