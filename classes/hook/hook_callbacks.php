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

namespace local_datacurso\hook;

use core_course\hook\before_activitychooserbutton_exported;
use moodle_url;
use action_link;
use context_course;
use pix_icon;
use section_info;


/**
 * Class hook_callbacks
 *
 * @package    local_datacurso
 * @copyright  2025 Buendata <soluciones@buendata.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Añade una acción "Add activity with IA" al botón del activity chooser.
     */
    public static function extend_activitychooser_button(before_activitychooserbutton_exported $hook): void {
        global $PAGE, $OUTPUT;

        /** @var section_info $section */
        $section = $hook->get_section();

        $courseid = $section->course;
        $context = context_course::instance($courseid);

        if (!has_capability('moodle/course:manageactivities', $context)) {
            return;
        }

        $attributes = [
            'class' => 'dropdown-item local_datacurso-add-activity-ai-link',
            'data-sectionnum' => $section->sectionnum,
            'data-sectionid' => $section->id,
            'data-courseid' => $courseid,
            'data-action' => 'local_datacurso/add_activity_ai',
        ];
        if ($hook->get_cm()) {
            $attributes['data-beforemod'] = $hook->get_cm()->id;
        }

        $hook->get_activitychooserbutton()->add_action_link(new action_link(
            new moodle_url('#'),
            $OUTPUT->render_from_template('local_datacurso/add_activity_ai_label', []),
            null,
            $attributes,
        ));

        $PAGE->requires->js_call_amd('local_datacurso/add_activity_ai', 'init');
    }
}
