<?php
declare(strict_types=1);

function gf_current_user(): ?array
{
    $id = $_SESSION['user_id'] ?? null;
    if (!$id) {
        return null;
    }
    $st = gf_pdo()->prepare('SELECT id, full_name, email, phone, avatar_url, goal, role, status, created_at, updated_at FROM users WHERE id = ?');
    $st->execute([(int) $id]);
    $user = $st->fetch() ?: null;
    return $user ?: null;
}

function gf_require_login(?string $redirect = null): array
{
    $user = gf_current_user();
    if (!$user) {
        $next = $redirect ?? ($_SERVER['REQUEST_URI'] ?? gf_url('index.php'));
        gf_redirect(gf_url('pages/auth/login.php?redirect=' . urlencode($next)));
    }
    if (($user['status'] ?? '') === 'locked') {
        unset($_SESSION['user_id']);
        gf_redirect(gf_url('pages/auth/login.php?error=locked'));
    }
    return $user;
}

function gf_require_admin(): array
{
    $user = gf_require_login(gf_url('pages/auth/login.php'));
    if (($user['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo 'Bạn không có quyền truy cập khu vực quản trị.';
        exit;
    }
    return $user;
}

function gf_login(string $login, string $password): array
{
    $email = strtolower(trim($login));
    $st = gf_pdo()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $user = $st->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['ok' => false, 'error' => 'Email hoặc mật khẩu không đúng.', 'code' => 'INVALID_CREDENTIALS'];
    }
    if ($user['status'] === 'locked') {
        return ['ok' => false, 'error' => 'Tài khoản đã bị khóa.', 'code' => 'ACCOUNT_LOCKED'];
    }
    if (!headers_sent()) {
        session_regenerate_id(true);
    }
    $_SESSION['user_id'] = (int) $user['id'];
    gf_pdo()->prepare('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([(int) $user['id']]);
    return ['ok' => true, 'user' => $user];
}

function gf_logout(): void
{
    unset($_SESSION['user_id']);
    if (!headers_sent()) {
        session_regenerate_id(true);
    }
}

function gf_register(string $fullName, string $email, string $password, ?string $phone = null, ?string $goal = null): array
{
    $fullName = trim($fullName);
    $email = strtolower(trim($email));
    $phone = $phone !== null && trim($phone) !== '' ? trim($phone) : null;
    $goals = ['giam_can', 'tang_co', 'tang_suc_manh', 'cai_thien_suc_khoe'];
    if ($fullName === '' || mb_strlen($fullName) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        return ['ok' => false, 'error' => 'Họ tên và email hợp lệ là bắt buộc.', 'code' => 'VALIDATION_ERROR'];
    }
    if (!gf_password_ok($password)) {
        return ['ok' => false, 'error' => 'Mật khẩu phải từ 8 đến 72 byte UTF-8.', 'code' => 'VALIDATION_ERROR'];
    }
    if ($phone !== null && !preg_match('/^\+?[0-9]{9,14}$/', $phone)) {
        return ['ok' => false, 'error' => 'Số điện thoại không hợp lệ.', 'code' => 'VALIDATION_ERROR'];
    }
    if ($goal !== null && $goal !== '' && !in_array($goal, $goals, true)) {
        return ['ok' => false, 'error' => 'Mục tiêu không hợp lệ.', 'code' => 'VALIDATION_ERROR'];
    }
    $hash = password_hash($password, PASSWORD_BCRYPT);
    try {
        $st = gf_pdo()->prepare(
            'INSERT INTO users (full_name, email, phone, password_hash, goal, role, status) VALUES (?,?,?,?,?,\'user\',\'active\')'
        );
        $st->execute([$fullName, $email, $phone, $hash, $goal ?: null]);
    } catch (PDOException $e) {
        if ((string) $e->getCode() === '23000') {
            return ['ok' => false, 'error' => 'Email đã được sử dụng.', 'code' => 'DUPLICATE_EMAIL'];
        }
        throw $e;
    }
    $id = (int) gf_pdo()->lastInsertId();
    $row = gf_pdo()->prepare('SELECT id, full_name, email, phone, goal, role, status, avatar_url, created_at FROM users WHERE id=?');
    $row->execute([$id]);
    return ['ok' => true, 'user' => $row->fetch()];
}

function gf_update_profile(int $userId, array $data): void
{
    $fields = [];
    $params = [];
    foreach (['full_name', 'phone', 'goal', 'avatar_url'] as $col) {
        if (array_key_exists($col, $data)) {
            $fields[] = "$col = ?";
            $val = $data[$col];
            if (is_string($val)) {
                $val = trim($val);
            }
            if ($col !== 'full_name' && $val === '') {
                $val = null;
            }
            $params[] = $val;
        }
    }
    if (!$fields) {
        return;
    }
    $params[] = $userId;
    gf_pdo()->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($params);
}

function gf_change_password(int $userId, string $current, string $new): array
{
    $st = gf_pdo()->prepare('SELECT password_hash FROM users WHERE id=?');
    $st->execute([$userId]);
    $hash = (string) $st->fetchColumn();
    if (!password_verify($current, $hash)) {
        return ['ok' => false, 'error' => 'Mật khẩu hiện tại không đúng.'];
    }
    if (!gf_password_ok($new)) {
        return ['ok' => false, 'error' => 'Mật khẩu mới phải từ 8 đến 72 byte UTF-8.'];
    }
    if ($current === $new) {
        return ['ok' => false, 'error' => 'Mật khẩu mới phải khác mật khẩu hiện tại.'];
    }
    gf_pdo()->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($new, PASSWORD_BCRYPT), $userId]);
    return ['ok' => true];
}
