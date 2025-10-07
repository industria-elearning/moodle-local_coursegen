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
 * @package     local_datacurso
 * @category    admin
 * @copyright   Josue <josue@datacurso.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $pluginname = 'local_datacurso';
    $admincategory = new admin_category($pluginname, get_string('pluginname', $pluginname));

    $ADMIN->add('localplugins', $admincategory);
    $settings = new admin_settingpage("{$pluginname}_settings", get_string('generalsettings', $pluginname));

    // Add tenantid setting.
    $settings->add(
        new admin_setting_configtext(
            "{$pluginname}/tenantid",
            get_string('tenantid', $pluginname),
            get_string('tenantid_desc', $pluginname),
            '',
        )
    );

    // Add tenant token setting.
    $settings->add(
        new admin_setting_configpasswordunmask(
            'local_datacurso/tenanttoken',
            new lang_string('tenanttoken', 'local_datacurso'),
            new lang_string('tenanttoken_desc', 'local_datacurso'),
            '',
        )
    );

    $ADMIN->add($pluginname, $settings);
    // Add manage models page.
    $ADMIN->add($pluginname, new admin_externalpage(
        'local_datacurso_manage_models',
        get_string('managemodels', 'local_datacurso'),
        new moodle_url('/local/datacurso/manage_models.php')
    ));
}
