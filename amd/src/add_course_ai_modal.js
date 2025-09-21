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
import * as chatbotRepository from 'local_datacurso/repository/chatbot';
import {startStreaming} from 'local_datacurso/course_streaming';

let currentModal = null;

/**
 * Initialize and show the course AI modal
 * @param {Object} params - Parameters object
 * @param {string} params.streamingurl - The complete URL for course streaming (including session)
 * @returns {Promise}
 */
export const init = async(params = {}) => {
    try {
      console.log(params);
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
 * Add AI message bubble
 * @param {Element} wrap - Messages container
 * @param {string} text - Message text
 */
const pushAI = (wrap, text) => addBubble(wrap, text, "ai");

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
 * Add typing indicator
 * @param {Element} wrap - Messages container
 * @returns {Element} The typing element
 */
const pushTyping = (wrap) => {
  const row = document.createElement("div");
  row.className = "local_datacurso_ai_msg ai local_datacurso_ai_typing";
  const bubble = document.createElement("div");
  bubble.className = "bubble";
  bubble.innerHTML =
    '<span class="dot"></span><span class="dot"></span><span class="dot"></span>';
  row.appendChild(bubble);
  wrap.appendChild(row);
  scrollToBottom(wrap);
  return row;
};

/**
 * Remove typing indicator
 * @param {Element} el - The typing element to remove
 */
const removeTyping = (el) => el?.remove();

/**
 * Scroll messages to bottom
 * @param {Element} wrap - Messages container
 */
const scrollToBottom = (wrap) => {
  wrap.scrollTop = wrap.scrollHeight;
};

/**
 * Set loading state for button
 * @param {Element} btn - Button element
 * @param {boolean} isLoading - Loading state
 */
const setLoading = (btn, isLoading) => {
  btn.disabled = isLoading;
  btn.style.opacity = isLoading ? 0.7 : 1;
};

/**
 * Render WebService result
 * @param {Element} wrap - Messages container
 * @param {Object} response - API response
 */
const renderWSResult = (wrap, response) => {
  const lines = [];

  if (response?.success === false) {
    if (response?.message) {
      pushAI(wrap, response.message);
    } else {
      get_string("addcourseai_faildefault", "local_datacurso").then((msg) => {
        pushAI(wrap, msg);
        return msg;
      }).catch(() => {
        pushAI(wrap, 'It was not possible to create the course.');
      });
    }
    return;
  }

  if (response?.message) {
    lines.push(response.message);
  }

  if (lines.length) {
    pushAI(wrap, lines.join("\n"));
    setTimeout(() => {
      const last = wrap.querySelector(".local_datacurso_ai_msg.ai:last-child .bubble");
      if (last) {
        last.textContent = lines.join("\n");
      }
    }, 50);
  } else {
    get_string("addcourseai_done", "local_datacurso").then((msg) => {
      pushAI(wrap, msg);
      return msg;
    }).catch(() => {
      pushAI(wrap, 'Done! The course was created successfully.');
    });
  }
};
