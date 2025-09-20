<?php
require_once 'includes/auth.php';

// Cerrar sesión
logoutUser();

// Redirigir al login
header('Location: login.php');
exit();
?>
