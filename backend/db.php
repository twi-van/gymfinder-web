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
        gf_db_ensure_trainer_link_restrict($pdo);
    }
    gf_db_ensure_password_reset_otps($pdo);
    gf_db_ensure_category_labels_vi($pdo);
    gf_db_ensure_premium_exclusive($pdo);
    $GLOBALS['GF_PDO'] = $pdo;
}

/** Keep existing SQLite installs aligned with the non-cascading N-N link policy. */
function gf_db_ensure_trainer_link_restrict(PDO $pdo): void
{
    $tables = ['trainer_specialties', 'gym_trainers'];
    $findCascadeTables = static function () use ($pdo, $tables): array {
        $rebuild = [];
        foreach ($tables as $table) {
            $exists = $pdo->prepare("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = ?");
            $exists->execute([$table]);
            $tableExists = (bool) $exists->fetchColumn();
            $exists->closeCursor();
            if (!$tableExists) {
                continue;
            }
            foreach ($pdo->query('PRAGMA foreign_key_list(' . $table . ')')->fetchAll() as $foreignKey) {
                if (strtoupper((string) $foreignKey['on_delete']) === 'CASCADE') {
                    $rebuild[$table] = true;
                    break;
                }
            }
        }
        return $rebuild;
    };
    $rebuild = $findCascadeTables();
    if (!$rebuild) {
        return;
    }

    // SQLite cannot alter an existing FK action, so rebuild only the affected join tables.
    $pdo->exec('PRAGMA busy_timeout = 5000');
    $pdo->exec('PRAGMA foreign_keys = OFF');
    $transactionStarted = false;
    try {
        // Serialize simultaneous first requests so only one connection rebuilds the tables.
        $pdo->exec('BEGIN IMMEDIATE');
        $transactionStarted = true;
        // Another request may have completed the migration while this one waited for the lock.
        $rebuild = $findCascadeTables();
        if (!$rebuild) {
            $pdo->exec('COMMIT');
            $transactionStarted = false;
            return;
        }
        if (isset($rebuild['trainer_specialties'])) {
            $pdo->exec('CREATE TABLE trainer_specialties_restrict (trainer_id INTEGER NOT NULL, specialty_id INTEGER NOT NULL, PRIMARY KEY (trainer_id, specialty_id), FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE RESTRICT, FOREIGN KEY (specialty_id) REFERENCES specialties(id) ON DELETE RESTRICT)');
            $pdo->exec('INSERT INTO trainer_specialties_restrict (trainer_id, specialty_id) SELECT trainer_id, specialty_id FROM trainer_specialties');
            $pdo->exec('DROP TABLE trainer_specialties');
            $pdo->exec('ALTER TABLE trainer_specialties_restrict RENAME TO trainer_specialties');
        }
        if (isset($rebuild['gym_trainers'])) {
            $pdo->exec('CREATE TABLE gym_trainers_restrict (gym_id INTEGER NOT NULL, trainer_id INTEGER NOT NULL, PRIMARY KEY (gym_id, trainer_id), FOREIGN KEY (gym_id) REFERENCES gyms(id) ON DELETE RESTRICT, FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE RESTRICT)');
            $pdo->exec('INSERT INTO gym_trainers_restrict (gym_id, trainer_id) SELECT gym_id, trainer_id FROM gym_trainers');
            $pdo->exec('DROP TABLE gym_trainers');
            $pdo->exec('ALTER TABLE gym_trainers_restrict RENAME TO gym_trainers');
        }
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_ts_specialty ON trainer_specialties(specialty_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_gt_trainer ON gym_trainers(trainer_id)');
        $pdo->exec('COMMIT');
        $transactionStarted = false;
    } catch (Throwable $e) {
        if ($transactionStarted) {
            $pdo->exec('ROLLBACK');
        }
        throw $e;
    } finally {
        $pdo->exec('PRAGMA foreign_keys = ON');
    }
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

/** Đồng bộ tên danh mục tiếng Việt (DB cũ có thể còn Fitness/Premium). */
function gf_db_ensure_category_labels_vi(PDO $pdo): void
{
    try {
        $map = [
            'fitness' => 'Gym phổ thông',
            'premium' => 'Gym cao cấp',
        ];
        $st = $pdo->prepare('UPDATE categories SET name = ? WHERE slug = ? AND name <> ?');
        foreach ($map as $slug => $name) {
            $st->execute([$name, $slug, $name]);
        }
    } catch (Throwable $e) {
        // Bảng categories chưa sẵn sàng — bỏ qua
    }
}

/** Gym cao cấp không đồng thời mang danh mục Gym phổ thông (seed cũ từng gán cả hai). */
function gf_db_ensure_premium_exclusive(PDO $pdo): void
{
    try {
        $fitnessId = (int) $pdo->query("SELECT id FROM categories WHERE slug = 'fitness' LIMIT 1")->fetchColumn();
        $premiumId = (int) $pdo->query("SELECT id FROM categories WHERE slug = 'premium' LIMIT 1")->fetchColumn();
        if ($fitnessId < 1 || $premiumId < 1) {
            return;
        }
        $st = $pdo->prepare(
            'DELETE FROM gym_categories
             WHERE category_id = ?
               AND gym_id IN (
                 SELECT gym_id FROM (
                   SELECT gym_id FROM gym_categories WHERE category_id = ?
                 ) AS premium_gyms
               )'
        );
        $st->execute([$fitnessId, $premiumId]);
    } catch (Throwable $e) {
        // Bảng chưa sẵn sàng — bỏ qua
    }
}

function gf_pdo(): PDO
{
    return $GLOBALS['GF_PDO'];
}

function gf_db(): PDO
{
    return gf_pdo();
}
