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

namespace local_datacurso;

/**
 * Helper class to manage AI module generation jobs (streaming) stored in local_datacurso_module_jobs.
 *
 * @package    local_datacurso
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class module_jobs {
    /**
     * Save or update a module job record by job_id.
     *
     * @param int $courseid
     * @param string $jobid
     * @param array $data Additional fields: userid,status,generate_images,context_type,model_name,sectionnum,beforemod
     * @return bool
     */
    public static function save_job(int $courseid, string $jobid, array $data = []): bool {
        global $DB, $USER;

        $now = time();
        $record = $DB->get_record('local_datacurso_module_jobs', ['job_id' => $jobid]);

        $entry = (object) [
            'courseid' => $courseid,
            'userid' => $data['userid'] ?? $USER->id,
            'job_id' => $jobid,
            'status' => $data['status'] ?? null,
            'generate_images' => $data['generate_images'],
            'context_type' => $data['context_type'] ?? null,
            'model_name' => $data['model_name'] ?? null,
            'sectionnum' => $data['sectionnum'] ?? null,
            'beforemod' => $data['beforemod'] ?? null,
            'timemodified' => $now,
        ];

        if ($record) {
            $entry->id = $record->id;
            return $DB->update_record('local_datacurso_module_jobs', $entry);
        } else {
            $entry->timecreated = $now;
            return (bool)$DB->insert_record('local_datacurso_module_jobs', $entry);
        }
    }

    /**
     * Update job status by job_id.
     *
     * @param string $jobid
     * @param string $status
     * @return bool
     */
    public static function update_status(string $jobid, string $status): bool {
        global $DB;
        $record = $DB->get_record('local_datacurso_module_jobs', ['job_id' => $jobid]);
        if (!$record) {
            return false;
        }
        $record->status = $status;
        $record->timemodified = time();
        return $DB->update_record('local_datacurso_module_jobs', $record);
    }

    /**
     * Get job by job_id.
     *
     * @param string $jobid
     * @return object|false
     */
    public static function get_by_jobid(string $jobid) {
        global $DB;
        return $DB->get_record('local_datacurso_module_jobs', ['job_id' => $jobid]);
    }

    /**
     * Get the latest job for a course/user.
     *
     * @param int $courseid
     * @param int|null $userid
     * @return object|false
     */
    public static function get_latest_for_course(int $courseid, ?int $userid = null) {
        global $DB, $USER;
        $userid = $userid ?? $USER->id;
        $sql = "SELECT * FROM {local_datacurso_module_jobs} ";
        $sql .= "WHERE courseid = :courseid AND userid = :userid ";
        $sql .= "ORDER BY timecreated DESC";
        return $DB->get_record_sql($sql, ['courseid' => $courseid, 'userid' => $userid]);
    }
}
