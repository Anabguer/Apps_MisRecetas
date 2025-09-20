<?php
// =====================================================
// UPLOAD SEGURO DE ARCHIVOS
// Protección contra archivos maliciosos
// =====================================================

header('Content-Type: application/json');
require_once '../config/app_config.php';
require_once '../config/database.php';
require_once '../includes/auth_final.php';
require_once '../includes/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

// Verificar autenticación
if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Debes iniciar sesión']);
    exit();
}

$userKey = getCurrentUserKey();

// Verificar rate limiting
$rateLimitCheck = Security::checkRateLimit($userKey, 'file_upload', 10, 300); // 10 archivos por 5 minutos
if (!$rateLimitCheck['allowed']) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => $rateLimitCheck['error']]);
    exit();
}

// Verificar que se subió un archivo
if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No se subió ningún archivo']);
    exit();
}

$file = $_FILES['file'];
$fileType = $_POST['type'] ?? 'image'; // 'image' o 'video'

// Validar archivo
$validation = Security::validateUploadedFile($file, $fileType);
if (!$validation['valid']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $validation['error']]);
    exit();
}

try {
    // Crear directorio del usuario si no existe
    $userUploadDir = 'uploads/' . $userKey . '/';
    if (!is_dir($userUploadDir)) {
        if (!mkdir($userUploadDir, 0755, true)) {
            throw new Exception('No se pudo crear el directorio de uploads');
        }
    }
    
    // Generar nombre de archivo seguro
    $secureFilename = Security::generateSecureFilename($file['name'], $userKey);
    $targetPath = $userUploadDir . $secureFilename;
    
    // Verificar que no existe un archivo con el mismo nombre
    if (file_exists($targetPath)) {
        $secureFilename = Security::generateSecureFilename($file['name'] . '_' . rand(100, 999), $userKey);
        $targetPath = $userUploadDir . $secureFilename;
    }
    
    // Mover archivo a ubicación segura
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception('Error al mover el archivo');
    }
    
    // Generar URL completa
    $fileUrl = getUploadBaseUrl() . $userKey . '/' . $secureFilename;
    
    // Log de seguridad
    Security::logSecurityEvent('file_uploaded', $userKey, [
        'filename' => $secureFilename,
        'type' => $fileType,
        'size' => $file['size']
    ]);
    
    echo json_encode([
        'success' => true,
        'url' => $fileUrl,
        'filename' => $secureFilename,
        'message' => 'Archivo subido exitosamente'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al subir archivo: ' . $e->getMessage()]);
    
    // Log de error
    Security::logSecurityEvent('file_upload_error', $userKey, [
        'error' => $e->getMessage(),
        'filename' => $file['name'] ?? 'unknown'
    ]);
}
?>
