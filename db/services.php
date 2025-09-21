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
 * External functions and service declaration for DataCurso
 *
 * Documentation: {@link https://moodledev.io/docs/apis/subsystems/external/description}
 *
 * @package    local_datacurso
 * @category   webservice
 * @copyright  2025 Industria Elearning <info@industriaelearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_datacurso_get_models_by_tenant' => [
        'classname' => 'local_datacurso\external\get_models_by_tenant',
        'methodname' => 'execute',
        'description' => 'Get models by tenant',
        'type' => 'read',
        'ajax' => true,
    ],
    'local_datacurso_get_courses_by_model' => [
        'classname'   => 'local_datacurso\external\get_courses_by_model',
        'methodname'  => 'execute',
        'classpath'   => 'local/datacurso/externallib.php',
        'description' => 'Get courses by model ID',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => '',
    ],
    'local_datacurso_create_course_context' => [
        'classname' => 'local_datacurso\external\create_course_context',
        'methodname' => 'execute',
        'description' => 'Create course context for ask question to chatbot based in that information',
        'type' => 'write',
        'ajax' => true,
    ],
    'local_datacurso_create_mod' => [
        'classname' => 'local_datacurso\external\create_mod',
        'methodname' => 'execute',
        'description' => 'Create module for ask question to chatbot based in that information',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/course:manageactivities,moodle/course:update',
    ],
    'local_datacurso_create_course' => [
        'classname' => 'local_datacurso\external\create_course',
        'methodname' => 'execute',
        'description' => 'Create course with AI assistance',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/course:create',
    ],
    'local_datacurso_plan_course_message' => [
        'classname' => 'local_datacurso\external\plan_course_message',
        'methodname' => 'execute',
        'description' => 'Send message to AI course planning session',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'moodle/course:update',
    ],
];
