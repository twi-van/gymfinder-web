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

/** Origin công khai (https://host), có tính proxy Railway. */
function gf_request_origin(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    $host = (string) ($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? 'localhost');
    $host = trim(explode(',', $host)[0]);
    if (!preg_match('/^[A-Za-z0-9.-]+(?::\d+)?$/', $host)) {
        $host = 'localhost';
    }
    return ($https ? 'https' : 'http') . '://' . $host;
}

/** URL tuyệt đối của một path trong app, dùng cho redirect OAuth. */
function gf_absolute_url(string $path): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $dir = trim(dirname($script), '/');
    $root = str_replace('\\', '/', (string) ($GLOBALS['GF_ROOT'] ?? ''));
    $file = str_replace('\\', '/', (string) (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: ''));
    $rel = '';
    if ($root !== '' && $file !== '' && str_starts_with($file, $root)) {
        $rel = trim(str_replace($root, '', dirname($file)), '/');
    }
    $depth = $rel === '' ? 0 : substr_count($rel, '/') + 1;
    $parts = ($dir === '' || $dir === '.') ? [] : explode('/', $dir);
    if ($depth > 0 && count($parts) >= $depth) {
        $parts = array_slice($parts, 0, -$depth);
    }
    $base = implode('/', $parts);
    $prefix = $base === '' ? '' : '/' . $base;
    return gf_request_origin() . $prefix . '/' . ltrim($path, '/');
}

function gf_h(?string $s): string
{
    $text = (string) $s;
    if (function_exists('gf_t')) {
        $text = gf_t($text);
    }
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function gf_format_price(?int $min, ?int $max): string
{
    if (!$min && !$max) {
        return function_exists('gf_t') ? gf_t('Liên hệ') : 'Liên hệ';
    }
    $en = function_exists('gf_lang') && gf_lang() === 'en';
    $fmt = static function (int $n) use ($en): string {
        if ($en) {
            return number_format($n, 0, '.', ',') . ' VND';
        }
        return number_format($n, 0, ',', '.') . 'đ';
    };
    $per = $en ? '/month' : '/tháng';
    if ($min && $max && $min !== $max) {
        return $fmt($min) . ' - ' . $fmt($max) . $per;
    }
    return $fmt((int) ($min ?: $max)) . $per;
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
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $isApi = preg_match('#(?:^|/)api(?:/|$)#', $path) === 1;
    $isAdminFormPost = !$isApi
        && preg_match('#(?:^|/)admin/pages/#', $path) === 1
        && strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST';

    // Admin pages are HTML forms; keep domain errors in the rendered UI instead
    // of returning a raw JSON document as the browser page.
    if ($isAdminFormPost && session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['gf_flash'] = [
            'type' => $http >= 500 ? 'danger' : 'warning',
            'message' => $message,
        ];
        $query = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
        gf_redirect($path . ($query ? '?' . $query : ''));
    }

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

function gf_resolve_image_url(?string $url): ?string
{
    if ($url === null) {
        return null;
    }
    $url = trim($url);
    if ($url === '') {
        return null;
    }

    // 1. Đường dẫn tĩnh thuộc assets/images/trainers hoặc URL gốc của HLV demo -> ưu tiên bản lưu cục bộ
    foreach (gf_demo_trainer_avatars() as $tId => $demoUrl) {
        if ($url === $demoUrl) {
            $localRel = gf_demo_trainer_local_path($tId);
            if ($localRel !== null) {
                return gf_url($localRel);
            }
            return $demoUrl;
        }
    }
    if (preg_match('#^/?assets/images/trainers/(trainer-(?:[1-9]|[12][0-9]|30)\.(?:webp|jpg|jpeg|png))$#i', $url, $tm)) {
        $root = $GLOBALS['GF_ROOT'] ?? dirname(__DIR__);
        $rel = 'assets/images/trainers/' . $tm[1];
        if (is_file($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel))) {
            return gf_url($rel);
        }
    }

    // 2. URL bên ngoài hợp lệ (http / https)
    if (preg_match('#^https?://#i', $url)) {
        if (str_contains($url, '1577221084712-45b0449d2d3f')) {
            return null;
        }
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }

    // 2. Không cho phép path traversal
    if (str_contains($url, '..')) {
        return null;
    }

    // 3. Đường dẫn thuộc thư mục uploads do hệ thống quản lý
    if (!preg_match('#^/?uploads/([A-Za-z0-9._-]+\.(?:jpg|jpeg|png|webp))$#i', $url, $m)) {
        return null;
    }

    $filename = $m[1];
    $root = $GLOBALS['GF_ROOT'] ?? dirname(__DIR__);
    $uploadDir = $root . DIRECTORY_SEPARATOR . 'uploads';
    $filePath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

    if (!is_file($filePath) || !@getimagesize($filePath)) {
        return null;
    }

    $realUploadDir = realpath($uploadDir);
    $realFile = realpath($filePath);
    if (!$realUploadDir || !$realFile) {
        return null;
    }

    $normUploadDir = rtrim(str_replace('\\', '/', $realUploadDir), '/');
    $normFile = str_replace('\\', '/', $realFile);
    if (!str_starts_with($normFile, $normUploadDir . '/')) {
        return null;
    }

    return gf_url('uploads/' . $filename);
}

function gf_demo_gym_photos(): array
{
    return [
        'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1540497077202-7c8a3999166f?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1574680096145-d05b474e2155?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1593079831268-3381b0db4a77?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1579758629938-03607ccdbaba?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1599058917212-d750089bc07e?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1599058945522-28d584b6f0ff?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1558611848-73f7eb4001a1?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1590487988256-9ed24133863e?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1549719386-74dfcbf7dbed?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1571902943202-507ec2618e8f?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1534258936925-c58bed479fcb?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1728486145245-d4cb0c9c3470?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1738321791421-232f9ee2c487?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1545612036-2872840642dc?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1758448756350-3d0eec02ba37?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1778828494354-9b717d36dc99?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1671970922029-0430d2ae122c?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1775993719568-290840203239?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1775993167276-743bbcde77e1?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1546920120-d60663fd4965?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1778828450059-f39d5bbb01af?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1689877020200-403d8542d95d?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1561214078-f3247647fc5e?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1757924284732-4189190321cf?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1728486887318-398f1448f7ae?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1775993703558-e7afab02b7bd?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1754258167836-6878c54e316c?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1771586791190-97ed536c54af?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1649068618811-9f3547ef98fc?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1741156229623-da94e6d7977d?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1765728617086-5d16a7bac916?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1775993167571-cd1ff4cadada?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1778828494365-c798ff9cce2c?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1689514226761-336eaf77e311?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1623874514711-0f321325f318?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1775993699105-4d18bcac7e54?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1761971974992-6df33df97c3a?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1770493895453-4f758c40d11d?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1687350119840-3e2cc5977e92?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1716367840427-4c148eb7bf15?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1790094181041-add24d34ccfd?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1649847727298-27a50354bd46?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1714181871329-1392197011c2?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1790094180961-8396479c6542?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1632758243488-7e6f9173cfa1?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1675026482188-8102367ecc16?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1761839258420-5c3e2f2e2a74?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1771270786606-f5a0e57db762?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1434682772747-f16d3ea162c3?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1630703178161-1e2f9beddbf8?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1624513764372-a4eb7b334c62?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1521804906057-1df8fdb718b7?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1789629455545-6418611bb5a2?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1770513649465-2c60c8039806?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1729156638758-6fa6405b289b?auto=format&fit=crop&w=800&q=80',
    ];
}

function gf_demo_gym_cover_photos(): array
{
    return array_map(
        static fn(string $url): string => str_replace('w=800', 'w=1200', $url),
        gf_demo_gym_photos()
    );
}

function gf_demo_gym_initial_gallery_photo(int $demoNum): string
{
    $photos = gf_demo_gym_photos();
    return match ($demoNum) {
        61 => $photos[1],
        62 => $photos[2],
        63 => $photos[4],
        64 => $photos[7],
        default => $photos[(max(1, $demoNum) + 2) % 8],
    };
}

function gf_cover_image(?string $url, int $id = 0): string
{
    $resolved = gf_resolve_image_url($url);
    if ($resolved !== null) {
        return $resolved;
    }

    $stock = gf_demo_gym_cover_photos();
    $count = count($stock);
    if ($id > 0) {
        return $stock[($id - 1) % $count];
    }
    return $stock[0];
}

function gf_demo_trainer_avatars(): array
{
    return [
        1 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2502497.webp?update_time=2025-11-27+03%3A30%3A05.737',
        2 => 'https://images.squarespace-cdn.com/content/v1/62e39313a3615b384cc19035/52a6f1e4-8e7c-40a3-b0c6-14a8bb0c05a2/xiu%2Bting.jpg',
        3 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2300326.webp?update_time=2025-06-21+08%3A48%3A01.600',
        4 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/700096.webp?update_time=2025-06-14+03%3A31%3A55.310',
        5 => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=400&q=80',
        6 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2405184.webp?update_time=2025-06-14+03%3A31%3A55.120',
        7 => 'https://tsquaredlab.com/images/trainers/haz.webp',
        8 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2101491.webp?update_time=2025-05-31+03%3A30%3A36.813',
        9 => 'https://static.wixstatic.com/media/3712cb_75431a0dff05471281ca426eefc1f808~mv2.jpg/v1/fill/w_980%2Ch_1307%2Cal_c%2Cq_85%2Cusm_0.66_1.00_0.01/3712cb_75431a0dff05471281ca426eefc1f808~mv2.jpg',
        10 => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=400&q=80',
        11 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2103599.webp?update_time=2025-05-29+03%3A31%3A37.583',
        12 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2405272.webp?update_time=2025-06-21+08%3A48%3A02.017',
        13 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2002233.webp?update_time=2025-07-31+03%3A30%3A07.820',
        14 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2502551.webp?update_time=2025-08-05+03%3A30%3A09.267',
        15 => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=400&q=80',
        16 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/790154.webp?update_time=2025-11-06+03%3A30%3A16.210',
        17 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2500506.webp?update_time=2025-05-31+03%3A30%3A36.857',
        18 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2402622.webp?update_time=2025-06-19+22%3A05%3A07.700',
        19 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2504511.webp?update_time=2026-02-15+03%3A30%3A11.880',
        20 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2302959.webp?update_time=2025-06-05+03%3A33%3A39.200',
        21 => 'https://images.squarespace-cdn.com/content/v1/588754e7bebafbcb2d477cbf/1759978920243-J0GHN0IZ0LQKLMY4Z3WN/RACH1.jpg',
        22 => 'https://www.workband.com.tw/store_image/forcefitness/P1174540170056.webp',
        23 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/370451.webp?update_time=2026-04-22+03%3A30%3A08.133',
        24 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2500840.webp?update_time=2026-04-22+03%3A30%3A08.127',
        25 => 'https://pure360-public.s3.ap-southeast-1.amazonaws.com/teacher_image_dev/1900.jpg?t=1663933548000',
        26 => 'https://stringssg-assets.s3-ap-southeast-1.amazonaws.com/static/personal-trainer/generated/Christabelle.jpeg',
        27 => 'https://images.squarespace-cdn.com/content/v1/63d9d5adb0b1063f7fa463a2/40365611-db3b-4e8c-9566-f14d5d3e2e6f/copy_AF8E7F50-5222-492E-A210-BFB5553B71CF.JPEG',
        28 => 'https://unrealpt.sg/cdn/shop/files/Hwa_Yang_Soh_Senior_Trainer.jpg?v=1726637029&width=2000',
        29 => 'https://images.squarespace-cdn.com/content/v1/66668442d380464892620714/97994512-76a2-433b-8e6c-dcc2dbc6d6e3/IMG_8221.jpg',
        30 => 'https://www.stringssg.com/_next/image?q=75&url=https%3A%2F%2Fstringssg-assets.s3.ap-southeast-1.amazonaws.com%2Fstatic%2Fpersonal-trainer%2Fgenerated%2FGerald.jpeg&w=1920',
    ];
}

function gf_demo_trainer_local_path(int $id): ?string
{
    if ($id < 1 || $id > 30) {
        return null;
    }
    $root = $GLOBALS['GF_ROOT'] ?? dirname(__DIR__);
    foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
        $rel = "assets/images/trainers/trainer-{$id}.{$ext}";
        $full = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
        if (is_file($full)) {
            return $rel;
        }
    }
    return null;
}

function gf_avatar_fallback(string $name = 'User'): string
{
    $clean = trim($name) !== '' ? trim($name) : 'User';
    return 'https://ui-avatars.com/api/?name=' . urlencode($clean) . '&background=00c9b1&color=fff';
}

function gf_avatar(?string $url, int $id = 0, string $name = 'User'): string
{
    $resolved = gf_resolve_image_url($url);
    if ($resolved !== null) {
        return $resolved;
    }

    if ($id > 0) {
        $localRel = gf_demo_trainer_local_path($id);
        if ($localRel !== null) {
            return gf_url($localRel);
        }
        $stock = gf_demo_trainer_avatars();
        if (isset($stock[$id])) {
            return $stock[$id];
        }
    }
    return gf_avatar_fallback($name);
}

function gf_gym_image_url(?string $url, int $fallbackId = 1): string
{
    return gf_cover_image($url, $fallbackId);
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

/**
 * Chuyển đổi mã vai trò người dùng sang nhãn hiển thị tiếng Việt.
 */
function gf_role_label(?string $role): string
{
    return match ($role) {
        'admin' => 'Quản trị viên',
        default => 'Người dùng',
    };
}

/**
 * Kiểm tra xem một URL ảnh upload có đang được tham chiếu bởi bất kỳ bản ghi nào trong hệ thống hay không.
 */
function gf_is_image_referenced(string $url): bool
{
    $pdo = gf_pdo();

    $st1 = $pdo->prepare('SELECT 1 FROM users WHERE avatar_url = ? LIMIT 1');
    $st1->execute([$url]);
    if ($st1->fetchColumn()) {
        return true;
    }

    $st2 = $pdo->prepare('SELECT 1 FROM gyms WHERE cover_image_url = ? LIMIT 1');
    $st2->execute([$url]);
    if ($st2->fetchColumn()) {
        return true;
    }

    $st3 = $pdo->prepare('SELECT 1 FROM gym_images WHERE image_url = ? LIMIT 1');
    $st3->execute([$url]);
    if ($st3->fetchColumn()) {
        return true;
    }

    $st4 = $pdo->prepare('SELECT 1 FROM trainers WHERE avatar_url = ? LIMIT 1');
    $st4->execute([$url]);
    if ($st4->fetchColumn()) {
        return true;
    }

    return false;
}

/**
 * Xóa an toàn các file upload không còn được tham chiếu trong hệ thống.
 * Chỉ xóa nếu:
 * 1. Thuộc định dạng /uploads/... hợp lệ.
 * 2. Nằm trong thư mục uploads của dự án (không vượt quyền, không phải static asset).
 * 3. Không còn bất kỳ bản ghi nào trong database tham chiếu đến file đó.
 */
function gf_delete_unreferenced_files(array $urls): void
{
    $root = $GLOBALS['GF_ROOT'] ?? dirname(__DIR__);
    $uploadDir = realpath($root . DIRECTORY_SEPARATOR . 'uploads');
    if (!$uploadDir) {
        return;
    }
    $normUploadDir = rtrim(str_replace('\\', '/', $uploadDir), '/');

    foreach (array_unique(array_filter($urls)) as $url) {
        $urlStr = (string) $url;
        if (!gf_uploads_url_ok($urlStr)) {
            continue;
        }

        $path = $root . str_replace('/', DIRECTORY_SEPARATOR, $urlStr);
        $realPath = realpath($path);
        if (!$realPath || !is_file($realPath)) {
            continue;
        }

        $normPath = str_replace('\\', '/', $realPath);
        if (!str_starts_with($normPath, $normUploadDir . '/')) {
            continue;
        }

        if (gf_is_image_referenced($urlStr)) {
            continue;
        }

        @unlink($realPath);
    }
}

/**
 * Render standardized GYMFINDER brand logo HTML
 *
 * @param string $variant 'header' | 'admin' | 'login' | 'footer'
 * @param string|null $href Custom target link. Defaults by variant.
 * @param string $extraClass Additional CSS classes
 * @param bool $asLink Whether output is wrapped in <a>
 * @return string HTML
 */
function gf_logo(string $variant = 'header', ?string $href = null, string $extraClass = '', bool $asLink = true): string
{
    $logoVariant = $variant;
    $logoHref = $href;
    $logoClass = $extraClass;
    $logoAsLink = $asLink;
    ob_start();
    require dirname(__DIR__) . '/includes/logo.php';
    return ob_get_clean() ?: '';
}

