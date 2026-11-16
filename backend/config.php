<?php
declare(strict_types=1);

/**
 * GYMFINDER – cấu hình
 * Mặc định local/demo: MySQL qua XAMPP (schema/seed: database/schema.sql + seed.sql)
 * Tuỳ chọn: SQLite — đặt GF_DB_DRIVER=sqlite (tự tạo database/gymfinder.sqlite)
 *
 * Nguồn cấu hình (ưu tiên cao → thấp):
 *   1) Env hệ thống / Railway Variables
 *   2) File `.env` (root dự án) — copy từ `.env.example`
 *   3) Giá trị mặc định bên dưới
 */
require_once __DIR__ . '/env.php';
gf_load_dotenv();

$env = static function (string $name, mixed $default = ''): mixed {
    $value = getenv($name);
    return $value !== false && $value !== '' ? $value : $default;
};
// Railway / paste often wraps values in quotes; strip for SMTP string fields.
$smtpStr = static function (string $name, string $default = '') use ($env): string {
    return trim((string) $env($name, $default), " \t\"'");
};

return [
    'driver' => $env('GF_DB_DRIVER', 'mysql'),
    'sqlite_path' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'gymfinder.sqlite',
    'mysql' => [
        'host' => (string) $env('GF_DB_HOST', '127.0.0.1'),
        'port' => (string) $env('GF_DB_PORT', '3306'),
        'database' => (string) $env('GF_DB_NAME', 'gymfinder'),
        'username' => (string) $env('GF_DB_USER', 'root'),
        'password' => (string) $env('GF_DB_PASS', ''),
        'charset' => 'utf8mb4',
    ],
    'smtp' => [
        'host' => $smtpStr('GF_SMTP_HOST'),
        'port' => (int) $env('GF_SMTP_PORT', 587),
        'encryption' => strtolower($smtpStr('GF_SMTP_ENCRYPTION', 'tls')),
        'username' => $smtpStr('GF_SMTP_USERNAME'),
        // Google displays App Passwords in four-character groups; strip pasted spaces.
        'password' => preg_replace('/\s+/', '', $smtpStr('GF_SMTP_PASSWORD')) ?? '',
        'from_email' => $smtpStr('GF_SMTP_FROM_EMAIL'),
        'from_name' => $smtpStr('GF_SMTP_FROM_NAME', 'GYMFINDER') ?: 'GYMFINDER',
        'demo_otp' => filter_var($env('GF_SMTP_DEMO_OTP', false), FILTER_VALIDATE_BOOL),
    ],
    'ai' => [
        'provider' => strtolower((string) $env('GF_AI_PROVIDER', 'groq')),
        'api_key' => (string) $env('GF_AI_API_KEY', ''),
        'model' => (string) $env('GF_AI_MODEL', 'qwen/qwen3.8-27b'),
        'base_url' => (string) $env('GF_AI_BASE_URL', ''),
        'enabled' => filter_var($env('GF_AI_ENABLED', true), FILTER_VALIDATE_BOOL),
    ],
    'google' => [
        'client_id' => (string) $env('GF_GOOGLE_CLIENT_ID', ''),
        'client_secret' => (string) $env('GF_GOOGLE_CLIENT_SECRET', ''),
    ],
];
