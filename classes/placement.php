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

namespace aiplacement_coursegen;

/**
 * Main class for the Course Creator AI AI placement plugin
 *
 * @package    aiplacement_coursegen
 * @copyright  2025 Wilber Narvaez <https://datacurso.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class placement extends \core_ai\placement {

    /**
     * Get the list of actions that this placement uses.
     *
     * @return array An array of action class names.
     */
    public function get_action_list(): array {
        // TODO list actions that this placement uses, for each action create a respective WebService.
        // For example, for the action: \core_ai\aiactions\generate_text::class
        // the WebService will be aiplacement_coursegen_generate_text .
        return [
        ];
    }
}
