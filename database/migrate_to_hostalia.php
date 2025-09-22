<?php
// =====================================================
// MIGRACIÓN DE DATOS A HOSTALIA
// Migra datos desde localhost a base de datos Hostalia
// =====================================================

set_time_limit(300); // 5 minutos máximo

echo "<h2>🚀 MIGRACIÓN A HOSTALIA - SISTEMA APPS</h2>\n";

// =====================================================
// CONFIGURACIÓN DE CONEXIONES
// =====================================================

// Conexión LOCALHOST (origen)
try {
    $dsn_local = "mysql:host=localhost;port=3306;dbname=sistema_apps_db;charset=utf8mb4";
    $pdo_local = new PDO($dsn_local, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    echo "✅ Conexión LOCAL establecida<br>\n";
} catch (PDOException $e) {
    die("❌ Error conectando a localhost: " . $e->getMessage());
}

// Conexión HOSTALIA (destino)
try {
    $dsn_hostalia = "mysql:host=PMYSQL165.dns-servicio.com;port=3306;dbname=9606966_sistema_apps_db;charset=utf8";
    $pdo_hostalia = new PDO($dsn_hostalia, 'sistema_apps_user', 'GestionUploadSistemaApps!', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    echo "✅ Conexión HOSTALIA establecida<br>\n";
} catch (PDOException $e) {
    die("❌ Error conectando a Hostalia: " . $e->getMessage());
}

// =====================================================
// CREAR ESTRUCTURA DE TABLAS EN HOSTALIA
// =====================================================

echo "<h3>📋 CREANDO ESTRUCTURA DE TABLAS</h3>\n";

$estructuraTablas = [
    // Tabla aplicaciones
    "aplicaciones" => "
        CREATE TABLE IF NOT EXISTS aplicaciones (
            app_id INT AUTO_INCREMENT PRIMARY KEY,
            app_codigo VARCHAR(50) UNIQUE NOT NULL,
            app_nombre VARCHAR(255) NOT NULL,
            app_descripcion TEXT,
            app_version VARCHAR(20) DEFAULT '1.0.0',
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            activo BOOLEAN DEFAULT TRUE,
            configuracion JSON
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
    ",
    
    // Tabla usuarios_aplicaciones
    "usuarios_aplicaciones" => "
        CREATE TABLE IF NOT EXISTS usuarios_aplicaciones (
            usuario_aplicacion_id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_aplicacion_key VARCHAR(150) UNIQUE NOT NULL,
            email VARCHAR(255) NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            app_codigo VARCHAR(50) NOT NULL,
            fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ultimo_acceso TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            activo BOOLEAN DEFAULT TRUE,
            configuracion JSON,
            INDEX idx_email_app (email, app_codigo),
            INDEX idx_usuario_key (usuario_aplicacion_key),
            INDEX idx_app_codigo (app_codigo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
    ",
    
    // Tabla recetas
    "recetas" => "
        CREATE TABLE IF NOT EXISTS recetas (
            receta_id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_aplicacion_key VARCHAR(150) NOT NULL,
            receta_nombre VARCHAR(255) NOT NULL,
            receta_tipo ENUM('Entrante', 'Principal', 'Postre', 'Bebida', 'Extra') NOT NULL,
            receta_ingredients TEXT NOT NULL,
            receta_preparation TEXT NOT NULL,
            receta_image VARCHAR(500) NULL,
            receta_video VARCHAR(500) NULL,
            receta_valoracion TINYINT(1) DEFAULT 5 CHECK (receta_valoracion >= 0 AND receta_valoracion <= 5),
            receta_saludable BOOLEAN DEFAULT FALSE,
            receta_tiempopreparacion VARCHAR(50) NULL,
            receta_dificultad ENUM('Fácil', 'Medio', 'Difícil') NULL,
            receta_porciones VARCHAR(50) NULL,
            fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_usuario_tipo (usuario_aplicacion_key, receta_tipo),
            INDEX idx_fecha_creacion (fecha_creacion)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
    "
];

foreach ($estructuraTablas as $tabla => $sql) {
    try {
        $pdo_hostalia->exec($sql);
        echo "✅ Tabla '$tabla' creada en Hostalia<br>\n";
    } catch (PDOException $e) {
        echo "⚠️ Error creando tabla '$tabla': " . $e->getMessage() . "<br>\n";
    }
}

// =====================================================
// MIGRAR DATOS
// =====================================================

echo "<h3>📦 MIGRANDO DATOS</h3>\n";

// 1. Migrar aplicaciones
try {
    $stmt_local = $pdo_local->query("SELECT * FROM aplicaciones");
    $aplicaciones = $stmt_local->fetchAll();
    
    foreach ($aplicaciones as $app) {
        $stmt_hostalia = $pdo_hostalia->prepare("
            INSERT IGNORE INTO aplicaciones (app_codigo, app_nombre, app_descripcion, app_version, fecha_creacion, activo, configuracion)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt_hostalia->execute([
            $app['app_codigo'],
            $app['app_nombre'],
            $app['app_descripcion'],
            $app['app_version'],
            $app['fecha_creacion'],
            $app['activo'],
            $app['configuracion']
        ]);
    }
    echo "✅ Aplicaciones migradas: " . count($aplicaciones) . "<br>\n";
} catch (PDOException $e) {
    echo "⚠️ Error migrando aplicaciones: " . $e->getMessage() . "<br>\n";
}

// 2. Migrar usuarios_aplicaciones
try {
    $stmt_local = $pdo_local->query("SELECT * FROM usuarios_aplicaciones");
    $usuarios = $stmt_local->fetchAll();
    
    foreach ($usuarios as $usuario) {
        $stmt_hostalia = $pdo_hostalia->prepare("
            INSERT IGNORE INTO usuarios_aplicaciones (usuario_aplicacion_key, email, nombre, password_hash, app_codigo, fecha_registro, ultimo_acceso, activo, configuracion)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt_hostalia->execute([
            $usuario['usuario_aplicacion_key'],
            $usuario['email'],
            $usuario['nombre'],
            $usuario['password_hash'],
            $usuario['app_codigo'],
            $usuario['fecha_registro'],
            $usuario['ultimo_acceso'],
            $usuario['activo'],
            $usuario['configuracion']
        ]);
    }
    echo "✅ Usuarios migrados: " . count($usuarios) . "<br>\n";
} catch (PDOException $e) {
    echo "⚠️ Error migrando usuarios: " . $e->getMessage() . "<br>\n";
}

// 3. Migrar recetas
try {
    $stmt_local = $pdo_local->query("SELECT * FROM recetas");
    $recetas = $stmt_local->fetchAll();
    
    foreach ($recetas as $receta) {
        $stmt_hostalia = $pdo_hostalia->prepare("
            INSERT IGNORE INTO recetas (usuario_aplicacion_key, receta_nombre, receta_tipo, receta_ingredients, receta_preparation, receta_image, receta_video, receta_valoracion, receta_saludable, receta_tiempopreparacion, receta_dificultad, receta_porciones, fecha_creacion, fecha_modificacion)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt_hostalia->execute([
            $receta['usuario_aplicacion_key'],
            $receta['receta_nombre'],
            $receta['receta_tipo'],
            $receta['receta_ingredients'],
            $receta['receta_preparation'],
            $receta['receta_image'],
            $receta['receta_video'],
            $receta['receta_valoracion'],
            $receta['receta_saludable'],
            $receta['receta_tiempopreparacion'],
            $receta['receta_dificultad'],
            $receta['receta_porciones'],
            $receta['fecha_creacion'],
            $receta['fecha_modificacion']
        ]);
    }
    echo "✅ Recetas migradas: " . count($recetas) . "<br>\n";
} catch (PDOException $e) {
    echo "⚠️ Error migrando recetas: " . $e->getMessage() . "<br>\n";
}

// =====================================================
// VERIFICAR MIGRACIÓN
// =====================================================

echo "<h3>🔍 VERIFICANDO MIGRACIÓN</h3>\n";

$tablas_verificar = ['aplicaciones', 'usuarios_aplicaciones', 'recetas'];

foreach ($tablas_verificar as $tabla) {
    try {
        $stmt = $pdo_hostalia->query("SELECT COUNT(*) as total FROM $tabla");
        $result = $stmt->fetch();
        echo "📊 $tabla: {$result['total']} registros<br>\n";
    } catch (PDOException $e) {
        echo "⚠️ Error verificando $tabla: " . $e->getMessage() . "<br>\n";
    }
}

echo "<h3>🎉 MIGRACIÓN COMPLETADA</h3>\n";
echo "<p><strong>✅ Datos migrados exitosamente a Hostalia</strong></p>\n";
echo "<p>🔗 Host: PMYSQL165.dns-servicio.com</p>\n";
echo "<p>🗄️ BD: 9606966_sistema_apps_db</p>\n";
echo "<p>👤 Usuario: sistema_apps_user</p>\n";

// Cerrar conexiones
$pdo_local = null;
$pdo_hostalia = null;

echo "<hr>\n";
echo "<p><a href='index.php'>🍃 Ir a Mis Recetas</a></p>\n";
?>
