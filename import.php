<?php
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

$header = get_string('importpagetitle', 'block_classmanager');


if(!isset($_GET['category']) and !isset($_POST['category'])) {
  $c .= "no category";	
} else {
  if(isset($_GET['category']))
    $categoryid = $_GET['category'];
  else
    $categoryid = $_POST['category'];
	
  $context = context_coursecat::instance($categoryid);
  if(has_capability(PERMISSION, $context)) {		
    $school = $DB->get_record('course_categories', array('id' => $categoryid));		
    $PAGE->navbar->add(get_string('manage', 'block_classmanager').' '.$school->name, new moodle_url($CFG->wwwroot.'/blocks/classmanager/admin.php?category='.$categoryid));	
    $PAGE->navbar->add(get_string('import', 'block_classmanager'));
			
    if(isset($_FILES['importfile'])) {
      $file = fopen($_FILES['importfile']['tmp_name'], "r");
      require '../../user/lib.php';
      require '../../cohort/lib.php';
      while(!feof($file)) {
	$line = fgets($file, 2048);
	$param = explode(';', $line);
	if(count($param) == 5 || count($param)==4) {
	  $newuser = new stdClass;
	  //FIXED UTF8 ISSUE
	  for($k=1; $k<3; $k++){
	    if(!mb_check_encoding($param[$k],'UTF-8')){
	   	$param[$k] = mb_convert_encoding($param[$k], 'utf8');
	    }
	  }
	  $newuser->classe = trim(strtoupper($param[0]));
	  $newuser->lastname = ucfirst(strtolower($param[1]));
	  $newuser->firstname = ucfirst(strtolower($param[2]));
				
	  $newuser->mail = trim(strtolower($param[3]));
	  if(
	     preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $newuser->mail)
	     ) {
	    $class = $DB->get_record('cohort', array('idnumber'=>$newuser->classe, 'contextid'=>$context->id));
	    if(!is_object($class)) {
	      $newclass = new stdClass;
	      switch($newuser->classe) {
	      case 'TE':
		$newclass->name = 'Lehrer';
		break;
	      case 'PE':
		$newclass->name = 'Personal';
		break;
	      default:
		$newclass->name = $newuser->classe;
	      }
	      $newclass->idnumber = $newuser->classe;
	      $newclass->description = $school->name;
	      $newclass->contextid = $context->id;
	      $class = cohort_add_cohort($newclass);
	      $c .= "<font color=\"green\">".get_string('classcreated', 'block_classmanager').": ".$newuser->classe."</font><br>";
	    }
	    $class = $DB->get_record('cohort', array('idnumber'=>$newuser->classe, 'contextid'=>$context->id));
						
	    //$passlength = 8;
	    //$passcharslower="abcdefghijklmnopqrstuvwxyz";
	    //$passcharsupper="ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	    //$passcharsnumbers="0123456789";
	    //$passcharsspecial=",.-;:_#+*!§$%&/()=?";
	    //$pass=substr(str_shuffle($passcharslower),0,2).substr(str_shuffle($passcharsnumbers),0,6);
	    //$pass=str_shuffle($pass);
	    $pass="Bz@".rand(10000, 99999);
	    
	    $insertuser = new stdClass;
	    $insertuser->auth = 'manual';
	    $insertuser->confirmed = 1;
	    $insertuser->policyagreed = 0;
	    $insertuser->username = $newuser->mail;
	    $insertuser->password = $pass;
	    $insertuser->firstname = $newuser->firstname;
	    $insertuser->lastname = $newuser->lastname;
	    $insertuser->email = $newuser->mail;
	    $insertuser->mnethostid = 1;
	    $insertuser->lang = 'de';
						
	    //test if user already exists
	    //somewhere here is an error
	    $olduser = $DB->get_record('user', array('username' => $insertuser->email));
	    if(is_object($olduser)) {
	      $c .= "<b><font color=\"green\">".get_string('useralreadyexists', 'block_classmanager').": ".$newuser->lastname." ".$newuser->firstname."</font></b><br>";
	    }						
	    else {
	      $user = user_create_user($insertuser);
	      $user_cohort = cohort_add_member($class->id, $user);
	      switch($newuser->classe){
	      case 'TE':		
		$role = TEACHERROLE; break;
	      case 'PE':
		$role = NOTTEACHINGROLE; break;
	      default:
		$role = STUDENTROLE; break;
	      }
											
	      $data = new stdClass();
	      $data->roleid = $role;
	      $data->userid = $user;
	      $data->timemodified = time();
	      $data->contextid=$context->id;
	      $roleassing = $DB->insert_record('role_assignments', $data);						
	      $c .= "".get_string('added', 'block_classmanager').": 
								".$newuser->lastname." ".$newuser->firstname." 
								".get_string('username')." ".$insertuser->username." 
								".get_string('password')." ".$pass."<br>";
	    }
	  }
	  else {//this is not a correct user!
	    $c .= "<b><font color=\"red\">".get_string('wrongdata', 'block_classmanager').": ".$newuser->lastname." ".$newuser->firstname."</font></b><br>";
	  }
	}
      }
      fclose($file);
    }
    else {
      $c .= get_string('importdescription', 'block_classmanager');
      $c .= "<form action=\"import.php\" method=\"post\" enctype=\"multipart/form-data\">
					<br><input type=\"file\"  name=\"importfile\">
					<input type=\"hidden\" name=\"category\" value=\"".$categoryid."\">
					<br><input type=\"submit\" value=\"".get_string('iknowwhatido', 'block_classmanager')."\">
					</form>";		
    }
  }
  else {
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

?>
