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


namespace local_autogradehelper\hook;

use core\hook\output\before_footer_html_generation;

defined('MOODLE_INTERNAL') || die();

class footer_injection
{
    public static function callback(before_footer_html_generation $hook)
    {
        global $PAGE;

        // DEBUG: Unconditional log to prove the hook runs
        $PAGE->requires->js_init_code("console.log('Autograde Helper: PHP Hook Running on page: " . $PAGE->pagetype . "');");

        // Run on any assignment page (view, grading, etc.)
        if (strpos($PAGE->pagetype, 'mod-assign-') === 0) {
            $context = $PAGE->context;
            if ($context->contextlevel == CONTEXT_MODULE && $PAGE->cm->modname === 'assign') {
                // DEBUG: Log context check pass
                $PAGE->requires->js_init_code("console.log('Autograde Helper: Context Check Passed');");

                if (has_capability('mod/assign:grade', $context)) {
                    // DEBUG: Log capability check pass
                    $PAGE->requires->js_init_code("console.log('Autograde Helper: Capability Check Passed');");

                    $PAGE->requires->js_call_amd('local_autogradehelper/grader', 'init', [
                        'assignmentid' => (int)$PAGE->cm->instance,
                        'courseid' => (int)$PAGE->course->id
                    ]);
                } else {
                    $PAGE->requires->js_init_code("console.log('Autograde Helper: Capability Check FAILED');");
                }
            }
        } else {
            $PAGE->requires->js_init_code("console.log('Autograde Helper: Page Type Check FAILED');");
        }
    }
}
