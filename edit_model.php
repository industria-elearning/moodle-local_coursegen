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
 * Edit model page for DataCurso plugin.
 *
 * @package    local_datacurso
 * @copyright  2025 Wilber Narvaez <soluciones@buendata.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_datacurso\form\model_form;
use local_datacurso\model;
use local_datacurso\ai_context;


admin_externalpage_setup('local_datacurso_manage_models');

$id = optional_param('id', 0, PARAM_INT);

$context = context_system::instance();
require_capability('local/datacurso:managemodels', $context);

$PAGE->set_url('/local/datacurso/edit_model.php', ['id' => $id]);

$modelobj = null;
if ($id > 0) {
    $modelobj = model::get_by_id($id);
    if (!$modelobj) {
        throw new moodle_exception('invalidmodel', 'local_datacurso');
    }
    $PAGE->set_title(get_string('editmodel', 'local_datacurso'));
    $PAGE->set_heading(get_string('editmodel', 'local_datacurso'));
} else {
    $modelobj = new model();
    $PAGE->set_title(get_string('addmodel', 'local_datacurso'));
    $PAGE->set_heading(get_string('addmodel', 'local_datacurso'));
}

$form = new model_form();

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
    redirect(new moodle_url('/local/datacurso/manage_models.php'));
} else if ($data = $form->get_data()) {

    // Update model object with form data.
    $modelobj->name = trim($data->name);
    $modelobj->content = $data->content_editor['text'];

    // Save the model.
    $modelobj->save();

    ai_context::upload_model_to_ai($modelobj);
    redirect(new moodle_url('/local/datacurso/manage_models.php'),
            get_string('modelsaved', 'local_datacurso'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

$form->display();

echo $OUTPUT->footer();
