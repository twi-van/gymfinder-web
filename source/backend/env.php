<?php
declare(strict_types=1);

/**
 * Load biến từ file .env (root dự án) vào getenv / $_ENV / $_SERVER.
 * Không ghi đè biến môi trường đã có (Railway/Docker Variables thắng).
 * Không cần Composer / phpdotenv.
 */
function gf_load_dotenv(?string $path = null): void
{
    $path = $path ?? dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if ($name === '' || !preg_match('/^[A-Z_][A-Z0-9_]*$/i', $name)) {
            continue;
        }

        // Bỏ quote bao quanh
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        $existing = getenv($name);
        if ($existing !== false && $existing !== '') {
            continue;
        }

        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}
