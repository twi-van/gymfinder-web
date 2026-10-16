<?php
declare(strict_types=1);

function gf_db_init(array $config): void
{
    $driver = $config['driver'] ?? 'sqlite';
    if ($driver === 'mysql') {
        $m = $config['mysql'];
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $m['host'],
            $m['port'],
            $m['database'],
            $m['charset']
        );
        $pdo = new PDO($dsn, $m['username'], $m['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec("SET time_zone = '+00:00'");
        $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    } else {
        $path = $config['sqlite_path'];
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $needSeed = !file_exists($path) || filesize($path) < 100;
        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        if ($needSeed) {
            require_once __DIR__ . '/sqlite_install.php';
            gf_sqlite_install($pdo);
        }
    }
    gf_db_ensure_password_reset_otps($pdo);
    $GLOBALS['GF_PDO'] = $pdo;
}

/** Bổ sung bảng OTP quên mật khẩu cho CSDL đã tạo trước đó. */
function gf_db_ensure_password_reset_otps(PDO $pdo): void
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ($driver === 'sqlite') {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS password_reset_otps (
              id INTEGER PRIMARY KEY AUTOINCREMENT,
              user_id INTEGER NOT NULL,
              otp_hash TEXT NOT NULL,
              attempts INTEGER NOT NULL DEFAULT 0,
              expires_at TEXT NOT NULL,
              verified_at TEXT,
              consumed_at TEXT,
              created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )'
        );
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_pwreset_user ON password_reset_otps(user_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_pwreset_expires ON password_reset_otps(expires_at)');
        return;
    }
    // MySQL: tạo nếu chưa có (idempotent)
    $st = $pdo->query("SHOW TABLES LIKE 'password_reset_otps'");
    if ($st && $st->fetch()) {
        return;
    }
    $pdo->exec(
        "CREATE TABLE password_reset_otps (
          id INT UNSIGNED NOT NULL AUTO_INCREMENT,
          user_id INT UNSIGNED NOT NULL,
          otp_hash VARCHAR(255) NOT NULL,
          attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
          expires_at DATETIME NOT NULL,
          verified_at DATETIME NULL,
          consumed_at DATETIME NULL,
          created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (id),
          KEY idx_pwreset_user (user_id),
          KEY idx_pwreset_expires (expires_at),
          CONSTRAINT fk_pwreset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function gf_pdo(): PDO
{
    return $GLOBALS['GF_PDO'];
}

function gf_db(): PDO
{
    return gf_pdo();
}
