<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csv.php';

$uid = current_user_id();
if ($uid === null) {
    header('Location: login.php');
    exit;
}

$listId = (int) ($_GET['list'] ?? 0);
if (!owns_list($uid, $listId)) {
    http_response_code(404);
    exit('Not found');
}

$list = list_get($listId);
$fname = preg_replace('/[^A-Za-z0-9._-]+/', '_', $list['name'] ?: 'packlab') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $fname . '"');
echo csv_export($listId);
