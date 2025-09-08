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
 * TODO describe module chatbot
 *
 * @module     local_datacurso/repository/chatbot
 * @copyright  2025 Buendata <soluciones@buendata.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import ajax from "core/ajax";

/**
 * Create course context for ask question to chatbot based in that information.
 *
 * @param {number} courseId - The ID of the course to create context for
 * @return {Promise<Object>} response
 */
export async function createCourseContext(courseId) {
    return ajax.call([
        {
            methodname: "local_datacurso_create_course_context",
            args: { courseid: courseId },
        },
    ])[0];
}

/**
 * Create module for ask question to chatbot based in that information.
 *
 * @param {number} courseId - The ID of the course to create context for
 * @param {string} message - The message to create
 * @param {number} section - The section number where the module will be created
 * @return {Promise<Object>} response
 */
export async function createMod(courseId, message, section) {
    return ajax.call([
        {
            methodname: "local_datacurso_create_mod",
            args: { courseid: parseInt(courseId), section: parseInt(section), message: message },
        },
    ])[0];
}

