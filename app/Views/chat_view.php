<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leonardito - Asistente TUPA | Municipalidad JLO</title>
    
    <!-- Favicon -->
    <link rel="icon" href="<?= base_url('favicon.ico') ?>">
    
    <!-- Chatbot Styles -->
    <link href="<?= base_url('assets/css/chatbot.css') ?>" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .main-content {
            text-align: center;
            color: #333;
            padding: 40px;
            max-width: 900px;
        }
        
        .logo-container {
            margin-bottom: 30px;
        }
        
        .logo-container img {
            max-width: 200px;
            height: auto;
        }
        
        .main-content h1 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 15px;
            color: #1E54B7;
        }
        
        .main-content p {
            font-size: 18px;
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .subtitle {
            font-size: 16px;
            color: #999;
            font-style: italic;
        }

        @media (max-width: 768px) {
            .logo-container img {
                max-width: 150px;
            }
            
            .main-content h1 {
                font-size: 28px;
            }
            
            .main-content p {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <!-- Contenido Principal -->
    <div class="main-content">
        <div class="logo-container">
            <img src="<?= base_url('logo_jlo.PNG') ?>" alt="Municipalidad JLO">
        </div>
        
        <h1>Municipalidad Distrital de José Leonardo Ortiz</h1>
        <p>Asistente Virtual TUPA</p>
        <p class="subtitle">Haz clic en el botón flotante abajo a la derecha para comenzar tu consulta</p>
    </div>

    <!-- Widget del Chatbot Flotante -->
    <div id="chatbot-container">
        <!-- Header -->
        <div class="chatbot-header">
            <div class="chatbot-header-content">
                <div class="chatbot-avatar">
                    <img src="<?= base_url('logo_jlo.PNG') ?>" alt="Municipalidad JLO" style="width: 90%; height: 90%; object-fit: contain;">
                </div>
                <div class="chatbot-info">
                    <h3>Asistente TUPA</h3>
                    <div class="chatbot-status">
                        <span class="status-dot"></span>
                        En línea
                    </div>
                </div>
            </div>
            <button class="chatbot-close" aria-label="Cerrar chat">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Área de mensajes -->
        <div class="chatbot-messages" id="chatbot-messages">
            <!-- Los mensajes se agregarán dinámicamente aquí -->
        </div>

        <!-- Input de mensaje -->
        <div class="chatbot-input-wrapper">
            <div class="chatbot-input-container">
                <textarea 
                    id="chatbot-input" 
                    placeholder="Escribe tu consulta sobre el TUPA..."
                    rows="1"
                ></textarea>
                <button id="chatbot-send-btn" disabled aria-label="Enviar mensaje">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Botón flotante para abrir/cerrar el chat -->
    <button id="chatbot-toggle-btn" aria-label="Abrir chat">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
        </svg>
        <span id="chatbot-badge" class="chatbot-badge">1</span>
    </button>

    <!-- JavaScript del Chatbot -->
    <script src="<?= base_url('assets/js/chatbot.js') ?>"></script>
</body>
</html>