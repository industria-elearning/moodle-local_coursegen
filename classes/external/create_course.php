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
 * External API for creating courses with AI assistance.
 *
 * @package    local_datacurso
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_datacurso\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot .'/course/lib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_system;
use moodle_exception;

/**
 * External API for creating courses with AI assistance.
 */
class create_course extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'categoryid' => new external_value(PARAM_INT, 'Category ID where the course will be created'),
            'prompt' => new external_value(PARAM_TEXT, 'AI prompt for course creation'),
            'generateimages' => new external_value(PARAM_INT, 'Generate images flag (0 or 1)', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Create a course with AI assistance.
     *
     * @param int $categoryid Category ID where the course will be created
     * @param string $prompt AI prompt for course creation
     * @param int $generateimages Generate images flag
     * @return array Result of the course creation
     * @throws moodle_exception
     */
    public static function execute(int $categoryid, string $prompt, int $generateimages = 0) {
        global $CFG, $DB, $USER;

        try {
            $params = self::validate_parameters(self::execute_parameters(), [
                'categoryid' => $categoryid,
                'prompt' => $prompt,
                'generateimages' => $generateimages,
            ]);

            // Validate context and permissions.
            $context = context_system::instance();
            self::validate_context($context);
            require_capability('moodle/course:create', $context);

            // Validate category exists and user has permission.
            $category = $DB->get_record('course_categories', ['id' => $params['categoryid']], '*', MUST_EXIST);
            $categorycontext = \context_coursecat::instance($category->id);
            require_capability('moodle/course:create', $categorycontext);

            // Generate unique shortname.
            $shortname = self::generate_unique_shortname();

            // Create the course with basic data first.
            $coursedata = new \stdClass();
            $coursedata->fullname = $shortname;
            $coursedata->shortname = $shortname;
            $coursedata->category = $params['categoryid'];
            $coursedata->summary = '';
            $coursedata->summaryformat = FORMAT_HTML;
            $coursedata->format = 'topics';
            $coursedata->visible = 0;
            $coursedata->startdate = time();
            $coursedata->enddate = 0;
            $coursedata->newsitems = 0;
            $coursedata->numsections = 0;

            // Create the course.
            $course = create_course($coursedata);

            // Prepare data for AI endpoint with course_id.
            $aidata = [
                'prompt' => $params['prompt'],
                'generate_images' => (bool)$params['generateimages'],
                'course_id' => $course->id,
                'category_id' => $params['categoryid'],
            ];

            $apitoken = get_config('local_datacurso', 'apitoken');
            $baseurl = get_config('local_datacurso', 'baseurl');

            // This request may take a long time depending on the complexity of the prompt that the AI ​​has to resolve.
            \core_php_time_limit::raise();
            raise_memory_limit(MEMORY_EXTRA);
            // Release the session so other tabs in the same session are not blocked.
            \core\session\manager::write_close();

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, rtrim($baseurl, '/') . '/resources/create-course');
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json', 'Authorization: Bearer ' . $apitoken]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FAILONERROR, true);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'site_id' => md5($CFG->wwwroot),
                'course_id' => $course->id,
                'message' => $prompt,
                'timezone' => \core_date::get_user_timezone(),
                'generate_images' => $generateimages == 1,
            ]));
            $result = curl_exec($ch);

            if (!$result) {
                $curlerror = curl_error($ch);
                debugging("CURL request failed while creating resource. Error: {$curlerror}");
                curl_close($ch);
                return [
                    'success' => false,
                    'message' => get_string('error_generating_resource', 'local_datacurso'),
                    'log' => "CURL request failed while creating resource. Error: {$curlerror}",
                ];
            }
            curl_close($ch);

            // Process API response.
            $apiresponse = json_decode($result, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'message' => 'Invalid JSON response from API',
                ];
            }

            // Process sections if provided in the response.
            if (!empty($apiresponse['sections_info'])) {
                self::process_course_sections($course->id, $apiresponse['sections_info']);
            }

            // Return success response.
            return [
                'success' => true,
                'courseid' => $course->id,
                'shortname' => $course->shortname,
                'fullname' => $course->fullname,
                'message' => get_string('coursecreated', 'local_datacurso'),
                'courseurl' => course_get_url($course->id)->out(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'courseid' => 0,
                'shortname' => '',
                'fullname' => '',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate a unique course shortname.
     *
     * @return string Unique shortname
     */
    private static function generate_unique_shortname() {
        global $DB;

        $baseprefix = 'AI_COURSE_';

        // Get the highest existing number from AI_COURSE_ prefixed courses.
        $sql = "SELECT shortname FROM {course} WHERE shortname LIKE ? ORDER BY shortname DESC LIMIT 1";
        $lastcourse = $DB->get_field_sql($sql, [$baseprefix . '%']);

        $nextnumber = 1;
        if ($lastcourse) {
            // Extract the number from the shortname (e.g., AI_COURSE_5 -> 5).
            if (preg_match('/AI_COURSE_(\d+)/', $lastcourse, $matches)) {
                $nextnumber = intval($matches[1]) + 1;
            }
        }

        return $baseprefix . $nextnumber;
    }

    /**
     * Process course sections from API response.
     *
     * @param int $courseid Course ID
     * @param array $sectionsinfo Sections information from API
     */
    private static function process_course_sections($courseid, $sectionsinfo) {
        global $DB;

        // Get course format to handle sections properly.
        $course = get_course($courseid);
        $courseformat = course_get_format($course);

        // Get existing sections indexed by section number.
        $sections = $DB->get_records('course_sections', ['course' => $courseid], 'section ASC');
        $existingsections = array_column($sections, null, 'section');

        foreach ($sectionsinfo as $sectioninfo) {
            $sectionnumber = (int)$sectioninfo['section'];
            $sectionname = $sectioninfo['name'] ?? '';

            if (isset($existingsections[$sectionnumber])) {
                // Update existing section name.
                if (!empty($sectionname) && $existingsections[$sectionnumber]->name !== $sectionname) {
                    $DB->update_record('course_sections', [
                        'id' => $existingsections[$sectionnumber]->id,
                        'name' => $sectionname,
                    ]);
                }
            } else {
                // Create new section.
                $sectiondata = new \stdClass();
                $sectiondata->course = $courseid;
                $sectiondata->section = $sectionnumber;
                $sectiondata->name = $sectionname;
                $sectiondata->summary = '';
                $sectiondata->summaryformat = FORMAT_HTML;
                $sectiondata->sequence = '';
                $sectiondata->visible = 1;
                $sectiondata->availability = null;
                $sectiondata->timemodified = time();

                $DB->insert_record('course_sections', $sectiondata);
            }
        }

        // Update course format options if needed.
        $maxsection = max(array_column($sectionsinfo, 'section'));
        if ($maxsection > 0) {
            // Update numsections for formats that support it.
            $formatoptions = $courseformat->get_format_options();
            if (isset($formatoptions['numsections'])) {
                $courseformat->update_course_format_options(['numsections' => $maxsection]);
            }
        }

        // Rebuild course cache.
        rebuild_course_cache($courseid, true);
    }

    /**
     * Returns description of method return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'courseid' => new external_value(PARAM_INT, 'Created course ID'),
            'shortname' => new external_value(PARAM_TEXT, 'Course shortname'),
            'fullname' => new external_value(PARAM_TEXT, 'Course fullname'),
            'message' => new external_value(PARAM_TEXT, 'Status message'),
            'courseurl' => new external_value(PARAM_URL, 'Course URL', VALUE_OPTIONAL),
        ]);
    }
}
