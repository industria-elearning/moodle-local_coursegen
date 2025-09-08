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

    /**
     * Clase principal del chat flotante
     */
    class DatacursoChat {
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

            this.init();
        }

        /**
         * Inicializa el chat
         */
        init() {
            try {
                if (!this.checkCourseContext()) {
                    return;
                }
                this.detectUserRole();
                this.createChatWidget();
                this.addEventListeners();
            } catch (error) {
                window.console && window.console.error('Error initializing chat:', error);
            }
        }

        /**
         * Verifica si estamos en contexto de curso
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
                        if (courseIdValue && !isNaN(courseIdValue)) {
                            this.courseId = parseInt(courseIdValue, 10);
                        }
                    }
                    this.isInCourseContext = true;
                    return true;
                }

                return false;
            } catch (error) {
                window.console && window.console.warn('Error checking course context:', error);
                return false;
            }
        }

        /**
         * Detecta el rol del usuario en el contexto del curso
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
                    } catch (e) {
                        
                    }
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
                window.console && window.console.warn('Error detecting user role:', error);
                this.userRole = 'Estudiante';
            }
        }

        /**
         * Crea el widget del chat
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
                    if (this.chatWidget) {
                        this.chatWidget.classList.add('show');
                    }
                }, 100);
            });
        }

        /**
         * Agrega event listeners
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

            this.chatWidget.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        }

        /**
         * Alterna entre minimizado y maximizado
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
         * Envía un mensaje y abre SSE
         */
        sendMessage() {
            const input = this.chatWidget.querySelector('#chatInput');
            const sendBtn = this.chatWidget.querySelector('#sendBtn');

            if (!input || !sendBtn) {
                window.console && window.console.error('Chat input or send button not found');
                return;
            }

            const messageText = input.value.trim();
            if (!messageText || this.streaming) {
                return;
            }

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

                if (!courseId || isNaN(courseId)) {
                    throw new Error('Course ID inválido');
                }

                const requests = Ajax.call([{
                    methodname: "local_datacurso_create_chat_message",
                    args: {
                        courseid: parseInt(courseId, 10),
                        lang: this._sanitizeString("es"),
                        message: this._sanitizeString(messageText.substring(0, 4000)),
                    },
                }]);

                requests[0].then((data) => {
                    if (!data) {
                        throw new Error('Respuesta vacía del servidor');
                    }
                    const streamUrl = data.stream_url || data.streamurl;
                    const sessionId = data.session_id || data.sessionId;
                    if (!streamUrl) {
                        throw new Error('URL de stream ausente en la respuesta');
                    }
                    this._startSSE(streamUrl, sessionId, sendBtn);
                }).catch((err) => {
                    this.hideTypingIndicator();
                    this.addMessage('[Error] No se pudo iniciar el stream: ' + (err.message || 'Error desconocido'), 'ai');
                    sendBtn.disabled = false;
                    window.console && window.console.error('Chat error:', err);
                    if (notification && notification.exception) {
                        notification.exception(err);
                    }
                });
            } catch (error) {
                this.hideTypingIndicator();
                this.addMessage('[Error] Error interno: ' + error.message, 'ai');
                sendBtn.disabled = false;
                window.console && window.console.error('Chat send error:', error);
            }
        }

        _sanitizeString(str) {
            if (typeof str !== 'string') {
                return '';
            }
            return str.replace(/[<>]/g, '');
        }

        /**
         * Abre EventSource, consume tokens y renderiza un mensaje AI en vivo
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
                const aiEl = document.createElement('div');
                aiEl.className = 'datacurso-chat-message ai';
                aiEl.textContent = '';
                messages.appendChild(aiEl);
                this.currentAIMessageEl = aiEl;

                const es = new EventSource(streamUrl);
                this.currentEventSource = es;
                this.streaming = true;
                let firstToken = true;
                let connectionTimeout = null;

                connectionTimeout = setTimeout(() => {
                    if (this.streaming && firstToken) {
                        this._appendToAIMessage('[Timeout: El servidor tardó demasiado en responder]');
                        this._finalizeStream(sendBtn);
                    }
                }, 30000);

                es.addEventListener('open', () => {
                    window.console && window.console.log('SSE connection opened');
                });

                es.addEventListener('meta', () => {
                });

                es.addEventListener('token', (ev) => {
                    try {
                        if (connectionTimeout) {
                            clearTimeout(connectionTimeout);
                            connectionTimeout = null;
                        }
                        const payload = JSON.parse(ev.data);
                        const t = payload.t || '';
                        if (firstToken) {
                            firstToken = false;
                            this.hideTypingIndicator();
                        }
                        this._appendToAIMessage(t);
                    } catch (e) {
                        window.console && window.console.warn('Invalid token data:', ev.data);
                    }
                });

                es.addEventListener('message_completed', () => {
                    if (connectionTimeout) {
                        clearTimeout(connectionTimeout);
                    }
                    this._finalizeStream(sendBtn);
                });

                es.addEventListener('error', (event) => {
                    if (connectionTimeout) {
                        clearTimeout(connectionTimeout);
                    }
                    window.console && window.console.error('SSE error:', event);
                    if (this.currentAIMessageEl && this.currentAIMessageEl.textContent.trim() === '') {
                        this._appendToAIMessage('[Error de conexión con el servidor]');
                    } else {
                        this._appendToAIMessage('\n[Conexión interrumpida]');
                    }
                    this._finalizeStream(sendBtn);
                });

                this.scrollToBottom();
            } catch (error) {
                window.console && window.console.error('Error starting SSE:', error);
                this.addMessage('[Error] No se pudo establecer conexión SSE', 'ai');
                this._finalizeStream(sendBtn);
            }
        }

        /**
         * Añade texto al mensaje AI actual
         * @param {string} text
         */
        _appendToAIMessage(text) {
            if (!this.currentAIMessageEl || typeof text !== 'string') {
                return;
            }
            
            const currentText = this.currentAIMessageEl.textContent || '';
            const maxLength = 10000;
            
            if (currentText.length + text.length > maxLength) {
                const remainingSpace = maxLength - currentText.length;
                if (remainingSpace > 0) {
                    this.currentAIMessageEl.textContent += text.substring(0, remainingSpace) + '...';
                }
                return;
            }
            
            this.currentAIMessageEl.textContent += text;
            this.scrollToBottom();
        }

        /**
         * Cierra y limpia el stream actual
         */
        _closeCurrentStream() {
            if (this.currentEventSource) {
                try {
                    this.currentEventSource.close();
                } catch (e) {
                    window.console && window.console.warn('Error closing EventSource:', e);
                }
            }
            this.currentEventSource = null;
            this.streaming = false;
            this.currentAIMessageEl = null;
            this.hideTypingIndicator();
        }

        /**
         * Finaliza flujo SSE: cierra ES y habilita UI
         * @param {HTMLElement} sendBtn
         */
        _finalizeStream(sendBtn) {
            this._closeCurrentStream();
            if (sendBtn) sendBtn.disabled = false;
        }

        /**
         * Agrega un mensaje al chat
         */
        addMessage(text, type) {
            if (!text || typeof text !== 'string') {
                return;
            }

            const messages = this.chatWidget.querySelector('#chatMessages');
            if (!messages) {
                return;
            }

            const messageElement = document.createElement('div');
            messageElement.className = `datacurso-chat-message ${type}`;
            
            const sanitizedText = text.replace(/</g, '&lt;').replace(/>/g, '&gt;');
            messageElement.textContent = sanitizedText.substring(0, 10000);
            
            messages.appendChild(messageElement);

            const maxMessages = 100;
            const messageElements = messages.querySelectorAll('.datacurso-chat-message:not(.typing-indicator)');
            if (messageElements.length > maxMessages) {
                for (let i = 0; i < messageElements.length - maxMessages; i++) {
                    messageElements[i].remove();
                }
            }

            this.scrollToBottom();
        }

        /**
         * Muestra indicador de escritura
         */
        showTypingIndicator() {
            try {
                const messages = this.chatWidget && this.chatWidget.querySelector('#chatMessages');
                if (!messages) {
                    return;
                }
                
                const existingIndicator = messages.querySelector('#typingIndicator');
                if (existingIndicator) {
                    return;
                }
                
                const typingElement = document.createElement('div');
                typingElement.className = 'datacurso-chat-message ai typing-indicator';
                typingElement.id = 'typingIndicator';
                typingElement.innerHTML = '<span></span><span></span><span></span>';
                messages.appendChild(typingElement);
                this.scrollToBottom();
            } catch (error) {
                window.console && window.console.warn('Error showing typing indicator:', error);
            }
        }

        /**
         * Oculta indicador de escritura
         */
        hideTypingIndicator() {
            try {
                const typingIndicator = this.chatWidget && this.chatWidget.querySelector('#typingIndicator');
                if (typingIndicator) {
                    typingIndicator.remove();
                }
            } catch (error) {
                window.console && window.console.warn('Error hiding typing indicator:', error);
            }
        }

        /**
         * Hace scroll al final de los mensajes
         */
        scrollToBottom() {
            try {
                const messages = this.chatWidget && this.chatWidget.querySelector('#chatMessages');
                if (messages) {
                    requestAnimationFrame(() => {
                        messages.scrollTop = messages.scrollHeight;
                    });
                }
            } catch (error) {
                window.console && window.console.warn('Error scrolling to bottom:', error);
            }
        }

        /**
         * Destruye el chat widget
         */
        destroy() {
            this._closeCurrentStream();
            if (this.chatWidget) {
                this.chatWidget.remove();
                this.chatWidget = null;
            }
        }
    }

    // Variable global para la instancia del chat
    let datacursoChatInstance = null;

    return {
        /**
         * Inicializa el chat flotante
         */
        init: function () {
            if (datacursoChatInstance) {
                datacursoChatInstance.destroy();
            }
            try {
                datacursoChatInstance = new DatacursoChat();
            } catch (error) {
                notification.exception(error);
            }
        },

        /**
         * Destruye el chat flotante
         */
        destroy: function () {
            if (datacursoChatInstance) {
                datacursoChatInstance.destroy();
                datacursoChatInstance = null;
            }
        }
    };
});
