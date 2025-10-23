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

import Templates from 'core/templates';
import {openChatModal} from 'local_coursegen/add_activity_ai';
/**
 * TODO describe module add_activity_ai_button
 *
 * @param {number} courseid - The course ID to add activity AI button for
 * @module     local_coursegen/add_activity_ai_button
 * @copyright  2025 Wilber Narvaez <https://datacurso.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
export function init(courseid) {
    const containers = document.querySelectorAll('.divider-content:has([data-action="open-chooser"])');
    containers.forEach(container => {
        injectButton(container, courseid);
    });
}

/**
 * Inject the add activity AI button into the container.
 *
 * @param {HTMLElement} container - The container to inject the button into
 * @param {number} courseid - The course ID to add activity AI button for
 */
export async function injectButton(container, courseid) {
    const openChooserButton = container.querySelector('[data-action="open-chooser"]');
    if (!openChooserButton) {
        return;
    }
    const sectionnum = openChooserButton.dataset.sectionnum;
    const beforemod = openChooserButton.dataset.beforemod;
    const arialabel = openChooserButton.getAttribute('aria-label');

    const {html} = await Templates.renderForPromise(
        'local_coursegen/add_activity_ai_button',
        {
            sectionnum,
            beforemod,
            arialabel,
        }
    );

    container.insertAdjacentHTML('beforeend', html);
    const addActivityAiButton = container.querySelector('.local_coursegen-add-activity-ai-button');
    addActivityAiButton.addEventListener('click', () => {
        openChatModal({
            sectionnum,
            beforemod,
            courseid,
        });
    });
}