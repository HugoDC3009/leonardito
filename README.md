# 🤖 Leonardito - Chatbot TUPA

![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)
![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4.x-EF4223?style=for-the-badge&logo=codeigniter&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Laragon](https://img.shields.io/badge/Laragon-Environment-00B0FF?style=for-the-badge)

## 📄 Descripción del Proyecto

**Leonardito** es una aplicación web inteligente diseñada para facilitar el acceso a la información del **Texto Único de Procedimientos Administrativos (TUPA)**. 

El sistema integra un **Chatbot** interactivo que permite a los ciudadanos consultar requisitos, costos y plazos de trámites administrativos mediante lenguaje natural, y un **Panel Administrativo** robusto para la gestión y actualización de estos procedimientos.

Este proyecto moderniza la atención al ciudadano, ofreciendo respuestas rápidas y precisas las 24 horas del día.

## ✨ Características Principales

*   **💬 Chatbot Inteligente**: Interfaz conversacional amigable para consultas rápidas sobre trámites (e.g., "requisitos para matrimonio civil").
*   **📂 Gestión de Contenidos (CRUD)**: Panel administrativo completo para crear, editar y eliminar procedimientos TUPA.
*   **📄 Procesamiento de PDF**: Capacidad integrada para manejar documentos TUPA oficiales (usando `smalot/pdfparser`).
*   **🔍 Búsqueda Avanzada**: Algoritmos para localizar trámites específicos basados en palabras clave del usuario.
*   **📱 Diseño Responsivo**: Accesible desde dispositivos móviles y de escritorio.

## 🛠️ Requisitos del Sistema

Para desplegar este proyecto, asegúrate de que tu servidor cumpla con los siguientes requisitos:

*   **PHP**: Versión 8.1 o superior.
*   **Extensiones PHP**: `intl`, `mbstring`, `json`, `mysqlnd`, `curl`.
*   **Base de Datos**: MySQL o MariaDB.
*   **Servidor Web**: Apache (recomendado con Laragon/XAMPP) o Nginx.
*   **Composer**: Para la gestión de dependencias.

## 🚀 Instalación y Configuración

Sigue estos pasos para configurar el proyecto en tu entorno local:

1.  **Clonar el Repositorio**
    ```bash
    git clone https://github.com/HugoDC3009/leonardito.git
    cd leonardito
    ```

2.  **Instalar Dependencias**
    Ejecuta Composer para instalar las librerías necesarias (CodeIgniter 4, PDFParser, etc.):
    ```bash
    composer install
    ```

3.  **Configurar Entorno**
    Copia el archivo de configuración de ejemplo y renómbralo:
    ```bash
    cp env .env
    ```
    Edita el archivo `.env` y configura tu base de datos y URL base:
    ```ini
    CI_ENVIRONMENT = development
    app.baseURL = 'http://leonardito.test/'

    database.default.hostname = localhost
    database.default.database = nombre_de_tu_bd
    database.default.username = root
    database.default.password = 
    database.default.DBDriver = MySQLi
    ```

4.  **Migrar Base de Datos**
    Ejecuta las migraciones para crear las tablas necesarias:
    ```bash
    php spark migrate
    ```

5.  **Iniciar Servidor**
    Si usas Laragon, el host virtual se creará automáticamente. Si no, puedes usar el servidor interno de CodeIgniter:
    ```bash
    php spark serve
    ```

## 📖 Uso del Sistema

### 🤖 Interfaz del Chatbot
Accede a la ruta principal `/` para interactuar con el bot.
*   **Ejemplo de consulta**: *"¿Cuánto cuesta la licencia de funcionamiento?"*

### ⚙️ Panel Administrativo
Accede a `/admin/tupa` para gestionar los procedimientos.
*   **Funciones**: Listar todos los trámites, agregar nuevos procedimientos manualmente o editar la información existente que el chatbot utiliza para responder.

## 📁 Estructura del Proyecto

*   `app/Controllers/Bot.php`: Lógica principal del chatbot.
*   `app/Controllers/Admin/Tupa.php`: Controlador para la gestión administrativa.
*   `app/Models/TupaModel.php`: Modelo de interacción con la base de datos de trámites.
*   `public/`: Archivos públicos (CSS, JS, imágenes y el archivo `index.php`).

---
Desarrollado con ❤️ para mejorar la gestión administrativa.
