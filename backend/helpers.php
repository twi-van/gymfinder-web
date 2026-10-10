<?php
declare(strict_types=1);

function gf_boot_web_root(): void
{
    $root = str_replace('\\', '/', realpath(dirname(__DIR__)) ?: dirname(__DIR__));
    $script = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: ($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $rel = '';
    if ($script && str_starts_with($script, $root)) {
        $rel = trim(str_replace($root, '', dirname($script)), '/');
    }
    $depth = $rel === '' ? 0 : substr_count($rel, '/') + 1;
    $prefix = $depth > 0 ? str_repeat('../', $depth) : '';
    $GLOBALS['GF_PREFIX'] = $prefix;
    $GLOBALS['GF_ROOT'] = $root;
}

function gf_url(string $path = ''): string
{
    $prefix = $GLOBALS['GF_PREFIX'] ?? '';
    return $prefix . ltrim($path, '/');
}

function gf_h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function gf_format_price(?int $min, ?int $max): string
{
    if (!$min && !$max) {
        return 'Liên hệ';
    }
    $fmt = static fn (int $n) => number_format($n, 0, ',', '.') . 'đ';
    if ($min && $max && $min !== $max) {
        return $fmt($min) . ' - ' . $fmt($max) . '/tháng';
    }
    return $fmt((int) ($min ?: $max)) . '/tháng';
}

function gf_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function gf_api_error(string $code, string $message, int $http, array $details = []): void
{
    gf_json(['error' => ['code' => $code, 'message' => $message, 'details' => $details ?: new stdClass()]], $http);
}

function gf_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf_token'];
}

function gf_csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . gf_h(gf_csrf_token()) . '">';
}

function gf_verify_csrf(?string $token = null): bool
{
    $sent = $token ?? (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf'] ?? '');
    return $sent !== '' && hash_equals(gf_csrf_token(), $sent);
}

function gf_require_csrf(): void
{
    if (!gf_verify_csrf()) {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if (str_starts_with($path, '/api')) {
            gf_api_error('CSRF_INVALID', 'Thiếu hoặc sai CSRF token.', 403);
        }
        http_response_code(403);
        echo 'CSRF không hợp lệ. Vui lòng tải lại trang.';
        exit;
    }
}

function gf_slugify(string $name, string $emptyPrefix = 'item'): string
{
    $map = [
        'à'=>'a','á'=>'a','ạ'=>'a','ả'=>'a','ã'=>'a','â'=>'a','ầ'=>'a','ấ'=>'a','ậ'=>'a','ẩ'=>'a','ẫ'=>'a','ă'=>'a','ằ'=>'a','ắ'=>'a','ặ'=>'a','ẳ'=>'a','ẵ'=>'a',
        'è'=>'e','é'=>'e','ẹ'=>'e','ẻ'=>'e','ẽ'=>'e','ê'=>'e','ề'=>'e','ế'=>'e','ệ'=>'e','ể'=>'e','ễ'=>'e',
        'ì'=>'i','í'=>'i','ị'=>'i','ỉ'=>'i','ĩ'=>'i',
        'ò'=>'o','ó'=>'o','ọ'=>'o','ỏ'=>'o','õ'=>'o','ô'=>'o','ồ'=>'o','ố'=>'o','ộ'=>'o','ổ'=>'o','ỗ'=>'o','ơ'=>'o','ờ'=>'o','ớ'=>'o','ợ'=>'o','ở'=>'o','ỡ'=>'o',
        'ù'=>'u','ú'=>'u','ụ'=>'u','ủ'=>'u','ũ'=>'u','ư'=>'u','ừ'=>'u','ứ'=>'u','ự'=>'u','ử'=>'u','ữ'=>'u',
        'ỳ'=>'y','ý'=>'y','ỵ'=>'y','ỷ'=>'y','ỹ'=>'y','đ'=>'d',
        'À'=>'a','Á'=>'a','Ạ'=>'a','Ả'=>'a','Ã'=>'a','Â'=>'a','Ầ'=>'a','Ấ'=>'a','Ậ'=>'a','Ẩ'=>'a','Ẫ'=>'a','Ă'=>'a','Ằ'=>'a','Ắ'=>'a','Ặ'=>'a','Ẳ'=>'a','Ẵ'=>'a',
        'È'=>'e','É'=>'e','Ẹ'=>'e','Ẻ'=>'e','Ẽ'=>'e','Ê'=>'e','Ề'=>'e','Ế'=>'e','Ệ'=>'e','Ể'=>'e','Ễ'=>'e',
        'Ì'=>'i','Í'=>'i','Ị'=>'i','Ỉ'=>'i','Ĩ'=>'i',
        'Ò'=>'o','Ó'=>'o','Ọ'=>'o','Ỏ'=>'o','Õ'=>'o','Ô'=>'o','Ồ'=>'o','Ố'=>'o','Ộ'=>'o','Ổ'=>'o','Ỗ'=>'o','Ơ'=>'o','Ờ'=>'o','Ớ'=>'o','Ợ'=>'o','Ở'=>'o','Ỡ'=>'o',
        'Ù'=>'u','Ú'=>'u','Ụ'=>'u','Ủ'=>'u','Ũ'=>'u','Ư'=>'u','Ừ'=>'u','Ứ'=>'u','Ự'=>'u','Ử'=>'u','Ữ'=>'u',
        'Ỳ'=>'y','Ý'=>'y','Ỵ'=>'y','Ỷ'=>'y','Ỹ'=>'y','Đ'=>'d',
    ];
    $s = strtr(mb_strtolower(trim($name), 'UTF-8'), $map);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?: '';
    $s = trim((string) $s, '-');
    if ($s === '' || preg_match('/^[0-9]+$/', $s)) {
        $s = $emptyPrefix . ($s === '' ? '' : '-' . $s);
        $s = trim($s, '-');
        if ($s === '' || preg_match('/^[0-9]+$/', $s)) {
            $s = $emptyPrefix;
        }
    }
    return $s;
}

function gf_iso(?string $dt): ?string
{
    if ($dt === null || $dt === '') {
        return null;
    }
    $dt = str_replace(' ', 'T', $dt);
    if (!preg_match('/Z$|[+-]\d{2}:\d{2}$/', $dt)) {
        $dt .= 'Z';
    }
    return $dt;
}

function gf_num1(mixed $n): float
{
    return (float) number_format((float) $n, 1, '.', '');
}

function gf_integer_like(mixed $value): bool
{
    if (is_int($value)) {
        return true;
    }
    if (is_float($value)) {
        return is_finite($value) && floor($value) === $value;
    }
    return is_string($value) && preg_match('/^-?\d+$/', trim($value)) === 1;
}

function gf_phone_ok(?string $phone): bool
{
    return $phone === null || $phone === '' || (bool) preg_match('/^\+?[0-9]{9,14}$/', $phone);
}

function gf_password_ok(string $password): bool
{
    $len = strlen($password);
    return $len >= 8 && $len <= 72;
}

function gf_uploads_url_ok(?string $url): bool
{
    if ($url === null || $url === '') {
        return true;
    }
    if (strlen($url) > 255 || str_contains($url, '..')) {
        return false;
    }
    return (bool) preg_match('#^/uploads/[A-Za-z0-9._-]+\.(jpg|jpeg|png|webp)$#i', $url);
}

function gf_like_contains(string $q): string
{
    return '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $q) . '%';
}

function gf_unknown_fields(array $body, array $allowed): array
{
    $details = [];
    foreach (array_keys($body) as $k) {
        if (!in_array((string) $k, $allowed, true)) {
            $details[(string) $k] = 'Field không được định nghĩa.';
        }
    }
    return $details;
}

function gf_readonly_sent(array $body, array $readonly): array
{
    $details = [];
    foreach ($readonly as $k) {
        if (array_key_exists($k, $body)) {
            $details[$k] = 'Field chỉ đọc.';
        }
    }
    return $details;
}

function gf_public_user(?array $u): ?array
{
    if (!$u) {
        return null;
    }
    unset($u['password_hash']);
    return $u;
}

function gf_csv_ints(?string $raw): array
{
    if ($raw === null || trim($raw) === '') {
        return [];
    }
    $out = [];
    foreach (explode(',', $raw) as $p) {
        $p = trim($p);
        if ($p === '' || !ctype_digit($p)) {
            return ['_invalid' => true];
        }
        $out[] = (int) $p;
    }
    return array_values(array_unique($out));
}

function gf_redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function gf_cover_image(?string $url, int $id = 0): string
{
    $stock = [
        1 => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=1200&q=80',
        2 => 'https://images.unsplash.com/photo-1571902943202-507ec2618e8f?auto=format&fit=crop&w=1200&q=80',
        3 => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&w=1200&q=80',
        4 => 'https://images.unsplash.com/photo-1593079831268-3381b0c13c75?auto=format&fit=crop&w=1200&q=80',
        5 => 'https://images.unsplash.com/photo-1549719386-74dfcbf7dbed?auto=format&fit=crop&w=1200&q=80',
        6 => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=1200&q=80',
        7 => 'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?auto=format&fit=crop&w=1200&q=80',
        8 => 'https://images.unsplash.com/photo-1576678927484-8bf88267b0b8?auto=format&fit=crop&w=1200&q=80',
    ];
    if ($url && !str_starts_with($url, '/uploads/')) {
        return $url;
    }
    return $stock[$id] ?? $stock[($id % 8) + 1] ?? $stock[1];
}

function gf_avatar(?string $url, int $id = 0, string $name = 'User'): string
{
    $stock = [
        1 => 'https://images.unsplash.com/photo-1568602471122-7832951cc4c5?auto=format&fit=crop&w=400&q=80',
        2 => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=400&q=80',
        3 => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=400&q=80',
        4 => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=400&q=80',
        5 => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=400&q=80',
        6 => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=400&q=80',
        7 => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=400&q=80',
        8 => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&w=400&q=80',
        9 => 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=400&q=80',
        10 => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=400&q=80',
    ];
    if ($url && !str_starts_with($url, '/uploads/')) {
        return $url;
    }
    if (isset($stock[$id])) {
        return $stock[$id];
    }
    return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=00c9b1&color=fff';
}

function gf_stars(float $rating): string
{
    $full = (int) floor($rating);
    $half = ($rating - $full) >= 0.5;
    $html = '';
    for ($i = 0; $i < $full; $i++) {
        $html .= '<i class="fa-solid fa-star"></i>';
    }
    if ($half) {
        $html .= '<i class="fa-solid fa-star-half-stroke"></i>';
        $full++;
    }
    for ($i = $full; $i < 5; $i++) {
        $html .= '<i class="fa-regular fa-star"></i>';
    }
    return $html;
}

function gf_goal_label(?string $goal): string
{
    return match ($goal) {
        'giam_can' => 'Giảm cân',
        'tang_co' => 'Tăng cơ',
        'tang_suc_manh' => 'Tăng sức mạnh',
        'cai_thien_suc_khoe' => 'Cải thiện sức khỏe',
        default => 'Chưa chọn',
    };
}
