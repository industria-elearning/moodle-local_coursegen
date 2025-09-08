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

defined('MOODLE_INTERNAL') || die();

class local_datacurso_observer {

    public static function course_created(\core\event\course_created $event) {
        $courseid = $event->objectid;
        $course = get_course($courseid);

        $data = [
            'event' => 'course_created',
            'id' => $course->id,
            'fullname' => $course->fullname,
            'shortname' => $course->shortname,
            'category' => $course->category,
            'startdate' => $course->startdate,
            'enddate' => $course->enddate,
        ];

        self::send_json($data);
    }

    public static function course_updated(\core\event\course_updated $event) {
        $courseid = $event->objectid;
        $course = get_course($courseid);

        $data = [
            'event' => 'course_updated',
            'id' => $course->id,
            'fullname' => $course->fullname,
            'shortname' => $course->shortname,
            'category' => $course->category,
            'startdate' => $course->startdate,
            'enddate' => $course->enddate,
        ];

        self::send_json($data);
    }

    private static function send_json($data) {
        $json = json_encode($data);

        // Ensure the logs directory exists before writing.
        $logdir = __DIR__ . '/../../logs';
        if (!is_dir($logdir)) {
            if (!mkdir($logdir, 0777, true) && !is_dir($logdir)) {
                // Could not create directory, handle error (optionally log via Moodle).
                error_log("Failed to create log directory: $logdir");
                return;
            }
        }
        $logfile = $logdir . '/course_events.log';
        if (file_put_contents($logfile, $json . PHP_EOL, FILE_APPEND) === false) {
            // Handle file write error (optionally log via Moodle).
            error_log("Failed to write to log file: $logfile");
        }
    }
}
