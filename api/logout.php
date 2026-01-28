<?php
require_once __DIR__ . '/security.php';

// Détruire toutes les variables de session
$_SESSION = array();

// Détruire la session
session_destroy();

// Redirection vers la page de login
header('Location: login.html');
exit;
?>