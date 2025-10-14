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

namespace local_coursegen\mod_parameters;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

/**
 * Class h5pactivity_parameters
 *
 * @package    local_coursegen
 * @copyright  2025 Wilber Narvaez <https://datacurso.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class h5pactivity_parameters extends base_parameters {
    /**
     * Returns the adjusted parameters for the module h5pactivity.
     *
     * @return object Adjusted parameters for the module h5pactivity.
     */
    public function get_parameters() {
        global $USER, $CFG;
        $userid = $USER->id;
        $draftid = file_get_unused_draft_itemid();

        $filepath = $CFG->dirroot . '/local/coursegen/classes/mod_parameters/h5pactivity/h5pactivity-test.h5p';
        $filename = basename($filepath);

        // Store image in moodledata.
        $fs = get_file_storage();
        $context = \context_user::instance($userid);
        $fileinfo = [
            'contextid' => $context->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftid,
            'filepath' => '/',
            'filename' => $filename,
        ];
        $file = $fs->create_file_from_pathname($fileinfo, $filepath);
        $this->parameters->packagefile = $file->get_itemid();
        return $this->parameters;
    }
}
