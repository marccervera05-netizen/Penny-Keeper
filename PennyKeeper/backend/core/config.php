<?php
/*
 * config.php
 * Configuració global de l'aplicació.
 * Totes les constants que usen la resta d'arxius es defineixen aquí.
 */

// Entorn: 'development' | 'production'
define('APP_ENV', 'development');

// Nom i versió
define('APP_NAME', 'PennyKeeper');
define('APP_VERSION', '1.0.0');

// URL base (sense trailing slash)
// Ajusta el path si no estàs a l'arrel de XAMPP
define('APP_URL', 'http://localhost/Projectes/PENNYKEEPER/frontend');

// Configuració de la base de dades
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'pennykeeper_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Durada de la sessió en segons (8 hores)
define('SESSION_LIFETIME', 28800);

// Errors: en development mostrem tot, en production res
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}