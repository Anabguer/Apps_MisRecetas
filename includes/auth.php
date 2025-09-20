<?php
// Sistema de autenticación simplificado para PWA
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUserName() {
    return $_SESSION['user_name'] ?? 'Usuario';
}

function getCurrentUserEmail() {
    return $_SESSION['user_email'] ?? '';
}

function loginUser($userId, $userName, $userEmail) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $userName;
    $_SESSION['user_email'] = $userEmail;
    $_SESSION['login_time'] = time();
}

function logoutUser() {
    session_destroy();
    session_start();
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Para esta PWA, todos los usuarios logueados pueden crear/editar sus propias recetas
function canEditRecetas() {
    return isLoggedIn();
}

function canDeleteRecetas() {
    return isLoggedIn();
}

// Validar que el usuario solo pueda editar sus propias recetas
function canEditRecipe($recipeUserId) {
    return isLoggedIn() && getCurrentUserId() == $recipeUserId;
}
?>
