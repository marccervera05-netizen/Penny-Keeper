<?php
/*
 * navbar.php
 * Navbar lateral reutilitzable.
 * S'inclou amb require a totes les pàgines protegides.
 * Espera que $currentPage estigui definida abans d'incloure'l.
 */

$username = Auth::username();

$navItems = [
    'dashboard' => ['icon' => 'bi-house',            'label' => 'Resum'],
    'incomes'   => ['icon' => 'bi-arrow-up-circle',  'label' => 'Ingressos'],
    'expenses'  => ['icon' => 'bi-arrow-down-circle','label' => 'Despeses'],
    'savings'   => ['icon' => 'bi-piggy-bank',       'label' => 'Estalvi'],
    'invest'    => ['icon' => 'bi-graph-up-arrow',   'label' => 'Inversions'],
];
?>

<aside class="sidebar" id="sidebar">

    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class="bi bi-coin"></i>
            <span>PennyKeeper</span>
        </div>
        <div class="sidebar-user">
            <i class="bi bi-person-circle"></i>
            <?= htmlspecialchars($username) ?>
        </div>
    </div>

    <nav class="sidebar-nav">
        <p class="nav-section-label">Principal</p>

        <?php foreach ($navItems as $page => $item): ?>
            <a
                href="<?= $page ?>.php"
                class="nav-item <?= $currentPage === $page ? 'active' : '' ?>"
            >
                <i class="bi <?= $item['icon'] ?>"></i>
                <span><?= $item['label'] ?></span>
            </a>
        <?php endforeach; ?>

        <p class="nav-section-label" style="margin-top: 1rem;">Compte</p>

        <a href="settings.php" class="nav-item <?= $currentPage === 'settings' ? 'active' : '' ?>">
            <i class="bi bi-gear"></i>
            <span>Configuració</span>
        </a>

        <a
            href="../../backend/api/controllers/AuthController.php?action=logout"
            class="nav-item nav-item--logout"
        >
            <i class="bi bi-box-arrow-right"></i>
            <span>Tancar sessió</span>
        </a>
    </nav>

</aside>

<!-- Navbar inferior mòbil -->
<nav class="mobile-nav">
    <?php foreach ($navItems as $page => $item): ?>
        <a href="<?= $page ?>.php" class="mobile-nav-item <?= $currentPage === $page ? 'active' : '' ?>">
            <i class="bi <?= $item['icon'] ?>"></i>
            <span><?= $item['label'] ?></span>
        </a>
    <?php endforeach; ?>
</nav>