<?php
/*
 * db.php
 * Connexió a la base de dades mitjançant PDO.
 * Implementa el patró Singleton per reutilitzar la mateixa connexió.
 */

require_once __DIR__ . '/config.php';

class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                if (APP_ENV === 'development') {
                    die('Error de connexió: ' . $e->getMessage());
                }
                die('No s\'ha pogut connectar amb la base de dades.');
            }
        }

        return self::$instance;
    }
}