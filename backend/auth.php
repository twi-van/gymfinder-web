<?php
declare(strict_types=1);

function gf_current_user(): ?array
{
    $id = $_SESSION['user_id'] ?? null;
    if (!$id) {
        return null;
    }
    $st = gf_pdo()->prepare('SELECT id, full_name, email, phone, avatar_url, role, status, created_at, updated_at FROM users WHERE id = ?');
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

function gf_login(string $login, string $password, bool $remember = false): array
{
    $raw = trim($login);
    if ($raw === '') {
        return ['ok' => false, 'error' => 'Email hoặc mật khẩu không đúng.', 'code' => 'INVALID_CREDENTIALS'];
    }
    if (str_contains($raw, '@')) {
        $st = gf_pdo()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $st->execute([strtolower($raw)]);
    } else {
        $phone = preg_replace('/\s+/', '', $raw) ?? $raw;
        $st = gf_pdo()->prepare('SELECT * FROM users WHERE phone = ? LIMIT 1');
        $st->execute([$phone]);
    }
    $user = $st->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['ok' => false, 'error' => 'Email/số điện thoại hoặc mật khẩu không đúng.', 'code' => 'INVALID_CREDENTIALS'];
    }
    if ($user['status'] === 'locked') {
        return ['ok' => false, 'error' => 'Tài khoản đã bị khóa.', 'code' => 'ACCOUNT_LOCKED'];
    }
    if (!headers_sent()) {
        session_regenerate_id(true);
    }
    $_SESSION['user_id'] = (int) $user['id'];
    gf_apply_remember_cookie($remember);
    gf_pdo()->prepare('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([(int) $user['id']]);
    return ['ok' => true, 'user' => $user];
}

/**
 * Gia hạn cookie phiên khi bật "Ghi nhớ đăng nhập" (30 ngày), hoặc cookie phiên trình duyệt khi tắt.
 */
function gf_apply_remember_cookie(bool $remember): void
{
    if (headers_sent()) {
        return;
    }
    $params = session_get_cookie_params();
    $lifetime = $remember ? 60 * 60 * 24 * 30 : 0;
    setcookie(session_name(), session_id(), [
        'expires'  => $lifetime > 0 ? time() + $lifetime : 0,
        'path'     => $params['path'] ?: '/',
        'secure'   => !empty($params['secure']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function gf_logout(): void
{
    unset($_SESSION['user_id']);
    if (!headers_sent()) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 3600,
            'path'     => $params['path'] ?: '/',
            'secure'   => !empty($params['secure']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_regenerate_id(true);
    }
}

function gf_register(string $fullName, string $email, string $password, ?string $phone = null): array
{
    $fullName = trim($fullName);
    $email = strtolower(trim($email));
    $phone = $phone !== null && trim($phone) !== '' ? (preg_replace('/\s+/', '', trim($phone)) ?? trim($phone)) : null;
    if ($fullName === '' || mb_strlen($fullName) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        return ['ok' => false, 'error' => 'Họ tên và email hợp lệ là bắt buộc.', 'code' => 'VALIDATION_ERROR'];
    }
    if (!gf_password_ok($password)) {
        return ['ok' => false, 'error' => 'Mật khẩu phải từ 8 đến 72 byte UTF-8.', 'code' => 'VALIDATION_ERROR'];
    }
    if ($phone !== null && !preg_match('/^\+?[0-9]{9,14}$/', $phone)) {
        return ['ok' => false, 'error' => 'Số điện thoại không hợp lệ.', 'code' => 'VALIDATION_ERROR'];
    }
    $hash = password_hash($password, PASSWORD_BCRYPT);
    try {
        $st = gf_pdo()->prepare(
            'INSERT INTO users (full_name, email, phone, password_hash, role, status) VALUES (?,?,?,?,\'user\',\'active\')'
        );
        $st->execute([$fullName, $email, $phone, $hash]);
    } catch (PDOException $e) {
        if ((string) $e->getCode() === '23000') {
            return ['ok' => false, 'error' => 'Email đã được sử dụng.', 'code' => 'DUPLICATE_EMAIL'];
        }
        throw $e;
    }
    $id = (int) gf_pdo()->lastInsertId();
    $row = gf_pdo()->prepare('SELECT id, full_name, email, phone, role, status, avatar_url, created_at FROM users WHERE id=?');
    $row->execute([$id]);
    return ['ok' => true, 'user' => $row->fetch()];
}

function gf_update_profile(int $userId, array $data): void
{
    $fields = [];
    $params = [];
    foreach (['full_name', 'phone', 'avatar_url'] as $col) {
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

/**
 * Quên mật khẩu bằng OTP email (6 số).
 * OTP lưu bảng password_reset_otps; session chỉ giữ id yêu cầu + trạng thái UI.
 * Gửi SMTP chưa bắt buộc — demo_otp hiển thị tạm trên UI khi chưa cấu hình thư.
 */
function gf_password_reset_clear(): void
{
    unset($_SESSION['pw_reset']);
}

function gf_password_reset_state(): ?array
{
    $state = $_SESSION['pw_reset'] ?? null;
    return is_array($state) ? $state : null;
}

function gf_find_user_by_login(string $login): ?array
{
    $raw = trim($login);
    if ($raw === '') {
        return null;
    }
    if (str_contains($raw, '@')) {
        $st = gf_pdo()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $st->execute([strtolower($raw)]);
    } else {
        $phone = preg_replace('/\s+/', '', $raw) ?? $raw;
        $st = gf_pdo()->prepare('SELECT * FROM users WHERE phone = ? LIMIT 1');
        $st->execute([$phone]);
    }
    $user = $st->fetch();
    return $user ?: null;
}

function gf_password_reset_mask_login(string $login, ?array $user): string
{
    if ($user && !empty($user['email']) && str_contains((string) $user['email'], '@')) {
        $email = (string) $user['email'];
        $parts = explode('@', $email, 2);
        $name = $parts[0];
        $domain = $parts[1] ?? '';
        $visible = mb_substr($name, 0, min(2, mb_strlen($name)));
        return $visible . str_repeat('*', max(1, mb_strlen($name) - mb_strlen($visible))) . '@' . $domain;
    }
    return $login;
}

function gf_password_reset_begin(string $login): array
{
    // Quên mật khẩu chỉ qua email OTP (không SMS / không tra theo SĐT).
    $login = strtolower(trim($login));
    if ($login === '' || mb_strlen($login) > 255 || !filter_var($login, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Vui lòng nhập email đã đăng ký để nhận mã OTP.'];
    }

    $st = gf_pdo()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $st->execute([$login]);
    $user = $st->fetch() ?: null;
    $mask = gf_password_reset_mask_login($login, $user);
    $expiresTs = time() + 600;
    $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $resetId = null;
    $demoOtp = null;

    if ($user) {
        $userId = (int) $user['id'];
        // Vô hiệu hóa OTP cũ chưa dùng của user
        gf_pdo()->prepare(
            'UPDATE password_reset_otps SET consumed_at = CURRENT_TIMESTAMP
             WHERE user_id = ? AND consumed_at IS NULL'
        )->execute([$userId]);

        $driver = gf_pdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
        $expiresAt = $driver === 'sqlite'
            ? gmdate('Y-m-d H:i:s', $expiresTs)
            : date('Y-m-d H:i:s', $expiresTs);

        gf_pdo()->prepare(
            'INSERT INTO password_reset_otps (user_id, otp_hash, attempts, expires_at)
             VALUES (?,?,0,?)'
        )->execute([$userId, password_hash($otp, PASSWORD_DEFAULT), $expiresAt]);
        $resetId = (int) gf_pdo()->lastInsertId();
        $demoOtp = $otp; // UI demo cho đến khi gửi email SMTP
    }

    $_SESSION['pw_reset'] = [
        'reset_id'      => $resetId,
        'login_display' => $login,
        'mask'          => $mask,
        'user_id'       => $user ? (int) $user['id'] : null,
        'demo_otp'      => $demoOtp,
        'expires'       => $expiresTs,
        'verified'      => false,
    ];

    return ['ok' => true, 'mask' => $mask];
}

function gf_password_reset_verify_otp(string $otp): array
{
    $state = gf_password_reset_state();
    if (!$state) {
        return ['ok' => false, 'error' => 'Phiên đặt lại mật khẩu đã hết hạn. Vui lòng gửi lại yêu cầu.', 'code' => 'EXPIRED'];
    }
    if (($state['expires'] ?? 0) < time()) {
        gf_password_reset_clear();
        return ['ok' => false, 'error' => 'Mã OTP đã hết hạn. Vui lòng gửi lại yêu cầu.', 'code' => 'EXPIRED'];
    }
    $otp = preg_replace('/\s+/', '', $otp) ?? '';
    if (!preg_match('/^\d{6}$/', $otp)) {
        return ['ok' => false, 'error' => 'Vui lòng nhập đủ 6 chữ số OTP.'];
    }

    $resetId = (int) ($state['reset_id'] ?? 0);
    if ($resetId <= 0) {
        // Không lộ việc tài khoản không tồn tại
        return ['ok' => false, 'error' => 'Mã OTP không đúng. Bạn còn tối đa 5 lần thử.'];
    }

    $st = gf_pdo()->prepare(
        'SELECT * FROM password_reset_otps WHERE id = ? AND consumed_at IS NULL LIMIT 1'
    );
    $st->execute([$resetId]);
    $row = $st->fetch();
    if (!$row) {
        gf_password_reset_clear();
        return ['ok' => false, 'error' => 'Yêu cầu OTP không còn hiệu lực. Vui lòng gửi lại.', 'code' => 'EXPIRED'];
    }

    $expiresAt = strtotime((string) $row['expires_at']);
    if ($expiresAt !== false && $expiresAt < time()) {
        gf_password_reset_clear();
        return ['ok' => false, 'error' => 'Mã OTP đã hết hạn. Vui lòng gửi lại yêu cầu.', 'code' => 'EXPIRED'];
    }

    $attempts = (int) $row['attempts'];
    if ($attempts >= 5) {
        gf_pdo()->prepare('UPDATE password_reset_otps SET consumed_at = CURRENT_TIMESTAMP WHERE id = ?')
            ->execute([$resetId]);
        gf_password_reset_clear();
        return ['ok' => false, 'error' => 'Bạn đã nhập sai quá nhiều lần. Vui lòng gửi lại yêu cầu.', 'code' => 'LOCKED'];
    }

    if (!password_verify($otp, (string) $row['otp_hash'])) {
        $attempts++;
        gf_pdo()->prepare('UPDATE password_reset_otps SET attempts = ? WHERE id = ?')
            ->execute([$attempts, $resetId]);
        $left = 5 - $attempts;
        if ($left <= 0) {
            gf_pdo()->prepare('UPDATE password_reset_otps SET consumed_at = CURRENT_TIMESTAMP WHERE id = ?')
                ->execute([$resetId]);
            gf_password_reset_clear();
            return ['ok' => false, 'error' => 'Bạn đã nhập sai quá nhiều lần. Vui lòng gửi lại yêu cầu.', 'code' => 'LOCKED'];
        }
        return ['ok' => false, 'error' => "Mã OTP không đúng. Bạn còn {$left} lần thử."];
    }

    gf_pdo()->prepare('UPDATE password_reset_otps SET verified_at = CURRENT_TIMESTAMP WHERE id = ?')
        ->execute([$resetId]);
    $_SESSION['pw_reset']['verified'] = true;
    unset($_SESSION['pw_reset']['demo_otp']);
    return ['ok' => true];
}

function gf_password_reset_complete(string $password, string $confirm): array
{
    $state = gf_password_reset_state();
    if (!$state || empty($state['verified']) || empty($state['user_id']) || empty($state['reset_id'])) {
        return ['ok' => false, 'error' => 'Phiên đặt lại mật khẩu không hợp lệ. Vui lòng bắt đầu lại.', 'code' => 'INVALID'];
    }
    if (($state['expires'] ?? 0) < time()) {
        gf_password_reset_clear();
        return ['ok' => false, 'error' => 'Phiên đã hết hạn. Vui lòng gửi lại yêu cầu.', 'code' => 'EXPIRED'];
    }
    if ($password !== $confirm) {
        return ['ok' => false, 'error' => 'Xác nhận mật khẩu không khớp.'];
    }
    if (!gf_password_ok($password)) {
        return ['ok' => false, 'error' => 'Mật khẩu mới phải từ 8 đến 72 byte UTF-8.'];
    }

    $resetId = (int) $state['reset_id'];
    $userId = (int) $state['user_id'];
    $st = gf_pdo()->prepare(
        'SELECT id FROM password_reset_otps
         WHERE id = ? AND user_id = ? AND verified_at IS NOT NULL AND consumed_at IS NULL
         LIMIT 1'
    );
    $st->execute([$resetId, $userId]);
    if (!$st->fetch()) {
        gf_password_reset_clear();
        return ['ok' => false, 'error' => 'Yêu cầu OTP không còn hiệu lực. Vui lòng bắt đầu lại.', 'code' => 'INVALID'];
    }

    gf_pdo()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
        ->execute([password_hash($password, PASSWORD_BCRYPT), $userId]);
    gf_pdo()->prepare('UPDATE password_reset_otps SET consumed_at = CURRENT_TIMESTAMP WHERE id = ?')
        ->execute([$resetId]);
    gf_password_reset_clear();
    return ['ok' => true];
}
