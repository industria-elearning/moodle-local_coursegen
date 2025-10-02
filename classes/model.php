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
 * Model class for DataCurso plugin.
 *
 * @package    local_datacurso
 * @copyright  2025 Buendata <soluciones@buendata.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_datacurso;

/**
 * Model class for handling datacurso models.
 */
class model {

    /** @var int Model ID */
    public $id;

    /** @var string Model name */
    public $name;

    /** @var string Model content */
    public $content;

    /** @var int Deleted flag */
    public $deleted;

    /** @var int Time created */
    public $timecreated;

    /** @var int Time modified */
    public $timemodified;

    /** @var int User who modified */
    public $usermodified;

    /**
     * Constructor.
     *
     * @param int $id Model ID
     */
    public function __construct($id = 0) {
        if ($id > 0) {
            $this->load($id);
        }
    }

    /**
     * Load model from database.
     *
     * @param int $id Model ID
     * @return bool
     */
    public function load($id) {
        global $DB;

        $record = $DB->get_record('local_datacurso_model', ['id' => $id, 'deleted' => 0]);
        if ($record) {
            $this->id = $record->id;
            $this->name = $record->name;
            $this->content = $record->content;
            $this->deleted = $record->deleted;
            $this->timecreated = $record->timecreated;
            $this->timemodified = $record->timemodified;
            $this->usermodified = $record->usermodified;
            return true;
        }
        return false;
    }

    /**
     * Save model to database.
     *
     * @return bool
     * @throws \moodle_exception
     */
    public function save() {
        global $DB, $USER;

        // Validate name uniqueness.
        if (!$this->validate_unique_name()) {
            throw new \moodle_exception('modelnameexists', 'local_datacurso');
        }

        $now = time();

        if (empty($this->id)) {
            // Create new model.
            $record = new \stdClass();
            $record->name = $this->name;
            $record->content = $this->content;
            $record->deleted = 0;
            $record->timecreated = $now;
            $record->timemodified = $now;
            $record->usermodified = $USER->id;

            $this->id = $DB->insert_record('local_datacurso_model', $record);
            $this->timecreated = $now;
            $this->timemodified = $now;
            $this->usermodified = $USER->id;
            $this->deleted = 0;
        } else {
            // Update existing model.
            $record = new \stdClass();
            $record->id = $this->id;
            $record->name = $this->name;
            $record->content = $this->content;
            $record->timemodified = $now;
            $record->usermodified = $USER->id;

            $DB->update_record('local_datacurso_model', $record);
            $this->timemodified = $now;
            $this->usermodified = $USER->id;
        }

        return true;
    }

    /**
     * Validate that the model name is unique.
     *
     * @return bool
     */
    private function validate_unique_name() {
        global $DB;

        if (empty(trim($this->name))) {
            return false;
        }

        $name = trim($this->name);

        if (!empty($this->id)) {
            // Editing existing model - exclude current record.
            $sql = "SELECT id FROM {local_datacurso_model} WHERE name = ? AND deleted = 0 AND id != ?";
            $params = [$name, $this->id];
        } else {
            // Creating new model.
            $sql = "SELECT id FROM {local_datacurso_model} WHERE name = ? AND deleted = 0";
            $params = [$name];
        }

        return !$DB->record_exists_sql($sql, $params);
    }

    /**
     * Delete model (soft delete).
     *
     * @return bool
     */
    public function delete() {
        global $DB;

        if (!empty($this->id)) {
            $DB->set_field('local_datacurso_model', 'deleted', 1, ['id' => $this->id]);
            $this->deleted = 1;
            return true;
        }
        return false;
    }

    /**
     * Get all active models.
     *
     * @return array
     */
    public static function get_all() {
        global $DB;

        return $DB->get_records('local_datacurso_model', ['deleted' => 0], 'timecreated DESC');
    }

    /**
     * Get model by ID.
     *
     * @param int $id Model ID
     * @return model|null
     */
    public static function get_by_id($id) {
        $model = new self();
        if ($model->load($id)) {
            return $model;
        }
        return null;
    }

    /**
     * Get model by course ID.
     *
     * @param int $courseid Course ID
     * @return model|null
     */
    public static function get_by_course($courseid) {
        global $DB;
        return $DB->get_record('local_datacurso_model', ['courseid' => $courseid, 'deleted' => 0]);
    }
}
