<?php
/*
 * index.php
 * Punt d'entrada de l'aplicació.
 * Redirigeix al dashboard si hi ha sessió, o a la landing si no.
 */

require_once __DIR__ . '/../backend/core/config.php';
require_once __DIR__ . '/../backend/core/auth.php';

Auth::startSession();

if (Auth::isLoggedIn()) {
    header('Location: ' . APP_URL . '/pages/dashboard.php');
} else {
    header('Location: ' . APP_URL . '/pages/landing.php');
}
exit;