<?php

/**
 * Dashboard to review pending AI grades.
 *
 * @package     local_autogradehelper
 * @copyright   2026 Mohammad Nabil <mohammad@smartlearn.education>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

global $DB, $PAGE, $OUTPUT, $USER;

// Check login
require_login();

// Set context (System context for plugin dashboard, usually)
$context = context_system::instance();
$PAGE->set_context($context);

$url = new moodle_url('/local/autogradehelper/reviews.php');
$PAGE->set_url($url);
$PAGE->set_title(get_string('pluginname', 'local_autogradehelper') . ': Pending Reviews');
$PAGE->set_heading('Pending AI Reviews');

// Custom simplified layout (admin like)
$PAGE->set_pagelayout('report');

echo $OUTPUT->header();

// Fetch pending reviews
// Join with assignment and user tables for more info
$sql = "SELECT r.id, r.assignmentid, r.submissionid, r.userid, r.timecreated,
               a.name as assignmentname, c.fullname as coursename,
               u.firstname, u.lastname
        FROM {local_autogradehelper_reviews} r
        JOIN {assign} a ON a.id = r.assignmentid
        JOIN {course} c ON c.id = a.course
        JOIN {user} u ON u.id = r.userid
        WHERE r.status = :status
        ORDER BY r.timecreated ASC";

$reviews = $DB->get_records_sql($sql, ['status' => 'pending']);

if (empty($reviews)) {
    echo $OUTPUT->notification('No pending reviews found. Good job!', 'success');
} else {
    echo html_writer::tag('h3', 'Pending Reviews (' . count($reviews) . ')');

    $table = new html_table();
    $table->head = ['Student', 'Assignment', 'Course', 'Waiting Since', 'Action'];
    $table->data = [];

    foreach ($reviews as $review) {
        $user_fullname = fullname($review);
        $review_url = new moodle_url('/local/autogradehelper/review.php', ['id' => $review->id]);
        $time_waiting = userdate($review->timecreated);

        // Action Button
        $btn = $OUTPUT->single_button($review_url, 'Review', 'get');

        $table->data[] = [
            $user_fullname,
            format_string($review->assignmentname),
            format_string($review->coursename),
            $time_waiting,
            $btn
        ];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
