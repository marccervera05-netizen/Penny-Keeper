<?php
/*
 * Investment.php
 * Model per gestionar inversions amb aportacions periòdiques
 * i historial de valor de mercat mes a mes.
 */

require_once __DIR__ . '/../core/db.php';

class Investment
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // ── CRUD inversions ──────────────────────────────────────

    /*
     * Crea una nova inversió.
     * Si té aportació inicial, la insereix a investment_contributions.
     */
    public function create(int $userId, array $data): int
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO investments (userId, name, type, initialAmount, currentValue, startDate, isRecurring, notes)
                 VALUES (:userId, :name, :type, :initialAmount, :currentValue, :startDate, :isRecurring, :notes)'
            );

            $stmt->execute([
                ':userId'        => $userId,
                ':name'          => $data['name'],
                ':type'          => $data['type'],
                ':initialAmount' => $data['initialAmount'],
                ':currentValue'  => $data['initialAmount'],
                ':startDate'     => $data['startDate'],
                ':isRecurring'   => $data['isRecurring'] ? 1 : 0,
                ':notes'         => $data['notes'] ?? null,
            ]);

            $investmentId = (int) $this->db->lastInsertId();

            if ((float) $data['initialAmount'] > 0) {
                $this->insertContribution(
                    $investmentId,
                    $userId,
                    (float) $data['initialAmount'],
                    $data['startDate'],
                    'Aportació inicial'
                );
            }

            $this->insertValue($investmentId, (float) $data['initialAmount'], $data['startDate']);

            $this->db->commit();
            return $investmentId;

        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /*
     * Retorna totes les inversions d'un usuari amb totals calculats.
     */
    public function findByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT
                i.*,
                COALESCE((
                    SELECT SUM(ic.amount)
                    FROM investment_contributions ic
                    WHERE ic.investmentId = i.id
                ), 0) AS totalContributed,
                COALESCE((
                    SELECT iv.value
                    FROM investment_values iv
                    WHERE iv.investmentId = i.id
                    ORDER BY iv.date DESC
                    LIMIT 1
                ), 0) AS latestValue
             FROM investments i
             WHERE i.userId = :userId
             ORDER BY i.startDate DESC'
        );
        $stmt->execute([':userId' => $userId]);
        return $stmt->fetchAll();
    }

    /*
     * Retorna una inversió concreta amb els seus totals.
     */
    public function findById(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT
                i.*,
                COALESCE((
                    SELECT SUM(ic.amount)
                    FROM investment_contributions ic
                    WHERE ic.investmentId = i.id
                ), 0) AS totalContributed,
                COALESCE((
                    SELECT iv.value
                    FROM investment_values iv
                    WHERE iv.investmentId = i.id
                    ORDER BY iv.date DESC
                    LIMIT 1
                ), 0) AS latestValue
             FROM investments i
             WHERE i.id = :id AND i.userId = :userId
             LIMIT 1'
        );
        $stmt->execute([':id' => $id, ':userId' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /*
     * Actualitza les dades bàsiques d'una inversió (no els imports).
     */
    public function update(int $id, int $userId, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE investments
             SET name        = :name,
                 type        = :type,
                 isRecurring = :isRecurring,
                 notes       = :notes
             WHERE id = :id AND userId = :userId'
        );

        return $stmt->execute([
            ':name'        => $data['name'],
            ':type'        => $data['type'],
            ':isRecurring' => $data['isRecurring'] ? 1 : 0,
            ':notes'       => $data['notes'] ?? null,
            ':id'          => $id,
            ':userId'      => $userId,
        ]);
    }

    /*
     * Elimina una inversió i totes les dades associades (CASCADE).
     */
    public function delete(int $id, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM investments WHERE id = :id AND userId = :userId'
        );
        return $stmt->execute([':id' => $id, ':userId' => $userId]);
    }

    // ── Aportacions ──────────────────────────────────────────

    /*
     * Afegeix una aportació econòmica a la inversió.
     * Actualitza el valor actual sumant l'aportació al darrer valor.
     */
    public function addContribution(int $investmentId, int $userId, float $amount, string $date, ?string $notes = null): bool
    {
        $invest = $this->findById($investmentId, $userId);
        if ($invest === null) return false;

        $this->db->beginTransaction();

        try {
            $this->insertContribution($investmentId, $userId, $amount, $date, $notes);

            $newValue = (float) $invest['latestValue'] + $amount;
            $this->insertValue($investmentId, $newValue, $date);

            $this->db->prepare(
                'UPDATE investments SET currentValue = :v WHERE id = :id'
            )->execute([':v' => $newValue, ':id' => $investmentId]);

            $this->db->commit();
            return true;

        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /*
     * Elimina una aportació.
     */
    public function deleteContribution(int $contributionId, int $userId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT ic.investmentId
             FROM investment_contributions ic
             INNER JOIN investments i ON i.id = ic.investmentId
             WHERE ic.id = :id AND i.userId = :userId
             LIMIT 1'
        );
        $stmt->execute([':id' => $contributionId, ':userId' => $userId]);

        if (!$stmt->fetch()) return false;

        return $this->db->prepare(
            'DELETE FROM investment_contributions WHERE id = :id'
        )->execute([':id' => $contributionId]);
    }

    /*
     * Retorna totes les aportacions d'una inversió ordenades per data.
     */
    public function getContributions(int $investmentId, int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT ic.*
             FROM investment_contributions ic
             INNER JOIN investments i ON i.id = ic.investmentId
             WHERE ic.investmentId = :investmentId AND i.userId = :userId
             ORDER BY ic.date DESC'
        );
        $stmt->execute([':investmentId' => $investmentId, ':userId' => $userId]);
        return $stmt->fetchAll();
    }

    // ── Valor de mercat ──────────────────────────────────────

    /*
     * Registra el valor actual de mercat (sense afegir diners).
     * L'usuari actualitza manualment quan consulta el seu broker.
     */
    public function updateMarketValue(int $investmentId, int $userId, float $value, string $date): bool
    {
        $invest = $this->findById($investmentId, $userId);
        if ($invest === null) return false;

        $this->db->beginTransaction();

        try {
            $this->insertValue($investmentId, $value, $date);

            $this->db->prepare(
                'UPDATE investments SET currentValue = :v WHERE id = :id'
            )->execute([':v' => $value, ':id' => $investmentId]);

            $this->db->commit();
            return true;

        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /*
     * Retorna l'historial de valors per al gràfic d'evolució.
     */
    public function getValueHistory(int $investmentId, int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT iv.date, iv.value
             FROM investment_values iv
             INNER JOIN investments i ON i.id = iv.investmentId
             WHERE iv.investmentId = :investmentId AND i.userId = :userId
             ORDER BY iv.date ASC'
        );
        $stmt->execute([':investmentId' => $investmentId, ':userId' => $userId]);
        return $stmt->fetchAll();
    }

    // ── Resum global ─────────────────────────────────────────

    /*
     * Resum de totes les inversions: total aportat vs valor actual.
     */
    public function getSummary(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(ic.amount), 0)
             FROM investment_contributions ic
             INNER JOIN investments i ON i.id = ic.investmentId
             WHERE i.userId = :userId'
        );
        $stmt->execute([':userId' => $userId]);
        $contributed = (float) $stmt->fetchColumn();

        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(currentValue), 0) FROM investments WHERE userId = :userId'
        );
        $stmt->execute([':userId' => $userId]);
        $current = (float) $stmt->fetchColumn();

        $profit = $current - $contributed;
        $pct    = $contributed > 0 ? ($profit / $contributed) * 100 : 0;

        return [
            'totalContributed' => $contributed,
            'totalCurrent'     => $current,
            'profit'           => $profit,
            'profitPct'        => $pct,
        ];
    }

    /*
     * Total aportat en un mes/any concret per al balanç del dashboard.
     * Exclou l'aportació inicial per no comptar-la com a despesa del mes.
     */
    public function contributedByMonth(int $userId, int $month, int $year): float
    {
        $stmt = $this->db->prepare(
            'SELECT COALESCE(SUM(ic.amount), 0)
             FROM investment_contributions ic
             INNER JOIN investments i ON i.id = ic.investmentId
             WHERE i.userId = :userId
               AND MONTH(ic.date) = :month
               AND YEAR(ic.date)  = :year
               AND ic.notes != "Aportació inicial"'
        );
        $stmt->execute([':userId' => $userId, ':month' => $month, ':year' => $year]);
        return (float) $stmt->fetchColumn();
    }

    // ── Privats ──────────────────────────────────────────────

    private function insertContribution(int $investmentId, int $userId, float $amount, string $date, ?string $notes): void
    {
        $this->db->prepare(
            'INSERT INTO investment_contributions (investmentId, userId, amount, date, notes)
             VALUES (:investmentId, :userId, :amount, :date, :notes)'
        )->execute([
            ':investmentId' => $investmentId,
            ':userId'       => $userId,
            ':amount'       => $amount,
            ':date'         => $date,
            ':notes'        => $notes,
        ]);
    }

    private function insertValue(int $investmentId, float $value, string $date): void
    {
        $this->db->prepare(
            'INSERT INTO investment_values (investmentId, value, date)
             VALUES (:investmentId, :value, :date)'
        )->execute([
            ':investmentId' => $investmentId,
            ':value'        => $value,
            ':date'         => $date,
        ]);
    }
}