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
            if (!this.checkCourseContext()) {
                return;
            }
            this.detectUserRole();
            this.createChatWidget();
            this.addEventListeners();
        }

        /**
         * Verifica si estamos en contexto de curso
         */
        checkCourseContext() {
            if (window.datacurso_chat_config && window.datacurso_chat_config.courseid > 0) {
                this.courseId = window.datacurso_chat_config.courseid;
                this.isInCourseContext = true;
                return true;
            }

            const url = window.location.href;
            const courseMatch = url.match(/course\/view\.php\?id=(\d+)/);
            const modMatch = url.match(/mod\/\w+\/view\.php.*course=(\d+)/);
            const activityMatch = url.match(/course\/modedit\.php.*course=(\d+)/);

            if (courseMatch) {
                this.courseId = courseMatch[1];
                this.isInCourseContext = true;
                return true;
            } else if (modMatch) {
                this.courseId = modMatch[1];
                this.isInCourseContext = true;
                return true;
            } else if (activityMatch) {
                this.courseId = activityMatch[1];
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
                    this.courseId = courseIdElement.getAttribute('data-courseid');
                }
                this.isInCourseContext = true;
                return true;
            }

            return false;
        }

        /**
         * Detecta el rol del usuario en el contexto del curso
         */
        detectUserRole() {
            if (window.datacurso_chat_config && window.datacurso_chat_config.userrole) {
                this.userRole = window.datacurso_chat_config.userrole;
                return;
            }

            const teacherElements = [
                '.editing',
                '[data-role="teacher"]',
                '.teacher-view',
                '.course-editing',
                'body.editing'
            ];

            for (const selector of teacherElements) {
                if (document.querySelector(selector)) {
                    this.userRole = 'Profesor';
                    return;
                }
            }

            const userMenu = document.querySelector('.usermenu') || document.querySelector('.user-menu');
            if (userMenu && userMenu.textContent.toLowerCase().includes('profesor')) {
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

            setTimeout(() => {
                this.chatWidget.classList.add('show');
            }, 100);
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

            input.addEventListener('keypress', (e) => {
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

            const messageText = input.value.trim();
            if (!messageText || this.streaming) {
                return;
            }

            // Cierra stream previo si existiera
            this._closeCurrentStream();

            // Deshabilitar botón de envío hasta finalizar el stream
            sendBtn.disabled = true;

            // Mensaje del usuario
            this.addMessage(messageText, 'user');

            // Limpiar input
            input.value = '';
            input.style.height = 'auto';
            this.scrollToBottom();
            this.showTypingIndicator();

            const courseId = window.courseid || this.courseId || 1;

            // Llamada WS Moodle -> devuelve { session_id, stream_url, expires_at }
            const requests = Ajax.call([{
                methodname: "local_datacurso_create_chat_message",
                args: {
                    courseid: Number(courseId),
                    lang: "es",
                    message: messageText,
                },
            }]);

            requests[0].then((data) => {
                const streamUrl = data.stream_url || data.streamurl;
                const sessionId = data.session_id || data.sessionId;
                if (!streamUrl) {
                    throw new Error('stream_url ausente');
                }
                this._startSSE(streamUrl, sessionId, sendBtn);
            }).catch((err) => {
                this.hideTypingIndicator();
                this.addMessage('[Error] No se pudo iniciar el stream.', 'ai');
                sendBtn.disabled = false;
                notification.exception(err);
            });
        }

        /**
         * Abre EventSource, consume tokens y renderiza un mensaje AI en vivo
         * @param {string} streamUrl
         * @param {string} sessionId
         * @param {HTMLElement} sendBtn
         */
        _startSSE(streamUrl, sessionId, sendBtn) {
            const messages = this.chatWidget.querySelector('#chatMessages');

            // Contenedor del mensaje de la IA que se irá completando
            const aiEl = document.createElement('div');
            aiEl.className = 'datacurso-chat-message ai';
            aiEl.textContent = ''; // comenzamos vacío
            messages.appendChild(aiEl);
            this.currentAIMessageEl = aiEl;

            // Abrir SSE
            const es = new EventSource(streamUrl);
            this.currentEventSource = es;
            this.streaming = true;
            let firstToken = true;

            es.addEventListener('meta', () => {
                // opcional: manejar metadatos
            });

            es.addEventListener('token', (ev) => {
                try {
                    const payload = JSON.parse(ev.data);
                    const t = payload.t || '';
                    if (firstToken) {
                        firstToken = false;
                        this.hideTypingIndicator();
                    }
                    this._appendToAIMessage(t);
                } catch (e) {
                    // ignora token malformado
                }
            });

            es.addEventListener('message_completed', () => {
                this._finalizeStream(sendBtn);
            });

            es.onerror = () => {
                this._appendToAIMessage('\n[Stream interrumpido]');
                this._finalizeStream(sendBtn);
            };

            this.scrollToBottom();
        }

        /**
         * Añade texto al mensaje AI actual
         * @param {string} text
         */
        _appendToAIMessage(text) {
            if (!this.currentAIMessageEl) {
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
                try { this.currentEventSource.close(); } catch (e) { /* no-op */ }
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
            if (sendBtn) {
                sendBtn.disabled = false;
            }
        }

        /**
         * Agrega un mensaje al chat
         * @param {string} text
         * @param {string} type 'user' o 'ai'
         */
        addMessage(text, type) {
            const messages = this.chatWidget.querySelector('#chatMessages');
            const messageElement = document.createElement('div');
            messageElement.className = `datacurso-chat-message ${type}`;
            messageElement.textContent = text;
            messages.appendChild(messageElement);
            this.scrollToBottom();
        }

        /**
         * Muestra indicador de escritura
         */
        showTypingIndicator() {
            const messages = this.chatWidget.querySelector('#chatMessages');
            const typingElement = document.createElement('div');
            typingElement.className = 'datacurso-chat-message ai typing-indicator';
            typingElement.id = 'typingIndicator';
            typingElement.innerHTML = '<span></span><span></span><span></span>';
            messages.appendChild(typingElement);
            this.scrollToBottom();
        }

        /**
         * Oculta indicador de escritura
         */
        hideTypingIndicator() {
            const typingIndicator = this.chatWidget.querySelector('#typingIndicator');
            if (typingIndicator) {
                typingIndicator.remove();
            }
        }

        /**
         * Hace scroll al final de los mensajes
         */
        scrollToBottom() {
            const messages = this.chatWidget.querySelector('#chatMessages');
            messages.scrollTop = messages.scrollHeight;
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
