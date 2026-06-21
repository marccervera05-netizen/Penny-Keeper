<?php
/*
 * TransactionController.php
 * Gestiona la creació d'ingressos i despeses via POST.
 * Retorna JSON en tots els casos.
 */

require_once __DIR__ . '/../../core/config.php';
require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../../core/auth.php';
require_once __DIR__ . '/../../models/Income.php';
require_once __DIR__ . '/../../models/Expense.php';

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
    'income'         => handleIncome($userId),
    'expense'        => handleExpense($userId),
    'update_income'  => handleUpdate($userId, 'income'),
    'update_expense' => handleUpdate($userId, 'expense'),
    'delete_income'  => handleDelete($userId, 'income'),
    'delete_expense' => handleDelete($userId, 'expense'),
    default          => jsonResponse(false, 'Acció no reconeguda.', 404),
};

// -------------------------------------------------------------

function handleIncome(int $userId): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, 'Mètode no permès.', 405);
        return;
    }

    $data   = collectTransactionData();
    $errors = validateTransaction($data);

    if (!empty($errors)) {
        jsonResponse(false, $errors[0], 422);
        return;
    }

    $model = new Income();
    $id    = $model->create($userId, $data);

    jsonResponse(true, 'Ingrés afegit correctament.', 201, ['id' => $id]);
}

function handleExpense(int $userId): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, 'Mètode no permès.', 405);
        return;
    }

    $data   = collectTransactionData();
    $errors = validateTransaction($data);

    if (!empty($errors)) {
        jsonResponse(false, $errors[0], 422);
        return;
    }

    $model = new Expense();
    $id    = $model->create($userId, $data);

    jsonResponse(true, 'Despesa afegida correctament.', 201, ['id' => $id]);
}

// -------------------------------------------------------------

function handleUpdate(int $userId, string $type): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, 'Mètode no permès.', 405);
        return;
    }

    $id   = (int) ($_POST['id'] ?? 0);
    $data = collectTransactionData();

    if ($id <= 0) {
        jsonResponse(false, 'ID no vàlid.', 422);
        return;
    }

    $errors = validateTransaction($data);
    if (!empty($errors)) {
        jsonResponse(false, $errors[0], 422);
        return;
    }

    $model  = $type === 'income' ? new Income() : new Expense();
    $result = $model->update($id, $userId, $data);

    if (!$result) {
        jsonResponse(false, 'No s\'ha pogut actualitzar.', 500);
        return;
    }

    $label = $type === 'income' ? 'Ingrés' : 'Despesa';
    jsonResponse(true, "$label actualitzat correctament.");
}

function handleDelete(int $userId, string $type): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, 'Mètode no permès.', 405);
        return;
    }

    $id = (int) ($_POST['id'] ?? 0);

    if ($id <= 0) {
        jsonResponse(false, 'ID no vàlid.', 422);
        return;
    }

    $model  = $type === 'income' ? new Income() : new Expense();
    $result = $model->delete($id, $userId);

    if (!$result) {
        jsonResponse(false, 'No s\'ha pogut eliminar.', 500);
        return;
    }

    $label = $type === 'income' ? 'Ingrés' : 'Despesa';
    jsonResponse(true, "$label eliminat correctament.");
}

function collectTransactionData(): array
{
    return [
        'description' => trim($_POST['description'] ?? ''),
        'amount'      => $_POST['amount'] ?? '',
        'date'        => trim($_POST['date'] ?? ''),
        'notes'       => trim($_POST['notes'] ?? '') ?: null,
        'isRecurring' => ($_POST['isRecurring'] ?? '0') === '1',
        'categoryId'  => isset($_POST['categoryId']) ? (int) $_POST['categoryId'] : null,
    ];
}

function validateTransaction(array $data): array
{
    $errors = [];

    if ($data['description'] === '') {
        $errors[] = 'La descripció és obligatòria.';
    }

    if (!is_numeric($data['amount']) || (float) $data['amount'] <= 0) {
        $errors[] = 'L\'import ha de ser un número positiu.';
    }

    if ($data['date'] === '' || !strtotime($data['date'])) {
        $errors[] = 'La data no és vàlida.';
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