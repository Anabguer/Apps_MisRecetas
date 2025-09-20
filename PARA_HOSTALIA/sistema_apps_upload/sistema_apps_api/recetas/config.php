<?php
// =====================================================
// CONFIGURACIÓN API RECETAS - HOSTALIA
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
// CONFIGURACIÓN DE LA APLICACIÓN
// =====================================================

define('APP_CODIGO', 'recetas');
define('APP_NOMBRE', 'Mis Recetas');
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
        'error' => 'Error de conexión a la base de datos',
        'debug' => $e->getMessage() // Solo en desarrollo
    ]);
    exit();
}

// =====================================================
// FUNCIONES DE UTILIDAD
// =====================================================

/**
 * Generar clave usuario-aplicación
 */
function generateUserAppKey($email, $app_codigo = null) {
    $app_codigo = $app_codigo ?? APP_CODIGO;
    return strtolower(trim($email)) . '_' . strtolower(trim($app_codigo));
}

/**
 * Validar token de sesión (simplificado)
 */
function validateSession($token) {
    // En una implementación real, validarías el token contra la BD
    // Por ahora, implementación básica
    return !empty($token);
}

/**
 * Obtener usuario desde token
 */
function getUserFromToken($token) {
    global $pdo;
    
    // Implementación simplificada
    // En producción, usarías JWT o tokens en BD
    try {
        $stmt = $pdo->prepare("
            SELECT usuario_aplicacion_key, email, nombre 
            FROM usuarios_aplicaciones 
            WHERE activo = 1 AND app_codigo = ?
            LIMIT 1
        ");
        $stmt->execute([APP_CODIGO]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Respuesta de error estándar
 */
function errorResponse($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit();
}

/**
 * Respuesta de éxito estándar
 */
function successResponse($data = [], $message = 'Operación exitosa') {
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

/**
 * Validar datos de entrada
 */
function validateInput($data, $requiredFields) {
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            errorResponse("El campo '$field' es requerido");
        }
    }
}

// =====================================================
// LOG DE DEPURACIÓN (OPCIONAL)
// =====================================================

function logDebug($message, $data = []) {
    $logFile = __DIR__ . '/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message" . (empty($data) ? '' : ' - ' . json_encode($data)) . "\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Log de inicio de API
logDebug("API Recetas iniciada", ['method' => $_SERVER['REQUEST_METHOD'], 'uri' => $_SERVER['REQUEST_URI']]);
?>
