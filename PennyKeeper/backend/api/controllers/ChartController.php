<?php
/*
 * ChartController.php
 * Retorna les dades dels últims 6 mesos per als gràfics del dashboard.
 */

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../models/Income.php';
require_once __DIR__ . '/../../models/Expense.php';

header('Content-Type: application/json');

Auth::startSession();

if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticat.']);
    exit;
}

$userId       = Auth::userId();
$incomeModel  = new Income();
$expenseModel = new Expense();

$monthNames = [
    1  => 'Gen', 2  => 'Feb', 3  => 'Mar',
    4  => 'Abr', 5  => 'Mai', 6  => 'Jun',
    7  => 'Jul', 8  => 'Ago', 9  => 'Set',
    10 => 'Oct', 11 => 'Nov', 12 => 'Des',
];

$labels   = [];
$incomes  = [];
$expenses = [];

// Recorre els últims 6 mesos des del mes actual cap enrere
for ($i = 5; $i >= 0; $i--) {
    $timestamp = strtotime("-{$i} months");
    $month     = (int) date('n', $timestamp);
    $year      = (int) date('Y', $timestamp);

    $labels[]   = $monthNames[$month] . ' ' . date('y', $timestamp);
    $incomes[]  = $incomeModel->sumByMonth($userId, $month, $year);
    $expenses[] = $expenseModel->sumByMonth($userId, $month, $year);
}

// Comprova si hi ha alguna dada real
$hasData = array_sum($incomes) > 0 || array_sum($expenses) > 0;

echo json_encode([
    'success'  => true,
    'hasData'  => $hasData,
    'labels'   => $labels,
    'incomes'  => $incomes,
    'expenses' => $expenses,
]);

echo json_encode([
    'success'  => true,
    'labels'   => $labels,
    'incomes'  => $incomes,
    'expenses' => $expenses,
]);