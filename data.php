<?php

require_once __DIR__ . '/db.php';

function token(int $bytes = 5): string
{
    return bin2hex(random_bytes($bytes));
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
    $s = db()->prepare('INSERT INTO items (category_id, name, description, weight, qty, worn, consumable, url, position) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $s->execute([
        $catId,
        $f['name'],
        $f['description'] ?? '',
        (float) ($f['weight'] ?? 0),
        max(0, (int) ($f['qty'] ?? 1)),
        !empty($f['worn']) ? 1 : 0,
        !empty($f['consumable']) ? 1 : 0,
        $f['url'] ?? null,
        $pos,
    ]);
    return (int) db()->lastInsertId();
}

function item_update(int $id, array $f): void
{
    $s = db()->prepare('UPDATE items SET name = ?, description = ?, weight = ?, qty = ?, worn = ?, consumable = ?, url = ? WHERE id = ?');
    $s->execute([
        $f['name'],
        $f['description'] ?? '',
        (float) ($f['weight'] ?? 0),
        max(0, (int) ($f['qty'] ?? 1)),
        !empty($f['worn']) ? 1 : 0,
        !empty($f['consumable']) ? 1 : 0,
        $f['url'] ?? null,
        $id,
    ]);
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

    $list['categories'] = $cats;
    $list['totals'] = $tot;
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
    $s = db()->prepare('UPDATE categories SET position = ? WHERE id = ?');
    $s->execute([(int) $b['position'], (int) $a['id']]);
    $s->execute([(int) $a['position'], (int) $b['id']]);
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
    $s = db()->prepare('UPDATE categories SET position = ? WHERE id = ?');
    foreach ($ids as $i => $id) {
        $s->execute([$i, $id]);
    }
}

function category_sort_items_by_weight(int $catId): void
{
    $items = items_for_category($catId);
    usort($items, fn($a, $b) =>
        ((float) $b['weight'] * (int) $b['qty']) <=> ((float) $a['weight'] * (int) $a['qty']));
    $s = db()->prepare('UPDATE items SET position = ? WHERE id = ?');
    foreach ($items as $i => $it) {
        $s->execute([$i, (int) $it['id']]);
    }
}

function list_sort_by_weight(int $listId): void
{
    categories_sort_by_weight($listId);
    foreach (categories_for_list($listId) as $c) {
        category_sort_items_by_weight((int) $c['id']);
    }
}
