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
 * TODO describe module add_activity_ai
 *
 * @module     local_datacurso/add_activity_ai
 * @copyright  2025 Buendata <soluciones@buendata.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
  "core/templates",
  "core/notification",
  "core/modal",
  "core/custom_interaction_events",
  "core/str",
  "local_datacurso/repository/chatbot",
  "local_datacurso/module_streaming",
], (Templates, Notification, Modal, CustomEvents, Str, chatbotRepository, moduleStreaming) => {
  const LINK_SELECTOR = '[data-action="local_datacurso/add_activity_ai"]';

  let modal = null;
  let initialized = false;

  const init = () => {
    if (initialized) {
      return;
    }
    initialized = true;

    const events = [
      "click",
      CustomEvents.events.activate,
      CustomEvents.events.keyboardActivate,
    ];

    CustomEvents.define(document, events);

    events.forEach((event) => {
      document.addEventListener(event, async (e) => {
        const link = e.target.closest(LINK_SELECTOR);
        if (!link) {
          return;
        }
        e.preventDefault();
        const payload = readDataset(link);
        await openChatModal(payload);
      });
    });
  };

  const readDataset = (el) => {
    const { sectionnum, beforemod } = el.dataset;
    return {
      sectionnum: Number(sectionnum),
      beforemod: beforemod ? Number(beforemod) : null,
    };
  };

  const openChatModal = async (payload) => {
    try {
      // Si ya hay un modal abierto, cerrarlo primero
      if (modal) {
        await modal.destroy();
        modal = null;
      }

      const bodyHTML = await Templates.render(
        "local_datacurso/add_activity_ai_modal",
        {}
      );

      const title = await Str.get_string(
        "addactivityai_modaltitle",
        "local_datacurso"
      );

      modal = await Modal.create({
        title,
        body: bodyHTML,
        large: true,
        scrollable: true,
        removeOnClose: true,
      });

      // Align width/appearance with course AI modal.
      modal.getRoot().addClass('local_datacurso_course_ai_modal');

      // Explicitly show after creation (no show: true in options).
      modal.show();

      // Manejar el evento de cierre del modal
      modal.getRoot().on("hidden.bs.modal", () => {
        if (modal) {
          modal.destroy();
          modal = null;
        }
      });
      // Handlers del chat
      const bodyEl = modal.getBody()[0];
      wireChatHandlers(bodyEl, payload);
    } catch (err) {
      Notification.exception(err);
    }
  };

  const wireChatHandlers = (container, payload) => {
    const messagesEl = container.querySelector(".local_datacurso_ai_messages");
    const form = container.querySelector("form.local_datacurso_ai_input");
    const textarea = form.querySelector("textarea");
    const sendBtn = form.querySelector(".local_datacurso_ai_send");

    // Mensaje de bienvenida.
    Str.get_string("addactivityai_welcome", "local_datacurso").then((s) => {
      pushAI(messagesEl, s);
    });

    // Enviar con submit.
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const prompt = textarea.value.trim();
      if (!prompt) return;

      const generateImages = document.querySelector('input[name="generate_images"]:checked').value;

      pushUser(messagesEl, prompt);
      textarea.value = "";

      // Disable form elements
      sendBtn.disabled = true;
      textarea.disabled = true;
      const radioButtons = document.querySelectorAll('input[name="generate_images"]');
      radioButtons.forEach((rb) => {
        rb.disabled = true;
      });
      setLoading(sendBtn, true);

      try {
        // Start streaming job
        const response = await chatbotRepository.createModStream({
          ...payload,
          prompt,
          generateimages: generateImages
        });

        if (!response.ok) {
          throw new Error(response.message);
        }

        // Clear messages and show streaming interface
        messagesEl.innerHTML = "";
        
        // Create streaming container
        const streamingContainer = document.createElement("div");
        streamingContainer.innerHTML = `
          <div data-region="local_datacurso/module_streaming/progress" style="display: block;">
            <div class="d-flex align-items-center mb-3">
              <div data-region="local_datacurso/module_streaming/progress/icon" class="me-2">
                <div class="spinner-border spinner-border-sm text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
              </div>
              <div>
                <h6 class="mb-0">Creando módulo...</h6>
                <small class="text-muted">Por favor espera mientras se genera el contenido</small>
              </div>
            </div>
          </div>
          <div data-region="local_datacurso/module_streaming" class="border rounded p-3" style="max-height: 300px; overflow-y: auto;"></div>
        `;
        messagesEl.appendChild(streamingContainer);

        // Start module streaming
        await moduleStreaming.startModuleStreaming(
          response.streamingurl, 
          streamingContainer, 
          {
            courseid: payload.courseid,
            sectionnum: payload.sectionnum,
            beforemod: payload.beforemod,
            jobid: response.job_id
          }
        );

      } catch (err) {
        console.error("Error starting module creation:", err);
        
        // Show error message
        if (err && err.message) {
          pushAI(messagesEl, `❌ Error: ${err.message}`);
        } else {
          const errorMsg = await Str.get_string("addactivityai_error", "local_datacurso");
          pushAI(messagesEl, `❌ ${errorMsg}`);
        }
      } finally {
        // Re-enable form elements
        radioButtons.forEach((rb) => {
          rb.disabled = false;
        });
        textarea.disabled = false;
        sendBtn.disabled = false;
        setLoading(sendBtn, false);
        scrollToBottom(messagesEl);
      }
    });

    // Enter para enviar (sin Ctrl, ya que es más común en chat)
    textarea.addEventListener("keydown", (e) => {
      if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        form.requestSubmit();
      }
    });
  };

  const pushUser = (wrap, text) => addBubble(wrap, text, "user");
  const pushAI = (wrap, text) => addBubble(wrap, text, "ai");

  const addBubble = (wrap, text, role) => {
    const row = document.createElement("div");
    row.className = `local_datacurso_ai_msg ${role}`;
    const b = document.createElement("div");
    b.className = "bubble";
    b.textContent = text;
    row.appendChild(b);
    wrap.appendChild(row);
    scrollToBottom(wrap);
  };

  const pushTyping = (wrap) => {
    const row = document.createElement("div");
    row.className = "local_datacurso_ai_msg ai local_datacurso_ai_typing";
    const b = document.createElement("div");
    b.className = "bubble";
    b.innerHTML =
      '<span class="dot"></span><span class="dot"></span><span class="dot"></span>';
    row.appendChild(b);
    wrap.appendChild(row);
    scrollToBottom(wrap);
    return row;
  };

  const removeTyping = (el) => el?.remove();

  const scrollToBottom = (wrap) => {
    wrap.scrollTop = wrap.scrollHeight;
  };

  const setLoading = (btn, isLoading) => {
    btn.disabled = isLoading;
    btn.style.opacity = isLoading ? 0.7 : 1;
  };

  const renderWSResult = (wrap, res) => {
    const lines = [];
    if (res?.success === false) {
      if (res?.message) {
        pushAI(wrap, res.message);
      } else {
        Str.get_string("addactivityai_faildefault", "local_datacurso").then(
          (s) => pushAI(wrap, s)
        );
      }
      return;
    }

    if (res?.message) lines.push(res.message);

    if (lines.length) {
      pushAI(wrap, lines.join("\n"));
      setTimeout(() => {
        const last = wrap.querySelector(".local_datacurso_ai_msg.ai:last-child .bubble");
        if (last) last.textContent = lines.join("\n");
      }, 50);
    } else {
      Str.get_string("addactivityai_done", "local_datacurso").then((s) =>
        pushAI(wrap, s)
      );
    }
  };

  return { init, openChatModal };
});
