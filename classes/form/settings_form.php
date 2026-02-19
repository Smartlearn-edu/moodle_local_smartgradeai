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


namespace local_autogradehelper\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class settings_form extends \moodleform
{
    public function definition()
    {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('settings_link', 'local_autogradehelper'));

        // Enable Student Review Button
        $mform->addElement('advcheckbox', 'enable_student_button', get_string('enable_student_button', 'local_autogradehelper'), get_string('enable_student_button_desc', 'local_autogradehelper'), [], [0, 1]);
        $mform->setDefault('enable_student_button', 0);
        $mform->setType('enable_student_button', PARAM_INT);

        // Review Mode (Human-in-the-Loop)
        if (get_config('local_autogradehelper', 'enable_review_mode')) {
            $mform->addElement('advcheckbox', 'review_mode', get_string('review_mode', 'local_autogradehelper'), get_string('review_mode_desc', 'local_autogradehelper'), [], [0, 1]);
            $mform->setDefault('review_mode', 0);
            $mform->setType('review_mode', PARAM_INT);
        }

        // System Message
        $mform->addElement('textarea', 'system_message', get_string('system_message', 'local_autogradehelper'), 'wrap="virtual" rows="10" cols="50"');
        $mform->addHelpButton('system_message', 'system_message', 'local_autogradehelper');
        $mform->setType('system_message', PARAM_TEXT);

        // AI Agent
        $agents = [];
        $models_config = get_config('local_autogradehelper', 'availablemodels');
        if (!empty($models_config)) {
            $lines = explode("\n", $models_config);
            foreach ($lines as $line) {
                $line = trim($line);
                if (!empty($line)) {
                    // Use the model name as both key and value
                    $agents[$line] = $line;
                }
            }
        }

        // Fallback if empty
        if (empty($agents)) {
            $agents = [
                'Gemini' => 'Gemini',
                'Claude' => 'Claude',
                'OpenAI' => 'OpenAI',
                'Deepseek' => 'Deepseek',
                'Ollama' => 'Ollama'
            ];
        }

        $mform->addElement('select', 'ai_agent', get_string('ai_agent', 'local_autogradehelper'), $agents);
        $mform->setType('ai_agent', PARAM_TEXT);

        // Subject / Domain
        $subjects = [
            'general'     => get_string('subject_general', 'local_autogradehelper'),
            'math'        => get_string('subject_math', 'local_autogradehelper'),
            'programming' => get_string('subject_programming', 'local_autogradehelper'),
            'medical'     => get_string('subject_medical', 'local_autogradehelper'),
            'science'     => get_string('subject_science', 'local_autogradehelper'),
            'law'         => get_string('subject_law', 'local_autogradehelper'),
            'creative'    => get_string('subject_creative', 'local_autogradehelper'),
        ];
        $mform->addElement('select', 'complexity', get_string('complexity', 'local_autogradehelper'), $subjects);
        $mform->setType('complexity', PARAM_ALPHA);

        // Hidden fields
        $mform->addElement('hidden', 'assignmentid');
        $mform->setType('assignmentid', PARAM_INT);

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $this->add_action_buttons();
    }
}
