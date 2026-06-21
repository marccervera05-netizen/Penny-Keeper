<?php
/*
 * dashboard.php
 * Pàgina principal de l'aplicació.
 * Mostra el resum del mes actual: ingressos, despeses i balanç.
 */

require_once __DIR__ . '/../../backend/core/config.php';
require_once __DIR__ . '/../../backend/core/auth.php';
require_once __DIR__ . '/../../backend/core/db.php';
require_once __DIR__ . '/../../backend/models/Income.php';
require_once __DIR__ . '/../../backend/models/Expense.php';

Auth::require();

$userId = Auth::userId();
$month  = (int) date('n');
$year   = (int) date('Y');

$incomeModel  = new Income();
$expenseModel = new Expense();

$totalIncome   = $incomeModel->sumByMonth($userId, $month, $year);
$totalExpenses = $expenseModel->sumByMonth($userId, $month, $year);
$balance       = $totalIncome - $totalExpenses;

$expensesByCategory = $expenseModel->sumByCategoryAndMonth($userId, $month, $year);

$recentIncomes  = $incomeModel->findByUser($userId, $month, $year);
$recentExpenses = $expenseModel->findByUser($userId, $month, $year);

$transactions = [];
foreach ($recentIncomes as $item) {
    $item['type'] = 'income';
    $transactions[] = $item;
}
foreach ($recentExpenses as $item) {
    $item['type'] = 'expense';
    $transactions[] = $item;
}
usort($transactions, fn($a, $b) => strcmp($b['date'], $a['date']));
$lastTransactions = array_slice($transactions, 0, 8);

$monthNames = [
    1 => 'Gener', 2 => 'Febrer', 3 => 'Març', 4 => 'Abril',
    5 => 'Maig', 6 => 'Juny', 7 => 'Juliol', 8 => 'Agost',
    9 => 'Setembre', 10 => 'Octubre', 11 => 'Novembre', 12 => 'Desembre',
];
$monthLabel = $monthNames[$month] . ' ' . $year;

$currentPage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resum · PennyKeeper</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/main.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>

<div class="app-layout">

    <?php require_once __DIR__ . '/../components/navbar.php'; ?>

    <main class="main-content">

        <!-- Capçalera -->
        <div class="page-header">
            <div>
                <h1>Resum del mes</h1>
                <p class="page-subtitle"><?= $monthLabel ?></p>
            </div>
            <button class="btn-primary-pk" id="btnAddTransaction">
                <i class="bi bi-plus-lg"></i> Afegir
            </button>
        </div>

        <!-- Targetes de resum -->
        <div class="summary-cards">
            <div class="summary-card summary-card--income">
                <p class="summary-label">Ingressos</p>
                <p class="summary-value">
                    <?= number_format($totalIncome, 2, ',', '.') ?> €
                </p>
            </div>
            <div class="summary-card summary-card--expense">
                <p class="summary-label">Despeses</p>
                <p class="summary-value">
                    <?= number_format($totalExpenses, 2, ',', '.') ?> €
                </p>
            </div>
            <div class="summary-card summary-card--balance">
                <p class="summary-label">Balanç net</p>
                <p class="summary-value <?= $balance >= 0 ? 'positive' : 'negative' ?>">
                    <?= ($balance >= 0 ? '+' : '') . number_format($balance, 2, ',', '.') ?> €
                </p>
            </div>
        </div>

        <!-- Gràfic evolució 6 mesos -->
        <div class="card-pk">
            <h2 class="card-title">Evolució dels últims 6 mesos</h2>
            <div class="chart-wrapper">
                <canvas id="evolutionChart"></canvas>
            </div>
        </div>

        <!-- Grid: categories + transaccions -->
        <div class="dashboard-grid">

            <!-- Despeses per categoria -->
            <div class="card-pk">
                <h2 class="card-title">Despeses per categoria</h2>
                <?php if (empty($expensesByCategory)): ?>
                    <div class="empty-state">
                        <i class="bi bi-bar-chart"></i>
                        <p>Sense despeses aquest mes</p>
                    </div>
                <?php else: ?>
                    <div class="category-bars" id="categoryBars"
                         data-categories='<?= json_encode($expensesByCategory) ?>'
                         data-total='<?= $totalExpenses ?>'>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Últimes transaccions -->
            <div class="card-pk">
                <h2 class="card-title">Últimes transaccions</h2>
                <?php if (empty($lastTransactions)): ?>
                    <div class="empty-state">
                        <i class="bi bi-receipt"></i>
                        <p>Sense moviments aquest mes</p>
                    </div>
                <?php else: ?>
                    <ul class="transaction-list">
                        <?php foreach ($lastTransactions as $tx): ?>
                            <li class="transaction-item">
                                <div class="tx-icon tx-icon--<?= $tx['type'] ?>">
                                    <i class="bi <?= htmlspecialchars($tx['categoryIcon'] ?? ($tx['type'] === 'income' ? 'bi-arrow-up' : 'bi-arrow-down')) ?>"></i>
                                </div>
                                <div class="tx-info">
                                    <span class="tx-desc"><?= htmlspecialchars($tx['description']) ?></span>
                                    <span class="tx-meta">
                                        <?= htmlspecialchars($tx['categoryName'] ?? '—') ?>
                                        · <?= date('d M', strtotime($tx['date'])) ?>
                                    </span>
                                </div>
                                <span class="tx-amount <?= $tx['type'] === 'income' ? 'positive' : 'negative' ?>">
                                    <?= $tx['type'] === 'income' ? '+' : '−' ?>
                                    <?= number_format($tx['amount'], 2, ',', '.') ?> €
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

        </div>

        <!-- Accions ràpides -->
        <div class="card-pk">
            <h2 class="card-title">Accions ràpides</h2>
            <div class="quick-actions">
                <button class="quick-action-btn" id="btnIncome">
                    <i class="bi bi-arrow-up-circle"></i>
                    <span>Nou ingrés</span>
                </button>
                <button class="quick-action-btn" id="btnExpense">
                    <i class="bi bi-arrow-down-circle"></i>
                    <span>Nova despesa</span>
                </button>
                <button class="quick-action-btn" id="btnSaving">
                    <i class="bi bi-piggy-bank"></i>
                    <span>Pla d'estalvi</span>
                </button>
            </div>
        </div>

    </main>
</div>

<!-- Modal: afegir transacció -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal-box" id="modalBox">
        <div class="modal-header">
            <h3 id="modalTitle">Nova transacció</h3>
            <button class="modal-close" id="modalClose" aria-label="Tancar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="modal-body" id="modalBody"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    const CATEGORIES     = <?= json_encode($expensesByCategory) ?>;
    const TOTAL_EXPENSES = <?= $totalExpenses ?>;
</script>
<script src="../assets/js/dashboard.js"></script>

</body>
</html>