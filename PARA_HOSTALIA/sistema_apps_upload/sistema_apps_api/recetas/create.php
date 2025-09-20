<?php
// =====================================================
// API RECETAS - CREAR RECETA
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

// Validar campos requeridos (SIN receta_id porque es nuevo)
$requiredFields = ['token', 'nombre', 'tipo', 'ingredientes', 'preparacion'];
foreach ($requiredFields as $field) {
    if (empty($input[$field])) {
        errorResponse("Campo requerido: $field");
    }
}

$token = $input['token'];

// Decodificar token (IGUAL QUE UPDATE.PHP)
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
    // Verificar que el usuario existe (IGUAL QUE UPDATE.PHP)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM usuarios_aplicaciones 
        WHERE usuario_aplicacion_key = ?
    ");
    $stmt->execute([$usuario_aplicacion_key]);
    $result = $stmt->fetch();
    
    if ($result['total'] == 0) {
        errorResponse('Usuario no encontrado', 404);
    }
    
    // Manejar archivos subidos (IGUAL QUE UPDATE.PHP)
    $imagenUrl = '';
    $videoUrl = '';
    
    // Limpiar nombre de receta para usar en archivo
    $nombreLimpio = preg_replace('/[^a-zA-Z0-9áéíóúñÁÉÍÓÚÑ\s]/', '', $input['nombre']);
    $nombreLimpio = preg_replace('/\s+/', '-', trim($nombreLimpio));
    $nombreLimpio = strtolower($nombreLimpio);
    
    // Procesar imagen nueva si existe
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $imageUploadDir = '../../sistema_apps_upload/recetas/images/';
        if (!is_dir($imageUploadDir)) {
            mkdir($imageUploadDir, 0777, true);
        }
        
        // Nombre: usuario_tipo-nombre.extension
        $extension = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
        $imageName = $usuario_aplicacion_key . '_' . strtolower($input['tipo']) . '-' . $nombreLimpio . '.' . $extension;
        $imagePath = $imageUploadDir . $imageName;
        
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $imagePath)) {
            $imagenUrl = 'https://colisan.com/sistema_apps_upload/sistema_apps_upload/recetas/images/' . $imageName;
        }
    }
    
    // Procesar video nuevo si existe
    if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
        $videoUploadDir = '../../sistema_apps_upload/recetas/videos/';
        if (!is_dir($videoUploadDir)) {
            mkdir($videoUploadDir, 0777, true);
        }
        
        // Nombre: usuario_tipo-nombre.extension
        $extension = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));
        $videoName = $usuario_aplicacion_key . '_' . strtolower($input['tipo']) . '-' . $nombreLimpio . '.' . $extension;
        $videoPath = $videoUploadDir . $videoName;
        
        if (move_uploaded_file($_FILES['video']['tmp_name'], $videoPath)) {
            $videoUrl = 'https://colisan.com/sistema_apps_upload/sistema_apps_upload/recetas/videos/' . $videoName;
        }
    }
    
    
    // Insertar receta (ESTRUCTURA SIMPLIFICADA)
    $sql = "INSERT INTO recetas (
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
        receta_porciones,
        usuario_aplicacion_key
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $input['nombre'],
        $input['tipo'],
        $input['ingredientes'],
        $input['preparacion'],
        $imagenUrl,
        $videoUrl,
        $valoracion,
        isset($input['saludable']) ? 1 : 0,  // Simplificado
        $input['tiempo'] ?? '',
        $input['dificultad'] ?? 'Fácil',
        $input['porciones'] ?? '',
        $usuario_aplicacion_key
    ]);
    
    if ($result) {
        $recipeId = $pdo->lastInsertId();
        
        
        echo json_encode([
            'success' => true,
            'message' => 'Receta creada exitosamente',
            'recipe_id' => $recipeId
        ]);
    } else {
        errorResponse('Error al crear receta', 500);
    }
    
} catch (Exception $e) {
    errorResponse('Error al crear receta: ' . $e->getMessage(), 500);
}
?>
