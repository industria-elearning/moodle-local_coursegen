/* eslint-disable */
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
 * Course Streaming Module for handling real-time course generation
 *
 * @module     local_datacurso/course_streaming
 * @copyright  2025 Buendata <soluciones@buendata.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import { createCourse } from "local_datacurso/repository/chatbot";

/**
 * Start course streaming from the provided URL
 * @param {string} streamingUrl - The EventSource URL for streaming
 * @param {Element} container - Container element for displaying results
 * @param {boolean} isCorrection - Whether this is a plan correction (don't clear existing content)
 * @returns {Promise} Promise that resolves when streaming is complete
 */
export const startStreaming = async (streamingUrl, container, isCorrection = false) => {
  const progressIndicator = container.querySelector(
    "[data-region='local_datacurso/course_streaming/progress']"
  );
  const eventList = container.querySelector(
    "[data-region='local_datacurso/course_streaming']"
  );
  const progressIcon = container.querySelector(
    "[data-region='local_datacurso/course_streaming/progress/icon']"
  );

  // Create local buffer and RAF state for this streaming instance
  let htmlBuffer = "";
  let rafPending = false;

  // Only clear content if this is not a correction
  if (!isCorrection) {
    eventList.innerHTML = "";
  }
  if (progressIndicator) {
    progressIndicator.style.display = "block";
  }

  // Local functions for this streaming instance
  const updateHtmlSoon = (container) => {
    if (rafPending) return;
    rafPending = true;
    requestAnimationFrame(() => {
      rafPending = false;
      container.innerHTML = htmlBuffer;
      container.scrollTop = container.scrollHeight;
    });
  };

  const appendToken = (token, container) => {
    htmlBuffer += token;
    updateHtmlSoon(container);
  };

  const evtSource = new EventSource(streamingUrl);

  evtSource.addEventListener("assistant_token", (event) => {
    appendToken(event.data, eventList);
  });

  evtSource.addEventListener("assistant_message_end", () => {
    progressIcon.innerHTML = `
        <div class="bg-success rounded-circle d-flex align-items-center justify-content-center" style="width: 1.5rem; height: 1.5rem;">
          <i class="text-white" style="font-size: 0.8rem;">✓</i>
        </div>
      `;
    const planningActions = document.getElementById("course-planning-actions");
    if (planningActions) {
      planningActions.style.display = "block";
    }
    evtSource.close();
  });
};

/**
 * Helper to parse backend dict-like payloads
 * @param {string} raw - Raw data from event
 * @returns {Object|null} Parsed object or null
 */
const parseBest = (raw) => {
  if (!raw) return null;
  const s = String(raw).trim();
  try {
    return JSON.parse(s);
  } catch (_) {}
  try {
    // Best-effort convert Python dict repr to JSON
    let t = s
      .replace(/'/g, '"')
      .replace(/\bNone\b/g, "null")
      .replace(/\bTrue\b/g, "true")
      .replace(/\bFalse\b/g, "false");
    return JSON.parse(t);
  } catch (_) {
    return null;
  }
};

/**
 * Add status message to the execution container
 * @param {string} message - Status message
 * @param {string} type - Status type (info, ok, error)
 * @param {Element} container - Container element
 */
const addStatus = (message, type, container) => {
  const statusDiv = document.createElement("div");
  statusDiv.className = `alert alert-${
    type === "ok" ? "success" : type === "error" ? "danger" : "info"
  } mb-2`;
  statusDiv.innerHTML = `<small>${message}</small>`;
  container.appendChild(statusDiv);
  container.scrollTop = container.scrollHeight;
};

/**
 * Start execution streaming from the provided URL
 * @param {string} streamingUrl - The EventSource URL for streaming
 * @param {Element} container - Container element for displaying results
 * @returns {Promise} Promise that resolves when streaming is complete
 */
export const startExecutionStreaming = async (
  streamingUrl,
  container,
  courseid
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

  console.log({ container, progressIndicator, eventList, progressIcon });
  eventList.innerHTML = "";
  if (progressIndicator) {
    progressIndicator.style.display = "block";
  }

  const es = new EventSource(streamingUrl);

  const onActStart = (e) => {
    console.log("onActStart", e);
    const obj = parseBest(e.data) || {};
    const idx = obj.index ?? "?";
    const title = obj.title || "";
    const sec = obj.section_index ?? "?";
    addStatus(
      `🧩 Iniciando actividad #${idx} (sección ${sec}): ${title}`,
      "info",
      eventList
    );
  };

  const onActDone = (e) => {
    console.log("onActDone", e);
    const obj = parseBest(e.data) || {};
    const done = obj.done ?? 0;
    const total = obj.total ?? 0;
    const percent = obj.percent ?? 0;
    addStatus(
      `✅ Actividad completada (${done}/${total}) — ${percent}%`,
      "ok",
      eventList
    );
  };

  const onProgress = (e) => {
    console.log("onProgress", e);
    const obj = parseBest(e.data) || {};
    const done = obj.done ?? 0;
    const total = obj.total ?? 0;
    const percent = obj.percent ?? 0;
    addStatus(`📈 Progreso: ${done}/${total} (${percent}%)`, "info", eventList);
  };

  const onExecError = (e) => {
    console.log("onExecError", e);
    addStatus(`❌ Error en una actividad`, "error", eventList);
  };

  const onComplete = async (e) => {
    console.log("onComplete", e);

    // Close the EventSource connection first
    es.close();
    progressIcon.innerHTML = `
        <div class="bg-success rounded-circle d-flex align-items-center justify-content-center" style="width: 1.5rem; height: 1.5rem;">
          <i class="text-white" style="font-size: 0.8rem;">✓</i>
        </div>
      `;

    // Call createCourse to apply the AI-generated content
    try {
      const result = await createCourse({ courseid });

      if (result.success) {
        addStatus("✅ Curso creado exitosamente", "ok", eventList);
        // Reload the page after 500ms
        setTimeout(() => {
          window.location.reload();
        }, 500);
      } else {
        addStatus(
          `❌ Error al crear el curso: ${result.message}`,
          "error",
          eventList
        );
      }
    } catch (error) {
      addStatus(
        `❌ Error al crear el curso: ${error.message}`,
        "error",
        eventList
      );
    }
  };

  // Register event listeners
  es.addEventListener("execution_activity_start", onActStart);
  es.addEventListener("execution_activity_done", onActDone);
  es.addEventListener("execution_progress", onProgress);
  es.addEventListener("execution_error", onExecError);
  es.addEventListener("execution_complete", onComplete);
};

