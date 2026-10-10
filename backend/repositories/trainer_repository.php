<?php
declare(strict_types=1);

// =============================================================================
// TRAINER REPOSITORY — tất cả query liên quan đến huấn luyện viên
// =============================================================================

/**
 * Format dữ liệu trainer cho danh sách (public).
 */
function gf_trainer_list_item(array $t): array
{
    return [
        'id'               => (int) $t['id'],
        'full_name'        => $t['full_name'],
        'slug'             => $t['slug'],
        'avatar_url'       => $t['avatar_url'] ?? null,
        'specialty_id'     => (int) $t['specialty_id'],
        'years_experience' => (int) $t['years_experience'],
        'gym_id'           => (int) $t['gym_id'],
        'district_id'      => (int) $t['district_id'],
        'avg_rating'       => gf_num1($t['avg_rating']),
        'review_count'     => (int) $t['review_count'],
        'is_featured'      => (int) $t['is_featured'],
    ];
}

/**
 * Tìm kiếm trainer có phân trang.
 */
function gf_search_trainers_paged(array $f): array
{
    $sql = "FROM trainers t
            JOIN specialties s ON s.id = t.specialty_id
            JOIN gyms g ON g.id = t.gym_id
            JOIN districts d ON d.id = t.district_id
            WHERE t.status = 'active' AND g.status = 'active'";
    $params = [];

    $q = trim((string) ($f['q'] ?? $f['keyword'] ?? ''));
    if ($q !== '') {
        $sql     .= " AND (t.full_name LIKE ? ESCAPE '!' OR s.name LIKE ? ESCAPE '!' OR g.name LIKE ? ESCAPE '!')";
        $like = gf_like_contains($q);
        array_push($params, $like, $like, $like);
    }
    if (!empty($f['specialty_id'])) {
        $sql     .= ' AND t.specialty_id = ?';
        $params[] = (int) $f['specialty_id'];
    }
    if (!empty($f['district_id'])) {
        $sql     .= ' AND t.district_id = ?';
        $params[] = (int) $f['district_id'];
    }
    if (!empty($f['featured'])) {
        $sql .= ' AND t.is_featured = 1';
    }
    if (isset($f['min_rating']) && $f['min_rating'] !== '') {
        $sql .= ' AND t.avg_rating >= ?';
        $params[] = (float) $f['min_rating'];
    }

    $exp = (string) ($f['exp'] ?? $f['experience'] ?? '');
    if ($exp === '0-2') {
        $sql .= ' AND t.years_experience BETWEEN 0 AND 2';
    } elseif ($exp === '3-5') {
        $sql .= ' AND t.years_experience BETWEEN 3 AND 5';
    } elseif ($exp === '6plus') {
        $sql .= ' AND t.years_experience >= 6';
    }

    $sort  = (string) ($f['sort'] ?? '');
    $order = match ($sort) {
        'rating_asc'      => 't.avg_rating ASC, t.id DESC',
        'experience_desc' => 't.years_experience DESC, t.id DESC',
        'experience_asc'  => 't.years_experience ASC, t.id DESC',
        'name_asc'        => 't.full_name ASC, t.id DESC',
        'rating_desc'     => 't.avg_rating DESC, t.id DESC',
        default           => 't.is_featured DESC, t.avg_rating DESC, t.id DESC',
    };

    $page  = max(1, (int) ($f['page']  ?? 1));
    $limit = min(100, max(1, (int) ($f['limit'] ?? 20)));

    $countSt = gf_pdo()->prepare("SELECT COUNT(*) $sql");
    $countSt->execute($params);
    $total = (int) $countSt->fetchColumn();

    $offset = ($page - 1) * $limit;
    $st     = gf_pdo()->prepare("SELECT t.*, s.name AS specialty_name, g.name AS gym_name, g.status AS gym_status, d.name AS district_name $sql ORDER BY $order LIMIT $limit OFFSET $offset");
    $st->execute($params);
    $rows = $st->fetchAll();

    foreach ($rows as &$t) {
        $t['avatar'] = gf_avatar($t['avatar_url'] ?? null, (int) $t['id'], $t['full_name']);
    }

    return ['items' => $rows, 'page' => $page, 'limit' => $limit, 'total' => $total];
}

/**
 * Wrapper trả về chỉ items (không phân trang).
 */
function gf_search_trainers(array $f): array
{
    return gf_search_trainers_paged($f)['items'];
}

/**
 * Lấy trainer theo ID hoặc slug (chỉ active).
 */
function gf_get_trainer_by_key(string $idOrSlug): ?array
{
    $sql = "SELECT t.*, s.name AS specialty_name, s.slug AS specialty_slug,
                   g.name AS gym_name, g.id AS gym_pk, g.address AS gym_address,
                   g.slug AS gym_slug, g.cover_image_url AS gym_cover,
                   d.name AS district_name, d.slug AS district_slug
            FROM trainers t
            JOIN specialties s ON s.id = t.specialty_id
            JOIN gyms g ON g.id = t.gym_id
            JOIN districts d ON d.id = t.district_id
            WHERE t.status='active' AND g.status='active' AND ";

    if (ctype_digit($idOrSlug)) {
        $sql .= 't.id = ?';
        $arg  = (int) $idOrSlug;
    } else {
        $sql .= 't.slug = ?';
        $arg  = $idOrSlug;
    }

    $st = gf_pdo()->prepare($sql);
    $st->execute([$arg]);
    $t = $st->fetch();
    if (!$t) {
        return null;
    }
    $t['avatar'] = gf_avatar($t['avatar_url'] ?? null, (int) $t['id'], $t['full_name']);
    return $t;
}

/**
 * Lấy trainer theo ID (wrapper).
 */
function gf_get_trainer(int $id): ?array
{
    return gf_get_trainer_by_key((string) $id);
}

/**
 * Lấy đánh giá đã approved của một trainer.
 */
function gf_trainer_reviews(int $trainerId): array
{
    $st = gf_pdo()->prepare("SELECT r.*, u.full_name FROM reviews r JOIN users u ON u.id = r.user_id WHERE r.trainer_id = ? AND r.status='approved' ORDER BY r.created_at DESC");
    $st->execute([$trainerId]);
    return $st->fetchAll();
}
