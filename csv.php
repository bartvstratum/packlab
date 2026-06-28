<?php

require_once __DIR__ . '/data.php';

function csv_truthy(string $v): bool
{
    $v = strtolower(trim($v));
    return $v !== '' && !in_array($v, ['0', 'false', 'no', 'n', 'off'], true);
}

function csv_to_grams(float $w, string $unit): float
{
    return match (strtolower(trim($unit))) {
        'kg', 'kilogram', 'kilograms'  => $w * 1000,
        'oz', 'ounce', 'ounces'        => $w * 28.349523125,
        'lb', 'lbs', 'pound', 'pounds' => $w * 453.59237,
        default                        => $w,
    };
}

function csv_num(float $w): string
{
    return rtrim(rtrim(number_format($w, 2, '.', ''), '0'), '.');
}

function csv_export(int $listId): string
{
    $list = list_full($listId);
    if (!$list) return '';

    $out = fopen('php://temp', 'r+');
    fputcsv($out, ['Item Name', 'Category', 'desc', 'qty', 'weight', 'unit', 'url', 'price', 'worn', 'consumable', 'flag']);
    foreach ($list['categories'] as $c) {
        foreach ($c['items'] as $it) {
            fputcsv($out, [
                $it['name'],
                $c['name'],
                $it['description'] ?? '',
                (int) $it['qty'],
                csv_num((float) $it['weight']),
                'g',
                $it['url'] ?? '',
                '',
                $it['worn'] ? '1' : '',
                $it['consumable'] ? '1' : '',
                $it['flag'] ? '1' : '',
            ]);
        }
    }
    rewind($out);
    return stream_get_contents($out);
}

function csv_import(int $userId, string $csv, ?string $listName = null): int
{
    $lines = preg_split('/\r\n|\r|\n/', trim($csv));
    $rows = array_map('str_getcsv', $lines);
    $header = array_map(fn($h) => strtolower(trim($h)), array_shift($rows));
    $col = array_flip($header);

    $get = function (array $r, string $key) use ($col): string {
        $i = $col[$key] ?? null;
        return $i === null ? '' : trim((string) ($r[$i] ?? ''));
    };

    $listId = list_create($userId, $listName ?: 'Imported list');
    $palette = ['#7c9cff', '#f08fb0', '#9b8cff', '#5bb3a9', '#f6a35c', '#74c47d', '#f6c453', '#6cc2d6'];
    $catIds = [];
    $ci = 0;

    foreach ($rows as $r) {
        $name = $get($r, 'item name');
        if ($name === '') continue;

        $catName = $get($r, 'category') ?: 'Uncategorized';
        if (!isset($catIds[$catName])) {
            $catIds[$catName] = category_create($listId, $catName, $palette[$ci % count($palette)]);
            $ci++;
        }

        $weight = csv_to_grams((float) $get($r, 'weight'), $get($r, 'unit') ?: 'g');
        $consumable = csv_truthy($get($r, 'consumable')) || stripos($catName, 'consumable') === 0;
        $qty = max(0, (int) (float) $get($r, 'qty'));

        item_create($catIds[$catName], [
            'name'        => $name,
            'description' => $get($r, 'desc'),
            'weight'      => $weight,
            'qty'         => $qty,
            'worn'        => csv_truthy($get($r, 'worn')) ? 1 : 0,
            'consumable'  => $consumable ? 1 : 0,
            'flag'        => csv_truthy($get($r, 'flag')) ? 1 : 0,
            'url'         => $get($r, 'url') ?: null,
        ]);
    }

    return $listId;
}

if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    $cmd = $argv[1] ?? '';
    if ($cmd === 'export') {
        echo csv_export((int) ($argv[2] ?? 0));
    } elseif ($cmd === 'import') {
        $id = csv_import((int) ($argv[2] ?? 0), file_get_contents($argv[3] ?? ''), $argv[4] ?? null);
        fwrite(STDERR, "Imported into list #$id\n");
    } else {
        fwrite(STDERR, "Usage: php csv.php export <listId> | import <userId> <file> [listName]\n");
    }
}
