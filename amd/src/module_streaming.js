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
 * Module Streaming Module for handling real-time module generation
 *
 * @module     local_datacurso/module_streaming
 * @copyright  2025 Buendata <soluciones@buendata.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import { get_string } from "core/str";

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
 * Add status message to the streaming container
 * @param {string} message - Status message
 * @param {string} type - Status type (info, success, warning, error)
 * @param {Element} container - Container element
 */
const addStatus = (message, type, container) => {
  const statusDiv = document.createElement("div");
  statusDiv.className = `alert alert-${
    type === "success" ? "success" : type === "error" ? "danger" : type === "warning" ? "warning" : "info"
  } mb-2`;
  statusDiv.innerHTML = `<small>${message}</small>`;
  container.appendChild(statusDiv);
  container.scrollTop = container.scrollHeight;
};

/**
 * Start module streaming from the provided URL
 * @param {string} streamingUrl - The EventSource URL for streaming
 * @param {Element} container - Container element for displaying results
 * @param {Object} params - Additional parameters (courseid, sectionnum, beforemod)
 */
export const startModuleStreaming = async (streamingUrl, container, params = {}) => {
  const progressIndicator = container.querySelector(
    "[data-region='local_datacurso/module_streaming/progress']"
  );
  const eventList = container.querySelector(
    "[data-region='local_datacurso/module_streaming']"
  );
  const progressIcon = container.querySelector(
    "[data-region='local_datacurso/module_streaming/progress/icon']"
  );

  console.log("Starting module streaming:", { streamingUrl, container, params });

  // Clear previous content
  if (eventList) {
    eventList.innerHTML = "";
  }
  if (progressIndicator) {
    progressIndicator.style.display = "block";
  }

  const es = new EventSource(streamingUrl);

  // Event handlers for module creation steps
  const onResourceStart = async (e) => {
    console.log("onResourceStart", e);
    const obj = parseBest(e.data) || {};
    addStatus("Iniciando creación del módulo...", "info", eventList);
  };

  const onIntentContextStart = async (e) => {
    console.log("onIntentContextStart", e);
    addStatus("Analizando intención y contexto...", "info", eventList);
  };

  const onIntentContextDone = async (e) => {
    console.log("onIntentContextDone", e);
    addStatus("✓ Intención y contexto analizados", "success", eventList);
  };

  const onSchemaStart = async (e) => {
    console.log("onSchemaStart", e);
    addStatus("Generando esquema del módulo...", "info", eventList);
  };

  const onSchemaDone = async (e) => {
    console.log("onSchemaDone", e);
    addStatus("✓ Esquema del módulo generado", "success", eventList);
  };

  const onDateStart = async (e) => {
    console.log("onDateStart", e);
    addStatus("Procesando fechas...", "info", eventList);
  };

  const onDateDone = async (e) => {
    console.log("onDateDone", e);
    addStatus("✓ Fechas procesadas", "success", eventList);
  };

  const onParametersStart = async (e) => {
    console.log("onParametersStart", e);
    addStatus("Configurando parámetros...", "info", eventList);
  };

  const onParametersDone = async (e) => {
    console.log("onParametersDone", e);
    addStatus("✓ Parámetros configurados", "success", eventList);
  };

  const onImagesStart = async (e) => {
    console.log("onImagesStart", e);
    addStatus("Generando imágenes...", "info", eventList);
  };

  const onImagesDone = async (e) => {
    console.log("onImagesDone", e);
    addStatus("✓ Imágenes generadas", "success", eventList);
  };

  const onOutputStart = async (e) => {
    console.log("onOutputStart", e);
    addStatus("Preparando salida final...", "info", eventList);
  };

  const onOutputDone = async (e) => {
    console.log("onOutputDone", e);
    addStatus("✓ Salida preparada", "success", eventList);
  };

  const onResourceComplete = async (e) => {
    console.log("onResourceComplete", e);

    // Close the EventSource connection first
    es.close();
    
    if (progressIcon) {
      progressIcon.innerHTML = `
        <div class="bg-success rounded-circle d-flex align-items-center justify-content-center" style="width: 1.5rem; height: 1.5rem;">
          <i class="text-white" style="font-size: 0.8rem;">✓</i>
        </div>
      `;
    }

    addStatus("✓ Generación del módulo completada", "success", eventList);

    try {
      const { createMod } = await import('local_datacurso/repository/chatbot');
      const response = await createMod({
        courseid: params.courseid,
        sectionnum: params.sectionnum,
        beforemod: params.beforemod,
        prompt: '', // No se usa cuando se envía jobid.
        generateimages: 0,
        jobid: params.jobid,
      });
      if (response && response.ok) {
        addStatus("✓ Módulo creado exitosamente", "success", eventList);
      } else {
        const msg = (response && response.message) ? response.message : 'No se pudo crear el módulo.';
        addStatus(`❌ ${msg}`, "error", eventList);
      }
    } catch (err) {
      console.error('Error al crear el módulo desde resource_complete:', err);
      addStatus(`❌ Error creando el módulo: ${err?.message || err}`, "error", eventList);
    }
  };

  const onError = async (e) => {
    console.error("Streaming error:", e);
    es.close();
    addStatus("❌ Error en la creación del módulo", "error", eventList);
  };

  // Register event listeners for all module creation steps
  es.addEventListener("resource_start", onResourceStart);
  es.addEventListener("resource_intent_context_start", onIntentContextStart);
  es.addEventListener("resource_intent_context_done", onIntentContextDone);
  es.addEventListener("resource_schema_start", onSchemaStart);
  es.addEventListener("resource_schema_done", onSchemaDone);
  es.addEventListener("resource_date_start", onDateStart);
  es.addEventListener("resource_date_done", onDateDone);
  es.addEventListener("resource_parameters_start", onParametersStart);
  es.addEventListener("resource_parameters_done", onParametersDone);
  es.addEventListener("resource_images_start", onImagesStart);
  es.addEventListener("resource_images_done", onImagesDone);
  es.addEventListener("resource_output_start", onOutputStart);
  es.addEventListener("resource_output_done", onOutputDone);
  es.addEventListener("resource_complete", onResourceComplete);
  es.addEventListener("error", onError);
};
