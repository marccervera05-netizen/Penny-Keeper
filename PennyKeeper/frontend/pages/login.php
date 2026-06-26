<?php
/*
 * login.php
 * Pàgina de inici de sessió.
 * Si l'usuari ja té sessió, redirigeix directament al dashboard.
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
    <title>Inicia sessió · PennyKeeper</title>

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
        <p>El teu gestor de finances personal. Controla ingressos, despeses i plans d'estalvi en un sol lloc.</p>
    </div>

    <!-- Panel dret: formulari -->
    <div class="auth-panel auth-panel--right">
        <div class="auth-form-box">

            <div>
                <h2>Benvingut de nou</h2>
                <p class="subtitle">Inicia sessió per continuar</p>
            </div>

            <div id="loginAlert" class="alert-pk alert-pk--error" role="alert"></div>

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
                        placeholder="••••••••"
                        autocomplete="current-password"
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

            <button
                type="button"
                id="loginBtn"
                class="btn-primary-pk"
                style="width:100%; padding: 0.75rem;"
            >
                Inicia sessió
            </button>

            <p style="text-align:center; font-size:0.88rem; color:var(--color-text-muted);">
                Encara no tens compte?
                <a href="register.php" style="color:var(--color-dark); font-weight:600;">Registra't</a>
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