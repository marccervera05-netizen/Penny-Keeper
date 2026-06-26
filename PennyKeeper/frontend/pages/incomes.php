<?php
/*
 * incomes.php
 * Gestió d'ingressos de l'usuari.
 * Mostra el llistat filtrat per mes/any amb opcions d'editar i eliminar.
 */

require_once __DIR__ . '/../../backend/core/config.php';
require_once __DIR__ . '/../../backend/core/auth.php';
require_once __DIR__ . '/../../backend/core/db.php';
require_once __DIR__ . '/../../backend/models/Income.php';
require_once __DIR__ . '/../../backend/models/Category.php';

Auth::require();

$userId = Auth::userId();

$month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('n');
$year  = isset($_GET['year'])  ? (int) $_GET['year']  : (int) date('Y');

$month = max(1, min(12, $month));
$year  = max(2000, min(2100, $year));

$incomeModel   = new Income();
$categoryModel = new Category();

$incomes    = $incomeModel->findByUser($userId, $month, $year);
$categories = $categoryModel->findByUser($userId, 'income');
$total      = $incomeModel->sumByMonth($userId, $month, $year);

$monthNames = [
    1  => 'Gener',   2  => 'Febrer',  3  => 'Març',
    4  => 'Abril',   5  => 'Maig',    6  => 'Juny',
    7  => 'Juliol',  8  => 'Agost',   9  => 'Setembre',
    10 => 'Octubre', 11 => 'Novembre',12 => 'Desembre',
];

$currentPage = 'incomes';
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingressos · PennyKeeper</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/main.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <link href="../assets/css/transactions.css" rel="stylesheet">
</head>
<body>

<div class="app-layout">

    <?php require_once __DIR__ . '/../components/navbar.php'; ?>

    <main class="main-content">

        <div class="page-header">
            <div>
                <h1>Ingressos</h1>
                <p class="page-subtitle">
                    <?= $monthNames[$month] ?> <?= $year ?>
                    · Total: <strong><?= number_format($total, 2, ',', '.') ?> €</strong>
                </p>
            </div>
            <button class="btn-primary-pk" id="btnAdd">
                <i class="bi bi-plus-lg"></i> Nou ingrés
            </button>
        </div>

        <!-- Filtre de mes -->
        <div class="month-filter">
            <a href="?month=<?= $month === 1 ? 12 : $month - 1 ?>&year=<?= $month === 1 ? $year - 1 : $year ?>"
               class="month-nav-btn" aria-label="Mes anterior">
                <i class="bi bi-chevron-left"></i>
            </a>
            <span class="month-label"><?= $monthNames[$month] ?> <?= $year ?></span>
            <a href="?month=<?= $month === 12 ? 1 : $month + 1 ?>&year=<?= $month === 12 ? $year + 1 : $year ?>"
               class="month-nav-btn" aria-label="Mes següent">
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>

        <!-- Llistat -->
        <div class="card-pk">
            <?php if (empty($incomes)): ?>
                <div class="empty-state">
                    <i class="bi bi-arrow-up-circle"></i>
                    <p>Sense ingressos aquest mes</p>
                    <button class="btn-outline-pk" id="btnAddEmpty" style="margin-top:0.5rem;">
                        Afegir el primer ingrés
                    </button>
                </div>
            <?php else: ?>
                <table class="tx-table">
                    <thead>
                        <tr>
                            <th>Descripció</th>
                            <th>Categoria</th>
                            <th>Data</th>
                            <th>Recurrent</th>
                            <th class="text-right">Import</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($incomes as $income): ?>
                            <tr data-id="<?= $income['id'] ?>">
                                <td class="tx-desc-cell">
                                    <?= htmlspecialchars($income['description']) ?>
                                    <?php if ($income['notes']): ?>
                                        <span class="tx-note"><?= htmlspecialchars($income['notes']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="category-badge">
                                        <?php if ($income['categoryIcon']): ?>
                                            <i class="bi <?= htmlspecialchars($income['categoryIcon']) ?>"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($income['categoryName'] ?? '—') ?>
                                    </span>
                                </td>
                                <td class="text-muted"><?= date('d M Y', strtotime($income['date'])) ?></td>
                                <td>
                                    <?php if ($income['isRecurring']): ?>
                                        <span class="badge-recurring">
                                            <i class="bi bi-arrow-repeat"></i> Sí
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right amount-positive">
                                    +<?= number_format($income['amount'], 2, ',', '.') ?> €
                                </td>
                                <td class="tx-actions">
                                    <button class="action-btn btn-edit"
                                            data-type="income"
                                            data-id="<?= $income['id'] ?>"
                                            data-description="<?= htmlspecialchars($income['description'], ENT_QUOTES) ?>"
                                            data-amount="<?= $income['amount'] ?>"
                                            data-date="<?= $income['date'] ?>"
                                            data-notes="<?= htmlspecialchars($income['notes'] ?? '', ENT_QUOTES) ?>"
                                            data-recurring="<?= $income['isRecurring'] ?>"
                                            data-category="<?= $income['categoryId'] ?>"
                                            aria-label="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="action-btn btn-delete"
                                            data-type="income"
                                            data-id="<?= $income['id'] ?>"
                                            data-description="<?= htmlspecialchars($income['description'], ENT_QUOTES) ?>"
                                            aria-label="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </main>
</div>

<!-- Modal -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="modalTitle">Nou ingrés</h3>
            <button class="modal-close" id="modalClose" aria-label="Tancar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="modal-body" id="modalBody"></div>
    </div>
</div>
<script>
    const API_BASE = '<?= rtrim(str_replace('/frontend', '', APP_URL), '/') ?>';
</script>
<script>
    const PAGE_TYPE   = 'income';
    const CATEGORIES  = <?= json_encode($categories) ?>;
    const CURRENT_URL = '?month=<?= $month ?>&year=<?= $year ?>';
</script>
<script src="../assets/js/transactions.js"></script>

</body>
</html>