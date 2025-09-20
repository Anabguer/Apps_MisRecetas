<?php
// =====================================================
// API RECETAS - ACTUALIZAR RECETA
// Endpoint: https://colisan/sistema_apps_api/recetas/update.php
// =====================================================

// Debug logging
error_log("UPDATE.PHP - Inicio");
error_log("UPDATE.PHP - Method: " . $_SERVER['REQUEST_METHOD']);
error_log("UPDATE.PHP - POST data: " . print_r($_POST, true));
error_log("UPDATE.PHP - FILES data: " . print_r($_FILES, true));

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
    
    // Manejar archivos subidos
    $imagenUrl = $input['imagen_url'] ?? '';  // URL existente por defecto
    $videoUrl = $input['video_url'] ?? '';    // URL existente por defecto
    
    // Procesar imagen nueva si existe
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../sistema_apps_upload/recetas/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $imageName = uniqid() . '_' . $_FILES['imagen']['name'];
        $imagePath = $uploadDir . $imageName;
        
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $imagePath)) {
            $imagenUrl = 'https://colisan.com/sistema_apps_upload/sistema_apps_upload/recetas/' . $imageName;
        }
    }
    
    // Procesar video nuevo si existe
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../sistema_apps_upload/recetas/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $videoName = uniqid() . '_' . $_FILES['video']['name'];
        $videoPath = $uploadDir . $videoName;
        
        if (move_uploaded_file($_FILES['video']['tmp_name'], $videoPath)) {
            $videoUrl = 'https://colisan.com/sistema_apps_upload/sistema_apps_upload/recetas/' . $videoName;
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
