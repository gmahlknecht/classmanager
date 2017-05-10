<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Class manager for multiple schools
 *
 * @package    block_classmanager
 * @copyright  2017 Stefan Raffeiner, Giovanni Mahlknecht
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_login();

/**
 * Class Manager block class
 *
 * @copyright  2017 Stefan Raffeiner, Giovanni Mahlknecht
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_classmanager extends block_base {

    /**
     * Sets the block title
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_classmanager');
    }

    /**
     * Creates the blocks main content
     *
     * @return string
     */
    public function get_content() {
        require_once("config.php");
        global $CFG;

        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = new stdClass;
        global $DB;
        $editcategories = array();
        $editcourses = array();
        $categories = $DB->get_records('course_categories', array('parent' => CORE_CATEGORY));
        foreach ($categories as $category) {
            $context = context_coursecat::instance($category->id);
            if (has_capability(PERMISSION, $context)) {
                $editcategories[] = $category;
            }
            if (has_capability("moodle/course:create", $context)) {
                $editcourses[] = $category;
            }
        }

        if (is_array($editcategories)) {
            $result = "";
            if (count($editcategories) > 1) {
                foreach ($editcategories as $category) {
                    $result .= "<a href=\"" . $CFG->wwwroot . "/blocks/classmanager/admin.php?category=".$category->id."\">"
                            . $category->name . "</a><br>";
                }
            } else {
                foreach ($editcategories as $category) {
                    $result .= "<b>" . $category->name . "</b><br>";
                    $result .= get_string("helpblock", "block_classmanager")."<br>";
                    $result .= "<a href=\"".$CFG->wwwroot."/course/index.php?categoryid=" . $category->id . "\">"
                            . get_string("courses")."</a><br>";
                    $result .= "<a href=\"".$CFG->wwwroot."/blocks/classmanager/classes.php?category=" . $category->id."\">"
                            . get_string("classes", "block_classmanager")."</a><br>";
                    $result .= "<a href=\"".$CFG->wwwroot."/blocks/classmanager/students.php?category=".$category->id."\">"
                            . get_string("users") . "</a><br>";
                    $result .= "<a href=\"".$CFG->wwwroot."/blocks/classmanager/deleteusers.php?category=".$category->id."\">"
                            . get_string("deleteusers", "block_classmanager") . "</a><br>";
                    $result .= "<a href=\"".$CFG->wwwroot."/blocks/classmanager/connections.php?category=".$category->id."\">"
                            . get_string("connections", "block_classmanager") . "</a><br>";
                    $result .= "<a href=\"".$CFG->wwwroot."/blocks/classmanager/import.php?category=" . $category->id."\">"
                            . get_string("import", "block_classmanager") . "</a><br>";
                }
            }
        }

        if (is_array($editcourses)) {
            $result .= "<a href=\"" . $CFG->wwwroot . "/blocks/classmanager/createcourse.php\">"
                    . get_string("createcourse", "block_classmanager") . "</a><br>";
        }
        $result .= "<a href=\"" . $CFG->wwwroot . "/course/\">Kursbereiche anzeigen</a><br>";
        // TODO: remove Hilfe und Support!
        $result .= "<a href=\"" . $CFG->wwwroot . "/course/view.php?id=1194\">Hilfe und Support</a><br><br>";
        $this->content->text = $result;
        $this->content->footer = '<small>Class Manager</small>';
        return $this->content;
    }

}
