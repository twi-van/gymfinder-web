<?php
declare(strict_types=1);

// =============================================================================
// BOOTSTRAP — Khởi động ứng dụng, load tất cả dependencies
// =============================================================================

if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

$config = require __DIR__ . '/config.php';
$GLOBALS['GF_CONFIG'] = $config;

// Core
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/ai_client.php';
require_once dirname(__DIR__) . '/includes/pagination.php';

// Repositories (data layer — truy vấn DB)
require_once __DIR__ . '/repositories/common_repository.php';
require_once __DIR__ . '/repositories/gym_repository.php';
require_once __DIR__ . '/repositories/trainer_repository.php';
require_once __DIR__ . '/repositories/review_repository.php';
require_once __DIR__ . '/repositories/favorite_repository.php';
require_once __DIR__ . '/repositories/ai_chat_repository.php';

// Resources (business logic layer — xử lý API)
require_once __DIR__ . '/resources/common.php';
require_once __DIR__ . '/resources/review_resource.php';
require_once __DIR__ . '/resources/upload_resource.php';
require_once __DIR__ . '/resources/admin_resource.php';

// Khởi tạo DB, web root và CSRF token
try {
    gf_db_init($config);
} catch (Throwable $e) {
    error_log('GYMFINDER DB init failed: ' . $e->getMessage());
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    $safe = htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    echo '<!DOCTYPE html><html lang="vi"><head><meta charset="utf-8"><title>GYMFINDER</title></head><body>';
    echo '<h1>Chưa kết nối được database</h1>';
    echo '<p>Kiểm tra Variables <code>GF_DB_*</code> trên Railway (Reference MySQL) và đã import schema/seed chưa.</p>';
    echo '<pre style="white-space:pre-wrap">' . $safe . '</pre>';
    echo '</body></html>';
    exit;
}
gf_boot_web_root();
gf_csrf_token();

// Bảo vệ CSRF trên các POST request từ web (không phải API)
$path  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isApi = str_starts_with($path, '/api');
if (!$isApi && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    gf_require_csrf();
}

// Admin không dùng portal công khai (trang chủ / gym / PT / user…)
gf_enforce_admin_portal_only();
