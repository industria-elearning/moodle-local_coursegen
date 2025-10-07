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
 * Tutor-IA Chat - Drawer y funcionalidad del chat (basado en aiplacement_courseassist)
 *
 * @module     local_datacurso/tutor_ia_chat
 * @copyright  2025 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'jquery',
    'core/ajax',
    'core/notification',
    'core/pubsub'
], function(
    $,
    Ajax,
    Notification,
    PubSub
) {
    'use strict';

    const SELECTORS = {
        TOGGLE_BTN: '[data-action="tutor-ia-toggle"]',
        DRAWER: '.tutor-ia-drawer',
        CLOSE_BTN: '.tutor-ia-close-button',
        MESSAGES: '[data-region="tutor-ia-messages"]',
        INPUT: '[data-region="tutor-ia-input"]',
        SEND_BTN: '[data-action="send-message"]',
        PAGE: '#page',
        JUMP_TO: '#jump-to',
        BODY: 'body'
    };

    class TutorIAChat {
        constructor(root, uniqueId, courseId, userId) {
            this.root = $(root);
            this.uniqueId = uniqueId;
            this.courseId = courseId;
            this.userId = userId;
            this.streaming = false;
            this.currentEventSource = null;
            this.currentSessionId = null;
            this.currentAIMessageEl = null;

            this.drawerElement = document.querySelector(SELECTORS.DRAWER);
            this.pageElement = document.querySelector(SELECTORS.PAGE);
            this.bodyElement = document.querySelector(SELECTORS.BODY);
            this.toggleButton = document.querySelector(SELECTORS.TOGGLE_BTN);
            this.closeButton = document.querySelector(SELECTORS.CLOSE_BTN);
            this.jumpTo = document.querySelector(SELECTORS.JUMP_TO);

            // Detectar posición del drawer (right/left) desde data-position.
            this.position = this.drawerElement ? this.drawerElement.getAttribute('data-position') || 'right' : 'right';
            this.pageClass = this.position === 'left' ? 'show-drawer-left' : 'show-drawer-right';
            // Clase para identificar que el drawer del Tutor-IA está abierto (para mover footer-popover).
            this.bodyClass = this.position === 'left' ? 'tutor-ia-drawer-open-left' : 'tutor-ia-drawer-open-right';

            this.init();
        }

        init() {
            this.registerEventListeners();
            window.addEventListener('beforeunload', () => this.cleanup());
        }

        registerEventListeners() {
            // Toggle button
            if (this.toggleButton) {
                this.toggleButton.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.toggleDrawer();
                });
            }

            // Close button
            if (this.closeButton) {
                this.closeButton.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.closeDrawer();
                });
            }

            // Send button
            this.root.find(SELECTORS.SEND_BTN).on('click', () => {
                this.sendMessage();
            });

            // Input - Enter to send
            const input = this.root.find(SELECTORS.INPUT);
            input.on('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });

            // Auto-resize textarea
            input.on('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            });

            // Close on Escape key
            document.addEventListener('keydown', (e) => {
                if (this.isDrawerOpen() && e.key === 'Escape') {
                    this.closeDrawer();
                }
            });

            // Close drawer if message drawer opens
            PubSub.subscribe('core_message/drawer_shown', () => {
                if (this.isDrawerOpen()) {
                    this.closeDrawer();
                }
            });

            // Jump to functionality
            if (this.jumpTo) {
                this.jumpTo.addEventListener('focus', () => {
                    if (this.closeButton) {
                        this.closeButton.focus();
                    }
                });
            }
        }

        isDrawerOpen() {
            return this.drawerElement && this.drawerElement.classList.contains('show');
        }

        openDrawer() {
            if (!this.drawerElement) {
                return;
            }

            // Close message drawer if open
            PubSub.publish('core_message/hide', {});

            this.drawerElement.classList.add('show');
            this.drawerElement.setAttribute('tabindex', '0');

            // Add padding to page (redistribute space) - usa this.pageClass (show-drawer-left o show-drawer-right)
            if (this.pageElement && !this.pageElement.classList.contains(this.pageClass)) {
                this.pageElement.classList.add(this.pageClass);
            }

            // Add class to body to identify Tutor-IA drawer is open (for footer-popover positioning)
            if (this.bodyElement && !this.bodyElement.classList.contains(this.bodyClass)) {
                this.bodyElement.classList.add(this.bodyClass);
            }

            // Focus management
            if (this.jumpTo) {
                this.jumpTo.setAttribute('tabindex', 0);
                this.jumpTo.focus();
            }
        }

        closeDrawer() {
            if (!this.drawerElement) {
                return;
            }

            this.drawerElement.classList.remove('show');
            this.drawerElement.setAttribute('tabindex', '-1');

            // Remove padding from page - usa this.pageClass
            if (this.pageElement && this.pageElement.classList.contains(this.pageClass)) {
                this.pageElement.classList.remove(this.pageClass);
            }

            // Remove class from body
            if (this.bodyElement && this.bodyElement.classList.contains(this.bodyClass)) {
                this.bodyElement.classList.remove(this.bodyClass);
            }

            // Focus management
            if (this.jumpTo) {
                this.jumpTo.setAttribute('tabindex', -1);
            }
            if (this.toggleButton) {
                this.toggleButton.focus();
            }
        }

        toggleDrawer() {
            if (this.isDrawerOpen()) {
                this.closeDrawer();
            } else {
                this.openDrawer();
            }
        }

        sendMessage() {
            const input = this.root.find(SELECTORS.INPUT);
            const sendBtn = this.root.find(SELECTORS.SEND_BTN);

            const messageText = input.val().trim();
            if (!messageText || this.streaming) {
                return;
            }

            if (messageText.length > 4000) {
                this.addMessage('[Error] El mensaje es demasiado largo. Máximo 4000 caracteres.', 'ai');
                return;
            }

            try {
                this.closeCurrentStream();
                sendBtn.prop('disabled', true);

                this.addMessage(messageText, 'user');
                input.val('');
                input.css('height', 'auto');
                this.scrollToBottom();
                this.showTypingIndicator();

                const requests = Ajax.call([{
                    methodname: "local_datacurso_create_chat_message",
                    args: {
                        courseid: parseInt(this.courseId, 10),
                        message: this.sanitizeString(messageText.substring(0, 4000)),
                        meta: JSON.stringify({
                            user_role: 'Estudiante',
                            timestamp: Math.floor(Date.now() / 1000)
                        })
                    },
                }]);

                requests[0]
                    .then((data) => {
                        if (!data || !data.stream_url) {
                            throw new Error('URL de stream ausente en la respuesta');
                        }
                        this.currentSessionId = data.session_id;
                        this.startSSE(data.stream_url, sendBtn);
                        return data;
                    })
                    .catch((err) => {
                        this.hideTypingIndicator();
                        this.addMessage('[Error] ' + (err.message || 'Error desconocido'), 'ai');
                        sendBtn.prop('disabled', false);
                        Notification.exception(err);
                    });
            } catch (error) {
                this.hideTypingIndicator();
                this.addMessage('[Error] Error interno: ' + error.message, 'ai');
                sendBtn.prop('disabled', false);
            }
        }

        startSSE(streamUrl, sendBtn) {
            try {
                const es = new EventSource(streamUrl);
                this.currentEventSource = es;
                this.streaming = true;
                let firstToken = true;
                let messageCompleted = false; // Flag para saber si el mensaje se completó correctamente

                es.addEventListener('token', (ev) => {
                    try {
                        const payload = JSON.parse(ev.data);
                        const text = payload.t || payload.content || '';

                        if (firstToken) {
                            firstToken = false;
                            this.ensureAIMessageEl();
                            this.hideTypingIndicator();
                        }
                        this.appendToAIMessage(text);
                    } catch (e) {
                        window.console.warn('Invalid token data:', ev.data);
                    }
                });

                // Escuchar evento 'done' (el servidor envía 'done', no 'message_completed')
                es.addEventListener('done', () => {
                    messageCompleted = true; // Marcar que el mensaje se completó correctamente
                    this.finalizeStream(sendBtn);
                });

                // Mantener compatibilidad con 'message_completed' por si cambia el servidor
                es.addEventListener('message_completed', () => {
                    messageCompleted = true;
                    this.finalizeStream(sendBtn);
                });

                es.addEventListener('error', (e) => {
                    // Solo mostrar error si el mensaje NO se completó correctamente
                    if (!messageCompleted) {
                        window.console.error('SSE error:', e);
                        this.appendToAIMessage('\n[Conexión interrumpida]');
                        this.finalizeStream(sendBtn);
                    }
                    // Si messageCompleted=true, el error es esperado (cierre normal después de completar)
                });
            } catch (error) {
                window.console.error('Error starting SSE:', error);
                this.addMessage('[Error] No se pudo establecer conexión SSE', 'ai');
                this.finalizeStream(sendBtn);
            }
        }

        ensureAIMessageEl() {
            if (this.currentAIMessageEl) {
                return this.currentAIMessageEl;
            }

            const messages = this.root.find(SELECTORS.MESSAGES);
            let el = messages.find('.tutor-ia-typing');

            if (el.length) {
                el.removeClass('tutor-ia-typing');
                el.addClass('tutor-ia-message ai');
                el.html('');
            } else {
                el = $('<div class="tutor-ia-message ai"></div>');
                messages.append(el);
            }

            this.currentAIMessageEl = el[0];
            return this.currentAIMessageEl;
        }

        appendToAIMessage(text) {
            if (!this.currentAIMessageEl) {
                this.ensureAIMessageEl();
            }
            if (!this.currentAIMessageEl || typeof text !== 'string') {
                return;
            }

            const currentText = this.currentAIMessageEl.textContent || '';
            const maxLength = 10000;

            if (currentText.length + text.length > maxLength) {
                const remaining = maxLength - currentText.length;
                if (remaining > 0) {
                    this.currentAIMessageEl.textContent += text.substring(0, remaining) + '...';
                }
                return;
            }

            this.currentAIMessageEl.textContent += text;
            this.scrollToBottom();
        }

        addMessage(text, type) {
            if (!text || typeof text !== 'string') {
                return;
            }

            const messages = this.root.find(SELECTORS.MESSAGES);
            const messageEl = $('<div></div>')
                .addClass('tutor-ia-message')
                .addClass(type)
                .text(text.substring(0, 10000));

            messages.append(messageEl);
            this.scrollToBottom();
        }

        showTypingIndicator() {
            const messages = this.root.find(SELECTORS.MESSAGES);
            if (messages.find('.tutor-ia-typing').length) {
                return;
            }

            const typing = $('<div class="tutor-ia-message ai tutor-ia-typing"></div>')
                .html('<span class="dot"></span><span class="dot"></span><span class="dot"></span>');
            messages.append(typing);
            this.scrollToBottom();
        }

        hideTypingIndicator() {
            this.root.find('.tutor-ia-typing').remove();
        }

        scrollToBottom() {
            const messages = this.root.find(SELECTORS.MESSAGES);
            messages.scrollTop(messages[0].scrollHeight);
        }

        closeCurrentStream() {
            if (this.currentEventSource) {
                try {
                    this.currentEventSource.close();
                } catch (e) {
                    window.console.warn('Error closing EventSource:', e);
                }
            }
            this.currentEventSource = null;
            this.streaming = false;
            this.currentAIMessageEl = null;
            this.hideTypingIndicator();
        }

        finalizeStream(sendBtn) {
            this.closeCurrentStream();
            if (sendBtn) {
                sendBtn.prop('disabled', false);
            }
        }

        sanitizeString(str) {
            if (typeof str !== 'string') {
                return '';
            }
            return str.replace(/[<>]/g, '');
        }

        cleanup() {
            this.closeCurrentStream();
        }

        destroy() {
            this.cleanup();
        }
    }

    return {
        init: function(root, uniqueId, courseId, userId) {
            return new TutorIAChat(root, uniqueId, courseId, userId);
        }
    };
});
