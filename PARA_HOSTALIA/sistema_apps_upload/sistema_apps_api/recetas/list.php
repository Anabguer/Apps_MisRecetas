<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Configuración BD Hostalia
$host = 'PMYSQL165.dns-servicio.com';
$dbname = '9606966_sistema_apps_db';
$username = 'sistema_apps_user';
$password = 'GestionUploadSistemaApps!';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Obtener recetas del usuario con todos los campos
    $stmt = $pdo->prepare("
        SELECT receta_id, receta_nombre, receta_tipo, receta_image, receta_video, 
               receta_valoracion, receta_saludable, receta_dificultad, 
               receta_tiempopreparacion, receta_porciones, receta_ingredients, receta_preparation
        FROM recetas 
        WHERE usuario_aplicacion_key = '1954amg@gmail.com_recetas'
        ORDER BY 
            CASE receta_tipo 
                WHEN 'Entrante' THEN 1
                WHEN 'Principal' THEN 2
                WHEN 'Postre' THEN 3
                WHEN 'Bebida' THEN 4
                WHEN 'Extra' THEN 5
            END,
            receta_nombre ASC
    ");
    $stmt->execute();
    $recetas = $stmt->fetchAll();
    
    // Procesar datos para la aplicación
    foreach ($recetas as &$receta) {
        // Convertir saludable a boolean
        $receta['receta_saludable'] = (bool)$receta['receta_saludable'];
        
        // Manejar valoración correctamente (0 = sin valorar, no 5)
        $receta['receta_valoracion'] = (int)$receta['receta_valoracion'];
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'recetas' => $recetas,
            'total' => count($recetas)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>
