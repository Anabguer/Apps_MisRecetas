<?php
header('Content-Type: application/json');
require_once '../config/app_config.php';
require_once '../config/database.php';
require_once '../includes/auth_final.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Debes iniciar sesión']);
    exit();
}

$recipeId = $_GET['id'] ?? '';

if (empty($recipeId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de receta requerido']);
    exit();
}

try {
    // Solo permitir ver recetas del usuario logueado
    $stmt = $pdo->prepare("SELECT * FROM recetas WHERE receta_id = ? AND usuario_aplicacion_key = ?");
    $stmt->execute([$recipeId, getCurrentUserKey()]);
    $recipe = $stmt->fetch();
    
    if (!$recipe) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Receta no encontrada']);
        exit();
    }
    
    echo json_encode(['success' => true, 'recipe' => $recipe]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>
