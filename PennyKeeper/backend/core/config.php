<?php
/*
 * config.php
 * Configuració global de l'aplicació.
 *
 * PER A ÚS LOCAL (XAMPP):
 *   APP_ENV  → 'development'
 *   APP_URL  → 'http://localhost/Projectes/PENNYKEEPER/frontend'
 *   DB_USER  → 'root'
 *   DB_PASS  → ''
 *
 * PER A PRODUCCIÓ (hosting):
 *   APP_ENV  → 'production'
 *   APP_URL  → 'https://el-teu-domini.com'
 *   DB_*     → credencials del hosting
 */

// ── Entorn ───────────────────────────────────────────────────
define('APP_ENV', 'production'); // canvia a 'production' al hosting

// ── App ──────────────────────────────────────────────────────
define('APP_NAME',    'PennyKeeper');
define('APP_VERSION', '1.0.0');

// ── URL base (sense trailing slash) ──────────────────────────
// LOCAL:
define('APP_URL', 'http://pennykeeper.es');
// PRODUCCIÓ (comenta l'anterior i descomenta aquesta):
// define('APP_URL', 'https://el-teu-domini.com');

// ── Base de dades ─────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_PORT',    3306);
define('DB_NAME',    'pennykeeper_db');
define('DB_USER',    'marccervera');      // hosting: usuari del panell de BD
define('DB_PASS',    'pokemonX1');        // hosting: contrasenya del panell de BD
define('DB_CHARSET', 'utf8mb4');

// ── Sessió ────────────────────────────────────────────────────
define('SESSION_LIFETIME', 28800); // 8 hores

// ── Errors ────────────────────────────────────────────────────
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}