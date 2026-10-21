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
    $item = [
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
    if (isset($t['specialties'])) {
        $item['specialties'] = $t['specialties'];
        $item['specialty_ids'] = array_map('intval', array_column($t['specialties'], 'id'));
    }
    if (isset($t['gyms'])) {
        $item['gyms'] = $t['gyms'];
        $item['gym_ids'] = array_map('intval', array_column($t['gyms'], 'id'));
    }
    return $item;
}

/**
 * Tìm kiếm trainer có phân trang.
 */
function gf_search_trainers_paged(array $f): array
{
    $sql = "FROM trainers t
            LEFT JOIN specialties s ON s.id = t.specialty_id
            LEFT JOIN gyms g ON g.id = t.gym_id
            LEFT JOIN districts d ON d.id = t.district_id
            WHERE t.status = 'active' AND (
                EXISTS (SELECT 1 FROM gym_trainers gt JOIN gyms g_sub ON g_sub.id = gt.gym_id WHERE gt.trainer_id = t.id AND g_sub.status = 'active')
                OR (NOT EXISTS (SELECT 1 FROM gym_trainers legacy_gt WHERE legacy_gt.trainer_id = t.id) AND g.status = 'active')
            )";
    $params = [];

    $q = trim((string) ($f['q'] ?? $f['keyword'] ?? ''));
    if ($q !== '') {
        $sql .= " AND (t.full_name LIKE ? ESCAPE '!' OR (g.status = 'active' AND g.name LIKE ? ESCAPE '!') OR s.name LIKE ? ESCAPE '!' OR EXISTS (SELECT 1 FROM trainer_specialties ts JOIN specialties s2 ON s2.id = ts.specialty_id WHERE ts.trainer_id = t.id AND s2.name LIKE ? ESCAPE '!') OR EXISTS (SELECT 1 FROM gym_trainers gt JOIN gyms g3 ON g3.id = gt.gym_id WHERE gt.trainer_id = t.id AND g3.status = 'active' AND g3.name LIKE ? ESCAPE '!'))";
        $like = gf_like_contains($q);
        array_push($params, $like, $like, $like, $like, $like);
    }
    if (!empty($f['specialty_id'])) {
        $sql .= ' AND (t.specialty_id = ? OR EXISTS (SELECT 1 FROM trainer_specialties ts WHERE ts.trainer_id = t.id AND ts.specialty_id = ?))';
        $params[] = (int) $f['specialty_id'];
        $params[] = (int) $f['specialty_id'];
    }
    if (!empty($f['district_id'])) {
        $sql .= " AND (EXISTS (SELECT 1 FROM gym_trainers gt JOIN gyms g_dist ON g_dist.id = gt.gym_id WHERE gt.trainer_id = t.id AND g_dist.status = 'active' AND g_dist.district_id = ?) OR (NOT EXISTS (SELECT 1 FROM gym_trainers legacy_gt WHERE legacy_gt.trainer_id = t.id) AND g.status = 'active' AND t.district_id = ?))";
        $params[] = (int) $f['district_id'];
        $params[] = (int) $f['district_id'];
    }
    if (!empty($f['gym_id'])) {
        $sql .= " AND (EXISTS (SELECT 1 FROM gym_trainers gt JOIN gyms gf ON gf.id = gt.gym_id WHERE gt.trainer_id = t.id AND gt.gym_id = ? AND gf.status = 'active') OR (NOT EXISTS (SELECT 1 FROM gym_trainers legacy_gt WHERE legacy_gt.trainer_id = t.id) AND t.gym_id = ? AND g.status = 'active'))";
        $params[] = (int) $f['gym_id'];
        $params[] = (int) $f['gym_id'];
    }
    if (!empty($f['featured'])) {
        $sql .= ' AND t.is_featured = 1';
    }
    if (isset($f['min_rating']) && $f['min_rating'] !== '') {
        $sql .= ' AND t.avg_rating >= ?';
        $params[] = (float) $f['min_rating'];
    }

    $exp = (string) ($f['exp'] ?? $f['experience'] ?? '');
    // 0-2 giữ tương thích URL cũ → gom về 1–2 (không còn 0 năm kinh nghiệm)
    if ($exp === '1-2' || $exp === '0-2') {
        $sql .= ' AND t.years_experience BETWEEN 1 AND 2';
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

    $countSt = gf_pdo()->prepare("SELECT COUNT(DISTINCT t.id) $sql");
    $countSt->execute($params);
    $total = (int) $countSt->fetchColumn();

    $offset = ($page - 1) * $limit;
    $st     = gf_pdo()->prepare("SELECT DISTINCT t.*, s.name AS specialty_name, g.name AS gym_name, g.status AS gym_status, d.name AS district_name $sql ORDER BY $order LIMIT $limit OFFSET $offset");
    $st->execute($params);
    $rows = $st->fetchAll();

    $tsSt = gf_pdo()->prepare("SELECT s.id, s.name, s.slug FROM specialties s JOIN trainer_specialties ts ON ts.specialty_id = s.id WHERE ts.trainer_id = ? ORDER BY s.name");
    $gtSt = gf_pdo()->prepare("SELECT g.id, g.name, g.slug, g.address, g.cover_image_url, g.district_id, d.name AS district_name FROM gyms g JOIN gym_trainers gt ON gt.gym_id = g.id JOIN districts d ON d.id = g.district_id WHERE gt.trainer_id = ? AND g.status = 'active' ORDER BY g.name");

    foreach ($rows as &$t) {
        $t['avatar'] = gf_avatar($t['avatar_url'] ?? null, (int) $t['id'], $t['full_name']);
        
        $tsSt->execute([(int) $t['id']]);
        $specs = $tsSt->fetchAll();
        if (empty($specs) && !empty($t['specialty_id'])) {
            $specs = [['id' => (int) $t['specialty_id'], 'name' => $t['specialty_name'], 'slug' => '']];
        }
        $t['specialties'] = $specs;
        $t['specialty_names'] = implode(', ', array_column($specs, 'name'));

        $gtSt->execute([(int) $t['id']]);
        $gymList = $gtSt->fetchAll();
        if (empty($gymList) && !empty($t['gym_id'])) {
            $gymList = [['id' => (int) $t['gym_id'], 'name' => $t['gym_name'], 'slug' => '', 'address' => '', 'cover_image_url' => '', 'district_name' => $t['district_name']]];
        }
        $t['gyms'] = $gymList;
        $t['gym_names'] = implode(', ', array_column($gymList, 'name'));
        if ($gymList !== []) {
            $publicPrimary = $gymList[0];
            foreach ($gymList as $linkedGym) {
                if ((int) $linkedGym['id'] === (int) $t['gym_id']) {
                    $publicPrimary = $linkedGym;
                    break;
                }
            }
            $t['gym_name'] = $publicPrimary['name'];
            $t['gym_status'] = 'active';
            $t['district_name'] = $publicPrimary['district_name'];
            $t['district_id'] = (int) ($publicPrimary['district_id'] ?? $t['district_id']);
        }
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
            LEFT JOIN specialties s ON s.id = t.specialty_id
            LEFT JOIN gyms g ON g.id = t.gym_id
            LEFT JOIN districts d ON d.id = t.district_id
            WHERE t.status='active'
              AND (
                EXISTS (SELECT 1 FROM gym_trainers gt JOIN gyms linked_g ON linked_g.id=gt.gym_id WHERE gt.trainer_id=t.id AND linked_g.status='active')
                OR (NOT EXISTS (SELECT 1 FROM gym_trainers legacy_gt WHERE legacy_gt.trainer_id=t.id) AND g.status='active')
              ) AND ";

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

    // Load specialties (N-N)
    $tsSt = gf_pdo()->prepare("SELECT s.id, s.name, s.slug FROM specialties s JOIN trainer_specialties ts ON ts.specialty_id = s.id WHERE ts.trainer_id = ? ORDER BY s.name");
    $tsSt->execute([(int) $t['id']]);
    $specs = $tsSt->fetchAll();
    if (empty($specs) && !empty($t['specialty_id'])) {
        $specs = [['id' => (int) $t['specialty_id'], 'name' => $t['specialty_name'], 'slug' => $t['specialty_slug']]];
    }
    $t['specialties'] = $specs;
    $t['specialty_names'] = implode(', ', array_column($specs, 'name'));

    // Load gyms (N-N)
    $gtSt = gf_pdo()->prepare("SELECT g.id, g.name, g.slug, g.address, g.cover_image_url, g.avg_rating, g.district_id, d.name AS district_name FROM gyms g JOIN gym_trainers gt ON gt.gym_id = g.id JOIN districts d ON d.id = g.district_id WHERE gt.trainer_id = ? AND g.status = 'active' ORDER BY g.name");
    $gtSt->execute([(int) $t['id']]);
    $gymList = $gtSt->fetchAll();
    if (empty($gymList) && !empty($t['gym_id'])) {
        $gymList = [[
            'id' => (int) $t['gym_id'],
            'name' => $t['gym_name'],
            'slug' => $t['gym_slug'],
            'address' => $t['gym_address'],
            'cover_image_url' => $t['gym_cover'],
            'avg_rating' => 0,
            'district_id' => (int) $t['district_id'],
            'district_name' => $t['district_name']
        ]];
    }
    $t['gyms'] = $gymList;
    $t['gym_names'] = implode(', ', array_column($gymList, 'name'));
    if ($gymList !== []) {
        // Never expose a hidden primary gym when this trainer is public through another linked gym.
        $publicPrimary = $gymList[0];
        foreach ($gymList as $linkedGym) {
            if ((int) $linkedGym['id'] === (int) $t['gym_id']) {
                $publicPrimary = $linkedGym;
                break;
            }
        }
        $t['gym_id'] = (int) $publicPrimary['id'];
        $t['gym_name'] = $publicPrimary['name'];
        $t['gym_slug'] = $publicPrimary['slug'];
        $t['gym_address'] = $publicPrimary['address'];
        $t['gym_cover'] = $publicPrimary['cover_image_url'];
        $t['district_id'] = (int) $publicPrimary['district_id'];
        $t['district_name'] = $publicPrimary['district_name'];
    }

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
    $st = gf_pdo()->prepare("SELECT r.*, u.full_name, u.avatar_url FROM reviews r JOIN users u ON u.id = r.user_id WHERE r.trainer_id = ? AND r.status='approved' ORDER BY r.created_at DESC");
    $st->execute([$trainerId]);
    return $st->fetchAll();
}
