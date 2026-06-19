<?php
/*
 * AuthController.php
 * Gestiona les peticions POST de registre i login.
 * Retorna JSON en tots els casos.
 */

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Category.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

match ($action) {
    'register' => handleRegister(),
    'login'    => handleLogin(),
    'logout'   => handleLogout(),
    default    => jsonResponse(false, 'Acció no reconeguda.', 404),
};

// -------------------------------------------------------------

function handleRegister(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, 'Mètode no permès.', 405);
        return;
    }

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['passwordConfirm'] ?? '';

    $errors = validateRegister($username, $email, $password, $confirm);

    if (!empty($errors)) {
        jsonResponse(false, $errors[0], 422);
        return;
    }

    $userModel = new User();

    if ($userModel->emailExists($email)) {
        jsonResponse(false, 'Aquest email ja està registrat.', 409);
        return;
    }

    if ($userModel->usernameExists($username)) {
        jsonResponse(false, 'Aquest nom d\'usuari ja existeix.', 409);
        return;
    }

    $userId = $userModel->create($username, $email, $password);

    if ($userId === false) {
        jsonResponse(false, 'Error en crear el compte. Torna-ho a intentar.', 500);
        return;
    }

    // Categories per defecte per al nou usuari
    $categoryModel = new Category();
    $categoryModel->createDefaults($userId);

    Auth::login($userId, $username);

    jsonResponse(true, 'Compte creat correctament.', 201, [
        'redirect' => APP_URL . '/pages/dashboard.php',
    ]);
}

function handleLogin(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, 'Mètode no permès.', 405);
        return;
    }

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        jsonResponse(false, 'Omple tots els camps.', 422);
        return;
    }

    $userModel = new User();
    $user      = $userModel->verify($email, $password);

    if ($user === null) {
        jsonResponse(false, 'Email o contrasenya incorrectes.', 401);
        return;
    }

    Auth::login($user['id'], $user['username']);

    jsonResponse(true, 'Sessió iniciada.', 200, [
        'redirect' => APP_URL . '/pages/dashboard.php',
    ]);
}

function handleLogout(): void
{
    Auth::logout();
}

// -------------------------------------------------------------

function validateRegister(string $username, string $email, string $password, string $confirm): array
{
    $errors = [];

    if ($username === '') {
        $errors[] = 'El nom d\'usuari és obligatori.';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = 'El nom d\'usuari ha de tenir entre 3 i 50 caràcters.';
    }

    if ($email === '') {
        $errors[] = 'L\'email és obligatori.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L\'email no té un format vàlid.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'La contrasenya ha de tenir mínim 8 caràcters.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Les contrasenyes no coincideixen.';
    }

    return $errors;
}

function jsonResponse(bool $success, string $message, int $statusCode = 200, array $extra = []): void
{
    http_response_code($statusCode);
    echo json_encode(array_merge(
        ['success' => $success, 'message' => $message],
        $extra
    ));
    exit;
}