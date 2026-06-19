<?php
/*
 * Income.php
 * Model per gestionar els ingressos de l'usuari.
 * Cobreix ingressos fixos mensuals i puntuals.
 */

require_once __DIR__ . '/../core/db.php';

class Income
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /*
     * Afegeix un nou ingrés.
     * Retorna l'id inserit.
     */
    public function create(int $userId, array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO incomes (userId, categoryId, description, amount, isRecurring, date, notes)
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
     * Retorna tots els ingressos d'un usuari, ordenats per data desc.
     * Opcionalment filtrats per mes i any.
     */
    public function findByUser(int $userId, ?int $month = null, ?int $year = null): array
    {
        $sql = 'SELECT i.*, c.name AS categoryName, c.icon AS categoryIcon
                FROM incomes i
                LEFT JOIN categories c ON c.id = i.categoryId
                WHERE i.userId = :userId';

        $params = [':userId' => $userId];

        if ($month !== null && $year !== null) {
            $sql .= ' AND MONTH(i.date) = :month AND YEAR(i.date) = :year';
            $params[':month'] = $month;
            $params[':year']  = $year;
        }

        $sql .= ' ORDER BY i.date DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /*
     * Retorna només els ingressos recurrents de l'usuari.
     */
    public function findRecurring(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT i.*, c.name AS categoryName
             FROM incomes i
             LEFT JOIN categories c ON c.id = i.categoryId
             WHERE i.userId = :userId AND i.isRecurring = 1
             ORDER BY i.description ASC'
        );
        $stmt->execute([':userId' => $userId]);
        return $stmt->fetchAll();
    }

    /*
     * Suma total d'ingressos d'un usuari en un mes/any concret.
     */
    public function sumByMonth(int $userId, int $month, int $year): float
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(amount), 0)
             FROM incomes
             WHERE userId = :userId
               AND MONTH(date) = :month
               AND YEAR(date)  = :year'
        );
        $stmt->execute([':userId' => $userId, ':month' => $month, ':year' => $year]);
        return (float) $stmt->fetchColumn();
    }

    /*
     * Actualitza un ingrés existent.
     * Retorna true si s'ha modificat alguna fila.
     */
    public function update(int $incomeId, int $userId, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE incomes
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
            ':id'          => $incomeId,
            ':userId'      => $userId,
        ]);
    }

    /*
     * Elimina un ingrés.
     * El userId evita que un usuari esborri dades d'un altre.
     */
    public function delete(int $incomeId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM incomes WHERE id = :id AND userId = :userId'
        );
        return $stmt->execute([':id' => $incomeId, ':userId' => $userId]);
    }
}