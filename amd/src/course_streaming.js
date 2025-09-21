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

function typeWriter(element, text, speed) {
  return new Promise((resolve) => {
    let i = 0;
    function typing() {
      if (i < text.length) {
        element.textContent += text.charAt(i);
        i++;
        setTimeout(typing, speed);
      } else {
        resolve();
      }
    }
    typing();
  });
}

/**
 * Start course streaming from the provided URL
 * @param {string} streamingUrl - The EventSource URL for streaming
 * @param {Element} container - Container element for displaying results
 * @returns {Promise} Promise that resolves when streaming is complete
 */
export const startStreaming = async (streamingUrl, container) => {
    const progressIndicator = document.getElementById("progress-indicator");
    const eventList = container.querySelector("[data-region='local_datacurso/course_streaming']");
    const progressIcon = document.getElementById("progress-icon");
  
    eventList.innerHTML = "";
    progressIndicator.style.display = "block";
  
    const evtSource = new EventSource(streamingUrl);
  
    evtSource.onmessage = async (event) => {
      const data = JSON.parse(event.data);
      console.log("Received:", data);
  
      switch (data.type) {
        case "section_start":
          const sectionHeader = document.createElement("div");
          sectionHeader.className = "mb-3";
          eventList.appendChild(sectionHeader);
  
          const title = document.createElement("h3");
          title.className = "mb-2";
          sectionHeader.appendChild(title);
          await typeWriter(
            title,
            `Sección ${data.section.section}: ${data.section.title}`,
            20
          );
  
          const details = document.createElement("ul");
          details.className = "mb-2 pl-3";
          sectionHeader.appendChild(details);
  
          const hours = document.createElement("li");
          details.appendChild(hours);
          await typeWriter(
            hours,
            `Horas teóricas: ${data.section.ht_hours} | Horas autónomas: ${data.section.had_hours}`,
            15
          );
  
          const objectives = document.createElement("li");
          details.appendChild(objectives);
          await typeWriter(
            objectives,
            `Objetivos: ${data.section.objectives}`,
            10
          );
          break;
  
        case "activity":
          const activityItem = document.createElement("ul");
          activityItem.className = "ml-4 mb-2";
          eventList.appendChild(activityItem);
  
          const activityLine = document.createElement("li");
          activityItem.appendChild(activityLine);
  
          const activityTitle = document.createElement("strong");
          activityLine.appendChild(activityTitle);
          await typeWriter(activityTitle, data.activity.title, 25);
  
          const activityDesc = document.createElement("span");
          activityLine.appendChild(activityDesc);
          await typeWriter(activityDesc, `: ${data.activity.description}`, 8);
  
          const resourceType = document.createElement("em");
          resourceType.textContent = ` (${data.activity.resource_type})`;
          activityLine.appendChild(resourceType);
          break;
  
        case "section_end":
          const separator = document.createElement("hr");
          eventList.appendChild(separator);
          break;
  
        case "complete":
          progressIcon.innerHTML = `
              <div class="bg-success rounded-circle d-flex align-items-center justify-content-center" style="width: 1.5rem; height: 1.5rem;">
                <i class="text-white" style="font-size: 0.8rem;">✓</i>
              </div>
            `;
          const completionMsg = document.createElement("div");
          completionMsg.className = "mt-4 text-center";
          eventList.appendChild(completionMsg);
          await typeWriter(completionMsg, "✅ Plan del curso completado", 50);
          evtSource.close();
          break;
      }
    };
  }
  
