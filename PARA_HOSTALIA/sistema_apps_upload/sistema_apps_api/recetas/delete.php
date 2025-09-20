<?php
// =====================================================
// API RECETAS - ELIMINAR RECETA
// Endpoint: https://colisan/sistema_apps_api/recetas/delete.php
// =====================================================

// Debug logging
error_log("DELETE.PHP - Inicio");
error_log("DELETE.PHP - Method: " . $_SERVER['REQUEST_METHOD']);

require_once 'config.php';

// Solo permitir POST (como update.php y create_new.php)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Método no permitido', 405);
}

// Obtener datos del JSON (igual que antes)
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    errorResponse('Datos inválidos');
}

// Validar campos requeridos
$requiredFields = ['token', 'receta_id'];
foreach ($requiredFields as $field) {
    if (empty($input[$field])) {
        errorResponse("Campo requerido: $field");
    }
}

$token = $input['token'];
$recipeId = $input['receta_id'];

// Decodificar token
$decoded = base64_decode($token);
$parts = explode(':', $decoded);

if (count($parts) !== 2) {
    errorResponse('Token inválido', 401);
}

$usuario_aplicacion_key = $parts[0];

try {
    // Verificar que la receta existe y pertenece al usuario
    $stmt = $pdo->prepare("
        SELECT receta_id, receta_nombre, receta_image, receta_video
        FROM recetas 
        WHERE receta_id = ? AND usuario_aplicacion_key = ?
    ");
    $stmt->execute([$recipeId, $usuario_aplicacion_key]);
    $receta = $stmt->fetch();
    
    if (!$receta) {
        errorResponse('Receta no encontrada o no tienes permisos', 404);
    }
    
    // Eliminar receta
    $stmt = $pdo->prepare("
        DELETE FROM recetas 
        WHERE receta_id = ? AND usuario_aplicacion_key = ?
    ");
    $result = $stmt->execute([$recipeId, $usuario_aplicacion_key]);
    
    if ($result) {
        // TODO: Eliminar archivos de imagen y video de Hostalia si existen
        // Esto requeriría una implementación adicional para limpiar archivos huérfanos
        
        logDebug("Receta eliminada", [
            'user_key' => $usuario_aplicacion_key,
            'recipe_id' => $recipeId,
            'nombre' => $receta['receta_nombre']
        ]);
        
        successResponse([
            'recipe_id' => $recipeId
        ], 'Receta eliminada exitosamente');
    } else {
        errorResponse('Error al eliminar receta', 500);
    }
    
} catch (Exception $e) {
    logDebug("Error eliminando receta", ['error' => $e->getMessage()]);
    errorResponse('Error al eliminar receta', 500);
}
?>
