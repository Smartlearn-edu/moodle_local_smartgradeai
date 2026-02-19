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

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/form/settings_form.php');

$courseid = required_param('courseid', PARAM_INT);
$assignmentid = required_param('assignmentid', PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('assign', $assignmentid, $courseid, false, MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/assign:grade', $context);

$PAGE->set_url(new moodle_url('/local/autogradehelper/settings_page.php', ['courseid' => $courseid, 'assignmentid' => $assignmentid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('settings_link', 'local_autogradehelper'));
$PAGE->set_heading($course->fullname);

$form = new \local_autogradehelper\form\settings_form();

// Load existing settings
$existing = $DB->get_record('local_autogradehelper_opts', ['assignmentid' => $assignmentid]);
$default_data = [
    'courseid' => $courseid,
    'assignmentid' => $assignmentid
];
if ($existing) {
    $default_data['system_message'] = $existing->system_message;
    $default_data['ai_agent'] = $existing->ai_agent;
    $default_data['complexity'] = $existing->complexity;
    $default_data['enable_student_button'] = $existing->enable_student_button;
    $default_data['review_mode'] = $existing->review_mode ?? 0;
}
$form->set_data($default_data);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/mod/assign/view.php', ['id' => $cm->id]));
} else if ($data = $form->get_data()) {
    $record = new stdClass();
    $record->assignmentid = $data->assignmentid;
    $record->enable_student_button = $data->enable_student_button;

    // Only save review_mode if enabled at system level
    if (get_config('local_autogradehelper', 'enable_review_mode')) {
        $record->review_mode = $data->review_mode;
    } else {
        $record->review_mode = 0; // Default off if system disabled
    }

    $record->system_message = $data->system_message;
    $record->ai_agent = $data->ai_agent;
    $record->complexity = $data->complexity;
    $record->timemodified = time();

    if ($existing) {
        $record->id = $existing->id;
        $DB->update_record('local_autogradehelper_opts', $record);
    } else {
        $record->timecreated = time();
        $DB->insert_record('local_autogradehelper_opts', $record);
    }
    redirect(new moodle_url('/mod/assign/view.php', ['id' => $cm->id]), get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('settings_link', 'local_autogradehelper'));
$form->display();
echo $OUTPUT->footer();
