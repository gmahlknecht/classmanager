<?php
/*
 *      stats.php
 * 		A statistic class for moodle 2.1
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
//For moodle integration
require_once "config.php";

/* 
 * FUNCTIONS:
 * users()
 * courses()
 * course_categories()
 * questions()
 * current_online()
 * user_access($time)
 * site_access($time)
 * files()
 */ 
class MoodleStatistics {
	
	public function __construct() {
		//do nothing :)		
		return;
	}
	
	//count records from a database table
	private function count($table, $params=array()) {
		global $DB;
		return $DB->count_records($table, $params);
	}
	//converts a string to a timestamp
	private function convert_time($time) {
		$seconds = 1;
		switch($time) {
			case 'month':
				$seconds *= 5;
			case 'week':
				$seconds *= 7;
			case 'day':
				$seconds *= 24;
			case 'hour':
				$seconds *= 60;
			case 'minute':
				$seconds *=60;
				break;
			case 'recently':
			default:
				$seconds *= 60*5;		
		}		
		return time()-$seconds;
	}
	
	//returns the number of active users
	public function users() {
		return $this->count('user', array('deleted' => '0'));		
	}
		
	//returns the number of courses
	public function courses() {
		return $this->count('course');
	}
	
	//returns the number of course categories
	public function course_categories() {
		return $this->count('course_categories');
	}
	
	//returns the number of questions
	public function questions() {
		return $this->count('question');
	}
	
	//returns the number of active users in the specified time
	//@params: $time: string, possible values: recently, month, week, day, hour, minute
	public function user_access($time='recently') {
		global $DB, $CFG;
		$seconds = $this->convert_time($time);
		$users = $DB->get_records_sql('SELECT userid as number FROM '.$CFG->prefix.'log 
			WHERE time > '.$seconds.' 
			GROUP BY userid');
		return count($users);
	}
	
	//returns the number of current online users
	public function current_online() {
		return $this->user_access('recently');
	}
	
	//returns the number of opened pages in the specified time IT RETURNS A WRONG NUMBER!
	public function site_access($time='recently') {
		global $DB, $CFG;
		$seconds = $this->convert_time($time);
		$access = $DB->get_record_sql('SELECT COUNT(*) as number FROM '.$CFG->prefix.'log 
			WHERE time > '.$seconds);
		//print_r($access);
                return $access->number;
	}
	
	//returns the number of uploaded files
	public function files() {
		return $this->count('files');
	}
	
	public function getJson() {
		$data = array(
			'users' => $this->users(),
			'courses' => $this->courses(),
			'course_categories' => $this->course_categories(),
			'questions' => $this->questions(),
			'user_online' => $this->user_access(),
			'user_access_hour' => $this->user_access('hour'),
			'user_access_day' => $this->user_access('day'),
			'user_access_week' => $this->user_access('week'),
			'user_access_month' => $this->user_access('month'),
			'site_access_hour' => $this->user_access('hour'),
			'site_access_day' => $this->user_access('day'),
			'site_access_week' => $this->user_access('week'),
			'site_access_month' => $this->user_access('month'),
			'files' => $this->files()	
		);
		
		print json_encode($data);
		
	}
}

