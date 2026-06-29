<?php

// Sets up / resets the public demo account.
// Run via Plesk -> Scheduled Tasks -> "Run a PHP script" (runs as CLI).
// CLI-only so it can't be triggered from the web.

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script runs from the command line / scheduled task only.\n");
}

require __DIR__ . '/csv.php';

$email = 'demo';
$pass  = 'demo';

$existing = user_by_email($email);
$uid = $existing ? (int) $existing['id'] : user_create($email, $pass, 'Demo');
user_set_password($uid, $pass); // always re-assert the demo password

// Wipe existing demo lists (cascade removes their categories + items)
foreach (lists_for_user($uid) as $l) {
    list_delete((int) $l['id']);
}

// Re-seed from the bundled demo CSV (flag/big3 columns included)
csv_import($uid, file_get_contents(__DIR__ . '/demo.csv'), 'Demo gear list');

fwrite(STDOUT, "Demo account reset: login '$email' / password '$pass'\n");
