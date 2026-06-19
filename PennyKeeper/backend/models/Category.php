<?php
/*
 * Category.php
 * Model per gestionar les categories d'ingressos i despeses.
 * Cada usuari té les seves pròpies categories.
 */

require_once __DIR__ . '/../core/db.php';

class Category
{
    private PDO $db;

    // Categories per defecte que es creen en registrar un usuari nou
    private const DEFAULT_INCOME = [
        ['name' => 'Salari',           'icon' => 'bi-briefcase'],
        ['name' => 'Freelance',        'icon' => 'bi-laptop'],
        ['name' => 'Inversions',       'icon' => 'bi-graph-up-arrow'],
        ['name' => 'Altres ingressos', 'icon' => 'bi-plus-circle'],
    ];

    private const DEFAULT_EXPENSE = [
        ['name' => 'Habitatge',     'icon' => 'bi-house'],
        ['name' => 'Alimentació',   'icon' => 'bi-cart'],
        ['name' => 'Transport',     'icon' => 'bi-car-front'],
        ['name' => 'Salut',         'icon' => 'bi-heart-pulse'],
        ['name' => 'Oci',           'icon' => 'bi-controller'],
        ['name' => 'Subscripcions', 'icon' => 'bi-play-circle'],
        ['name' => 'Roba',          'icon' => 'bi-bag'],
        ['name' => 'Estalvi',       'icon' => 'bi-piggy-bank'],
        ['name' => 'Altres',        'icon' => 'bi-three-dots'],
    ];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /*
     * Crea les categories per defecte per a un usuari nou.
     * Es crida just després de registrar l'usuari.
     */
    public function createDefaults(int $userId): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO categories (userId, name, type, icon)
             VALUES (:userId, :name, :type, :icon)'
        );

        foreach (self::DEFAULT_INCOME as $cat) {
            $stmt->execute([
                ':userId' => $userId,
                ':name'   => $cat['name'],
                ':type'   => 'income',
                ':icon'   => $cat['icon'],
            ]);
        }

        foreach (self::DEFAULT_EXPENSE as $cat) {
            $stmt->execute([
                ':userId' => $userId,
                ':name'   => $cat['name'],
                ':type'   => 'expense',
                ':icon'   => $cat['icon'],
            ]);
        }
    }

    /*
     * Retorna totes les categories d'un usuari filtrades per tipus.
     * $type: 'income' | 'expense' | null (totes)
     */
    public function findByUser(int $userId, ?string $type = null): array
    {
        $sql    = 'SELECT * FROM categories WHERE userId = :userId';
        $params = [':userId' => $userId];

        if ($type !== null) {
            $sql .= ' AND type = :type';
            $params[':type'] = $type;
        }

        $sql .= ' ORDER BY name ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /*
     * Crea una categoria personalitzada.
     */
    public function create(int $userId, string $name, string $type, ?string $icon = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO categories (userId, name, type, icon)
             VALUES (:userId, :name, :type, :icon)'
        );
        $stmt->execute([
            ':userId' => $userId,
            ':name'   => $name,
            ':type'   => $type,
            ':icon'   => $icon,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /*
     * Elimina una categoria de l'usuari.
     */
    public function delete(int $categoryId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM categories WHERE id = :id AND userId = :userId'
        );
        return $stmt->execute([':id' => $categoryId, ':userId' => $userId]);
    }
}