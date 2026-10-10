<?php
declare(strict_types=1);

// Admin resource: users.

/**
 * Admin xem danh sách user, có filter & phân trang.
 */
function gf_admin_users_paged(array $q): array
{
    $pageLimit = gf_api_page_limit($q);
    $page  = $pageLimit['page'];
    $limit = $pageLimit['limit'];
    $where  = ['1=1'];
    $params = [];

    $kw = trim((string) ($q['q'] ?? ''));
    if ($kw !== '') {
        $where[] = "(full_name LIKE ? ESCAPE '!' OR email LIKE ? ESCAPE '!')";
        $like    = gf_like_contains($kw);
        $params[] = $like;
        $params[] = $like;
    }
    $status = (string) ($q['status'] ?? '');
    if ($status !== '') {
        if (!in_array($status, ['active', 'locked'], true)) { gf_api_error('VALIDATION_ERROR', 'status chỉ active|locked.', 422, ['status' => 'active|locked']); }
        $where[]  = 'status = ?';
        $params[] = $status;
    }
    $role = (string) ($q['role'] ?? '');
    if ($role !== '') {
        if (!in_array($role, ['user', 'admin'], true)) { gf_api_error('VALIDATION_ERROR', 'role chỉ user|admin.', 422, ['role' => 'user|admin']); }
        $where[]  = 'role = ?';
        $params[] = $role;
    }
    $sort  = (string) ($q['sort'] ?? 'newest');
    $order = match ($sort) {
        'oldest'    => 'id ASC',
        'name_asc'  => 'full_name ASC, id DESC',
        'name_desc' => 'full_name DESC, id DESC',
        'newest'    => 'id DESC',
        default     => gf_api_error('VALIDATION_ERROR', 'sort không hợp lệ.', 422, ['sort' => 'newest|oldest|name_asc|name_desc']),
    };

    $wSql = implode(' AND ', $where);
    $cSt  = gf_pdo()->prepare("SELECT COUNT(*) FROM users WHERE {$wSql}");
    $cSt->execute($params);
    $total = (int) $cSt->fetchColumn();

    $offset = ($page - 1) * $limit;
    $st     = gf_pdo()->prepare("SELECT * FROM users WHERE {$wSql} ORDER BY {$order} LIMIT {$limit} OFFSET {$offset}");
    $st->execute($params);
    $items = [];
    foreach ($st->fetchAll() as $u) {
        $items[] = [
            'id'           => (int) $u['id'],
            'full_name'    => $u['full_name'],
            'email'        => $u['email'],
            'phone'        => $u['phone'],
            'goal'         => $u['goal'],
            'avatar_url'   => $u['avatar_url'],
            'role'         => $u['role'],
            'status'       => $u['status'],
            'last_login_at'=> gf_iso($u['last_login_at']),
            'created_at'   => gf_iso($u['created_at']),
        ];
    }
    return ['items' => $items, 'page' => $page, 'limit' => $limit, 'total' => $total];
}


/**
 * Lấy payload user đầy đủ cho admin.
 */
function gf_admin_user_payload(int $id): ?array
{
    $st = gf_pdo()->prepare('SELECT * FROM users WHERE id=?');
    $st->execute([$id]);
    $u = $st->fetch();
    if (!$u) {
        return null;
    }
    return [
        'id'            => (int) $u['id'],
        'full_name'     => $u['full_name'],
        'email'         => $u['email'],
        'phone'         => $u['phone'],
        'goal'          => $u['goal'],
        'avatar_url'    => $u['avatar_url'],
        'role'          => $u['role'],
        'status'        => $u['status'],
        'last_login_at' => gf_iso($u['last_login_at']),
        'created_at'    => gf_iso($u['created_at']),
    ];
}


/**
 * Admin khóa/mở khóa user.
 */
function gf_admin_patch_user(int $id, array $d, array $admin): array
{
    if ((int) $admin['id'] === $id) { gf_api_error('BUSINESS_RULE', 'Admin không được tự khóa chính mình.', 422, ['status' => 'Không tự khóa chính mình.']); }
    $u = gf_admin_user_payload($id);
    if (!$u) { gf_api_error('NOT_FOUND', 'Không tìm thấy người dùng.', 404); }
    gf_require_known_body($d, ['status'], ['id', 'full_name', 'email', 'phone', 'goal', 'avatar_url', 'role', 'last_login_at', 'created_at']);
    if (!isset($d['status']) || !in_array($d['status'], ['active', 'locked'], true)) { gf_api_error('VALIDATION_ERROR', 'status chỉ active|locked.', 422, ['status' => 'active|locked']); }
    gf_pdo()->prepare('UPDATE users SET status=? WHERE id=?')->execute([(string) $d['status'], $id]);
    return gf_admin_user_payload($id);
}


/**
 * Admin xóa vĩnh viễn user.
 */
function gf_admin_delete_user(int $id, array $admin): void
{
    if ((int) $admin['id'] === $id) { gf_api_error('BUSINESS_RULE', 'Admin không được tự xóa chính mình.', 422, ['id' => 'Không tự xóa chính mình.']); }
    $u = gf_admin_user_payload($id);
    if (!$u) { gf_api_error('NOT_FOUND', 'Không tìm thấy người dùng.', 404); }

    $pdo = gf_pdo();
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare("SELECT target_type, gym_id, trainer_id FROM reviews WHERE user_id=? AND status='approved'");
        $st->execute([$id]);
        $targets = [];
        foreach ($st->fetchAll() as $row) {
            $type = $row['target_type'];
            $tid  = $type === 'gym' ? (int) $row['gym_id'] : (int) $row['trainer_id'];
            $targets[$type . ':' . $tid] = ['type' => $type, 'id' => $tid];
        }
        $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
        foreach ($targets as $t) { gf_recalc_rating($t); }
        $pdo->commit();
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        gf_api_error('SERVER_ERROR', 'Lỗi khi xóa người dùng.', 500);
    }
}
