<?php
// =====================================================
// API RECETAS - ACTUALIZAR RECETA
// Endpoint: https://colisan/sistema_apps_api/recetas/update.php
// =====================================================

require_once '../config.php';

// Solo permitir PUT
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    errorResponse('Método no permitido', 405);
}

// Obtener datos del JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    errorResponse('Datos inválidos');
}

// Validar campos requeridos
validateInput($input, ['token', 'recipe_id', 'nombre', 'tipo', 'ingredientes', 'preparacion']);

$token = $input['token'];
$recipeId = $input['recipe_id'];

// Decodificar token
$decoded = base64_decode($token);
$parts = explode(':', $decoded);

if (count($parts) !== 2) {
    errorResponse('Token inválido', 401);
}

$usuario_aplicacion_key = $parts[0];

// Validar tipo de receta
$tiposValidos = ['Entrante', 'Principal', 'Postre', 'Bebida', 'Extra'];
if (!in_array($input['tipo'], $tiposValidos)) {
    errorResponse('Tipo de receta no válido');
}

// Validar valoración
$valoracion = $input['valoracion'] ?? 5;
if ($valoracion < 1 || $valoracion > 5) {
    errorResponse('La valoración debe ser entre 1 y 5');
}

try {
    // Verificar que la receta existe y pertenece al usuario
    $stmt = $pdo->prepare("
        SELECT receta_id 
        FROM recetas 
        WHERE receta_id = ? AND usuario_aplicacion_key = ?
    ");
    $stmt->execute([$recipeId, $usuario_aplicacion_key]);
    
    if (!$stmt->fetch()) {
        errorResponse('Receta no encontrada o no tienes permisos', 404);
    }
    
    // Actualizar receta
    $sql = "UPDATE recetas SET 
        receta_nombre = ?, 
        receta_tipo = ?, 
        receta_ingredients = ?, 
        receta_preparation = ?, 
        receta_image = ?, 
        receta_video = ?, 
        receta_valoracion = ?, 
        receta_saludable = ?, 
        receta_tiempopreparacion = ?, 
        receta_dificultad = ?, 
        receta_porciones = ?,
        fecha_modificacion = CURRENT_TIMESTAMP
        WHERE receta_id = ? AND usuario_aplicacion_key = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $input['nombre'],
        $input['tipo'],
        $input['ingredientes'],
        $input['preparacion'],
        $input['imagen'] ?? '',
        $input['video'] ?? '',
        $valoracion,
        isset($input['saludable']) ? (bool)$input['saludable'] : false,
        $input['tiempo'] ?? '',
        $input['dificultad'] ?? 'Fácil',
        $input['porciones'] ?? '',
        $recipeId,
        $usuario_aplicacion_key
    ]);
    
    if ($result) {
        logDebug("Receta actualizada", [
            'user_key' => $usuario_aplicacion_key,
            'recipe_id' => $recipeId,
            'nombre' => $input['nombre']
        ]);
        
        successResponse([
            'recipe_id' => $recipeId
        ], 'Receta actualizada exitosamente');
    } else {
        errorResponse('Error al actualizar receta', 500);
    }
    
} catch (Exception $e) {
    logDebug("Error actualizando receta", ['error' => $e->getMessage()]);
    errorResponse('Error al actualizar receta', 500);
}
?>
