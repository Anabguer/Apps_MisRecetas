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
    echo json_encode(['success' => false, 'error' => 'Debes iniciar sesión para eliminar recetas']);
    exit();
}

// Obtener datos del JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de receta requerido']);
    exit();
}

$recipeId = $input['id'];

try {
    // Verificar que la receta existe y pertenece al usuario
    $stmt = $pdo->prepare("SELECT usuario_aplicacion_key, receta_nombre FROM recetas WHERE receta_id = ?");
    $stmt->execute([$recipeId]);
    $recipe = $stmt->fetch();
    
    if (!$recipe) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Receta no encontrada']);
        exit();
    }
    
    if ($recipe['usuario_aplicacion_key'] != getCurrentUserKey()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'No tienes permisos para eliminar esta receta']);
        exit();
    }
    
    // Eliminar la receta
    $stmt = $pdo->prepare("DELETE FROM recetas WHERE receta_id = ? AND usuario_aplicacion_key = ?");
    $result = $stmt->execute([$recipeId, getCurrentUserKey()]);
    
    if ($result) {
        echo json_encode([
            'success' => true, 
            'message' => 'Receta "' . $recipe['receta_nombre'] . '" eliminada exitosamente'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error al eliminar la receta']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>
