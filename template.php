<?php

require_once("../../config.php"); //for Moodle integration
require_once("config.php"); //for Classmanager Configuration
$params = array();
$PAGE->set_url('/my/index.php', $params);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('my-index');
$PAGE->blocks->add_region('content');
$context = get_context_instance(CONTEXT_USER, $USER->id);
$PAGE->set_context($context);
$c = '';
$header = get_string('adminpagetitle', 'block_classmanager');
$PAGE->navbar->add(get_string('manageschools', 'block_classmanager')); //, new moodle_url('/a/link/if/you/want/one.php'));


$PAGE->set_title($header);
$PAGE->set_heading($header);
$PAGE->navbar->ignore_active();
echo $OUTPUT->header();
echo $OUTPUT->blocks_for_region('content');
echo $c;
echo $OUTPUT->footer();

