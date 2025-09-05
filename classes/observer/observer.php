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

        // Ejemplo: log a archivo
        file_put_contents(__DIR__ . '/../../logs/course_events.log', $json . PHP_EOL, FILE_APPEND);
    }
}
