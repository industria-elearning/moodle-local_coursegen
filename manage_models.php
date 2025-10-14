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
 * @package    aiplacement_coursegen
 * @copyright  2025 Wilber Narvaez <https://datacurso.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('aiplacement_coursegen_manage_models');

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

$context = context_system::instance();
require_capability('local/coursegen:managemodels', $context);

$PAGE->set_url('/ai/placement/coursegen/manage_models.php');
$PAGE->set_title(get_string('managemodels', 'aiplacement_coursegen'));
$PAGE->set_heading(get_string('managemodels', 'aiplacement_coursegen'));

// Handle delete action.
if ($action === 'delete' && $id > 0) {
    if ($confirm && confirm_sesskey()) {
        // Soft delete the model.
        $DB->set_field('aiplacement_coursegen_model', 'deleted', 1, ['id' => $id]);
        redirect($PAGE->url, get_string('modeldeleted', 'aiplacement_coursegen'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        // Show confirmation dialog.
        $model = $DB->get_record('aiplacement_coursegen_model', ['id' => $id, 'deleted' => 0], '*', MUST_EXIST);

        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('deletemodel', 'aiplacement_coursegen'));

        $confirmurl = new moodle_url($PAGE->url, ['action' => 'delete', 'id' => $id, 'confirm' => 1, 'sesskey' => sesskey()]);
        $cancelurl = $PAGE->url;

        echo $OUTPUT->confirm(
            get_string('confirmdelete', 'aiplacement_coursegen') . '<br><strong>' . format_string($model->name) . '</strong>',
            $confirmurl,
            $cancelurl
        );

        echo $OUTPUT->footer();
        exit;
    }
}

echo $OUTPUT->header();

// Add model button.
$addurl = new moodle_url('/ai/placement/coursegen/edit_model.php');
echo html_writer::div(
    $OUTPUT->single_button($addurl, get_string('addmodel', 'aiplacement_coursegen'), 'get'),
    'mb-3'
);

// Create table to display models.
$table = new html_table();
$table->head = [
    get_string('modelname', 'aiplacement_coursegen'),
    get_string('modelcreated', 'aiplacement_coursegen'),
    get_string('modelmodified', 'aiplacement_coursegen'),
    get_string('actions', 'aiplacement_coursegen'),
];
$table->attributes['class'] = 'table table-striped';

// Get models from database.
$models = $DB->get_records('aiplacement_coursegen_model', ['deleted' => 0], 'timecreated DESC');

if (empty($models)) {
    echo html_writer::div(
        html_writer::tag('p', get_string('nomodels', 'aiplacement_coursegen'), ['class' => 'alert alert-info']),
        'mt-3'
    );
} else {
    foreach ($models as $model) {
        $editurl = new moodle_url('/ai/placement/coursegen/edit_model.php', ['id' => $model->id]);
        $deleteurl = new moodle_url($PAGE->url, ['action' => 'delete', 'id' => $model->id]);

        $editicon = $OUTPUT->pix_icon('t/edit', get_string('edit', 'aiplacement_coursegen'));
        $deleteicon = $OUTPUT->pix_icon('t/delete', get_string('delete', 'aiplacement_coursegen'));

        $actions = html_writer::link($editurl, $editicon, ['title' => get_string('edit', 'aiplacement_coursegen')]);
        $actions .= html_writer::link($deleteurl, $deleteicon, ['title' => get_string('delete', 'aiplacement_coursegen')]);

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
