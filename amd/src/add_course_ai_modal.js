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
import {startStreaming, startExecutionStreaming} from 'local_datacurso/course_streaming';
import {planCourseMessage, planCourseExecute} from 'local_datacurso/repository/chatbot';

let currentModal = null;

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
  return element.scrollTop + element.clientHeight >= element.scrollHeight - threshold;
};

/**
 * Setup scroll detection to pause auto-scroll when user scrolls manually
 * @param {Element} scrollContainer - The container to monitor for scroll
 */
const setupScrollDetection = (scrollContainer) => {
  if (!scrollContainer) return;
  
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
  
  scrollContainer.addEventListener('scroll', handleScroll, { passive: true });
};

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
        
        // Reset scroll state and setup detection
        userHasScrolled = false;
        if (scrollTimeout) {
          clearTimeout(scrollTimeout);
          scrollTimeout = null;
        }
        
        // Setup scroll detection on modal body
        const modalBody = currentModal.getBody()[0];
        if (modalBody) {
          setupScrollDetection(modalBody);
        }
        
        initializeChatInterface(bodyEl, params);
        setupPlanningButtons(bodyEl, params);
        setupPlanningToggle(bodyEl);
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
        // Ensure chat interface is hidden when (re)starting streaming
        const chatInterface = container.querySelector('#course-chat-interface');
        if (chatInterface) {
            chatInterface.style.display = 'none';
        }

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
 * Scroll messages to bottom smoothly - only if user hasn't scrolled
 * @param {Element} wrap - Messages container
 */
const scrollToBottom = (wrap) => {
  if (!userHasScrolled) {
    const modalBody = document.querySelector('.modal-body');
    if (modalBody) {
      modalBody.scrollTop = modalBody.scrollHeight;
    } else {
      wrap.scrollTop = wrap.scrollHeight;
    }
  }
};

/**
 * Typewriter effect for streaming text into an element with auto-scroll.
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
        
        // Auto-scroll during typewriter effect - only if user hasn't scrolled
        if (!userHasScrolled) {
          const modalBody = document.querySelector('.modal-body');
          if (modalBody) {
            modalBody.scrollTop = modalBody.scrollHeight;
          }
        }
        
        setTimeout(typing, speed);
      } else {
        resolve();
      }
    }
    typing();
  });
}

/**
 * Collapse the planning phase and show summary
 * @param {Element} container - The modal container element
 */
const collapsePlanningPhase = (container) => {
    const planningPhase = container.querySelector('#planning-phase-section');
    const planningSummary = container.querySelector('#planning-summary-collapsed');
    const planningDetailsContent = container.querySelector('#planning-details-content');
    
    if (planningPhase && planningSummary && planningDetailsContent) {
        // Move planning content to collapsed section
        const planningContent = planningPhase.innerHTML;
        planningDetailsContent.innerHTML = planningContent;
        
        // Hide planning phase and show summary
        planningPhase.style.display = 'none';
        planningSummary.style.display = 'block';
    }
};

/**
 * Setup collapsible planning details toggle
 * @param {Element} container - The modal container element
 */
const setupPlanningToggle = (container) => {
    const toggleBtn = container.querySelector('#toggle-planning-details');
    const collapseElement = container.querySelector('#planning-details-collapse');
    const toggleIcon = container.querySelector('#planning-toggle-icon');
    
    if (toggleBtn && collapseElement && toggleIcon) {
        toggleBtn.addEventListener('click', () => {
            const isCollapsed = !collapseElement.classList.contains('show');
            
            if (isCollapsed) {
                collapseElement.classList.add('show');
                toggleIcon.classList.remove('fa-chevron-down');
                toggleIcon.classList.add('fa-chevron-up');
            } else {
                collapseElement.classList.remove('show');
                toggleIcon.classList.remove('fa-chevron-up');
                toggleIcon.classList.add('fa-chevron-down');
            }
        });
    }
};

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
            acceptBtn.textContent = await get_string('creating_course', 'local_datacurso');
            
            try {
                // Extract course ID from streaming URL or use params.courseid
                const courseId = params.courseid;
                
                if (!courseId) {
                    throw new Error(await get_string('error_no_course_id', 'local_datacurso'));
                }

                // Call the plan course execute webservice
                const response = await planCourseExecute(courseId);
                
                if (!response.success) {
                    throw new Error(response.message || await get_string('error_executing_plan', 'local_datacurso'));
                }

                // Hide planning buttons since execution has started
                const planningActions = container.querySelector('#course-planning-actions');
                if (planningActions) {
                    planningActions.style.display = 'none';
                }

                // Collapse planning phase and show execution phase
                collapsePlanningPhase(container);

                // Get execution container
                const executionContainer = container.querySelector('#execution-phase-container');

                // Start execution streaming
                if (response.data && response.data.streamingurl && executionContainer) {
                    
                    // Create streaming block template with execution-specific texts
                    const html = await Templates.render('local_datacurso/course_streaming_inline', {
                        title: await get_string('course_creating_title', 'local_datacurso'),
                        subtitle: await get_string('course_creating_subtitle', 'local_datacurso')
                    });
                    const temp = document.createElement('div');
                    temp.innerHTML = html;
                    const streamingBlock = temp.firstElementChild;
                    executionContainer.appendChild(streamingBlock);
                    await startExecutionStreaming(response.data.streamingurl, streamingBlock, courseId);
                }

                // Show success notification after execution completes
                const message = await get_string('addcourseai_done', 'local_datacurso')
                Notification.addNotification({
                    message: message,
                    type: 'success'
                });
                
            } catch (error) {
                console.error('Error creating course:', error);
                acceptBtn.disabled = false;
                acceptBtn.textContent = await get_string('accept_planning_create_course', 'local_datacurso');
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
                    const errorMsg = response.message || await get_string('error_processing_request', 'local_datacurso');
                    await typeWriter(errorResponse, errorMsg, 15);
                    return;
                }

                // If backend returns a streaming URL, render an inline streaming block and start streaming
                const streamingUrl = response.data.streamingurl;
                if (streamingUrl) {
                    // Hide chat interface when a new streaming session starts
                    if (chatInterface) {
                        chatInterface.style.display = 'none';
                    }

                    // Add a separator to distinguish the correction from previous content
                    const separator = document.createElement('div');
                    separator.className = 'mt-4 mb-3 border-top pt-3';
                    const correctionText = await get_string('adjust_planning_title', 'local_datacurso');
                    separator.innerHTML = `<h6 class="text-muted"><i class="fa fa-edit"></i> ${correctionText}</h6>`;
                    streamingContainer.appendChild(separator);

                    const html = await Templates.render('local_datacurso/course_streaming_inline', {});
                    const temp = document.createElement('div');
                    temp.innerHTML = html;
                    const streamingBlock = temp.firstElementChild;
                    streamingContainer.appendChild(streamingBlock);
                    await startStreaming(streamingUrl, streamingBlock, true); // Pass true to indicate this is a correction
                }
                
            } catch (error) {
                // Add error message as streaming text
                const errorResponse = document.createElement("div");
                errorResponse.className = "mb-3 text-danger";
                streamingContainer.appendChild(errorResponse);
                const errorMsg = await get_string('error_sending_message', 'local_datacurso');
                await typeWriter(errorResponse, `${errorMsg}: ${error.message}`, 15);
                console.error('Error sending message:', error);
            } finally {
                submitBtn.disabled = false;
            }
        });
    }
};

