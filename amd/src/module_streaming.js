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
 * Module Streaming Module for handling real-time module generation
 *
 * @module     local_datacurso/module_streaming
 * @copyright  2025 Wilber Narvaez <https://datacurso.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_string} from "core/str";

// Global state for scroll behavior
let userHasScrolled = false;
let scrollTimeout = null;

/**
 * Check if user is at the bottom of the scrollable container
 * @param {Element} element - The scrollable element
 * @returns {boolean} - True if user is at bottom
 */
const isAtBottom = (element) => {
  const threshold = 50; // 50px threshold
  return (
    element.scrollTop + element.clientHeight >= element.scrollHeight - threshold
  );
};

/**
 * Setup scroll detection to pause auto-scroll when user scrolls manually
 * @param {Element} scrollContainer - The container to monitor for scroll
 */
const setupScrollDetection = (scrollContainer) => {
  if (!scrollContainer) {
    return;
  }

  const handleScroll = () => {
    // Clear existing timeout
    if (scrollTimeout) {
      clearTimeout(scrollTimeout);
    }

    // Mark that user has scrolled
    userHasScrolled = true;

    // Check if user scrolled back to bottom
    if (isAtBottom(scrollContainer)) {
      // Reset flag after a short delay to resume auto-scroll
      scrollTimeout = setTimeout(() => {
        userHasScrolled = false;
      }, 1000);
    }
  };

  scrollContainer.addEventListener("scroll", handleScroll, { passive: true });
};

/**
 * Add status message to the streaming container with smart scrolling
 * @param {string} message - Status message
 * @param {string} type - Status type (info, success, warning, error)
 * @param {Element} container - Container element
 */
const addStatus = (message, type, container) => {
  const statusDiv = document.createElement("div");
  // Map status types to Bootstrap alert classes
  const alertClassMap = {
    success: "success",
    error: "danger",
    warning: "warning",
    info: "info"
  };

  statusDiv.className = `alert alert-${alertClassMap[type] || "info"} mb-2`;
  statusDiv.innerHTML = `<small>${message}</small>`;
  container.appendChild(statusDiv);

  // Only auto-scroll if user hasn't manually scrolled
  if (!userHasScrolled) {
    const modalBody = document.querySelector(".modal-body");
    if (modalBody) {
      modalBody.scrollTop = modalBody.scrollHeight;
    } else {
      container.scrollTop = container.scrollHeight;
    }
  }
};

/**
 * Start module streaming from the provided URL
 * @param {string} streamingUrl - The EventSource URL for streaming
 * @param {Element} container - Container element for displaying results
 * @param {Object} params - Additional parameters (courseid, sectionnum, beforemod)
 */
export const startModuleStreaming = async (
  streamingUrl,
  container,
  params = {}
) => {
  const progressIndicator = container.querySelector(
    "[data-region='local_datacurso/course_streaming/progress']"
  );
  const eventList = container.querySelector(
    "[data-region='local_datacurso/course_streaming']"
  );
  const progressIcon = container.querySelector(
    "[data-region='local_datacurso/course_streaming/progress/icon']"
  );

  // Clear previous content
  if (eventList) {
    eventList.innerHTML = "";
  }
  if (progressIndicator) {
    progressIndicator.style.display = "block";
  }

  // Reset scroll state for new streaming session
  userHasScrolled = false;
  if (scrollTimeout) {
    clearTimeout(scrollTimeout);
    scrollTimeout = null;
  }

  // Setup scroll detection on modal body
  const modalBody = document.querySelector(".modal-body");
  if (modalBody) {
    setupScrollDetection(modalBody);
  }

  const es = new EventSource(streamingUrl);

  // Essential event handlers for module creation
  const onResourceStart = async () => {
    const message = await get_string(
      "module_streaming_start",
      "local_datacurso"
    );
    addStatus(message, "info", eventList);
  };

  const onSchemaStart = async () => {
    const message = await get_string(
      "module_streaming_schema_start",
      "local_datacurso"
    );
    addStatus(message, "info", eventList);
  };

  const onSchemaDone = async () => {
    const message = await get_string(
      "module_streaming_schema_done",
      "local_datacurso"
    );
    addStatus(message, "success", eventList);
  };

  const onImagesStart = async () => {
    const message = await get_string(
      "module_streaming_images_start",
      "local_datacurso"
    );
    addStatus(message, "info", eventList);
  };

  const onImagesDone = async () => {
    const message = await get_string(
      "module_streaming_images_done",
      "local_datacurso"
    );
    addStatus(message, "success", eventList);
  };

  const onParametersStart = async () => {
    const message = await get_string(
      "module_streaming_parameters_start",
      "local_datacurso"
    );
    addStatus(message, "info", eventList);
  };

  const onParametersDone = async () => {
    const message = await get_string(
      "module_streaming_parameters_done",
      "local_datacurso"
    );
    addStatus(message, "success", eventList);
  };

  const onOutputStart = async () => {
    const message = await get_string(
      "module_streaming_output_start",
      "local_datacurso"
    );
    addStatus(message, "info", eventList);
  };

  const onResourceComplete = async () => {
    // Close the EventSource connection first
    es.close();

    if (progressIcon) {
      progressIcon.innerHTML = `
        <div class="bg-success rounded-circle d-flex align-items-center justify-content-center"
             style="width: 1.5rem; height: 1.5rem;">
          <i class="text-white" style="font-size: 0.8rem;">✓</i>
        </div>
      `;
    }

    const completeMessage = await get_string(
      "module_streaming_complete",
      "local_datacurso"
    );
    addStatus(completeMessage, "success", eventList);

    try {
      const { createMod } = await import("local_datacurso/repository/chatbot");
      const response = await createMod({
        courseid: params.courseid,
        sectionnum: params.sectionnum,
        beforemod: params.beforemod,
        jobid: params.jobid,
      });
      if (response && response.ok) {
        const successMessage = await get_string(
          "module_streaming_added_success",
          "local_datacurso"
        );
        addStatus(successMessage, "success", eventList);
        setTimeout(() => {
          window.location.href = response.data.activityurl;
        }, 1000);
      } else {
        const defaultError = await get_string(
          "module_streaming_add_error",
          "local_datacurso"
        );
        const msg =
          response && response.message ? response.message : defaultError;
        addStatus(`⚠️ ${msg}`, "error", eventList);
      }
    } catch (err) {
      const errorMessage = await get_string(
        "module_streaming_add_problem",
        "local_datacurso",
        err?.message || err
      );
      addStatus(`⚠️ ${errorMessage}`, "error", eventList);
    }
  };

  const onError = async () => {
    es.close();
    const errorMessage = await get_string(
      "module_streaming_creation_error",
      "local_datacurso"
    );
    addStatus(errorMessage, "error", eventList);
  };

  const generateImages = Number(params.generateimages) || 0;

  // Register only essential event listeners for better user experience
  es.addEventListener("resource_start", onResourceStart);
  es.addEventListener("resource_schema_start", onSchemaStart);
  es.addEventListener("resource_schema_done", onSchemaDone);
  if (generateImages === 1) {
    es.addEventListener("resource_images_start", onImagesStart);
    es.addEventListener("resource_images_done", onImagesDone);
  }
  es.addEventListener("resource_parameters_start", onParametersStart);
  es.addEventListener("resource_parameters_done", onParametersDone);
  es.addEventListener("resource_output_start", onOutputStart);
  es.addEventListener("resource_complete", onResourceComplete);
  es.addEventListener("error", onError);
};
