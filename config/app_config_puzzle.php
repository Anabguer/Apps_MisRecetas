<?php
// =====================================================
// CONFIGURACIÓN DE LA APLICACIÓN - JUEGO PUZZLE
// Sistema unificado para localhost y Hostalia
// =====================================================

// Configuración de esta aplicación específica
define('APP_CODIGO', 'puzzle');
define('APP_NOMBRE', 'Juego de Puzzle');
define('APP_VERSION', '1.0.0');
define('APP_DESCRIPCION', 'Juego de rompecabezas con diferentes dificultades');

// Configuración de archivos para el juego
define('UPLOAD_BASE_PATH', 'uploads/');
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB para imágenes del juego
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// URLs base (para producción en Hostalia)
define('API_BASE_URL', 'https://colisan.com/sistema_apps_upload/sistema_apps_api/');
define('UPLOAD_BASE_URL', 'https://colisan.com/sistema_apps_upload/uploads/');

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

// Configuración específica del juego
define('PUZZLE_NIVELES_MAXIMOS', [
    'Fácil' => 10,
    'Medio' => 15,
    'Difícil' => 20
]);

define('PUZZLE_PUNTUACION_BASE', [
    'Fácil' => 100,
    'Medio' => 150,
    'Difícil' => 200
]);

define('PUZZLE_TIEMPO_LIMITE', [
    'Fácil' => 300,    // 5 minutos
    'Medio' => 600,    // 10 minutos
    'Difícil' => 900   // 15 minutos
]);

// Función para calcular puntuación del juego
function calcularPuntuacion($dificultad, $tiempo_segundos, $movimientos, $completado) {
    if (!$completado) return 0;
    
    $puntuacion_base = PUZZLE_PUNTUACION_BASE[$dificultad] ?? 100;
    $tiempo_limite = PUZZLE_TIEMPO_LIMITE[$dificultad] ?? 300;
    
    // Penalizar por tiempo excesivo
    $penalizacion_tiempo = max(0, ($tiempo_segundos - ($tiempo_limite * 0.5)) / 10);
    
    // Penalizar por muchos movimientos
    $penalizacion_movimientos = max(0, ($movimientos - 50) / 5);
    
    $puntuacion_final = $puntuacion_base - $penalizacion_tiempo - $penalizacion_movimientos;
    
    return max(0, round($puntuacion_final));
}

// Función para verificar logros
function verificarLogros($usuario_key, $estadisticas) {
    $logros = [];
    
    // Logro: Primera partida completada
    if ($estadisticas['partidas_completadas'] == 1) {
        $logros[] = [
            'codigo' => 'primera_victoria',
            'nombre' => 'Primera Victoria',
            'descripcion' => 'Completaste tu primera partida',
            'icono' => '🏆'
        ];
    }
    
    // Logro: 10 partidas completadas
    if ($estadisticas['partidas_completadas'] == 10) {
        $logros[] = [
            'codigo' => 'decena_victorias',
            'nombre' => 'Decena de Victorias',
            'descripcion' => 'Completaste 10 partidas',
            'icono' => '🎯'
        ];
    }
    
    // Logro: Tiempo récord
    if ($estadisticas['mejor_tiempo'] > 0 && $estadisticas['mejor_tiempo'] < 60) {
        $logros[] = [
            'codigo' => 'velocidad_rayo',
            'nombre' => 'Velocidad del Rayo',
            'descripcion' => 'Completaste un puzzle en menos de 1 minuto',
            'icono' => '⚡'
        ];
    }
    
    return $logros;
}

// Debug: mostrar configuración actual
if (isset($_GET['debug_config']) && $_GET['debug_config'] === 'true') {
    echo "<pre>";
    echo "APP_CODIGO: " . APP_CODIGO . "\n";
    echo "APP_NOMBRE: " . APP_NOMBRE . "\n";
    echo "API_BASE_URL: " . getApiBaseUrl() . "\n";
    echo "UPLOAD_BASE_URL: " . getUploadBaseUrl() . "\n";
    echo "Ejemplo usuario_key: " . generateUserAppKey('test@email.com') . "\n";
    echo "Niveles máximos: " . json_encode(PUZZLE_NIVELES_MAXIMOS) . "\n";
    echo "Puntuación ejemplo: " . calcularPuntuacion('Medio', 120, 45, true) . "\n";
    echo "</pre>";
    exit;
}
?>
