<?php
/*
 * SettingsController.php
 * Gestiona l'actualització de les dades del compte de l'usuari.
 * Retorna JSON en tots els casos.
 */

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../models/User.php';

header('Content-Type: application/json');

Auth::startSession();

if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticat.']);
    exit;
}

$action = $_GET['action'] ?? '';
$userId = Auth::userId();

match ($action) {
    'update_profile'  => handleUpdateProfile($userId),
    'update_password' => handleUpdatePassword($userId),
    default           => jsonResponse(false, 'Acció no reconeguda.', 404),
};

// -------------------------------------------------------------

function handleUpdateProfile(int $userId): void
{
    requirePost();

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $currency = trim($_POST['currency'] ?? 'EUR');

    if ($username === '' || strlen($username) < 3) {
        jsonResponse(false, 'El nom d\'usuari ha de tenir mínim 3 caràcters.', 422);
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'L\'email no és vàlid.', 422);
        return;
    }

    $validCurrencies = ['EUR', 'USD', 'GBP'];
    if (!in_array($currency, $validCurrencies, true)) {
        jsonResponse(false, 'Moneda no vàlida.', 422);
        return;
    }

    $userModel = new User();
    $current   = $userModel->findById($userId);

    // Comprova duplicats només si han canviat
    if ($email !== $current['email'] && $userModel->emailExists($email)) {
        jsonResponse(false, 'Aquest email ja està en ús.', 409);
        return;
    }

    if ($username !== $current['username'] && $userModel->usernameExists($username)) {
        jsonResponse(false, 'Aquest nom d\'usuari ja està en ús.', 409);
        return;
    }

    $db   = Database::getConnection();
    $stmt = $db->prepare(
        'UPDATE users SET username = :username, email = :email WHERE id = :id'
    );
    $stmt->execute([':username' => $username, ':email' => $email, ':id' => $userId]);

    $userModel->updateCurrency($userId, $currency);

    // Actualitza el nom d'usuari a la sessió
    $_SESSION['username'] = $username;

    jsonResponse(true, 'Perfil actualitzat correctament.');
}

function handleUpdatePassword(int $userId): void
{
    requirePost();

    $current  = $_POST['currentPassword'] ?? '';
    $new      = $_POST['newPassword'] ?? '';
    $confirm  = $_POST['confirmPassword'] ?? '';

    if ($current === '' || $new === '' || $confirm === '') {
        jsonResponse(false, 'Omple tots els camps.', 422);
        return;
    }

    if (strlen($new) < 8) {
        jsonResponse(false, 'La nova contrasenya ha de tenir mínim 8 caràcters.', 422);
        return;
    }

    if ($new !== $confirm) {
        jsonResponse(false, 'Les contrasenyes no coincideixen.', 422);
        return;
    }

    $userModel = new User();
    $db        = Database::getConnection();

    // Obtenim el hash actual per verificar
    $stmt = $db->prepare('SELECT passwordHash FROM users WHERE id = :id');
    $stmt->execute([':id' => $userId]);
    $row = $stmt->fetch();

    if (!password_verify($current, $row['passwordHash'])) {
        jsonResponse(false, 'La contrasenya actual és incorrecta.', 401);
        return;
    }

    $stmt = $db->prepare(
        'UPDATE users SET passwordHash = :hash WHERE id = :id'
    );
    $stmt->execute([
        ':hash' => password_hash($new, PASSWORD_BCRYPT),
        ':id'   => $userId,
    ]);

    jsonResponse(true, 'Contrasenya actualitzada correctament.');
}

// -------------------------------------------------------------

function requirePost(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, 'Mètode no permès.', 405);
        exit;
    }
}

function jsonResponse(bool $success, string $message, int $statusCode = 200, array $extra = []): void
{
    http_response_code($statusCode);
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}