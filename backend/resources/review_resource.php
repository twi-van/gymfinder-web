<?php
declare(strict_types=1);

// =============================================================================
// REVIEW RESOURCE — logic xử lý đánh giá (user & admin)
// =============================================================================

/**
 * Format payload review cho user (người viết review thấy).
 */
function gf_review_user_payload(array $r): array
{
    return [
        'id'            => (int) $r['id'],
        'target_type'   => $r['target_type'],
        'target_id'     => $r['target_type'] === 'gym' ? (int) $r['gym_id'] : (int) $r['trainer_id'],
        'rating'        => (int) $r['rating'],
        'comment'       => $r['comment'],
        'status'        => $r['status'],
        'created_at'    => gf_iso($r['created_at']),
        'updated_at'    => gf_iso($r['updated_at']),
        'reject_reason' => $r['reject_reason'],
    ];
}

/**
 * Lấy chi tiết một review, tùy quyền của user.
 */
function gf_get_review_detail(int $id, ?array $user): array
{
    $r = gf_fetch_review($id);
    if (!$r) {
        gf_api_error('NOT_FOUND', 'Không tìm thấy đánh giá.', 404);
    }

    $isAdmin = $user && ($user['role'] ?? '') === 'admin';
    $isOwner = $user && (int) $user['id'] === (int) $r['user_id'];
    $tid     = $r['target_type'] === 'gym' ? (int) $r['gym_id'] : (int) $r['trainer_id'];

    if ($isAdmin) {
        return [
            'id'            => (int) $r['id'],
            'target_type'   => $r['target_type'],
            'target_id'     => $tid,
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
    if ($isOwner) {
        return [
            'id'            => (int) $r['id'],
            'target_type'   => $r['target_type'],
            'target_id'     => $tid,
            'rating'        => (int) $r['rating'],
            'comment'       => $r['comment'],
            'status'        => $r['status'],
            'reject_reason' => $r['reject_reason'],
            'created_at'    => gf_iso($r['created_at']),
            'updated_at'    => gf_iso($r['updated_at']),
        ];
    }

    if ($r['status'] !== 'approved' || !gf_target_is_public_for_review($r)) {
        gf_api_error('NOT_FOUND', 'Không tìm thấy đánh giá.', 404);
    }
    return [
        'id'         => (int) $r['id'],
        'target_type'=> $r['target_type'],
        'target_id'  => $tid,
        'rating'     => (int) $r['rating'],
        'comment'    => $r['comment'],
        'status'     => $r['status'],
        'created_at' => gf_iso($r['created_at']),
        'updated_at' => gf_iso($r['updated_at']),
    ];
}

/**
 * Cho phép chủ review cập nhật rating/comment (reset về pending).
 */
function gf_patch_review_owner(int $id, array $body, array $user): array
{
    $r = gf_fetch_review($id);
    if (!$r) {
        gf_api_error('NOT_FOUND', 'Không tìm thấy đánh giá.', 404);
    }
    if ((int) $user['id'] !== (int) $r['user_id']) {
        gf_api_error('FORBIDDEN', 'Bạn không có quyền sửa đánh giá này.', 403);
    }
    gf_require_known_body($body, ['rating', 'comment'], ['id', 'user_id', 'target_type', 'target_id', 'gym_id', 'trainer_id', 'status', 'reviewed_by', 'reviewed_at', 'reject_reason', 'created_at', 'updated_at']);
    if ($body === []) {
        gf_api_error('VALIDATION_ERROR', 'PATCH body rỗng.', 422, ['body' => 'Cần ít nhất một field rating hoặc comment.']);
    }

    $details    = [];
    $newRating  = (int) $r['rating'];
    $newComment = (string) $r['comment'];

    if (array_key_exists('rating', $body)) {
        $rateVal = $body['rating'];
        if (!is_numeric($rateVal) || (int) $rateVal != $rateVal || (int) $rateVal < 1 || (int) $rateVal > 5) {
            $details['rating'] = 'rating phải là số nguyên 1–5.';
        } else {
            $newRating = (int) $rateVal;
        }
    }
    if (array_key_exists('comment', $body)) {
        $cVal = trim((string) $body['comment']);
        $len  = mb_strlen($cVal);
        if ($len < 1 || $len > 2000) {
            $details['comment'] = 'comment 1–2000 ký tự.';
        } else {
            $newComment = $cVal;
        }
    }
    if ($details) {
        gf_api_error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ.', 422, $details);
    }

    $tid     = $r['target_type'] === 'gym' ? (int) $r['gym_id'] : (int) $r['trainer_id'];
    $changed = ($newRating !== (int) $r['rating']) || ($newComment !== (string) $r['comment']);
    if (!$changed) {
        return [
            'id'            => (int) $r['id'],
            'target_type'   => $r['target_type'],
            'target_id'     => $tid,
            'rating'        => (int) $r['rating'],
            'comment'       => $r['comment'],
            'status'        => $r['status'],
            'reject_reason' => $r['reject_reason'],
            'created_at'    => gf_iso($r['created_at']),
            'updated_at'    => gf_iso($r['updated_at']),
        ];
    }

    $pdo = gf_pdo();
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare("UPDATE reviews SET rating=?, comment=?, status='pending', reviewed_by=NULL, reviewed_at=NULL, reject_reason=NULL, updated_at=CURRENT_TIMESTAMP WHERE id=?");
        $st->execute([$newRating, $newComment, $id]);
        gf_recalc_rating(gf_review_target($r));
        $pdo->commit();
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        gf_api_error('SERVER_ERROR', 'Lỗi khi cập nhật đánh giá.', 500);
    }

    $updated = gf_fetch_review($id);
    return [
        'id'            => (int) $updated['id'],
        'target_type'   => $updated['target_type'],
        'target_id'     => $tid,
        'rating'        => (int) $updated['rating'],
        'comment'       => $updated['comment'],
        'status'        => 'pending',
        'reject_reason' => null,
        'created_at'    => gf_iso($updated['created_at']),
        'updated_at'    => gf_iso($updated['updated_at']),
    ];
}

/**
 * Cho phép chủ review xóa đánh giá của mình.
 */
function gf_delete_review_owner(int $id, array $user): void
{
    $r = gf_fetch_review($id);
    if (!$r) {
        gf_api_error('NOT_FOUND', 'Không tìm thấy đánh giá.', 404);
    }
    if ((int) $user['id'] !== (int) $r['user_id']) {
        gf_api_error('FORBIDDEN', 'Bạn không có quyền xóa đánh giá này.', 403);
    }

    $pdo = gf_pdo();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM reviews WHERE id=?')->execute([$id]);
        gf_recalc_rating(gf_review_target($r));
        $pdo->commit();
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        gf_api_error('SERVER_ERROR', 'Lỗi khi xóa đánh giá.', 500);
    }
}
