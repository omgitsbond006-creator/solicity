<?php
/**
 * Solicity Bank — database connection (MySQL / MariaDB).
 * Guarded like the rest of /db and /lib — see router.php and lib/config.php.
 *
 * ---------------------------------------------------------------------
 *  Local dev (XAMPP/MAMP/WAMP/plain mysql): the defaults below already
 *  match the standard setup, no editing needed.
 *
 *  Hosted (Railway, Render, etc.): set DB_HOST / DB_PORT / DB_NAME /
 *  DB_USER / DB_PASS as environment variables on the service — they
 *  override the local defaults automatically, nothing to edit here.
 * ---------------------------------------------------------------------
 */
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'solicity_bank');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

defined('SOLICITY_ENTRY') or die('Direct access forbidden.');

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
    } catch (PDOException $e) {
        http_response_code(500);
        die(
            "Couldn't connect to the MySQL database '" . DB_NAME . "' at " . DB_HOST . ':' . DB_PORT . ".\n\n" .
            "Check db/connect.php — DB_HOST / DB_USER / DB_PASS need to match your MySQL setup, " .
            "and the '" . DB_NAME . "' database needs to exist (create it in phpMyAdmin, or import db/schema.sql there and it'll be created for you).\n\n" .
            "Underlying error: " . $e->getMessage()
        );
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // ---- auto-provision on first run ----
    // If you already imported db/schema.sql via phpMyAdmin, the tables
    // exist and this is skipped. If not, the app creates them itself.
    $tableExists = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
    if (!$tableExists) {
        $schema = file_get_contents(__DIR__ . '/schema.sql');
        foreach (array_filter(array_map('trim', explode(';', $schema))) as $statement) {
            if ($statement === '' || str_starts_with($statement, '--')) continue;
            $pdo->exec($statement);
        }
    }

    // Same idea for demo data: only seed once, when the admins table is
    // still empty (fresh install either way — auto-created or imported).
    $adminCount = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    if ($adminCount === 0) {
        require __DIR__ . '/seed.php';
        seed_database($pdo);
    }

    // ---- incremental migration: tables added after your first install ----
    // Runs every request but is a cheap no-op once the table exists, so a
    // database set up before external transfers were added still gets it
    // without needing to re-import schema.sql or lose any existing data.
    $pdo->exec("CREATE TABLE IF NOT EXISTS external_transfers (
        id              VARCHAR(64) NOT NULL PRIMARY KEY,
        transaction_id  VARCHAR(64) NOT NULL,
        bank_name       VARCHAR(191) NOT NULL,
        account_name    VARCHAR(191) NOT NULL,
        account_number  VARCHAR(64) NOT NULL,
        routing_number  VARCHAR(32) NULL,
        swift_code      VARCHAR(32) NULL,
        created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_ext_transaction FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    return $pdo;
}

function new_id(string $prefix = ''): string {
    return $prefix . bin2hex(random_bytes(8));
}