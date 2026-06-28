<?php

function config(): array
{
    static $cfg = null;
    if ($cfg !== null) return $cfg;
    $file = __DIR__ . '/config.php';
    if (!file_exists($file)) $file = __DIR__ . '/config.example.php';
    $cfg = require $file;
    return $cfg;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $path = config()['db_path'];
    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0775, true);

    $pdo = new PDO('sqlite:' . $path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec(file_get_contents(__DIR__ . '/schema.sql'));

    // Lightweight migrations for existing databases
    $cols = $pdo->query("PRAGMA table_info(items)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('flag', $cols, true)) {
        $pdo->exec('ALTER TABLE items ADD COLUMN flag INTEGER NOT NULL DEFAULT 0');
    }

    return $pdo;
}
