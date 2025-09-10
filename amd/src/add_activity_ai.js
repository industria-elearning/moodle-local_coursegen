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
  'core/templates',
  'core/notification',
  'core/modal',
  'core/custom_interaction_events',
  'core/str',
  'local_datacurso/repository/chatbot'
], (Templates, Notification, Modal, CustomEvents, Str, chatbotRepository) => {

  const LINK_SELECTOR = '[data-action="local_datacurso/add_activity_ai"]';

  let modal = null;
  let initialized = false;

  const init = () => {
    if (initialized) {
      return;
    }
    initialized = true;

    const events = [
      'click',
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
    const { courseid, sectionid, sectionnum, beforemod } = el.dataset;
    return {
      courseid: Number(courseid),
      sectionid: Number(sectionid),
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

      const bodyHTML = await Templates.render('local_datacurso/add_activity_ai_modal', {});

      const title = await Str.get_string('addactivityai_modaltitle', 'local_datacurso');

      modal = await Modal.create({
        title,
        large: true,
        show: true,
        removeOnClose: true,
      });

      // Manejar el evento de cierre del modal
      modal.getRoot().on('hidden.bs.modal', () => {
        if (modal) {
          modal.destroy();
          modal = null;
        }
      });

      await modal.setBody(bodyHTML);
      // No llamar a show() nuevamente; el modal ya fue creado con show: true.

      // Handlers del chat
      const bodyEl = modal.getBody()[0];
      wireChatHandlers(bodyEl, payload);

    } catch (err) {
      Notification.exception(err);
    }
  };

  const wireChatHandlers = (container, payload) => {
    const messagesEl = container.querySelector('.bdai-messages');
    const form = container.querySelector('form.bdai-input');
    const textarea = form.querySelector('textarea');
    const sendBtn = form.querySelector('.bdai-send');

    // Mensaje de bienvenida.
    Str.get_string('addactivityai_welcome', 'local_datacurso').then((s) => {
      pushAI(messagesEl, s);
    });

    // Enviar con submit.
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const prompt = textarea.value.trim();
      if (!prompt) return;

      pushUser(messagesEl, prompt);
      textarea.value = '';
      textarea.focus();

      // Llamada al WS
      setLoading(sendBtn, true);
      const typing = pushTyping(messagesEl);
      try {
        const response = await chatbotRepository.createMod({ ...payload, prompt });
        removeTyping(typing);
        renderWSResult(messagesEl, response);
        
        // Cerrar modal antes de redirigir
        if (modal) {
          await modal.hide();
        }
        
        setTimeout(() => {
          window.location.href = response.courseurl;
        }, 500); // Pequeño delay para que se vea el mensaje de éxito
        
      } catch (err) {
        removeTyping(typing);
        Str.get_string('addactivityai_error', 'local_datacurso').then((s) => {
          pushAI(messagesEl, `❌ ${s}`);
        });
        Notification.exception(err);
      } finally {
        setLoading(sendBtn, false);
        scrollToBottom(messagesEl);
      }
    });

    // Enter para enviar (sin Ctrl, ya que es más común en chat)
    textarea.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        form.requestSubmit();
      }
    });
  };

  const pushUser = (wrap, text) => addBubble(wrap, text, 'user');
  const pushAI = (wrap, text) => addBubble(wrap, text, 'ai');

  const addBubble = (wrap, text, role) => {
    const row = document.createElement('div');
    row.className = `bdai-msg ${role}`;
    const b = document.createElement('div');
    b.className = 'bubble';
    b.textContent = text;
    row.appendChild(b);
    wrap.appendChild(row);
    scrollToBottom(wrap);
  };

  const pushTyping = (wrap) => {
    const row = document.createElement('div');
    row.className = 'bdai-msg ai bdai-typing';
    const b = document.createElement('div');
    b.className = 'bubble';
    b.innerHTML = '<span class="dot"></span><span class="dot"></span><span class="dot"></span>';
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
    btn.style.opacity = isLoading ? .7 : 1;
  };

  const renderWSResult = (wrap, res) => {
    const lines = [];
    if (res?.success === false) {
      if (res?.message) {
        pushAI(wrap, res.message);
      } else {
        Str.get_string('addactivityai_faildefault', 'local_datacurso').then((s) => pushAI(wrap, s));
      }
      return;
    }

    if (res?.message) lines.push(res.message);
    if (res?.name && res?.type) {
      Str.get_string('addactivityai_created_named', 'local_datacurso', {type: res.type, name: res.name})
        .then((s) => lines.push(`✅ ${s}`));
    } else if (res?.cmid) {
      Str.get_string('addactivityai_created_cmid', 'local_datacurso', res.cmid)
        .then((s) => lines.push(`✅ ${s}`));
    }
    if (res?.warnings?.length) {
      Str.get_string('addactivityai_warnings_prefix', 'local_datacurso', res.warnings.join(' '))
        .then((s) => lines.push(`⚠️ ${s}`));
    }

    if (res?.preview || res?.log) {
      const extras = document.createElement('div');
      extras.className = 'bdai-msg ai';
      const b = document.createElement('div');
      b.className = 'bubble';
      const pre = document.createElement('pre');
      pre.style.whiteSpace = 'pre-wrap';
      pre.textContent = (res.preview || res.log);
      b.appendChild(pre);
      extras.appendChild(b);
      wrap.appendChild(extras);
    }

    if (lines.length) {
      pushAI(wrap, lines.join('\n'));
      setTimeout(() => {
        const last = wrap.querySelector('.bdai-msg.ai:last-child .bubble');
        if (last) last.textContent = lines.join('\n');
      }, 50);
    } else {
      Str.get_string('addactivityai_done', 'local_datacurso').then((s) => pushAI(wrap, s));
    }
  };

  return { init };
});