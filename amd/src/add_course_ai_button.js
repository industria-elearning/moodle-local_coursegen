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
 * Add AI button to course creation/edit form
 *
 * @module     local_datacurso/add_course_ai_button
 * @copyright  2025 Buendata <soluciones@buendata.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Templates from "core/templates";
import { openCourseModal } from "local_datacurso/add_course_ai_modal";

/**
 * Initialize the AI button functionality
 */
export const init = () => {
  addAIButton();
};

/**
 * Add the AI button before the submit button
 */
const addAIButton = async () => {
  // Find the submit form element
  const submitElement = document.querySelector(".mb-3.fitem.form-submit");

  if (!submitElement) {
    // If not found, try alternative selectors
    const alternativeSubmit =
      document.querySelector('div[data-fieldtype="submit"]') ||
      document.querySelector('input[name="saveandreturn"]')?.closest(".fitem");

    if (alternativeSubmit) {
      await insertAIButton(alternativeSubmit);
    }
    return;
  }

  await insertAIButton(submitElement);
  const button = document.querySelector(
    '[data-action="local_datacurso/add_ai_course"]'
  );
  button.addEventListener("click", handleAIButtonClick);
};

/**
 * Handle the AI button click event
 */
const handleAIButtonClick = async () => {
  // Get course ID from URL if editing existing course
  const urlParams = new URLSearchParams(window.location.search);
  const courseId = urlParams.get("id");

  // Prepare payload for the modal
  const payload = {
    courseid: courseId || null,
    action: "create_course",
  };

  // Open the course AI modal
  await openCourseModal(payload);
};

/**
 * Insert the AI button before the target element
 * @param {Element} targetElement - The element before which to insert the button
 */
const insertAIButton = async (targetElement) => {
  // Check if button already exists to avoid duplicates
  if (document.querySelector('[data-action="local_datacurso/add_ai_course"]')) {
    return;
  }

  // Render the template (no context needed as template is self-contained)
  const { html } = await Templates.renderForPromise(
    "local_datacurso/add_ai_course_button",
    {}
  );
  const buttonContainer = document.createElement("div");
  buttonContainer.innerHTML = html;

  // Insert before the submit element
  targetElement.parentNode.insertBefore(buttonContainer, targetElement);
};
