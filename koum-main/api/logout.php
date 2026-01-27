<?php
/**
 * NEXT DRIVE IMPORT - Logout v2.1.0
 * Déconnexion sécurisée de l'admin
 */

// Démarrer la session
session_start();

// Détruire toutes les variables de session
$_SESSION = array();

// Supprimer le cookie de session si existant
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Détruire la session
session_destroy();

// Redirection vers la page de login
header('Location: login.html');
exit;
?>