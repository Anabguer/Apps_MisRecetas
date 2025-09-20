<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/auth_final.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false, 
        'error' => 'Método no permitido'
    ]);
    exit();
}

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Debes iniciar sesión para crear recetas']);
    exit();
}

// Obtener datos del JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit();
}

// Validar campos requeridos
$requiredFields = ['nombre', 'tipo', 'ingredientes', 'preparacion'];
foreach ($requiredFields as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "El campo $field es requerido"]);
        exit();
    }
}

// Validar tipo de receta
$tiposValidos = ['Entrante', 'Principal', 'Postre', 'Bebida', 'Extra'];
if (!in_array($input['tipo'], $tiposValidos)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tipo de receta no válido']);
    exit();
}

// Sanitizar datos básicos
$input['nombre'] = htmlspecialchars(trim($input['nombre']), ENT_QUOTES, 'UTF-8');
$input['ingredientes'] = htmlspecialchars(trim($input['ingredientes']), ENT_QUOTES, 'UTF-8');
$input['preparacion'] = htmlspecialchars(trim($input['preparacion']), ENT_QUOTES, 'UTF-8');

try {
    // Preparar datos para inserción
    $sql = "INSERT INTO recetas (
        usuario_aplicacion_key,
        receta_nombre, 
        receta_tipo, 
        receta_ingredients, 
        receta_preparation, 
        receta_image, 
        receta_valoracion, 
        receta_saludable, 
        receta_tiempopreparacion, 
        receta_dificultad, 
        receta_porciones, 
        receta_video
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        getCurrentUserKey(),
        $input['nombre'],
        $input['tipo'],
        $input['ingredientes'],
        $input['preparacion'],
        $input['imagen'] ?? '',
        $input['valoracion'] ?? 5,
        $input['saludable'] ?? 0,
        $input['tiempo'] ?? '',
        $input['dificultad'] ?? 'Fácil',
        $input['porciones'] ?? '',
        $input['enlace_video'] ?? ''
    ]);
    
    if ($result) {
        $recipeId = $pdo->lastInsertId();
        echo json_encode([
            'success' => true, 
            'message' => 'Receta creada exitosamente',
            'recipe_id' => $recipeId
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error al crear la receta']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>
