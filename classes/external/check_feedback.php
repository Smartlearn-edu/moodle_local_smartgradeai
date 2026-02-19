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
 * Smart Grade AI plugin.
 *
 * @package     local_smartgradeai
 * @copyright   2026 Mohammad Nabil <mohammad@smartlearn.education>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace local_smartgradeai\external;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use context_course;

class check_feedback extends external_api
{

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters()
    {
        return new external_function_parameters([
            'assignmentid' => new external_value(PARAM_INT, 'Assignment ID'),
            'courseid'     => new external_value(PARAM_INT, 'Course ID'),
            'userid'       => new external_value(PARAM_INT, 'User ID'),
            'submissionid' => new external_value(PARAM_INT, 'Submission ID'),
        ]);
    }

    /**
     * Execution method
     *
     * @param int $assignmentid
     * @param int $courseid
     * @param int $userid
     * @param int $submissionid
     * @return array
     */
    public static function execute($assignmentid, $courseid, $userid, $submissionid)
    {
        global $DB;

        // Validate parameters
        $params = self::validate_parameters(self::execute_parameters(), [
            'assignmentid' => $assignmentid,
            'courseid'     => $courseid,
            'userid'       => $userid,
            'submissionid' => $submissionid,
        ]);

        // Context validation
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);
        // Students should be able to view the assignment to check feedback
        require_capability('mod/assign:view', $context);

        // Get Global Settings
        $webhookurl = get_config('local_smartgradeai', 'webhookurl');
        $token = get_config('local_smartgradeai', 'token');

        if (empty($webhookurl)) {
            return ['success' => false, 'message' => 'Webhook URL not configured'];
        }

        // Get Assignment Settings (to pass AI model preferences etc.)
        $settings = $DB->get_record('local_smartgradeai_opts', ['assignmentid' => $assignmentid]);

        $payload = [
            'token' => $token,
            'action' => 'check_feedback',
            'courseId' => $courseid,
            'assignmentId' => $assignmentid,
            'userId' => $userid,
            'submissionId' => $submissionid,
            'systemMessage' => $settings ? $settings->system_message : '',
            'preferredAgent' => $settings ? $settings->ai_agent : '',
            'complexity' => $settings ? $settings->complexity : ''
        ];

        // Send to n8n
        $curl = new \curl();
        $options = [
            'CURLOPT_HTTPHEADER' => ['Content-Type: application/json']
        ];

        $response = $curl->post($webhookurl, json_encode($payload), $options);
        $info = $curl->get_info();

        if ($info['http_code'] == 200) {
            // Update Job Status to 'pending'
            $existing_job = $DB->get_record('local_smartgradeai_jobs', ['submissionid' => $submissionid]);

            $job = new \stdClass();
            $job->submissionid = $submissionid;
            $job->status = 'pending';
            $job->timemodified = time();

            if ($existing_job) {
                $job->id = $existing_job->id;
                $DB->update_record('local_smartgradeai_jobs', $job);
            } else {
                $job->timecreated = time();
                $DB->insert_record('local_smartgradeai_jobs', $job);
            }

            // We can optionally return the n8n response body if it contains a message
            // $responseData = json_decode($response, true);
            // $message = isset($responseData['message']) ? $responseData['message'] : 'Feedback request sent successfully';
            return ['success' => true, 'message' => 'Feedback request sent to AI Agent.'];
        } else {
            return ['success' => false, 'message' => 'Error connecting to AI service: ' . $info['http_code']];
        }
    }

    /**
     * Returns description of method result value
     * @return external_single_structure
     */
    public static function execute_returns()
    {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Status of the operation'),
            'message' => new external_value(PARAM_TEXT, 'Message from the server'),
        ]);
    }
}
