<?php
/**
 * Router for PHP's built-in server: `php -S localhost:8000 router.php`
 * Blocks direct HTTP access to /db/ (SQLite file + schema) and /lib/
 * (shared logic). Everything else falls through to normal serving.
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (preg_match('#/(db|lib)/#', $uri)) {
    http_response_code(403);
    header('Content-Type: text/plain');
    echo "403 Forbidden";
    return true;
}

return false;
