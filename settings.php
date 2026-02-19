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

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_smartgradeai', get_string('pluginname', 'local_smartgradeai'));

    $settings->add(new admin_setting_configtext(
        'local_smartgradeai/webhookurl',
        get_string('n8n_url', 'local_smartgradeai'),
        get_string('n8n_url_desc', 'local_smartgradeai'),
        '',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_smartgradeai/token',
        get_string('n8n_token', 'local_smartgradeai'),
        get_string('n8n_token_desc', 'local_smartgradeai'),
        ''
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_smartgradeai/enable_review_mode',
        get_string('enable_review_mode', 'local_smartgradeai'),
        get_string('enable_review_mode_desc', 'local_smartgradeai'),
        0
    ));

    $defaultmodels = "Gemini\nClaude\nOpenAI\nDeepseek\nOllama";
    $settings->add(new admin_setting_configtextarea(
        'local_smartgradeai/availablemodels',
        get_string('availablemodels', 'local_smartgradeai'),
        get_string('availablemodels_desc', 'local_smartgradeai'),
        $defaultmodels,
        PARAM_TEXT
    ));

    $ADMIN->add('localplugins', $settings);
}
