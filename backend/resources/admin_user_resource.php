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
    $u = gf_admin_user_payload($id);
    if (!$u) { gf_api_error('NOT_FOUND', 'Không tìm thấy người dùng.', 404); }
    if (($u['role'] ?? '') !== 'user') {
        gf_api_error('BUSINESS_RULE', 'Chỉ được khóa hoặc mở khóa tài khoản người dùng.', 422, ['status' => 'Không hỗ trợ thay đổi trạng thái tài khoản Admin.']);
    }
    if ((int) $admin['id'] === $id) { gf_api_error('BUSINESS_RULE', 'Admin không được tự khóa chính mình.', 422, ['status' => 'Không tự khóa chính mình.']); }
    gf_require_known_body($d, ['status'], ['id', 'full_name', 'email', 'phone', 'avatar_url', 'role', 'last_login_at', 'created_at']);
    if (!isset($d['status']) || !in_array($d['status'], ['active', 'locked'], true)) { gf_api_error('VALIDATION_ERROR', 'status chỉ active|locked.', 422, ['status' => 'active|locked']); }
    gf_pdo()->prepare('UPDATE users SET status=? WHERE id=?')->execute([(string) $d['status'], $id]);
    return gf_admin_user_payload($id);
}


/**
 * Không còn hỗ trợ xóa cứng user (khớp đặc tả: chỉ khóa / mở khóa).
 * Giữ hàm để tránh lỗi call cũ — luôn trả BUSINESS_RULE.
 */
function gf_admin_delete_user(int $id, array $admin): void
{
    unset($id, $admin);
    gf_api_error(
        'BUSINESS_RULE',
        'Không hỗ trợ xóa tài khoản. Dùng khóa / mở khóa (status active|locked).',
        405
    );
}
