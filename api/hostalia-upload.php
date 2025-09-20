<?php
// =====================================================
// SISTEMA DE UPLOADS PARA HOSTALIA
// Gestión de imágenes y videos por usuario
// =====================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/app_config.php';
require_once '../config/database.php';
require_once '../includes/auth_final.php';

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

// Verificar autenticación
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Debes iniciar sesión para subir archivos']);
    exit();
}

$userKey = getCurrentUserKey();

// Configuración Hostalia
$HOSTALIA_UPLOAD_BASE = 'https://colisan/sistema_apps_upload/';

// Verificar que se recibió un archivo
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Error al recibir el archivo']);
    exit();
}

$file = $_FILES['file'];
$fileName = $file['name'];
$fileTmpName = $file['tmp_name'];
$fileSize = $file['size'];
$fileType = $file['type'];

$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Definir tipos y tamaños permitidos
$allowedImageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$allowedVideoExts = ['mp4', 'webm', 'ogg', 'mov'];
$maxFileSize = 50 * 1024 * 1024; // 50MB

$isImage = in_array($fileExt, $allowedImageExts);
$isVideo = in_array($fileExt, $allowedVideoExts);

if (!$isImage && !$isVideo) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tipo de archivo no permitido. Solo imágenes (jpg, png, gif, webp) o videos (mp4, webm, ogg, mov).']);
    exit();
}

if ($fileSize > $maxFileSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El archivo es demasiado grande (máx. 50MB).']);
    exit();
}

// Verificar tipo MIME real
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($fileTmpName);

if ($isImage && !str_starts_with($mimeType, 'image/')) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El archivo de imagen no es un tipo MIME de imagen válido.']);
    exit();
}
if ($isVideo && !str_starts_with($mimeType, 'video/')) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'El archivo de video no es un tipo MIME de video válido.']);
    exit();
}

// Determinar subcarpeta (imagenes o videos)
$subFolder = $isImage ? 'imagenes' : 'videos';

// Crear estructura de carpetas en Hostalia
$userFolder = $HOSTALIA_UPLOAD_BASE . $userKey . '/';
$targetFolder = $userFolder . $subFolder . '/';

// Función para crear carpetas via HTTP (si tienes API)
function createFolderIfNotExists($folderPath) {
    // IMPORTANTE: Aquí necesitarás implementar la creación de carpetas
    // según el método que proporcione tu hosting Hostalia
    // Puede ser vía FTP, API REST, o script PHP en el servidor
    
    // Por ahora, asumimos que las carpetas se crean automáticamente
    // o que tienes un script en Hostalia que las crea
    return true;
}

// Generar nombre único para el archivo
$timestamp = time();
$randomId = uniqid();
$newFileName = $timestamp . '_' . $randomId . '.' . $fileExt;

// URL final del archivo
$publicUrl = $targetFolder . $newFileName;

// AQUÍ NECESITAS IMPLEMENTAR LA SUBIDA A HOSTALIA
// Opciones:
// 1. FTP upload
// 2. cURL POST a script PHP en Hostalia
// 3. API REST de tu hosting

// EJEMPLO CON cURL (necesitarás un script receptor en Hostalia)
$uploadSuccess = uploadToHostalia($fileTmpName, $publicUrl, $userKey, $subFolder, $newFileName);

if ($uploadSuccess) {
    echo json_encode([
        'success' => true, 
        'message' => 'Archivo subido exitosamente a Hostalia',
        'url' => $publicUrl,
        'type' => $isImage ? 'image' : 'video',
        'size' => $fileSize
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al subir archivo a Hostalia']);
}

/**
 * Función para subir archivo a Hostalia usando cURL
 */
function uploadToHostalia($fileTmpName, $publicUrl, $userKey, $subFolder, $fileName) {
    // Crear script receptor en Hostalia si no existe
    createUploadHandlerScript();
    
    // Subir archivo usando cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://colisan/sistema_apps_upload/upload_handler.php");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'user_key' => $userKey,
        'sub_folder' => $subFolder,
        'file_name' => $fileName,
        'file' => new CURLFile($fileTmpName),
        'create_folders' => true
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("Error cURL subiendo a Hostalia: " . $error);
        return false;
    }
    
    return $httpCode === 200;
}

/**
 * Crear script receptor en Hostalia (solo una vez)
 */
function createUploadHandlerScript() {
    // Esta función crearía el script upload_handler.php en Hostalia
    // Por ahora, asumimos que ya existe o lo crearemos manualmente
    return true;
}
?>
