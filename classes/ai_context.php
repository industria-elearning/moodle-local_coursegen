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

namespace local_coursegen;

use aiprovider_datacurso\httpclient\ai_course_api;

/**
 * Class ai_context
 *
 * @package    local_coursegen
 * @copyright  2025 Wilber Narvaez <https://datacurso.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_context {
    /** @var string Context type model */
    const CONTEXT_TYPE_MODEL = 'model';
    /** @var string Context type syllabus */
    const CONTEXT_TYPE_SYLLABUS = 'syllabus';
    /** @var string Context type custom prompt */
    const CONTEXT_TYPE_CUSTOM_PROMPT = 'prompt';

    /**
     * Uploads the content of the instructional model to the AI endpoint.
     *
     * @param model $model The instructional model selected.
     */
    public static function upload_model_to_ai(model $model): void {
        global $CFG;

        try {
            $siteid = md5($CFG->wwwroot);

            $postdata = [
                'model_name' => $model->name,
                'model_context' => $model->content,
                'site_id' => $siteid,
            ];

            $client = new ai_course_api();
            $client->request('POST', '/context/upload-model-context', $postdata);
        } catch (\Exception $e) {
            // Show error notification to the user.
            \core\notification::error(get_string('error_upload_failed_model', 'local_coursegen', $e->getMessage()));
        }
    }

    /**
     * Uploads the syllabus file to the AI endpoint.
     *
     * @param int $courseid ID of the course.
     */
    public static function upload_syllabus_to_ai(int $courseid): void {
        global $CFG;

        $fs = get_file_storage();
        $context = \context_course::instance($courseid);

        $files = $fs->get_area_files($context->id, 'local_coursegen', 'syllabus', 0, 'itemid', false);

        if (empty($files)) {
            return;
        }

        $file = reset($files);
        if (!$file) {
            return;
        }

        $siteid = md5($CFG->wwwroot);

        // Save the file temporarily.
        $filepath = $file->copy_content_to_temp();

        $postdata = [
            'title' => $file->get_filename(),
            'site_id' => $siteid,
            'course_id' => $courseid,
        ];

        $client = new ai_course_api();
        $client->upload_file('/context/upload', $filepath, $file->get_mimetype(), $file->get_filename(), $postdata);
    }

    /**
     * Save syllabus PDF file from draft area to course context.
     *
     * @param int $courseid Course ID where the syllabus will be saved
     * @param int|null $draftitemid Draft item ID from the syllabus file picker
     * @return bool True if syllabus was saved successfully, false otherwise
     */
    public static function save_syllabus_from_draft(int $courseid, ?int $draftitemid = null): bool {
        if (!$draftitemid) {
            return false;
        }

        // Syllabus file options - only PDF files allowed.
        $fileoptions = [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['.pdf'],
        ];

        try {
            file_save_draft_area_files(
                $draftitemid,
                \context_course::instance($courseid)->id,
                'local_coursegen',
                self::CONTEXT_TYPE_SYLLABUS,
                0,
                [
                    'subdirs' => 0,
                    'maxfiles' => 1,
                    'accepted_types' => ['.pdf'],
                ]
            );
            return true;
        } catch (\Exception $e) {
            // Log the error or handle it as needed.
            debugging('Error saving syllabus from draft: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Save course context data to database.
     *
     * @param int $courseid Course ID
     * @param string $contexttype Context type (model, syllabus or customprompt)
     * @param int|null $modelid Selected model ID (if context type is model)
     * @param string|null $prompttext Custom prompt text (if context type is custom prompt)
     * @param int|null $promptformat Format of the custom prompt text
     */
    public static function save_course_context(
        int $courseid,
        string $contexttype,
        ?int $modelid = null,
        ?string $prompttext = null,
        ?int $promptformat = null
    ): void {
        global $DB, $USER;

        $now = time();

        $iscustomprompt = ($contexttype === self::CONTEXT_TYPE_CUSTOM_PROMPT);
        $prompttext = $iscustomprompt ? $prompttext : null;
        $promptformat = $iscustomprompt ? ($promptformat ?? FORMAT_HTML) : null;

        // Check if record already exists.
        $existingrecord = $DB->get_record('local_coursegen_course_context', ['courseid' => $courseid]);

        if ($existingrecord) {
            // Update existing record.
            $record = new \stdClass();
            $record->id = $existingrecord->id;
            $record->context_type = $contexttype;
            $record->model_id = ($contexttype === self::CONTEXT_TYPE_MODEL) ? $modelid : null;
            $record->prompt_text = $prompttext;
            $record->prompt_format = $promptformat;
            $record->timemodified = $now;
            $record->usermodified = $USER->id;

            $DB->update_record('local_coursegen_course_context', $record);
        } else {
            // Create new record.
            $record = new \stdClass();
            $record->courseid = $courseid;
            $record->context_type = $contexttype;
            $record->model_id = ($contexttype === self::CONTEXT_TYPE_MODEL) ? $modelid : null;
            $record->prompt_text = $prompttext;
            $record->prompt_format = $promptformat;
            $record->timecreated = $now;
            $record->timemodified = $now;
            $record->usermodified = $USER->id;

            $DB->insert_record('local_coursegen_course_context', $record);
        }
    }

    /**
     * Get AI course context info from database.
     *
     * @param int $courseid Course ID
     * @return mixed Course AI context info
     */
    public static function get_course_context_info($courseid): mixed {
        global $DB;

        $aicontext = $DB->get_record_sql(
            'SELECT cc.context_type, cc.prompt_text, cc.prompt_format, m.name AS model_name
            FROM
                {local_coursegen_course_context} cc
                LEFT JOIN {local_coursegen_model} m ON cc.model_id = m.id
            WHERE
                cc.courseid = ?',
            [$courseid]
        );

        if ($aicontext && !isset($aicontext->name)) {
            $aicontext->name = $aicontext->model_name;
        }

        return $aicontext;
    }

    /**
     * Returns a valid course AI context or null if not properly configured.
     * - For context type 'model': requires a non-empty model name.
     * - For context type 'syllabus': requires at least one syllabus file saved in course context.
     *
     * @param int $courseid Course ID
     * @return \stdClass|null Object with properties context_type and model_name (or null)
     */
    public static function get_valid_course_context(int $courseid): ?\stdClass {
        $aicontext = self::get_course_context_info($courseid);
        if (!$aicontext || empty($aicontext->context_type)) {
            return null;
        }

        if ($aicontext->context_type === self::CONTEXT_TYPE_MODEL) {
            if (empty($aicontext->name)) {
                return null;
            }
            return (object) [
                'context_type' => self::CONTEXT_TYPE_MODEL,
                'model_name' => $aicontext->name,
            ];
        }

        if ($aicontext->context_type === self::CONTEXT_TYPE_SYLLABUS) {
            $fs = get_file_storage();
            $context = \context_course::instance($courseid);
            $files = $fs->get_area_files($context->id, 'local_coursegen', 'syllabus', 0, 'itemid', false);
            if (empty($files)) {
                return null;
            }
            return (object) [
                'context_type' => self::CONTEXT_TYPE_SYLLABUS,
                'model_name' => null,
            ];
        }

        if ($aicontext->context_type === self::CONTEXT_TYPE_CUSTOM_PROMPT) {
            $prompttext = $aicontext->prompt_text ?? '';
            $stripped = trim(strip_tags($prompttext));
            if ($prompttext === null || $stripped === '') {
                return null;
            }
            return (object) [
                'context_type' => self::CONTEXT_TYPE_CUSTOM_PROMPT,
                'prompt_text' => $prompttext,
                'prompt_format' => $aicontext->prompt_format ?? FORMAT_HTML,
            ];
        }

        return null;
    }
}
