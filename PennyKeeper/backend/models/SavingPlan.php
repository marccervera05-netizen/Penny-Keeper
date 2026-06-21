<?php
/*
 * SavingPlan.php
 * Model per gestionar plans d'estalvi i les seves aportacions.
 */

require_once __DIR__ . '/../core/db.php';

class SavingPlan
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /*
     * Crea un nou pla d'estalvi.
     */
    public function create(int $userId, array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO saving_plans (userId, name, targetAmount, savedAmount, deadline, notes)
             VALUES (:userId, :name, :targetAmount, :savedAmount, :deadline, :notes)'
        );

        $stmt->execute([
            ':userId'       => $userId,
            ':name'         => $data['name'],
            ':targetAmount' => $data['targetAmount'],
            ':savedAmount'  => $data['savedAmount'] ?? 0.00,
            ':deadline'     => $data['deadline'] ?? null,
            ':notes'        => $data['notes'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /*
     * Retorna tots els plans d'un usuari filtrats per estat.
     * $status: 'active' | 'completed' | 'cancelled' | null (tots)
     */
    public function findByUser(int $userId, ?string $status = null): array
    {
        $sql    = 'SELECT * FROM saving_plans WHERE userId = :userId';
        $params = [':userId' => $userId];

        if ($status !== null) {
            $sql .= ' AND status = :status';
            $params[':status'] = $status;
        }

        $sql .= ' ORDER BY createdAt DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /*
     * Retorna un pla concret verificant que pertany a l'usuari.
     */
    public function findById(int $planId, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM saving_plans WHERE id = :id AND userId = :userId LIMIT 1'
        );
        $stmt->execute([':id' => $planId, ':userId' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /*
     * Actualitza les dades bàsiques d'un pla.
     */
    public function update(int $planId, int $userId, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE saving_plans
             SET name         = :name,
                 targetAmount = :targetAmount,
                 deadline     = :deadline,
                 notes        = :notes,
                 status       = :status
             WHERE id = :id AND userId = :userId'
        );

        return $stmt->execute([
            ':name'         => $data['name'],
            ':targetAmount' => $data['targetAmount'],
            ':deadline'     => $data['deadline'] ?? null,
            ':notes'        => $data['notes'] ?? null,
            ':status'       => $data['status'] ?? 'active',
            ':id'           => $planId,
            ':userId'       => $userId,
        ]);
    }

    /*
     * Elimina un pla i totes les seves aportacions (CASCADE a la BD).
     */
    public function delete(int $planId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM saving_plans WHERE id = :id AND userId = :userId'
        );
        return $stmt->execute([':id' => $planId, ':userId' => $userId]);
    }

    /*
     * Afegeix una aportació a un pla i actualitza savedAmount.
     */
    public function addContribution(int $planId, int $userId, float $amount, string $date, ?string $notes = null): bool
    {
        $plan = $this->findById($planId, $userId);
        if ($plan === null) return false;

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO saving_contributions (planId, userId, amount, date, notes)
                 VALUES (:planId, :userId, :amount, :date, :notes)'
            );
            $stmt->execute([
                ':planId' => $planId,
                ':userId' => $userId,
                ':amount' => $amount,
                ':date'   => $date,
                ':notes'  => $notes,
            ]);

            $newSaved = (float) $plan['savedAmount'] + $amount;

            $status = $newSaved >= (float) $plan['targetAmount'] ? 'completed' : 'active';

            $stmt = $this->db->prepare(
                'UPDATE saving_plans
                 SET savedAmount = :savedAmount, status = :status
                 WHERE id = :id'
            );
            $stmt->execute([
                ':savedAmount' => $newSaved,
                ':status'      => $status,
                ':id'          => $planId,
            ]);

            $this->db->commit();
            return true;

        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /*
     * Retorna les aportacions d'un pla ordenades per data desc.
     */
    public function getContributions(int $planId, int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT sc.*
             FROM saving_contributions sc
             INNER JOIN saving_plans sp ON sp.id = sc.planId
             WHERE sc.planId = :planId AND sp.userId = :userId
             ORDER BY sc.date DESC'
        );
        $stmt->execute([':planId' => $planId, ':userId' => $userId]);
        return $stmt->fetchAll();
    }

    /*
     * Elimina una aportació i resta l'import del savedAmount del pla.
     */
    public function deleteContribution(int $contributionId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT sc.amount, sc.planId
             FROM saving_contributions sc
             INNER JOIN saving_plans sp ON sp.id = sc.planId
             WHERE sc.id = :id AND sp.userId = :userId
             LIMIT 1'
        );
        $stmt->execute([':id' => $contributionId, ':userId' => $userId]);
        $contribution = $stmt->fetch();

        if (!$contribution) return false;

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'DELETE FROM saving_contributions WHERE id = :id'
            );
            $stmt->execute([':id' => $contributionId]);

            $stmt = $this->db->prepare(
                'UPDATE saving_plans
                 SET savedAmount = GREATEST(0, savedAmount - :amount),
                     status = CASE
                         WHEN GREATEST(0, savedAmount - :amount2) < targetAmount THEN "active"
                         ELSE status
                     END
                 WHERE id = :planId'
            );
            $stmt->execute([
                ':amount'  => $contribution['amount'],
                ':amount2' => $contribution['amount'],
                ':planId'  => $contribution['planId'],
            ]);

            $this->db->commit();
            return true;

        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}