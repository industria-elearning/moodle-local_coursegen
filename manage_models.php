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
 * Manage models page for DataCurso plugin.
 *
 * @package    local_datacurso
 * @copyright  2025 Wilber Narvaez <https://datacurso.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_datacurso_manage_models');

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

$context = context_system::instance();
require_capability('local/datacurso:managemodels', $context);

$PAGE->set_url('/local/datacurso/manage_models.php');
$PAGE->set_title(get_string('managemodels', 'local_datacurso'));
$PAGE->set_heading(get_string('managemodels', 'local_datacurso'));

// Handle delete action.
if ($action === 'delete' && $id > 0) {
    if ($confirm && confirm_sesskey()) {
        // Soft delete the model.
        $DB->set_field('local_datacurso_model', 'deleted', 1, ['id' => $id]);
        redirect($PAGE->url, get_string('modeldeleted', 'local_datacurso'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        // Show confirmation dialog.
        $model = $DB->get_record('local_datacurso_model', ['id' => $id, 'deleted' => 0], '*', MUST_EXIST);

        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('deletemodel', 'local_datacurso'));

        $confirmurl = new moodle_url($PAGE->url, ['action' => 'delete', 'id' => $id, 'confirm' => 1, 'sesskey' => sesskey()]);
        $cancelurl = $PAGE->url;

        echo $OUTPUT->confirm(
            get_string('confirmdelete', 'local_datacurso') . '<br><strong>' . format_string($model->name) . '</strong>',
            $confirmurl,
            $cancelurl
        );

        echo $OUTPUT->footer();
        exit;
    }
}

echo $OUTPUT->header();

// Add model button.
$addurl = new moodle_url('/local/datacurso/edit_model.php');
echo html_writer::div(
    $OUTPUT->single_button($addurl, get_string('addmodel', 'local_datacurso'), 'get'),
    'mb-3'
);

// Create table to display models.
$table = new html_table();
$table->head = [
    get_string('modelname', 'local_datacurso'),
    get_string('modelcreated', 'local_datacurso'),
    get_string('modelmodified', 'local_datacurso'),
    get_string('actions', 'local_datacurso'),
];
$table->attributes['class'] = 'table table-striped';

// Get models from database.
$models = $DB->get_records('local_datacurso_model', ['deleted' => 0], 'timecreated DESC');

if (empty($models)) {
    echo html_writer::div(
        html_writer::tag('p', get_string('nomodels', 'local_datacurso'), ['class' => 'alert alert-info']),
        'mt-3'
    );
} else {
    foreach ($models as $model) {
        $editurl = new moodle_url('/local/datacurso/edit_model.php', ['id' => $model->id]);
        $deleteurl = new moodle_url($PAGE->url, ['action' => 'delete', 'id' => $model->id]);

        $editicon = $OUTPUT->pix_icon('t/edit', get_string('edit', 'local_datacurso'));
        $deleteicon = $OUTPUT->pix_icon('t/delete', get_string('delete', 'local_datacurso'));

        $actions = html_writer::link($editurl, $editicon, ['title' => get_string('edit', 'local_datacurso')]);
        $actions .= html_writer::link($deleteurl, $deleteicon, ['title' => get_string('delete', 'local_datacurso')]);

        $table->data[] = [
            format_string($model->name),
            userdate($model->timecreated),
            userdate($model->timemodified),
            $actions,
        ];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
