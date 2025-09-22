// JavaScript principal para PWA Mis Recetas

// Variables globales
let currentRecipeId = null;
let currentRecipeData = null;

// Funciones de modales
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.classList.add('modal-open');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        
        // Limpiar formulario si es el modal de nueva receta
        if (modalId === 'newRecipeModal') {
            document.getElementById('newRecipeForm').reset();
        }
    }
}

function showNewRecipeModal() {
    showModal('newRecipeModal');
}

function showRecipeDetail(recipeId) {
    currentRecipeId = recipeId;
    
    // Obtener datos de la receta
    fetch(`api/get-recipe.php?id=${recipeId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentRecipeData = data.recipe;
                populateRecipeDetail(data.recipe);
                showModal('recipeDetailModal');
            } else {
                alert('❌ Error: ' + (data.error || 'No se pudo cargar la receta'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Error de conexión');
        });
}

function populateRecipeDetail(recipe) {
    // Iconos por tipo
    const tipoIcons = {
        'Entrante': '🫒',
        'Principal': '🍽️',
        'Postre': '🍰',
        'Bebida': '🥤',
        'Extra': '🧺'
    };
    
    // Imagen
    const imageContainer = document.getElementById('recipeDetailImageContainer');
    if (recipe.receta_image) {
        imageContainer.innerHTML = `<img src="${recipe.receta_image}" alt="${recipe.receta_nombre}" class="recipe-detail-image" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
        <div class="recipe-detail-fallback" style="display: none;">
            <span class="recipe-detail-fallback-icon">${tipoIcons[recipe.receta_tipo] || '🍳'}</span>
        </div>`;
    } else {
        imageContainer.innerHTML = `<div class="recipe-detail-fallback">
            <span class="recipe-detail-fallback-icon">${tipoIcons[recipe.receta_tipo] || '🍳'}</span>
        </div>`;
    }
    
    // Título
    document.getElementById('recipeDetailTitle').textContent = recipe.receta_nombre;
    
    // Tipo
    document.getElementById('recipeDetailType').textContent = `${tipoIcons[recipe.receta_tipo] || '🍳'} ${recipe.receta_tipo}`;
    
    // Valoración
    const rating = parseInt(recipe.receta_valoracion) || 0;
    const ratingElement = document.getElementById('recipeDetailRating');
    ratingElement.innerHTML = `⭐ ${rating}/5`;
    
    // Dificultad
    const difficultyElement = document.getElementById('recipeDetailDifficulty');
    if (recipe.receta_dificultad) {
        const difficultyIcons = {
            'Fácil': '😊',
            'Medio': '😐',
            'Difícil': '😰'
        };
        difficultyElement.textContent = `${difficultyIcons[recipe.receta_dificultad] || '😊'} ${recipe.receta_dificultad}`;
        difficultyElement.style.display = 'inline-block';
    } else {
        difficultyElement.style.display = 'none';
    }
    
    // Saludable
    const healthyElement = document.getElementById('recipeDetailHealthy');
    if (recipe.receta_saludable == 1) {
        healthyElement.style.display = 'inline-block';
    } else {
        healthyElement.style.display = 'none';
    }
    
    // Video en la barra superior
    const videoElement = document.getElementById('recipeDetailVideo');
    if (recipe.receta_video) {
        videoElement.style.display = 'inline-block';
        videoElement.onclick = function() {
            window.open(recipe.receta_video, '_blank');
        };
    } else {
        videoElement.style.display = 'none';
    }
    
    // Información adicional
    const timeElement = document.getElementById('recipeDetailTime');
    if (recipe.receta_tiempopreparacion) {
        timeElement.textContent = `⏱️ ${recipe.receta_tiempopreparacion}`;
        timeElement.style.display = 'inline-block';
    } else {
        timeElement.style.display = 'none';
    }
    
    const portionsElement = document.getElementById('recipeDetailPortions');
    if (recipe.receta_porciones) {
        portionsElement.textContent = `👥 ${recipe.receta_porciones}`;
        portionsElement.style.display = 'inline-block';
    } else {
        portionsElement.style.display = 'none';
    }
    
    // Ingredientes
    document.getElementById('recipeDetailIngredients').textContent = recipe.receta_ingredients;
    
    // Preparación
    document.getElementById('recipeDetailPreparation').textContent = recipe.receta_preparation;
    
    // Video ya se maneja en la barra superior
}

function editRecipe() {
    if (!currentRecipeData) {
        alert('❌ Error: No hay datos de la receta para editar');
        return;
    }
    
    // Cerrar modal de detalle
    closeModal('recipeDetailModal');
    
    // Rellenar formulario de edición con datos actuales
    document.getElementById('edit-nombre').value = currentRecipeData.receta_nombre;
    document.getElementById('edit-ingredientes').value = currentRecipeData.receta_ingredients;
    document.getElementById('edit-preparacion').value = currentRecipeData.receta_preparation;
    document.getElementById('edit-tiempo').value = currentRecipeData.receta_tiempopreparacion || '';
    document.getElementById('edit-porciones').value = currentRecipeData.receta_porciones || '';
    document.getElementById('edit-saludable').checked = currentRecipeData.receta_saludable == 1;
    document.getElementById('edit-imagen').value = currentRecipeData.receta_image || '';
    document.getElementById('edit-enlace_video').value = currentRecipeData.receta_video || '';
    document.getElementById('edit-valoracion').value = currentRecipeData.receta_valoracion || 5;
    document.getElementById('edit-recipe-id').value = currentRecipeId;
    
    // Seleccionar tipo
    selectEditType(currentRecipeData.receta_tipo);
    
    // Seleccionar dificultad
    selectEditDifficulty(currentRecipeData.receta_dificultad || 'Fácil');
    
    // Seleccionar valoración
    const currentRating = parseInt(currentRecipeData.receta_valoracion) || 5;
    setEditRating(currentRating);
    
    // Inicializar imagen y video
    setTimeout(() => {
        initializeImageContainer('edit-image-container', 'edit-imagen');
        initializeVideoContainer('edit-video-container', 'edit-enlace_video');
    }, 100);
    
    showModal('editRecipeModal');
}

function deleteRecipe() {
    if (!currentRecipeId || !currentRecipeData) {
        alert('❌ Error: No hay receta seleccionada para eliminar');
        return;
    }
    
    const recipeName = currentRecipeData.receta_nombre;
    
    if (confirm(`¿Estás seguro de que quieres eliminar la receta "${recipeName}"?\n\nEsta acción no se puede deshacer.`)) {
        // Mostrar loading
        const deleteBtn = document.getElementById('deleteRecipeBtn');
        const originalText = deleteBtn.innerHTML;
        deleteBtn.innerHTML = '⏳ Eliminando...';
        deleteBtn.disabled = true;
        
        fetch('api/delete-recipe.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: currentRecipeId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                closeModal('recipeDetailModal');
                location.reload(); // Recargar para actualizar la lista
            } else {
                alert('❌ Error: ' + (data.error || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Error de conexión');
        })
        .finally(() => {
            deleteBtn.innerHTML = originalText;
            deleteBtn.disabled = false;
        });
    }
}

// Función de logout
function logout() {
    if (confirm('¿Estás seguro de que quieres cerrar sesión?')) {
        window.location.href = 'logout.php';
    }
}

// Manejo del formulario de nueva receta / edición
document.addEventListener('DOMContentLoaded', function() {
    const newRecipeForm = document.getElementById('newRecipeForm');
    
    if (newRecipeForm) {
        newRecipeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const isEditMode = this.getAttribute('data-edit-mode') === 'true';
            const recipeId = this.getAttribute('data-recipe-id');
            
            // Recopilar datos del formulario
            const formData = new FormData(this);
            const recipeData = {
                nombre: formData.get('nombre'),
                tipo: formData.get('tipo'),
                ingredientes: formData.get('ingredientes'),
                preparacion: formData.get('preparacion'),
                tiempo: formData.get('tiempo'),
                dificultad: formData.get('dificultad'),
                porciones: formData.get('porciones'),
                valoracion: formData.get('valoracion'),
                saludable: formData.get('saludable') ? 1 : 0,
                imagen: formData.get('imagen'),
                enlace_video: formData.get('enlace_video')
            };
            
            // Si es modo edición, añadir ID
            if (isEditMode && recipeId) {
                recipeData.id = recipeId;
            }
            
            // Validar campos requeridos
            if (!recipeData.nombre || !recipeData.tipo || !recipeData.ingredientes || !recipeData.preparacion) {
                alert('Por favor completa todos los campos requeridos (*)');
                return;
            }
            
            // Mostrar loading
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = isEditMode ? '⏳ Actualizando...' : '⏳ Guardando...';
            submitBtn.disabled = true;
            
            // Determinar endpoint
            const endpoint = isEditMode ? 'api/update-recipe.php' : 'api/create-recipe.php';
            
            // Enviar datos
            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(recipeData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const message = isEditMode ? '✅ Receta actualizada exitosamente' : '✅ Receta creada exitosamente';
                    showToast(message, 'success');
                    closeModal('newRecipeModal');
                    
                    // Reset form mode
                    this.removeAttribute('data-edit-mode');
                    this.removeAttribute('data-recipe-id');
                    document.querySelector('#newRecipeModal .modal-header h2').textContent = '➕ Nueva Receta';
                    submitBtn.innerHTML = '💾 Guardar Receta';
                    
                    location.reload(); // Recargar para mostrar los cambios
                } else {
                    alert('❌ Error: ' + (data.error || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Error de conexión');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }
    
    // Cerrar modal al hacer clic fuera
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-overlay')) {
            const modal = e.target.closest('.modal');
            if (modal) {
                closeModal(modal.id);
            }
        }
    });
    
    // Escape para cerrar modales
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal[style*="block"]');
            openModals.forEach(modal => {
                closeModal(modal.id);
            });
        }
    });
});

// Funciones de utilidad
function showToast(message, type = 'info') {
    // Crear toast notification
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    
    // Estilos del toast
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'error' ? '#e74c3c' : type === 'success' ? '#27ae60' : '#4ECDC4'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 10000;
        opacity: 0;
        transform: translateX(100%);
        transition: all 0.3s ease;
        max-width: 300px;
        word-wrap: break-word;
        font-family: 'Geist', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;
    `;
    
    document.body.appendChild(toast);
    
    // Animar entrada
    setTimeout(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(0)';
    }, 100);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 3000);
}

// Función para manejar errores de imágenes
function handleImageError(img) {
    img.style.display = 'none';
    const fallback = img.nextElementSibling;
    if (fallback && fallback.classList.contains('recipe-fallback')) {
        fallback.style.display = 'flex';
    }
}

// Funciones para PWA
function checkInstallPrompt() {
    let deferredPrompt;
    
    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevenir que Chrome 67 y anteriores muestren automáticamente el prompt
        e.preventDefault();
        // Guardar el evento para poder dispararlo después
        deferredPrompt = e;
        
        // Mostrar botón de instalación personalizado
        showInstallButton();
    });
    
    function showInstallButton() {
        const installBtn = document.createElement('button');
        installBtn.textContent = '📱 Instalar App';
        installBtn.className = 'install-btn';
        installBtn.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #4ECDC4;
            color: white;
            border: none;
            padding: 0.75rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 1000;
            transition: all 0.3s;
            font-family: 'Geist', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;
        `;
        
        installBtn.addEventListener('click', () => {
            // Mostrar el prompt de instalación
            deferredPrompt.prompt();
            // Esperar a que el usuario responda al prompt
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('Usuario aceptó instalar la PWA');
                    showToast('¡Aplicación instalada correctamente!', 'success');
                } else {
                    console.log('Usuario rechazó instalar la PWA');
                }
                deferredPrompt = null;
                installBtn.remove();
            });
        });
        
        document.body.appendChild(installBtn);
        
        // Ocultar después de 10 segundos si no se usa
        setTimeout(() => {
            if (installBtn.parentNode) {
                installBtn.style.opacity = '0';
                setTimeout(() => installBtn.remove(), 300);
            }
        }, 10000);
    }
}

// Inicializar funciones PWA
document.addEventListener('DOMContentLoaded', function() {
    checkInstallPrompt();
    
    // Detectar si está instalada como PWA
    if (window.navigator.standalone || window.matchMedia('(display-mode: standalone)').matches) {
        console.log('Ejecutándose como PWA instalada');
        document.body.classList.add('pwa-installed');
    }
});

// Funciones de conectividad
window.addEventListener('online', function() {
    showToast('Conexión restaurada', 'success');
});

window.addEventListener('offline', function() {
    showToast('Sin conexión a internet', 'error');
});

// Función para validar URLs
function isValidUrl(string) {
    try {
        new URL(string);
        return true;
    } catch (_) {
        return false;
    }
}

// Auto-resize de textareas
document.addEventListener('DOMContentLoaded', function() {
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    });
    
    // Inicializar contenedores de imagen y video
    initializeImageContainer('new-image-container', 'new-imagen');
    initializeImageContainer('edit-image-container', 'edit-imagen');
    initializeVideoContainer('new-video-container', 'new-enlace_video');
    initializeVideoContainer('edit-video-container', 'edit-enlace_video');
});

// ===== FUNCIONES PARA IMAGEN Y VIDEO =====

function initializeImageContainer(containerId, inputId) {
    const container = document.getElementById(containerId);
    const input = document.getElementById(inputId);
    
    if (!container) return;
    
    const currentValue = input ? input.value : '';
    
    if (currentValue) {
        showImagePreview(containerId, currentValue, inputId);
    } else {
        showImageUploadButton(containerId, inputId);
    }
}

function initializeVideoContainer(containerId, inputId) {
    const container = document.getElementById(containerId);
    const input = document.getElementById(inputId);
    
    if (!container) return;
    
    const currentValue = input ? input.value : '';
    
    if (currentValue) {
        showVideoButtons(containerId, inputId, true);
    } else {
        showVideoButtons(containerId, inputId, false);
    }
}

function showImageUploadButton(containerId, inputId) {
    const container = document.getElementById(containerId);
    const fileInputId = containerId.replace('-container', '-file');
    
    container.innerHTML = `
        <div class="image-no-preview" onclick="document.getElementById('${fileInputId}').click()">
            <div class="image-no-preview-button">
                <div class="image-no-preview-icon">🖼️</div>
                <div class="image-no-preview-text">Subir imagen</div>
            </div>
        </div>
    `;
}

function showImagePreview(containerId, imageUrl, inputId) {
    const container = document.getElementById(containerId);
    const fileInputId = containerId.replace('-container', '-file');
    
    container.innerHTML = `
        <div class="image-preview-container" onclick="document.getElementById('${fileInputId}').click()">
            <img src="${imageUrl}" alt="Preview" class="image-preview-img" />
            <button type="button" class="image-change-btn" onclick="event.stopPropagation(); document.getElementById('${fileInputId}').click()">
                📝 Cambiar
            </button>
            <button type="button" class="image-remove-btn" onclick="event.stopPropagation(); removeImage('${containerId}', '${inputId}')">
                ✕
            </button>
        </div>
    `;
}

function showVideoButtons(containerId, inputId, hasVideo) {
    const container = document.getElementById(containerId);
    
    if (hasVideo) {
        const videoUrl = document.getElementById(inputId).value;
        container.innerHTML = `
            <button type="button" class="video-change-btn" onclick="changeVideo('${inputId}')">
                🎥 Cambiar video
            </button>
            <button type="button" class="video-remove-btn" onclick="removeVideo('${containerId}', '${inputId}')">
                ✕ Quitar
            </button>
            <a href="${videoUrl}" target="_blank" class="video-upload-btn">
                👁️ Ver
            </a>
        `;
    } else {
        container.innerHTML = `
            <button type="button" class="video-upload-btn" onclick="addVideo('${inputId}')">
                🎥 Añadir video
            </button>
        `;
    }
}

function handleNewImageUpload(input) {
    handleImageUpload(input, 'new-image-container', 'new-imagen');
}

function handleEditImageUpload(input) {
    handleImageUpload(input, 'edit-image-container', 'edit-imagen');
}

function handleImageUpload(input, containerId, inputId) {
    const file = input.files[0];
    if (!file) return;
    
    // Crear URL temporal para preview
    const reader = new FileReader();
    reader.onload = function(e) {
        const imageUrl = e.target.result;
        document.getElementById(inputId).value = imageUrl;
        showImagePreview(containerId, imageUrl, inputId);
    };
    reader.readAsDataURL(file);
}

function removeImage(containerId, inputId) {
    document.getElementById(inputId).value = '';
    showImageUploadButton(containerId, inputId);
}

function addVideo(inputId) {
    const url = prompt('Introduce la URL del video:');
    if (url && isValidUrl(url)) {
        document.getElementById(inputId).value = url;
        const containerId = inputId.replace('enlace_video', 'video-container');
        showVideoButtons(containerId, inputId, true);
    }
}

function changeVideo(inputId) {
    const currentUrl = document.getElementById(inputId).value;
    const url = prompt('Introduce la nueva URL del video:', currentUrl);
    if (url !== null) {
        const containerId = inputId.replace('enlace_video', 'video-container');
        if (url && isValidUrl(url)) {
            document.getElementById(inputId).value = url;
            showVideoButtons(containerId, inputId, true);
        } else if (url === '') {
            removeVideo(containerId, inputId);
        }
    }
}

function removeVideo(containerId, inputId) {
    document.getElementById(inputId).value = '';
    showVideoButtons(containerId, inputId, false);
}

// Funciones para búsqueda expandible inline
function toggleSearchInline() {
    // Crear parámetros URL para expandir búsqueda
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.set('expand_search', '1');
    
    // Mantener otros filtros
    const currentUrl = new URL(window.location);
    currentUrl.search = urlParams.toString();
    
    // Redirigir con parámetro para mostrar campo expandido
    window.location.href = currentUrl.toString();
}

function clearSearchInline() {
    // Crear nueva URL sin parámetro de búsqueda
    const urlParams = new URLSearchParams(window.location.search);
    urlParams.delete('busqueda');
    urlParams.delete('expand_search');
    
    const currentUrl = new URL(window.location);
    currentUrl.search = urlParams.toString();
    
    // Redirigir para limpiar búsqueda
    window.location.href = currentUrl.toString();
}

// Función para búsqueda en tiempo real
let searchTimeout;
function searchAsYouType(input) {
    // Limpiar timeout anterior
    clearTimeout(searchTimeout);
    
    // Esperar 500ms después de que el usuario deje de escribir
    searchTimeout = setTimeout(() => {
        const value = input.value.trim();
        const urlParams = new URLSearchParams(window.location.search);
        
        if (value) {
            urlParams.set('busqueda', value);
            urlParams.set('expand_search', '1');
        } else {
            urlParams.delete('busqueda');
            urlParams.delete('expand_search');
        }
        
        const currentUrl = new URL(window.location);
        currentUrl.search = urlParams.toString();
        
        // Redirigir con la nueva búsqueda
        window.location.href = currentUrl.toString();
    }, 500);
}

// Funciones para nueva receta
function selectNewType(type) {
    // Quitar active de todos los botones
    document.querySelectorAll('.type-button[data-type]').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Activar el seleccionado
    document.querySelector(`.type-button[data-type="${type}"]`).classList.add('active');
    
    // Guardar en input hidden
    document.getElementById('new-tipo').value = type;
}

function selectNewDifficulty(difficulty) {
    // Quitar active de todos los botones de dificultad
    document.querySelectorAll('.difficulty-button[data-difficulty]').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Activar el seleccionado
    document.querySelector(`.difficulty-button[data-difficulty="${difficulty}"]`).classList.add('active');
    
    // Guardar en input hidden
    document.getElementById('new-dificultad').value = difficulty;
}

function setNewRating(rating) {
    console.log('Setting new rating to:', rating); // Debug
    
    // Quitar active de todas las estrellas
    document.querySelectorAll('.star-button[data-star]').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Validar rating
    const validRating = Math.max(0, Math.min(5, parseInt(rating) || 5));
    
    // Activar las estrellas hasta el rating seleccionado
    for (let i = 1; i <= validRating; i++) {
        const star = document.querySelector(`.star-button[data-star="${i}"]`);
        if (star) {
            star.classList.add('active');
        }
    }
    
    // Guardar en input hidden
    document.getElementById('new-valoracion').value = validRating;
}

// Funciones para editar receta
function selectEditType(type) {
    // Quitar active de todos los botones
    document.querySelectorAll('.edit-type-button[data-type]').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Activar el seleccionado
    const button = document.querySelector(`.edit-type-button[data-type="${type}"]`);
    if (button) {
        button.classList.add('active');
    }
    
    // Guardar en input hidden
    document.getElementById('edit-tipo').value = type;
}

function selectEditDifficulty(difficulty) {
    // Quitar active de todos los botones de dificultad
    document.querySelectorAll('.edit-difficulty-button[data-difficulty]').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Activar el seleccionado
    const button = document.querySelector(`.edit-difficulty-button[data-difficulty="${difficulty}"]`);
    if (button) {
        button.classList.add('active');
    }
    
    // Guardar en input hidden
    document.getElementById('edit-dificultad').value = difficulty;
}

function setEditRating(rating) {
    console.log('Setting edit rating to:', rating); // Debug
    
    // Quitar active de todas las estrellas
    document.querySelectorAll('.edit-star-button[data-star]').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Validar rating
    const validRating = Math.max(0, Math.min(5, parseInt(rating) || 5));
    
    // Activar las estrellas hasta el rating seleccionado
    for (let i = 1; i <= validRating; i++) {
        const star = document.querySelector(`.edit-star-button[data-star="${i}"]`);
        if (star) {
            star.classList.add('active');
        }
    }
    
    // Guardar en input hidden
    document.getElementById('edit-valoracion').value = validRating;
}

// Manejar envío de nueva receta
function handleNewRecipe(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const recipeData = {
        nombre: formData.get('nombre'),
        tipo: formData.get('tipo'),
        ingredientes: formData.get('ingredientes'),
        preparacion: formData.get('preparacion'),
        tiempo: formData.get('tiempo'),
        dificultad: formData.get('dificultad'),
        porciones: formData.get('porciones'),
        valoracion: formData.get('valoracion'),
        saludable: formData.get('saludable') ? 1 : 0,
        imagen: formData.get('imagen'),
        enlace_video: formData.get('enlace_video')
    };
    
    // Validar campos requeridos
    if (!recipeData.nombre || !recipeData.tipo || !recipeData.ingredientes || !recipeData.preparacion) {
        alert('Por favor completa todos los campos requeridos (*)');
        return;
    }
    
    // Enviar datos
    fetch('api/create-recipe.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(recipeData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('✅ Receta creada exitosamente', 'success');
            closeModal('newRecipeModal');
            location.reload();
        } else {
            alert('❌ Error: ' + (data.error || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error de conexión');
    });
}

// Debug eliminado - funciones verificadas

// Manejar envío de edición de receta
function handleEditRecipe(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const recipeData = {
        id: formData.get('id'),
        nombre: formData.get('nombre'),
        tipo: formData.get('tipo'),
        ingredientes: formData.get('ingredientes'),
        preparacion: formData.get('preparacion'),
        tiempo: formData.get('tiempo'),
        dificultad: formData.get('dificultad'),
        porciones: formData.get('porciones'),
        valoracion: formData.get('valoracion'),
        saludable: formData.get('saludable') ? 1 : 0,
        imagen: formData.get('imagen'),
        enlace_video: formData.get('enlace_video')
    };
    
    // Validar campos requeridos
    if (!recipeData.nombre || !recipeData.tipo || !recipeData.ingredientes || !recipeData.preparacion) {
        alert('Por favor completa todos los campos requeridos (*)');
        return;
    }
    
    // Enviar datos
    fetch('api/update-recipe.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(recipeData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('✅ Receta actualizada exitosamente', 'success');
            closeModal('editRecipeModal');
            location.reload();
        } else {
            alert('❌ Error: ' + (data.error || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error de conexión');
    });
}

// Debug eliminado - funciones verificadas