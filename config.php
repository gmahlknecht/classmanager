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
defined('MOODLE_INTERNAL') || die();
define("CATEGORY", 2); // Category with connected fachbereiche
define("CORE_CATEGORY", 16); // Category with connected fachbereiche
define("ROLEID", 11); // Role teachers have in a connected fachbereich
define("TEACHERROLE", 12); // Role teachers have in there schools
define("NOTTEACHINGROLE", 18); // Role not teachers have in there schools
define("STUDENTROLE", 17); // Role students have in there schools
define("PERMISSION", "block/classmanager:manage"); // What capability a user has to have to use classmanager?
