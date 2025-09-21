-- =====================================================
-- SQL SIMPLE PARA NUEVO JUEGO
-- Solo lo que necesitas crear
-- =====================================================

-- =====================================================
-- 1. VERIFICAR QUE EXISTEN LAS TABLAS PRINCIPALES
-- =====================================================

-- Esta tabla YA EXISTE en Hostalia - NO la crees
-- usuarios_aplicaciones (ya funciona con recetas)

-- Esta tabla YA EXISTE en Hostalia - NO la crees  
-- aplicaciones (ya funciona con recetas)

-- =====================================================
-- 2. CREAR SOLO LA TABLA DE TU JUEGO
-- =====================================================

-- EJEMPLO: JUEGO DE PUZZLE
-- Cambia 'puzzle' por el nombre de tu juego
CREATE TABLE IF NOT EXISTS puzzle_partidas (
    partida_id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_aplicacion_key VARCHAR(150) NOT NULL,  -- OBLIGATORIO: mismo formato que recetas
    puzzle_nombre VARCHAR(255) NOT NULL,
    puzzle_dificultad ENUM('Fácil', 'Medio', 'Difícil') DEFAULT 'Fácil',
    puzzle_completado BOOLEAN DEFAULT FALSE,
    tiempo_segundos INT DEFAULT 0,
    puntuacion INT DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_usuario (usuario_aplicacion_key),
    INDEX idx_fecha (fecha_creacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- =====================================================
-- 3. REGISTRAR TU JUEGO EN LA TABLA APLICACIONES
-- =====================================================

-- Esto le dice al sistema que existe tu nuevo juego
INSERT IGNORE INTO aplicaciones (app_codigo, app_nombre, app_descripcion, app_version) 
VALUES ('puzzle', 'Juego de Puzzle', 'Juego de rompecabezas', '1.0.0');

-- =====================================================
-- 4. USUARIO DE PRUEBA (OPCIONAL - SOLO PARA DESARROLLO)
-- =====================================================

-- Crear usuario de prueba para tu juego
-- Email: test@puzzle.com
-- Password: password
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

-- =====================================================
-- 5. VERIFICAR QUE TODO ESTÁ BIEN
-- =====================================================

-- Ver que tu juego está registrado
SELECT * FROM aplicaciones WHERE app_codigo = 'puzzle';

-- Ver usuarios de tu juego
SELECT * FROM usuarios_aplicaciones WHERE app_codigo = 'puzzle';

-- Ver estructura de tu tabla
DESCRIBE puzzle_partidas;

-- =====================================================
-- 6. EJEMPLOS DE USO
-- =====================================================

-- Crear una partida nueva
-- INSERT INTO puzzle_partidas (usuario_aplicacion_key, puzzle_nombre, puntuacion, tiempo_segundos) 
-- VALUES ('test@puzzle.com_puzzle', 'Puzzle 1', 1500, 120);

-- Ver partidas de un usuario
-- SELECT * FROM puzzle_partidas WHERE usuario_aplicacion_key = 'test@puzzle.com_puzzle';

-- Ver estadísticas de un usuario
-- SELECT 
--     COUNT(*) as total_partidas,
--     MAX(puntuacion) as mejor_puntuacion,
--     MIN(tiempo_segundos) as mejor_tiempo
-- FROM puzzle_partidas 
-- WHERE usuario_aplicacion_key = 'test@puzzle.com_puzzle';
