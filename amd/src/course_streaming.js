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

let htmlBuffer = '';
let rafPending = false;

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
    const progressIndicator = container.querySelector("[data-region='local_datacurso/course_streaming/progress']");
    const eventList = container.querySelector("[data-region='local_datacurso/course_streaming']");
    const progressIcon = container.querySelector("[data-region='local_datacurso/course_streaming/progress/icon']");
  
    eventList.innerHTML = "";
    if (progressIndicator) {
      progressIndicator.style.display = "block";
    }
  
    const evtSource = new EventSource(streamingUrl);
  
    evtSource.addEventListener('assistant_token', (event) => {
      appendToken(event.data, eventList);
    });

    evtSource.addEventListener('assistant_message_end', () => {
      progressIcon.innerHTML = `
        <div class="bg-success rounded-circle d-flex align-items-center justify-content-center" style="width: 1.5rem; height: 1.5rem;">
          <i class="text-white" style="font-size: 0.8rem;">✓</i>
        </div>
      `;
      const planningActions = document.getElementById('course-planning-actions');
      if (planningActions) {
        planningActions.style.display = 'block';
      }
      evtSource.close();
    });
};

function updateHtmlSoon(container) {
  if (rafPending) return;
  rafPending = true;
  requestAnimationFrame(() => {
    rafPending = false;
    container.innerHTML = htmlBuffer;
    container.scrollTop = container.scrollHeight;
  });
}

function appendToken(token, container) {
  htmlBuffer += token;
  updateHtmlSoon(container);
}
  
