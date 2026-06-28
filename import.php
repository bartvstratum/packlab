<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csv.php';

header('Content-Type: application/json');
$uid = require_login();

if (!csrf_check($_POST['csrf'] ?? null)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

if (empty($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$content = file_get_contents($_FILES['csv']['tmp_name']);
$listName = trim(preg_replace('/\.csv$/i', '', $_FILES['csv']['name'])) ?: 'Imported list';

try {
    $id = csv_import($uid, $content, $listName);
    echo json_encode(['ok' => true, 'id' => $id]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
