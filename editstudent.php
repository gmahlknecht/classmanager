<?php


//define('TEACHERROLE', 9);

require_once("../../config.php"); //for Moodle integration
require_once("config.php");

$params = array();
$PAGE->set_url('/my/index.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');


$context = context_user::instance($USER->id);
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
	if(isset($_GET['userid']))
		$userid = $_GET['userid'];
	else
		$userid = $_POST['userid'];
	
	$context = context_coursecat::instance($categoryid);
	
	if($userid>0)
		$user = $DB->get_record_sql('SELECT u.id, u.lastname, u.firstname, u.email, c.id as classe
			FROM '.$CFG->prefix.'user u, '.$CFG->prefix.'cohort c, '.$CFG->prefix.'cohort_members m 
			WHERE u.id = m.userid 
				AND m.cohortid = c.id
				AND c.contextid=? 
				AND u.id=?', array($context->id, $userid));
	else
		$user = new stdClass();
	if(has_capability(PERMISSION, $context) and (count($user)>0 or $userid==0)) {
		$header = get_string('classespagetitle', 'block_classmanager');
		$school = $DB->get_record('course_categories', array('id' => $categoryid));
		
		$c .= get_string('editstudentdescription', 'block_classmanager')."<br>";
		
		require('../../user/lib.php');
		require('../../cohort/lib.php');
		if(isset($_POST['lastname'])) {
			if(isset($_POST['lastname']) && $_POST['lastname']!='' && 
				isset($_POST['firstname']) && $_POST['firstname']!='' &&
				isset($_POST['email']) && $_POST['email']!='' &&
				isset($_POST['class']) && $_POST['class']!='' &&
				($userid>0 || (isset($_POST['password']) && $_POST['password'] != ''))) {
					
				$cohort = $DB->get_record('cohort', array('id'=>$_POST['class'], 'contextid'=>$context->id));
				if(isset($cohort->id)) {
			
					$user->lastname = $_POST['lastname'];
					$user->firstname = $_POST['firstname'];
					$user->email = $_POST['email'];
					$user->username = $_POST['email'];
					$user->auth = 'manual';
					$user->confirmed = 1;
					$user->policyagreed = 0;
					$user->mnethostid = 1;
					$user->lang = 'de';
					
					if(isset($_POST['password']) && $_POST['password'] != '') {
						$user->password = $_POST['password'];						
						$user->password = hash_internal_user_password($user->password);
				 	}
					if($userid >0) {
						$user->id = $userid;
						//print "update";
						//print $user->classe;
						//print $_POST['class'];

						$user->timemodified = time();
						$DB->update_record('user', $user);
						if($user->classe != $_POST['class']) {
							cohort_remove_member($user->classe, $userid);
							cohort_add_member($_POST['class'], $userid);
								
							switch($cohort->idnumber){
								case 'TE':		
									$role = TEACHERROLE; break;
								case 'PE':
									$role = NOTTEACHINGROLE; break;
								default:
									$role = STUDENTROLE; break;
							}
							print_r($_POST);
							
							$data = new stdClass();
							$data->roleid = $role;
							$data->userid = $user->id;
							$data->contextid=$context->id;
							$x = $DB->get_records('role_assignments', array('roleid' => $data->roleid, 'userid'=> $data->userid, 'contextid' => $context->id));
							print_r($x);
							print "x";
							if(!is_array($x) or count($x)<1) {
								print "update";
								$data->timemodified = time();							
								$DB->delete_records('role_assignments', array('userid'=> $data->userid, 'contextid' => $context->id));
								$roleassing = $DB->insert_record('role_assignments', $data);	
							}
						}
		
					} else {
						$user->password = $_POST['password'];
						$user->username = $user->email;
						$userid = user_create_user($user);
						$user->id = $userid;
						cohort_add_member($_POST['class'], $userid);
						$cohort = $DB->get_record('cohort', array('id' => $_POST['class']));
						switch($cohort->idnumber){
							case 'TE':		
								$role = TEACHERROLE; break;
							case 'PE':
								$role = NOTTEACHINGROLE; break;
							default:
								$role = STUDENTROLE; break;
							
						}
										
						$data = new stdClass();
						$data->roleid = $role;
						$data->userid = $userid;
						$data->timemodified = time();
						$data->contextid=$context->id;
						$roleassing = $DB->insert_record('role_assignments', $data);	
					}
					//print $cohort->idnumber;
					
					$user->classe = $_POST['class'];
					$c .= "<b><font color=\"green\">".get_string('saved', 'block_classmanager')."</font></b>";
				} else {
					$c .= "<b><font color=\"red\">".get_string('notalldata', 'block_classmanager')."</font></b>";
				}
			}
			else
				$c .= "<b><font color=\"red\">".get_string('notalldata', 'block_classmanager')."</font></b>";
		}
		if(isset($userid)) {
			//if($userid>0)
				//$user = $DB->get_record('user', array('id' => $userid));
			//else {
			if($userid == 0) {
				$user = new stdClass;
				$user->id = 0;
				$user->classe = 0;
				$user->lastname = '';
				$user->firstname = '';
				$user->email = '';
				$user->id = 0;
				
			}
			
			if(is_object($user)) {
				if($userid>0)
						
				$PAGE->navbar->add(get_string('manage', 'block_classmanager').' '.$school->name, new moodle_url($CFG->wwwroot.'/blocks/classmanager/admin.php?category='.$categoryid));	
				$PAGE->navbar->add(get_string('students', 'block_classmanager'), new moodle_url($CFG->wwwroot.'/blocks/classmanager/students.php?category='.$categoryid));
				$PAGE->navbar->add($user->lastname." ".$user->firstname);
				
				$classes = $DB->get_records('cohort', array('contextid' => $context->id), 'name ASC');
				
				$c .= "<form method=\"post\" action=\"".$CFG->wwwroot."/blocks/classmanager/editstudent.php\">
					<input type=\"hidden\" name=\"userid\" value=\"".$userid."\" >
					<input type=\"hidden\" name=\"category\" value=\"".$categoryid."\" >
					<table>
					<tr>
						<td>".get_string('lastname')."</td>
						<td><input type=\"text\" name=\"lastname\" value=\"".$user->lastname."\"></td>
					</tr>
					<tr>
						<td>".get_string('firstname')."</td>
						<td><input type=\"text\" name=\"firstname\" value=\"".$user->firstname."\"></td>
					</tr>
					<tr>
						<td>".get_string('newpassword')."</td>
						<td><input type=\"password\" name=\"password\" value=\"\"></td>
					</tr>
					<tr>
						<td>".get_string('email')."</td>
						<td><input type=\"text\" name=\"email\" value=\"".$user->email."\"></td>
					</tr>
					<tr>
						<td>".get_string('class', 'block_classmanager')."</td>
						<td><select size=\"1\"  name=\"class\" >";
				if(is_array($classes)) {
					foreach($classes as $class) {
						if($class->id == $user->classe)
							$c .= "<option value=\"".$class->id."\" selected=\"selected\">".$class->name."</option>";
						else
							$c .= "<option value=\"".$class->id."\">".$class->name."</option>";
					}					
				}
						
				$c .= "	</select></td>
					</tr>
					<tr>
						<td><a href=\"#\" onclick=\"var answer = confirm('".get_string("areyousure", "block_classmanager")."');
                                    if (answer){
                                            window.location = '".$CFG->wwwroot."/blocks/classmanager/students.php?action=DELETE&userid=".$userid."&category=".$categoryid."';}\">".get_string('delete')."</a></td>
						<td><input type=\"submit\" value=\"".get_string('submit')."\"</td>
					</tr>";
					
				$c .= "<tr><td></td><td><a href=\"".$CFG->wwwroot."/blocks/classmanager/students.php?category=".$categoryid."\">".get_string('back')."</a></td></tr>";
				
				$c .= "</table></form>";
				
				
			}
			else
				$c .= "1".get_string('error');			
		}
		else
			$c .= "2".get_string('error');
			
	}
	else {
		$c .= "ich root - du nix";
	}

}


$PAGE->set_title($header);
$PAGE->set_heading($header);

$PAGE->navbar->ignore_active();

echo $OUTPUT->header();
echo $OUTPUT->blocks_for_region('content');

    
    
echo $c;


echo $OUTPUT->footer();



