<?php
// =====================================================
// API RECETAS - AUTENTICACIÓN CON REGISTRO Y VERIFICACIÓN
// Endpoint: https://colisan.com/sistema_apps_api/recetas/auth_register.php
// =====================================================

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

// Configuración de base de datos
$host = 'PMYSQL165.dns-servicio.com';
$dbname = '9606966_sistema_apps_db';
$username = 'sistema_apps_user';
$password_db = 'GestionUploadSistemaApps!';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
    exit();
}

// =====================================================
// FUNCIONES DE UTILIDAD
// =====================================================

function sendVerificationEmail($email, $code, $name) {
    $subject = "Código de verificación - Mis Recetas";
    $message = "
    <html>
    <head>
        <title>Verificación de Email - Mis Recetas</title>
    </head>
    <body>
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
            <div style='text-align: center; margin-bottom: 30px;'>
                <div style='font-size: 3rem; margin-bottom: 10px;'>🍃</div>
                <h2 style='color: #22c55e; margin: 0;'>Mis Recetas</h2>
            </div>
            
            <h3>¡Hola $name!</h3>
            
            <p>Gracias por registrarte en <strong>Mis Recetas</strong>. Para completar tu registro, necesitamos verificar tu dirección de email.</p>
            
            <div style='background: #f8f9fa; border: 2px solid #22c55e; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0;'>
                <h2 style='margin: 0; color: #22c55e; font-size: 2rem; letter-spacing: 3px;'>$code</h2>
                <p style='margin: 10px 0 0 0; color: #6b7280;'>Código de verificación</p>
            </div>
            
            <p><strong>Instrucciones:</strong></p>
            <ol>
                <li>Vuelve a la aplicación</li>
                <li>Introduce este código de 6 dígitos</li>
                <li>¡Listo! Ya podrás iniciar sesión</li>
            </ol>
            
            <p style='color: #6b7280; font-size: 0.9rem;'>
                <strong>Nota:</strong> Este código expira en 15 minutos por seguridad.
                Si no solicitaste este registro, puedes ignorar este email.
            </p>
            
            <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;'>
            
            <p style='text-align: center; color: #6b7280; font-size: 0.8rem;'>
                Este email fue enviado desde <strong>Mis Recetas</strong><br>
                Sistema de gestión de recetas personales
            </p>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Mis Recetas <noreply@colisan.com>" . "\r\n";
    
    return mail($email, $subject, $message, $headers);
}

function generateVerificationCode() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// =====================================================
// MANEJO DE ACCIONES
// =====================================================

$action = $input['action'] ?? '';

switch ($action) {
    
    // ===== REGISTRO DE USUARIO =====
    case 'register':
        $name = trim($input['name'] ?? '');
        $email = trim(strtolower($input['email'] ?? ''));
        $password = $input['password'] ?? '';
        
        // Validaciones
        if (empty($name) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Todos los campos son obligatorios']);
            exit();
        }
        
        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres']);
            exit();
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Email no válido']);
            exit();
        }
        
        // Verificar si el email ya existe
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios_aplicaciones WHERE email = ? AND app_codigo = 'recetas'");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'error' => 'Este email ya está registrado']);
            exit();
        }
        
        // Generar código de verificación
        $verification_code = generateVerificationCode();
        $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        // Crear usuario pendiente de verificación
        $usuario_aplicacion_key = $email . '_recetas';
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO usuarios_aplicaciones 
            (usuario_aplicacion_key, email, nombre, password_hash, app_codigo, activo, verification_code, verification_expiry, created_at) 
            VALUES (?, ?, ?, ?, 'recetas', 0, ?, ?, NOW())
        ");
        
        if ($stmt->execute([$usuario_aplicacion_key, $email, $name, $password_hash, $verification_code, $expiry])) {
            // Enviar email de verificación
            if (sendVerificationEmail($email, $verification_code, $name)) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Registro exitoso. Código de verificación enviado a tu email.'
                ]);
            } else {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Registro exitoso. Código de verificación: ' . $verification_code . ' (Error de email)'
                ]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al crear la cuenta']);
        }
        break;
    
    // ===== VERIFICACIÓN DE EMAIL =====
    case 'verify':
        $email = trim(strtolower($input['email'] ?? ''));
        $code = trim($input['code'] ?? '');
        
        if (empty($email) || empty($code)) {
            echo json_encode(['success' => false, 'error' => 'Email y código son obligatorios']);
            exit();
        }
        
        // Buscar usuario con código válido
        $stmt = $pdo->prepare("
            SELECT usuario_aplicacion_key 
            FROM usuarios_aplicaciones 
            WHERE email = ? AND app_codigo = 'recetas' 
            AND verification_code = ? AND verification_expiry > NOW() 
            AND activo = 0
        ");
        $stmt->execute([$email, $code]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Activar usuario
            $stmt = $pdo->prepare("
                UPDATE usuarios_aplicaciones 
                SET activo = 1, verification_code = NULL, verification_expiry = NULL, verified_at = NOW()
                WHERE email = ? AND app_codigo = 'recetas'
            ");
            
            if ($stmt->execute([$email])) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Email verificado correctamente. Ya puedes iniciar sesión.'
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error al verificar la cuenta']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Código de verificación inválido o expirado']);
        }
        break;
    
    // ===== REENVIAR CÓDIGO =====
    case 'resend_code':
        $email = trim(strtolower($input['email'] ?? ''));
        
        if (empty($email)) {
            echo json_encode(['success' => false, 'error' => 'Email es obligatorio']);
            exit();
        }
        
        // Buscar usuario no verificado
        $stmt = $pdo->prepare("
            SELECT nombre 
            FROM usuarios_aplicaciones 
            WHERE email = ? AND app_codigo = 'recetas' AND activo = 0
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generar nuevo código
            $verification_code = generateVerificationCode();
            $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            $stmt = $pdo->prepare("
                UPDATE usuarios_aplicaciones 
                SET verification_code = ?, verification_expiry = ?
                WHERE email = ? AND app_codigo = 'recetas'
            ");
            
            if ($stmt->execute([$verification_code, $expiry, $email])) {
                if (sendVerificationEmail($email, $verification_code, $user['nombre'])) {
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Nuevo código de verificación enviado'
                    ]);
                } else {
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Nuevo código: ' . $verification_code . ' (Error de email)'
                    ]);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Error al generar nuevo código']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Usuario no encontrado o ya verificado']);
        }
        break;
    
    // ===== LOGIN MEJORADO =====
    case 'login':
        $email = trim(strtolower($input['email'] ?? ''));
        $password = $input['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Email y contraseña son obligatorios']);
            exit();
        }
        
        // Buscar usuario activo
        $stmt = $pdo->prepare("
            SELECT usuario_aplicacion_key, nombre, password_hash, activo 
            FROM usuarios_aplicaciones 
            WHERE email = ? AND app_codigo = 'recetas'
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            if ($user['activo'] == 0) {
                echo json_encode(['success' => false, 'error' => 'Debes verificar tu email antes de iniciar sesión']);
                exit();
            }
            
            if (password_verify($password, $user['password_hash'])) {
                // Generar token
                $timestamp = time();
                $token_data = $user['usuario_aplicacion_key'] . ':' . $timestamp;
                $token = base64_encode($token_data);
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'token' => $token,
                        'user' => [
                            'email' => $email,
                            'nombre' => $user['nombre']
                        ]
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Contraseña incorrecta']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
        }
        break;
    
    default:
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        break;
}
?>
