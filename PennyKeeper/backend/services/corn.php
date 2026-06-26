<?php
/*
 * cron.php
 * Script executat automàticament per generar transaccions recurrents.
 * Hostinger l'executa cada dia a través del gestor de tasques cron.
 *
 * Lògica:
 * 1. Busca tots els ingressos i despeses marcats com a isRecurring = 1
 * 2. Comprova si ja existeix una transacció del mes actual per cada recurrent
 * 3. Si no existeix, la crea automàticament amb la data del primer dia del mes
 */

require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/db.php';

$db    = Database::getConnection();
$month = (int) date('n');
$year  = (int) date('Y');
$date  = date('Y-m-') . '01'; // Primer dia del mes actual

$generated = 0;
$errors    = 0;

// ── Ingressos recurrents ─────────────────────────────────────

$stmt = $db->prepare(
    'SELECT * FROM incomes WHERE isRecurring = 1'
);
$stmt->execute();
$recurringIncomes = $stmt->fetchAll();

foreach ($recurringIncomes as $income) {
    // Comprova si ja existeix aquest ingrés al mes actual
    $check = $db->prepare(
        'SELECT COUNT(*) FROM incomes
         WHERE userId      = :userId
           AND description = :description
           AND amount      = :amount
           AND isRecurring = 1
           AND MONTH(date) = :month
           AND YEAR(date)  = :year
           AND id          != :id'
    );
    $check->execute([
        ':userId'      => $income['userId'],
        ':description' => $income['description'],
        ':amount'      => $income['amount'],
        ':month'       => $month,
        ':year'        => $year,
        ':id'          => $income['id'],
    ]);

    if ((int) $check->fetchColumn() > 0) {
        continue; // Ja generat aquest mes
    }

    // Comprova que l'ingrés original no sigui ja del mes actual
    if (
        (int) date('n', strtotime($income['date'])) === $month &&
        (int) date('Y', strtotime($income['date'])) === $year
    ) {
        continue;
    }

    try {
        $insert = $db->prepare(
            'INSERT INTO incomes (userId, categoryId, description, amount, isRecurring, date, notes)
             VALUES (:userId, :categoryId, :description, :amount, 1, :date, :notes)'
        );
        $insert->execute([
            ':userId'      => $income['userId'],
            ':categoryId'  => $income['categoryId'],
            ':description' => $income['description'],
            ':amount'      => $income['amount'],
            ':date'        => $date,
            ':notes'       => 'Generat automàticament (recurrent)',
        ]);
        $generated++;
    } catch (PDOException $e) {
        $errors++;
    }
}

// ── Despeses recurrents ──────────────────────────────────────

$stmt = $db->prepare(
    'SELECT * FROM expenses WHERE isRecurring = 1'
);
$stmt->execute();
$recurringExpenses = $stmt->fetchAll();

foreach ($recurringExpenses as $expense) {
    $check = $db->prepare(
        'SELECT COUNT(*) FROM expenses
         WHERE userId      = :userId
           AND description = :description
           AND amount      = :amount
           AND isRecurring = 1
           AND MONTH(date) = :month
           AND YEAR(date)  = :year
           AND id          != :id'
    );
    $check->execute([
        ':userId'      => $expense['userId'],
        ':description' => $expense['description'],
        ':amount'      => $expense['amount'],
        ':month'       => $month,
        ':year'        => $year,
        ':id'          => $expense['id'],
    ]);

    if ((int) $check->fetchColumn() > 0) {
        continue;
    }

    if (
        (int) date('n', strtotime($expense['date'])) === $month &&
        (int) date('Y', strtotime($expense['date'])) === $year
    ) {
        continue;
    }

    try {
        $insert = $db->prepare(
            'INSERT INTO expenses (userId, categoryId, description, amount, isRecurring, date, notes)
             VALUES (:userId, :categoryId, :description, :amount, 1, :date, :notes)'
        );
        $insert->execute([
            ':userId'      => $expense['userId'],
            ':categoryId'  => $expense['categoryId'],
            ':description' => $expense['description'],
            ':amount'      => $expense['amount'],
            ':date'        => $date,
            ':notes'       => 'Generat automàticament (recurrent)',
        ]);
        $generated++;
    } catch (PDOException $e) {
        $errors++;
    }
}

// ── Log del resultat ─────────────────────────────────────────

$logMessage = date('Y-m-d H:i:s') . " | Recurrents: {$generated} generats, {$errors} errors\n";
file_put_contents(__DIR__ . '/cron.log', $logMessage, FILE_APPEND);

echo $logMessage;