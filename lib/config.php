<?php
/**
 * Solicity Bank — shared bootstrap. Every entry-point script requires
 * this before doing anything else.
 */

if (!defined('SOLICITY_ENTRY')) {
    define('SOLICITY_ENTRY', true);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['path' => '/']);
    session_start();
}

define('SOLICITY_ROOT', dirname(__DIR__));

require_once SOLICITY_ROOT . '/db/connect.php';
require_once SOLICITY_ROOT . '/lib/helpers.php';
require_once SOLICITY_ROOT . '/lib/auth.php';
