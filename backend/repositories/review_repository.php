<?php
declare(strict_types=1);

// =============================================================================
// REVIEW REPOSITORY — tất cả query liên quan đến đánh giá
// =============================================================================

/**
 * Lấy một review theo ID.
 */
function gf_fetch_review(int $id): ?array
{
    $st = gf_pdo()->prepare('SELECT * FROM reviews WHERE id=?');
    $st->execute([$id]);
    $r = $st->fetch();
    return $r ?: null;
}

/**
 * Trả về {type, id} của target từ một row review.
 */
function gf_review_target(array $r): array
{
    return [
        'type' => $r['target_type'],
        'id'   => $r['target_type'] === 'gym' ? (int) $r['gym_id'] : (int) $r['trainer_id'],
    ];
}

/**
 * Kiểm tra target của review có đang public không.
 */
function gf_target_is_public_for_review(array $r): bool
{
    $t = gf_review_target($r);
    return gf_target_public($t['type'], $t['id']);
}

/**
 * Lấy review preview (3 review mới nhất) của gym hoặc trainer.
 */
function gf_preview_reviews(string $type, int $id): array
{
    if ($type === 'gym') {
        $st = gf_pdo()->prepare("SELECT id, rating, comment, created_at FROM reviews WHERE gym_id=? AND status='approved' ORDER BY created_at DESC, id DESC LIMIT 3");
    } else {
        $st = gf_pdo()->prepare("SELECT id, rating, comment, created_at FROM reviews WHERE trainer_id=? AND status='approved' ORDER BY created_at DESC, id DESC LIMIT 3");
    }
    $st->execute([$id]);
    $out = [];
    foreach ($st->fetchAll() as $r) {
        $out[] = [
            'id'         => (int) $r['id'],
            'rating'     => (int) $r['rating'],
            'comment'    => $r['comment'],
            'created_at' => gf_iso($r['created_at']),
        ];
    }
    return $out;
}

/**
 * Lấy review của user đối với một target cụ thể.
 */
function gf_my_review_row(int $userId, string $type, int $targetId): ?array
{
    if ($type === 'gym') {
        $st = gf_pdo()->prepare('SELECT * FROM reviews WHERE user_id=? AND gym_id=?');
    } else {
        $st = gf_pdo()->prepare('SELECT * FROM reviews WHERE user_id=? AND trainer_id=?');
    }
    $st->execute([$userId, $targetId]);
    $r = $st->fetch();
    if (!$r) {
        return null;
    }
    return [
        'id'            => (int) $r['id'],
        'rating'        => (int) $r['rating'],
        'comment'       => $r['comment'],
        'status'        => $r['status'],
        'reject_reason' => $r['reject_reason'],
        'created_at'    => gf_iso($r['created_at']),
        'updated_at'    => gf_iso($r['updated_at']),
    ];
}

/**
 * Lấy danh sách review đã approved của một target, có phân trang.
 */
function gf_reviews_list(string $type, int $targetId, int $page, int $limit, ?array $user): array
{
    $col   = $type === 'gym' ? 'gym_id' : 'trainer_id';
    $count = gf_pdo()->prepare("SELECT COUNT(*) FROM reviews WHERE {$col}=? AND status='approved'");
    $count->execute([$targetId]);
    $total = (int) $count->fetchColumn();

    $offset = ($page - 1) * $limit;
    $st     = gf_pdo()->prepare("SELECT id, rating, comment, created_at FROM reviews WHERE {$col}=? AND status='approved' ORDER BY created_at DESC, id DESC LIMIT $limit OFFSET $offset");
    $st->execute([$targetId]);

    $items = [];
    foreach ($st->fetchAll() as $r) {
        $items[] = [
            'id'         => (int) $r['id'],
            'rating'     => (int) $r['rating'],
            'comment'    => $r['comment'],
            'created_at' => gf_iso($r['created_at']),
        ];
    }

    $mine = null;
    if ($user && ($user['role'] ?? '') !== 'admin') {
        $mine = gf_my_review_row((int) $user['id'], $type, $targetId);
    }

    return ['items' => $items, 'page' => $page, 'limit' => $limit, 'total' => $total, 'my_review' => $mine];
}

/**
 * Tạo review mới từ user.
 */
function gf_create_review(int $userId, string $type, int $tid, int $rating, string $comment): array
{
    $comment = trim($comment);
    $len     = mb_strlen($comment);

    if (!gf_integer_like($rating) || $rating < 1 || $rating > 5) {
        return ['ok' => false, 'code' => 'VALIDATION_ERROR', 'http' => 422, 'error' => 'rating 1–5.', 'details' => ['rating' => '1–5']];
    }
    if ($len < 1 || $len > 2000) {
        return ['ok' => false, 'code' => 'VALIDATION_ERROR', 'http' => 422, 'error' => 'comment 1–2000 ký tự.', 'details' => ['comment' => '1–2000']];
    }

    $col = $type === 'gym' ? 'gym_id' : 'trainer_id';
    $chk = gf_pdo()->prepare("SELECT id FROM reviews WHERE user_id = ? AND target_type = ? AND {$col} = ?");
    $chk->execute([$userId, $type, $tid]);
    if ($chk->fetch()) {
        return ['ok' => false, 'code' => 'DUPLICATE_REVIEW', 'http' => 409, 'error' => 'Bạn đã đánh giá đối tượng này.'];
    }

    try {
        if ($type === 'gym') {
            gf_pdo()->prepare("INSERT INTO reviews (user_id, target_type, gym_id, rating, comment, status) VALUES (?, 'gym', ?, ?, ?, 'pending')")
                ->execute([$userId, $tid, $rating, $comment]);
        } else {
            gf_pdo()->prepare("INSERT INTO reviews (user_id, target_type, trainer_id, rating, comment, status) VALUES (?, 'trainer', ?, ?, ?, 'pending')")
                ->execute([$userId, $tid, $rating, $comment]);
        }
    } catch (\PDOException $e) {
        return ['ok' => false, 'code' => 'DUPLICATE_REVIEW', 'http' => 409, 'error' => 'Bạn đã đánh giá đối tượng này.'];
    }

    $row = gf_fetch_review((int) gf_pdo()->lastInsertId());
    return ['ok' => true, 'review' => $row];
}
