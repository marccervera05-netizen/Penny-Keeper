<?php
/*
 * landing.php
 * Pàgina d'inici pública de PennyKeeper.
 * Si l'usuari ja té sessió, redirigeix al dashboard.
 */

require_once __DIR__ . '/../../backend/core/config.php';
require_once __DIR__ . '/../../backend/core/auth.php';

Auth::startSession();

if (Auth::isLoggedIn()) {
    header('Location: ' . APP_URL . '/pages/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PennyKeeper · Controla les teves finances</title>
    <meta name="description" content="PennyKeeper és el gestor de finances personal que t'ajuda a controlar ingressos, despeses, estalvi i inversions en un sol lloc.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:ital,wght@0,600;0,700;1,600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/main.css" rel="stylesheet">
    <link href="../assets/css/landing.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<header class="landing-nav">
    <div class="landing-nav-inner">
        <div class="landing-logo">
            <i class="bi bi-coin"></i>
            <span>PennyKeeper</span>
        </div>
        <div class="landing-nav-actions">
            <a href="login.php" class="btn-outline-pk">Inicia sessió</a>
            <a href="register.php" class="btn-primary-pk">Comença gratis</a>
        </div>
    </div>
</header>

<!-- Hero -->
<section class="hero">
    <div class="hero-inner">
        <div class="hero-badge">
            <i class="bi bi-stars"></i> Gestió financera personal
        </div>
        <h1 class="hero-title">
            Els teus diners,<br>
            <em>sota control.</em>
        </h1>
        <p class="hero-subtitle">
            PennyKeeper t'ajuda a entendre on van els teus diners, planificar el teu estalvi i fer créixer les teves inversions — tot en un sol lloc, sense complicacions.
        </p>
        <div class="hero-actions">
            <a href="register.php" class="btn-hero-primary">
                Crea el teu compte gratis
                <i class="bi bi-arrow-right"></i>
            </a>
            <a href="login.php" class="btn-hero-secondary">
                Ja tinc compte
            </a>
        </div>
        <p class="hero-note">
            <i class="bi bi-shield-check"></i>
            Sense targetes de crèdit. Sense compromisos.
        </p>
    </div>

    <!-- Preview del dashboard -->
    <div class="hero-preview">
        <div class="preview-card">
            <div class="preview-header">
                <span class="preview-title">Resum del mes · Juny 2026</span>
            </div>
            <div class="preview-stats">
                <div class="preview-stat preview-stat--income">
                    <p class="preview-stat-label">Ingressos</p>
                    <p class="preview-stat-value">2.400,00 €</p>
                </div>
                <div class="preview-stat preview-stat--expense">
                    <p class="preview-stat-label">Despeses</p>
                    <p class="preview-stat-value">1.150,00 €</p>
                </div>
                <div class="preview-stat preview-stat--balance">
                    <p class="preview-stat-label">Balanç net</p>
                    <p class="preview-stat-value">+1.250,00 €</p>
                </div>
            </div>
            <div class="preview-list">
                <div class="preview-tx">
                    <div class="preview-tx-icon preview-tx-icon--income">
                        <i class="bi bi-briefcase"></i>
                    </div>
                    <div class="preview-tx-info">
                        <span>Salari</span>
                        <span class="preview-tx-date">01 Jun</span>
                    </div>
                    <span class="preview-tx-amount preview-tx-amount--income">+2.000,00 €</span>
                </div>
                <div class="preview-tx">
                    <div class="preview-tx-icon preview-tx-icon--expense">
                        <i class="bi bi-house"></i>
                    </div>
                    <div class="preview-tx-info">
                        <span>Lloguer</span>
                        <span class="preview-tx-date">02 Jun</span>
                    </div>
                    <span class="preview-tx-amount preview-tx-amount--expense">−650,00 €</span>
                </div>
                <div class="preview-tx">
                    <div class="preview-tx-icon preview-tx-icon--expense">
                        <i class="bi bi-cart"></i>
                    </div>
                    <div class="preview-tx-info">
                        <span>Supermercat</span>
                        <span class="preview-tx-date">05 Jun</span>
                    </div>
                    <span class="preview-tx-amount preview-tx-amount--expense">−87,50 €</span>
                </div>
                <div class="preview-tx">
                    <div class="preview-tx-icon preview-tx-icon--income">
                        <i class="bi bi-laptop"></i>
                    </div>
                    <div class="preview-tx-info">
                        <span>Freelance</span>
                        <span class="preview-tx-date">10 Jun</span>
                    </div>
                    <span class="preview-tx-amount preview-tx-amount--income">+400,00 €</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features -->
<section class="features">
    <div class="features-inner">
        <div class="features-header">
            <h2>Tot el que necessites per controlar les teves finances</h2>
            <p>Sense fulls de càlcul, sense complicacions. PennyKeeper ho fa simple.</p>
        </div>
        <div class="features-grid">

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
                <h3>Ingressos i despeses</h3>
                <p>Registra qualsevol moviment en segons. Categoritza, afegeix notes i filtra per mes per tenir una visió clara.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
                <h3>Recurrents automàtics</h3>
                <p>Apunta el lloguer, les subscripcions o el salari com a recurrent i PennyKeeper ho comptabilitza sol cada mes. Sense feina extra.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-piggy-bank"></i>
                </div>
                <h3>Plans d'estalvi</h3>
                <p>Crea objectius d'estalvi, afegeix les aportacions i segueix el progrés amb barres visuals. Vacances, cotxe, fons d'emergència.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <h3>Inversions</h3>
                <p>Registra les teves inversions. Veu el rendiment real comparant el que has aportat amb el valor actual.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-bar-chart-line"></i>
                </div>
                <h3>Dashboard intel·ligent</h3>
                <p>Cada vegada que entres, veus el balanç del mes, les últimes transaccions i les despeses per categoria amb un cop d'ull.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="bi bi-phone"></i>
                </div>
                <h3>Responsive total</h3>
                <p>Funciona perfectament al mòbil, tauleta i ordinador. PennyKeeper s'adapta per ser còmoda en qualsevol pantalla.</p>
            </div>

        </div>
    </div>
</section>

<!-- Com funciona -->
<section class="how-it-works">
    <div class="how-inner">
        <div class="features-header">
            <h2>Comença en menys d'un minut</h2>
            <p>Tres passos i ja tens les teves finances sota control.</p>
        </div>
        <div class="steps">
            <div class="step">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h3>Crea el teu compte</h3>
                    <p>Registra't amb email i contrasenya. Gratis, sense targetes ni subscripcions.</p>
                </div>
            </div>
            <div class="step-divider">
                <i class="bi bi-arrow-right"></i>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h3>Afegeix els teus moviments</h3>
                    <p>Registra ingressos i despeses. Marca els recurrents i s'afegiran sols cada mes.</p>
                </div>
            </div>
            <div class="step-divider">
                <i class="bi bi-arrow-right"></i>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h3>Pren decisions millors</h3>
                    <p>Veu on vas els teus diners, planifica l'estalvi i fes créixer les teves inversions.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA final -->
<section class="cta-section">
    <div class="cta-inner">
        <h2>Comença avui mateix</h2>
        <p>Uneix-te als usuaris que ja controlen les seves finances amb PennyKeeper.</p>
        <a href="register.php" class="btn-hero-primary">
            Crea el teu compte gratis
            <i class="bi bi-arrow-right"></i>
        </a>
    </div>
</section>

<!-- Footer -->
<footer class="landing-footer">
    <div class="landing-footer-inner">
        <div class="landing-logo">
            <i class="bi bi-coin"></i>
            <span>PennyKeeper</span>
        </div>
        <p>Autor · Marc Cervera</p>
    </div>
</footer>

</body>
</html>