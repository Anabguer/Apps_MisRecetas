<?php
// =====================================================
// API RECETAS - ACTUALIZAR RECETA
// Endpoint: https://colisan/sistema_apps_api/recetas/update.php
// =====================================================

require_once 'config.php';

// Solo permitir POST (para FormData)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Método no permitido', 405);
}

// Obtener datos del FormData (POST)
$input = $_POST;

if (empty($input)) {
    errorResponse('Datos inválidos');
}

// Validar campos requeridos
$requiredFields = ['token', 'receta_id', 'nombre', 'tipo', 'ingredientes', 'preparacion'];
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

// Validar tipo de receta
$tiposValidos = ['Entrante', 'Principal', 'Postre', 'Bebida', 'Extra'];
if (!in_array($input['tipo'], $tiposValidos)) {
    errorResponse('Tipo de receta no válido');
}

// Validar valoración
$valoracion = isset($input['valoracion']) ? (int)$input['valoracion'] : 5;
if ($valoracion < 0 || $valoracion > 5) {
    errorResponse('La valoración debe ser entre 0 y 5');
}

try {
    // Verificar que la receta existe y pertenece al usuario, y obtener datos actuales
    $stmt = $pdo->prepare("
        SELECT receta_id, receta_image, receta_video 
        FROM recetas 
        WHERE receta_id = ? AND usuario_aplicacion_key = ?
    ");
    $stmt->execute([$recipeId, $usuario_aplicacion_key]);
    $currentRecipe = $stmt->fetch();
    
    if (!$currentRecipe) {
        errorResponse('Receta no encontrada o no tienes permisos', 404);
    }
    
    // Limpiar nombre de receta para usar en archivo
    $nombreLimpio = preg_replace('/[^a-zA-Z0-9áéíóúñÁÉÍÓÚÑ\s]/', '', $input['nombre']);
    $nombreLimpio = preg_replace('/\s+/', '-', trim($nombreLimpio));
    $nombreLimpio = strtolower($nombreLimpio);
    
    // Inicializar URLs con valores existentes de la base de datos
    $imagenUrl = $currentRecipe['receta_image'] ?? '';
    $videoUrl = $currentRecipe['receta_video'] ?? '';
    
    // Procesar imagen nueva si existe
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $imageUploadDir = '../../sistema_apps_upload_media/recetas/images/';
        if (!is_dir($imageUploadDir)) {
            mkdir($imageUploadDir, 0777, true);
        }
        
        // Nombre único: usuario_tipo-nombre-timestamp.extension (evita caché)
        $extension = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
        $timestamp = time();
        $imageName = $usuario_aplicacion_key . '_' . strtolower($input['tipo']) . '-' . $nombreLimpio . '-' . $timestamp . '.' . $extension;
        $imagePath = $imageUploadDir . $imageName;
        
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $imagePath)) {
            $imagenUrl = 'https://colisan.com/sistema_apps_upload/sistema_apps_upload_media/recetas/images/' . $imageName;
        }
    }
    
    // Procesar video nuevo si existe
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $videoUploadDir = '../../sistema_apps_upload_media/recetas/videos/';
        if (!is_dir($videoUploadDir)) {
            mkdir($videoUploadDir, 0777, true);
        }
        
        // Nombre único: usuario_tipo-nombre-timestamp.extension (evita caché)
        $extension = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
        $timestamp = time();
        $videoName = $usuario_aplicacion_key . '_' . strtolower($input['tipo']) . '-' . $nombreLimpio . '-' . $timestamp . '.' . $extension;
        $videoPath = $videoUploadDir . $videoName;
        
        if (move_uploaded_file($_FILES['video']['tmp_name'], $videoPath)) {
            $videoUrl = 'https://colisan.com/sistema_apps_upload/sistema_apps_upload_media/recetas/videos/' . $videoName;
        }
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
        $imagenUrl,
        $videoUrl,
        $valoracion,
        isset($input['saludable']) && $input['saludable'] ? 1 : 0,
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
    logDebug("Error actualizando receta", [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    errorResponse('Error al actualizar receta: ' . $e->getMessage(), 500);
}
?>
