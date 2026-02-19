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


namespace local_autogradehelper\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

class trigger_grading extends \external_api
{

    public static function execute_parameters()
    {
        return new \external_function_parameters([
            'assignmentid' => new \external_value(PARAM_INT, 'The assignment ID')
        ]);
    }

    public static function execute($assignmentid)
    {
        global $DB, $CFG;

        // DEBUG: Log what we received
        debugging('AUTOGRADEHELPER: execute() called with: ' . var_export($assignmentid, true), DEBUG_DEVELOPER);
        debugging('AUTOGRADEHELPER: func_get_args: ' . var_export(func_get_args(), true), DEBUG_DEVELOPER);

        $params = self::validate_parameters(self::execute_parameters(), ['assignmentid' => $assignmentid]);
        $assignmentid = $params['assignmentid'];

        // Context validation
        $cm = get_coursemodule_from_instance('assign', $assignmentid, 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/assign:grade', $context);

        // Get Global Settings
        $webhookurl = get_config('local_autogradehelper', 'webhookurl');
        $token = get_config('local_autogradehelper', 'token');

        if (empty($webhookurl)) {
            return ['success' => false, 'message' => 'Webhook URL not configured'];
        }

        // Get Assignment Settings
        $settings = $DB->get_record('local_autogradehelper_opts', ['assignmentid' => $assignmentid]);

        $payload = [
            'token' => $token,
            'courseId' => $cm->course,
            'assignmentId' => $assignmentid,
            'systemMessage' => $settings ? $settings->system_message : '',
            'preferredAgent' => $settings ? $settings->ai_agent : '',
            'complexity' => $settings ? $settings->complexity : ''
        ];

        // Send to n8n
        $curl = new \curl();
        $options = [
            'CURLOPT_HTTPHEADER' => ['Content-Type: application/json']
        ];

        // If token is needed in header as well, add it here. 
        // For now, we are sending it in the body as requested.

        $response = $curl->post($webhookurl, json_encode($payload), $options);
        $info = $curl->get_info();

        if ($info['http_code'] == 200) {
            return ['success' => true, 'message' => 'Triggered successfully'];
        } else {
            return ['success' => false, 'message' => 'n8n returned error: ' . $info['http_code']];
        }
    }

    public static function execute_returns()
    {
        return new \external_single_structure([
            'success' => new \external_value(PARAM_BOOL, 'Success status'),
            'message' => new \external_value(PARAM_TEXT, 'Message')
        ]);
    }
}
