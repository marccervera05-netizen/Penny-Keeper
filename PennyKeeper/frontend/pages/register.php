<?php
/*
 * register.php
 * Pàgina de registre de compte nou.
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
    <title>Crea un compte · PennyKeeper</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/main.css" rel="stylesheet">
</head>
<body>

<div class="auth-wrapper">

    <!-- Panel esquerre: marca -->
    <div class="auth-panel auth-panel--left">
        <div class="logo-mark">
            <i class="bi bi-coin"></i>
        </div>
        <h1>PennyKeeper</h1>
        <p>Comença a tenir control real dels teus diners. Crea el teu compte en menys d'un minut.</p>
    </div>

    <!-- Panel dret: formulari -->
    <div class="auth-panel auth-panel--right">
        <div class="auth-form-box">

            <div>
                <h2>Crea un compte</h2>
                <p class="subtitle">Omple les dades per començar</p>
            </div>

            <div id="registerAlert" class="alert-pk" role="alert"></div>

            <div class="form-group">
                <label class="form-label" for="username">Nom d'usuari</label>
                <input
                    class="form-input"
                    type="text"
                    id="username"
                    name="username"
                    placeholder="el_teu_nom"
                    autocomplete="username"
                    maxlength="50"
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input
                    class="form-input"
                    type="email"
                    id="email"
                    name="email"
                    placeholder="nom@exemple.com"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Contrasenya</label>
                <div style="position:relative;">
                    <input
                        class="form-input"
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Mínim 8 caràcters"
                        autocomplete="new-password"
                        style="padding-right: 2.8rem;"
                        required
                    >
                    <button
                        type="button"
                        id="togglePassword"
                        aria-label="Mostrar contrasenya"
                        style="position:absolute; right:0.75rem; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:var(--color-text-muted); padding:0;"
                    >
                        <i class="bi bi-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="passwordConfirm">Confirma la contrasenya</label>
                <input
                    class="form-input"
                    type="password"
                    id="passwordConfirm"
                    name="passwordConfirm"
                    placeholder="Repeteix la contrasenya"
                    autocomplete="new-password"
                    required
                >
            </div>

            <button
                type="button"
                id="registerBtn"
                class="btn-primary-pk"
                style="width:100%; padding: 0.75rem;"
            >
                Crea el compte
            </button>

            <p style="text-align:center; font-size:0.88rem; color:var(--color-text-muted);">
                Ja tens compte?
                <a href="login.php" style="color:var(--color-dark); font-weight:600;">Inicia sessió</a>
            </p>

        </div>
    </div>

</div>
<script>
    const API_BASE = '<?= rtrim(str_replace('/frontend', '', APP_URL), '/') ?>';
</script>
<script src="../assets/js/auth.js"></script>
</body>
</html>