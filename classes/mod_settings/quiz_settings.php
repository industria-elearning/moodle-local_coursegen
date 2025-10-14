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

namespace local_coursegen\mod_settings;

/**
 * Class quiz_settings
 *
 * @package    local_coursegen
 * @copyright  2025 Wilber Narvaez <https://datacurso.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_settings extends base_settings {
    /**
     * Add specific settings for book module.
     */
    public function add_settings() {
        foreach ($this->modsettings['questions'] as $question) {
            $this->add_question($question);
        }
    }

    /**
     * Add question to quiz.
     *
     * @param array $aiquestiondata Question data.
     */
    protected function add_question($aiquestiondata) {
        global $DB, $USER;
        $cm = $this->cm;
        $context = \context_module::instance($cm->coursemodule);
        require_capability('moodle/question:add', $context);

        $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);

        $categoryinfo = question_get_default_category($context->id);

        $category = "{$categoryinfo->id},{$categoryinfo->contextid}";
        $aiquestiondata['category'] = $category;

        $questionrecord = new \stdClass();
        $questionrecord->category = $categoryinfo->id;
        $questionrecord->qtype = $aiquestiondata['qtype'];
        $questionrecord->createdby = $USER->id;

        $qtypeobj = \question_bank::get_qtype($aiquestiondata['qtype']);

        $question = $qtypeobj->save_question($questionrecord, (object)$aiquestiondata);

        // Purge this question from the cache.
        \question_bank::notify_question_edited($question->id);
    }
}
