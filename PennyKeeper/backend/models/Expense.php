<?php
/*
 * Expense.php
 * Model per gestionar les despeses de l'usuari.
 * Estructura idèntica a Income però sobre la taula `expenses`.
 */

require_once __DIR__ . '/../core/db.php';

class Expense
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /*
     * Afegeix una nova despesa.
     * Retorna l'id inserit.
     */
    public function create(int $userId, array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO expenses (userId, categoryId, description, amount, isRecurring, date, notes)
             VALUES (:userId, :categoryId, :description, :amount, :isRecurring, :date, :notes)'
        );

        $stmt->execute([
            ':userId'      => $userId,
            ':categoryId'  => $data['categoryId'] ?? null,
            ':description' => $data['description'],
            ':amount'      => $data['amount'],
            ':isRecurring' => $data['isRecurring'] ? 1 : 0,
            ':date'        => $data['date'],
            ':notes'       => $data['notes'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /*
     * Retorna totes les despeses d'un usuari, ordenades per data desc.
     * Opcionalment filtrades per mes i any.
     */
    public function findByUser(int $userId, ?int $month = null, ?int $year = null): array
    {
        $sql = 'SELECT e.*, c.name AS categoryName, c.icon AS categoryIcon
                FROM expenses e
                LEFT JOIN categories c ON c.id = e.categoryId
                WHERE e.userId = :userId';

        $params = [':userId' => $userId];

        if ($month !== null && $year !== null) {
            $sql .= ' AND MONTH(e.date) = :month AND YEAR(e.date) = :year';
            $params[':month'] = $month;
            $params[':year']  = $year;
        }

        $sql .= ' ORDER BY e.date DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /*
     * Retorna només les despeses recurrents de l'usuari.
     */
    public function findRecurring(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT e.*, c.name AS categoryName
             FROM expenses e
             LEFT JOIN categories c ON c.id = e.categoryId
             WHERE e.userId = :userId AND e.isRecurring = 1
             ORDER BY e.description ASC'
        );
        $stmt->execute([':userId' => $userId]);
        return $stmt->fetchAll();
    }

    /*
     * Suma total de despeses d'un usuari en un mes/any concret.
     */
    public function sumByMonth(int $userId, int $month, int $year): float
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(amount), 0)
             FROM expenses
             WHERE userId = :userId
               AND MONTH(date) = :month
               AND YEAR(date)  = :year'
        );
        $stmt->execute([':userId' => $userId, ':month' => $month, ':year' => $year]);
        return (float) $stmt->fetchColumn();
    }

    /*
     * Suma de despeses agrupades per categoria en un mes/any.
     * Útil per als gràfics del dashboard.
     */
    public function sumByCategoryAndMonth(int $userId, int $month, int $year): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.name AS category, c.icon, COALESCE(SUM(e.amount), 0) AS total
             FROM expenses e
             LEFT JOIN categories c ON c.id = e.categoryId
             WHERE e.userId = :userId
               AND MONTH(e.date) = :month
               AND YEAR(e.date)  = :year
             GROUP BY e.categoryId
             ORDER BY total DESC'
        );
        $stmt->execute([':userId' => $userId, ':month' => $month, ':year' => $year]);
        return $stmt->fetchAll();
    }

    /*
     * Actualitza una despesa existent.
     */
    public function update(int $expenseId, int $userId, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE expenses
             SET categoryId  = :categoryId,
                 description = :description,
                 amount      = :amount,
                 isRecurring = :isRecurring,
                 date        = :date,
                 notes       = :notes
             WHERE id = :id AND userId = :userId'
        );

        return $stmt->execute([
            ':categoryId'  => $data['categoryId'] ?? null,
            ':description' => $data['description'],
            ':amount'      => $data['amount'],
            ':isRecurring' => $data['isRecurring'] ? 1 : 0,
            ':date'        => $data['date'],
            ':notes'       => $data['notes'] ?? null,
            ':id'          => $expenseId,
            ':userId'      => $userId,
        ]);
    }

    /*
     * Elimina una despesa.
     * El userId evita que un usuari esborri dades d'un altre.
     */
    public function delete(int $expenseId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM expenses WHERE id = :id AND userId = :userId'
        );
        return $stmt->execute([':id' => $expenseId, ':userId' => $userId]);
    }
}