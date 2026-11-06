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
            'must_change_password' => (int) ($u['must_change_password'] ?? 0),
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
/**
 * Quản trị viên đang đăng nhập tạo thêm một quản trị viên và gửi mật khẩu tạm qua email.
 * Mật khẩu tạm hết hạn sau 5 phút. Lần đăng nhập đầu bắt buộc đổi mật khẩu.
 */
function gf_admin_invite_admin(string $fullName, string $email): array
{
    $fullName = trim($fullName);
    $email = strtolower(trim($email));
    if ($fullName === '' || mb_strlen($fullName) > 100) {
        return ['ok' => false, 'error' => 'Họ tên hợp lệ là bắt buộc.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255 || preg_match('/[\r\n]/', $email)) {
        return ['ok' => false, 'error' => 'Email không hợp lệ.'];
    }
    if (!gf_smtp_is_configured()) {
        return ['ok' => false, 'error' => 'Chưa cấu hình gửi email, nên chưa tạo được tài khoản.'];
    }
    $exists = gf_pdo()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $exists->execute([$email]);
    if ($exists->fetch()) {
        return ['ok' => false, 'error' => 'Email này đã có tài khoản.'];
    }

    $tempPassword = gf_temporary_password();
    $expiresAt = gf_temp_password_expiry_value(time() + 300);
    try {
        gf_pdo()->prepare(
            'INSERT INTO users (full_name, email, phone, password_hash, role, status, must_change_password, temp_password_expires_at)
             VALUES (?,?,NULL,?,\'admin\',\'active\',1,?)'
        )->execute([$fullName, $email, password_hash($tempPassword, PASSWORD_BCRYPT), $expiresAt]);
        $id = (int) gf_pdo()->lastInsertId();
    } catch (PDOException $e) {
        if ((string) $e->getCode() === '23000') {
            return ['ok' => false, 'error' => 'Email này đã có tài khoản.'];
        }
        throw $e;
    }

    try {
        $sent = gf_send_admin_invite_email($email, $fullName, $tempPassword);
    } catch (Throwable $e) {
        error_log('GYMFINDER admin invite mail failed: ' . $e->getMessage());
        $sent = false;
    }
    if (!$sent) {
        gf_pdo()->prepare('DELETE FROM users WHERE id = ? AND must_change_password = 1')->execute([$id]);
        return ['ok' => false, 'error' => 'Không gửi được email. Tài khoản chưa được tạo.'];
    }
    return ['ok' => true, 'message' => 'Đã gửi email tài khoản quản trị. Mật khẩu tạm có hiệu lực 5 phút.'];
}

/** Issue a new 5-minute password when the invited admin has not finished the first login. */
function gf_admin_resend_temp_password(int $id): array
{
    if (!gf_smtp_is_configured()) {
        return ['ok' => false, 'error' => 'Chưa cấu hình gửi email, nên chưa gửi lại được mật khẩu.'];
    }
    $st = gf_pdo()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $st->execute([$id]);
    $user = $st->fetch();
    if (!$user || ($user['role'] ?? '') !== 'admin' || (int) ($user['must_change_password'] ?? 0) !== 1) {
        return ['ok' => false, 'error' => 'Chỉ gửi lại mật khẩu cho quản trị viên chưa đổi mật khẩu tạm.'];
    }
    $tempPassword = gf_temporary_password();
    $expiresAt = gf_temp_password_expiry_value(time() + 300);
    $previousHash = (string) $user['password_hash'];
    $previousExpiry = $user['temp_password_expires_at'];
    gf_pdo()->prepare('UPDATE users SET password_hash=?, temp_password_expires_at=? WHERE id=?')
        ->execute([password_hash($tempPassword, PASSWORD_BCRYPT), $expiresAt, $id]);
    try {
        $sent = gf_send_admin_invite_email((string) $user['email'], (string) $user['full_name'], $tempPassword);
    } catch (Throwable $e) {
        error_log('GYMFINDER admin resend mail failed: ' . $e->getMessage());
        $sent = false;
    }
    if (!$sent) {
        gf_pdo()->prepare('UPDATE users SET password_hash=?, temp_password_expires_at=? WHERE id=?')
            ->execute([$previousHash, $previousExpiry, $id]);
        return ['ok' => false, 'error' => 'Không gửi được email. Mật khẩu tạm cũ vẫn giữ nguyên.'];
    }
    return ['ok' => true, 'message' => 'Đã gửi lại mật khẩu tạm. Mật khẩu mới có hiệu lực 5 phút.'];
}

/**
 * Chỉ quản trị viên đang đăng nhập được xóa tài khoản quản trị của chính mình.
 * Tài khoản hội viên vẫn chỉ khóa hoặc mở khóa.
 */
function gf_admin_delete_own_account(int $id, array $admin): array
{
    if ((int) ($admin['id'] ?? 0) !== $id) {
        return ['ok' => false, 'error' => 'Chỉ quản trị viên đang đăng nhập được xóa tài khoản của chính mình.'];
    }
    $user = gf_admin_user_payload($id);
    if (!$user || ($user['role'] ?? '') !== 'admin') {
        return ['ok' => false, 'error' => 'Chỉ được xóa tài khoản quản trị của chính mình.'];
    }
    $adminCount = (int) gf_pdo()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    if ($adminCount <= 1) {
        return ['ok' => false, 'error' => 'Không thể xóa quản trị viên cuối cùng.'];
    }
    $pdo = gf_pdo();
    $pdo->prepare('UPDATE reviews SET reviewed_by = NULL WHERE reviewed_by = ?')->execute([$id]);
    $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'")->execute([$id]);
    return ['ok' => true];
}

/**
 * Không xóa tài khoản hội viên. Xóa quản trị chỉ qua gf_admin_delete_own_account.
 */
function gf_admin_delete_user(int $id, array $admin): void
{
    $result = gf_admin_delete_own_account($id, $admin);
    if (!$result['ok']) {
        gf_api_error('BUSINESS_RULE', (string) ($result['error'] ?? 'Không hỗ trợ xóa tài khoản này.'), 422);
    }
}
