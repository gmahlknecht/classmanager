<?php


require_once("../../config.php"); //for Moodle integration
require_once("config.php");


$params = array();
$PAGE->set_url('/my/index.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');

$context = get_context_instance(CONTEXT_USER, $USER->id);
$PAGE->set_context($context);

$c = '';

$header = get_string('connectionspagetitle', 'block_classmanager');


if(!isset($_GET['category']) and !isset($_POST['category'])) {
		$c .= "no category";
	
} else {
	if(isset($_GET['category']))
		$categoryid = $_GET['category'];
	else
		$categoryid = $_POST['category'];
	
	$context = get_context_instance(CONTEXT_COURSECAT, $categoryid);
	if(has_capability(PERMISSION, $context)) {
		$school = $DB->get_record('course_categories', array('id' => $categoryid));
		
		$PAGE->navbar->add(get_string('manage', 'block_classmanager').' '.$school->name, new moodle_url($CFG->wwwroot.'/blocks/classmanager/admin.php?category='.$categoryid));	
		$PAGE->navbar->add(get_string('connections', 'block_classmanager'));
		
		$c .= get_string('connectionsdescription', 'block_classmanager');
		$context = get_context_instance(CONTEXT_COURSECAT, $categoryid);
		
		
		if(isset($_GET['connect']) and $_GET['connect'] != '' and $_GET['connect']>0) {
			$topic = $DB->get_record('course_categories', array('parent' => CATEGORY, 'id'=>$_GET['connect']));
			if(is_object($topic)) {
				$connected = $DB->get_records('course_categories', array('parent' => $topic->id, 'name' => $school->name));
				if(!(is_array($connected) and count($connected)>0)) {
					
					$newcategory = new stdClass();
					$newcategory->name = $school->name;
					$newcategory->parent = $_GET['connect'];
					$newcategory->descriptionformat = 1;
					$newcategory->description = $topic->name.' - '.$school->name;
					$newcategory->sortorder = 999;
					$newcategory->id = $DB->insert_record('course_categories', $newcategory);
					print_r($newcategory);
					$newcategory->context = get_context_instance(CONTEXT_COURSECAT, $newcategory->id);
					$categorycontext = $newcategory->context;
					mark_context_dirty($newcategory->context->path);
					
					
					/*$users = $DB->get_records_sql('SELECT u.id, u.firstname, u.lastname
						FROM '.$CFG->prefix.'user u, '.$CFG->prefix.'cohort c, '.$CFG->prefix.'cohort_members m 
						WHERE u.id = m.userid 
							AND m.cohortid = c.id
							AND c.contextid=? 
							AND c.idnumber="TE"', array($context->id));
					*/
					/*foreach($users as $user) {
						$data = new stdClass();
						$data->roleid = ROLEID;
						$data->userid = $user->id;
						$data->timemodified = time();

						$data->contextid=$categorycontext->id;
						if(has_capability(PERMISSION, $categorycontext, $data->userid)) {
							print $user->firstname;
							$roleassing = $DB->insert_record('role_assignments', $data);
						}
					}*/
					$c .= '<br><b><font color="green">'.get_string('connectioncreated', 'block_classmanager').'</font></b>';					
				} else {
					$c .= '<br><b><font color="green">'.get_string('connectionalreadyexists', 'block_classmanager').'</font></b>';
				}
			} else {
				$c .=  '<b><font color="red">berechtigungsfehler</font></b>';
			}
			
			
			
		}
		
		$topics = $DB->get_records('course_categories', array('parent' => CATEGORY));
		if(is_array($topics) and count($topics)>0) {
			$c .= "<hr>";
			foreach($topics as $topic) {
				$c .= '<table width="100%"><tr><td>
					<h2>'.$topic->name.'</h2>
					</td><td>';
				$connected = $DB->get_records('course_categories', array('parent' => $topic->id, 'name' => $school->name));
				if(is_array($connected) and count($connected)>0) {
					$c .= "<font color=\"green\">".get_string('connected', 'block_classmanager')."</font>";
				} else {
					//$c .= '<a href="'.$CFG->wwwroot.'/blocks/classmanager/connections.php?category='.$categoryid.'&connect='.$topic->id.'">'.
					//	get_string('connect', 'block_classmanager').'</a>';
					$c .= "Zur Zeit können keine Verbindungen hergestellt werden. Bitte kontaktieren Sie einen Administrator";
				}
				$c .= '</td></tr></table>'.$topic->description.'<hr>';
				
			}
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

