<?php
declare(strict_types=1);

// =============================================================================
// FAVORITE REPOSITORY — tất cả query liên quan đến danh sách yêu thích
// =============================================================================

/**
 * Kiểm tra user đã yêu thích target chưa.
 */
function gf_is_fav(int $userId, string $type, int $targetId): bool
{
    if ($type === 'gym') {
        $st = gf_pdo()->prepare('SELECT id FROM favorites WHERE user_id=? AND gym_id=?');
        $st->execute([$userId, $targetId]);
    } else {
        $st = gf_pdo()->prepare('SELECT id FROM favorites WHERE user_id=? AND trainer_id=?');
        $st->execute([$userId, $targetId]);
    }
    return (bool) $st->fetch();
}

/**
 * Bật/tắt favorite từ form PHP của giao diện.
 * Target chỉ cần public khi thêm mới; xóa vẫn được phép nếu target đã ẩn.
 */
function gf_toggle_fav(int $userId, string $type, int $targetId): bool
{
    if (!in_array($type, ['gym', 'trainer'], true) || $targetId < 1) {
        gf_api_error('VALIDATION_ERROR', 'Đối tượng yêu thích không hợp lệ.', 422);
    }

    if (gf_is_fav($userId, $type, $targetId)) {
        $column = $type === 'gym' ? 'gym_id' : 'trainer_id';
        $st = gf_pdo()->prepare("DELETE FROM favorites WHERE user_id=? AND target_type=? AND {$column}=?");
        $st->execute([$userId, $type, $targetId]);
        return false;
    }

    if (!gf_target_public($type, $targetId)) {
        gf_api_error('NOT_FOUND', 'Đối tượng không tồn tại hoặc đang ẩn.', 404);
    }

    gf_add_favorite_row($userId, $type, $targetId);
    return true;
}

/**
 * Lấy danh sách yêu thích của user, có phân trang.
 */
function gf_favorites_paged(int $userId, int $page, int $limit): array
{
    $sql = "FROM favorites f
            LEFT JOIN gyms g ON g.id = f.gym_id
            LEFT JOIN trainers t ON t.id = f.trainer_id
            LEFT JOIN gyms tg ON tg.id = t.gym_id
            WHERE f.user_id = ?
              AND (
                (f.target_type='gym' AND g.status='active')
                OR (f.target_type='trainer' AND t.status='active' AND tg.status='active')
              )";

    $count = gf_pdo()->prepare("SELECT COUNT(*) $sql");
    $count->execute([$userId]);
    $total = (int) $count->fetchColumn();

    $offset = ($page - 1) * $limit;
    $st     = gf_pdo()->prepare("SELECT f.id, f.target_type, f.gym_id, f.trainer_id, f.created_at,
            g.name AS gym_name, g.slug AS gym_slug, g.cover_image_url AS gym_cover, g.avg_rating AS gym_rating, g.review_count AS gym_rc,
            t.full_name AS trainer_name, t.slug AS trainer_slug, t.avatar_url AS trainer_avatar, t.avg_rating AS trainer_rating, t.review_count AS trainer_rc
            $sql ORDER BY f.created_at DESC, f.id DESC LIMIT $limit OFFSET $offset");
    $st->execute([$userId]);

    $items = [];
    foreach ($st->fetchAll() as $row) {
        $isGym = $row['target_type'] === 'gym';
        $tid   = $isGym ? (int) $row['gym_id'] : (int) $row['trainer_id'];
        $items[] = [
            'id'          => (int) $row['id'],
            'target_type' => $row['target_type'],
            'target_id'   => $tid,
            'created_at'  => gf_iso($row['created_at']),
            'target'      => [
                'id'           => $tid,
                'name'         => $isGym ? $row['gym_name'] : $row['trainer_name'],
                'slug'         => $isGym ? $row['gym_slug'] : $row['trainer_slug'],
                'image_url'    => $isGym ? $row['gym_cover'] : $row['trainer_avatar'],
                'avg_rating'   => gf_num1($isGym ? $row['gym_rating'] : $row['trainer_rating']),
                'review_count' => (int) ($isGym ? $row['gym_rc'] : $row['trainer_rc']),
            ],
        ];
    }

    return ['items' => $items, 'page' => $page, 'limit' => $limit, 'total' => $total];
}

/**
 * Thêm một mục vào danh sách yêu thích, trả về row mới tạo.
 */
function gf_add_favorite_row(int $userId, string $type, int $tid): array
{
    if ($type === 'gym') {
        gf_pdo()->prepare("INSERT INTO favorites (user_id, target_type, gym_id) VALUES (?, 'gym', ?)")->execute([$userId, $tid]);
    } else {
        gf_pdo()->prepare("INSERT INTO favorites (user_id, target_type, trainer_id) VALUES (?, 'trainer', ?)")->execute([$userId, $tid]);
    }
    $id = (int) gf_pdo()->lastInsertId();
    $st = gf_pdo()->prepare('SELECT id, target_type, gym_id, trainer_id, created_at FROM favorites WHERE id=?');
    $st->execute([$id]);
    $row = $st->fetch();
    return [
        'id'          => (int) $row['id'],
        'target_type' => $row['target_type'],
        'target_id'   => $type === 'gym' ? (int) $row['gym_id'] : (int) $row['trainer_id'],
        'created_at'  => gf_iso($row['created_at']),
    ];
}

/**
 * Xóa một mục khỏi danh sách yêu thích của user.
 */
function gf_delete_favorite_item(int $userId, string $type, int $tid): void
{
    if (!in_array($type, ['gym', 'trainer'], true)) {
        gf_api_error('VALIDATION_ERROR', 'target_type không hợp lệ.', 422, ['target_type' => 'gym | trainer']);
    }
    $col = $type === 'gym' ? 'gym_id' : 'trainer_id';
    $st  = gf_pdo()->prepare("SELECT id FROM favorites WHERE user_id=? AND target_type=? AND {$col}=?");
    $st->execute([$userId, $type, $tid]);
    $favId = $st->fetchColumn();
    if (!$favId) {
        gf_api_error('NOT_FOUND', 'Mục yêu thích không tồn tại.', 404);
    }
    gf_pdo()->prepare('DELETE FROM favorites WHERE id=?')->execute([(int) $favId]);
}

/**
 * Đồng bộ danh sách yêu thích từ client (bulk add, bỏ qua duplicate).
 */
function gf_sync_favorites(int $userId, mixed $body): array
{
    $list = $body;
    if (is_array($body) && isset($body['items']) && is_array($body['items'])) {
        $list = $body['items'];
    }
    if (!is_array($list)) {
        gf_api_error('VALIDATION_ERROR', 'Danh sách sync không hợp lệ.', 422, ['items' => 'Phải là mảng.']);
    }
    if (count($list) > 100) {
        gf_api_error('VALIDATION_ERROR', 'Tối đa 100 phần tử.', 422, ['items' => 'Tối đa 100 phần tử.']);
    }

    foreach ($list as $idx => $row) {
        if (!is_array($row) || !isset($row['target_type'], $row['target_id'])) {
            gf_api_error('VALIDATION_ERROR', "Phần tử {$idx} thiếu target_type hoặc target_id.", 422, ["items.{$idx}" => 'Sai định dạng.']);
        }
        if (!in_array($row['target_type'], ['gym', 'trainer'], true)
            || !is_numeric($row['target_id'])
            || (int) $row['target_id'] != $row['target_id']
            || (int) $row['target_id'] < 1) {
            gf_api_error('VALIDATION_ERROR', "Phần tử {$idx} có target_type hoặc target_id không hợp lệ.", 422, ["items.{$idx}" => 'target_type hoặc target_id không hợp lệ.']);
        }
    }

    $added   = [];
    $skipped = [];
    $seen    = [];

    foreach ($list as $row) {
        $type = (string) $row['target_type'];
        $tid  = (int)    $row['target_id'];
        $key  = $type . ':' . $tid;

        if (isset($seen[$key])) {
            $skipped[] = ['target_type' => $type, 'target_id' => $tid, 'reason' => 'DUPLICATE_IN_REQUEST'];
            continue;
        }
        $seen[$key] = true;

        if (!gf_target_public($type, $tid)) {
            $skipped[] = ['target_type' => $type, 'target_id' => $tid, 'reason' => 'TARGET_UNAVAILABLE'];
            continue;
        }
        if (gf_is_fav($userId, $type, $tid)) {
            $skipped[] = ['target_type' => $type, 'target_id' => $tid, 'reason' => 'ALREADY_FAVORITED'];
            continue;
        }

        gf_add_favorite_row($userId, $type, $tid);
        $added[] = ['target_type' => $type, 'target_id' => $tid];
    }

    return ['added' => $added, 'skipped' => $skipped];
}

/**
 * Lấy toàn bộ favorites (gyms + trainers) của user, không phân trang.
 */
function gf_user_favorites(int $userId): array
{
    $gyms = gf_pdo()->prepare("SELECT g.*, d.name AS district_name FROM favorites f JOIN gyms g ON g.id=f.gym_id JOIN districts d ON d.id=g.district_id WHERE f.user_id=? AND f.target_type='gym' AND g.status='active'");
    $gyms->execute([$userId]);

    $trainers = gf_pdo()->prepare("SELECT t.*, s.name AS specialty_name, g.name AS gym_name, d.name AS district_name
        FROM favorites f
        JOIN trainers t ON t.id=f.trainer_id
        JOIN specialties s ON s.id=t.specialty_id
        JOIN gyms g ON g.id=t.gym_id
        JOIN districts d ON d.id=t.district_id
        WHERE f.user_id=? AND f.target_type='trainer' AND t.status='active' AND g.status='active'");
    $trainers->execute([$userId]);

    $gRows = array_map('gf_attach_gym_extras', $gyms->fetchAll());
    $tRows = $trainers->fetchAll();
    foreach ($tRows as &$t) {
        $t['avatar'] = gf_avatar($t['avatar_url'] ?? null, (int) $t['id'], $t['full_name']);
    }

    return ['gyms' => $gRows, 'trainers' => $tRows];
}
