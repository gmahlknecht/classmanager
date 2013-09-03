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

$header = get_string('deleteuserspagetitle', 'block_classmanager');

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
    $PAGE->navbar->add(get_string('deleteusers', 'block_classmanager'));
    $c .= get_string('deleteusersdescription', 'block_classmanager');
    
    if(isset($_GET['action']) && $_GET['action'] == 'DELETE' && isset($_GET['filter']) ) { //TODO isset filter
      $user_match = $DB->get_records_sql('SELECT u.id as userid, u.username, u.auth, u.firstname , u.lastname, c.id as classe, c.idnumber as classname, u.email
					FROM '.$CFG->prefix.'user u, '.$CFG->prefix.'cohort_members m, '.$CFG->prefix.'cohort c 
					WHERE u.id = m.userid
						AND m.cohortid = c.id
						AND c.id = '.$_GET['filter'].'
						AND c.contextid='.$context->id.'
						GROUP BY u.id
						ORDER BY u.lastname, u.firstname');

      if(is_array($user_match)  && count($user_match) > 0) {
		require('../../user/lib.php');

	foreach($user_match as $user) {
	  $del_user = new stdClass();
	  $del_user->id = $user->userid;
	  $del_user->email = $user->email;
	  $del_user->username = $user->username;
	  $del_user->auth = $user->auth;
	  user_delete_user($del_user);
	  //$c .='<br><b>Deleted user:'.$user->userid.' '.$user->firstname.' '.$user->lastname.' '.$user->username.'</b>';
	  $c .='<br><b>'.get_string("userdeleted", "block_classmanager").':'.$user->userid.' '.$user->firstname.' '.$user->lastname.' '.$user->username.'</b>';
	}
        $c.='<br>';
      } else {
	$c =  "berechtigungsfehler";
      }				
    }
		
    $cohorts = $DB->get_records('cohort', array('contextid' => $context->id), 'name');
    if(is_array($cohorts)) {
      $c .= "<br>".get_string('deleteuserschoosecohort', 'block_classmanager')."<br>"; 
      foreach($cohorts as $cohort) {
	if(isset($_GET['filter']) and $_GET['filter'] == $cohort->id)
	  $c .= $cohort->name.', ';
	else
	  $c .= '<a href="'.$CFG->wwwroot.'/blocks/classmanager/deleteusers.php?category='.$categoryid.'&filter='.$cohort->id.'">'.
	    $cohort->name.'</a>, ';
      }	
      $c .= "<br>";	
    }
    
    if(isset($_GET['filter'])){
      $users = $DB->get_records_sql('SELECT u.id, u.firstname , u.lastname, c.id as classe, c.idnumber as classname
					FROM '.$CFG->prefix.'user u, '.$CFG->prefix.'cohort_members m, '.$CFG->prefix.'cohort c 
					WHERE u.id = m.userid
						AND m.cohortid = c.id
						AND c.id = '.$_GET['filter'].'
						AND c.contextid='.$context->id.'
						GROUP BY u.id
						ORDER BY u.lastname, u.firstname');
      
      $numofusers = count($users);
      if($numofusers == 0)
	$c .= get_string('nousers', 'block_classmanager');
      else{
        $c.= "<a href=\"#\" onclick=\"var answer = confirm('".get_string("areyousure", "block_classmanager")."');
                                    if (answer){
                                            window.location = '".$CFG->wwwroot."/blocks/classmanager/deleteusers.php?action=DELETE&category=".$categoryid."&filter=".$_GET['filter']."';}\">"
					.get_string('deleteusersdeletelink', 'block_classmanager')."</a>";                                   
      }		
      if(is_array($users)) {
	$c .= "<table>";
	$count = 0;
	foreach($users as $user) {
	  if($count > 0)
	    $c .= "</tr>";
	  $c .= "<tr>";
	  $c .= "<td>".$user->lastname." ".$user->firstname." ".$user->classname."</td>";
	  $count++;
	}
	$c .= "</table>";
      } //isarray users
      else
	$c .= get_string('nousercreated', 'block_classmanager');

    } //isset filter
    else{
      //$c .= "<h1>Please select first the class</h1>";
    }

  } //hascapability
  else {
    $c .= 'Ich root - du nix';
  } //hascapability else
} //isset category


$PAGE->set_title($header);
$PAGE->set_heading($header);
$PAGE->navbar->ignore_active();
echo $OUTPUT->header();
echo $OUTPUT->blocks_for_region('content');

echo $c;

echo $OUTPUT->footer();
