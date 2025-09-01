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
 * Floating chat for AI assistant in course contexts
 *
 * @module     local_datacurso/chat
 * @copyright  2025 Datacurso <josue@datacurso.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/notification'], function (notification) {
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

            this.init();
        }

        /**
         * Inicializa el chat
         */
        init() {
            // Verificar si estamos en contexto de curso
            if (!this.checkCourseContext()) {
                return;
            }

            // Detectar rol del usuario
            this.detectUserRole();

            // Crear el widget del chat
            this.createChatWidget();

            // Agregar event listeners
            this.addEventListeners();
        }

        /**
         * Verifica si estamos en contexto de curso
         */
        checkCourseContext() {
            // Primero verificar si PHP ya confirmó que estamos en contexto de curso
            if (window.datacurso_chat_config && window.datacurso_chat_config.courseid > 0) {
                this.courseId = window.datacurso_chat_config.courseid;
                this.isInCourseContext = true;
                return true;
            }

            // Verificar URL para contexto de curso
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

            // Verificar si hay elementos específicos de curso en la página
            const courseContent = document.querySelector('#page-course-view') ||
                document.querySelector('.course-content') ||
                document.querySelector('[data-region="course-content"]') ||
                document.querySelector('body.path-course') ||
                document.querySelector('body.path-mod');

            if (courseContent) {
                // Intentar obtener course ID del DOM
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
            // Primero intentar usar los datos pasados desde PHP
            if (window.datacurso_chat_config && window.datacurso_chat_config.userrole) {
                this.userRole = window.datacurso_chat_config.userrole;
                return;
            }

            // Verificar si hay elementos que indiquen que es profesor
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

            // Verificar en el menú de usuario o navegación
            const userMenu = document.querySelector('.usermenu') || document.querySelector('.user-menu');
            if (userMenu && userMenu.textContent.toLowerCase().includes('profesor')) {
                this.userRole = 'Profesor';
                return;
            }

            // Verificar permisos de edición
            if (document.querySelector('a[href*="edit=on"]') ||
                document.querySelector('.turn-editing-on') ||
                document.querySelector('.editing-on')) {
                this.userRole = 'Profesor';
                return;
            }

            // Por defecto, asumir que es estudiante
            this.userRole = 'Estudiante';
        }

        /**
         * Crea el widget del chat
         */
        createChatWidget() {
            // Crear el HTML del chat
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

            // Crear elemento y agregarlo al DOM
            const chatContainer = document.createElement('div');
            chatContainer.innerHTML = chatHTML;
            this.chatWidget = chatContainer.firstElementChild;

            // APLICAR ESTADO INICIAL SEGÚN this.isMinimized
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

            // Agregar animación de entrada
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

            // Toggle chat
            header.addEventListener('click', () => this.toggleChat());

            // Send message
            sendBtn.addEventListener('click', () => this.sendMessage());

            // Enter key to send
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });

            // Auto-resize textarea
            input.addEventListener('input', () => {
                input.style.height = 'auto';
                input.style.height = Math.min(input.scrollHeight, 100) + 'px';
            });

            // Prevent chat from interfering with page interactions
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
         * Envía un mensaje
         */
        sendMessage() {
            const input = this.chatWidget.querySelector('#chatInput');
            const sendBtn = this.chatWidget.querySelector('#sendBtn');

            const messageText = input.value.trim();
            if (!messageText) {
                return;
            }

            // Deshabilitar botón de envío
            sendBtn.disabled = true;

            // Agregar mensaje del usuario
            this.addMessage(messageText, 'user');

            // Limpiar input
            input.value = '';
            input.style.height = 'auto';

            // Scroll al final
            this.scrollToBottom();

            // Aquí es donde se integraría la lógica de IA
            // Por ahora, simular una respuesta
            this.simulateAIResponse();

            // Rehabilitar botón de envío
            setTimeout(() => {
                sendBtn.disabled = false;
            }, 1000);
        }

        /**
         * Agrega un mensaje al chat
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
         * Simula una respuesta de IA (placeholder para la lógica real)
         */
        simulateAIResponse() {
            // Mostrar indicador de escritura
            this.showTypingIndicator();

            setTimeout(() => {
                this.hideTypingIndicator();

                const responses = [
                    'Gracias por tu mensaje. Aquí es donde se integraría la lógica de IA.',
                    'Entiendo tu consulta. ¿Podrías proporcionar más detalles?',
                    'Estoy aquí para ayudarte con tus dudas sobre el curso.',
                    `Como ${this.userRole.toLowerCase()}, tienes acceso a funciones específicas. ¿En qué puedo asistirte?`
                ];

                const randomResponse = responses[Math.floor(Math.random() * responses.length)];
                this.addMessage(randomResponse, 'ai');
            }, 1500);
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
            // Asegurar que solo hay una instancia
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
