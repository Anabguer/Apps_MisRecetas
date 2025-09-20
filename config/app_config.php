<?php
// =====================================================
// CONFIGURACIÓN DE LA APLICACIÓN
// Define qué aplicación es esta para el sistema unificado
// =====================================================

// Configuración de esta aplicación específica
define('APP_CODIGO', 'recetas');
define('APP_NOMBRE', 'Mis Recetas');
define('APP_VERSION', '1.0.0');
define('APP_DESCRIPCION', 'Aplicación de gestión de recetas personales');

// Configuración para futuras aplicaciones
// Para puzzle_game/config/app_config.php → APP_CODIGO = 'puzzle'
// Para memoria_game/config/app_config.php → APP_CODIGO = 'memoria'

// Configuración de archivos
define('UPLOAD_BASE_PATH', 'uploads/');
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_VIDEO_TYPES', ['mp4', 'webm', 'mov']);

// URLs base (para producción en Hostalia)
define('API_BASE_URL', 'https://tu-dominio.com/api/');
define('UPLOAD_BASE_URL', 'https://tu-dominio.com/uploads/');

// Configuración de desarrollo (localhost)
$isLocalhost = ($_SERVER['HTTP_HOST'] === 'localhost' || 
                $_SERVER['HTTP_HOST'] === '127.0.0.1' || 
                strpos($_SERVER['HTTP_HOST'], 'localhost') !== false);

if ($isLocalhost) {
    // URLs para desarrollo local
    define('API_BASE_URL_DEV', 'http://localhost/mis_recetas/api/');
    define('UPLOAD_BASE_URL_DEV', 'http://localhost/mis_recetas/uploads/');
}

// Función para obtener la URL base correcta
function getApiBaseUrl() {
    global $isLocalhost;
    return $isLocalhost ? API_BASE_URL_DEV : API_BASE_URL;
}

function getUploadBaseUrl() {
    global $isLocalhost;
    return $isLocalhost ? UPLOAD_BASE_URL_DEV : UPLOAD_BASE_URL;
}

// Función para generar usuario_aplicacion_key
function generateUserAppKey($email, $app_codigo = null) {
    $app_codigo = $app_codigo ?? APP_CODIGO;
    return $email . '_' . $app_codigo;
}

// Función para generar ruta de upload
function generateUploadPath($usuario_aplicacion_key, $filename) {
    return UPLOAD_BASE_PATH . $usuario_aplicacion_key . '/' . $filename;
}

// Debug: mostrar configuración actual
if (isset($_GET['debug_config']) && $_GET['debug_config'] === 'true') {
    echo "<pre>";
    echo "APP_CODIGO: " . APP_CODIGO . "\n";
    echo "APP_NOMBRE: " . APP_NOMBRE . "\n";
    echo "API_BASE_URL: " . getApiBaseUrl() . "\n";
    echo "UPLOAD_BASE_URL: " . getUploadBaseUrl() . "\n";
    echo "Ejemplo usuario_key: " . generateUserAppKey('test@email.com') . "\n";
    echo "</pre>";
    exit;
}
?>
