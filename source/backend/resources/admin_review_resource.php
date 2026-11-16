<?php
declare(strict_types=1);

// Admin resource: reviews.

/**
 * Admin xem danh sách review, có filter & phân trang.
 */
function gf_admin_reviews_paged(array $q): array
{
    $pageLimit = gf_api_page_limit($q);
    $page  = $pageLimit['page'];
    $limit = $pageLimit['limit'];
    $where  = ['1=1'];
    $params = [];

    $status = (string) ($q['status'] ?? '');
    if ($status !== '') {
        if (!in_array($status, ['pending', 'approved', 'rejected'], true)) { gf_api_error('VALIDATION_ERROR', 'status phải thuộc: pending, approved, rejected.', 422, ['status' => 'pending|approved|rejected']); }
        $where[]  = 'r.status = ?';
        $params[] = $status;
    }
    $type = (string) ($q['target_type'] ?? '');
    $tid  = $q['target_id'] ?? null;
    if ($tid !== null && $tid !== '') {
        if ($type === '') { gf_api_error('VALIDATION_ERROR', 'target_id phải đi kèm target_type.', 422, ['target_type' => 'Bắt buộc khi có target_id.']); }
        if (!in_array($type, ['gym', 'trainer'], true)) { gf_api_error('VALIDATION_ERROR', 'target_type chỉ gym|trainer.', 422, ['target_type' => 'gym|trainer']); }
        $col     = $type === 'gym' ? 'r.gym_id' : 'r.trainer_id';
        $where[] = "r.target_type = ? AND {$col} = ?";
        $params[] = $type;
        $params[] = (int) $tid;
    } elseif ($type !== '') {
        if (!in_array($type, ['gym', 'trainer'], true)) { gf_api_error('VALIDATION_ERROR', 'target_type chỉ gym|trainer.', 422, ['target_type' => 'gym|trainer']); }
        $where[]  = 'r.target_type = ?';
        $params[] = $type;
    }
    $sort  = (string) ($q['sort'] ?? 'newest');
    $order = match ($sort) {
        'oldest'      => 'r.id ASC',
        'rating_desc' => 'r.rating DESC, r.id DESC',
        'rating_asc'  => 'r.rating ASC, r.id DESC',
        'newest'      => 'r.id DESC',
        default       => gf_api_error('VALIDATION_ERROR', 'sort không hợp lệ.', 422, ['sort' => 'newest|oldest|rating_desc|rating_asc']),
    };

    $wSql = implode(' AND ', $where);
    $cSt  = gf_pdo()->prepare("SELECT COUNT(*) FROM reviews r WHERE {$wSql}");
    $cSt->execute($params);
    $total = (int) $cSt->fetchColumn();

    $offset = ($page - 1) * $limit;
    $st     = gf_pdo()->prepare("SELECT r.* FROM reviews r WHERE {$wSql} ORDER BY {$order} LIMIT {$limit} OFFSET {$offset}");
    $st->execute($params);
    $items = [];
    foreach ($st->fetchAll() as $r) {
        $items[] = [
            'id'            => (int) $r['id'],
            'user_id'       => (int) $r['user_id'],
            'target_type'   => $r['target_type'],
            'target_id'     => $r['target_type'] === 'gym' ? (int) $r['gym_id'] : (int) $r['trainer_id'],
            'rating'        => (int) $r['rating'],
            'comment'       => $r['comment'],
            'status'        => $r['status'],
            'reject_reason' => $r['reject_reason'],
            'reviewed_by'   => $r['reviewed_by'] !== null ? (int) $r['reviewed_by'] : null,
            'reviewed_at'   => gf_iso($r['reviewed_at']),
            'created_at'    => gf_iso($r['created_at']),
            'updated_at'    => gf_iso($r['updated_at']),
        ];
    }
    return ['items' => $items, 'page' => $page, 'limit' => $limit, 'total' => $total];
}


/**
 * Admin duyệt hoặc từ chối một review.
 */
function gf_admin_moderate_review(int $id, array $d, array $admin): array
{
    $r = gf_fetch_review($id);
    if (!$r) { gf_api_error('NOT_FOUND', 'Không tìm thấy đánh giá.', 404); }

    gf_require_known_body($d, ['status', 'reject_reason'], ['id', 'user_id', 'target_type', 'target_id', 'gym_id', 'trainer_id', 'rating', 'comment', 'reviewed_by', 'reviewed_at', 'created_at', 'updated_at']);
    if (!isset($d['status'])) { gf_api_error('VALIDATION_ERROR', 'Thiếu status.', 422, ['status' => 'Bắt buộc.']); }

    $newStatus = (string) $d['status'];
    if (!in_array($newStatus, ['approved', 'rejected'], true)) { gf_api_error('VALIDATION_ERROR', 'status chỉ approved|rejected.', 422, ['status' => 'approved|rejected']); }

    $reason = null;
    if ($newStatus === 'rejected') {
        if (!isset($d['reject_reason'])) { gf_api_error('VALIDATION_ERROR', 'reject_reason là bắt buộc khi rejected.', 422, ['reject_reason' => 'Bắt buộc khi rejected.']); }
        $rStr = trim((string) $d['reject_reason']);
        if ($rStr === '' || mb_strlen($rStr) > 255) { gf_api_error('VALIDATION_ERROR', 'reject_reason 1–255 ký tự.', 422, ['reject_reason' => '1–255 ký tự.']); }
        $reason = $rStr;
    } else {
        if (array_key_exists('reject_reason', $d) && $d['reject_reason'] !== null) { gf_api_error('VALIDATION_ERROR', 'Không gửi reject_reason khi approved.', 422, ['reject_reason' => 'Chỉ dùng khi rejected.']); }
    }

    $oldStatus     = $r['status'];
    $statusChanged = ($oldStatus !== $newStatus);
    $target        = gf_review_target($r);
    $adminId       = (int) $admin['id'];

    $pdo = gf_pdo();
    $pdo->beginTransaction();
    try {
        if ($newStatus === 'approved') {
            if ($oldStatus === 'approved') { $pdo->rollBack(); return gf_get_review_detail($id, $admin); }
            $pdo->prepare("UPDATE reviews SET status='approved', reviewed_by=?, reviewed_at=CURRENT_TIMESTAMP, reject_reason=NULL, updated_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$adminId, $id]);
            gf_recalc_rating($target);
        } else {
            $pdo->prepare("UPDATE reviews SET status='rejected', reviewed_by=?, reviewed_at=CURRENT_TIMESTAMP, reject_reason=?, updated_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$adminId, $reason, $id]);
            if ($statusChanged && $oldStatus === 'approved') { gf_recalc_rating($target); }
        }
        $pdo->commit();
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        gf_api_error('SERVER_ERROR', 'Lỗi khi duyệt đánh giá.', 500);
    }

    return gf_get_review_detail($id, $admin);
}


/**
 * Admin xóa vĩnh viễn một review.
 */
function gf_admin_delete_review(int $id): void
{
    $r = gf_fetch_review($id);
    if (!$r) { gf_api_error('NOT_FOUND', 'Không tìm thấy đánh giá.', 404); }
    $target = gf_review_target($r);
    $pdo    = gf_pdo();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM reviews WHERE id=?')->execute([$id]);
        gf_recalc_rating($target);
        $pdo->commit();
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        gf_api_error('SERVER_ERROR', 'Lỗi khi xóa đánh giá.', 500);
    }
}

// ---------------------------------------------------------------------------
// ADMIN USER MANAGEMENT
// ---------------------------------------------------------------------------
