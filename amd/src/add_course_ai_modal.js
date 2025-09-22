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
 * Course AI Modal Module using Moodle's modal factory
 *
 * @module     local_datacurso/add_course_ai_modal
 * @copyright  2025 Buendata <soluciones@buendata.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Modal from 'core/modal';
import Templates from 'core/templates';
import Notification from 'core/notification';
import {get_string} from 'core/str';
import {startStreaming} from 'local_datacurso/course_streaming';
import {planCourseMessage} from 'local_datacurso/repository/chatbot';

let currentModal = null;

/**
 * Initialize and show the course AI modal
 * @param {Object} params - Parameters object
 * @param {string} params.streamingurl - The complete URL for course streaming (including session)
 * @returns {Promise}
 */
export const init = async(params = {}) => {
    try {
        // Close existing modal if open
        if (currentModal) {
            currentModal.destroy();
            currentModal = null;
        }

        // Get modal title and body content
        const [title, bodyHTML] = await Promise.all([
            get_string('addcourseai_modaltitle', 'local_datacurso'),
            Templates.render('local_datacurso/add_course_ai_modal', {})
        ]);

        // Create modal using modern Modal class
        currentModal = await Modal.create({
            title: title,
            body: bodyHTML,
            large: true,
            scrollable: true,
            removeOnClose: true
        });

        currentModal.getRoot().addClass('local_datacurso_course_ai_modal');

        currentModal.show();

        const bodyEl = currentModal.getBody()[0];
        initializeChatInterface(bodyEl, params);
        setupPlanningButtons(bodyEl, params);
        return currentModal;

    } catch (error) {
        Notification.exception(error);
        return null;
    }
};

/**
 * Initialize the chat interface
 * @param {Element} container - The modal container element
 * @param {Object} params - The parameters including streaming URL
 */
const initializeChatInterface = async (container, params) => {
    // Find the streaming button and add event listener
    try {
        // Start streaming with the URL provided by PHP (already includes session)
        await startStreaming(params.streamingurl, container);
        
    } catch (error) {
        console.error('Error starting streaming:', error);
        Notification.exception(error);
    }
};

/**
 * Add user message bubble
 * @param {Element} wrap - Messages container
 * @param {string} text - Message text
 */
const pushUser = (wrap, text) => addBubble(wrap, text, "user");

/**
 * Add message bubble
 * @param {Element} wrap - Messages container
 * @param {string} text - Message text
 * @param {string} role - Message role (user/ai)
 */
const addBubble = (wrap, text, role) => {
  const row = document.createElement("div");
  row.className = `local_datacurso_ai_msg ${role}`;
  const bubble = document.createElement("div");
  bubble.className = "bubble";
  bubble.textContent = text;
  row.appendChild(bubble);
  wrap.appendChild(row);
  scrollToBottom(wrap);
};

/**
 * Scroll messages to bottom
 * @param {Element} wrap - Messages container
 */
const scrollToBottom = (wrap) => {
  wrap.scrollTop = wrap.scrollHeight;
};

/**
 * Typewriter effect for streaming text into an element.
 * @param {HTMLElement} element
 * @param {string} text
 * @param {number} speed
 * @returns {Promise<void>}
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
 * Setup planning buttons event handlers
 * @param {Element} container - The modal container element
 * @param {Object} params - The parameters including course ID
 */
const setupPlanningButtons = (container, params) => {
    const acceptBtn = container.querySelector('#accept-planning-btn');
    const adjustBtn = container.querySelector('#adjust-planning-btn');
    const chatInterface = container.querySelector('#course-chat-interface');
    const chatForm = container.querySelector('#course-chat-form');
    const chatInput = container.querySelector('#courseChatInput');
    const streamingContainer = container.querySelector("[data-region='local_datacurso/course_streaming']");

    if (acceptBtn) {
        acceptBtn.addEventListener('click', async () => {
            // Disable button to prevent double clicks
            acceptBtn.disabled = true;
            acceptBtn.textContent = 'Creating Course...';
            
            try {
                // Here you would call the actual course creation API
                // For now, just show a success message
                await new Promise(resolve => setTimeout(resolve, 2000)); // Simulate API call
                
                get_string('addcourseai_done', 'local_datacurso').then((msg) => {
                    Notification.addNotification({
                        message: msg,
                        type: 'success'
                    });
                    if (currentModal) {
                        currentModal.destroy();
                    }
                }).catch(() => {
                    Notification.addNotification({
                        message: 'Course created successfully!',
                        type: 'success'
                    });
                    if (currentModal) {
                        currentModal.destroy();
                    }
                });
                
            } catch (error) {
                acceptBtn.disabled = false;
                get_string('accept_planning_create_course', 'local_datacurso').then((msg) => {
                    acceptBtn.textContent = msg;
                });
                Notification.exception(error);
            }
        });
    }

    if (adjustBtn) {
        adjustBtn.addEventListener('click', () => {
            // Disable button to prevent double clicks
            adjustBtn.disabled = true;
            
            // Show chat interface
            if (chatInterface) {
                chatInterface.style.display = 'block';
                chatInput.focus();
            }
        });
    }

    // Add Enter key support for chat input
    if (chatInput) {
        chatInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (chatForm) {
                    chatForm.dispatchEvent(new Event('submit'));
                }
            }
        });
    }

    if (chatForm) {
        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const message = chatInput.value.trim();
            if (!message) return;

            // Check if streamingContainer exists
            if (!streamingContainer) {
                console.error('Streaming container not found');
                return;
            }

            // Add user message bubble to streaming container
            pushUser(streamingContainer, message);
            
            // Clear input
            chatInput.value = '';
            
            // Disable form while processing
            const submitBtn = chatForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            
            try {
                // Call the plan_course_message via repository
                const response = await planCourseMessage({
                    courseid: params.courseid,
                    text: message,
                });
                
                if (!response.success) {
                    // Add error message as streaming text
                    const errorResponse = document.createElement("div");
                    errorResponse.className = "mb-3 text-danger";
                    streamingContainer.appendChild(errorResponse);
                    await typeWriter(errorResponse, response.message || 'Error processing your request', 15);
                }

                // If backend returns a streaming URL, render an inline streaming block and start streaming
                const streamingUrl = response.data.streamingurl;
                if (streamingUrl) {
                    const html = await Templates.render('local_datacurso/course_streaming_inline', {});
                    const temp = document.createElement('div');
                    temp.innerHTML = html;
                    const streamingBlock = temp.firstElementChild;
                    streamingContainer.appendChild(streamingBlock);
                    await startStreaming(streamingUrl, streamingBlock);
                }
                
            } catch (error) {
                // Add error message as streaming text
                const errorResponse = document.createElement("div");
                errorResponse.className = "mb-3 text-danger";
                streamingContainer.appendChild(errorResponse);
                await typeWriter(errorResponse, 'Error: ' + error.message, 15);
                console.error('Error sending message:', error);
            } finally {
                submitBtn.disabled = false;
            }
        });
    }
};

