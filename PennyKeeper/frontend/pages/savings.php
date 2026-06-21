<?php
/*
 * savings.php
 * Gestió de plans d'estalvi.
 * Mostra tots els plans amb barra de progrés i historial d'aportacions.
 */

require_once __DIR__ . '/../../backend/core/config.php';
require_once __DIR__ . '/../../backend/core/auth.php';
require_once __DIR__ . '/../../backend/core/db.php';
require_once __DIR__ . '/../../backend/models/SavingPlan.php';

Auth::require();

$userId = Auth::userId();
$model  = new SavingPlan();

$activePlans    = $model->findByUser($userId, 'active');
$completedPlans = $model->findByUser($userId, 'completed');

// Aportacions de cada pla actiu
$contributions = [];
foreach ($activePlans as $plan) {
    $contributions[$plan['id']] = $model->getContributions($plan['id'], $userId);
}

$currentPage = 'savings';
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estalvi · PennyKeeper</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/main.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <link href="../assets/css/savings.css" rel="stylesheet">
</head>
<body>

<div class="app-layout">

    <?php require_once __DIR__ . '/../components/navbar.php'; ?>

    <main class="main-content">

        <div class="page-header">
            <div>
                <h1>Plans d'estalvi</h1>
                <p class="page-subtitle">
                    <?= count($activePlans) ?> actiu<?= count($activePlans) !== 1 ? 's' : '' ?>
                    · <?= count($completedPlans) ?> completat<?= count($completedPlans) !== 1 ? 's' : '' ?>
                </p>
            </div>
            <button class="btn-primary-pk" id="btnNewPlan">
                <i class="bi bi-plus-lg"></i> Nou pla
            </button>
        </div>

        <!-- Plans actius -->
        <?php if (empty($activePlans)): ?>
            <div class="card-pk">
                <div class="empty-state">
                    <i class="bi bi-piggy-bank"></i>
                    <p>Encara no tens cap pla d'estalvi</p>
                    <button class="btn-outline-pk" id="btnNewPlanEmpty" style="margin-top:0.5rem;">
                        Crear el primer pla
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div class="plans-grid">
                <?php foreach ($activePlans as $plan):
                    $pct          = $plan['targetAmount'] > 0
                        ? min(100, ($plan['savedAmount'] / $plan['targetAmount']) * 100)
                        : 0;
                    $remaining    = max(0, $plan['targetAmount'] - $plan['savedAmount']);
                    $planContribs = $contributions[$plan['id']] ?? [];
                ?>
                    <div class="plan-card card-pk">

                        <div class="plan-card-header">
                            <div>
                                <h3 class="plan-name"><?= htmlspecialchars($plan['name']) ?></h3>
                                <?php if ($plan['deadline']): ?>
                                    <span class="plan-deadline">
                                        <i class="bi bi-calendar3"></i>
                                        Fins <?= date('d M Y', strtotime($plan['deadline'])) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="plan-actions">
                                <button class="action-btn btn-contribute"
                                        data-id="<?= $plan['id'] ?>"
                                        data-name="<?= htmlspecialchars($plan['name'], ENT_QUOTES) ?>"
                                        aria-label="Afegir aportació">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                                <button class="action-btn btn-edit-plan"
                                        data-id="<?= $plan['id'] ?>"
                                        data-name="<?= htmlspecialchars($plan['name'], ENT_QUOTES) ?>"
                                        data-target="<?= $plan['targetAmount'] ?>"
                                        data-deadline="<?= $plan['deadline'] ?? '' ?>"
                                        data-notes="<?= htmlspecialchars($plan['notes'] ?? '', ENT_QUOTES) ?>"
                                        aria-label="Editar">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="action-btn btn-delete-plan"
                                        data-id="<?= $plan['id'] ?>"
                                        data-name="<?= htmlspecialchars($plan['name'], ENT_QUOTES) ?>"
                                        aria-label="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Progrés -->
                        <div class="plan-progress-info">
                            <span class="plan-saved">
                                <?= number_format($plan['savedAmount'], 2, ',', '.') ?> €
                            </span>
                            <span class="plan-target">
                                de <?= number_format($plan['targetAmount'], 2, ',', '.') ?> €
                            </span>
                            <span class="plan-pct"><?= number_format($pct, 1) ?>%</span>
                        </div>

                        <div class="plan-progress-track">
                            <div class="plan-progress-fill" style="width: <?= $pct ?>%"></div>
                        </div>

                        <p class="plan-remaining">
                            Falten <strong><?= number_format($remaining, 2, ',', '.') ?> €</strong> per arribar a l'objectiu
                        </p>

                        <?php if ($plan['notes']): ?>
                            <p class="plan-notes"><?= htmlspecialchars($plan['notes']) ?></p>
                        <?php endif; ?>

                        <!-- Últimes aportacions -->
                        <?php if (!empty($planContribs)): ?>
                            <div class="contributions-section">
                                <p class="contributions-title">Últimes aportacions</p>
                                <ul class="contributions-list">
                                    <?php foreach (array_slice($planContribs, 0, 4) as $contrib): ?>
                                        <li class="contribution-item">
                                            <span class="contribution-date">
                                                <?= date('d M', strtotime($contrib['date'])) ?>
                                            </span>
                                            <span class="contribution-notes">
                                                <?= htmlspecialchars($contrib['notes'] ?? '—') ?>
                                            </span>
                                            <span class="contribution-amount">
                                                +<?= number_format($contrib['amount'], 2, ',', '.') ?> €
                                            </span>
                                            <button class="btn-delete-contribution"
                                                    data-id="<?= $contrib['id'] ?>"
                                                    aria-label="Eliminar aportació">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Plans completats -->
        <?php if (!empty($completedPlans)): ?>
            <div>
                <h2 style="font-size:1rem; color:var(--color-text-muted); margin-bottom:1rem;">
                    <i class="bi bi-check-circle" style="color:var(--color-success);"></i>
                    Completats
                </h2>
                <div class="plans-grid">
                    <?php foreach ($completedPlans as $plan): ?>
                        <div class="plan-card plan-card--completed card-pk">
                            <div class="plan-card-header">
                                <div>
                                    <h3 class="plan-name"><?= htmlspecialchars($plan['name']) ?></h3>
                                    <span class="badge-completed">
                                        <i class="bi bi-check-circle"></i> Completat
                                    </span>
                                </div>
                                <button class="action-btn btn-delete-plan"
                                        data-id="<?= $plan['id'] ?>"
                                        data-name="<?= htmlspecialchars($plan['name'], ENT_QUOTES) ?>"
                                        aria-label="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                            <div class="plan-progress-track">
                                <div class="plan-progress-fill plan-progress-fill--completed" style="width:100%"></div>
                            </div>
                            <p style="font-size:0.82rem; color:var(--color-text-muted); margin-top:0.5rem;">
                                <?= number_format($plan['savedAmount'], 2, ',', '.') ?> €
                                de <?= number_format($plan['targetAmount'], 2, ',', '.') ?> €
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </main>
</div>

<!-- Modal -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="modalTitle">Nou pla d'estalvi</h3>
            <button class="modal-close" id="modalClose" aria-label="Tancar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="modal-body" id="modalBody"></div>
    </div>
</div>

<script src="../assets/js/savings.js"></script>

</body>
</html>