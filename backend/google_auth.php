<?php
declare(strict_types=1);

function gf_google_config(): array
{
    $google = $GLOBALS['GF_CONFIG']['google'] ?? [];
    return [
        'client_id' => trim((string) ($google['client_id'] ?? '')),
        'client_secret' => trim((string) ($google['client_secret'] ?? '')),
    ];
}

function gf_google_is_configured(): bool
{
    $google = gf_google_config();
    return $google['client_id'] !== '' && $google['client_secret'] !== '';
}

function gf_google_redirect_uri(): string
{
    return gf_absolute_url('pages/auth/google_callback.php');
}

function gf_google_button(string $href, string $label): string
{
    $mark = '<svg class="auth-google-mark" viewBox="0 0 48 48" width="18" height="18" aria-hidden="true">'
        . '<path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/>'
        . '<path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4 16.318 4 9.656 8.337 6.306 14.691z"/>'
        . '<path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238A11.91 11.91 0 0 1 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>'
        . '<path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.04 12.04 0 0 1-4.087 5.571l.003-.002 6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/>'
        . '</svg>';
    return '<a class="auth-btn-google" href="' . gf_h($href) . '">' . $mark . gf_h(gf_t($label)) . '</a>';
}

function gf_google_oauth_error_message(string $code): string
{
    return match ($code) {
        'google_config' => 'Chưa cấu hình đăng nhập Google. Thêm GF_GOOGLE_CLIENT_ID và GF_GOOGLE_CLIENT_SECRET.',
        'google_denied' => 'Bạn đã hủy đăng nhập Google.',
        'google_failed' => 'Không đăng nhập được bằng Google. Vui lòng thử lại.',
        'google_unverified' => 'Email Google chưa được xác minh.',
        default => '',
    };
}

function gf_google_safe_next(string $to): string
{
    $to = trim($to);
    if ($to === '' || str_starts_with($to, '//') || str_contains($to, '\\') || preg_match('/^[a-z][a-z0-9+.-]*:/i', $to)) {
        return '';
    }
    return $to;
}

function gf_google_fail(string $code, string $from, string $next = ''): void
{
    unset($_SESSION['google_oauth']);
    $page = $from === 'register' ? 'pages/auth/register.php' : 'pages/auth/login.php';
    $query = 'error=' . rawurlencode($code);
    $next = gf_google_safe_next($next);
    if ($next !== '') {
        $query .= '&redirect=' . rawurlencode($next);
    }
    gf_redirect(gf_url($page . '?' . $query));
}

/** Bắt đầu OAuth: lưu state + PKCE rồi chuyển sang Google. */
function gf_google_begin(string $from, string $next): void
{
    $from = $from === 'register' ? 'register' : 'login';
    if (!gf_google_is_configured()) {
        gf_google_fail('google_config', $from, $next);
    }
    $verifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $_SESSION['google_oauth'] = [
        'state' => bin2hex(random_bytes(16)),
        'verifier' => $verifier,
        'next' => gf_google_safe_next($next),
        'from' => $from,
        'exp' => time() + 600,
    ];
    $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    $params = [
        'client_id' => gf_google_config()['client_id'],
        'redirect_uri' => gf_google_redirect_uri(),
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'state' => $_SESSION['google_oauth']['state'],
        'code_challenge' => $challenge,
        'code_challenge_method' => 'S256',
        'prompt' => 'select_account',
    ];
    gf_redirect('https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
}

/** Đổi code lấy hồ sơ Google, tạo hoặc mở tài khoản cùng email. */
function gf_google_finish(string $code, string $state): void
{
    $pending = $_SESSION['google_oauth'] ?? null;
    unset($_SESSION['google_oauth']);
    $from = is_array($pending) ? (string) ($pending['from'] ?? 'login') : 'login';
    $next = is_array($pending) ? (string) ($pending['next'] ?? '') : '';
    $verifier = is_array($pending) ? (string) ($pending['verifier'] ?? '') : '';
    $expected = is_array($pending) ? (string) ($pending['state'] ?? '') : '';
    if (
        !is_array($pending)
        || ($pending['exp'] ?? 0) < time()
        || $code === ''
        || $verifier === ''
        || $expected === ''
        || !hash_equals($expected, $state)
    ) {
        gf_google_fail('google_failed', $from, $next);
    }

    try {
        $token = gf_google_http('POST', 'https://oauth2.googleapis.com/token', [
            'Content-Type: application/x-www-form-urlencoded',
        ], http_build_query([
            'code' => $code,
            'client_id' => gf_google_config()['client_id'],
            'client_secret' => gf_google_config()['client_secret'],
            'redirect_uri' => gf_google_redirect_uri(),
            'grant_type' => 'authorization_code',
            'code_verifier' => $verifier,
        ]));
        $access = (string) ($token['json']['access_token'] ?? '');
        if (($token['status'] ?? 0) !== 200 || $access === '') {
            error_log('GYMFINDER Google token exchange failed: HTTP ' . (int) ($token['status'] ?? 0));
            gf_google_fail('google_failed', $from, $next);
        }
        $profile = gf_google_http('GET', 'https://openidconnect.googleapis.com/v1/userinfo', [
            'Authorization: Bearer ' . $access,
        ]);
    } catch (Throwable $e) {
        error_log('GYMFINDER Google OAuth failed: ' . $e->getMessage());
        gf_google_fail('google_failed', $from, $next);
    }

    if (($profile['status'] ?? 0) !== 200) {
        error_log('GYMFINDER Google userinfo failed: HTTP ' . (int) ($profile['status'] ?? 0));
        gf_google_fail('google_failed', $from, $next);
    }
    $verified = $profile['json']['email_verified'] ?? false;
    $emailOk = $verified === true || $verified === 1 || $verified === 'true';
    if (!$emailOk) {
        gf_google_fail('google_unverified', $from, $next);
    }

    $result = gf_google_login_profile(
        (string) ($profile['json']['email'] ?? ''),
        (string) ($profile['json']['name'] ?? ''),
        isset($profile['json']['picture']) ? (string) $profile['json']['picture'] : null
    );
    if (!$result['ok']) {
        if (($result['error'] ?? '') === 'locked') {
            gf_redirect(gf_url('pages/auth/login.php?error=locked'));
        }
        gf_google_fail('google_failed', $from, $next);
    }

    gf_establish_session($result['user'], false);
    gf_redirect(gf_post_login_path($result['user'], $next));
}

function gf_google_login_profile(string $email, string $name, ?string $avatar): array
{
    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        return ['ok' => false, 'error' => 'google_failed'];
    }
    $st = gf_pdo()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $st->execute([$email]);
    $user = $st->fetch();
    if ($user) {
        if (($user['status'] ?? '') === 'locked') {
            return ['ok' => false, 'error' => 'locked'];
        }
        return ['ok' => true, 'user' => $user];
    }

    $name = trim((string) (preg_replace('/\s+/u', ' ', $name) ?? ''));
    if ($name === '') {
        $name = strstr($email, '@', true) ?: 'User';
    }
    $name = mb_substr($name, 0, 100);
    $picture = ($avatar !== null && str_starts_with($avatar, 'https://') && strlen($avatar) <= 255) ? $avatar : null;
    try {
        gf_pdo()->prepare(
            'INSERT INTO users (full_name, email, phone, password_hash, avatar_url, role, status) VALUES (?,?,NULL,?,?,\'user\',\'active\')'
        )->execute([$name, $email, password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT), $picture]);
    } catch (PDOException $e) {
        if ((string) $e->getCode() === '23000') {
            $st->execute([$email]);
            $user = $st->fetch();
            if ($user && ($user['status'] ?? '') !== 'locked') {
                return ['ok' => true, 'user' => $user];
            }
            return ['ok' => false, 'error' => $user ? 'locked' : 'google_failed'];
        }
        throw $e;
    }
    $id = (int) gf_pdo()->lastInsertId();
    $row = gf_pdo()->prepare('SELECT * FROM users WHERE id = ?');
    $row->execute([$id]);
    $created = $row->fetch();
    return $created ? ['ok' => true, 'user' => $created] : ['ok' => false, 'error' => 'google_failed'];
}

/** @return array{status:int,json:array} */
function gf_google_http(string $method, string $url, array $headers = [], ?string $body = null): array
{
    if (!function_exists('curl_init')) {
        throw new RuntimeException('PHP cURL is required for Google sign-in.');
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_POSTFIELDS => $body,
    ]);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);
    if ($raw === false) {
        throw new RuntimeException($err !== '' ? $err : 'Google request failed');
    }
    $json = json_decode($raw, true);
    return ['status' => $status, 'json' => is_array($json) ? $json : []];
}
