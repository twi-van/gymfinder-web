<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';

$pending = $_SESSION['google_oauth'] ?? [];
$from = is_array($pending) && (($pending['from'] ?? '') === 'register') ? 'register' : 'login';
$next = is_array($pending) ? (string) ($pending['next'] ?? '') : '';

if (isset($_GET['error'])) {
    $code = (string) $_GET['error'] === 'access_denied' ? 'google_denied' : 'google_failed';
    gf_google_fail($code, $from, $next);
}

gf_google_finish((string) ($_GET['code'] ?? ''), (string) ($_GET['state'] ?? ''));
