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

use local_datacurso\ai_context;

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
            'formdata' => new external_value(PARAM_RAW, 'Course form data'),
            'prompt' => new external_value(PARAM_TEXT, 'AI prompt for course creation'),
            'generateimages' => new external_value(PARAM_INT, 'Generate images flag (0 or 1)', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Create a course with AI assistance.
     *
     * @param string $formdata Course form data
     * @param string $prompt AI prompt for course creation
     * @param int $generateimages Generate images flag
     * @return array Result of the course creation
     * @throws moodle_exception
     */
    public static function execute($formdata, $prompt, $generateimages = 0) {
        global $CFG, $DB;

        try {
            $params = self::validate_parameters(self::execute_parameters(), [
                'formdata' => $formdata,
                'prompt' => $prompt,
                'generateimages' => $generateimages,
            ]);

            $formdata = json_decode($params['formdata'], true);

            // Course fields.
            $categoryid = clean_param($formdata['category'], PARAM_INT);
            $fullname = clean_param($formdata['fullname'], PARAM_TEXT);
            $shortname = clean_param($formdata['shortname'], PARAM_TEXT);

            // Datacurso fields.
            $contexttype = clean_param($formdata['local_datacurso_context_type'], PARAM_TEXT);
            $draftitemid = clean_param($formdata['local_datacurso_syllabus_pdf'], PARAM_INT);
            $modelid = $contexttype === 'model' ? clean_param($formdata['local_datacurso_select_model'], PARAM_INT) : null;

            // Validate context and permissions.
            $context = context_system::instance();
            self::validate_context($context);
            require_capability('moodle/course:create', $context);

            // Validate category exists and user has permission.
            $category = $DB->get_record('course_categories', ['id' => $categoryid], '*', MUST_EXIST);
            $categorycontext = \context_coursecat::instance($category->id);
            require_capability('moodle/course:create', $categorycontext);

            // Create the course with basic data first.
            $coursedata = new \stdClass();
            $coursedata->fullname = $fullname;
            $coursedata->shortname = $shortname;
            $coursedata->category = $categoryid;
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

            if ($contexttype === 'syllabus') {

                // Save syllabus PDF from draft area.
                $success = ai_context::save_syllabus_from_draft($course->id, $draftitemid);

                if ($success) {
                    ai_context::upload_syllabus_to_ai($course->id);
                }
            }

            // Store the context type and selected option in the database.
            ai_context::save_course_context($course->id, $contexttype, $modelid);

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
                'course_name' => $course->fullname,
                'context_type' => $contexttype,
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

            // Process generated activities if provided in the response.
            if (!empty($apiresponse['generated_activities'])) {
                self::process_generated_activities($course->id, $apiresponse['generated_activities']);
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
     * Process generated activities from API response.
     *
     * @param int $courseid Course ID
     * @param array $activities Generated activities from API
     */
    private static function process_generated_activities($courseid, $activities) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/course/modlib.php');

        $course = get_course($courseid);

        foreach ($activities as $activity) {
            $modname = $activity['resource_type'];
            $parameters = $activity['parameters'];

            try {
                // Validate module exists.
                $modmoodleform = "$CFG->dirroot/mod/$modname/mod_form.php";
                if (!file_exists($modmoodleform)) {
                    debugging("Form file not found for module: {$modname}");
                    continue;
                }
                require_once($modmoodleform);

                $sectionnum = $parameters['section'] ?? 0;

                // Prepare module data.
                list($module, $context, $cw, $cm, $data) = prepare_new_moduleinfo_data($course, $modname, $sectionnum);

                $mformclassname = 'mod_' . $modname . '_mod_form';
                $mform = new $mformclassname($data, $cw->section, $cm, $course);

                // Convert parameters to object and add required fields.
                $moduledata = (object)$parameters;
                $moduledata->section = $sectionnum;
                $moduledata->module = $module->id;

                // Process parameters through parameter class if exists.
                $paramclass = '\\local_datacurso\\mod_parameters\\' . $modname . '_parameters';
                if (class_exists($paramclass) && is_subclass_of($paramclass, \local_datacurso\mod_parameters\base_parameters::class)) {
                    /** @var \local_datacurso\mod_parameters\base_parameters $paraminstance */
                    $paraminstance = new $paramclass($moduledata);
                    $moduledata = $paraminstance->get_parameters();
                }

                // Create the module.
                $newcm = add_moduleinfo($moduledata, $course, $mform);

                // Process module settings if provided.
                if (!empty($moduledata->mod_settings)) {
                    $settingsclass = '\\local_datacurso\\mod_settings\\' . $modname . '_settings';
                    if (class_exists($settingsclass) && is_subclass_of($settingsclass, \local_datacurso\mod_settings\base_settings::class)) {
                        /** @var \local_datacurso\mod_settings\base_settings $settingsinstance */
                        $settingsinstance = new $settingsclass($newcm, $moduledata->mod_settings);
                        $settingsinstance->add_settings();
                    }
                }

            } catch (\Exception $e) {
                debugging("Error creating module {$modname}: " . $e->getMessage());
                continue; // Continue with next activity
            }
        }

        // Rebuild course cache after adding all activities.
        rebuild_course_cache($courseid, true);
    }
}
