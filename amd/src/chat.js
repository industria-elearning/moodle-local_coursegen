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
 * Floating chat for AI assistant in course contexts (con SSE)
 *
 * @module     local_datacurso/chat
 * @copyright  2025 Datacurso
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/ajax', 'core/notification'], function (Ajax, notification) {
    'use strict';

    class DatacursoChat {
        /**
         * Constructor for DatacursoChat class.
         * Initializes chat widget state and starts initialization.
         */
        constructor() {
            this.chatWidget = null;
            this.isMinimized = true;
            this.userRole = 'Estudiante';
            this.courseId = null;
            this.isInCourseContext = false;

            // Estado SSE
            this.currentEventSource = null;
            this.currentAIMessageEl = null;
            this.streaming = false;
            this.currentSessionId = null;

            // Cleanup session on page unload
            window.addEventListener('beforeunload', () => this.cleanupSession());

            this.init();
        }

        /**
         * Initializes the chat widget if in course context.
         */
        init() {
            try {
                if (!this.checkCourseContext()) return;
                this.detectUserRole();
                this.createChatWidget();
                this.addEventListeners();
            } catch (error) {
                if (window.console) console.error('Error initializing chat:', error);
            }
        }

        /**
         * Checks if the current page is in a course context.
         * @returns {boolean}
         */
        checkCourseContext() {
            try {
                if (window.datacurso_chat_config && window.datacurso_chat_config.courseid > 0) {
                    this.courseId = parseInt(window.datacurso_chat_config.courseid, 10);
                    this.isInCourseContext = true;
                    return true;
                }

                const url = window.location.href;
                const courseMatch = url.match(/course\/view\.php\?id=(\d+)/);
                const modMatch = url.match(/mod\/\w+\/view\.php.*course=(\d+)/);
                const activityMatch = url.match(/course\/modedit\.php.*course=(\d+)/);

                if (courseMatch) {
                    this.courseId = parseInt(courseMatch[1], 10);
                    this.isInCourseContext = true;
                    return true;
                } else if (modMatch) {
                    this.courseId = parseInt(modMatch[1], 10);
                    this.isInCourseContext = true;
                    return true;
                } else if (activityMatch) {
                    this.courseId = parseInt(activityMatch[1], 10);
                    this.isInCourseContext = true;
                    return true;
                }

                const courseContent = document.querySelector('#page-course-view') ||
                    document.querySelector('.course-content') ||
                    document.querySelector('[data-region="course-content"]') ||
                    document.querySelector('body.path-course') ||
                    document.querySelector('body.path-mod');

                if (courseContent) {
                    const courseIdElement = document.querySelector('[data-courseid]');
                    if (courseIdElement) {
                        const courseIdValue = courseIdElement.getAttribute('data-courseid');
                        if (courseIdValue && !isNaN(courseIdValue)) this.courseId = parseInt(courseIdValue, 10);
                    }
                    this.isInCourseContext = true;
                    return true;
                }

                return false;
            } catch (error) {
                if (window.console) console.warn('Error checking course context:', error);
                return false;
            }
        }

        /**
         * Detects the user's role (Teacher or Student).
         */
        detectUserRole() {
            try {
                if (window.datacurso_chat_config && window.datacurso_chat_config.userrole) {
                    const role = window.datacurso_chat_config.userrole;
                    if (typeof role === 'string' && role.trim()) {
                        this.userRole = role.trim();
                        return;
                    }
                }

                const teacherElements = [
                    '.editing',
                    '[data-role="teacher"]',
                    '.teacher-view',
                    '.course-editing',
                    'body.editing'
                ];

                for (const selector of teacherElements) {
                    try {
                        if (document.querySelector(selector)) {
                            this.userRole = 'Profesor';
                            return;
                        }
                    } catch (_) {}
                }

                const userMenu = document.querySelector('.usermenu') || document.querySelector('.user-menu');
                if (userMenu && userMenu.textContent && userMenu.textContent.toLowerCase().includes('profesor')) {
                    this.userRole = 'Profesor';
                    return;
                }

                if (document.querySelector('a[href*="edit=on"]') ||
                    document.querySelector('.turn-editing-on') ||
                    document.querySelector('.editing-on')) {
                    this.userRole = 'Profesor';
                    return;
                }

                this.userRole = 'Estudiante';
            } catch (error) {
                if (window.console) console.warn('Error detecting user role:', error);
                this.userRole = 'Estudiante';
            }
        }

        /**
         * Creates the chat widget and appends it to the DOM.
         */
        createChatWidget() {
            const chatHTML = `
                <div class="datacurso-chat-widget" id="datacursoChat">
                    <div class="datacurso-chat-header" id="chatHeader">
                        <div class="datacurso-chat-header-content">
                            <h3>Asistente IA</h3>
                            <span class="datacurso-chat-role" id="userRole">${this.userRole}</span>
                        </div>
                        <button class="datacurso-chat-toggle" id="toggleBtn" aria-label="Minimizar/Maximizar chat">-</button>
                    </div>

                    <div class="datacurso-chat-body" id="chatBody">
                        <div class="datacurso-chat-messages" id="chatMessages">
                            <div class="datacurso-chat-message ai">
                                ¡Hola! Soy tu asistente de IA. ¿En qué puedo ayudarte hoy?
                            </div>
                        </div>

                        <div class="datacurso-chat-input-container">
                            <div class="datacurso-chat-input-wrapper">
                                <textarea
                                    class="datacurso-chat-input"
                                    id="chatInput"
                                    placeholder="Escribe tu mensaje..."
                                    rows="1"
                                    aria-label="Mensaje para el asistente IA"></textarea>
                                <button id="sendBtn" class="datacurso-chat-send" aria-label="Enviar mensaje">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="22" y1="2" x2="11" y2="13"></line>
                                        <polygon points="22,2 15,22 11,13 2,9"></polygon>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="datacurso-chat-footer">
                        Powered by Datacurso IA
                    </div>
                </div>
            `;

            const chatContainer = document.createElement('div');
            chatContainer.innerHTML = chatHTML;
            this.chatWidget = chatContainer.firstElementChild;

            const body = this.chatWidget.querySelector('#chatBody');
            const toggleBtn = this.chatWidget.querySelector('#toggleBtn');

            if (this.isMinimized) {
                this.chatWidget.classList.add('minimized');
                body.style.display = 'none';
                toggleBtn.textContent = '+';
                toggleBtn.setAttribute('aria-label', 'Maximizar chat');
            } else {
                body.style.display = 'flex';
                toggleBtn.textContent = '-';
                toggleBtn.setAttribute('aria-label', 'Minimizar chat');
            }

            document.body.appendChild(this.chatWidget);

            requestAnimationFrame(() => {
                setTimeout(() => {
                    if (this.chatWidget) this.chatWidget.classList.add('show');
                }, 100);
            });
        }

        /**
         * Adds event listeners to chat widget elements.
         */
        addEventListeners() {
            const header = this.chatWidget.querySelector('#chatHeader');
            const sendBtn = this.chatWidget.querySelector('#sendBtn');
            const input = this.chatWidget.querySelector('#chatInput');

            header.addEventListener('click', () => this.toggleChat());
            sendBtn.addEventListener('click', () => this.sendMessage());

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });

            input.addEventListener('input', () => {
                input.style.height = 'auto';
                input.style.height = Math.min(input.scrollHeight, 100) + 'px';
            });

            this.chatWidget.addEventListener('click', (e) => e.stopPropagation());
        }

        /**
         * Toggles the chat widget between minimized and maximized states.
         */
        toggleChat() {
            const body = this.chatWidget.querySelector('#chatBody');
            const toggleBtn = this.chatWidget.querySelector('#toggleBtn');

            if (this.isMinimized) {
                this.chatWidget.classList.remove('minimized');
                body.style.display = 'flex';
                toggleBtn.textContent = '-';
                toggleBtn.setAttribute('aria-label', 'Minimizar chat');
                this.isMinimized = false;
            } else {
                this.chatWidget.classList.add('minimized');
                body.style.display = 'none';
                toggleBtn.textContent = '+';
                toggleBtn.setAttribute('aria-label', 'Maximizar chat');
                this.isMinimized = true;
            }
        }

        /**
         * Handles sending a message from the user to the AI assistant.
         */
        sendMessage() {
            const input = this.chatWidget.querySelector('#chatInput');
            const sendBtn = this.chatWidget.querySelector('#sendBtn');
            if (!input || !sendBtn) return;

            const messageText = input.value.trim();
            if (!messageText || this.streaming) return;

            if (messageText.length > 4000) {
                this.addMessage('[Error] El mensaje es demasiado largo. Máximo 4000 caracteres.', 'ai');
                return;
            }

            try {
                this._closeCurrentStream();
                sendBtn.disabled = true;

                this.addMessage(messageText, 'user');
                input.value = '';
                input.style.height = 'auto';
                this.scrollToBottom();
                this.showTypingIndicator();

                const courseId = window.courseid || this.courseId || 1;
                if (!courseId || isNaN(courseId)) throw new Error('Course ID inválido');

                const requests = Ajax.call([{
                    methodname: "local_datacurso_create_chat_message",
                    args: {
                        courseid: parseInt(courseId, 10),
                        message: this._sanitizeString(messageText.substring(0, 4000)),
                        meta: JSON.stringify({
                            user_role: this.userRole,
                            timestamp: Math.floor(Date.now() / 1000)
                        })
                    },
                }]);

                requests[0].then((data) => {
                    if (!data) throw new Error('Respuesta vacía del servidor');
                    const streamUrl = data.stream_url;
                    const sessionId = data.session_id;
                    if (!streamUrl) throw new Error('URL de stream ausente en la respuesta');

                    // Save session ID for cleanup
                    this.currentSessionId = sessionId;

                    this._startSSE(streamUrl, sessionId, sendBtn);
                }).catch((err) => {
                    this.hideTypingIndicator();
                    this.addMessage('[Error] No se pudo iniciar el stream: ' + (err.message || 'Error desconocido'), 'ai');
                    sendBtn.disabled = false;
                    if (window.console) console.error('Chat error:', err);
                    if (notification && notification.exception) notification.exception(err);
                });
            } catch (error) {
                this.hideTypingIndicator();
                this.addMessage('[Error] Error interno: ' + error.message, 'ai');
                sendBtn.disabled = false;
                if (window.console) console.error('Chat send error:', error);
            }
        }

        /**
         * Sanitizes a string by removing angle brackets.
         * @param {string} str
         * @returns {string}
         */
        _sanitizeString(str) {
            if (typeof str !== 'string') return '';
            return str.replace(/[<>]/g, '');
        }

        /**
         * Ensures there is a single AI message bubble, converting typing indicator if needed.
         * @returns {HTMLElement}
         */
        _ensureAIMessageEl() {
            if (this.currentAIMessageEl) return this.currentAIMessageEl;

            const messages = this.chatWidget.querySelector('#chatMessages');
            let el = messages.querySelector('#typingIndicator');
            if (el) {
                // Convertir el typing en globo AI definitivo
                el.id = ''; // ya no es indicador
                el.classList.remove('typing-indicator');
                el.className = 'datacurso-chat-message ai';
                el.innerHTML = '';
            } else {
                el = document.createElement('div');
                el.className = 'datacurso-chat-message ai';
                el.textContent = '';
                messages.appendChild(el);
            }
            this.currentAIMessageEl = el;
            return el;
        }

        /**
         * Starts SSE connection and handles incoming tokens for AI response.
         * @param {string} streamUrl
         * @param {string} sessionId
         * @param {HTMLElement} sendBtn
         */
        _startSSE(streamUrl, sessionId, sendBtn) {
            if (!streamUrl) {
                this._finalizeStream(sendBtn);
                this.addMessage('[Error] URL de stream inválida', 'ai');
                return;
            }

            const messages = this.chatWidget.querySelector('#chatMessages');
            if (!messages) {
                this._finalizeStream(sendBtn);
                return;
            }

            try {
                const es = new EventSource(streamUrl);
                this.currentEventSource = es;
                this.streaming = true;
                let firstToken = true;
                let connectionTimeout = setTimeout(() => {
                    if (this.streaming && firstToken) {
                        this._appendToAIMessage('[Timeout: El servidor tardó demasiado en responder]');
                        this._finalizeStream(sendBtn);
                    }
                }, 30000);

                es.addEventListener('open', () => {
                    if (window.console) console.log('SSE connection opened to Tutor-IA');
                });

                es.addEventListener('meta', () => {
                    // Metadata event - can be logged if needed
                });

                es.addEventListener('token', (ev) => {
                    try {
                        if (connectionTimeout) { clearTimeout(connectionTimeout); connectionTimeout = null; }
                        const payload = JSON.parse(ev.data);
                        // Support both formats: 't' and 'content'
                        const text = payload.t || payload.content || '';

                        if (firstToken) {
                            firstToken = false;
                            // Convert typing indicator to AI message bubble
                            this._ensureAIMessageEl();
                            this.hideTypingIndicator();
                        }
                        this._appendToAIMessage(text);
                    } catch (e) {
                        if (window.console) console.warn('Invalid token data:', ev.data);
                    }
                });

                es.addEventListener('message_completed', () => {
                    if (window.console) console.log('Message completed from Tutor-IA');
                    if (connectionTimeout) clearTimeout(connectionTimeout);
                    this._finalizeStream(sendBtn);
                });

                es.addEventListener('error', (event) => {
                    if (connectionTimeout) clearTimeout(connectionTimeout);
                    if (window.console) console.error('SSE error:', event);
                    if (!this.currentAIMessageEl || this.currentAIMessageEl.textContent.trim() === '') {
                        this._appendToAIMessage('[Error de conexión con el servidor]');
                    } else {
                        this._appendToAIMessage('\n[Conexión interrumpida]');
                    }
                    this._finalizeStream(sendBtn);
                });

                this.scrollToBottom();
            } catch (error) {
                if (window.console) console.error('Error starting SSE:', error);
                this.addMessage('[Error] No se pudo establecer conexión SSE', 'ai');
                this._finalizeStream(sendBtn);
            }
        }

        /**
         * Appends text to the current AI message bubble.
         * @param {string} text
         */
        _appendToAIMessage(text) {
            // Asegura que existe un único globo AI
            if (!this.currentAIMessageEl) this._ensureAIMessageEl();
            if (!this.currentAIMessageEl || typeof text !== 'string') return;

            const currentText = this.currentAIMessageEl.textContent || '';
            const maxLength = 10000;

            if (currentText.length + text.length > maxLength) {
                const remaining = maxLength - currentText.length;
                if (remaining > 0) this.currentAIMessageEl.textContent += text.substring(0, remaining) + '...';
                return;
            }

            this.currentAIMessageEl.textContent += text;
            this.scrollToBottom();
        }

        /**
         * Closes the current SSE stream and resets state.
         */
        _closeCurrentStream() {
            if (this.currentEventSource) {
                try { this.currentEventSource.close(); } catch (e) {
                    if (window.console) console.warn('Error closing EventSource:', e);
                }
            }
            this.currentEventSource = null;
            this.streaming = false;
            this.currentAIMessageEl = null;
            this.hideTypingIndicator();
        }

        /**
         * Finalizes the SSE stream and re-enables the send button.
         * @param {HTMLElement} sendBtn
         */
        _finalizeStream(sendBtn) {
            this._closeCurrentStream();
            if (sendBtn) sendBtn.disabled = false;
        }

        /**
         * Adds a message to the chat window.
         * @param {string} text
         * @param {string} type
         */
        addMessage(text, type) {
            if (!text || typeof text !== 'string') return;

            const messages = this.chatWidget.querySelector('#chatMessages');
            if (!messages) return;

            const messageElement = document.createElement('div');
            messageElement.className = `datacurso-chat-message ${type}`;

            const sanitizedText = text.replace(/</g, '&lt;').replace(/>/g, '&gt;');
            messageElement.textContent = sanitizedText.substring(0, 10000);

            messages.appendChild(messageElement);

            const maxMessages = 100;
            const nodes = messages.querySelectorAll('.datacurso-chat-message:not(.typing-indicator)');
            if (nodes.length > maxMessages) {
                for (let i = 0; i < nodes.length - maxMessages; i++) nodes[i].remove();
            }

            this.scrollToBottom();
        }

        /**
         * Shows the typing indicator in the chat window.
         */
        showTypingIndicator() {
            try {
                const messages = this.chatWidget && this.chatWidget.querySelector('#chatMessages');
                if (!messages) return;

                // No dupliques el indicador
                if (messages.querySelector('#typingIndicator')) return;

                const typingElement = document.createElement('div');
                typingElement.className = 'datacurso-chat-message ai typing-indicator';
                typingElement.id = 'typingIndicator';
                typingElement.innerHTML = '<span></span><span></span><span></span>';
                messages.appendChild(typingElement);
                this.scrollToBottom();
            } catch (error) {
                if (window.console) console.warn('Error showing typing indicator:', error);
            }
        }

        /**
         * Hides the typing indicator from the chat window.
         */
        hideTypingIndicator() {
            try {
                const typingIndicator = this.chatWidget && this.chatWidget.querySelector('#typingIndicator');
                if (typingIndicator) typingIndicator.remove();
            } catch (error) {
                if (window.console) console.warn('Error hiding typing indicator:', error);
            }
        }

        /**
         * Scrolls the chat messages container to the bottom.
         */
        scrollToBottom() {
            try {
                const messages = this.chatWidget && this.chatWidget.querySelector('#chatMessages');
                if (messages) {
                    requestAnimationFrame(() => { messages.scrollTop = messages.scrollHeight; });
                }
            } catch (error) {
                if (window.console) console.warn('Error scrolling to bottom:', error);
            }
        }

        /**
         * Cleanup the current chat session when user leaves the page.
         */
        cleanupSession() {
            if (!this.currentSessionId) {
                return;
            }

            // Use sendBeacon to ensure request is sent even if page is closing
            if (navigator.sendBeacon && window.M && window.M.cfg && window.M.cfg.wwwroot) {
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

        /**
         * Destroys the chat widget and closes any open streams.
         */
        destroy() {
            this.cleanupSession();
            this._closeCurrentStream();
            if (this.chatWidget) {
                this.chatWidget.remove();
                this.chatWidget = null;
            }
        }
    }

    let datacursoChatInstance = null;

    return {
        init: function () {
            if (datacursoChatInstance) datacursoChatInstance.destroy();
            try { datacursoChatInstance = new DatacursoChat(); }
            catch (error) { notification.exception(error); }
        },
        destroy: function () {
            if (datacursoChatInstance) {
                datacursoChatInstance.destroy();
                datacursoChatInstance = null;
            }
        }
    };
});
