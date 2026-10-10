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
    $GLOBALS['GF_PDO'] = $pdo;
}

function gf_pdo(): PDO
{
    return $GLOBALS['GF_PDO'];
}

function gf_db(): PDO
{
    return gf_pdo();
}
