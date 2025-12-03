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
 * Edit system instruction page for DataCurso plugin.
 *
 * @package    local_coursegen
 * @copyright  2025 Wilber Narvaez <https://datacurso.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_coursegen\form\system_instruction_form;
use local_coursegen\system_instruction;
use local_coursegen\ai_context;


admin_externalpage_setup('local_coursegen_manage_system_instructions');

$id = optional_param('id', 0, PARAM_INT);


$context = context_system::instance();
require_capability('local/coursegen:managesysteminstructions', $context);

$PAGE->set_url('/local/coursegen/edit_system_instruction.php', ['id' => $id]);

$modelobj = null;
if ($id > 0) {
    $modelobj = system_instruction::get_by_id($id);
    if (!$modelobj) {
        throw new moodle_exception('invalidsysteminstruction', 'local_coursegen');
    }
    $PAGE->set_title(get_string('editsysteminstruction', 'local_coursegen'));
    $PAGE->set_heading(get_string('editsysteminstruction', 'local_coursegen'));
} else {
    $modelobj = new system_instruction();
    $PAGE->set_title(get_string('addsysteminstruction', 'local_coursegen'));
    $PAGE->set_heading(get_string('addsysteminstruction', 'local_coursegen'));
}

$form = new system_instruction_form();

// Set form data if editing.
if ($modelobj && $modelobj->id > 0) {
    $formdata = new stdClass();
    $formdata->id = $modelobj->id;
    $formdata->name = $modelobj->name;

    // Prepare content for editor.
    $formdata->content_editor = [
        'text' => $modelobj->content,
        'format' => FORMAT_HTML,
    ];

    $form->set_data($formdata);
}

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/coursegen/manage_system_instructions.php'));
} else if ($data = $form->get_data()) {
    // Update model object with form data.
    $modelobj->name = trim($data->name);
    $modelobj->content = $data->content_editor['text'];

    // Save the system instruction.
    $modelobj->save();

    ai_context::upload_model_to_ai($modelobj);
    redirect(
        new moodle_url('/local/coursegen/manage_system_instructions.php'),
        get_string('systeminstructionsaved', 'local_coursegen'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();

$form->display();

echo $OUTPUT->footer();
