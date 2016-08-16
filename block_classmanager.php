<?php

/*
 *      block_classmanager.php
 *      
 *      Copyright 2011 Stefan Raffeiner <stefan.raffeiner@gmail.com>
 *      
 *      This program is free software; you can redistribute it and/or modify
 *      it under the terms of the GNU General Public License as published by
 *      the Free Software Foundation; either version 2 of the License, or
 *      (at your option) any later version.
 *      
 *      This program is distributed in the hope that it will be useful,
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *      GNU General Public License for more details.
 *      
 *      You should have received a copy of the GNU General Public License
 *      along with this program; if not, write to the Free Software
 *      Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *      MA 02110-1301, USA.
 *      
 *      
 */




class block_classmanager extends block_base {
  function init() {
    $this->title   = get_string('pluginname', 'block_classmanager');
  }
  
  function get_content() {	 
	 
	
    require_once("config.php");
    global $CFG; 
	
    if ($this->content !== NULL) {
      return $this->content;
    }
    
    $course = $this->page->course->category;
    // print($course)."<br>"; //->coursecategory;
    $this->content         =  new stdClass;
   
    global $DB;
    $edit_categories = array();
    $edit_courses = array();
    $categories = $DB->get_records('course_categories', array('parent' => CORE_CATEGORY));
    foreach($categories as $category) {
      $context = get_context_instance(CONTEXT_COURSECAT, $category->id);
      if(has_capability(PERMISSION, $context)) {
	$edit_categories[] = $category;
      }
      if(has_capability("moodle/course:create", $context)) {
	$edit_courses[] = $category;
      }
    }
	
    if(is_array($edit_categories)) {
      $c =  "";
		
      if(count($edit_categories)>1) {
	foreach($edit_categories as $category) {				
	  $c .=  "<a href=\"".$CFG->wwwroot."/blocks/classmanager/admin.php?category=".$category->id."\">".$category->name."</a><br>";
	}		
      } else {
	foreach($edit_categories as $category) {				
	  $c .= "<b>".$category->name."</b><br>";
	  $c .= get_string("helpblock", "block_classmanager")."<br>";
	  $c .=  "<a href=\"".$CFG->wwwroot."/course/category.php?id=".$category->id."\">".get_string("courses")."</a><br>";		
	  $c .=  "<a href=\"".$CFG->wwwroot."/blocks/classmanager/classes.php?category=".$category->id."\">".get_string("classes", "block_classmanager")."</a><br>";		
	  $c .=  "<a href=\"".$CFG->wwwroot."/blocks/classmanager/students.php?category=".$category->id."\">".get_string("users")."</a><br>";		
	  $c .=  "<a href=\"".$CFG->wwwroot."/blocks/classmanager/deleteusers.php?category=".$category->id."\">".get_string("deleteusers", "block_classmanager")."</a><br>";		
	  $c .=  "<a href=\"".$CFG->wwwroot."/blocks/classmanager/connections.php?category=".$category->id."\">".get_string("connections", "block_classmanager")."</a><br>";		
	  $c .=  "<a href=\"".$CFG->wwwroot."/blocks/classmanager/import.php?category=".$category->id."\">".get_string("import", "block_classmanager")."</a><br>";	
	}
      }
    }
	
    if(is_array($edit_courses)) {
      $c .=  "<a href=\"".$CFG->wwwroot."/blocks/classmanager/createcourse.php\">".get_string("createcourse", "block_classmanager")."</a><br>";		
    }
	
	
    // else {
    //	$this->content->text = "Ich root - du nix";		
    //}

    /*	$edit_categories = array();
	$categories = $DB->get_records('course_categories', array('parent' => CORE_CATEGORY));//CORE_CATEGORY));
	foreach($categories as $category) {
	$context = get_context_instance(CONTEXT_COURSECAT, $category->id);
	if(has_capability('block/mrbs:viewmrbs', $context)) {
	$dbman = $DB->get_manager();
	if($dbman->table_exists('mrbs_'.$category->id.'_mrbs_area')) {
	if($DB->count_records('mrbs_'.$category->id.'_mrbs_area')>0) {
					
	$edit_categories[] = $category;
					
	}
	}
	}
	}*/
    //	if(count($edit_categories) >0) {
    //$c .= "<br><a href=\"".$CFG->wwwroot."/blocks/mrbs/web/index.php\">Raumbuchungssystem</b>";
    //	}
    //$c .= "<br>";
    $c .= "<a href=\"".$CFG->wwwroot."/course/\">Kursbereiche anzeigen</a><br>";
    $c .= "<a href=\"".$CFG->wwwroot."/course/view.php?id=1194\">Hilfe und Support</a><br><br>";

    //$data = file_get_contents('http://localhost/stats.php');
    //$stats = json_decode($data);	
    //require_once 'statistics.php';
    //$stats = new MoodleStatistics();

    //$c .= '<b>Benutzer Statistiken:</b><br>
//	jetzt aktiv: '.$stats->user_access().'<br>
//	in der letzten Stunde aktiv: '.$stats->user_access('hour').'<br>
//	in den letzten 24 Stunden aktiv: '.$stats->user_access('day').'<br>';
    //	in den letzten 7 Tagen aktiv: '.$stats->user_access_week.'<br>';
	
    $this->content->text = $c;

    $this->content->footer = '<small>Class Manager by Stefan Raffeiner</small>';
    //$this->content->text .= "<br>kurs id: {$course}";
    return $this->content;
  }
}  
?>
