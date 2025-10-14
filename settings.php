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
 * Plugin administration pages are defined here.
 *
 * @package     aiplacement_coursegen
 * @category    admin
 * @copyright   2025 Josue Condori <https://datacurso.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Create the main category "AI Placement Plugins" under the "Plugins" section.
    $maincat = 'aiplacement_plugins_cat';
    $ADMIN->add('modules', new admin_category(
        $maincat,
        get_string('aiplacementplugins', 'aiplacement_coursegen')
    ));

    // Create the subcategory "Course Creator AI" inside the main category.
    $subcategory = 'aiplacement_coursecreator_cat';
    $ADMIN->add($maincat, new admin_category(
        $subcategory,
        get_string('coursecreatorai', 'aiplacement_coursegen')
    ));

    // Add the external admin page "Manage models" inside the "Course Creator AI" subcategory.
    $ADMIN->add($subcategory, new admin_externalpage(
        'aiplacement_coursegen_manage_models',
        get_string('managemodels', 'aiplacement_coursegen'),
        new moodle_url('/ai/placement/coursegen/manage_models.php')
    ));
}
