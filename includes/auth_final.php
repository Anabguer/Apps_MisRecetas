<?php
// =====================================================
// SISTEMA DE AUTENTICACIÓN FINAL
// Para aplicaciones móviles con usuario-aplicación
// =====================================================

session_start();

// Definir APP_CODIGO aquí temporalmente para evitar errores
if (!defined('APP_CODIGO')) {
    define('APP_CODIGO', 'recetas');
}

// Función helper para generar user key
if (!function_exists('generateUserAppKey')) {
    function generateUserAppKey($email, $app_codigo = null) {
        $app_codigo = $app_codigo ?? APP_CODIGO;
        return $email . '_' . $app_codigo;
    }
}

class AuthFinal {
    private $pdo;
    private $app_codigo;
    
    public function __construct($pdo, $app_codigo = null) {
        $this->pdo = $pdo;
        $this->app_codigo = $app_codigo ?? APP_CODIGO; // Usa la configuración por defecto
    }
    
    /**
     * Registrar nuevo usuario en la aplicación específica
     */
    public function registerUser($email, $nombre, $password) {
        try {
            // 1. Crear usuario_aplicacion_key usando función helper
            $usuario_aplicacion_key = generateUserAppKey($email, $this->app_codigo);
            
            // 2. Verificar si ya está registrado en esta aplicación
            $stmt = $this->pdo->prepare("SELECT usuario_aplicacion_key FROM usuarios_aplicaciones WHERE usuario_aplicacion_key = ?");
            $stmt->execute([$usuario_aplicacion_key]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'Ya estás registrado en esta aplicación'];
            }
            
            // 3. Crear registro en usuarios_aplicaciones
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("
                INSERT INTO usuarios_aplicaciones (usuario_aplicacion_key, email, nombre, password_hash, app_codigo) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$usuario_aplicacion_key, $email, $nombre, $password_hash, $this->app_codigo]);
            
            return [
                'success' => true, 
                'usuario_aplicacion_key' => $usuario_aplicacion_key
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Error al registrar usuario: ' . $e->getMessage()];
        }
    }
    
    /**
     * Login de usuario en aplicación específica
     */
    public function loginUser($email, $password) {
        try {
            // 1. Buscar directamente en usuarios_aplicaciones para esta app
            $usuario_aplicacion_key = generateUserAppKey($email, $this->app_codigo);
            $stmt = $this->pdo->prepare("
                SELECT usuario_aplicacion_id, usuario_aplicacion_key, email, nombre, password_hash 
                FROM usuarios_aplicaciones 
                WHERE usuario_aplicacion_key = ? AND activo = 1
            ");
            $stmt->execute([$usuario_aplicacion_key]);
            $userApp = $stmt->fetch();
            
            if (!$userApp || !password_verify($password, $userApp['password_hash'])) {
                // 2. Verificar si el usuario existe en otras aplicaciones
                $stmt = $this->pdo->prepare("
                    SELECT usuario_aplicacion_id, email, nombre 
                    FROM usuarios_aplicaciones 
                    WHERE email = ? AND activo = 1
                    LIMIT 1
                ");
                $stmt->execute([$email]);
                $existingUser = $stmt->fetch();
                
                if ($existingUser && password_verify($password, $userApp['password_hash'] ?? '')) {
                    return [
                        'success' => false, 
                        'error' => 'No estás registrado en esta aplicación',
                        'need_app_registration' => true,
                        'email' => $email
                    ];
                } else {
                    return ['success' => false, 'error' => 'Credenciales inválidas'];
                }
            }
            
            // 3. Actualizar último acceso
            $stmt = $this->pdo->prepare("
                UPDATE usuarios_aplicaciones 
                SET ultimo_acceso = CURRENT_TIMESTAMP 
                WHERE usuario_aplicacion_key = ?
            ");
            $stmt->execute([$usuario_aplicacion_key]);
            
            // 4. Crear sesión
            $_SESSION['user_id'] = $userApp['usuario_aplicacion_id'];
            $_SESSION['user_name'] = $userApp['nombre'];
            $_SESSION['user_email'] = $userApp['email'];
            $_SESSION['usuario_key'] = $usuario_aplicacion_key;
            $_SESSION['app_codigo'] = $this->app_codigo;
            
            return [
                'success' => true,
                'usuario_aplicacion_id' => $userApp['usuario_aplicacion_id'],
                'nombre' => $userApp['nombre'],
                'usuario_key' => $usuario_aplicacion_key
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Error de conexión: ' . $e->getMessage()];
        }
    }
    
    /**
     * Registrar usuario existente en una nueva aplicación
     */
    public function registerInApp($usuario_id) {
        try {
            // Obtener email del usuario
            $stmt = $this->pdo->prepare("SELECT email FROM usuarios WHERE usuario_id = ?");
            $stmt->execute([$usuario_id]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'error' => 'Usuario no encontrado'];
            }
            
            // Obtener ID de la aplicación
            $stmt = $this->pdo->prepare("SELECT app_id FROM aplicaciones WHERE app_codigo = ?");
            $stmt->execute([$this->app_codigo]);
            $app = $stmt->fetch();
            
            if (!$app) {
                return ['success' => false, 'error' => 'Aplicación no encontrada'];
            }
            
            // Crear usuario_key y registrar
            $usuario_key = $user['email'] . '_' . $this->app_codigo;
            $stmt = $this->pdo->prepare("
                INSERT INTO usuarios_aplicaciones (usuario_key, usuario_id, app_id) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$usuario_key, $usuario_id, $app['app_id']]);
            
            return ['success' => true, 'usuario_key' => $usuario_key];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Error al registrar en aplicación: ' . $e->getMessage()];
        }
    }
    
    /**
     * Verificar si el usuario está logueado
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['usuario_key']);
    }
    
    /**
     * Obtener ID del usuario actual
     */
    public function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Obtener nombre del usuario actual
     */
    public function getCurrentUserName() {
        return $_SESSION['user_name'] ?? 'Usuario';
    }
    
    /**
     * Obtener usuario_key actual
     */
    public function getCurrentUserKey() {
        return $_SESSION['usuario_key'] ?? null;
    }
    
    /**
     * Cerrar sesión
     */
    public function logout() {
        session_destroy();
        return ['success' => true];
    }
    
    /**
     * Obtener estadísticas del usuario en la aplicación
     */
    public function getUserAppStats($usuario_id) {
        try {
            $stats = [];
            
            switch ($this->app_codigo) {
                case 'recetas':
                    $stmt = $this->pdo->prepare("
                        SELECT 
                            COUNT(*) as total_recetas,
                            COUNT(CASE WHEN receta_saludable = 1 THEN 1 END) as recetas_saludables,
                            AVG(receta_valoracion) as valoracion_promedio
                        FROM recetas 
                        WHERE usuario_id = ?
                    ");
                    $stmt->execute([$usuario_id]);
                    $stats = $stmt->fetch();
                    break;
                    
                case 'puzzle':
                    $stmt = $this->pdo->prepare("
                        SELECT 
                            COUNT(*) as partidas_jugadas,
                            MAX(puntuacion) as mejor_puntuacion,
                            MIN(tiempo_segundos) as mejor_tiempo
                        FROM puzzle_puntuaciones 
                        WHERE usuario_id = ?
                    ");
                    $stmt->execute([$usuario_id]);
                    $stats = $stmt->fetch();
                    break;
                    
                case 'memoria':
                    $stmt = $this->pdo->prepare("
                        SELECT 
                            COUNT(*) as partidas_jugadas,
                            MAX(puntuacion) as mejor_puntuacion,
                            AVG(tiempo_segundos) as tiempo_promedio
                        FROM memoria_partidas 
                        WHERE usuario_id = ?
                    ");
                    $stmt->execute([$usuario_id]);
                    $stats = $stmt->fetch();
                    break;
            }
            
            return ['success' => true, 'stats' => $stats];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Error al obtener estadísticas'];
        }
    }
}

// =====================================================
// FUNCIONES DE CONVENIENCIA PARA COMPATIBILIDAD
// =====================================================

// Inicializar sistema de auth
$authFinal = new AuthFinal($pdo, 'recetas');

function isLoggedIn() {
    global $authFinal;
    return $authFinal->isLoggedIn();
}

function getCurrentUserId() {
    global $authFinal;
    return $authFinal->getCurrentUserId();
}

function getCurrentUserName() {
    global $authFinal;
    return $authFinal->getCurrentUserName();
}

function getCurrentUserKey() {
    global $authFinal;
    return $authFinal->getCurrentUserKey();
}

function logout() {
    global $authFinal;
    return $authFinal->logout();
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}
?>
