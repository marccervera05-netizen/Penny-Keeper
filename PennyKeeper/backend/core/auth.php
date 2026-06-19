<?php
/*
 * auth.php
 * Gestió de sessió i helpers d'autenticació.
 * Qualsevol pàgina protegida ha de cridar Auth::require() al principi.
 */

require_once __DIR__ . '/config.php';

class Auth
{
    /*
     * Inicia la sessió si no està activa.
     */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path'     => '/',
                'secure'   => APP_ENV === 'production',
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
            session_start();
        }
    }

    /*
     * Comprova si hi ha un usuari autenticat.
     */
    public static function isLoggedIn(): bool
    {
        self::startSession();
        return isset($_SESSION['userId']) && is_int($_SESSION['userId']);
    }

    /*
     * Redirigeix a login si l'usuari no està autenticat.
     * Usat al principi de qualsevol pàgina protegida.
     */
    public static function require(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: ' . APP_URL . '/pages/login.php');
            exit;
        }
    }

    /*
     * Retorna l'id de l'usuari de la sessió activa.
     * Retorna null si no hi ha sessió.
     */
    public static function userId(): ?int
    {
        self::startSession();
        return $_SESSION['userId'] ?? null;
    }

    /*
     * Retorna el nom d'usuari de la sessió activa.
     */
    public static function username(): ?string
    {
        self::startSession();
        return $_SESSION['username'] ?? null;
    }

    /*
     * Inicia sessió per a un usuari (crida després de verificar credencials).
     */
    public static function login(int $userId, string $username): void
    {
        self::startSession();
        session_regenerate_id(true);
        $_SESSION['userId']   = $userId;
        $_SESSION['username'] = $username;
    }

    /*
     * Tanca la sessió i redirigeix a login.
     */
    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        session_destroy();
        header('Location: ' . APP_URL . '/pages/login.php');
        exit;
    }
}