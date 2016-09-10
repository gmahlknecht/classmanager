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


if(!isset($_GET['category']) and !isset($_POST['category'])) {
		$c .= "no category";
	
} else {
	if(isset($_GET['category']))
		$categoryid = $_GET['category'];
	else
		$categoryid = $_POST['category'];
	
	$context = context_coursecat::instance($categoryid);
	if(has_capability(PERMISSION, $context)) {
		$header = get_string('classespagetitle', 'block_classmanager');
		$school = $DB->get_record('course_categories', array('id' => $categoryid));

						
		$PAGE->navbar->add(get_string('manage', 'block_classmanager').' '.$school->name, new moodle_url($CFG->wwwroot.'/blocks/classmanager/admin.php?category='.$categoryid));	
		$PAGE->navbar->add(get_string('students', 'block_classmanager'));//, new moodle_url($CFG->wwwroot.'/blocks/classmanager/students.php'));
				
			$c .= get_string('studentsdescription', 'block_classmanager');
			
			if(isset($_GET['action']) && $_GET['action'] == 'DELETE') {
				$user_match = $DB->get_records_sql('SELECT u.id as userid, u.username, u.auth, u.firstname , u.lastname, u.id, c.id as classe, c.idnumber as classname, u.email
					FROM '.$CFG->prefix.'user u, '.$CFG->prefix.'cohort_members m, '.$CFG->prefix.'cohort c 
					WHERE u.id = m.userid
						AND m.cohortid = c.id
						AND c.contextid=?
						AND u.id = ?
						ORDER BY u.lastname, u.firstname', array($context->id, $_GET['userid']));
				if(is_array($user_match)  && count($user_match) > 0) {
						require('../../user/lib.php');
						$del_user = new stdClass();
						$del_user->id = $_GET['userid'];
						$del_user->email = $user_match[$_GET['userid']]->email;
						$del_user->username = $user_match[$_GET['userid']]->username;
						$del_user->auth = $user_match[$_GET['userid']]->auth;
                        user_delete_user($del_user);
                        $c .= "<br><b>".get_string("userdeleted", "block_classmanager")."</b><br>";
                    } else {
                        $c =  "berechtigungsfehler";
                    }
				
				
			}
			
			
			$cohorts = $DB->get_records('cohort', array('contextid' => $context->id), 'name');
			if(is_array($cohorts)) {
				$c .= ".<br>".get_string('choosecohort', 'block_classmanager')."<br>";
				foreach($cohorts as $cohort) {
					if(isset($_GET['filter']) and $_GET['filter'] == $cohort->id)
						$c .= $cohort->name.', ';
					else
						$c .= '<a href="'.$CFG->wwwroot.'/blocks/classmanager/students.php?category='.$categoryid.'&filter='.$cohort->id.'">'.
							$cohort->name.'</a>, ';
					
					
				}	
				$c .= "<br>";	
				
			}
			
			if(isset($_GET['filter']))
				$users = $DB->get_records_sql('SELECT u.id, u.firstname , u.lastname, c.id as classe, c.idnumber as classname
					FROM '.$CFG->prefix.'user u, '.$CFG->prefix.'cohort_members m, '.$CFG->prefix.'cohort c 
					WHERE u.id = m.userid
						AND m.cohortid = c.id
						AND c.id = '.$_GET['filter'].'
						AND c.contextid='.$context->id.'
						GROUP BY u.id, c.id
						ORDER BY u.lastname, u.firstname');//, array($_GET['filter'], $context->id));
			else
				$users = $DB->get_records_sql('SELECT u.id, u.firstname , u.lastname, c.id as classe, c.idnumber as classname
					FROM '.$CFG->prefix.'user u, '.$CFG->prefix.'cohort_members m, '.$CFG->prefix.'cohort c 
					WHERE u.id = m.userid
						AND m.cohortid = c.id
						AND c.contextid=?
						GROUP BY u.id, c.id
						ORDER BY u.lastname, u.firstname', array($context->id));
			
				
			$numofusers = count($users);
			if($numofusers == 0)
				$c .= get_string('nousers', 'block_classmanager');
			$c .= "<br><a href=\"".$CFG->wwwroot."/blocks/classmanager/editstudent.php?category=".$categoryid."&userid=0\">".get_string('createnewuser', 'block_classmanager')."</a>";
			
			if(is_array($users)) {
				$c .= "<table>";
				$count = 0;
				foreach($users as $user) {
					//if($count%3 ==0) {
						if($count > 0)
							$c .= "</tr>";
						$c .= "<tr>";
					//}
					$c .= "<td><a href=\"".$CFG->wwwroot."/blocks/classmanager/editstudent.php?category=".$categoryid."&userid=".$user->id."\">"
						.$user->lastname." ".$user->firstname."</a> ".$user->classname."</td>";
					$count++;
					
					/*$data = new stdClass();
					$data->roleid = STUDENTROLE;
					$data->userid = $user->id;
					$data->contextid=$context->id;
					$x = $DB->get_records('role_assignments', array('userid'=> $data->userid, 'contextid' => $context->id));
					print_r($x);
					print "<br>x";
					if(!is_array($x) or count($x)<1) {
						//print "update";
						$data->timemodified = time();	
						print_r($data);
						//$DB->delete_records('role_assignments', array('userid'=> $data->userid, 'contextid' => $context->id));
						$roleassing = $DB->insert_record('role_assignments', $data);	
					}*/
					
					
				}
				$c .= "</table>";
			}
			else
				$c .= get_string('nousercreated', 'block_classmanager');
			
				
		
			
	}
	else {
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


