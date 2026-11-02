<?php
declare(strict_types=1);

function gf_db_init(array $config): void
{
    $driver = $config['driver'] ?? 'mysql';
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
        $pdo->exec('PRAGMA busy_timeout = 5000');
        $pdo->exec('PRAGMA foreign_keys = ON');
        if ($needSeed) {
            require_once __DIR__ . '/sqlite_install.php';
            gf_sqlite_install($pdo);
        }
        gf_db_ensure_sqlite_user_schema($pdo);
        gf_db_ensure_trainer_link_restrict($pdo);
        gf_db_ensure_trainer_experience_range($pdo);
    }
    gf_db_backfill_trainer_links($pdo);
    gf_db_ensure_demo_gym_gallery($pdo);
    gf_db_ensure_demo_trainer_avatars($pdo);
    gf_db_ensure_password_reset_otps($pdo);
    gf_db_ensure_category_labels_vi($pdo);
    gf_db_ensure_premium_exclusive($pdo);
    $GLOBALS['GF_PDO'] = $pdo;
}

/** Bring seeded demo gyms up to the documented cover/gallery image counts. */
function gf_db_ensure_demo_gym_gallery(PDO $pdo): void
{
    $galleryPhotos = function_exists('gf_demo_gym_photos') ? gf_demo_gym_photos() : [];
    $coverPhotos = function_exists('gf_demo_gym_cover_photos') ? gf_demo_gym_cover_photos() : [];
    if (!$galleryPhotos || !$coverPhotos) {
        return;
    }

    try {
        $gyms = $pdo->query("SELECT g.id, g.email, g.cover_image_url, g.is_featured
            FROM gyms g
            ORDER BY g.id")->fetchAll(PDO::FETCH_ASSOC);
        if (!$gyms) {
            return;
        }

        $sqlite = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
        $insert = $pdo->prepare($sqlite
            ? 'INSERT OR IGNORE INTO gym_images (gym_id, image_url, caption, sort_order) VALUES (?,?,?,?)'
            : 'INSERT IGNORE INTO gym_images (gym_id, image_url, caption, sort_order) VALUES (?,?,?,?)');

        $deleteImg = $pdo->prepare('DELETE FROM gym_images WHERE id=?');
        $updateCover = $pdo->prepare('UPDATE gyms SET cover_image_url=? WHERE id=?');
        $current = $pdo->prepare('SELECT id, image_url, caption, sort_order FROM gym_images WHERE gym_id=? ORDER BY sort_order, id');

        $poolCount = count($galleryPhotos);
        $usedCovers = [];

        foreach ($gyms as $gym) {
            $gymId = (int) $gym['id'];
            $email = (string) ($gym['email'] ?? '');
            $target = (int) ($gym['is_featured'] ?? 0) === 1 ? 6 : 5;
            $cover = (string) ($gym['cover_image_url'] ?? '');

            // 1. Xác định cover chuẩn cho gym: mỗi gym 1 ảnh bìa khác nhau
            $demoNum = 0;
            if (preg_match('/contact\.gym(\d+)@gymfinder\.test/', $email, $m)) {
                $demoNum = (int) $m[1];
            }

            if ($demoNum >= 1 && $demoNum <= count($coverPhotos)) {
                $targetCover = $coverPhotos[$demoNum - 1];
            } else {
                $idx = ($gymId - 1) % count($coverPhotos);
                $targetCover = $coverPhotos[$idx];
            }

            // Nếu gym chưa có cover, cover là 404, hoặc bị trùng cover với gym khác đã duyệt
            $isBrokenCover = str_contains($cover, '1577221084712-45b0449d2d3f');
            $isDemoMatch = $demoNum > 0;
            $isUploadCover = preg_match('#^/?uploads/#i', $cover);
            $needsUpdate = $isBrokenCover || ($isDemoMatch && !$isUploadCover && $cover !== $targetCover) || (!$isUploadCover && empty($cover));

            if ($needsUpdate) {
                $cover = $targetCover;
                $updateCover->execute([$cover, $gymId]);
            }
            $usedCovers[] = $cover;

            // Lấy ID ảnh của cover để kiểm tra không trùng trong bộ sưu tập
            $coverPhotoId = preg_match('#/photo-([^?]+)#', $cover, $coverMatch) ? $coverMatch[1] : '';

            // 2. Xử lý bộ sưu tập ảnh (gym_images):
            // - Giữ các ảnh cũ
            // - Chỉ bỏ ảnh trùng lặp (trùng với cover của gym này hoặc trùng ảnh khác trong cùng 1 gym)
            // - Bỏ ảnh lỗi 404
            $current->execute([$gymId]);
            $rows = $current->fetchAll(PDO::FETCH_ASSOC);

            $keptUrls = [];
            $keptPhotoIds = [];

            foreach ($rows as $row) {
                $rowId = (int) $row['id'];
                $url = (string) $row['image_url'];
                $photoId = preg_match('#/photo-([^?]+)#', $url, $pm) ? $pm[1] : $url;

                // Ảnh bị lỗi 404
                if (str_contains($url, '1577221084712-45b0449d2d3f')) {
                    $deleteImg->execute([$rowId]);
                    continue;
                }

                // Trùng với cover_image của chính gym này
                if ($coverPhotoId !== '' && $photoId === $coverPhotoId) {
                    $deleteImg->execute([$rowId]);
                    continue;
                }

                // Trùng với một ảnh khác đã có trong bộ sưu tập của gym này
                if (in_array($photoId, $keptPhotoIds, true) || in_array($url, $keptUrls, true)) {
                    $deleteImg->execute([$rowId]);
                    continue;
                }

                $keptUrls[] = $url;
                $keptPhotoIds[] = $photoId;
            }

            // Nếu là demo gym và chưa có ảnh gốc 'Khu tập luyện đa năng', thêm lại để giữ ảnh cũ
            if ($demoNum >= 1 && $demoNum <= 64 && function_exists('gf_demo_gym_initial_gallery_photo')) {
                $initialPhoto = gf_demo_gym_initial_gallery_photo($demoNum);
                $initialPhotoId = preg_match('#/photo-([^?]+)#', $initialPhoto, $ipm) ? $ipm[1] : '';
                if ($initialPhotoId !== $coverPhotoId && !in_array($initialPhotoId, $keptPhotoIds, true) && !in_array($initialPhoto, $keptUrls, true)) {
                    if (count($keptUrls) < $target || count($rows) === 0) {
                        $sortOrder = count($keptUrls) + 1;
                        $insert->execute([$gymId, $initialPhoto, 'Khu tập luyện đa năng', $sortOrder]);
                        $keptUrls[] = $initialPhoto;
                        $keptPhotoIds[] = $initialPhotoId;
                    }
                }
            }

            // 3. Thêm các ảnh khác cho đa dạng nếu còn thiếu (< target)
            if (count($keptUrls) < $target) {
                $start = (($gymId - 1) * 13) % $poolCount;
                for ($step = 0; $step < $poolCount && count($keptUrls) < $target; $step++) {
                    $photo = $galleryPhotos[($start + $step * 7) % $poolCount];
                    $photoId = preg_match('#/photo-([^?]+)#', $photo, $ppm) ? $ppm[1] : '';

                    if ($photoId !== '' && $photoId === $coverPhotoId) {
                        continue;
                    }
                    if (in_array($photo, $keptUrls, true) || in_array($photoId, $keptPhotoIds, true)) {
                        continue;
                    }

                    $sortOrder = count($keptUrls) + 1;
                    $insert->execute([$gymId, $photo, 'Không gian phòng tập', $sortOrder]);
                    $keptUrls[] = $photo;
                    $keptPhotoIds[] = $photoId;
                }
            }
        }
    } catch (Throwable $e) {
        error_log('GYMFINDER demo gallery backfill failed: ' . $e->getMessage());
    }
}

/** Correct seeded trainer portraits to a distinct, gender-matched headshot per demo profile. */
function gf_db_ensure_demo_trainer_avatars(PDO $pdo): void
{
    // The seed data has no trainer age field, so do not invent exact ages here.
    // RandomUser portraits are used as adult-style profile headshots; trainer experience
    // remains the only age-adjacent information in the current schema.
    $femaleIds = [2, 4, 5, 7, 9, 12, 15, 18, 21, 23, 26, 29];
    $legacyAvatars = [
        'https://images.unsplash.com/photo-1567013127542-490d757e51fc?auto=format&fit=crop&w=400&q=80',
        'https://images.unsplash.com/photo-1594381898411-846e7d193883?auto=format&fit=crop&w=400&q=80',
        'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=400&q=80',
        'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=400&q=80',
        'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=400&q=80',
        'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=400&q=80',
    ];
    // Keep the three portraits explicitly requested by the user. Use professional
    // trainer headshots for the remaining demo profiles, matching the seeded names.
    $portraitOverrides = [
        1 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2502497.webp?update_time=2025-11-27+03%3A30%3A05.737',
        2 => 'https://images.squarespace-cdn.com/content/v1/62e39313a3615b384cc19035/52a6f1e4-8e7c-40a3-b0c6-14a8bb0c05a2/xiu%2Bting.jpg',
        3 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2300326.webp?update_time=2025-06-21+08%3A48%3A01.600',
        4 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/700096.webp?update_time=2025-06-14+03%3A31%3A55.310',
        5 => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=400&q=80',
        6 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2405184.webp?update_time=2025-06-14+03%3A31%3A55.120',
        7 => 'https://tsquaredlab.com/images/trainers/haz.webp',
        8 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2101491.webp?update_time=2025-05-31+03%3A30%3A36.813',
        9 => 'https://static.wixstatic.com/media/3712cb_75431a0dff05471281ca426eefc1f808~mv2.jpg/v1/fill/w_980%2Ch_1307%2Cal_c%2Cq_85%2Cusm_0.66_1.00_0.01/3712cb_75431a0dff05471281ca426eefc1f808~mv2.jpg',
        10 => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=400&q=80',
        11 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2103599.webp?update_time=2025-05-29+03%3A31%3A37.583',
        12 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2405272.webp?update_time=2025-06-21+08%3A48%3A02.017',
        13 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2002233.webp?update_time=2025-07-31+03%3A30%3A07.820',
        14 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2502551.webp?update_time=2025-08-05+03%3A30%3A09.267',
        15 => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=400&q=80',
        16 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/790154.webp?update_time=2025-11-06+03%3A30%3A16.210',
        17 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2500506.webp?update_time=2025-05-31+03%3A30%3A36.857',
        18 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2402622.webp?update_time=2025-06-19+22%3A05%3A07.700',
        19 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2504511.webp?update_time=2026-02-15+03%3A30%3A11.880',
        20 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2302959.webp?update_time=2025-06-05+03%3A33%3A39.200',
        21 => 'https://images.squarespace-cdn.com/content/v1/588754e7bebafbcb2d477cbf/1759978920243-J0GHN0IZ0LQKLMY4Z3WN/RACH1.jpg',
        22 => 'https://www.workband.com.tw/store_image/forcefitness/P1174540170056.webp',
        23 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/370451.webp?update_time=2026-04-22+03%3A30%3A08.133',
        24 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2500840.webp?update_time=2026-04-22+03%3A30%3A08.127',
        25 => 'https://pure360-public.s3.ap-southeast-1.amazonaws.com/teacher_image_dev/1900.jpg?t=1663933548000',
        26 => 'https://stringssg-assets.s3-ap-southeast-1.amazonaws.com/static/personal-trainer/generated/Christabelle.jpeg',
        27 => 'https://images.squarespace-cdn.com/content/v1/63d9d5adb0b1063f7fa463a2/40365611-db3b-4e8c-9566-f14d5d3e2e6f/copy_AF8E7F50-5222-492E-A210-BFB5553B71CF.JPEG',
        28 => 'https://unrealpt.sg/cdn/shop/files/Hwa_Yang_Soh_Senior_Trainer.jpg?v=1726637029&width=2000',
        29 => 'https://images.squarespace-cdn.com/content/v1/66668442d380464892620714/97994512-76a2-433b-8e6c-dcc2dbc6d6e3/IMG_8221.jpg',
        30 => 'https://www.stringssg.com/_next/image?q=75&url=https%3A%2F%2Fstringssg-assets.s3.ap-southeast-1.amazonaws.com%2Fstatic%2Fpersonal-trainer%2Fgenerated%2FGerald.jpeg&w=1920',
    ];
    try {
        $legacyPlaceholders = implode(',', array_fill(0, count($legacyAvatars), '?'));
        $update = $pdo->prepare(
            "UPDATE trainers SET avatar_url=? WHERE email=? AND (
                avatar_url IN ($legacyPlaceholders)
                OR avatar_url LIKE 'https://randomuser.me/api/portraits/men/%'
                OR avatar_url LIKE 'https://randomuser.me/api/portraits/women/%'
            )"
        );
        foreach (range(1, 30) as $trainerId) {
            $gender = in_array($trainerId, $femaleIds, true) ? 'women' : 'men';
            $avatar = $portraitOverrides[$trainerId] ?? "https://randomuser.me/api/portraits/{$gender}/{$trainerId}.jpg";
            $update->execute(array_merge([$avatar, "trainer{$trainerId}@gymfinder.test"], $legacyAvatars));
        }
    } catch (Throwable $e) {
        error_log('GYMFINDER demo trainer avatar update failed: ' . $e->getMessage());
    }
}

/** Remove the retired goal field and safely enforce the required email on SQLite. */
function gf_db_ensure_sqlite_user_schema(PDO $pdo): void
{
    $columns = $pdo->query('PRAGMA table_info(users)')->fetchAll();
    $byName = [];
    foreach ($columns as $column) {
        $byName[(string) $column['name']] = $column;
    }

    $hasMissingEmail = (bool) $pdo->query("SELECT 1 FROM users WHERE email IS NULL OR trim(email) = '' LIMIT 1")->fetchColumn();
    $hasGoal = isset($byName['goal']);
    $emailAlreadyRequired = !empty($byName['email']['notnull']);
    if (!$hasGoal && $hasMissingEmail) {
        // The only remaining action requires the account owner to provide a real
        // address. Install guards once and leave the existing account untouched.
        $pdo->exec("CREATE TRIGGER IF NOT EXISTS trg_users_email_required_insert
            BEFORE INSERT ON users
            WHEN NEW.email IS NULL OR trim(NEW.email) = ''
            BEGIN SELECT RAISE(ABORT, 'users.email is required'); END");
        $pdo->exec("CREATE TRIGGER IF NOT EXISTS trg_users_email_required_update
            BEFORE UPDATE OF email ON users
            WHEN NEW.email IS NULL OR trim(NEW.email) = ''
            BEGIN SELECT RAISE(ABORT, 'users.email is required'); END");
        return;
    }
    if (!$hasGoal && !$hasMissingEmail && $emailAlreadyRequired) {
        return;
    }

    // Rebuild instead of ALTER DROP COLUMN: older XAMPP SQLite builds lack that
    // syntax. If a legacy account lacks email, preserve it and keep the column
    // nullable temporarily; once a real email is supplied, the next startup makes
    // the schema-level NOT NULL guarantee explicit. Never invent account data.
    $pdo->exec('PRAGMA busy_timeout = 5000');
    $pdo->exec('PRAGMA foreign_keys = OFF');
    $transactionStarted = false;
    try {
        $pdo->exec('BEGIN IMMEDIATE');
        $transactionStarted = true;
        $hasMissingEmail = (bool) $pdo->query("SELECT 1 FROM users WHERE email IS NULL OR trim(email) = '' LIMIT 1")->fetchColumn();
        $emailRequired = !$hasMissingEmail;
        $emailDefinition = $emailRequired ? 'TEXT NOT NULL UNIQUE' : 'TEXT UNIQUE';
        $pdo->exec("CREATE TABLE users_schema_migration (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            email {$emailDefinition},
            phone TEXT UNIQUE,
            password_hash TEXT NOT NULL,
            avatar_url TEXT,
            role TEXT NOT NULL DEFAULT 'user',
            status TEXT NOT NULL DEFAULT 'active',
            last_login_at TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec('INSERT INTO users_schema_migration (id, full_name, email, phone, password_hash, avatar_url, role, status, last_login_at, created_at, updated_at) SELECT id, full_name, email, phone, password_hash, avatar_url, role, status, last_login_at, created_at, updated_at FROM users');
        $pdo->exec('DROP TABLE users');
        $pdo->exec('ALTER TABLE users_schema_migration RENAME TO users');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_users_role_status ON users(role, status)');
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

    if (!$emailRequired) {
        // Keep legacy rows intact, while making all future user writes require email.
        $pdo->exec("CREATE TRIGGER IF NOT EXISTS trg_users_email_required_insert
            BEFORE INSERT ON users
            WHEN NEW.email IS NULL OR trim(NEW.email) = ''
            BEGIN SELECT RAISE(ABORT, 'users.email is required'); END");
        $pdo->exec("CREATE TRIGGER IF NOT EXISTS trg_users_email_required_update
            BEFORE UPDATE OF email ON users
            WHEN NEW.email IS NULL OR trim(NEW.email) = ''
            BEGIN SELECT RAISE(ABORT, 'users.email is required'); END");
    }
}

/** Enforce the SQLite spec range for existing databases without rewriting trainer data. */
function gf_db_ensure_trainer_experience_range(PDO $pdo): void
{
    $existing = $pdo->query("SELECT name FROM sqlite_master WHERE type='trigger' AND name IN ('trg_trainers_exp_insert','trg_trainers_exp_update')")
        ->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('trg_trainers_exp_insert', $existing, true)) {
        $pdo->exec("CREATE TRIGGER IF NOT EXISTS trg_trainers_exp_insert
            BEFORE INSERT ON trainers
            WHEN NEW.years_experience < 0 OR NEW.years_experience > 60
            BEGIN SELECT RAISE(ABORT, 'years_experience must be between 0 and 60'); END");
    }
    if (!in_array('trg_trainers_exp_update', $existing, true)) {
        $pdo->exec("CREATE TRIGGER IF NOT EXISTS trg_trainers_exp_update
            BEFORE UPDATE OF years_experience ON trainers
            WHEN NEW.years_experience < 0 OR NEW.years_experience > 60
            BEGIN SELECT RAISE(ABORT, 'years_experience must be between 0 and 60'); END");
    }
}

/** Idempotently move legacy trainer links into N-N tables when those links are missing. */
function gf_db_backfill_trainer_links(PDO $pdo): void
{
    try {
        $sqlite = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
        $ignore = $sqlite ? 'INSERT OR IGNORE' : 'INSERT IGNORE';
        $hasLegacySpecialty = $pdo->query("SELECT 1 FROM trainers t
            WHERE t.specialty_id IS NOT NULL
              AND EXISTS (SELECT 1 FROM specialties s WHERE s.id=t.specialty_id)
              AND NOT EXISTS (SELECT 1 FROM trainer_specialties ts WHERE ts.trainer_id=t.id)
            LIMIT 1")->fetchColumn();
        if ($hasLegacySpecialty) {
            $pdo->exec("{$ignore} INTO trainer_specialties (trainer_id, specialty_id)
                SELECT t.id, t.specialty_id FROM trainers t
                WHERE t.specialty_id IS NOT NULL
                  AND EXISTS (SELECT 1 FROM specialties s WHERE s.id=t.specialty_id)
                  AND NOT EXISTS (SELECT 1 FROM trainer_specialties ts WHERE ts.trainer_id=t.id)");
        }

        $hasLegacyGym = $pdo->query("SELECT 1 FROM trainers t
            WHERE t.gym_id IS NOT NULL
              AND EXISTS (SELECT 1 FROM gyms g WHERE g.id=t.gym_id)
              AND NOT EXISTS (SELECT 1 FROM gym_trainers gt WHERE gt.trainer_id=t.id)
            LIMIT 1")->fetchColumn();
        if ($hasLegacyGym) {
            $pdo->exec("{$ignore} INTO gym_trainers (gym_id, trainer_id)
                SELECT t.gym_id, t.id FROM trainers t
                WHERE t.gym_id IS NOT NULL
                  AND EXISTS (SELECT 1 FROM gyms g WHERE g.id=t.gym_id)
                  AND NOT EXISTS (SELECT 1 FROM gym_trainers gt WHERE gt.trainer_id=t.id)");
        }
    } catch (Throwable $e) {
        error_log('GYMFINDER trainer link backfill failed: ' . $e->getMessage());
    }
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
