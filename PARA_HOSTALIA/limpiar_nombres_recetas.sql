-- =====================================================
-- SCRIPT PARA LIMPIAR NOMBRES DE RECETAS
-- Elimina símbolos extraños de la base de datos
-- =====================================================

-- Actualizar nombres de recetas eliminando símbolos problemáticos
UPDATE recetas 
SET receta_nombre = TRIM(
    REPLACE(
        REPLACE(
            REPLACE(
                REPLACE(
                    REPLACE(receta_nombre, '">', ''),
                    '"">', ''
                ),
                '"', ''
            ),
            '>', ''
        ),
        '&quot;', ''
    )
)
WHERE usuario_aplicacion_key = '1954amg@gmail.com_recetas'
AND (
    receta_nombre LIKE '%">%' 
    OR receta_nombre LIKE '%"">%'
    OR receta_nombre LIKE '%"%'
    OR receta_nombre LIKE '%>%'
    OR receta_nombre LIKE '%&quot;%'
);

-- Verificar resultados
SELECT receta_id, receta_nombre, receta_tipo 
FROM recetas 
WHERE usuario_aplicacion_key = '1954amg@gmail.com_recetas'
ORDER BY receta_tipo, receta_nombre
LIMIT 10;

-- Contar recetas afectadas
SELECT COUNT(*) as recetas_limpiadas
FROM recetas 
WHERE usuario_aplicacion_key = '1954amg@gmail.com_recetas';
