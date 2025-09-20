# Mis Recetas - PWA

Una aplicación web progresiva (PWA) para crear y gestionar recetas personales.

## 🚀 Características

- **PWA Completa**: Instalable como app nativa en móviles y escritorio
- **Optimizada para móvil**: Interfaz diseñada mobile-first
- **Autenticación simple**: Login con email (sin contraseña por simplicidad)
- **Gestión de recetas**: Crear, ver y organizar recetas personales
- **Filtros inteligentes**: Por tipo, saludable, búsqueda
- **Trabajo offline**: Service Worker para funcionalidad offline básica

## 📱 Instalación

### Requisitos
- XAMPP o servidor PHP con MySQL
- PHP 7.4+
- MySQL 5.7+

### Configuración

1. **Base de datos**:
   - Crear base de datos `mis_recetas_db`
   - Ejecutar el script `setup_database.sql`

2. **Servidor**:
   - Copiar archivos a `C:/xampp/htdocs/mis_recetas/`
   - Acceder a `http://localhost/mis_recetas/`

3. **Iconos PWA** (opcional):
   - Generar iconos de diferentes tamaños en la carpeta `icons/`
   - Tamaños necesarios: 72x72, 96x96, 128x128, 144x144, 152x152, 192x192, 384x384, 512x512

## 🎯 Uso

1. **Registro/Login**: 
   - Registrarse con email y nombre
   - Login simplificado solo con email

2. **Crear recetas**:
   - Botón "Nueva" en el header
   - Completar formulario con ingredientes y preparación
   - Añadir imágenes y videos opcionales

3. **Gestionar recetas**:
   - Ver todas las recetas en grid
   - Filtrar por tipo o búsqueda
   - Marcar como saludables

4. **Instalar como app**:
   - Chrome/Edge mostrarán prompt de instalación
   - O usar menú "Instalar aplicación"

## 🔧 Estructura

```
mis_recetas/
├── api/                 # APIs REST
├── config/             # Configuración DB
├── css/               # Estilos
├── icons/             # Iconos PWA
├── includes/          # Includes PHP
├── js/                # JavaScript
├── index.php          # Página principal
├── login.php          # Autenticación
├── manifest.json      # Manifiesto PWA
└── sw.js             # Service Worker
```

## 🛠️ Tecnologías

- **Frontend**: HTML5, CSS3, JavaScript ES6+
- **Backend**: PHP 7.4+, MySQL
- **PWA**: Service Worker, Web App Manifest
- **Diseño**: Mobile-first, CSS Grid, Flexbox

## 📝 Próximas mejoras

- Modal de detalle de recetas
- Edición de recetas existentes
- Compartir recetas
- Importación desde URLs
- Modo oscuro
- Notificaciones push
