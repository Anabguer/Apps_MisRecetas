<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

// Leer datos
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit();
}

// Conectar a BD real para obtener nombre correcto
$host = 'PMYSQL165.dns-servicio.com';
$dbname = '9606966_sistema_apps_db';
$username = 'sistema_apps_user';
$password_db = 'GestionUploadSistemaApps!';

if ($input['action'] === 'login') {
    if ($input['email'] === '1954amg@gmail.com' && !empty($input['password'])) {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password_db);
            $stmt = $pdo->prepare("SELECT nombre FROM usuarios_aplicaciones WHERE usuario_aplicacion_key = '1954amg@gmail.com_recetas'");
            $stmt->execute();
            $user = $stmt->fetch();
            
            $nombre = $user ? $user['nombre'] : 'Usuario';
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'token' => 'test_token_123',
                    'user' => [
                        'email' => '1954amg@gmail.com',
                        'nombre' => $nombre
                    ]
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'token' => 'test_token_123',
                    'user' => [
                        'email' => '1954amg@gmail.com',
                        'nombre' => 'Antonio Miguel Guerrero'
                    ]
                ]
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Credenciales inválidas']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}
?>
