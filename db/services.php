<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Autograde helper plugin.
 *
 * @package     local_autogradehelper
 * @copyright   2026 Mohammad Nabil <mohammad@smartlearn.education>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_autogradehelper_trigger_grading' => [
        'classname'   => 'local_autogradehelper\external\trigger_grading',
        'methodname'  => 'execute',
        'classpath'   => 'local/autogradehelper/classes/external/trigger_grading.php',
        'description' => 'Triggers the n8n grading workflow',
        'type'        => 'write',
        'ajax'        => true,
    ],
    'local_autogradehelper_check_feedback' => [
        'classname'   => 'local_autogradehelper\external\check_feedback',
        'methodname'  => 'execute',
        'classpath'   => 'local/autogradehelper/classes/external/check_feedback.php',
        'description' => 'Checks for AI feedback availability',
        'type'        => 'read',
        'ajax'        => true,
    ],
    'local_autogradehelper_save_rubric_grade' => [
        'classname'   => 'local_autogradehelper\external\save_rubric_grade',
        'methodname'  => 'execute',
        'classpath'   => 'local/autogradehelper/classes/external/save_rubric_grade.php',
        'description' => 'Saves a rubric grade for a submission',
        'type'        => 'write',
        'ajax'        => true,
    ],
    'local_autogradehelper_process_review' => [
        'classname'   => 'local_autogradehelper\external\process_review',
        'methodname'  => 'execute',
        'description' => 'Approve or Reject an AI draft review.',
        'type'        => 'write',
        'ajax'        => true,
    ],
];
