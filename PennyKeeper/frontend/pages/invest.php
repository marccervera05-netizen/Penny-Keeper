<?php
/*
 * invest.php
 * Gestió d'inversions amb aportacions periòdiques i historial de valor.
 */

require_once __DIR__ . '/../../backend/core/config.php';
require_once __DIR__ . '/../../backend/core/auth.php';
require_once __DIR__ . '/../../backend/core/db.php';
require_once __DIR__ . '/../../backend/models/Investment.php';

Auth::require();

$userId      = Auth::userId();
$model       = new Investment();
$investments = $model->findByUser($userId);
$summary     = $model->getSummary($userId);

$typeLabels = [
    'stocks'      => 'Borsa',
    'crypto'      => 'Criptomonedes',
    'funds'       => 'Fons d\'inversió',
    'real_estate' => 'Immobles',
    'other'       => 'Altres',
];

$typeIcons = [
    'stocks'      => 'bi-graph-up-arrow',
    'crypto'      => 'bi-currency-bitcoin',
    'funds'       => 'bi-pie-chart',
    'real_estate' => 'bi-building',
    'other'       => 'bi-briefcase',
];

// Aportacions i historial per a cada inversió
$contributions = [];
$valueHistory  = [];
foreach ($investments as $inv) {
    $contributions[$inv['id']] = $model->getContributions($inv['id'], $userId);
    $valueHistory[$inv['id']]  = $model->getValueHistory($inv['id'], $userId);
}

$currentPage = 'invest';
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inversions · PennyKeeper</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/main.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <link href="../assets/css/invest.css" rel="stylesheet">
</head>
<body>

<div class="app-layout">

    <?php require_once __DIR__ . '/../components/navbar.php'; ?>

    <main class="main-content">

        <div class="page-header">
            <div>
                <h1>Inversions</h1>
                <p class="page-subtitle"><?= count($investments) ?> posicion<?= count($investments) !== 1 ? 's' : '' ?> obertes</p>
            </div>
            <button class="btn-primary-pk" id="btnNewInvest">
                <i class="bi bi-plus-lg"></i> Nova inversió
            </button>
        </div>

        <!-- Resum global -->
        <?php if (!empty($investments)): ?>
        <div class="invest-summary">
            <div class="invest-summary-card">
                <p class="summary-label">Total aportat</p>
                <p class="summary-value"><?= number_format($summary['totalContributed'], 2, ',', '.') ?> €</p>
            </div>
            <div class="invest-summary-card">
                <p class="summary-label">Valor actual</p>
                <p class="summary-value"><?= number_format($summary['totalCurrent'], 2, ',', '.') ?> €</p>
            </div>
            <div class="invest-summary-card">
                <p class="summary-label">Rendiment net</p>
                <p class="summary-value <?= $summary['profit'] >= 0 ? 'positive' : 'negative' ?>">
                    <?= ($summary['profit'] >= 0 ? '+' : '') . number_format($summary['profit'], 2, ',', '.') ?> €
                </p>
            </div>
            <div class="invest-summary-card">
                <p class="summary-label">Variació total</p>
                <p class="summary-value <?= $summary['profitPct'] >= 0 ? 'positive' : 'negative' ?>">
                    <?= ($summary['profitPct'] >= 0 ? '+' : '') . number_format($summary['profitPct'], 2, ',', '.') ?>%
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Llistat d'inversions -->
        <?php if (empty($investments)): ?>
            <div class="card-pk">
                <div class="empty-state">
                    <i class="bi bi-graph-up-arrow"></i>
                    <p>Encara no tens cap inversió registrada</p>
                    <button class="btn-outline-pk" id="btnNewInvestEmpty" style="margin-top:0.5rem;">
                        Afegir la primera inversió
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div class="invest-cards-grid">
                <?php foreach ($investments as $inv):
                    $profit     = (float) $inv['latestValue'] - (float) $inv['totalContributed'];
                    $profitPct  = (float) $inv['totalContributed'] > 0
                        ? ($profit / (float) $inv['totalContributed']) * 100
                        : 0;
                    $isPositive  = $profit >= 0;
                    $invContribs = $contributions[$inv['id']] ?? [];
                    $invHistory  = $valueHistory[$inv['id']] ?? [];
                ?>
                <div class="invest-card card-pk">

                    <div class="invest-card-header">
                        <div class="invest-name-cell">
                            <div class="invest-icon">
                                <i class="bi <?= $typeIcons[$inv['type']] ?? 'bi-briefcase' ?>"></i>
                            </div>
                            <div>
                                <h3 class="invest-name"><?= htmlspecialchars($inv['name']) ?></h3>
                                <span class="invest-type-badge invest-type-badge--<?= $inv['type'] ?>">
                                    <?= $typeLabels[$inv['type']] ?? 'Altres' ?>
                                </span>
                                <?php if ($inv['isRecurring']): ?>
                                    <span class="badge-recurring" style="margin-left:0.3rem;">
                                        <i class="bi bi-arrow-repeat"></i> Mensual
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="invest-card-actions">
                            <button class="action-btn btn-contribute-invest"
                                    data-id="<?= $inv['id'] ?>"
                                    data-name="<?= htmlspecialchars($inv['name'], ENT_QUOTES) ?>"
                                    title="Afegir aportació">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                            <button class="action-btn btn-update-value"
                                    data-id="<?= $inv['id'] ?>"
                                    data-name="<?= htmlspecialchars($inv['name'], ENT_QUOTES) ?>"
                                    data-current="<?= $inv['latestValue'] ?>"
                                    title="Actualitzar valor de mercat">
                                <i class="bi bi-arrow-repeat"></i>
                            </button>
                            <button class="action-btn btn-edit-invest"
                                    data-id="<?= $inv['id'] ?>"
                                    data-name="<?= htmlspecialchars($inv['name'], ENT_QUOTES) ?>"
                                    data-type="<?= $inv['type'] ?>"
                                    data-recurring="<?= $inv['isRecurring'] ?>"
                                    data-notes="<?= htmlspecialchars($inv['notes'] ?? '', ENT_QUOTES) ?>"
                                    title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="action-btn btn-delete-invest"
                                    data-id="<?= $inv['id'] ?>"
                                    data-name="<?= htmlspecialchars($inv['name'], ENT_QUOTES) ?>"
                                    title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Xifres clau -->
                    <div class="invest-figures">
                        <div class="invest-figure">
                            <span class="invest-figure-label">Total aportat</span>
                            <span class="invest-figure-value">
                                <?= number_format($inv['totalContributed'], 2, ',', '.') ?> €
                            </span>
                        </div>
                        <div class="invest-figure">
                            <span class="invest-figure-label">Valor actual</span>
                            <span class="invest-figure-value">
                                <?= number_format($inv['latestValue'], 2, ',', '.') ?> €
                            </span>
                        </div>
                        <div class="invest-figure">
                            <span class="invest-figure-label">Rendiment</span>
                            <span class="invest-figure-value <?= $isPositive ? 'positive' : 'negative' ?>">
                                <?= ($isPositive ? '+' : '') . number_format($profit, 2, ',', '.') ?> €
                            </span>
                        </div>
                        <div class="invest-figure">
                            <span class="invest-figure-label">Variació</span>
                            <span class="invest-pct <?= $isPositive ? 'invest-pct--up' : 'invest-pct--down' ?>">
                                <i class="bi <?= $isPositive ? 'bi-arrow-up-short' : 'bi-arrow-down-short' ?>"></i>
                                <?= number_format(abs($profitPct), 2) ?>%
                            </span>
                        </div>
                    </div>

                    <?php if ($inv['notes']): ?>
                        <p class="invest-notes"><?= htmlspecialchars($inv['notes']) ?></p>
                    <?php endif; ?>

                    <!-- Últimes aportacions -->
                    <?php if (!empty($invContribs)): ?>
                    <div class="contributions-section">
                        <p class="contributions-title">Aportacions</p>
                        <ul class="contributions-list">
                            <?php foreach (array_slice($invContribs, 0, 4) as $contrib): ?>
                                <li class="contribution-item">
                                    <span class="contribution-date">
                                        <?= date('d M Y', strtotime($contrib['date'])) ?>
                                    </span>
                                    <span class="contribution-notes">
                                        <?= htmlspecialchars($contrib['notes'] ?? '—') ?>
                                    </span>
                                    <span class="contribution-amount">
                                        +<?= number_format($contrib['amount'], 2, ',', '.') ?> €
                                    </span>
                                    <?php if ($contrib['notes'] !== 'Aportació inicial'): ?>
                                        <button class="btn-delete-contribution"
                                                data-id="<?= $contrib['id'] ?>"
                                                aria-label="Eliminar">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>
</div>

<!-- Modal -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="modalTitle">Nova inversió</h3>
            <button class="modal-close" id="modalClose" aria-label="Tancar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="modal-body" id="modalBody"></div>
    </div>
</div>

<script src="../assets/js/invest.js"></script>
</body>
</html>