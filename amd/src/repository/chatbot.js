/* eslint-disable */
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
 * @copyright  2025 Wilber Narvaez <soluciones@buendata.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import ajax from "core/ajax";

/**
 * Send a planning message for the course AI conversation.
 * The backend should also return a streaming URL to reconnect to the stream.
 *
 * @param {{
 *   courseid: number,
 *   text: string,
 * }} payload - The payload to send the planning message
 * @return {Promise<Object>} response
 */
export async function planCourseMessage({courseid, text}) {
    const args = {
        courseid: Number(courseid) || 0,
        text,
    };
    return ajax.call([
        {
            methodname: "local_datacurso_plan_course_message",
            args,
        },
    ])[0];
}

/**
 * Create module for ask question to chatbot based in that information.
 *
 * @param {{
 *     courseid: number,
 *     sectionnum: number,
 *     beforemod: number,
 *     jobid: number,
 * }} payload - The payload to create module
 * - courseid: The ID of the course to create module for
 * - sectionnum: The number of the section to create module for
 * - beforemod: The ID of the module before which the new module will be created
 * @return {Promise<Object>} response
 */
export async function createMod({courseid, sectionnum, beforemod, jobid}) {
    const args = {
        courseid: Number(courseid),
        sectionnum: Number(sectionnum),
        beforemod: beforemod ? Number(beforemod) : null,
        jobid: jobid
    };
    return ajax.call([
        {
            methodname: "local_datacurso_create_mod",
            args,
        },
    ])[0];
}

/**
 * Create module with streaming support for real-time updates.
 *
 * @param {{
 *     courseid: number,
 *     sectionnum: number,
 *     beforemod: number,
 *     prompt: string,
 *     generateimages: number,
 * }} payload - The payload to create module with streaming
 * - courseid: The ID of the course to create module for
 * - sectionnum: The number of the section to create module for
 * - beforemod: The ID of the module before which the new module will be created
 * - prompt: The message to create
 * - generateimages: 0 not generate images, 1 generate images
 * @return {Promise<Object>} response
 */
export async function createModStream({courseid, sectionnum, beforemod, prompt, generateimages}) {
    const args = {
        courseid: Number(courseid),
        sectionnum: Number(sectionnum),
        beforemod: beforemod ? Number(beforemod) : null,
        prompt,
        generateimages: generateimages ? Number(generateimages) : 0,
    };
    return ajax.call([
        {
            methodname: "local_datacurso_create_mod_stream",
            args,
        },
    ])[0];
}

/**
 * Apply AI-generated content to an existing course.
 *
 * @param {{
 *     courseid: number,
 * }} payload - The payload to apply course content
 * - courseid: The course ID to apply content to
 * @return {Promise<Object>} response
 */
export async function createCourse({courseid}) {
    const args = {
        courseid: Number(courseid),
    };
    return ajax.call([
        {
            methodname: "local_datacurso_create_course",
            args,
        },
    ])[0];
}

/**
 * Execute AI course planning session.
 *
 * @param {number} courseid - The course ID to execute
 * @return {Promise<Object>} response
 */
export async function planCourseExecute(courseid) {
    return ajax.call([
        {
            methodname: "local_datacurso_plan_course_execute",
            args: {courseid: Number(courseid)},
        },
    ])[0];
}

