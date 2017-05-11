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
 * Classmanager config file
 *
 * @package block_classmanager
 * @copyright 2017 Stefan Raffeiner, Giovanni Mahlknecht
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("../../config.php");
require_login();
require_once("config.php");

$params = array ();
$PAGE->set_url('/my/index.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');

$context = context_user::instance($USER->id);
$PAGE->set_context($context);

$c = '';

$header = get_string('connectionspagetitle', 'block_classmanager');

if (! isset($_GET['category']) and ! isset($_POST['category'])) {
    $c .= "no category";
} else {
    if (isset($_GET['category'])) {
        $categoryid = $_GET['category'];
    } else {
        $categoryid = $_POST['category'];
    }
    $context = context_coursecat::instance($categoryid);
    if (has_capability(PERMISSION, $context)) {
        $school = $DB->get_record('course_categories', array (
                'id' => $categoryid
        ));
        $newurl = new moodle_url($CFG->wwwroot . '/blocks/classmanager/admin.php?category=' . $categoryid);
        $PAGE->navbar->add(get_string('manage', 'block_classmanager') . ' ' . $school->name, $newurl);
        $PAGE->navbar->add(get_string('connections', 'block_classmanager'));
        $c .= get_string('connectionsdescription', 'block_classmanager');
        $context = context_coursecat::instance($categoryid);
        if (isset($_GET['connect']) and $_GET['connect'] != '' and $_GET['connect'] > 0) {
            $topic = $DB->get_record('course_categories', array (
                    'parent' => CATEGORY,
                    'id' => $_GET['connect']
            ));
            if (is_object($topic)) {
                $connected = $DB->get_records('course_categories', array (
                        'parent' => $topic->id,
                        'name' => $school->name
                ));
                if (! (is_array($connected) and count($connected) > 0)) {
                    $newcategory = new stdClass();
                    $newcategory->name = $school->name;
                    $newcategory->parent = $_GET['connect'];
                    $newcategory->descriptionformat = 1;
                    $newcategory->description = $topic->name . ' - ' . $school->name;
                    $newcategory->sortorder = 999;
                    $newcategory->id = $DB->insert_record('course_categories', $newcategory);
                    $newcategory->context = get_context_instance(CONTEXT_COURSECAT, $newcategory->id);
                    $categorycontext = $newcategory->context;
                    mark_context_dirty($newcategory->context->path);
                    $c .= '<br><b><font color="green">' . get_string('connectioncreated', 'block_classmanager');
                    $c .= '</font></b>';
                } else {
                    $c .= '<br><b><font color="green">' . get_string('connectionalreadyexists', 'block_classmanager');
                    $c .= '</font></b>';
                }
            } else {
                $c .= '<b><font color="red">berechtigungsfehler</font></b>';
            }
        }
        $topics = $DB->get_records('course_categories', array (
                'parent' => CATEGORY
        ));
        if (is_array($topics) and count($topics) > 0) {
            $c .= "<hr>";
            foreach ($topics as $topic) {
                $c .= '<table width="100%"><tr><td><h2>' . $topic->name . '</h2></td><td>';
                $connected = $DB->get_records('course_categories', array (
                        'parent' => $topic->id,
                        'name' => $school->name
                ));
                if (is_array($connected) and count($connected) > 0) {
                    $c .= "<font color=\"green\">" . get_string('connected', 'block_classmanager') . "</font>";
                } else {
                    $c .= "Zur Zeit können keine Verbindungen hergestellt werden. Bitte kontaktieren Sie einen Administrator";
                }
                $c .= '</td></tr></table>' . $topic->description . '<hr>';
            }
        }
    } else {
        $c .= get_string('rightsproblem', 'block_classmanager');
    }
}

$PAGE->set_title($header);
$PAGE->set_heading($header);
$PAGE->navbar->ignore_active();
echo $OUTPUT->header();
echo $OUTPUT->blocks_for_region('content');
echo $c;
echo $OUTPUT->footer();