<?php
/*
 * SavingController.php
 * Gestiona totes les operacions CRUD de plans d'estalvi i aportacions.
 * Retorna JSON en tots els casos.
 */

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../models/SavingPlan.php';

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
    'create'               => handleCreate($userId),
    'update'               => handleUpdate($userId),
    'delete'               => handleDelete($userId),
    'add_contribution'     => handleAddContribution($userId),
    'delete_contribution'  => handleDeleteContribution($userId),
    default                => jsonResponse(false, 'Acció no reconeguda.', 404),
};

// -------------------------------------------------------------

function handleCreate(int $userId): void
{
    requirePost();

    $data   = collectPlanData();
    $errors = validatePlan($data);

    if (!empty($errors)) {
        jsonResponse(false, $errors[0], 422);
        return;
    }

    $model = new SavingPlan();
    $id    = $model->create($userId, $data);

    jsonResponse(true, 'Pla d\'estalvi creat correctament.', 201, ['id' => $id]);
}

function handleUpdate(int $userId): void
{
    requirePost();

    $id   = (int) ($_POST['id'] ?? 0);
    $data = collectPlanData();

    if ($id <= 0) { jsonResponse(false, 'ID no vàlid.', 422); return; }

    $errors = validatePlan($data);
    if (!empty($errors)) { jsonResponse(false, $errors[0], 422); return; }

    $model  = new SavingPlan();
    $result = $model->update($id, $userId, $data);

    $result
        ? jsonResponse(true, 'Pla actualitzat correctament.')
        : jsonResponse(false, 'No s\'ha pogut actualitzar.', 500);
}

function handleDelete(int $userId): void
{
    requirePost();

    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) { jsonResponse(false, 'ID no vàlid.', 422); return; }

    $model  = new SavingPlan();
    $result = $model->delete($id, $userId);

    $result
        ? jsonResponse(true, 'Pla eliminat correctament.')
        : jsonResponse(false, 'No s\'ha pogut eliminar.', 500);
}

function handleAddContribution(int $userId): void
{
    requirePost();

    $planId = (int) ($_POST['planId'] ?? 0);
    $amount = $_POST['amount'] ?? '';
    $date   = trim($_POST['date'] ?? '');
    $notes  = trim($_POST['notes'] ?? '') ?: null;

    if ($planId <= 0)                              { jsonResponse(false, 'Pla no vàlid.', 422); return; }
    if (!is_numeric($amount) || (float)$amount <= 0) { jsonResponse(false, 'L\'import ha de ser positiu.', 422); return; }
    if (!$date || !strtotime($date))               { jsonResponse(false, 'La data no és vàlida.', 422); return; }

    $model  = new SavingPlan();
    $result = $model->addContribution($planId, $userId, (float) $amount, $date, $notes);

    $result
        ? jsonResponse(true, 'Aportació afegida correctament.')
        : jsonResponse(false, 'No s\'ha pogut afegir l\'aportació.', 500);
}

function handleDeleteContribution(int $userId): void
{
    requirePost();

    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) { jsonResponse(false, 'ID no vàlid.', 422); return; }

    $model  = new SavingPlan();
    $result = $model->deleteContribution($id, $userId);

    $result
        ? jsonResponse(true, 'Aportació eliminada correctament.')
        : jsonResponse(false, 'No s\'ha pogut eliminar.', 500);
}

// -------------------------------------------------------------

function collectPlanData(): array
{
    return [
        'name'         => trim($_POST['name'] ?? ''),
        'targetAmount' => $_POST['targetAmount'] ?? '',
        'deadline'     => trim($_POST['deadline'] ?? '') ?: null,
        'notes'        => trim($_POST['notes'] ?? '') ?: null,
        'status'       => $_POST['status'] ?? 'active',
    ];
}

function validatePlan(array $data): array
{
    $errors = [];

    if ($data['name'] === '') {
        $errors[] = 'El nom del pla és obligatori.';
    }
    if (!is_numeric($data['targetAmount']) || (float) $data['targetAmount'] <= 0) {
        $errors[] = 'L\'objectiu ha de ser un número positiu.';
    }
    if ($data['deadline'] !== null && !strtotime($data['deadline'])) {
        $errors[] = 'La data límit no és vàlida.';
    }

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
    echo json_encode(array_merge(
        ['success' => $success, 'message' => $message],
        $extra
    ));
    exit;
}