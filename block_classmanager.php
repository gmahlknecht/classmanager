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
 */

class block_classmanager extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_classmanager');
    }

    function get_content() {
        require_once("config.php");
        global $CFG;

        if ($this->content !== NULL) {
            return $this->content;
        }
        $this->content = new stdClass;
        global $DB;
        $edit_categories = array();
        $edit_courses = array();
        $categories = $DB->get_records('course_categories', array('parent' => CORE_CATEGORY));
        foreach ($categories as $category) {
            $context = context_coursecat::instance($category->id);
            if (has_capability(PERMISSION, $context)) {
                $edit_categories[] = $category;
            }
            if (has_capability("moodle/course:create", $context)) {
                $edit_courses[] = $category;
            }
        }

        if (is_array($edit_categories)) {
            $c = "";
            if (count($edit_categories) > 1) {
                foreach ($edit_categories as $category) {
                    $c .= "<a href=\"" . $CFG->wwwroot . "/blocks/classmanager/admin.php?category=" . $category->id . "\">" . $category->name . "</a><br>";
                }
            } else {
                foreach ($edit_categories as $category) {
                    $c .= "<b>" . $category->name . "</b><br>";
                    $c .= get_string("helpblock", "block_classmanager") . "<br>";
                    $c .= "<a href=\"" . $CFG->wwwroot . "/course/index.php?categoryid=" . $category->id . "\">" . get_string("courses") . "</a><br>";
                    $c .= "<a href=\"" . $CFG->wwwroot . "/blocks/classmanager/classes.php?category=" . $category->id . "\">" . get_string("classes", "block_classmanager") . "</a><br>";
                    $c .= "<a href=\"" . $CFG->wwwroot . "/blocks/classmanager/students.php?category=" . $category->id . "\">" . get_string("users") . "</a><br>";
                    $c .= "<a href=\"" . $CFG->wwwroot . "/blocks/classmanager/deleteusers.php?category=" . $category->id . "\">" . get_string("deleteusers", "block_classmanager") . "</a><br>";
                    $c .= "<a href=\"" . $CFG->wwwroot . "/blocks/classmanager/connections.php?category=" . $category->id . "\">" . get_string("connections", "block_classmanager") . "</a><br>";
                    $c .= "<a href=\"" . $CFG->wwwroot . "/blocks/classmanager/import.php?category=" . $category->id . "\">" . get_string("import", "block_classmanager") . "</a><br>";
                }
            }
        }

        if (is_array($edit_courses)) {
            $c .= "<a href=\"" . $CFG->wwwroot . "/blocks/classmanager/createcourse.php\">" . get_string("createcourse", "block_classmanager") . "</a><br>";
        }
        $c .= "<a href=\"" . $CFG->wwwroot . "/course/\">Kursbereiche anzeigen</a><br>";
        $c .= "<a href=\"" . $CFG->wwwroot . "/course/view.php?id=1194\">Hilfe und Support</a><br><br>";
        
        $this->content->text = $c;
        $this->content->footer = '<small>Class Manager by Stefan Raffeiner</small>';
        return $this->content;
    }

}

?>
