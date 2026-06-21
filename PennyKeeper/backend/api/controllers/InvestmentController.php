<?php
/*
 * InvestmentController.php
 * Gestiona CRUD d'inversions, aportacions i actualització de valor de mercat.
 */

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../models/Investment.php';

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
    'create'              => handleCreate($userId),
    'update'              => handleUpdate($userId),
    'delete'              => handleDelete($userId),
    'add_contribution'    => handleAddContribution($userId),
    'delete_contribution' => handleDeleteContribution($userId),
    'update_value'        => handleUpdateValue($userId),
    default               => jsonResponse(false, 'Acció no reconeguda.', 404),
};

// -------------------------------------------------------------

function handleCreate(int $userId): void
{
    requirePost();
    $data   = collectBaseData();
    $errors = validateBase($data);
    if (!empty($errors)) { jsonResponse(false, $errors[0], 422); return; }

    $model = new Investment();
    $id    = $model->create($userId, $data);
    jsonResponse(true, 'Inversió creada correctament.', 201, ['id' => $id]);
}

function handleUpdate(int $userId): void
{
    requirePost();
    $id   = (int) ($_POST['id'] ?? 0);
    $data = collectBaseData();
    if ($id <= 0) { jsonResponse(false, 'ID no vàlid.', 422); return; }

    $model  = new Investment();
    $result = $model->update($id, $userId, $data);
    $result
        ? jsonResponse(true, 'Inversió actualitzada.')
        : jsonResponse(false, 'No s\'ha pogut actualitzar.', 500);
}

function handleDelete(int $userId): void
{
    requirePost();
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) { jsonResponse(false, 'ID no vàlid.', 422); return; }

    $model  = new Investment();
    $result = $model->delete($id, $userId);
    $result
        ? jsonResponse(true, 'Inversió eliminada.')
        : jsonResponse(false, 'No s\'ha pogut eliminar.', 500);
}

function handleAddContribution(int $userId): void
{
    requirePost();
    $investmentId = (int) ($_POST['investmentId'] ?? 0);
    $amount       = $_POST['amount'] ?? '';
    $date         = trim($_POST['date'] ?? '');
    $notes        = trim($_POST['notes'] ?? '') ?: null;

    if ($investmentId <= 0)                            { jsonResponse(false, 'Inversió no vàlida.', 422); return; }
    if (!is_numeric($amount) || (float)$amount <= 0)   { jsonResponse(false, 'L\'import ha de ser positiu.', 422); return; }
    if (!$date || !strtotime($date))                   { jsonResponse(false, 'La data no és vàlida.', 422); return; }

    $model  = new Investment();
    $result = $model->addContribution($investmentId, $userId, (float) $amount, $date, $notes);
    $result
        ? jsonResponse(true, 'Aportació afegida correctament.')
        : jsonResponse(false, 'No s\'ha pogut afegir l\'aportació.', 500);
}

function handleDeleteContribution(int $userId): void
{
    requirePost();
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) { jsonResponse(false, 'ID no vàlid.', 422); return; }

    $model  = new Investment();
    $result = $model->deleteContribution($id, $userId);
    $result
        ? jsonResponse(true, 'Aportació eliminada.')
        : jsonResponse(false, 'No s\'ha pogut eliminar.', 500);
}

function handleUpdateValue(int $userId): void
{
    requirePost();
    $investmentId = (int) ($_POST['investmentId'] ?? 0);
    $value        = $_POST['value'] ?? '';
    $date         = trim($_POST['date'] ?? '');

    if ($investmentId <= 0)                          { jsonResponse(false, 'Inversió no vàlida.', 422); return; }
    if (!is_numeric($value) || (float)$value < 0)   { jsonResponse(false, 'El valor no pot ser negatiu.', 422); return; }
    if (!$date || !strtotime($date))                 { jsonResponse(false, 'La data no és vàlida.', 422); return; }

    $model  = new Investment();
    $result = $model->updateMarketValue($investmentId, $userId, (float) $value, $date);
    $result
        ? jsonResponse(true, 'Valor de mercat actualitzat.')
        : jsonResponse(false, 'No s\'ha pogut actualitzar.', 500);
}

// -------------------------------------------------------------

function collectBaseData(): array
{
    return [
        'name'          => trim($_POST['name'] ?? ''),
        'type'          => $_POST['type'] ?? 'other',
        'initialAmount' => $_POST['initialAmount'] ?? '0',
        'startDate'     => trim($_POST['startDate'] ?? ''),
        'isRecurring'   => ($_POST['isRecurring'] ?? '0') === '1',
        'notes'         => trim($_POST['notes'] ?? '') ?: null,
    ];
}

function validateBase(array $data): array
{
    $validTypes = ['stocks', 'crypto', 'funds', 'real_estate', 'other'];
    $errors     = [];

    if ($data['name'] === '')                              $errors[] = 'El nom és obligatori.';
    if (!in_array($data['type'], $validTypes, true))       $errors[] = 'Tipus no vàlid.';
    if (!is_numeric($data['initialAmount']) || (float)$data['initialAmount'] < 0) $errors[] = 'L\'import inicial no pot ser negatiu.';
    if ($data['startDate'] === '' || !strtotime($data['startDate'])) $errors[] = 'La data d\'inici no és vàlida.';

    return $errors;
}

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