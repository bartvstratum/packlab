<?php

require_once __DIR__ . '/db.php';

function token(int $bytes = 5): string
{
    return bin2hex(random_bytes($bytes));
}

// HTML-escape for output
function h($s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

// Default category accent colors: Okabe-Ito colorblind-safe palette (colorful
// first, neutral grey/black last). Cycled when creating categories.
function category_palette(): array
{
    return [
        '#0072b2', // blue
        '#e69f00', // orange
        '#009e73', // bluish green
        '#d55e00', // vermillion
        '#cc79a7', // reddish purple
        '#56b4e9', // sky blue
        '#f0e442', // yellow
        '#999999', // grey
        '#000000', // black
    ];
}

// Only allow http(s) links; rejects javascript:, data:, etc. (stored-XSS guard)
function safe_url(?string $url): ?string
{
    $url = trim((string) $url);
    if ($url === '') return null;
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return in_array($scheme, ['http', 'https'], true) ? $url : null;
}

// Run $fn atomically. Reentrant: a no-op (just runs) if already in a transaction.
function with_transaction(callable $fn)
{
    $pdo = db();
    if ($pdo->inTransaction()) {
        return $fn();
    }
    $pdo->beginTransaction();
    try {
        $result = $fn();
        $pdo->commit();
        return $result;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function next_pos(string $table, string $fk, int $fkVal): int
{
    $allowed = ['lists' => 'user_id', 'categories' => 'list_id', 'items' => 'category_id'];
    if (($allowed[$table] ?? null) !== $fk) {
        throw new InvalidArgumentException('Invalid position target');
    }
    $s = db()->prepare("SELECT COALESCE(MAX(position), -1) + 1 AS p FROM $table WHERE $fk = ?");
    $s->execute([$fkVal]);
    return (int) $s->fetch()['p'];
}

function user_get(int $id): ?array
{
    $s = db()->prepare('SELECT * FROM users WHERE id = ?');
    $s->execute([$id]);
    return $s->fetch() ?: null;
}

function user_by_email(string $email): ?array
{
    $s = db()->prepare('SELECT * FROM users WHERE email = ?');
    $s->execute([strtolower(trim($email))]);
    return $s->fetch() ?: null;
}

function user_create(string $email, string $password, ?string $name = null): int
{
    $s = db()->prepare('INSERT INTO users (email, name, password_hash) VALUES (?, ?, ?)');
    $s->execute([strtolower(trim($email)), $name, password_hash($password, PASSWORD_DEFAULT)]);
    return (int) db()->lastInsertId();
}

function user_verify(array $user, string $password): bool
{
    return password_verify($password, $user['password_hash']);
}

function lists_for_user(int $userId): array
{
    $s = db()->prepare('SELECT * FROM lists WHERE user_id = ? ORDER BY position, id');
    $s->execute([$userId]);
    return $s->fetchAll();
}

function list_get(int $id): ?array
{
    $s = db()->prepare('SELECT * FROM lists WHERE id = ?');
    $s->execute([$id]);
    return $s->fetch() ?: null;
}

function list_by_token(string $token): ?array
{
    $s = db()->prepare('SELECT * FROM lists WHERE share_token = ?');
    $s->execute([$token]);
    return $s->fetch() ?: null;
}

function list_create(int $userId, string $name): int
{
    $pos = next_pos('lists', 'user_id', $userId);
    $s = db()->prepare('INSERT INTO lists (user_id, name, position) VALUES (?, ?, ?)');
    $s->execute([$userId, $name, $pos]);
    return (int) db()->lastInsertId();
}

function list_rename(int $id, string $name): void
{
    $s = db()->prepare("UPDATE lists SET name = ?, updated_at = datetime('now') WHERE id = ?");
    $s->execute([$name, $id]);
}

function list_delete(int $id): void
{
    $s = db()->prepare('DELETE FROM lists WHERE id = ?');
    $s->execute([$id]);
}

function list_share_enable(int $id): string
{
    $list = list_get($id);
    if (!empty($list['share_token'])) return $list['share_token'];
    $t = token();
    $s = db()->prepare('UPDATE lists SET share_token = ? WHERE id = ?');
    $s->execute([$t, $id]);
    return $t;
}

// Deep-copy a list (categories + items, all flags) into a new list for the same user.
function list_duplicate(int $listId, ?string $name = null): int
{
    return with_transaction(function () use ($listId, $name) {
        $src = list_get($listId);
        if (!$src) throw new RuntimeException('List not found', 404);
        $newName = ($name !== null && trim($name) !== '') ? trim($name) : $src['name'] . ' (copy)';
        $newId = list_create((int) $src['user_id'], $newName);
        foreach (categories_for_list($listId) as $c) {
            $newCat = category_create($newId, $c['name'], $c['color'], $c['icon']);
            foreach (items_for_category((int) $c['id']) as $it) {
                item_create($newCat, [
                    'name'        => $it['name'],
                    'description' => $it['description'],
                    'weight'      => $it['weight'],
                    'qty'         => $it['qty'],
                    'worn'        => $it['worn'],
                    'consumable'  => $it['consumable'],
                    'flag'        => $it['flag'],
                    'big3'        => $it['big3'],
                    'packed'      => $it['packed'],
                    'url'         => $it['url'],
                ]);
            }
        }
        return $newId;
    });
}

// Clear the pack-checklist ticks for every item in a list.
function list_reset_packed(int $listId): void
{
    db()->prepare('UPDATE items SET packed = 0 WHERE category_id IN (SELECT id FROM categories WHERE list_id = ?)')
        ->execute([$listId]);
}

// Distinct items across all of a user's lists (for the add-item autocomplete).
function items_for_user(int $uid): array
{
    $s = db()->prepare(
        'SELECT DISTINCT i.name, i.weight, i.description, i.url
         FROM items i
         JOIN categories c ON c.id = i.category_id
         JOIN lists l ON l.id = c.list_id
         WHERE l.user_id = ?
         ORDER BY i.name COLLATE NOCASE'
    );
    $s->execute([$uid]);
    return $s->fetchAll();
}

function categories_for_list(int $listId): array
{
    $s = db()->prepare('SELECT * FROM categories WHERE list_id = ? ORDER BY position, id');
    $s->execute([$listId]);
    return $s->fetchAll();
}

function category_create(int $listId, string $name, ?string $color = null, ?string $icon = null): int
{
    $pos = next_pos('categories', 'list_id', $listId);
    $s = db()->prepare('INSERT INTO categories (list_id, name, color, icon, position) VALUES (?, ?, ?, ?, ?)');
    $s->execute([$listId, $name, $color, $icon, $pos]);
    return (int) db()->lastInsertId();
}

function category_update(int $id, array $f): void
{
    $s = db()->prepare('UPDATE categories SET name = ?, color = ?, icon = ? WHERE id = ?');
    $s->execute([$f['name'], $f['color'] ?? null, $f['icon'] ?? null, $id]);
}

function category_delete(int $id): void
{
    $s = db()->prepare('DELETE FROM categories WHERE id = ?');
    $s->execute([$id]);
}

function items_for_category(int $catId): array
{
    $s = db()->prepare('SELECT * FROM items WHERE category_id = ? ORDER BY position, id');
    $s->execute([$catId]);
    return $s->fetchAll();
}

function item_create(int $catId, array $f): int
{
    $pos = next_pos('items', 'category_id', $catId);
    $s = db()->prepare('INSERT INTO items (category_id, name, description, weight, qty, worn, consumable, flag, big3, packed, url, position) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $s->execute([
        $catId,
        $f['name'],
        $f['description'] ?? '',
        (float) ($f['weight'] ?? 0),
        max(0, (int) ($f['qty'] ?? 1)),
        !empty($f['worn']) ? 1 : 0,
        !empty($f['consumable']) ? 1 : 0,
        !empty($f['flag']) ? 1 : 0,
        !empty($f['big3']) ? 1 : 0,
        !empty($f['packed']) ? 1 : 0,
        safe_url($f['url'] ?? null),
        $pos,
    ]);
    return (int) db()->lastInsertId();
}

function item_update(int $id, array $f): void
{
    $s = db()->prepare('UPDATE items SET name = ?, description = ?, weight = ?, qty = ?, worn = ?, consumable = ?, flag = ?, big3 = ?, url = ? WHERE id = ?');
    $s->execute([
        $f['name'],
        $f['description'] ?? '',
        (float) ($f['weight'] ?? 0),
        max(0, (int) ($f['qty'] ?? 1)),
        !empty($f['worn']) ? 1 : 0,
        !empty($f['consumable']) ? 1 : 0,
        !empty($f['flag']) ? 1 : 0,
        !empty($f['big3']) ? 1 : 0,
        safe_url($f['url'] ?? null),
        $id,
    ]);
}

function item_set_flag(int $id, string $flag, int $val): void
{
    if (!in_array($flag, ['worn', 'consumable', 'flag', 'packed'], true)) return;
    // worn and consumable are mutually exclusive: turning one on clears the other
    if ($val && $flag === 'worn') {
        db()->prepare('UPDATE items SET worn = 1, consumable = 0 WHERE id = ?')->execute([$id]);
    } elseif ($val && $flag === 'consumable') {
        db()->prepare('UPDATE items SET consumable = 1, worn = 0 WHERE id = ?')->execute([$id]);
    } else {
        db()->prepare("UPDATE items SET $flag = ? WHERE id = ?")->execute([$val ? 1 : 0, $id]);
    }
}

function item_delete(int $id): void
{
    $s = db()->prepare('DELETE FROM items WHERE id = ?');
    $s->execute([$id]);
}

function item_move(int $id, int $catId): void
{
    $pos = next_pos('items', 'category_id', $catId);
    $s = db()->prepare('UPDATE items SET category_id = ?, position = ? WHERE id = ?');
    $s->execute([$catId, $pos, $id]);
}

function list_full(int $listId): ?array
{
    $list = list_get($listId);
    if (!$list) return null;

    $cats = categories_for_list($listId);
    $tot = ['base' => 0.0, 'consumable' => 0.0, 'worn' => 0.0, 'pack' => 0.0, 'total' => 0.0];
    $big3 = ['weight' => 0.0, 'items' => []];

    foreach ($cats as &$c) {
        $items = items_for_category((int) $c['id']);
        $cw = 0.0;
        foreach ($items as &$it) {
            $line = (float) $it['weight'] * (int) $it['qty'];
            $it['line_weight'] = $line;
            $cw += $line;
            $tot['total'] += $line;
            if ($it['worn']) {
                $tot['worn'] += (float) $it['weight'] * min((int) $it['qty'], 1);
            } elseif ($it['consumable']) {
                $tot['consumable'] += $line;
            }
            if ($it['big3']) {
                $big3['weight'] += $line;
                $big3['items'][] = ['name' => $it['name'], 'weight' => $line];
            }
        }
        unset($it);
        $c['items'] = $items;
        $c['weight'] = $cw;
    }
    unset($c);

    $tot['base'] = $tot['total'] - $tot['worn'] - $tot['consumable'];
    $tot['pack'] = $tot['total'] - $tot['worn'];

    $t = $tot['total'] ?: 1;
    foreach (['base', 'consumable', 'pack', 'worn'] as $k) {
        $tot[$k . '_pct'] = (int) round($tot[$k] / $t * 100);
    }
    $tot['total_pct'] = 100;
    foreach ($cats as &$c) {
        $c['pct'] = (int) round($c['weight'] / $t * 100);
    }
    unset($c);

    // Big 3 as a share of base weight (what you actually optimize)
    usort($big3['items'], fn($a, $b) => $b['weight'] <=> $a['weight']);
    $big3['pct'] = $tot['base'] > 0 ? (int) round($big3['weight'] / $tot['base'] * 100) : 0;

    $list['categories'] = $cats;
    $list['totals'] = $tot;
    $list['big3'] = $big3;
    return $list;
}

function item_get(int $id): ?array
{
    $s = db()->prepare('SELECT * FROM items WHERE id = ?');
    $s->execute([$id]);
    return $s->fetch() ?: null;
}

function owns_list(int $uid, int $listId): bool
{
    $l = list_get($listId);
    return $l && (int) $l['user_id'] === $uid;
}

function category_owner(int $catId): ?int
{
    $s = db()->prepare('SELECT l.user_id FROM categories c JOIN lists l ON l.id = c.list_id WHERE c.id = ?');
    $s->execute([$catId]);
    $r = $s->fetch();
    return $r ? (int) $r['user_id'] : null;
}

function item_owner(int $itemId): ?int
{
    $s = db()->prepare('SELECT l.user_id FROM items i JOIN categories c ON c.id = i.category_id JOIN lists l ON l.id = c.list_id WHERE i.id = ?');
    $s->execute([$itemId]);
    $r = $s->fetch();
    return $r ? (int) $r['user_id'] : null;
}

function item_list_id(int $itemId): ?int
{
    $s = db()->prepare('SELECT c.list_id FROM items i JOIN categories c ON c.id = i.category_id WHERE i.id = ?');
    $s->execute([$itemId]);
    $r = $s->fetch();
    return $r ? (int) $r['list_id'] : null;
}

function share_url(string $token): string
{
    $base = rtrim(config()['base_url'] ?? '', '/');
    return $base . '/share.php?token=' . $token;
}

function category_get(int $id): ?array
{
    $s = db()->prepare('SELECT * FROM categories WHERE id = ?');
    $s->execute([$id]);
    return $s->fetch() ?: null;
}

function category_move(int $id, int $dir): void
{
    $cat = category_get($id);
    if (!$cat) return;
    $cats = categories_for_list((int) $cat['list_id']);
    $ids = array_map('intval', array_column($cats, 'id'));
    $pos = array_search($id, $ids, true);
    if ($pos === false) return;
    $swap = $pos + ($dir < 0 ? -1 : 1);
    if ($swap < 0 || $swap >= count($cats)) return;
    $a = $cats[$pos];
    $b = $cats[$swap];
    with_transaction(function () use ($a, $b) {
        $s = db()->prepare('UPDATE categories SET position = ? WHERE id = ?');
        $s->execute([(int) $b['position'], (int) $a['id']]);
        $s->execute([(int) $a['position'], (int) $b['id']]);
    });
}

function categories_sort_by_weight(int $listId): void
{
    $cats = categories_for_list($listId);
    $weight = [];
    foreach ($cats as $c) {
        $sum = 0.0;
        foreach (items_for_category((int) $c['id']) as $it) {
            $sum += (float) $it['weight'] * (int) $it['qty'];
        }
        $weight[(int) $c['id']] = $sum;
    }
    $ids = array_map(fn($c) => (int) $c['id'], $cats);
    usort($ids, fn($a, $b) => $weight[$b] <=> $weight[$a]);
    with_transaction(function () use ($ids) {
        $s = db()->prepare('UPDATE categories SET position = ? WHERE id = ?');
        foreach ($ids as $i => $id) {
            $s->execute([$i, $id]);
        }
    });
}

function category_sort_items_by_weight(int $catId): void
{
    $items = items_for_category($catId);
    usort($items, fn($a, $b) =>
        ((float) $b['weight'] * (int) $b['qty']) <=> ((float) $a['weight'] * (int) $a['qty']));
    with_transaction(function () use ($items) {
        $s = db()->prepare('UPDATE items SET position = ? WHERE id = ?');
        foreach ($items as $i => $it) {
            $s->execute([$i, (int) $it['id']]);
        }
    });
}

function list_sort_by_weight(int $listId): void
{
    with_transaction(function () use ($listId) {
        categories_sort_by_weight($listId);
        foreach (categories_for_list($listId) as $c) {
            category_sort_items_by_weight((int) $c['id']);
        }
    });
}

function user_set_password(int $id, string $password): void
{
    $s = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $s->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
}
