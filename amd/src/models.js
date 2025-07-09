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
 * TODO describe module models
 *
 * @module     local_datacurso/models
 * @copyright  2025 Industria Elearning <info@industriaelearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as repository from 'local_datacurso/repository/models';
import Templates from 'core/templates';
import {exception as displayException} from 'core/notification';

/**
 * Initialize the models page
 */
export async function init() {
    const models = await repository.getModels();

    // This will call the function to load and render our template.
    Templates.renderForPromise('local_datacurso/model_list', {models})

        // It returns a promise that needs to be resoved.
        .then(({html, js}) => {
            return Templates.replaceNodeContents('[data-region="local_datacurso-models-page"]', html, js);
        })
        // Deal with this exception (Using core/notify exception function is recommended).
        .catch((error) => displayException(error));
}