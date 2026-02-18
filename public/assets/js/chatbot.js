/**
 * CHATBOT TUPA PROFESIONAL - JAVASCRIPT
 * Manejo de interacciones y comunicación con el backend
 */

class TupaChatbot {
    constructor() {
        this.baseUrl = window.location.origin;
        this.chatContainer = null;
        this.toggleBtn = null;
        this.messagesContainer = null;
        this.input = null;
        this.sendBtn = null;
        this.isOpen = false;
        this.isTyping = false;

        this.init();
    }

    init() {
        // Cargar estado guardado
        this.isOpen = localStorage.getItem('chatbot_open') === 'true';

        // Esperar a que el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }

    setup() {
        // Obtener elementos
        this.chatContainer = document.getElementById('chatbot-container');
        this.toggleBtn = document.getElementById('chatbot-toggle-btn');
        this.messagesContainer = document.getElementById('chatbot-messages');
        this.input = document.getElementById('chatbot-input');
        this.sendBtn = document.getElementById('chatbot-send-btn');

        if (!this.chatContainer || !this.toggleBtn) {
            console.error('Elementos del chatbot no encontrados');
            return;
        }

        // Configurar eventos
        this.toggleBtn.addEventListener('click', () => this.toggle());

        const closeBtn = document.querySelector('.chatbot-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.close());
        }

        if (this.sendBtn) {
            this.sendBtn.addEventListener('click', () => this.sendMessage());
        }

        if (this.input) {
            this.input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });

            // Deshabilitar botón cuando input está vacío, auto-scroll y auto-expandir textarea
            this.input.addEventListener('input', () => {
                this.sendBtn.disabled = !this.input.value.trim();

                // Auto-expandir el textarea dinámicamente
                this.input.style.height = 'auto';
                this.input.style.height = (this.input.scrollHeight) + 'px';

                // Deslizar hacia abajo mientras escribe para mantener el contexto
                this.scrollToBottom();
            });
        }

        // Mostrar mensaje de bienvenida
        this.showWelcomeMessage();

        // Restaurar estado
        if (this.isOpen) {
            this.open();
        }
    }

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        this.chatContainer.classList.add('show');
        this.toggleBtn.classList.add('active');
        this.isOpen = true;
        localStorage.setItem('chatbot_open', 'true');

        // Focus en el input
        setTimeout(() => {
            if (this.input) this.input.focus();
        }, 300);

        // Scroll al final
        this.scrollToBottom();
    }

    close() {
        this.chatContainer.classList.remove('show');
        this.toggleBtn.classList.remove('active');
        this.isOpen = false;
        localStorage.setItem('chatbot_open', 'false');
    }

    showWelcomeMessage() {
        const logoUrl = window.location.origin + window.location.pathname.replace(/\/(public\/)?$/, '') + '/logo_jlo.PNG';
        const welcomeHTML = `
            <div class="message bot welcome-message">
                <div class="message-avatar"><img src="${logoUrl}" alt="JLO" style="width: 100%; height: 100%; object-fit: contain; border-radius: 50%;"></div>
                <div class="message-bubble">
                    <strong>¡Hola! Soy tu Asistente Virtual</strong> 👋<br><br>
                    Estoy aquí para ayudarte con consultas sobre trámites del TUPA de la Municipalidad.<br><br>
                    Puedo ayudarte con información sobre:
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li>📋 Requisitos de trámites</li>
                        <li>💰 Costos y tarifas</li>
                        <li>⏱️ Plazos de atención</li>
                        <li>📍 Dónde presentar documentos</li>
                    </ul>
                    <em>¿Qué trámite necesitas consultar?</em>
                </div>
            </div>
        `;

        if (this.messagesContainer && this.messagesContainer.children.length === 0) {
            this.messagesContainer.innerHTML = welcomeHTML;
        }
    }

    async sendMessage() {
        const message = this.input.value.trim();

        if (!message || this.isTyping) return;

        // Agregar mensaje del usuario
        this.addMessage(message, 'user');

        // Limpiar input y resetear tamaño
        this.input.value = '';
        this.input.style.height = 'auto';
        this.sendBtn.disabled = true;

        // Mostrar indicador de escritura
        this.showTypingIndicator();

        try {
            // Enviar al backend - usar ruta relativa para que funcione con CodeIgniter
            const apiUrl = window.location.origin + window.location.pathname.replace(/\/(public\/)?$/, '') + '/bot/consultar';

            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `mensaje=${encodeURIComponent(message)}`
            });

            const data = await response.json();

            // Remover indicador de escritura
            this.hideTypingIndicator();

            // Agregar respuesta del bot
            if (data.respuesta) {
                this.addMessage(data.respuesta, 'bot');
            }

        } catch (error) {
            console.error('Error al enviar mensaje:', error);
            this.hideTypingIndicator();
            this.addMessage('⚠️ Error de conexión. Por favor intenta nuevamente.', 'bot');
        }
    }

    addMessage(text, sender = 'bot') {
        const logoUrl = window.location.origin + window.location.pathname.replace(/\/(public\/)?$/, '') + '/logo_jlo.PNG';
        const avatarContent = sender === 'bot'
            ? `<img src="${logoUrl}" alt="JLO" style="width: 100%; height: 100%; object-fit: contain; border-radius: 50%;">`
            : '👤';

        const messageHTML = `
            <div class="message ${sender}">
                <div class="message-avatar">${avatarContent}</div>
                <div class="message-bubble">${text}</div>
            </div>
        `;

        this.messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
        this.scrollToBottom();
    }

    showTypingIndicator() {
        this.isTyping = true;
        const logoUrl = window.location.origin + window.location.pathname.replace(/\/(public\/)?$/, '') + '/logo_jlo.PNG';
        const typingHTML = `
            <div class="message bot typing-message">
                <div class="message-avatar"><img src="${logoUrl}" alt="JLO" style="width: 100%; height: 100%; object-fit: contain; border-radius: 50%;"></div>
                <div class="typing-indicator">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>
        `;
        this.messagesContainer.insertAdjacentHTML('beforeend', typingHTML);
        this.scrollToBottom();
    }

    hideTypingIndicator() {
        this.isTyping = false;
        const typingMessage = this.messagesContainer.querySelector('.typing-message');
        if (typingMessage) {
            typingMessage.remove();
        }
    }

    scrollToBottom() {
        // Ejecutar inmediatamente y con un pequeño delay para asegurar que el DOM se renderizó
        const scroll = () => {
            if (this.messagesContainer) {
                this.messagesContainer.scrollTo({
                    top: this.messagesContainer.scrollHeight,
                    behavior: 'smooth'
                });
            }
        };

        scroll();
        setTimeout(scroll, 50);
        setTimeout(scroll, 200); // Fail-safe para contenido lento
    }
}

// Inicializar chatbot cuando el DOM esté listo
let chatbot;
const initChatbot = () => {
    chatbot = new TupaChatbot();

    // Función global para soportar los clics en botones generados por el bot
    // Se expone al objeto window para asegurarse de que sea accesible globalmente
    window.enviarMensaje = function (mensaje) {
        if (chatbot && chatbot.input) {
            chatbot.input.value = mensaje;
            chatbot.sendBtn.disabled = false;
            chatbot.sendMessage();
        }
    };
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initChatbot);
} else {
    initChatbot();
}
