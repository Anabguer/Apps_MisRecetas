-- =====================================================
-- ESTRUCTURA SQL PARA NUEVO JUEGO
-- Sistema multi-aplicación compatible con Hostalia
-- =====================================================

-- =====================================================
-- 1. TABLA APLICACIONES (si no existe)
-- =====================================================
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

-- =====================================================
-- 2. TABLA USUARIOS_APLICACIONES (ya existe en Hostalia)
-- =====================================================
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

-- =====================================================
-- 3. TABLA PARA TU JUEGO (CAMBIAR NOMBRE SEGÚN EL JUEGO)
-- =====================================================

-- EJEMPLO: JUEGO DE PUZZLE
CREATE TABLE IF NOT EXISTS puzzle_partidas (
    partida_id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_aplicacion_key VARCHAR(150) NOT NULL,
    puzzle_nombre VARCHAR(255) NOT NULL,
    puzzle_dificultad ENUM('Fácil', 'Medio', 'Difícil') DEFAULT 'Fácil',
    puzzle_imagen VARCHAR(500),
    puzzle_completado BOOLEAN DEFAULT FALSE,
    tiempo_segundos INT DEFAULT 0,
    puntuacion INT DEFAULT 0,
    movimientos INT DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_usuario (usuario_aplicacion_key),
    INDEX idx_fecha (fecha_creacion),
    INDEX idx_dificultad (puzzle_dificultad),
    FOREIGN KEY (usuario_aplicacion_key) REFERENCES usuarios_aplicaciones(usuario_aplicacion_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- =====================================================
-- 4. TABLA PARA ESTADÍSTICAS DEL JUEGO
-- =====================================================
CREATE TABLE IF NOT EXISTS puzzle_estadisticas (
    estadistica_id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_aplicacion_key VARCHAR(150) NOT NULL,
    partidas_jugadas INT DEFAULT 0,
    partidas_completadas INT DEFAULT 0,
    mejor_puntuacion INT DEFAULT 0,
    mejor_tiempo INT DEFAULT 0,
    tiempo_total_jugado INT DEFAULT 0,
    nivel_maximo_alcanzado INT DEFAULT 1,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user (usuario_aplicacion_key),
    FOREIGN KEY (usuario_aplicacion_key) REFERENCES usuarios_aplicaciones(usuario_aplicacion_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- =====================================================
-- 5. TABLA PARA LOGROS/DESAFÍOS
-- =====================================================
CREATE TABLE IF NOT EXISTS puzzle_logros (
    logro_id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_aplicacion_key VARCHAR(150) NOT NULL,
    logro_codigo VARCHAR(100) NOT NULL,
    logro_nombre VARCHAR(255) NOT NULL,
    logro_descripcion TEXT,
    logro_icono VARCHAR(100),
    fecha_obtenido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user_logro (usuario_aplicacion_key, logro_codigo),
    INDEX idx_usuario (usuario_aplicacion_key),
    INDEX idx_codigo (logro_codigo),
    FOREIGN KEY (usuario_aplicacion_key) REFERENCES usuarios_aplicaciones(usuario_aplicacion_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- =====================================================
-- 6. INSERTAR APLICACIÓN EN LA TABLA APLICACIONES
-- =====================================================
INSERT IGNORE INTO aplicaciones (app_codigo, app_nombre, app_descripcion, app_version) 
VALUES ('puzzle', 'Juego de Puzzle', 'Juego de rompecabezas con diferentes dificultades', '1.0.0');

-- =====================================================
-- 7. EJEMPLOS DE DATOS DE PRUEBA (SOLO PARA DESARROLLO)
-- =====================================================

-- Usuario de prueba para el juego
INSERT IGNORE INTO usuarios_aplicaciones (
    usuario_aplicacion_key, 
    email, 
    nombre, 
    password_hash, 
    app_codigo
) VALUES (
    'test@puzzle.com_puzzle',
    'test@puzzle.com',
    'Usuario Test Puzzle',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password
    'puzzle'
);

-- Estadísticas iniciales para el usuario de prueba
INSERT IGNORE INTO puzzle_estadisticas (
    usuario_aplicacion_key,
    partidas_jugadas,
    partidas_completadas,
    mejor_puntuacion,
    mejor_tiempo
) VALUES (
    'test@puzzle.com_puzzle',
    0,
    0,
    0,
    0
);

-- =====================================================
-- 8. VISTAS ÚTILES PARA CONSULTAS
-- =====================================================

-- Vista para obtener estadísticas completas del usuario
CREATE OR REPLACE VIEW vista_estadisticas_puzzle AS
SELECT 
    ua.usuario_aplicacion_key,
    ua.email,
    ua.nombre,
    COALESCE(es.partidas_jugadas, 0) as partidas_jugadas,
    COALESCE(es.partidas_completadas, 0) as partidas_completadas,
    COALESCE(es.mejor_puntuacion, 0) as mejor_puntuacion,
    COALESCE(es.mejor_tiempo, 0) as mejor_tiempo,
    COALESCE(es.tiempo_total_jugado, 0) as tiempo_total_jugado,
    COALESCE(es.nivel_maximo_alcanzado, 1) as nivel_maximo_alcanzado,
    COUNT(DISTINCT l.logro_id) as logros_obtenidos
FROM usuarios_aplicaciones ua
LEFT JOIN puzzle_estadisticas es ON ua.usuario_aplicacion_key = es.usuario_aplicacion_key
LEFT JOIN puzzle_logros l ON ua.usuario_aplicacion_key = l.usuario_aplicacion_key
WHERE ua.app_codigo = 'puzzle'
GROUP BY ua.usuario_aplicacion_key;

-- =====================================================
-- 9. PROCEDIMIENTOS ALMACENADOS ÚTILES
-- =====================================================

DELIMITER //

-- Procedimiento para actualizar estadísticas después de una partida
CREATE PROCEDURE IF NOT EXISTS ActualizarEstadisticasPuzzle(
    IN p_usuario_key VARCHAR(150),
    IN p_puntuacion INT,
    IN p_tiempo_segundos INT,
    IN p_completado BOOLEAN
)
BEGIN
    DECLARE v_partidas_jugadas INT DEFAULT 0;
    DECLARE v_partidas_completadas INT DEFAULT 0;
    DECLARE v_mejor_puntuacion INT DEFAULT 0;
    DECLARE v_mejor_tiempo INT DEFAULT 0;
    
    -- Obtener estadísticas actuales
    SELECT 
        COALESCE(partidas_jugadas, 0),
        COALESCE(partidas_completadas, 0),
        COALESCE(mejor_puntuacion, 0),
        COALESCE(mejor_tiempo, 0)
    INTO v_partidas_jugadas, v_partidas_completadas, v_mejor_puntuacion, v_mejor_tiempo
    FROM puzzle_estadisticas 
    WHERE usuario_aplicacion_key = p_usuario_key;
    
    -- Insertar o actualizar estadísticas
    INSERT INTO puzzle_estadisticas (
        usuario_aplicacion_key,
        partidas_jugadas,
        partidas_completadas,
        mejor_puntuacion,
        mejor_tiempo,
        tiempo_total_jugado
    ) VALUES (
        p_usuario_key,
        v_partidas_jugadas + 1,
        v_partidas_completadas + IF(p_completado, 1, 0),
        GREATEST(v_mejor_puntuacion, p_puntuacion),
        CASE 
            WHEN v_mejor_tiempo = 0 OR (p_completado AND p_tiempo_segundos < v_mejor_tiempo) 
            THEN p_tiempo_segundos 
            ELSE v_mejor_tiempo 
        END,
        (SELECT COALESCE(SUM(tiempo_segundos), 0) + p_tiempo_segundos 
         FROM puzzle_partidas 
         WHERE usuario_aplicacion_key = p_usuario_key)
    ) ON DUPLICATE KEY UPDATE
        partidas_jugadas = partidas_jugadas + 1,
        partidas_completadas = partidas_completadas + IF(p_completado, 1, 0),
        mejor_puntuacion = GREATEST(mejor_puntuacion, p_puntuacion),
        mejor_tiempo = CASE 
            WHEN mejor_tiempo = 0 OR (p_completado AND p_tiempo_segundos < mejor_tiempo) 
            THEN p_tiempo_segundos 
            ELSE mejor_tiempo 
        END,
        tiempo_total_jugado = (
            SELECT COALESCE(SUM(tiempo_segundos), 0) 
            FROM puzzle_partidas 
            WHERE usuario_aplicacion_key = p_usuario_key
        );
END //

DELIMITER ;

-- =====================================================
-- 10. ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- =====================================================

-- Índices para consultas frecuentes
CREATE INDEX IF NOT EXISTS idx_puzzle_usuario_fecha ON puzzle_partidas(usuario_aplicacion_key, fecha_creacion DESC);
CREATE INDEX IF NOT EXISTS idx_puzzle_completado ON puzzle_partidas(puzzle_completado);
CREATE INDEX IF NOT EXISTS idx_puzzle_puntuacion ON puzzle_partidas(puntuacion DESC);

-- =====================================================
-- VERIFICACIÓN DE ESTRUCTURA CREADA
-- =====================================================

-- Mostrar todas las tablas relacionadas con el juego
SELECT 
    TABLE_NAME as 'Tabla',
    TABLE_ROWS as 'Registros',
    CREATE_TIME as 'Creada'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME LIKE '%puzzle%'
ORDER BY TABLE_NAME;

-- Mostrar estructura de la tabla principal
DESCRIBE puzzle_partidas;
