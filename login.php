<?php
require_once 'config/database.php';
require_once 'includes/auth_final.php';

// Si ya está logueado, redirigir
if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $action = $_POST['action'] ?? 'login';
    
    // Validación básica de email
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email no válido';
    } else {
    
    if ($action === 'register') {
        // Registro con contraseña
        $nombre = trim($_POST['nombre'] ?? '');
        
        if (empty($email) || empty($nombre) || empty($password)) {
            $error = 'Todos los campos son requeridos';
        } elseif (strlen($password) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres';
        } else {
            $result = $authFinal->registerUser($email, $nombre, $password);
            
            if ($result['success']) {
                $success = 'Cuenta creada exitosamente. Ya puedes iniciar sesión.';
            } else {
                $error = $result['error'];
            }
        }
    } elseif ($action === 'register_app') {
        // Registrar usuario existente en esta aplicación
        $usuario_id = $_POST['usuario_id'] ?? '';
        
        if ($usuario_id) {
            $result = $authFinal->registerInApp($usuario_id);
            
            if ($result['success']) {
                // Hacer login automático
                $_SESSION['user_id'] = $usuario_id;
                $_SESSION['usuario_key'] = $result['usuario_key'];
                header('Location: index.php');
                exit();
            } else {
                $error = $result['error'];
            }
        }
    } else {
        // Login con contraseña
        if (empty($password)) {
            $error = 'Contraseña es requerida';
        } else {
            $result = $authFinal->loginUser($email, $password);
            
            if ($result['success']) {
                header('Location: index.php');
                exit();
            } else {
                if (isset($result['need_app_registration'])) {
                    // Usuario existe pero no está registrado en esta app
                    $error = 'Tu cuenta existe pero no estás registrado en "Mis Recetas". ';
                    $error .= '<form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="register_app">
                                <input type="hidden" name="email" value="' . htmlspecialchars($email) . '">
                                <button type="submit" style="background:#4ECDC4;color:white;border:none;padding:5px 10px;border-radius:5px;cursor:pointer;">
                                    Registrarme en Mis Recetas
                                </button>
                               </form>';
                } else {
                    $error = $result['error'];
                }
            }
        }
    }
    } // Cerrar validación de email
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Recetas 🍃</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#4ECDC4">
    <link rel="apple-touch-icon" href="icons/icon-192x192.png">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #4ECDC4 0%, #45B7D1 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .logo p {
            color: #7f8c8d;
            font-size: 1.1rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            color: #2c3e50;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        input[type="email"], input[type="text"], input[type="password"] {
            width: 100%;
            padding: 15px;
            border: 2px solid #ecf0f1;
            border-radius: 10px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="email"]:focus, input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #4ECDC4;
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 15px;
        }
        
        .btn-primary {
            background: #4ECDC4;
            color: white;
        }
        
        .btn-primary:hover {
            background: #45B7D1;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #ecf0f1;
            color: #2c3e50;
        }
        
        .btn-secondary:hover {
            background: #d5dbdb;
        }
        
        .error {
            background: #e74c3c;
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success {
            background: #27ae60;
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .toggle-form {
            text-align: center;
            margin-top: 20px;
        }
        
        .toggle-form a {
            color: #4ECDC4;
            text-decoration: none;
            font-weight: 500;
        }
        
        .form-register {
            display: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🍃</h1>
            <p>Mis Recetas</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <!-- Formulario de Login -->
        <form method="POST" id="loginForm">
            <input type="hidden" name="action" value="login">
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit" class="btn btn-primary">
                Entrar 🚀
            </button>
        </form>
        
        <!-- Formulario de Registro -->
        <form method="POST" id="registerForm" class="form-register">
            <input type="hidden" name="action" value="register">
            
            <div class="form-group">
                <label for="reg_nombre">Nombre</label>
                <input type="text" id="reg_nombre" name="nombre" required>
            </div>
            
            <div class="form-group">
                <label for="reg_email">Email</label>
                <input type="email" id="reg_email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="reg_password">Contraseña</label>
                <input type="password" id="reg_password" name="password" required minlength="6" placeholder="Mínimo 6 caracteres">
            </div>
            
            <button type="submit" class="btn btn-primary">
                Crear Cuenta 🎉
            </button>
        </form>
        
        <div class="toggle-form">
            <a href="#" onclick="toggleForm()" id="toggleLink">
                ¿No tienes cuenta? Regístrate aquí
            </a>
        </div>
    </div>
    
    <script>
        function toggleForm() {
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            const toggleLink = document.getElementById('toggleLink');
            
            if (loginForm.style.display === 'none') {
                loginForm.style.display = 'block';
                registerForm.style.display = 'none';
                toggleLink.textContent = '¿No tienes cuenta? Regístrate aquí';
            } else {
                loginForm.style.display = 'none';
                registerForm.style.display = 'block';
                toggleLink.textContent = '¿Ya tienes cuenta? Inicia sesión';
            }
        }
    </script>
</body>
</html>
