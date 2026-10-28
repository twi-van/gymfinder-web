<?php
declare(strict_types=1);

/**
 * GYMFINDER – cấu hình (Sprint 0 / TV2 Backend + Database)
 * Mặc định local/demo: MySQL qua XAMPP (schema/seed: database/schema.sql + seed.sql)
 * Tuỳ chọn: SQLite — đặt GF_DB_DRIVER=sqlite (tự tạo database/gymfinder.sqlite)
 */
$smtpLocal = [];
$smtpLocalFile = __DIR__ . DIRECTORY_SEPARATOR . 'smtp.local.php';
if (is_file($smtpLocalFile)) {
    $loadedSmtp = require $smtpLocalFile;
    if (is_array($loadedSmtp)) {
        $smtpLocal = $loadedSmtp;
    }
}

$aiLocal = [];
$aiLocalFile = __DIR__ . DIRECTORY_SEPARATOR . 'ai.local.php';
if (is_file($aiLocalFile)) {
    $loadedAi = require $aiLocalFile;
    if (is_array($loadedAi)) {
        $aiLocal = $loadedAi;
    }
}

$smtpValue = static function (string $envName, string $key, mixed $default = '') use ($smtpLocal): mixed {
    $value = getenv($envName);
    return $value !== false && $value !== '' ? $value : ($smtpLocal[$key] ?? $default);
};

$aiValue = static function (string $envName, string $key, mixed $default = '') use ($aiLocal): mixed {
    $value = getenv($envName);
    return $value !== false && $value !== '' ? $value : ($aiLocal[$key] ?? $default);
};

return [
    'driver' => getenv('GF_DB_DRIVER') ?: 'mysql',
    'sqlite_path' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'gymfinder.sqlite',
    'mysql' => [
        'host' => getenv('GF_DB_HOST') ?: '127.0.0.1',
        'port' => getenv('GF_DB_PORT') ?: '3306',
        'database' => getenv('GF_DB_NAME') ?: 'gymfinder',
        'username' => getenv('GF_DB_USER') ?: 'root',
        'password' => getenv('GF_DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'smtp' => [
        'host' => (string) $smtpValue('GF_SMTP_HOST', 'host'),
        'port' => (int) $smtpValue('GF_SMTP_PORT', 'port', 587),
        'encryption' => strtolower((string) $smtpValue('GF_SMTP_ENCRYPTION', 'encryption', 'tls')),
        'username' => (string) $smtpValue('GF_SMTP_USERNAME', 'username'),
        // Google displays App Passwords in four-character groups; strip pasted spaces.
        'password' => preg_replace('/\s+/', '', (string) $smtpValue('GF_SMTP_PASSWORD', 'password')) ?? '',
        'from_email' => (string) $smtpValue('GF_SMTP_FROM_EMAIL', 'from_email'),
        'from_name' => (string) $smtpValue('GF_SMTP_FROM_NAME', 'from_name', 'GYMFINDER'),
        'demo_otp' => filter_var($smtpValue('GF_SMTP_DEMO_OTP', 'demo_otp', false), FILTER_VALIDATE_BOOL),
    ],
    'ai' => [
        'provider' => strtolower((string) $aiValue('GF_AI_PROVIDER', 'provider', 'groq')),
        'api_key' => (string) $aiValue('GF_AI_API_KEY', 'api_key'),
        'model' => (string) $aiValue('GF_AI_MODEL', 'model', 'qwen/qwen3.8-27b'),
        'base_url' => (string) $aiValue('GF_AI_BASE_URL', 'base_url'),
        'enabled' => filter_var($aiValue('GF_AI_ENABLED', 'enabled', true), FILTER_VALIDATE_BOOL),
    ],
];
