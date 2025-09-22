<?php
// =====================================================
// SCRIPT RECEPTOR PARA HOSTALIA
// Este archivo debe subirse a: https://colisan/sistema_apps_upload/upload_handler.php
// =====================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

// Verificar que se recibieron los datos necesarios
if (!isset($_POST['user_key']) || !isset($_POST['sub_folder']) || !isset($_POST['file_name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Faltan parámetros requeridos']);
    exit();
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Error al recibir el archivo']);
    exit();
}

$userKey = $_POST['user_key'];
$subFolder = $_POST['sub_folder']; // 'imagenes' o 'videos'
$fileName = $_POST['file_name'];
$createFolders = isset($_POST['create_folders']) && $_POST['create_folders'];

$file = $_FILES['file'];

// Validar tipo de archivo
$allowedTypes = [
    'imagenes' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    'videos' => ['mp4', 'webm', 'ogg', 'mov']
];

$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if (!isset($allowedTypes[$subFolder]) || !in_array($fileExt, $allowedTypes[$subFolder])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tipo de archivo no permitido']);
    exit();
}

// Crear estructura de carpetas
$baseDir = __DIR__; // Directorio actual (sistema_apps_upload)
$userDir = $baseDir . '/' . $userKey;
$targetDir = $userDir . '/' . $subFolder;

if ($createFolders) {
    // Crear carpeta del usuario si no existe
    if (!is_dir($userDir)) {
        if (!mkdir($userDir, 0755, true)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'No se pudo crear la carpeta del usuario']);
            exit();
        }
    }
    
    // Crear subcarpeta (imagenes/videos) si no existe
    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0755, true)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'No se pudo crear la subcarpeta']);
            exit();
        }
    }
}

// Verificar que las carpetas existan
if (!is_dir($targetDir)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'La carpeta de destino no existe']);
    exit();
}

// Mover archivo subido
$targetFile = $targetDir . '/' . $fileName;

if (move_uploaded_file($file['tmp_name'], $targetFile)) {
    // Generar URL pública
    $publicUrl = 'https://colisan/sistema_apps_upload/sistema_apps_upload_media/' . $userKey . '/' . $subFolder . '/' . $fileName;
    
    echo json_encode([
        'success' => true,
        'message' => 'Archivo subido exitosamente',
        'url' => $publicUrl,
        'path' => $targetFile,
        'size' => filesize($targetFile)
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al mover el archivo']);
}

// Log para debugging (opcional)
error_log("Upload a Hostalia - Usuario: $userKey, Tipo: $subFolder, Archivo: $fileName");
?>
