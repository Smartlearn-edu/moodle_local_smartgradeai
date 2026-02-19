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
 * Autograde helper library functions.
 *
 * @package     local_autogradehelper
 * @copyright   2026 Mohammad Nabil <mohammad@smartlearn.education>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extend the settings navigation to add the AI Grader Settings link.
 *
 * @param settings_navigation $settingsnav
 * @param context $context
 */
/**
 * Extend the settings navigation to add the AI Grader Settings link.
 *
 * @param settings_navigation $settingsnav
 * @param context $context
 */
/**
 * Extend the settings navigation to add the AI Grader Settings link.
 *
 * @param settings_navigation $settingsnav
 * @param context $context
 */
function local_autogradehelper_extend_settings_navigation(settings_navigation $settingsnav, context $context)
{
    global $PAGE, $DB, $USER;

    if ($context->contextlevel == CONTEXT_MODULE && $PAGE->cm->modname === 'assign') {
        $assignmentid = (int)$PAGE->cm->instance;
        $courseid = (int)$PAGE->course->id;
        $userid = (int)$USER->id;
        $submissionid = 0;

        // Check if the current user has a submission
        // We look for the latest submission for this user in this assignment
        $attemptnumber = 0;
        $submissionstatus = 'new';
        if ($submission = $DB->get_record('assign_submission', ['assignment' => $assignmentid, 'userid' => $userid, 'latest' => 1])) {
            $submissionid = (int)$submission->id;
            if (isset($submission->attemptnumber)) {
                $attemptnumber = (int)$submission->attemptnumber;
            }
            $submissionstatus = $submission->status;
            // If status is 'new', it means the student hasn't actually submitted anything substantive yet
            // treating it as no submission for our purposes.
            if ($submission->status === 'new') {
                $submissionid = 0;
            }
        }

        $isteacher = has_capability('mod/assign:grade', $context);

        // 1. TEACHER LOGIC (Settings + Grade Button)
        if ($isteacher) {
            $url = new moodle_url('/local/autogradehelper/settings_page.php', [
                'courseid' => $courseid,
                'assignmentid' => $assignmentid
            ]);

            $node = $settingsnav->find('modulesettings', navigation_node::TYPE_SETTING);
            if ($node) {
                $node->add(
                    get_string('settings_link', 'local_autogradehelper'),
                    $url,
                    navigation_node::TYPE_SETTING
                );

                // Link to Pending Reviews Dashboard
                if (get_config('local_autogradehelper', 'enable_review_mode')) {
                    $review_url = new moodle_url('/local/autogradehelper/reviews.php');
                    $node->add(
                        'Pending AI Reviews', // Hardcoded string for now or get_string if exists
                        $review_url,
                        navigation_node::TYPE_SETTING,
                        null,
                        'local_autogradehelper_reviews',
                        new pix_icon('i/grades', '')
                    );
                }
            }

            // Inject AMD Module strictly for teachers (restoring original behavior)
            $PAGE->requires->js_call_amd('local_autogradehelper/grader', 'init', [
                'assignmentid' => $assignmentid,
                'courseid' => $courseid,
                'userid' => $userid,
                'submissionid' => $submissionid,
                'isteacher' => true
            ]);
        }

        // 2. STUDENT LOGIC (Check Feedback Button)
        // We use Raw JS injection here to avoid AMD caching issues for the new feature
        // Skip if we are on the 'editsubmission' page
        $action = optional_param('action', '', PARAM_ALPHA);
        if (!$isteacher && $action !== 'editsubmission') {
            // Check if enabled by teacher
            $opts = $DB->get_record('local_autogradehelper_opts', ['assignmentid' => $assignmentid]);
            if (!$opts || empty($opts->enable_student_button)) {
                return;
            }

            $job_status = 'ready';
            $job_time = 0;
            $is_graded = false;

            if ($submissionid) {
                // Check Job Status
                $job = $DB->get_record('local_autogradehelper_jobs', ['submissionid' => $submissionid]);
                $job_status = $job ? $job->status : 'ready';
                $job_time = $job ? $job->timemodified : 0;

                // Check if Graded
                // Moodle assign grades usually store -1.00000 if not graded, or >= 0 if graded.
                // We use the calculated $attemptnumber to check the SPECIFIC attempt's grade, not just attempt 0.
                $grade_record = $DB->get_record('assign_grades', ['assignment' => $assignmentid, 'userid' => $userid, 'attemptnumber' => $attemptnumber]);
                $is_graded = ($grade_record && $grade_record->grade >= 0);
            }

            // Check if student passed
            $has_passed = false;
            $gradeitem = $DB->get_record('grade_items', ['courseid' => $courseid, 'itemtype' => 'mod', 'itemmodule' => 'assign', 'iteminstance' => $assignmentid, 'itemnumber' => 0]);
            if ($gradeitem && $gradeitem->gradepass > 0 && $is_graded && $grade_record) {
                // Precision check might be needed but simple comparison usually works for float in this context
                if ($grade_record->grade >= $gradeitem->gradepass) {
                    $has_passed = true;
                }
            }

            $current_time = time();

            // Fetch max attempts
            $assign_record = $DB->get_record('assign', ['id' => $assignmentid], 'maxattempts');
            $maxattempts = $assign_record ? (int)$assign_record->maxattempts : 1;

            // Pass PHP variables to JS safely
            $js_vars = "
                var agh_assignmentid = {$assignmentid};
                var agh_courseid = {$courseid};
                var agh_userid = {$userid};
                var agh_submissionid = {$submissionid};
                var agh_submissionstatus = '{$submissionstatus}';
                var agh_status = '{$job_status}';
                var agh_jobtime = {$job_time};
                var agh_now = {$current_time};
                var agh_now = {$current_time};
                var agh_isgraded = " . ($is_graded ? 'true' : 'false') . ";
                var agh_haspassed = " . ($has_passed ? 'true' : 'false') . ";
                var agh_hassubmission = " . ($submissionid ? 'true' : 'false') . ";
                var agh_maxattempts = {$maxattempts};
                var agh_attemptnumber = {$attemptnumber};
             ";
            $PAGE->requires->js_init_code($js_vars);

            $js_logic = <<<JS
                require(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
                    $(document).ready(function() {
                        // Avoid duplicates
                        if ($('#autogradehelper-student-btn').length) return;

                        var btnLabel = "Check AI Feedback";
                        var isInitiallyDisabled = false;
                        var btnClass = "btn btn-info ml-2";

                        // Logic B & C: Check Status
                        
                        // New Condition: Enable if multiple attempts allowed and student has chances left
                        var maxAttempts = agh_maxattempts;
                        var attemptNum = agh_attemptnumber;
                        // "If assignment has more than one Allowed attempts"
                        var isMultiAttempt = (maxAttempts === -1) || (maxAttempts > 1);
                        // "and student still have a chance" (meaning they haven't exhausted all attempts)
                        // If they are on the last attempt, attemptNum + 1 == maxAttempts, so hasChance is false.
                        // This matches the requirement to only enable if they "still have a chance".
                        var hasChance = (maxAttempts === -1) || ((attemptNum + 1) < maxAttempts);
                        
                        var enableOverride = isMultiAttempt && hasChance;

                        if (!agh_hassubmission || agh_submissionstatus !== 'submitted') {
                            btnLabel = "Submit to use AI Feedback";
                            isInitiallyDisabled = true;
                            btnClass = "btn btn-secondary ml-2";
                        } else if (agh_haspassed) {
                            btnLabel = "Passed - Great Job!";
                            isInitiallyDisabled = true;
                            btnClass = "btn btn-success ml-2";
                        } else if (agh_isgraded && !enableOverride) {
                            btnLabel = "Feedback Available";
                            isInitiallyDisabled = true; 
                            btnClass = "btn btn-success ml-2"; // Green if done
                        } else if (agh_status === 'pending') {
                            var diff = agh_now - agh_jobtime;
                            if (diff < 600) { // 10 minutes
                                var minsLeft = Math.ceil((600 - diff) / 60);
                                btnLabel = "AI is thinking... (" + minsLeft + "m)";
                                isInitiallyDisabled = true;
                                btnClass = "btn btn-secondary ml-2"; 
                            }
                            // If > 10 mins, we leave it enabled (Reset)
                        }

                        var studentButton = $('<button id="autogradehelper-student-btn" class="' + btnClass + '">' + btnLabel + '</button>');
                        if (isInitiallyDisabled) {
                            studentButton.prop('disabled', true);
                        }
                        
                        studentButton.click(function(e) {
                            e.preventDefault();
                            studentButton.prop('disabled', true);
                            
                            Ajax.call([{
                                methodname: 'local_autogradehelper_check_feedback',
                                args: { 
                                    submissionid: agh_submissionid,
                                    assignmentid: agh_assignmentid,
                                    courseid: agh_courseid, 
                                    userid: agh_userid
                                }
                            }])[0].done(function(response) {
                                if (response.success) {
                                    Notification.alert('Feedback', response.message || 'Feedback request sent!', 'Ok');
                                    // User requirement: "feedback_status should be set to pending... button disabled"
                                    studentButton.text("Feedback request sent to AI Agent.");
                                    studentButton.prop('disabled', true);
                                    studentButton.removeClass('btn-info').addClass('btn-secondary');
                                } else {
                                    Notification.alert('Info', response.message || 'No feedback available yet.', 'Ok');
                                    // User requirement: "if fail , dont change feedback_status" -> Re-enable
                                    studentButton.prop('disabled', false);
                                }
                            }).fail(function(ex) {
                                console.error(ex);
                                Notification.alert('Error', 'Could not fetch feedback status.', 'Ok');
                                studentButton.prop('disabled', false);
                            });
                        });

                        // Append to the UI
                        var container = $('.submissionstatustable').first();
                        
                        if ($('.submissionlinks').length) {
                             $('.submissionlinks').append(studentButton);
                        } else if (container.length) {
                            container.after(studentButton);
                        } else if ($('[role="main"]').length) {
                            $('[role="main"]').append(studentButton);
                        }
                    });
                });
JS;
            $PAGE->requires->js_init_code($js_logic);
        }
    }
}
