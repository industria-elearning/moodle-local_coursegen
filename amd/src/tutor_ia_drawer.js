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
 * Tutor-IA Drawer - Drawer lateral para chat con IA
 *
 * @module     local_datacurso/tutor_ia_drawer
 * @copyright  2025 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'jquery',
    'core/ajax',
    'core/notification',
    'core/drawer',
    'core/pubsub',
    'core/custom_interaction_events',
    'core/modal_backdrop',
    'core/templates',
    'core/local/aria/focuslock'
], function(
    $,
    Ajax,
    Notification,
    Drawer,
    PubSub,
    CustomEvents,
    ModalBackdrop,
    Templates,
    FocusLock
) {
    'use strict';

    const SELECTORS = {
        DRAWER: '[data-region="right-hand-drawer"]',
        DRAWER_CONTENT: '[data-region="tutor-ia-drawer"]',
        MESSAGES: '[data-region="tutor-ia-messages"]',
        INPUT: '[data-region="tutor-ia-input"]',
        SEND_BTN: '[data-action="send-message"]',
        CLOSE_BTN: '[data-action="close-drawer"]',
        TRIGGER: '[data-action="toggle-tutor-ia-drawer"]',
        BADGE: '[data-region="tutor-ia-badge"]'
    };

    const EVENTS = {
        SHOW: 'local_datacurso/tutor_ia_drawer/show',
        HIDE: 'local_datacurso/tutor_ia_drawer/hide',
        TOGGLE: 'local_datacurso/tutor_ia_drawer/toggle'
    };

    class TutorIADrawer {
        constructor(root, uniqueId, courseId, userId) {
            this.root = $(root);
            this.uniqueId = uniqueId;
            this.courseId = courseId;
            this.userId = userId;
            this.drawerRoot = null;
            this.backdrop = null;
            this.streaming = false;
            this.currentEventSource = null;
            this.currentSessionId = null;
            this.currentAIMessageEl = null;

            this.init();
        }

        init() {
            this.drawerRoot = Drawer.getDrawerRoot(this.root);
            if (!this.drawerRoot.length) {
                window.console.error('Tutor-IA: No se encontró drawer root');
                return;
            }

            this.setupBackdrop();
            this.registerEventListeners();
            this.registerTrigger();

            window.addEventListener('beforeunload', () => this.cleanup());
        }

        setupBackdrop() {
            Templates.render('core/modal_backdrop', {})
                .then(html => {
                    this.backdrop = new ModalBackdrop(html);
                    const zIndex = window.getComputedStyle(this.drawerRoot[0]).zIndex;
                    if (zIndex) {
                        this.backdrop.setZIndex(zIndex - 1);
                    }
                    this.backdrop.getAttachmentPoint().get(0).addEventListener('click', () => {
                        this.hide();
                    });
                    return this.backdrop;
                })
                .catch(Notification.exception);
        }

        registerEventListeners() {
            // Close button
            this.root.find(SELECTORS.CLOSE_BTN).on('click', (e) => {
                e.preventDefault();
                this.hide();
            });

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

            // PubSub events
            PubSub.subscribe(EVENTS.SHOW, () => this.show());
            PubSub.subscribe(EVENTS.HIDE, () => this.hide());
            PubSub.subscribe(EVENTS.TOGGLE, () => this.toggle());

            // Close drawer if message drawer opens
            PubSub.subscribe('core_message/show', () => {
                if (Drawer.isVisible(this.drawerRoot)) {
                    this.hide();
                }
            });
        }

        registerTrigger() {
            const trigger = $(SELECTORS.TRIGGER);
            if (!trigger.length) {
                return;
            }

            CustomEvents.define(trigger, [CustomEvents.events.activate]);
            trigger.on(CustomEvents.events.activate, (e) => {
                e.preventDefault();
                this.toggle();
            });
        }

        show() {
            if (!this.drawerRoot.length) {
                return;
            }

            // Close message drawer if open
            PubSub.publish('core_message/hide', {});

            // Show backdrop
            if (this.backdrop) {
                this.backdrop.show();
                const pageWrapper = document.getElementById('page');
                if (pageWrapper) {
                    pageWrapper.style.overflow = 'hidden';
                }
            }

            // Show drawer
            Drawer.show(this.drawerRoot);

            // Trap focus
            FocusLock.trapFocus(this.root[0]);

            // Update trigger
            $(SELECTORS.TRIGGER).attr('aria-expanded', 'true');
            $('body').addClass('tutor-ia-drawer-open');

            // Focus close button
            this.root.find(SELECTORS.CLOSE_BTN).focus();
        }

        hide() {
            if (!this.drawerRoot.length) {
                return;
            }

            // Hide backdrop
            if (this.backdrop) {
                this.backdrop.hide();
                const pageWrapper = document.getElementById('page');
                if (pageWrapper) {
                    pageWrapper.style.overflow = 'visible';
                }
            }

            // Hide drawer
            Drawer.hide(this.drawerRoot);

            // Release focus
            FocusLock.untrapFocus();

            // Update trigger
            $(SELECTORS.TRIGGER).attr('aria-expanded', 'false').focus();
            $('body').removeClass('tutor-ia-drawer-open');
        }

        toggle() {
            if (Drawer.isVisible(this.drawerRoot)) {
                this.hide();
            } else {
                this.show();
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

                const connectionTimeout = setTimeout(() => {
                    if (this.streaming && firstToken) {
                        this.appendToAIMessage('[Timeout: El servidor tardó demasiado en responder]');
                        this.finalizeStream(sendBtn);
                    }
                }, 30000);

                es.addEventListener('open', () => {
                    window.console.log('SSE connection opened to Tutor-IA');
                });

                es.addEventListener('meta', () => {
                    // Metadata event - can be logged if needed
                });

                es.addEventListener('token', (ev) => {
                    try {
                        if (connectionTimeout) {
                            clearTimeout(connectionTimeout);
                        }
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

                es.addEventListener('message_completed', () => {
                    window.console.log('Message completed from Tutor-IA');
                    if (connectionTimeout) {
                        clearTimeout(connectionTimeout);
                    }
                    this.finalizeStream(sendBtn);
                });

                es.addEventListener('error', () => {
                    if (connectionTimeout) {
                        clearTimeout(connectionTimeout);
                    }
                    window.console.error('SSE error');
                    if (!this.currentAIMessageEl || this.currentAIMessageEl.textContent.trim() === '') {
                        this.appendToAIMessage('[Error de conexión con el servidor]');
                    } else {
                        this.appendToAIMessage('\n[Conexión interrumpida]');
                    }
                    this.finalizeStream(sendBtn);
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
            if (messages.length && messages[0]) {
                messages.scrollTop(messages[0].scrollHeight);
            }
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
            // Cleanup session if needed
            if (this.currentSessionId && navigator.sendBeacon && window.M && window.M.cfg && window.M.cfg.wwwroot) {
                const formData = new FormData();
                formData.append('sesskey', window.M.cfg.sesskey || '');
                formData.append('info', 'local_datacurso_delete_chat_session');

                const params = [{
                    index: 0,
                    methodname: 'local_datacurso_delete_chat_session',
                    args: {sessionid: this.currentSessionId}
                }];

                formData.append('args', JSON.stringify(params));
                navigator.sendBeacon(window.M.cfg.wwwroot + '/lib/ajax/service.php', formData);
            }
            this.currentSessionId = null;
        }

        destroy() {
            this.cleanup();
            FocusLock.untrapFocus();
            if (this.backdrop) {
                this.backdrop.hide();
            }
        }
    }

    return {
        init: function(root, uniqueId, courseId, userId) {
            return new TutorIADrawer(root, uniqueId, courseId, userId);
        }
    };
});
