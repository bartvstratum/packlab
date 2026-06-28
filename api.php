<?php

require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');
$uid = require_login();

$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in)) {
    $in = $_POST;
}

if (!csrf_check($in['csrf'] ?? null)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$fields_from = function (array $in): array {
    return [
        'name'        => trim((string) ($in['name'] ?? '')),
        'description' => trim((string) ($in['description'] ?? '')),
        'weight'      => (float) ($in['weight'] ?? 0),
        'qty'         => (int) ($in['qty'] ?? 1),
        'worn'        => !empty($in['worn']) ? 1 : 0,
        'consumable'  => !empty($in['consumable']) ? 1 : 0,
        'url'         => trim((string) ($in['url'] ?? '')) ?: null,
    ];
};

try {
    switch ($in['action'] ?? '') {

        case 'item_save':
            $f = $fields_from($in);
            if ($f['name'] === '') {
                throw new RuntimeException('Name is required', 400);
            }
            $id = (int) ($in['id'] ?? 0);
            if ($id > 0) {
                if (item_owner($id) !== $uid) {
                    throw new RuntimeException('Item not found', 404);
                }
                $item = item_get($id);
                $catId = (int) ($in['category_id'] ?? $item['category_id']);
                if ($catId !== (int) $item['category_id'] && category_owner($catId) === $uid) {
                    item_move($id, $catId);
                }
                item_update($id, $f);
            } else {
                $catId = (int) ($in['category_id'] ?? 0);
                if (category_owner($catId) !== $uid) {
                    throw new RuntimeException('Invalid category', 400);
                }
                $id = item_create($catId, $f);
            }
            echo json_encode(['ok' => true, 'id' => $id]);
            break;

        case 'item_delete':
            $id = (int) ($in['id'] ?? 0);
            if (item_owner($id) !== $uid) {
                throw new RuntimeException('Item not found', 404);
            }
            item_delete($id);
            echo json_encode(['ok' => true]);
            break;

        case 'category_create':
            $listId = (int) ($in['list_id'] ?? 0);
            if (!owns_list($uid, $listId)) {
                throw new RuntimeException('Invalid list', 400);
            }
            $name = trim((string) ($in['name'] ?? ''));
            if ($name === '') {
                throw new RuntimeException('Name is required', 400);
            }
            $palette = ['#7c9cff', '#f08fb0', '#9b8cff', '#5bb3a9', '#f6a35c', '#74c47d', '#f6c453', '#6cc2d6'];
            $n = count(categories_for_list($listId));
            $id = category_create($listId, $name, $palette[$n % count($palette)]);
            echo json_encode(['ok' => true, 'id' => $id]);
            break;

        case 'share_enable':
            $listId = (int) ($in['list_id'] ?? 0);
            if (!owns_list($uid, $listId)) {
                throw new RuntimeException('Invalid list', 400);
            }
            $token = list_share_enable($listId);
            echo json_encode(['ok' => true, 'token' => $token, 'url' => share_url($token)]);
            break;

        case 'list_create':
            $name = trim((string) ($in['name'] ?? ''));
            if ($name === '') {
                throw new RuntimeException('Name is required', 400);
            }
            $id = list_create($uid, $name);
            echo json_encode(['ok' => true, 'id' => $id]);
            break;

        case 'list_rename':
            $listId = (int) ($in['list_id'] ?? 0);
            if (!owns_list($uid, $listId)) {
                throw new RuntimeException('Invalid list', 400);
            }
            $name = trim((string) ($in['name'] ?? ''));
            if ($name === '') {
                throw new RuntimeException('Name is required', 400);
            }
            list_rename($listId, $name);
            echo json_encode(['ok' => true]);
            break;

        case 'list_delete':
            $listId = (int) ($in['list_id'] ?? 0);
            if (!owns_list($uid, $listId)) {
                throw new RuntimeException('Invalid list', 400);
            }
            list_delete($listId);
            echo json_encode(['ok' => true]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unknown action']);
    }
} catch (Throwable $e) {
    $code = (int) $e->getCode();
    http_response_code($code >= 400 && $code < 600 ? $code : 400);
    echo json_encode(['error' => $e->getMessage()]);
}
