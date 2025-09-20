<?php
require_once 'config/database.php';
require_once 'includes/auth_final.php';

// Verificar que el usuario esté logueado
requireLogin();

$userId = getCurrentUserId();
$userKey = getCurrentUserKey();

// Obtener parámetros de filtro
$tipo = htmlspecialchars($_GET['tipo'] ?? '', ENT_QUOTES, 'UTF-8');
$saludable = isset($_GET['saludable']) ? (bool)$_GET['saludable'] : false;
$busqueda = htmlspecialchars($_GET['busqueda'] ?? '', ENT_QUOTES, 'UTF-8');

// Construir consulta SQL - solo recetas del usuario logueado en esta aplicación
$sql = "SELECT * FROM recetas WHERE usuario_aplicacion_key = ?";
$params = [$userKey];

if (!empty($tipo)) {
    $sql .= " AND receta_tipo = ?";
    $params[] = $tipo;
}

if ($saludable) {
    $sql .= " AND receta_saludable = 1";
}

if (!empty($busqueda)) {
    $sql .= " AND receta_nombre LIKE ?";
    $params[] = "%$busqueda%";
}

$sql .= " ORDER BY receta_tipo ASC, receta_nombre ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $recetas = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Error al cargar las recetas: " . $e->getMessage();
    $recetas = [];
}

// Obtener tipos únicos para los filtros
$tiposOrdenados = ['Entrante', 'Principal', 'Postre', 'Bebida', 'Extra'];
try {
    $stmt = $pdo->prepare("SELECT DISTINCT receta_tipo FROM recetas WHERE usuario_aplicacion_key = ?");
    $stmt->execute([$userKey]);
    $tiposDB = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $tipos = array_intersect($tiposOrdenados, $tiposDB);
} catch (PDOException $e) {
    $tipos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Recetas 🍃</title>
    
    <!-- PWA Meta Tags -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#22c55e">
    <!-- <link rel="apple-touch-icon" href="icons/icon-192x192.png"> -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Mis Recetas">
    
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <!-- Header móvil como en la imagen -->
    <header class="mobile-header">
        <div class="mobile-header-content">
            <div class="mobile-header-left">
                <div class="mobile-logo">
                    <span class="mobile-logo-icon">🍃</span>
                    <div class="mobile-logo-text">
                        <div class="mobile-logo-title-top">Mis</div>
                        <div class="mobile-logo-title-bottom">Recetas</div>
                    </div>
                </div>
            </div>
            <div class="mobile-header-right">
                <div class="mobile-user-info">
                    <div class="mobile-user-greeting">
                        <div class="greeting-line">👋 Hola</div>
                        <div class="user-name-line"><?php echo htmlspecialchars(getCurrentUserName()); ?></div>
                    </div>
                </div>
                <button onclick="logout()" class="mobile-logout-btn" title="Salir">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V19C21 19.5304 20.7893 20.0391 20.4142 20.4142C20.0391 20.7893 19.5304 21 19 21H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M10 17L15 12L10 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M15 12H3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <!-- Panel de filtros móvil como en la imagen -->
    <div class="mobile-filters-panel">
        <form method="GET" class="mobile-filters-form">
            <div class="mobile-filters-row">
                <!-- Filtros por tipo con iconos circulares -->
                <div class="mobile-filter-types">
                    <button type="submit" name="tipo" value="" class="mobile-filter-circle <?php echo empty($tipo) ? 'active' : ''; ?>" title="Todos">
                        🍽️
                    </button>
                    <?php 
                    $tiposIcons = [
                        'Entrante' => '🫒',
                        'Principal' => '🍽️',
                        'Postre' => '🍰', 
                        'Bebida' => '🥤',
                        'Extra' => '🧺'
                    ];
                    // Mostrar TODOS los tipos, no solo los que tienen recetas
                    foreach ($tiposOrdenados as $tipoItem): 
                        $isActive = $tipo === $tipoItem;
                        $icon = $tiposIcons[$tipoItem] ?? '🍳';
                    ?>
                        <button type="submit" name="tipo" value="<?php echo htmlspecialchars($tipoItem); ?>" 
                                class="mobile-filter-circle <?php echo $isActive ? 'active' : ''; ?>" 
                                title="<?php echo htmlspecialchars($tipoItem); ?>">
                            <?php echo $icon; ?>
                        </button>
                    <?php endforeach; ?>
                    
                    <!-- Filtro saludable -->
                    <label class="mobile-filter-circle <?php echo $saludable ? 'active' : ''; ?>" title="Saludable">
                        <input type="checkbox" name="saludable" value="1" <?php echo $saludable ? 'checked' : ''; ?> onchange="this.form.submit();" style="display: none;">
                        💚
                    </label>
                    
                    <!-- Búsqueda (solo icono en línea principal) -->
                    <?php if (empty($busqueda) && !isset($_GET['expand_search'])): ?>
                        <button type="button" onclick="toggleSearchInline()" class="mobile-filter-circle" title="Buscar">
                            🔍
                        </button>
                    <?php endif; ?>
                </div>
                
                <!-- Botón Nueva receta -->
                <div class="mobile-actions">
                    <button type="button" onclick="showNewRecipeModal()" class="mobile-new-recipe-btn">
                        ➕
                    </button>
                </div>
            </div>
            
            <!-- Línea inferior: Contador + Campo de búsqueda expandido -->
            <div class="mobile-bottom-row">
                <!-- Contador de recetas -->
                <?php 
                $totalRecetas = count($recetas);
                $hayFiltros = !empty($tipo) || $saludable || !empty($busqueda);
                $searchExpanded = !empty($busqueda) || isset($_GET['expand_search']);
                if ($totalRecetas > 0): 
                ?>
                    <div class="recipe-counter <?php echo $searchExpanded ? 'with-search' : 'centered'; ?>">
                        <?php if ($hayFiltros): ?>
                            <span class="counter-text">
                                <?php echo $totalRecetas; ?> receta<?php echo $totalRecetas != 1 ? 's' : ''; ?>
                                <?php if (!empty($tipo)): ?>
                                    <?php echo htmlspecialchars($tipo); ?>
                                <?php endif; ?>
                                (filtradas)
                            </span>
                        <?php else: ?>
                            <span class="counter-text"><?php echo $totalRecetas; ?> receta<?php echo $totalRecetas != 1 ? 's' : ''; ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Campo de búsqueda expandido (solo cuando está activo) -->
                <?php if ($searchExpanded): ?>
                    <div class="mobile-search-bottom">
                        <input type="text" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>" 
                               placeholder="Buscar..." class="mobile-search-input-bottom" autofocus 
                               oninput="searchAsYouType(this)">
                        <button type="button" onclick="clearSearchInline()" class="mobile-search-clear-bottom" title="Limpiar">✕</button>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Lista de recetas -->
    <main class="recipes-container">
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (empty($recetas)): ?>
            <div class="empty-state">
                <div class="empty-icon">🍳</div>
                <h3>No tienes recetas aún</h3>
                <p>¡Crea tu primera receta y empieza a cocinar!</p>
                <button onclick="showNewRecipeModal()" class="btn-primary">
                    ➕ Crear Primera Receta
                </button>
            </div>
        <?php else: ?>
            <!-- Debug: <?php echo count($recetas); ?> recetas encontradas -->
            <div class="recipes-grid">
                <?php foreach ($recetas as $receta): ?>
                    <div class="recipe-card" onclick="showRecipeDetail(<?php echo $receta['receta_id']; ?>)">
                        <div class="recipe-image-container">
                            <!-- Imagen de la receta -->
                            <?php if (!empty($receta['receta_image'])): ?>
                                <img src="<?php echo htmlspecialchars($receta['receta_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($receta['receta_nombre']); ?>"
                                     class="recipe-image"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <?php endif; ?>
                            
                            <!-- Imagen de fallback -->
                            <div class="recipe-fallback" style="display: <?php echo !empty($receta['receta_image']) ? 'none' : 'flex'; ?>">
                                <span class="text-4xl text-amber-600">
                                    <?php 
                                    $tipoIcons = [
                                        'Entrante' => '🫒',
                                        'Principal' => '🍽️',
                                        'Postre' => '🍰', 
                                        'Bebida' => '🥤',
                                        'Extra' => '🧺'
                                    ];
                                    echo $tipoIcons[$receta['receta_tipo']] ?? '🍳';
                                    ?>
                                </span>
                            </div>
                            
                            <!-- Gradiente para legibilidad del texto -->
                            <div class="recipe-overlay"></div>
                            
                            <!-- Badges: corazón verde si es saludable y icono de video -->
                            <div class="recipe-badge">
                                <?php if ($receta['receta_saludable'] == '1'): ?>
                                    <span class="text-2xl drop-shadow-lg">💚</span>
                                <?php endif; ?>
                                <?php if (!empty($receta['receta_video'])): ?>
                                    <span class="text-2xl drop-shadow-lg ml-2 cursor-pointer hover:scale-110 transition-transform duration-200" 
                                          onclick="event.stopPropagation(); window.open('<?php echo htmlspecialchars($receta['receta_video']); ?>', '_blank');" 
                                          title="Ver video">🎥</span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Icono de tipo en la esquina superior izquierda -->
                            <div class="recipe-type-icon">
                                <span class="text-2xl drop-shadow-lg">
                                    <?php 
                                    $tipoIcons = [
                                        'Entrante' => '🫒',
                                        'Principal' => '🍽️',
                                        'Postre' => '🍰', 
                                        'Bebida' => '🥤',
                                        'Extra' => '🧺'
                                    ];
                                    echo $tipoIcons[$receta['receta_tipo']] ?? '🍳';
                                    ?>
                                </span>
                            </div>
                            
                            <!-- Título y estrellas como overlay en la parte inferior -->
                            <div class="recipe-info">
                                <h3 class="recipe-title">
                                    <?php echo htmlspecialchars($receta['receta_nombre']); ?>
                                </h3>
                                
                                <div class="recipe-stars">
                                    <span class="text-yellow-300 text-base drop-shadow-lg">
                                        <?php 
                                        $rating = (int)($receta['receta_valoracion'] ?? 0);
                                        if ($rating > 0) {
                                            echo str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
                                        } else {
                                            echo '☆☆☆☆☆';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Modal de Nueva Receta -->
    <div id="newRecipeModal" class="modal">
        <div class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                            <span class="text-green-600 text-lg">✨</span>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Nueva Receta</h2>
                            <p class="text-sm text-gray-600">Crea una nueva receta deliciosa</p>
                        </div>
                    </div>
                    
                    <div class="modal-header-actions">
                        <button type="submit" form="new-recipe-form" class="modal-action-btn save" title="Guardar">
                            💾
                        </button>
                        <button class="modal-close" onclick="closeModal('newRecipeModal')">✕</button>
                    </div>
                </div>
                
                <div class="modal-body">
                    <form id="new-recipe-form" onsubmit="handleNewRecipe(event)">
                        <!-- Nombre de la receta y Saludable -->
                        <div class="flex items-center justify-between gap-4 mb-6">
                            <div class="flex-1 text-center">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Nombre de la receta *
                                </label>
                                <input type="text" name="nombre" id="new-nombre" required placeholder="Ej: Paella de Marisco" />
                            </div>
                            
                            <!-- Saludable -->
                            <div class="flex flex-col items-center">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Saludable</label>
                                <label class="health-toggle-container">
                                    <input type="checkbox" name="saludable" id="new-saludable" class="health-toggle-input" />
                                    <div class="health-toggle-slider">
                                        <div class="health-toggle-knob"></div>
                                    </div>
                                    <span class="health-toggle-icon">💚</span>
                                </label>
                            </div>
                        </div>

                        <!-- Separador visual -->
                        <div class="border-t border-gray-200 my-6"></div>

                        <!-- Tipos de receta en dos líneas -->
                        <div class="flex flex-col gap-2 justify-center mb-6">
                            <!-- Primera línea: Entrante, Principal, Postre -->
                            <div class="flex gap-2 justify-center">
                                <button type="button" onclick="selectNewType('Entrante')" class="type-button" data-type="Entrante">
                                    <div class="type-icon">
                                        <span class="text-xs">🫒</span>
                                    </div>
                                    <span>Entrante</span>
                                </button>
                                <button type="button" onclick="selectNewType('Principal')" class="type-button" data-type="Principal">
                                    <div class="type-icon">
                                        <span class="text-xs">🍽️</span>
                                    </div>
                                    <span>Principal</span>
                                </button>
                                <button type="button" onclick="selectNewType('Postre')" class="type-button" data-type="Postre">
                                    <div class="type-icon">
                                        <span class="text-xs">🍰</span>
                                    </div>
                                    <span>Postre</span>
                                </button>
                            </div>
                            <!-- Segunda línea: Bebida, Extra -->
                            <div class="flex gap-2 justify-center">
                                <button type="button" onclick="selectNewType('Bebida')" class="type-button" data-type="Bebida">
                                    <div class="type-icon">
                                        <span class="text-xs">🥤</span>
                                    </div>
                                    <span>Bebida</span>
                                </button>
                                <button type="button" onclick="selectNewType('Extra')" class="type-button" data-type="Extra">
                                    <div class="type-icon">
                                        <span class="text-xs">🧺</span>
                                    </div>
                                    <span>Extra</span>
                                </button>
                            </div>
                        </div>
                        <input type="hidden" name="tipo" id="new-tipo" required />

                        <!-- Separador visual -->
                        <div class="border-t border-gray-200 my-6"></div>

                        <!-- Porciones y Tiempo -->
                        <div class="flex justify-center space-x-4 mb-6">
                            <div class="portions-container">
                                <div class="w-6 h-6 rounded-full bg-orange-100 border border-orange-200 flex items-center justify-center">
                                    <span class="text-orange-600 text-sm">🍽️</span>
                                </div>
                                <input type="number" name="porciones" id="new-porciones" min="1" class="w-6 text-sm font-bold text-gray-800 bg-transparent border-none focus:outline-none text-center" placeholder="4" />
                                <span class="text-sm text-orange-600">porc</span>
                            </div>
                            
                            <div class="time-container">
                                <div class="w-6 h-6 rounded-full bg-blue-100 border border-blue-200 flex items-center justify-center">
                                    <span class="text-blue-600 text-sm">⏱️</span>
                                </div>
                                <input type="text" name="tiempo" id="new-tiempo" class="w-6 text-sm font-bold text-gray-800 bg-transparent border-none focus:outline-none text-center" placeholder="30" />
                                <span class="text-sm text-blue-600">min</span>
                            </div>
                        </div>

                        <!-- Dificultad -->
                        <div class="flex justify-center mb-4">
                            <div class="flex gap-2">
                                <button type="button" onclick="selectNewDifficulty('Fácil')" class="difficulty-button" data-difficulty="Fácil">
                                    <span class="text-sm">😊</span>
                                    <span>Fácil</span>
                                </button>
                                <button type="button" onclick="selectNewDifficulty('Medio')" class="difficulty-button" data-difficulty="Medio">
                                    <span class="text-sm">😐</span>
                                    <span>Medio</span>
                                </button>
                                <button type="button" onclick="selectNewDifficulty('Difícil')" class="difficulty-button" data-difficulty="Difícil">
                                    <span class="text-sm">😰</span>
                                    <span>Difícil</span>
                                </button>
                            </div>
                        </div>
                        <input type="hidden" name="dificultad" id="new-dificultad" />

                        <!-- Valoración -->
                        <div class="flex justify-center mb-6">
                            <div class="flex items-center space-x-1">
                                <button type="button" onclick="setNewRating(1)" class="star-button" data-star="1">⭐</button>
                                <button type="button" onclick="setNewRating(2)" class="star-button" data-star="2">⭐</button>
                                <button type="button" onclick="setNewRating(3)" class="star-button" data-star="3">⭐</button>
                                <button type="button" onclick="setNewRating(4)" class="star-button" data-star="4">⭐</button>
                                <button type="button" onclick="setNewRating(5)" class="star-button" data-star="5">⭐</button>
                            </div>
                            <input type="hidden" name="valoracion" id="new-valoracion" value="5" />
                        </div>

                        <!-- Ingredientes -->
                        <div class="ingredients-section mb-4">
                            <div class="ingredients-header">
                                <span class="text-2xl mr-2">🥘</span>
                                <span class="ingredients-title">Ingredientes</span>
                            </div>
                            <textarea name="ingredientes" id="new-ingredientes" rows="4" class="ingredients-textarea" placeholder="Lista de ingredientes, uno por línea..." required></textarea>
                        </div>

                        <!-- Preparación -->
                        <div class="preparation-section mb-4">
                            <div class="preparation-header">
                                <span class="text-2xl mr-2">👨‍🍳</span>
                                <span class="preparation-title">Preparación</span>
                            </div>
                            <textarea name="preparacion" id="new-preparacion" rows="8" class="preparation-textarea" placeholder="Pasos de preparación, uno por línea..." required></textarea>
                        </div>

                        <!-- Imagen -->
                        <div class="image-section rounded-xl p-4 shadow-sm mb-4">
                            <div class="flex items-center mb-3">
                                <span class="text-2xl mr-2">🖼️</span>
                                <span class="text-sm font-medium text-gray-800">Imagen</span>
                            </div>
                            <input type="file" id="new-image-file" accept="image/*" class="hidden" onchange="handleNewImageUpload(this)" />
                            <input type="hidden" name="imagen" id="new-imagen" />
                            
                            <div id="new-image-container">
                                <!-- Se llenará dinámicamente con JavaScript -->
                            </div>
                        </div>

                        <!-- Video -->
                        <div class="image-section rounded-xl p-4 shadow-sm mb-4">
                            <div class="flex items-center mb-3">
                                <span class="text-2xl mr-2">🎥</span>
                                <span class="text-sm font-medium text-gray-800">Video</span>
                            </div>
                            <input type="hidden" name="enlace_video" id="new-enlace_video" />
                            
                            <div id="new-video-container" class="video-upload-container">
                                <!-- Se llenará dinámicamente con JavaScript -->
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edición de Receta -->
    <div id="editRecipeModal" class="modal">
        <div class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
                            <span class="text-purple-600 text-lg">✏️</span>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">Editar Receta</h2>
                            <p class="text-sm text-gray-600">Modifica los detalles de tu receta</p>
                        </div>
                    </div>
                    
                    <div class="modal-header-actions">
                        <button type="submit" form="edit-recipe-form" class="modal-action-btn save" title="Guardar">
                            💾
                        </button>
                        <button class="modal-close" onclick="closeModal('editRecipeModal')">✕</button>
                    </div>
                </div>
                
                <div class="modal-body">
                    <form id="edit-recipe-form" onsubmit="handleEditRecipe(event)">
                        <!-- Nombre de la receta y Saludable -->
                        <div class="flex items-center justify-between gap-4 mb-6">
                            <div class="flex-1 text-center">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Nombre de la receta *
                                </label>
                                <input type="text" name="nombre" id="edit-nombre" required placeholder="Ej: Paella de Marisco" />
                            </div>
                            
                            <!-- Saludable -->
                            <div class="flex flex-col items-center">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Saludable</label>
                                <label class="health-toggle-container">
                                    <input type="checkbox" name="saludable" id="edit-saludable" class="health-toggle-input" />
                                    <div class="health-toggle-slider">
                                        <div class="health-toggle-knob"></div>
                                    </div>
                                    <span class="health-toggle-icon">💚</span>
                                </label>
                            </div>
                        </div>

                        <!-- Separador visual -->
                        <div class="border-t border-gray-200 my-6"></div>

                        <!-- Tipos de receta en dos líneas -->
                        <div class="flex flex-col gap-2 justify-center mb-6">
                            <!-- Primera línea: Entrante, Principal, Postre -->
                            <div class="flex gap-2 justify-center">
                                <button type="button" onclick="selectEditType('Entrante')" class="type-button edit-type-button" data-type="Entrante">
                                    <div class="type-icon">
                                        <span class="text-xs">🫒</span>
                                    </div>
                                    <span>Entrante</span>
                                </button>
                                <button type="button" onclick="selectEditType('Principal')" class="type-button edit-type-button" data-type="Principal">
                                    <div class="type-icon">
                                        <span class="text-xs">🍽️</span>
                                    </div>
                                    <span>Principal</span>
                                </button>
                                <button type="button" onclick="selectEditType('Postre')" class="type-button edit-type-button" data-type="Postre">
                                    <div class="type-icon">
                                        <span class="text-xs">🍰</span>
                                    </div>
                                    <span>Postre</span>
                                </button>
                            </div>
                            <!-- Segunda línea: Bebida, Extra -->
                            <div class="flex gap-2 justify-center">
                                <button type="button" onclick="selectEditType('Bebida')" class="type-button edit-type-button" data-type="Bebida">
                                    <div class="type-icon">
                                        <span class="text-xs">🥤</span>
                                    </div>
                                    <span>Bebida</span>
                                </button>
                                <button type="button" onclick="selectEditType('Extra')" class="type-button edit-type-button" data-type="Extra">
                                    <div class="type-icon">
                                        <span class="text-xs">🧺</span>
                                    </div>
                                    <span>Extra</span>
                                </button>
                            </div>
                        </div>
                        <input type="hidden" name="tipo" id="edit-tipo" required />

                        <!-- Separador visual -->
                        <div class="border-t border-gray-200 my-6"></div>

                        <!-- Porciones y Tiempo -->
                        <div class="flex justify-center space-x-4 mb-6">
                            <div class="portions-container">
                                <div class="w-6 h-6 rounded-full bg-orange-100 border border-orange-200 flex items-center justify-center">
                                    <span class="text-orange-600 text-sm">🍽️</span>
                                </div>
                                <input type="number" name="porciones" id="edit-porciones" min="1" class="w-6 text-sm font-bold text-gray-800 bg-transparent border-none focus:outline-none text-center" placeholder="4" />
                                <span class="text-sm text-orange-600">porc</span>
                            </div>
                            
                            <div class="time-container">
                                <div class="w-6 h-6 rounded-full bg-blue-100 border border-blue-200 flex items-center justify-center">
                                    <span class="text-blue-600 text-sm">⏱️</span>
                                </div>
                                <input type="text" name="tiempo" id="edit-tiempo" class="w-6 text-sm font-bold text-gray-800 bg-transparent border-none focus:outline-none text-center" placeholder="30" />
                                <span class="text-sm text-blue-600">min</span>
                            </div>
                        </div>

                        <!-- Dificultad -->
                        <div class="flex justify-center mb-4">
                            <div class="flex gap-2">
                                <button type="button" onclick="selectEditDifficulty('Fácil')" class="difficulty-button edit-difficulty-button" data-difficulty="Fácil">
                                    <span class="text-sm">😊</span>
                                    <span>Fácil</span>
                                </button>
                                <button type="button" onclick="selectEditDifficulty('Medio')" class="difficulty-button edit-difficulty-button" data-difficulty="Medio">
                                    <span class="text-sm">😐</span>
                                    <span>Medio</span>
                                </button>
                                <button type="button" onclick="selectEditDifficulty('Difícil')" class="difficulty-button edit-difficulty-button" data-difficulty="Difícil">
                                    <span class="text-sm">😰</span>
                                    <span>Difícil</span>
                                </button>
                            </div>
                        </div>
                        <input type="hidden" name="dificultad" id="edit-dificultad" />

                        <!-- Valoración -->
                        <div class="flex justify-center mb-6">
                            <div class="flex items-center space-x-1">
                                <button type="button" onclick="setEditRating(1)" class="star-button edit-star-button" data-star="1">⭐</button>
                                <button type="button" onclick="setEditRating(2)" class="star-button edit-star-button" data-star="2">⭐</button>
                                <button type="button" onclick="setEditRating(3)" class="star-button edit-star-button" data-star="3">⭐</button>
                                <button type="button" onclick="setEditRating(4)" class="star-button edit-star-button" data-star="4">⭐</button>
                                <button type="button" onclick="setEditRating(5)" class="star-button edit-star-button" data-star="5">⭐</button>
                            </div>
                            <input type="hidden" name="valoracion" id="edit-valoracion" value="5" />
                        </div>

                        <!-- Ingredientes -->
                        <div class="ingredients-section mb-4">
                            <div class="ingredients-header">
                                <span class="text-2xl mr-2">🥘</span>
                                <span class="ingredients-title">Ingredientes</span>
                            </div>
                            <textarea name="ingredientes" id="edit-ingredientes" rows="4" class="ingredients-textarea" placeholder="Lista de ingredientes, uno por línea..." required></textarea>
                        </div>

                        <!-- Preparación -->
                        <div class="preparation-section mb-4">
                            <div class="preparation-header">
                                <span class="text-2xl mr-2">👨‍🍳</span>
                                <span class="preparation-title">Preparación</span>
                            </div>
                            <textarea name="preparacion" id="edit-preparacion" rows="8" class="preparation-textarea" placeholder="Pasos de preparación, uno por línea..." required></textarea>
                        </div>

                        <!-- Imagen -->
                        <div class="image-section rounded-xl p-4 shadow-sm mb-4">
                            <input type="file" id="edit-image-file" accept="image/*" class="hidden" onchange="handleEditImageUpload(this)" />
                            <input type="hidden" name="imagen" id="edit-imagen" />
                            
                            <div id="edit-image-container">
                                <!-- Se llenará dinámicamente con JavaScript -->
                            </div>
                        </div>

                        <!-- Video -->
                        <div class="image-section rounded-xl p-4 shadow-sm mb-4">
                            <input type="hidden" name="enlace_video" id="edit-enlace_video" />
                            
                            <div id="edit-video-container" class="video-upload-container">
                                <!-- Se llenará dinámicamente con JavaScript -->
                            </div>
                        </div>
                        
                        <input type="hidden" id="edit-recipe-id" name="id" />
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de detalle de receta -->
    <div id="recipeDetailModal" class="modal">
        <div class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>📖 Detalle de la Receta</h2>
                    <div class="modal-header-actions">
                        <button id="editRecipeBtnHeader" class="modal-action-btn edit" onclick="editRecipe()" title="Editar">
                            ✏️
                        </button>
                        <button id="deleteRecipeBtnHeader" class="modal-action-btn delete" onclick="deleteRecipe()" title="Eliminar">
                            🗑️
                        </button>
                        <button class="modal-close" onclick="closeModal('recipeDetailModal')">✕</button>
                    </div>
                </div>
                <div class="modal-body">
                    <!-- Título encima de la imagen -->
                    <h1 id="recipeDetailTitle" class="recipe-detail-title"></h1>
                    
                    <!-- Imagen de la receta -->
                    <div id="recipeDetailImageContainer"></div>
                    
                    <div class="recipe-detail-meta">
                        <span id="recipeDetailType" class="recipe-detail-badge recipe-detail-type"></span>
                        <div id="recipeDetailRating" class="recipe-detail-badge recipe-detail-rating"></div>
                        <span id="recipeDetailDifficulty" class="recipe-detail-badge recipe-detail-difficulty" style="display: none;"></span>
                        <span id="recipeDetailHealthy" class="recipe-detail-badge recipe-detail-healthy" style="display: none;">💚</span>
                        <span id="recipeDetailVideo" class="recipe-detail-badge recipe-detail-video" style="display: none;">🎥</span>
                    </div>
                    
                    <!-- Información adicional -->
                    <div class="recipe-detail-meta">
                        <span id="recipeDetailTime" class="recipe-detail-info" style="display: none;"></span>
                        <span id="recipeDetailPortions" class="recipe-detail-info" style="display: none;"></span>
                    </div>
                    
                    <!-- Ingredientes -->
                    <div class="recipe-detail-section">
                        <h3 class="recipe-detail-section-title">🥬 Ingredientes</h3>
                        <div id="recipeDetailIngredients" class="recipe-detail-content"></div>
                    </div>
                    
                    <!-- Preparación -->
                    <div class="recipe-detail-section">
                        <h3 class="recipe-detail-section-title">👨‍🍳 Preparación</h3>
                        <div id="recipeDetailPreparation" class="recipe-detail-content"></div>
                    </div>
                    
                    <!-- Video eliminado - ahora está en la barra superior -->
                    
                    <!-- Botones movidos al header -->
                </div>
            </div>
        </div>
    </div>

    <script src="js/main.js"></script>
    <script>
        // Variables para PWA
        let deferredPrompt;
        
        // Manejar evento de instalación PWA
        window.addEventListener('beforeinstallprompt', function(e) {
            console.log('PWA: Evento de instalación detectado');
            e.preventDefault(); // Prevenir el banner automático
            deferredPrompt = e;
            
            // Aquí podrías mostrar tu propio botón de instalación
            // showInstallButton();
        });
        
        // Detectar cuando se instala la PWA
        window.addEventListener('appinstalled', function(evt) {
            console.log('PWA: Aplicación instalada exitosamente');
            deferredPrompt = null;
        });
        
        // Registrar Service Worker final para PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('sw_final.js')
                    .then(function(registration) {
                        console.log('✅ SW registrado exitosamente');
                    })
                    .catch(function(err) {
                        console.log('❌ Error al registrar SW:', err);
                    });
            });
        }
        
        // Función para instalar PWA manualmente (para futuro uso)
        function installPWA() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then(function(choiceResult) {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('PWA: Usuario aceptó la instalación');
                    } else {
                        console.log('PWA: Usuario rechazó la instalación');
                    }
                    deferredPrompt = null;
                });
            }
        }
    </script>
</body>
</html>
