<?php
// =====================================================
// API RECETAS - SUBIR ARCHIVOS
// Endpoint: https://colisan/sistema_apps_api/recetas/upload.php
// =====================================================

require_once 'config.php';

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    errorResponse('Método no permitido', 405);
}

// Validar token
$token = $_POST['token'] ?? '';
if (empty($token)) {
    errorResponse('Token requerido', 401);
}

// Decodificar token
$decoded = base64_decode($token);
$parts = explode(':', $decoded);

if (count($parts) !== 2) {
    errorResponse('Token inválido', 401);
}

$usuario_aplicacion_key = $parts[0];

// Verificar que se recibió un archivo
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    errorResponse('Error al recibir el archivo');
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
    errorResponse('Tipo de archivo no permitido. Solo imágenes (jpg, png, gif, webp) o videos (mp4, webm, ogg, mov).');
}

if ($fileSize > $maxFileSize) {
    errorResponse('El archivo es demasiado grande (máx. 50MB).');
}

// Verificar tipo MIME real
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($fileTmpName);

if ($isImage && !str_starts_with($mimeType, 'image/')) {
    errorResponse('El archivo de imagen no es un tipo MIME de imagen válido.');
}
if ($isVideo && !str_starts_with($mimeType, 'video/')) {
    errorResponse('El archivo de video no es un tipo MIME de video válido.');
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
    
    // Determinar subcarpeta (imagenes o videos)
    $subFolder = $isImage ? 'imagenes' : 'videos';
    
    // Generar nombre único para el archivo
    $timestamp = time();
    $randomId = uniqid();
    $newFileName = $timestamp . '_' . $randomId . '.' . $fileExt;
    
    // Subir archivo a Hostalia usando cURL
    $uploadSuccess = uploadToHostalia($fileTmpName, $usuario_aplicacion_key, $subFolder, $newFileName);
    
    if ($uploadSuccess) {
        // URL final del archivo
        $publicUrl = UPLOAD_BASE_URL . $usuario_aplicacion_key . '/' . $subFolder . '/' . $newFileName;
        
        logDebug("Archivo subido", [
            'user_key' => $usuario_aplicacion_key,
            'type' => $subFolder,
            'filename' => $newFileName,
            'size' => $fileSize
        ]);
        
        successResponse([
            'url' => $publicUrl,
            'filename' => $newFileName,
            'type' => $isImage ? 'image' : 'video',
            'size' => $fileSize
        ], 'Archivo subido exitosamente');
    } else {
        errorResponse('Error al subir archivo a Hostalia', 500);
    }
    
} catch (Exception $e) {
    logDebug("Error subiendo archivo", ['error' => $e->getMessage()]);
    errorResponse('Error al subir archivo', 500);
}

/**
 * Función para subir archivo a Hostalia usando cURL
 */
function uploadToHostalia($fileTmpName, $userKey, $subFolder, $fileName) {
    // Subir archivo usando cURL al script receptor en Hostalia
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://colisan.com/sistema_apps_upload/recetas/upload_handler.php");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'user_key' => $userKey,
        'sub_folder' => $subFolder,
        'file_name' => $fileName,
        'file' => new CURLFile($fileTmpName),
        'create_folders' => true
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        logDebug("Error cURL subiendo a Hostalia", ['error' => $error]);
        return false;
    }
    
    return $httpCode === 200;
}
?>
