<?php
/*
 * User.php
 * Model per gestionar els usuaris de l'aplicació.
 * Totes les operacions de BD relacionades amb `users` passen per aquí.
 */

require_once __DIR__ . '/../core/db.php';

class User
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /*
     * Crea un usuari nou.
     * Retorna l'id inserit o false si l'email/username ja existeix.
     */
    public function create(string $username, string $email, string $password): int|false
    {
        $stmt = $this->db->prepare(
            'INSERT INTO users (username, email, passwordHash)
             VALUES (:username, :email, :passwordHash)'
        );

        try {
            $stmt->execute([
                ':username'     => $username,
                ':email'        => $email,
                ':passwordHash' => password_hash($password, PASSWORD_BCRYPT),
            ]);
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            // Clau duplicada (email o username ja existeix)
            if ($e->getCode() === '23000') {
                return false;
            }
            throw $e;
        }
    }

    /*
     * Busca un usuari per email.
     * Retorna l'array de dades o null si no existeix.
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, email, passwordHash, currency, createdAt
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /*
     * Busca un usuari per id.
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, username, email, currency, createdAt
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /*
     * Verifica les credencials d'un usuari.
     * Retorna les dades de l'usuari si són correctes, null si no.
     */
    public function verify(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);

        if ($user === null) {
            return null;
        }

        if (!password_verify($password, $user['passwordHash'])) {
            return null;
        }

        return $user;
    }

    /*
     * Actualitza la moneda preferida de l'usuari.
     */
    public function updateCurrency(int $userId, string $currency): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE users SET currency = :currency WHERE id = :id'
        );
        return $stmt->execute([':currency' => $currency, ':id' => $userId]);
    }

    /*
     * Comprova si un email ja està registrat.
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM users WHERE email = :email'
        );
        $stmt->execute([':email' => $email]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /*
     * Comprova si un username ja està registrat.
     */
    public function usernameExists(string $username): bool
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM users WHERE username = :username'
        );
        $stmt->execute([':username' => $username]);
        return (int) $stmt->fetchColumn() > 0;
    }
}