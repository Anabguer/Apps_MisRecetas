<?php
// =====================================================
// SISTEMA DE SEGURIDAD REFORZADO
// Protección contra ataques comunes
// =====================================================

class Security {
    
    /**
     * Sanitizar entrada de texto para prevenir XSS
     */
    public static function sanitizeText($input, $maxLength = 1000) {
        if (empty($input)) return '';
        
        // Eliminar caracteres peligrosos
        $input = trim($input);
        $input = strip_tags($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        // Limitar longitud
        if (strlen($input) > $maxLength) {
            $input = substr($input, 0, $maxLength);
        }
        
        return $input;
    }
    
    /**
     * Sanitizar HTML preservando saltos de línea para textareas
     */
    public static function sanitizeTextarea($input, $maxLength = 5000) {
        if (empty($input)) return '';
        
        $input = trim($input);
        // Permitir saltos de línea pero eliminar HTML
        $input = strip_tags($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        // Limitar longitud
        if (strlen($input) > $maxLength) {
            $input = substr($input, 0, $maxLength);
        }
        
        return $input;
    }
    
    /**
     * Validar email
     */
    public static function validateEmail($email) {
        $email = trim($email);
        
        if (empty($email)) {
            return ['valid' => false, 'error' => 'Email es requerido'];
        }
        
        if (strlen($email) > 255) {
            return ['valid' => false, 'error' => 'Email demasiado largo'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'error' => 'Email no válido'];
        }
        
        // Verificar dominios peligrosos
        $dangerousDomains = ['tempmail.org', '10minutemail.com', 'guerrillamail.com'];
        $domain = substr(strrchr($email, "@"), 1);
        if (in_array($domain, $dangerousDomains)) {
            return ['valid' => false, 'error' => 'Dominio de email no permitido'];
        }
        
        return ['valid' => true, 'email' => strtolower($email)];
    }
    
    /**
     * Validar contraseña
     */
    public static function validatePassword($password) {
        if (empty($password)) {
            return ['valid' => false, 'error' => 'Contraseña es requerida'];
        }
        
        if (strlen($password) < 6) {
            return ['valid' => false, 'error' => 'Contraseña debe tener al menos 6 caracteres'];
        }
        
        if (strlen($password) > 128) {
            return ['valid' => false, 'error' => 'Contraseña demasiado larga'];
        }
        
        // Verificar contraseñas comunes
        $commonPasswords = ['123456', 'password', 'admin', 'qwerty', '123456789'];
        if (in_array(strtolower($password), $commonPasswords)) {
            return ['valid' => false, 'error' => 'Contraseña demasiado común, usa una más segura'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Validar tipo de receta
     */
    public static function validateRecipeType($tipo) {
        $tiposValidos = ['Entrante', 'Principal', 'Postre', 'Bebida', 'Extra'];
        
        if (empty($tipo)) {
            return ['valid' => false, 'error' => 'Tipo de receta es requerido'];
        }
        
        if (!in_array($tipo, $tiposValidos)) {
            return ['valid' => false, 'error' => 'Tipo de receta no válido'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Validar valoración
     */
    public static function validateRating($valoracion) {
        $rating = (int)$valoracion;
        
        if ($rating < 1 || $rating > 5) {
            return ['valid' => false, 'error' => 'Valoración debe estar entre 1 y 5'];
        }
        
        return ['valid' => true, 'rating' => $rating];
    }
    
    /**
     * Validar archivo subido
     */
    public static function validateUploadedFile($file, $type = 'image') {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'Error al subir archivo'];
        }
        
        // Verificar tamaño
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $maxSize) {
            return ['valid' => false, 'error' => 'Archivo demasiado grande (máximo 10MB)'];
        }
        
        // Verificar tipo MIME
        $allowedTypes = [];
        if ($type === 'image') {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        } elseif ($type === 'video') {
            $allowedTypes = ['video/mp4', 'video/webm', 'video/quicktime'];
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return ['valid' => false, 'error' => 'Tipo de archivo no permitido'];
        }
        
        // Verificar extensión
        $allowedExtensions = [];
        if ($type === 'image') {
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        } elseif ($type === 'video') {
            $allowedExtensions = ['mp4', 'webm', 'mov'];
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            return ['valid' => false, 'error' => 'Extensión de archivo no permitida'];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Generar nombre de archivo seguro
     */
    public static function generateSecureFilename($originalName, $userKey) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $hash = hash('sha256', $userKey . time() . rand(1000, 9999));
        return substr($hash, 0, 20) . '_' . time() . '.' . $extension;
    }
    
    /**
     * Validar URL
     */
    public static function validateUrl($url) {
        if (empty($url)) return ['valid' => true, 'url' => '']; // URL vacía es válida
        
        if (strlen($url) > 500) {
            return ['valid' => false, 'error' => 'URL demasiado larga'];
        }
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['valid' => false, 'error' => 'URL no válida'];
        }
        
        // Verificar que sea HTTPS (más seguro)
        if (!str_starts_with($url, 'https://')) {
            return ['valid' => false, 'error' => 'Solo se permiten URLs HTTPS'];
        }
        
        return ['valid' => true, 'url' => $url];
    }
    
    /**
     * Verificar límite de rate limiting por usuario
     */
    public static function checkRateLimit($userKey, $action, $maxAttempts = 10, $timeWindow = 300) {
        // Implementación simple usando archivos (en producción usar Redis/Memcached)
        $rateLimitFile = sys_get_temp_dir() . '/rate_limit_' . hash('sha256', $userKey . $action) . '.txt';
        
        $attempts = [];
        if (file_exists($rateLimitFile)) {
            $content = file_get_contents($rateLimitFile);
            $attempts = json_decode($content, true) ?: [];
        }
        
        // Limpiar intentos antiguos
        $currentTime = time();
        $attempts = array_filter($attempts, function($timestamp) use ($currentTime, $timeWindow) {
            return ($currentTime - $timestamp) < $timeWindow;
        });
        
        // Verificar límite
        if (count($attempts) >= $maxAttempts) {
            return ['allowed' => false, 'error' => 'Demasiados intentos. Espera unos minutos.'];
        }
        
        // Registrar nuevo intento
        $attempts[] = $currentTime;
        file_put_contents($rateLimitFile, json_encode($attempts));
        
        return ['allowed' => true];
    }
    
    /**
     * Verificar token CSRF
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Limpiar logs de seguridad
     */
    public static function logSecurityEvent($event, $userKey = null, $details = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'user_key' => $userKey,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ];
        
        $logFile = __DIR__ . '/../logs/security.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Verificar permisos de archivo
     */
    public static function checkFilePermissions($filePath, $userKey) {
        // Verificar que el archivo pertenece al usuario
        if (strpos($filePath, $userKey) === false) {
            return ['allowed' => false, 'error' => 'No tienes permisos para acceder a este archivo'];
        }
        
        // Verificar que está en la carpeta de uploads
        $uploadsPath = realpath(__DIR__ . '/../uploads/');
        $fullPath = realpath($filePath);
        
        if (!$fullPath || strpos($fullPath, $uploadsPath) !== 0) {
            return ['allowed' => false, 'error' => 'Ruta de archivo no válida'];
        }
        
        return ['allowed' => true];
    }
}

// =====================================================
// FUNCIONES DE CONVENIENCIA
// =====================================================

/**
 * Sanitizar datos de entrada de formularios
 */
function sanitizeFormData($data) {
    $sanitized = [];
    
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $sanitized[$key] = sanitizeFormData($value);
        } else {
            switch ($key) {
                case 'email':
                    $result = Security::validateEmail($value);
                    $sanitized[$key] = $result['valid'] ? $result['email'] : '';
                    break;
                    
                case 'nombre':
                    $sanitized[$key] = Security::sanitizeText($value, 255);
                    break;
                    
                case 'receta_nombre':
                    $sanitized[$key] = Security::sanitizeText($value, 255);
                    break;
                    
                case 'ingredientes':
                case 'preparacion':
                    $sanitized[$key] = Security::sanitizeTextarea($value, 5000);
                    break;
                    
                case 'tipo':
                    $result = Security::validateRecipeType($value);
                    $sanitized[$key] = $result['valid'] ? $value : '';
                    break;
                    
                case 'valoracion':
                    $result = Security::validateRating($value);
                    $sanitized[$key] = $result['valid'] ? $result['rating'] : 5;
                    break;
                    
                case 'enlace_video':
                case 'receta_video':
                    $result = Security::validateUrl($value);
                    $sanitized[$key] = $result['valid'] ? $result['url'] : '';
                    break;
                    
                default:
                    $sanitized[$key] = Security::sanitizeText($value, 500);
            }
        }
    }
    
    return $sanitized;
}

/**
 * Verificar si una IP está en lista negra
 */
function isBlacklisted($ip) {
    $blacklistedIPs = [
        // Añadir IPs problemáticas aquí
        // '192.168.1.100',
        // '10.0.0.50'
    ];
    
    return in_array($ip, $blacklistedIPs);
}

/**
 * Headers de seguridad
 */
function setSecurityHeaders() {
    // Prevenir clickjacking
    header('X-Frame-Options: SAMEORIGIN');
    
    // Prevenir MIME sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Prevenir XSS
    header('X-XSS-Protection: 1; mode=block');
    
    // Política de contenido estricta
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' https://fonts.gstatic.com; connect-src 'self'");
    
    // Forzar HTTPS en producción
    if (!isset($_SERVER['HTTPS']) && $_SERVER['HTTP_HOST'] !== 'localhost') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    
    // Política de referrer
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

/**
 * Verificar integridad de sesión
 */
function validateSession() {
    // Regenerar ID de sesión periódicamente
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutos
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    // Verificar IP del usuario (opcional, puede causar problemas con proxies)
    if (isset($_SESSION['user_ip'])) {
        if ($_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) {
            // Log de seguridad
            Security::logSecurityEvent('session_ip_mismatch', $_SESSION['usuario_key'] ?? null);
        }
    } else {
        $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Aplicar todas las protecciones de seguridad
 */
function applySecurity() {
    // Verificar IP en lista negra
    $userIP = $_SERVER['REMOTE_ADDR'] ?? '';
    if (isBlacklisted($userIP)) {
        http_response_code(403);
        die('Acceso denegado');
    }
    
    // Aplicar headers de seguridad
    setSecurityHeaders();
    
    // Validar sesión si existe
    if (session_status() === PHP_SESSION_ACTIVE) {
        validateSession();
    }
    
    // Log de acceso
    if (isset($_SESSION['usuario_key'])) {
        Security::logSecurityEvent('page_access', $_SESSION['usuario_key'], [
            'page' => $_SERVER['REQUEST_URI'],
            'method' => $_SERVER['REQUEST_METHOD']
        ]);
    }
}

// =====================================================
// AUTO-APLICAR SEGURIDAD
// =====================================================

// Aplicar protecciones automáticamente al incluir este archivo
applySecurity();
?>
