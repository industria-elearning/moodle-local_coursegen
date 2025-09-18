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
 * Course AI Modal Module - Modern ES6 implementation
 *
 * @module     local_datacurso/add_course_ai_modal
 * @copyright  2025 Buendata <soluciones@buendata.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Templates from "core/templates";
import Notification from "core/notification";
import Modal from "core/modal";
// eslint-disable-next-line camelcase
import { get_string } from "core/str";
import chatbotRepository from "local_datacurso/repository/chatbot";

let modal = null;

/**
 * Open the course AI chat modal
 * @param {Object} payload - The payload data for the course
 */
export const openCourseModal = async (payload = {}) => {
  try {
    // Close existing modal if open
    if (modal) {
      await modal.destroy();
      modal = null;
    }

    // Render modal body template
    const bodyHTML = await Templates.render(
      "local_datacurso/add_course_ai_modal",
      {}
    );

    // Get modal title
    const title = await get_string("addcourseai_modaltitle", "local_datacurso");

    // Create modal
    modal = await Modal.create({
      title,
      large: true,
      show: true,
      removeOnClose: true,
    });

    // Handle modal close event
    modal.getRoot().on("hidden.bs.modal", () => {
      if (modal) {
        modal.destroy();
        modal = null;
      }
    });

    // Set modal body
    await modal.setBody(bodyHTML);

    // Wire up chat handlers
    const bodyEl = modal.getBody()[0];
    wireChatHandlers(bodyEl, payload);
  } catch (error) {
    Notification.exception(error);
  }
};

/**
 * Wire up chat handlers for the modal
 * @param {Element} container - The modal container element
 * @param {Object} payload - The payload data
 */
const wireChatHandlers = (container, payload) => {
  const messagesEl = container.querySelector(".bdai-messages");
  const form = container.querySelector("form.bdai-input");
  const textarea = form.querySelector("textarea");
  const sendBtn = form.querySelector(".bdai-send");

  // Welcome message
  get_string('addcourseai_welcome', 'local_datacurso').then((welcomeMsg) => {
    pushAI(messagesEl, welcomeMsg);
    return welcomeMsg;
  }).catch(() => {
    // Fallback welcome message
    pushAI(messagesEl, 'Hi! Tell me what course you need and I will help you create it. 😊');
  });

  // Handle form submission
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const prompt = textarea.value.trim();
    if (!prompt) {
      return;
    }

    // Get generate images option
    const generateImages =
      document.querySelector('input[name="generate_images"]:checked')?.value ||
      "no";

    // Add user message
    pushUser(messagesEl, prompt);
    textarea.value = "";
    textarea.focus();

    // Disable form elements
    setFormDisabled(true);
    setLoading(sendBtn, true);
    const typing = pushTyping(messagesEl);

    try {
      // Call API to create course
      const response = await chatbotRepository.createCourse({
        ...payload,
        prompt,
        generateimages: generateImages,
      });

      if (!response.ok) {
        throw new Error(response.message);
      }

      removeTyping(typing);
      renderWSResult(messagesEl, response);

      // Reload page after success
      setTimeout(() => {
        window.location.reload();
      }, 800);
    } catch (error) {
      removeTyping(typing);

      if (!error) {
        // Handle timeout case
        const successMsg = await get_string(
          "coursecreatedsuccess",
          "local_datacurso"
        );
        pushAI(messagesEl, successMsg);
        setTimeout(() => {
          window.location.reload();
        }, 800);
      } else {
        // Handle other errors
        const errorMsg = await get_string(
          "addcourseai_error",
          "local_datacurso"
        );
        pushAI(messagesEl, `❌ ${errorMsg}`);
      }
    } finally {
      setFormDisabled(false);
      setLoading(sendBtn, false);
      scrollToBottom(messagesEl);
    }
  });

  // Handle Enter key for sending
  textarea.addEventListener("keydown", (e) => {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      form.requestSubmit();
    }
  });

  /**
   * Enable/disable form elements
   * @param {boolean} disabled - Whether to disable the form
   */
  function setFormDisabled(disabled) {
    textarea.disabled = disabled;
    sendBtn.disabled = disabled;
    const radioButtons = document.querySelectorAll(
      'input[name="generate_images"]'
    );
    radioButtons.forEach((rb) => {
      rb.disabled = disabled;
    });
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
  row.className = `bdai-msg ${role}`;
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
  row.className = "bdai-msg ai bdai-typing";
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
      const last = wrap.querySelector(".bdai-msg.ai:last-child .bubble");
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
