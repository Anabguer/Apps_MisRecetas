# 📱 MANUAL SISTEMA MULTI-APLICACIÓN HOSTALIA

## 🎯 **INTRODUCCIÓN**

Este manual te guía paso a paso para crear nuevas aplicaciones móviles usando el sistema multi-aplicación ya implementado en Hostalia. Cada nueva app (puzzle, memory, etc.) podrá usar el mismo sistema de autenticación y base de datos.

---

## 🗃️ **1. ESTRUCTURA DE BASE DE DATOS**

### **📊 TABLA PRINCIPAL: `usuarios_aplicaciones` (YA EXISTE)**

```sql
-- ESTRUCTURA ACTUAL EN HOSTALIA:
usuarios_aplicaciones (
    usuario_aplicacion_id    INT(11)      AUTO_INCREMENT PRIMARY KEY,
    usuario_aplicacion_key   VARCHAR(150) NOT NULL UNIQUE,
    email                    VARCHAR(255) NOT NULL,
    nombre                   VARCHAR(255) NOT NULL,
    password_hash            VARCHAR(255) NOT NULL,
    app_codigo               VARCHAR(50)  NOT NULL,
    fecha_registro           TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso            TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    activo                   TINYINT(1)   DEFAULT 1,
    configuracion            LONGTEXT     NULL
);

-- NOTA: Esta tabla YA EXISTE y está funcionando
-- NO es necesario crearla de nuevo

-- VERIFICAR QUE EXISTE:
SHOW TABLES LIKE 'usuarios_aplicaciones';
SELECT COUNT(*) FROM usuarios_aplicaciones;
```

### **🔑 CLAVE ÚNICA: `usuario_aplicacion_key`**
- **Formato:** `email_appcodigo`
- **Ejemplo:** `1954amg@gmail.com_recetas`
- **Función:** Permite al mismo usuario estar en múltiples apps

---

## 🌐 **2. CONFIGURACIÓN HOSTALIA**

### **📡 DATOS DE CONEXIÓN:**

```php
// Configuración Base de Datos Hostalia
define('DB_HOST', 'PMYSQL165.dns-servicio.com');
define('DB_USUARIO', 'sistema_apps_user');
define('DB_CONTRA', 'GestionUploadSistemaApps!');
define('DB_NOMBRE', '9606966_sistema_apps_db');
define('DB_CHARSET', 'utf8');
define('DB_PORT', 3306);
```

### **🔗 URLs BASE:**

```php
// URLs de Hostalia
define('API_BASE_URL', 'https://colisan.com/sistema_apps_upload/sistema_apps_api/');
define('UPLOAD_BASE_URL', 'https://colisan.com/sistema_apps_upload/');
define('WEB_BASE_URL', 'https://colisan.com/sistema_apps_upload/');
```

---

## 📁 **3. ESTRUCTURA DE CARPETAS EN HOSTALIA**

```
sistema_apps_upload/ (RAÍZ)
├── index.html                    ← Selector de aplicaciones
├── router.html                   ← Router inteligente
├── app_recetas.html             ← Aplicación Recetas (ESPECÍFICA)
├── app_puzzle.html              ← Aplicación Puzzle (ESPECÍFICA - nueva)
├── app_memory.html              ← Aplicación Memory (ESPECÍFICA - nueva)
├── sistema_apps_api/
│   ├── recetas/                 ← APIs Recetas
│   │   ├── config.php
│   │   ├── auth.php
│   │   ├── list.php
│   │   ├── create.php
│   │   ├── update.php
│   │   ├── delete.php
│   │   ├── get.php
│   │   └── upload.php
│   ├── puzzle/                  ← APIs Puzzle (nueva)
│   └── memory/                  ← APIs Memory (nueva)
└── sistema_apps_upload/
    ├── recetas/                 ← Uploads Recetas
    │   └── upload_handler.php
    ├── puzzle/                  ← Uploads Puzzle (nueva)
    └── memory/                  ← Uploads Memory (nueva)
```

---

## 🔄 **4. PROCESO CRONOLÓGICO PARA NUEVA APLICACIÓN**

### **PASO 1: 🗃️ CREAR SOLO TABLA DE DATOS ESPECÍFICOS**

```sql
-- IMPORTANTE: La tabla usuarios_aplicaciones YA EXISTE
-- Solo crear la tabla específica de tu nueva aplicación

-- Ejemplo para aplicación Puzzle:
CREATE TABLE puzzle_datos (
    puzzle_id INT(11) AUTO_INCREMENT PRIMARY KEY,
    usuario_aplicacion_key VARCHAR(150) NOT NULL,
    puzzle_nombre VARCHAR(255) NOT NULL,
    puzzle_dificultad ENUM('Fácil', 'Medio', 'Difícil') DEFAULT 'Fácil',
    puzzle_imagen VARCHAR(500),
    puzzle_completado TINYINT(1) DEFAULT 0,
    puzzle_tiempo_mejor INT DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (usuario_aplicacion_key) REFERENCES usuarios_aplicaciones(usuario_aplicacion_key),
    INDEX idx_usuario (usuario_aplicacion_key)
);

-- NOTA: usuario_aplicacion_key debe ser VARCHAR(150) para coincidir con la tabla principal
```

### **PASO 2: 📁 CREAR CARPETAS EN HOSTALIA**

```bash
# En FileZilla o panel de control:
sistema_apps_upload/sistema_apps_api/puzzle/
sistema_apps_upload/sistema_apps_upload/puzzle/
```

### **PASO 3: 🔧 CREAR CONFIG.PHP**

```php
<?php
// =====================================================
// CONFIGURACIÓN API [NOMBRE_APP] - HOSTALIA
// Solo para uso en Hostalia (producción)
// =====================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// =====================================================
// CONFIGURACIÓN DE BASE DE DATOS HOSTALIA
// =====================================================

define('DB_HOST', 'PMYSQL165.dns-servicio.com');
define('DB_USUARIO', 'sistema_apps_user');
define('DB_CONTRA', 'GestionUploadSistemaApps!');
define('DB_NOMBRE', '9606966_sistema_apps_db');
define('DB_CHARSET', 'utf8');
define('DB_PORT', 3306);

// =====================================================
// CONFIGURACIÓN DE LA APLICACIÓN - CAMBIAR ESTOS VALORES
// =====================================================

define('APP_CODIGO', 'puzzle');           // ← CAMBIAR por código de tu app
define('APP_NOMBRE', 'Puzzle Game');      // ← CAMBIAR por nombre de tu app
define('UPLOAD_BASE_URL', 'https://colisan.com/sistema_apps_upload/');

// =====================================================
// CONEXIÓN A LA BASE DE DATOS
// =====================================================

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NOMBRE . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USUARIO, DB_CONTRA, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
    ]);
    
    // Configurar zona horaria
    $pdo->exec("SET time_zone = '+01:00'"); // Zona horaria de España
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Error de conexión a la base de datos'
    ]);
    exit();
}

// =====================================================
// FUNCIONES DE UTILIDAD
// =====================================================

function generateUserAppKey($email, $app_codigo = null) {
    $app_codigo = $app_codigo ?? APP_CODIGO;
    return strtolower(trim($email)) . '_' . strtolower(trim($app_codigo));
}

function errorResponse($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit();
}

function successResponse($data = [], $message = 'Operación exitosa') {
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// Compatible con PHP 7.4 (reemplaza str_starts_with)
function startsWith($haystack, $needle) {
    return substr($haystack, 0, strlen($needle)) === $needle;
}
?>
```

**📝 PARA NUEVA APLICACIÓN:**
- **Línea 33:** Cambiar `'puzzle'` por código de tu app
- **Línea 34:** Cambiar `'Puzzle Game'` por nombre de tu app
- **Todo lo demás:** Dejar igual

### **PASO 4: 🔐 CREAR AUTH.PHP**

```php
<?php
// sistema_apps_api/puzzle/auth.php
require_once 'config.php';

// EXACTAMENTE IGUAL que recetas/auth.php
// Solo cambia APP_CODIGO automáticamente
?>
```

### **PASO 5: 📋 CREAR APIs ESPECÍFICAS**

```php
// sistema_apps_api/puzzle/list.php
require_once 'config.php';

// Consulta específica de puzzle
$stmt = $pdo->prepare("
    SELECT * FROM puzzle_datos 
    WHERE usuario_aplicacion_key = ?
    ORDER BY fecha_creacion DESC
");
```

### **PASO 6: 📤 CREAR UPLOAD HANDLER**

```php
// sistema_apps_upload/puzzle/upload_handler.php
// EXACTAMENTE IGUAL que recetas/upload_handler.php
// Las carpetas se crean automáticamente por usuario
```

### **PASO 7: 🌐 CREAR APLICACIÓN WEB ESPECÍFICA**

```html
<!-- app_puzzle.html - ARCHIVO ESPECÍFICO PARA PUZZLE -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Puzzle Game</title>
    <style>
        /* CSS específico del puzzle */
        /* Copiar estructura base de app_recetas.html y adaptar */
    </style>
</head>
<body>
    <!-- HTML específico del puzzle -->
    
    <script>
        // Configuración API específica
        const API_BASE = 'https://colisan.com/sistema_apps_upload/sistema_apps_api/puzzle/';
        
        // Lógica específica del puzzle
        // Copiar funciones base de app_recetas.html y adaptar
    </script>
</body>
</html>
```

**📝 IMPORTANTE:**
- **CADA aplicación** tiene su archivo HTML específico
- **NO reutilizar** app_recetas.html para otras apps
- **Copiar estructura base** y adaptar para cada aplicación

### **PASO 8: 🎯 ACTUALIZAR ROUTER**

```javascript
// En router.html - añadir nueva aplicación
const APLICACIONES = {
    'recetas': {
        nombre: 'Mis Recetas',
        emoji: '🍃',
        archivo: 'app.html'
    },
    'puzzle': {
        nombre: 'Puzzle Game', 
        emoji: '🧩',
        archivo: 'app_puzzle.html'
    }
};
```

### **PASO 9: 🏠 ACTUALIZAR SELECTOR**

```html
<!-- En index.html - añadir nueva tarjeta -->
<a href="app_puzzle.html" class="app-card">
    <div class="app-icon">🧩</div>
    <div class="app-name">Puzzle Game</div>
    <div class="app-desc">Juego de rompecabezas</div>
</a>
```

### **PASO 10: 📱 CREAR APK NUEVA**

```kotlin
// MainActivity.kt para Puzzle
webView.loadUrl("https://colisan.com/sistema_apps_upload/router.html?app=puzzle")
```

---

## 🔧 **5. DATOS ESPECÍFICOS PARA CADA PASO**

### **📊 CONFIGURACIÓN DE APLICACIÓN:**

```php
// Datos que cambian por aplicación:
APP_CODIGO = 'puzzle'           // Código único de la app
APP_NOMBRE = 'Puzzle Game'      // Nombre para mostrar
TABLA_DATOS = 'puzzle_datos'    // Tabla específica de datos
```

### **🗃️ ESTRUCTURA DE TABLA PERSONALIZABLE:**

```sql
-- Campos comunes (OBLIGATORIOS):
usuario_aplicacion_key VARCHAR(255) NOT NULL,
fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

-- Campos específicos (VARIABLES por app):
puzzle_nombre VARCHAR(255),
puzzle_dificultad ENUM(...),
puzzle_imagen VARCHAR(500),
-- etc.
```

### **🔗 URLs DE ACCESO:**

```
Selector:  https://colisan.com/sistema_apps_upload/
Router:    https://colisan.com/sistema_apps_upload/router.html?app=CODIGO
App Web:   https://colisan.com/sistema_apps_upload/app_CODIGO.html
API Auth:  https://colisan.com/sistema_apps_upload/sistema_apps_api/CODIGO/auth.php
API List:  https://colisan.com/sistema_apps_upload/sistema_apps_api/CODIGO/list.php
Uploads:   https://colisan.com/sistema_apps_upload/sistema_apps_upload/CODIGO/

EJEMPLOS ESPECÍFICOS:
- Recetas: https://colisan.com/sistema_apps_upload/app_recetas.html
- Puzzle:  https://colisan.com/sistema_apps_upload/app_puzzle.html
- Memory:  https://colisan.com/sistema_apps_upload/app_memory.html
```

---

## ⚡ **6. VENTAJAS DEL SISTEMA**

### **🚀 ESCALABILIDAD:**
- ✅ **Una sola base de datos** para todas las apps
- ✅ **Sistema de login unificado** 
- ✅ **Estructura reutilizable**
- ✅ **APIs consistentes**

### **🛠️ MANTENIMIENTO:**
- ✅ **Configuración centralizada**
- ✅ **Código reutilizable**
- ✅ **Fácil debugging**
- ✅ **Backups unificados**

---

## 📞 **7. CONTACTO Y SOPORTE**

**Para implementar una nueva aplicación:**
1. **📋 Define** los campos específicos de tu app
2. **🗃️ Crea** la tabla con la estructura base
3. **📁 Sigue** los pasos cronológicos del manual
4. **🧪 Prueba** cada paso antes de continuar

**¡El sistema está preparado para crecer!** 🌱✨

---

*Manual creado: 20 de Septiembre, 2025*  
*Sistema Multi-Aplicación v1.0*
