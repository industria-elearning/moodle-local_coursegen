<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Hook callbacks for Tenant Navigator
 *
 * @package    local_datacurso
 * @copyright  2025 Industria Elearning <info@industriaelearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => core_course\hook\after_form_definition::class,
        'callback' => 'local_datacurso\hook\course_form_hook::after_form_definition',
        'priority' => 100,
    ],
    [
        'hook' => core_course\hook\after_form_definition_after_data::class,
        'callback' => 'local_datacurso\hook\course_form_hook::after_form_definition_after_data',
        'priority' => 100,
    ],
    [
        'hook' => core_course\hook\after_form_submission::class,
        'callback' => 'local_datacurso\hook\course_form_hook::after_form_submission',
        'priority' => 100,
    ],
    [
        'hook' => core\hook\output\before_footer_html_generation::class,
        'callback' => 'local_datacurso\hook\chat_hook::before_footer_html_generation',
        'priority' => 100,
    ],
    [
        'hook' => core\hook\output\before_standard_head_html_generation::class,
        'callback' => 'local_datacurso\hook\chat_hook::before_standard_head_html_generation',
        'priority' => 100,
    ],
    [
        'hook' => core_course\hook\before_activitychooserbutton_exported::class,
        'callback' => 'local_datacurso\hook\hook_callbacks::extend_activitychooser_button',
    ],
];
