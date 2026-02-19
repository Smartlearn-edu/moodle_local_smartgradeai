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
 * Grader helper class.
 *
 * @package     local_autogradehelper
 * @copyright   2026 Mohammad Nabil <mohammad@smartlearn.education>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_autogradehelper;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/grade/grading/lib.php');

use assign;
use context_module;
use grading_manager;
use stdClass;

class grader_helper
{

    /**
     * Save a rubric grade for a user in an assignment.
     * 
     * @param int $assignmentid
     * @param int $userid
     * @param array $rubric_items Array of rubric criteria and levels
     * @param int $graderid The user ID of the grader (or 0 for system)
     * @return array Result with success boolean and message
     */
    public static function save_rubric_grade($assignmentid, $userid, $rubric_items, $graderid)
    {
        global $DB, $CFG, $PAGE;

        // Get course module
        $cm = get_coursemodule_from_instance('assign', $assignmentid);
        if (!$cm) {
            return ['success' => false, 'message' => 'Invalid course module'];
        }
        $context = context_module::instance($cm->id);

        // Get course - needed for PAGE setup
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

        // CRITICAL: Fully initialize $PAGE before any grading operations
        // get_active_controller() internally accesses $PAGE properties
        // We only set this up if we are not already in a valid page context context to avoid stomping
        if ($PAGE->context->id !== $context->id) {
            $PAGE->set_context($context);
            $PAGE->set_url(new \moodle_url('/mod/assign/view.php', ['id' => $cm->id]));
            $PAGE->set_course($course);
            $PAGE->set_cm($cm);
        }

        // Create assign instance
        $assign = new assign($context, $cm, $course);

        // Get Grading Manager for rubric
        $gradingmanager = new grading_manager();
        $gradingmanager->set_context($context);
        $gradingmanager->set_component('mod_assign');
        $gradingmanager->set_area('submissions');

        $controller = $gradingmanager->get_active_controller();

        // Check if rubric grading is enabled
        if (!$controller) {
            return [
                'success' => false,
                'message' => 'No active grading controller found. Grading method for assignment ID ' . $assignmentid . ' may not be set to rubric.'
            ];
        }

        // Build rubric grade data structure for the controller
        $rubric_grades = ['criteria' => []];
        foreach ($rubric_items as $item) {
            $item = (array) $item;
            if (!isset($item['criterionid']) || !isset($item['levelid'])) {
                continue;
            }
            // Use string keys to match Moodle's internal format
            $cid = (string)$item['criterionid'];
            $rubric_grades['criteria'][$cid] = [
                'levelid' => (string)$item['levelid'],
                'remark' => isset($item['remark']) ? $item['remark'] : ''
            ];
        }

        // Get the grade record for this user
        $grade = $assign->get_user_grade($userid, true);

        // LOGIC: Save directly to database tables (simulating rubric submission)
        $definition = $controller->get_definition();
        $definitionid = $definition->id;

        // Create grading instance record
        $instance_record = new stdClass();
        $instance_record->definitionid = $definitionid;
        $instance_record->raterid = $graderid > 0 ? $graderid : 2; // Default to admin (2) if 0 passed? Or handle better.
        // Actually, if it's AI (0), maybe we should attribute it to a specific system user?
        // For now let's use the passed graderid. If specific logic needed, add here.
        if ($graderid <= 0) {
            $instance_record->raterid = 2; // Fallback to admin if 0
        }

        $instance_record->itemid = $grade->id;
        $instance_record->rawgrade = null; // Will calculate after
        $instance_record->status = 1; // INSTANCE_STATUS_ACTIVE
        $instance_record->feedback = '';
        $instance_record->feedbackformat = FORMAT_HTML;
        $instance_record->timemodified = time();

        // Check if instance exists
        $existing = $DB->get_record('grading_instances', [
            'definitionid' => $definitionid,
            'itemid' => $grade->id
        ]);

        if ($existing) {
            $instance_record->id = $existing->id;
            $DB->update_record('grading_instances', $instance_record);
            $instanceid = $existing->id;
            // Delete old fillings
            $DB->delete_records('gradingform_rubric_fillings', ['instanceid' => $instanceid]);
        } else {
            $instanceid = $DB->insert_record('grading_instances', $instance_record);
        }

        // Insert rubric fillings and calculate total score
        $total_score = 0;
        foreach ($rubric_grades['criteria'] as $criterionid => $data) {
            $filling = new stdClass();
            $filling->instanceid = $instanceid;
            $filling->criterionid = (int)$criterionid;
            $filling->levelid = (int)$data['levelid'];
            $filling->remark = $data['remark'] ?? '';
            $filling->remarkformat = FORMAT_HTML;

            $DB->insert_record('gradingform_rubric_fillings', $filling);

            // Get level score
            $level = $DB->get_record('gradingform_rubric_levels', ['id' => $filling->levelid]);
            if ($level) {
                $total_score += $level->score;
            }
        }

        // Update instance with calculated raw grade
        $DB->set_field('grading_instances', 'rawgrade', $total_score, ['id' => $instanceid]);

        // Calculate the normalized grade (scale to assignment max grade)
        $max_grade = $assign->get_instance()->grade;
        $rubric_max = self::get_rubric_max_score($DB, $definitionid);

        if ($rubric_max > 0 && $max_grade > 0) {
            $normalized_grade = ($total_score / $rubric_max) * $max_grade;
        } else {
            $normalized_grade = $total_score;
        }

        // Update the grade record
        $grade->grade = $normalized_grade;
        $grade->grader = $instance_record->raterid;

        // Save the final grade
        try {
            $assign->update_grade($grade);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error saving grade: ' . $e->getMessage()];
        }

        return [
            'success' => true,
            'message' => 'Rubric grade saved successfully. Grade: ' . round($normalized_grade, 2) . '/' . $max_grade
        ];
    }

    /**
     * Calculate the maximum possible score for a rubric
     */
    private static function get_rubric_max_score($DB, $definitionid)
    {
        $criteria = $DB->get_records('gradingform_rubric_criteria', ['definitionid' => $definitionid]);
        $max_score = 0;

        foreach ($criteria as $criterion) {
            // Get the highest score level for this criterion
            $max_level = $DB->get_field_sql(
                'SELECT MAX(score) FROM {gradingform_rubric_levels} WHERE criterionid = ?',
                [$criterion->id]
            );
            $max_score += (float)$max_level;
        }

        return $max_score;
    }
}
