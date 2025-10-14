<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin upgrade steps are defined here.
 *
 * @package     aiplacement_coursegen
 * @category    upgrade
 * @copyright   2025 Josue Condori <https://datacurso.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute aiplacement_coursegen upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_aiplacement_coursegen_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();
    // Add aiplacement_coursegen_model table.
    if ($oldversion < 2025091701) {
        // Define table aiplacement_coursegen_model to be created.
        $table = new xmldb_table('aiplacement_coursegen_model');

        // Adding fields to table aiplacement_coursegen_model.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('content', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table aiplacement_coursegen_model.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for aiplacement_coursegen_model.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Datacurso savepoint reached.
        upgrade_plugin_savepoint(true, 2025091701, 'local', 'datacurso');
    }

    // Add aiplacement_coursegen_course_context table.
    if ($oldversion < 2025091704) {
        // Define table aiplacement_coursegen_course_context to be created.
        $table = new xmldb_table('aiplacement_coursegen_course_context');

        // Adding fields to table aiplacement_coursegen_course_context.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('context_type', XMLDB_TYPE_CHAR, '20', null, null, null, null);
        $table->add_field('model_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table aiplacement_coursegen_course_context.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('model_id', XMLDB_KEY_FOREIGN, ['model_id'], 'aiplacement_coursegen_model', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);

        // Conditionally launch create table for aiplacement_coursegen_course_context.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Datacurso savepoint reached.
        upgrade_plugin_savepoint(true, 2025091704, 'local', 'datacurso');
    }

    if ($oldversion < 2025092001) {
        // Define table aiplacement_coursegen_course_sessions to be created.
        $table = new xmldb_table('aiplacement_coursegen_course_sessions');

        // Adding fields to table aiplacement_coursegen_course_sessions.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('session_id', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table aiplacement_coursegen_course_sessions.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Conditionally launch create table for aiplacement_coursegen_course_sessions.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Datacurso savepoint reached.
        upgrade_plugin_savepoint(true, 2025092001, 'local', 'datacurso');
    }

    // Add aiplacement_coursegen_module_jobs table for AI module generation tracking.
    if ($oldversion < 2025092401) {
        // Define table aiplacement_coursegen_module_jobs to be created.
        $table = new xmldb_table('aiplacement_coursegen_module_jobs');

        // Adding fields to table aiplacement_coursegen_module_jobs.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('job_id', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('generate_images', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('context_type', XMLDB_TYPE_CHAR, '20', null, null, null, null);
        $table->add_field('model_name', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('sectionnum', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('beforemod', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table aiplacement_coursegen_module_jobs.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Conditionally launch create table for aiplacement_coursegen_module_jobs.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Datacurso savepoint reached.
        upgrade_plugin_savepoint(true, 2025092401, 'local', 'datacurso');
    }

    return true;
}
