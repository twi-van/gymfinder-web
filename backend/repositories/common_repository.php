<?php
declare(strict_types=1);

// =============================================================================
// COMMON REPOSITORY — dùng chung cho nhiều loại resource
// =============================================================================

/**
 * Lấy điều kiện WHERE cho gym public (chỉ hiển thị gym active).
 */
function gf_gym_public_where(string $alias = 'g'): string
{
    return "$alias.status = 'active'";
}

/**
 * Kiểm tra ID có tồn tại trong bảng không.
 */
function gf_exists_id(string $table, int $id): bool
{
    if (!in_array($table, ['districts', 'categories', 'amenities', 'specialties', 'gyms', 'trainers', 'users'], true)) {
        return false;
    }
    $st = gf_pdo()->prepare("SELECT 1 FROM {$table} WHERE id=?");
    $st->execute([$id]);
    return (bool) $st->fetchColumn();
}

/**
 * Lấy hàng từ DB với khóa (dùng trong transaction để tránh race condition).
 */
function gf_lock_row(string $table, int $id): ?array
{
    if (!in_array($table, ['gyms', 'trainers', 'reviews', 'users', 'categories'], true)) {
        return null;
    }
    $sql = "SELECT * FROM {$table} WHERE id = ?";
    if (gf_driver() === 'mysql') {
        $sql .= ' FOR UPDATE';
    }
    $st = gf_pdo()->prepare($sql);
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
}

/**
 * Trả về tên driver PDO (sqlite, mysql...).
 */
function gf_driver(): string
{
    return (string) gf_pdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
}

/**
 * Cập nhật trạng thái cho một entity (gym, trainer, category).
 */
function gf_set_entity_status(string $table, int $id, string $status): void
{
    if (!in_array($table, ['gyms', 'trainers', 'categories'], true)) {
        return;
    }
    if ($table === 'categories') {
        gf_pdo()->prepare('UPDATE categories SET is_active=? WHERE id=?')->execute([$status === 'active' ? 1 : 0, $id]);
        return;
    }
    gf_pdo()->prepare("UPDATE {$table} SET status=? WHERE id=?")->execute([$status, $id]);
}

/**
 * Tạo slug duy nhất trong một bảng.
 */
function gf_unique_slug(string $table, string $base, int $maxLen = 120): string
{
    if (!in_array($table, ['gyms', 'trainers', 'categories'], true)) {
        return $base;
    }
    $i = 2;
    $st = gf_pdo()->prepare("SELECT id FROM {$table} WHERE slug=?");
    $slug = mb_substr($base, 0, $maxLen);
    while (true) {
        $st->execute([$slug]);
        if (!$st->fetch()) {
            return $slug;
        }
        $suffix = '-' . $i;
        $slug = mb_substr($base, 0, $maxLen - strlen($suffix)) . $suffix;
        $i++;
        if ($i > 500) {
            return $slug . '-' . bin2hex(random_bytes(2));
        }
    }
}

/**
 * Lấy danh sách quận/huyện.
 */
function gf_list_districts(): array
{
    return gf_pdo()->query('SELECT * FROM districts ORDER BY name')->fetchAll();
}

/**
 * Lấy danh sách danh mục.
 */
function gf_list_categories(bool $activeOnly = true): array
{
    $sql = 'SELECT * FROM categories';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    return gf_pdo()->query($sql . ' ORDER BY name')->fetchAll();
}

/**
 * Lấy danh sách tiện ích.
 */
function gf_list_amenities(): array
{
    return gf_pdo()->query('SELECT * FROM amenities ORDER BY name')->fetchAll();
}

/**
 * Lấy danh sách chuyên môn.
 */
function gf_list_specialties(): array
{
    return gf_pdo()->query('SELECT * FROM specialties ORDER BY name')->fetchAll();
}

/**
 * Tính lại rating trung bình cho một gym hoặc trainer.
 * @param array{type: string, id: int} $target
 */
function gf_recalc_rating(array $target): void
{
    $type = $target['type'];
    $id   = (int) $target['id'];
    if ($type === 'gym') {
        gf_lock_row('gyms', $id);
        $st = gf_pdo()->prepare("SELECT AVG(rating) AS avg_r, COUNT(*) AS cnt FROM reviews WHERE gym_id=? AND status='approved'");
        $st->execute([$id]);
        $row = $st->fetch();
        $avg = $row && $row['cnt'] ? round((float) $row['avg_r'], 1) : 0;
        $cnt = (int) ($row['cnt'] ?? 0);
        gf_pdo()->prepare('UPDATE gyms SET avg_rating=?, review_count=? WHERE id=?')->execute([$avg, $cnt, $id]);
        return;
    }
    gf_lock_row('trainers', $id);
    $st = gf_pdo()->prepare("SELECT AVG(rating) AS avg_r, COUNT(*) AS cnt FROM reviews WHERE trainer_id=? AND status='approved'");
    $st->execute([$id]);
    $row = $st->fetch();
    $avg = $row && $row['cnt'] ? round((float) $row['avg_r'], 1) : 0;
    $cnt = (int) ($row['cnt'] ?? 0);
    gf_pdo()->prepare('UPDATE trainers SET avg_rating=?, review_count=? WHERE id=?')->execute([$avg, $cnt, $id]);
}

/**
 * Tính lại tất cả rating (dùng khi moderate hàng loạt).
 */
function gf_recalc_ratings(): void
{
    gf_pdo()->exec("UPDATE gyms SET avg_rating = COALESCE((SELECT ROUND(AVG(rating),1) FROM reviews WHERE gym_id = gyms.id AND status='approved'),0), review_count = (SELECT COUNT(*) FROM reviews WHERE gym_id = gyms.id AND status='approved')");
    gf_pdo()->exec("UPDATE trainers SET avg_rating = COALESCE((SELECT ROUND(AVG(rating),1) FROM reviews WHERE trainer_id = trainers.id AND status='approved'),0), review_count = (SELECT COUNT(*) FROM reviews WHERE trainer_id = trainers.id AND status='approved')");
}
