<?php
// =====================================================
// API RECETAS - CREAR RECETA
// Endpoint: https://colisan/sistema_apps_api/recetas/create.php
// =====================================================

require_once 'config.php';

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Método no permitido', 405);
}

// Obtener datos del FormData (POST)
$input = $_POST;

if (empty($input)) {
    errorResponse('Datos inválidos');
}

// Validar campos requeridos
$requiredFields = ['token', 'nombre', 'tipo', 'ingredientes', 'preparacion'];
foreach ($requiredFields as $field) {
    if (empty($input[$field])) {
        errorResponse("Campo requerido: $field");
    }
}

$token = $input['token'];

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
    // Verificar que el usuario existe
    $stmt = $pdo->prepare("
        SELECT usuario_aplicacion_id 
        FROM usuarios_aplicaciones 
        WHERE usuario_aplicacion_key = ? AND activo = 1
    ");
    $stmt->execute([$usuario_aplicacion_key]);
    
    if (!$stmt->fetch()) {
        errorResponse('Token inválido', 401);
    }
    
    // Manejar archivos subidos
    $imagenUrl = '';
    $videoUrl = '';
    
    // Limpiar nombre de receta para usar en archivo
    $nombreLimpio = preg_replace('/[^a-zA-Z0-9áéíóúñÁÉÍÓÚÑ\s]/', '', $input['nombre']);
    $nombreLimpio = preg_replace('/\s+/', '-', trim($nombreLimpio));
    $nombreLimpio = strtolower($nombreLimpio);
    
    // Procesar imagen si existe
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $imageUploadDir = '../../sistema_apps_upload/recetas/images/';
        if (!is_dir($imageUploadDir)) {
            mkdir($imageUploadDir, 0777, true);
        }
        
        // Nombre: tipo-nombre.extension
        $extension = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
        $imageName = strtolower($input['tipo']) . '-' . $nombreLimpio . '.' . $extension;
        $imagePath = $imageUploadDir . $imageName;
        
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $imagePath)) {
            $imagenUrl = 'https://colisan.com/sistema_apps_upload/sistema_apps_upload/recetas/images/' . $imageName;
        }
    }
    
    // Procesar video si existe
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $videoUploadDir = '../../sistema_apps_upload/recetas/videos/';
        if (!is_dir($videoUploadDir)) {
            mkdir($videoUploadDir, 0777, true);
        }
        
        // Nombre: tipo-nombre.extension
        $extension = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
        $videoName = strtolower($input['tipo']) . '-' . $nombreLimpio . '.' . $extension;
        $videoPath = $videoUploadDir . $videoName;
        
        if (move_uploaded_file($_FILES['video']['tmp_name'], $videoPath)) {
            $videoUrl = 'https://colisan.com/sistema_apps_upload/sistema_apps_upload/recetas/videos/' . $videoName;
        }
    }
    
    // Insertar receta
    $sql = "INSERT INTO recetas (
        usuario_aplicacion_key,
        receta_nombre, 
        receta_tipo, 
        receta_ingredients, 
        receta_preparation, 
        receta_image, 
        receta_video, 
        receta_valoracion, 
        receta_saludable, 
        receta_tiempopreparacion, 
        receta_dificultad, 
        receta_porciones
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $usuario_aplicacion_key,
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
        $input['porciones'] ?? ''
    ]);
    
    if ($result) {
        $recipeId = $pdo->lastInsertId();
        
        logDebug("Receta creada", [
            'user_key' => $usuario_aplicacion_key,
            'recipe_id' => $recipeId,
            'nombre' => $input['nombre']
        ]);
        
        successResponse([
            'recipe_id' => $recipeId
        ], 'Receta creada exitosamente');
    } else {
        errorResponse('Error al crear receta', 500);
    }
    
} catch (Exception $e) {
    logDebug("Error creando receta", ['error' => $e->getMessage()]);
    errorResponse('Error al crear receta', 500);
}
?>
