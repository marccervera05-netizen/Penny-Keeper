<?php
/*
 * settings.php
 * Configuració del compte de l'usuari.
 * Permet canviar nom, email, moneda i contrasenya.
 */

require_once __DIR__ . '/../../backend/core/config.php';
require_once __DIR__ . '/../../backend/core/auth.php';
require_once __DIR__ . '/../../backend/core/db.php';
require_once __DIR__ . '/../../backend/models/User.php';

Auth::require();

$userId    = Auth::userId();
$userModel = new User();
$user      = $userModel->findById($userId);

$currentPage = 'settings';
?>
<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuració · PennyKeeper</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/main.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <link href="../assets/css/settings.css" rel="stylesheet">
</head>
<body>

<div class="app-layout">

    <?php require_once __DIR__ . '/../components/navbar.php'; ?>

    <main class="main-content">

        <div class="page-header">
            <div>
                <h1>Configuració</h1>
                <p class="page-subtitle">Gestiona el teu compte</p>
            </div>
        </div>

        <div class="settings-layout">

            <!-- Perfil -->
            <section class="card-pk settings-section">
                <div class="settings-section-header">
                    <div class="settings-section-icon">
                        <i class="bi bi-person"></i>
                    </div>
                    <div>
                        <h2>Informació del perfil</h2>
                        <p>Actualitza el teu nom, email i moneda preferida</p>
                    </div>
                </div>

                <div id="profileAlert" class="alert-pk" role="alert"></div>

                <div class="settings-form">
                    <div class="form-group">
                        <label class="form-label" for="username">Nom d'usuari</label>
                        <input class="form-input" type="text" id="username"
                               value="<?= htmlspecialchars($user['username']) ?>"
                               maxlength="50" autocomplete="username">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-input" type="email" id="email"
                               value="<?= htmlspecialchars($user['email']) ?>"
                               autocomplete="email">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="currency">Moneda</label>
                        <select class="form-select" id="currency">
                            <option value="EUR" <?= $user['currency'] === 'EUR' ? 'selected' : '' ?>>€ Euro (EUR)</option>
                            <option value="USD" <?= $user['currency'] === 'USD' ? 'selected' : '' ?>>$ Dòlar (USD)</option>
                            <option value="GBP" <?= $user['currency'] === 'GBP' ? 'selected' : '' ?>>£ Lliura (GBP)</option>
                        </select>
                    </div>

                    <div class="settings-form-footer">
                        <p class="settings-meta">
                            Compte creat el <?= date('d M Y', strtotime($user['createdAt'])) ?>
                        </p>
                        <button class="btn-primary-pk" id="btnSaveProfile">
                            Guardar canvis
                        </button>
                    </div>
                </div>
            </section>

            <!-- Contrasenya -->
            <section class="card-pk settings-section">
                <div class="settings-section-header">
                    <div class="settings-section-icon">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <div>
                        <h2>Contrasenya</h2>
                        <p>Canvia la teva contrasenya d'accés</p>
                    </div>
                </div>

                <div id="passwordAlert" class="alert-pk" role="alert"></div>

                <div class="settings-form">
                    <div class="form-group">
                        <label class="form-label" for="currentPassword">Contrasenya actual</label>
                        <input class="form-input" type="password" id="currentPassword"
                               placeholder="••••••••" autocomplete="current-password">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="newPassword">Nova contrasenya</label>
                        <input class="form-input" type="password" id="newPassword"
                               placeholder="Mínim 8 caràcters" autocomplete="new-password">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirmPassword">Confirma la nova contrasenya</label>
                        <input class="form-input" type="password" id="confirmPassword"
                               placeholder="Repeteix la contrasenya" autocomplete="new-password">
                    </div>

                    <div class="settings-form-footer">
                        <span></span>
                        <button class="btn-primary-pk" id="btnSavePassword">
                            Canviar contrasenya
                        </button>
                    </div>
                </div>
            </section>

            <!-- Zona de perill -->
            <section class="card-pk settings-section settings-section--danger">
                <div class="settings-section-header">
                    <div class="settings-section-icon settings-section-icon--danger">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div>
                        <h2>Zona de perill</h2>
                        <p>Accions irreversibles sobre el teu compte</p>
                    </div>
                </div>

                <div class="danger-zone">
                    <div class="danger-item">
                        <div>
                            <p class="danger-item-title">Tancar sessió</p>
                            <p class="danger-item-desc">Tanca la sessió actual en aquest dispositiu</p>
                        </div>
                        <a href="../../backend/api/controllers/AuthController.php?action=logout"
                           class="btn-outline-pk">
                            Tancar sessió
                        </a>
                    </div>
                </div>
            </section>

        </div>

    </main>
</div>
<script>
    const API_BASE = '<?= rtrim(str_replace('/frontend', '', APP_URL), '/') ?>';
</script>
<script src="../assets/js/settings.js"></script>

</body>
</html>