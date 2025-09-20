<?php
header('Content-Type: application/json');
require_once '../config/app_config.php';
require_once '../config/database.php';
require_once '../includes/auth_final.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Debes iniciar sesión para editar recetas']);
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
$requiredFields = ['id', 'nombre', 'tipo', 'ingredientes', 'preparacion'];
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

try {
    // Verificar que la receta existe y pertenece al usuario
    $stmt = $pdo->prepare("SELECT usuario_id FROM recetas WHERE receta_id = ?");
    $stmt->execute([$input['id']]);
    $recipe = $stmt->fetch();
    
    if (!$recipe) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Receta no encontrada']);
        exit();
    }
    
    if ($recipe['usuario_aplicacion_key'] != getCurrentUserKey()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'No tienes permisos para editar esta receta']);
        exit();
    }
    
    // Actualizar la receta
    $sql = "UPDATE recetas SET 
        receta_nombre = ?, 
        receta_tipo = ?, 
        receta_ingredients = ?, 
        receta_preparation = ?, 
        receta_image = ?, 
        receta_valoracion = ?, 
        receta_saludable = ?, 
        receta_tiempopreparacion = ?, 
        receta_dificultad = ?, 
        receta_porciones = ?, 
        receta_video = ?,
        fecha_modificacion = CURRENT_TIMESTAMP
        WHERE receta_id = ? AND usuario_aplicacion_key = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
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
        $input['enlace_video'] ?? '',
        $input['id'],
        getCurrentUserKey()
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Receta actualizada exitosamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error al actualizar la receta']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>
