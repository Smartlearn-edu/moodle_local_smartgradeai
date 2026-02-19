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

function xmldb_local_autogradehelper_upgrade($oldversion)
{
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2023122801) {
        // Define field enable_student_button to be added to local_autogradehelper_opts.
        $table = new xmldb_table('local_autogradehelper_opts');
        $field = new xmldb_field('enable_student_button', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'complexity');

        // Conditionally launch add field enable_student_button.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Autogradehelper savepoint reached.
        upgrade_plugin_savepoint(true, 2023122801, 'local', 'autogradehelper');
    }

    if ($oldversion < 2026020201) {
        // Define field review_mode to be added to local_autogradehelper_opts.
        $table = new xmldb_table('local_autogradehelper_opts');
        $field = new xmldb_field('review_mode', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'enable_student_button');

        // Conditionally launch add field review_mode.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Autogradehelper savepoint reached.
        upgrade_plugin_savepoint(true, 2026020201, 'local', 'autogradehelper');
    }

    if ($oldversion < 2026020202) {
        // Define table local_autogradehelper_reviews to be created.
        $table = new xmldb_table('local_autogradehelper_reviews');

        // Adding fields to table local_autogradehelper_reviews.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('assignmentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('submissionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('graderid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('rubric_data', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('feedback_text', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pending');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_autogradehelper_reviews.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table local_autogradehelper_reviews.
        $table->add_index('submissionid', XMLDB_INDEX_NOTUNIQUE, ['submissionid']);
        $table->add_index('assignmentid', XMLDB_INDEX_NOTUNIQUE, ['assignmentid']);
        $table->add_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']);

        // Conditionally launch create table for local_autogradehelper_reviews.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Autogradehelper savepoint reached.
        upgrade_plugin_savepoint(true, 2026020202, 'local', 'autogradehelper');
    }

    return true;
}
