<?php
// =====================================================
// API RECETAS - OBTENER RECETA INDIVIDUAL
// Endpoint: https://colisan/sistema_apps_api/recetas/get.php
// =====================================================

require_once '../config.php';

// Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    errorResponse('Método no permitido', 405);
}

// Obtener parámetros
$token = $_GET['token'] ?? '';
$recipeId = $_GET['recipe_id'] ?? '';

// Validar parámetros
if (empty($token)) {
    errorResponse('Token requerido', 401);
}

if (empty($recipeId)) {
    errorResponse('ID de receta requerido');
}

// Decodificar token
$decoded = base64_decode($token);
$parts = explode(':', $decoded);

if (count($parts) !== 2) {
    errorResponse('Token inválido', 401);
}

$usuario_aplicacion_key = $parts[0];

try {
    // Obtener receta específica
    $stmt = $pdo->prepare("
        SELECT * FROM recetas 
        WHERE receta_id = ? AND usuario_aplicacion_key = ?
    ");
    $stmt->execute([$recipeId, $usuario_aplicacion_key]);
    $receta = $stmt->fetch();
    
    if (!$receta) {
        errorResponse('Receta no encontrada', 404);
    }
    
    // Convertir valores booleanos
    $receta['receta_saludable'] = (bool)$receta['receta_saludable'];
    
    // Procesar URLs de archivos
    if (!empty($receta['receta_image']) && !str_starts_with($receta['receta_image'], 'http')) {
        $receta['receta_image'] = UPLOAD_BASE_URL . $usuario_aplicacion_key . '/imagenes/' . $receta['receta_image'];
    }
    
    if (!empty($receta['receta_video']) && !str_starts_with($receta['receta_video'], 'http')) {
        $receta['receta_video'] = UPLOAD_BASE_URL . $usuario_aplicacion_key . '/videos/' . $receta['receta_video'];
    }
    
    logDebug("Receta obtenida", [
        'user_key' => $usuario_aplicacion_key,
        'recipe_id' => $recipeId,
        'nombre' => $receta['receta_nombre']
    ]);
    
    successResponse([
        'receta' => $receta
    ], 'Receta obtenida exitosamente');
    
} catch (Exception $e) {
    logDebug("Error obteniendo receta", ['error' => $e->getMessage()]);
    errorResponse('Error al obtener receta', 500);
}
?>
