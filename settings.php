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

    // Add baseurl setting.
    $settings->add(
        new admin_setting_configtext(
            'local_datacurso/baseurl',
            new lang_string('baseurl', 'local_datacurso'),
            new lang_string('baseurl_desc', 'local_datacurso'),
            '',
        )
    );

    // Add apitoken setting.
    $settings->add(
        new admin_setting_configpasswordunmask(
            'local_datacurso/apitoken',
            new lang_string('apitoken', 'local_datacurso'),
            new lang_string('apitoken_desc', 'local_datacurso'),
            '',
        )
    );

    // Add Tutor-IA API URL setting.
    $settings->add(
        new admin_setting_configtext(
            'local_datacurso/tutoraiapiurl',
            new lang_string('tutoraiapiurl', 'local_datacurso'),
            new lang_string('tutoraiapiurl_desc', 'local_datacurso'),
            'https://plugins-ai-dev.datacurso.com',
        )
    );

    // Add Tutor-IA token setting.
    $settings->add(
        new admin_setting_configpasswordunmask(
            'local_datacurso/tutoraitoken',
            new lang_string('tutoraitoken', 'local_datacurso'),
            new lang_string('tutoraitoken_desc', 'local_datacurso'),
            '',
        )
    );

    // Add setting to enable/disable chat globally.
    $settings->add(
        new admin_setting_configcheckbox(
            'local_datacurso/enablechat',
            new lang_string('enablechat', 'local_datacurso'),
            new lang_string('enablechat_desc', 'local_datacurso'),
            1
        )
    );

    // Selector de avatar para Tutor-IA.
    $avatars = [];
    $avatar_dir = $CFG->dirroot . '/local/datacurso/pix/avatars/';

    // Lista de avatares disponibles.
    $available_avatars = ['01', '03', '04', '05', '06', '07', '08', '09', '10'];

    foreach ($available_avatars as $num) {
        $avatar_file = $avatar_dir . 'avatar_profesor_' . $num . '.png';
        if (file_exists($avatar_file)) {
            $avatars[$num] = get_string('avatar', 'local_datacurso') . ' ' . ltrim($num, '0');
        }
    }

    // Si no hay avatares disponibles, al menos agregar el por defecto.
    if (empty($avatars)) {
        $avatars['01'] = get_string('avatar', 'local_datacurso') . ' 1 (' . get_string('default', 'core') . ')';
    }

    $settings->add(
        new admin_setting_configselect(
            'local_datacurso/tutoria_avatar',
            get_string('tutoria_avatar', 'local_datacurso'),
            get_string('tutoria_avatar_desc', 'local_datacurso'),
            '01', // Avatar por defecto.
            $avatars
        )
    );

    // Selector de posición del avatar (derecha/izquierda).
    $settings->add(
        new admin_setting_configselect(
            'local_datacurso/tutoria_avatar_position',
            get_string('tutoria_avatar_position', 'local_datacurso'),
            get_string('tutoria_avatar_position_desc', 'local_datacurso'),
            'right', // Posición por defecto.
            [
                'right' => get_string('position_right', 'local_datacurso'),
                'left' => get_string('position_left', 'local_datacurso'),
            ]
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
