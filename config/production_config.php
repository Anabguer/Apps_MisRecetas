<?php
// =====================================================
// CONFIGURACIÓN DE PRODUCCIÓN PARA APK
// URLs y configuraciones específicas para Hostalia
// =====================================================

// Configuración de producción
define('PRODUCTION_MODE', true);

// URLs base para Hostalia
define('HOSTALIA_API_BASE', 'https://colisan/sistema_apps_api/');
define('HOSTALIA_UPLOAD_BASE', 'https://colisan/sistema_apps_upload/');

// Configuración de base de datos (ya está en database.php)
// Pero aquí podemos tener configuraciones específicas para la APK

// Configuración de uploads
define('UPLOAD_MAX_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_VIDEO_TYPES', ['mp4', 'webm', 'ogg', 'mov']);

// Configuración de la aplicación
define('APP_NAME_PRODUCTION', 'Mis Recetas');
define('APP_VERSION_PRODUCTION', '1.0.0');

// Configuración de seguridad
define('SESSION_TIMEOUT', 3600 * 24 * 7); // 7 días
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutos

// Función para generar URLs de archivos en Hostalia
function getHostaliaFileUrl($userKey, $type, $filename) {
    // $type: 'imagenes' o 'videos'
    return HOSTALIA_UPLOAD_BASE . $userKey . '/' . $type . '/' . $filename;
}

// Función para verificar si estamos en modo producción
function isProductionMode() {
    // En la APK, siempre será producción
    // En localhost, será desarrollo
    return !($_SERVER['HTTP_HOST'] === 'localhost' || 
             $_SERVER['HTTP_HOST'] === '127.0.0.1' ||
             strpos($_SERVER['HTTP_HOST'], 'localhost') !== false);
}

// Configuración de endpoints API para la APK
$API_ENDPOINTS = [
    'login' => HOSTALIA_API_BASE . 'auth/login.php',
    'register' => HOSTALIA_API_BASE . 'auth/register.php',
    'recipes_list' => HOSTALIA_API_BASE . 'recipes/list.php',
    'recipe_create' => HOSTALIA_API_BASE . 'recipes/create.php',
    'recipe_update' => HOSTALIA_API_BASE . 'recipes/update.php',
    'recipe_delete' => HOSTALIA_API_BASE . 'recipes/delete.php',
    'upload_file' => HOSTALIA_API_BASE . 'upload/file.php'
];

// Función para obtener endpoint API
function getApiEndpoint($endpoint) {
    global $API_ENDPOINTS;
    return $API_ENDPOINTS[$endpoint] ?? null;
}

// Debug info para desarrollo
if (isset($_GET['debug_production']) && $_GET['debug_production'] === 'true') {
    echo "<pre>";
    echo "=== CONFIGURACIÓN DE PRODUCCIÓN ===\n";
    echo "Modo producción: " . (isProductionMode() ? 'SÍ' : 'NO') . "\n";
    echo "API Base: " . HOSTALIA_API_BASE . "\n";
    echo "Upload Base: " . HOSTALIA_UPLOAD_BASE . "\n";
    echo "\nEndpoints API:\n";
    global $API_ENDPOINTS;
    foreach ($API_ENDPOINTS as $name => $url) {
        echo "$name: $url\n";
    }
    echo "</pre>";
    exit;
}
?>
