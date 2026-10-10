<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/backend/bootstrap.php';

$rawPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if (preg_match('#(?:^|/)api(?:/(.*))?$#', $rawPath, $matches)) {
    $path = '/' . trim($matches[1] ?? '', '/');
} else {
    $path = '/' . trim($rawPath, '/');
}
if ($path === '//') {
    $path = '/';
}
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$raw = file_get_contents('php://input') ?: '';
$body = [];
$ct = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? ''));

if (in_array($method, ['POST', 'PATCH', 'PUT', 'DELETE'], true)) {
    if (str_contains($ct, 'multipart/form-data')) {
        $body = $_POST;
    } elseif ($raw !== '') {
        if ($ct !== '' && !str_contains($ct, 'application/json') && !str_contains($ct, 'application/x-www-form-urlencoded')) {
            gf_api_error('BAD_REQUEST', 'Sai Content-Type.', 400);
        }
        if (str_contains($ct, 'application/json') || str_starts_with(ltrim($raw), '{') || str_starts_with(ltrim($raw), '[')) {
            $decoded = json_decode($raw, true);
            if (!is_array($decoded) && json_last_error() !== JSON_ERROR_NONE) {
                gf_api_error('BAD_REQUEST', 'JSON không hợp lệ.', 400);
            }
            $body = is_array($decoded) ? $decoded : [];
        } else {
            parse_str($raw, $parsed);
            $body = is_array($parsed) ? $parsed : [];
        }
    } else {
        $body = $_POST ?: [];
    }
}

function gf_api_csrf_mutating(string $method): void
{
    if (in_array($method, ['POST', 'PATCH', 'PUT', 'DELETE'], true)) {
        gf_require_csrf();
    }
}

function gf_api_user(): ?array
{
    $u = gf_current_user();
    if ($u && ($u['status'] ?? '') === 'locked') {
        unset($_SESSION['user_id']);
        session_regenerate_id(true);
        gf_api_error('ACCOUNT_LOCKED', 'Tài khoản đã bị khóa.', 403);
    }
    return $u;
}

function gf_api_require_user(): array
{
    $u = gf_api_user();
    if (!$u) {
        gf_api_error('AUTH_REQUIRED', 'Cần đăng nhập.', 401);
    }
    return $u;
}

function gf_api_require_admin(): array
{
    $u = gf_api_require_user();
    if (($u['role'] ?? '') !== 'admin') {
        gf_api_error('FORBIDDEN', 'Không đủ quyền quản trị.', 403);
    }
    return $u;
}

// Bắt buộc kiểm tra CSRF trên toàn bộ mutating methods
gf_api_csrf_mutating($method);

// =========================================================================
// 1. AUTHENTICATION & PROFILE APIS (Sprint 1)
// =========================================================================

if ($path === '/auth/csrf' && $method === 'GET') {
    gf_json(['data' => ['csrf_token' => gf_csrf_token()]]);
}

if ($path === '/auth/register' && $method === 'POST') {
    gf_require_known_body($body, ['full_name', 'email', 'phone', 'password', 'goal'], ['id', 'role', 'status', 'avatar_url', 'created_at', 'updated_at']);
    $res = gf_register(
        (string) ($body['full_name'] ?? ''),
        (string) ($body['email'] ?? ''),
        (string) ($body['password'] ?? ''),
        isset($body['phone']) ? (string) $body['phone'] : null,
        isset($body['goal']) ? (string) $body['goal'] : null
    );
    if (!$res['ok']) {
        $code = $res['code'] ?? 'VALIDATION_ERROR';
        gf_api_error($code, $res['error'], $code === 'DUPLICATE_EMAIL' ? 409 : 422);
    }
    $u = $res['user'];
    gf_json(['data' => [
        'id' => (int) $u['id'],
        'full_name' => $u['full_name'],
        'email' => $u['email'],
        'phone' => $u['phone'],
        'goal' => $u['goal'],
        'role' => $u['role'],
        'status' => $u['status'],
        'avatar_url' => $u['avatar_url'],
        'created_at' => gf_iso($u['created_at']),
    ]], 201);
}

if ($path === '/auth/login' && $method === 'POST') {
    gf_require_known_body($body, ['email', 'password', 'login']);
    $email = trim((string) ($body['email'] ?? $body['login'] ?? ''));
    $pass = (string) ($body['password'] ?? '');
    if ($email === '' || $pass === '') {
        gf_api_error('VALIDATION_ERROR', 'Email và mật khẩu là bắt buộc.', 422, [
            'email' => $email === '' ? 'Bắt buộc.' : null,
            'password' => $pass === '' ? 'Bắt buộc.' : null,
        ]);
    }
    $res = gf_login($email, $pass);
    if (!$res['ok']) {
        $isLocked = ($res['code'] ?? '') === 'ACCOUNT_LOCKED';
        gf_api_error($isLocked ? 'ACCOUNT_LOCKED' : 'INVALID_CREDENTIALS', $res['error'], $isLocked ? 403 : 401);
    }
    $u = $res['user'];
    gf_json(['data' => [
        'id' => (int) $u['id'],
        'full_name' => $u['full_name'],
        'email' => $u['email'],
        'role' => $u['role'],
        'status' => $u['status'],
    ]]);
}

if ($path === '/auth/logout' && $method === 'POST') {
    gf_api_require_user();
    gf_logout();
    http_response_code(204);
    exit;
}

if ($path === '/auth/me' && $method === 'GET') {
    $u = gf_api_require_user();
    gf_json(['data' => [
        'id' => (int) $u['id'],
        'full_name' => $u['full_name'],
        'email' => $u['email'],
        'role' => $u['role'],
        'status' => $u['status'],
    ]]);
}

if ($path === '/auth/change-password' && $method === 'POST') {
    $u = gf_api_require_user();
    gf_require_known_body($body, ['current_password', 'new_password']);
    $cur = (string) ($body['current_password'] ?? '');
    $new = (string) ($body['new_password'] ?? '');
    if ($cur === '' || $new === '') {
        gf_api_error('VALIDATION_ERROR', 'Thiếu mật khẩu hiện tại hoặc mật khẩu mới.', 422, [
            'current_password' => $cur === '' ? 'Bắt buộc.' : null,
            'new_password' => $new === '' ? 'Bắt buộc.' : null,
        ]);
    }
    if (!gf_password_ok($new)) {
        gf_api_error('VALIDATION_ERROR', 'Mật khẩu mới phải từ 8 đến 72 byte UTF-8.', 422, ['new_password' => '8–72 byte.']);
    }
    if ($cur === $new) {
        gf_api_error('VALIDATION_ERROR', 'Mật khẩu mới phải khác mật khẩu hiện tại.', 422, ['new_password' => 'Phải khác mật khẩu hiện tại.']);
    }
    $res = gf_change_password((int) $u['id'], $cur, $new);
    if (!$res['ok']) {
        gf_api_error('VALIDATION_ERROR', $res['error'], 422, ['current_password' => $res['error']]);
    }
    http_response_code(204);
    exit;
}

if ($path === '/profile' && $method === 'GET') {
    $u = gf_api_require_user();
    if (($u['role'] ?? '') === 'admin') {
        gf_api_error('FORBIDDEN', 'Admin không dùng /profile user.', 403);
    }
    gf_json(['data' => [
        'id' => (int) $u['id'],
        'full_name' => $u['full_name'],
        'email' => $u['email'],
        'phone' => $u['phone'],
        'goal' => $u['goal'],
        'avatar_url' => $u['avatar_url'],
    ]]);
}

if ($path === '/profile' && $method === 'PATCH') {
    $u = gf_api_require_user();
    if (($u['role'] ?? '') === 'admin') {
        gf_api_error('FORBIDDEN', 'Admin không dùng /profile user.', 403);
    }
    gf_require_known_body($body, ['avatar_url', 'full_name', 'phone', 'goal'], ['id', 'email', 'role', 'status', 'created_at', 'updated_at']);
    if ($body === []) {
        gf_api_error('VALIDATION_ERROR', 'PATCH body rỗng.', 422, ['body' => 'Cần ít nhất một field.']);
    }
    $details = [];
    if (array_key_exists('full_name', $body)) {
        $fn = trim((string) $body['full_name']);
        if ($fn === '' || mb_strlen($fn) > 100) {
            $details['full_name'] = 'full_name 1–100 ký tự.';
        }
    }
    if (array_key_exists('phone', $body) && $body['phone'] !== null && $body['phone'] !== '') {
        if (!gf_phone_ok((string) $body['phone'])) {
            $details['phone'] = 'Số điện thoại không hợp lệ.';
        }
    }
    if (array_key_exists('goal', $body) && $body['goal'] !== null && $body['goal'] !== '') {
        if (!in_array($body['goal'], ['giam_can', 'tang_co', 'tang_suc_manh', 'cai_thien_suc_khoe'], true)) {
            $details['goal'] = 'Mục tiêu không hợp lệ.';
        }
    }
    if (array_key_exists('avatar_url', $body) && $body['avatar_url'] !== null && $body['avatar_url'] !== '') {
        if (!gf_uploads_url_ok((string) $body['avatar_url'])) {
            $details['avatar_url'] = 'Chỉ nhận đường dẫn /uploads/... do hệ thống cấp.';
        }
    }
    if ($details) {
        gf_api_error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ.', 422, $details);
    }
    gf_update_profile((int) $u['id'], $body);
    $fresh = gf_current_user();
    gf_json(['data' => [
        'id' => (int) $fresh['id'],
        'full_name' => $fresh['full_name'],
        'email' => $fresh['email'],
        'phone' => $fresh['phone'],
        'goal' => $fresh['goal'],
        'avatar_url' => $fresh['avatar_url'],
    ]]);
}

// =========================================================================
// 2. IMAGE UPLOAD API (Sprint 1, 2, 3 & Admin)
// =========================================================================

if ($path === '/uploads/images' && $method === 'POST') {
    $u = gf_api_require_user();
    $file = $_FILES['file'] ?? [];
    $type = (string) ($_POST['type'] ?? '');
    $res = gf_save_upload($file, $type, $u);
    if (!$res['ok']) {
        gf_api_error($res['code'], $res['error'], $res['http'], $res['details'] ?? []);
    }
    gf_json(['data' => ['url' => $res['url']]], 201);
}

// =========================================================================
// 3. TAXONOMY APIS (Sprint 2 & 3)
// =========================================================================

if ($path === '/districts' && $method === 'GET') {
    $rows = array_map(static fn ($d) => ['id' => (int) $d['id'], 'name' => $d['name'], 'slug' => $d['slug']], gf_list_districts());
    gf_json(['data' => ['items' => $rows]]);
}

if ($path === '/categories' && $method === 'GET') {
    $rows = array_map(static fn ($c) => ['id' => (int) $c['id'], 'name' => $c['name'], 'slug' => $c['slug']], gf_list_categories(true));
    gf_json(['data' => ['items' => $rows]]);
}

if ($path === '/amenities' && $method === 'GET') {
    $rows = array_map(static fn ($a) => ['id' => (int) $a['id'], 'name' => $a['name'], 'slug' => $a['slug'], 'icon' => $a['icon']], gf_list_amenities());
    gf_json(['data' => ['items' => $rows]]);
}

if ($path === '/specialties' && $method === 'GET') {
    $rows = array_map(static fn ($s) => ['id' => (int) $s['id'], 'name' => $s['name'], 'slug' => $s['slug']], gf_list_specialties());
    gf_json(['data' => ['items' => $rows]]);
}

// =========================================================================
// 4. PUBLIC GYM APIS (Sprint 2)
// =========================================================================

if ($path === '/gyms' && $method === 'GET') {
    $validated = gf_validate_gym_search_params($_GET);
    $paged = gf_search_gyms_paged($validated);
    $items = [];
    foreach ($paged['items'] as $g) {
        $items[] = gf_gym_list_item($g);
    }
    gf_json(['data' => [
        'items' => $items,
        'page' => $paged['page'],
        'limit' => $paged['limit'],
        'total' => $paged['total'],
    ]]);
}

if (preg_match('#^/gyms/([^/]+)/reviews$#', $path, $m) && $method === 'GET') {
    $key = urldecode($m[1]);
    $g = gf_get_gym_by_key($key, true);
    if (!$g) {
        gf_api_error('NOT_FOUND', 'Không tìm thấy phòng tập.', 404);
    }
    $pageLimit = gf_api_page_limit($_GET);
    $u = gf_api_user();
    $data = gf_reviews_list('gym', (int) $g['id'], $pageLimit['page'], $pageLimit['limit'], $u);
    gf_json(['data' => $data]);
}

if (preg_match('#^/gyms/([^/]+)$#', $path, $m) && $method === 'GET') {
    $key = urldecode($m[1]);
    $g = gf_get_gym_by_key($key, true);
    if (!$g) {
        gf_api_error('NOT_FOUND', 'Không tìm thấy phòng tập.', 404);
    }
    $u = gf_api_user();
    $detail = gf_public_gym_detail($g, $u);
    gf_json(['data' => $detail]);
}

// =========================================================================
// 5. PUBLIC TRAINER APIS (Sprint 3)
// =========================================================================

if ($path === '/trainers' && $method === 'GET') {
    $validated = gf_validate_trainer_search_params($_GET);
    $paged = gf_search_trainers_paged($validated);
    $items = [];
    foreach ($paged['items'] as $t) {
        $items[] = gf_trainer_list_item($t);
    }
    gf_json(['data' => [
        'items' => $items,
        'page' => $paged['page'],
        'limit' => $paged['limit'],
        'total' => $paged['total'],
    ]]);
}

if (preg_match('#^/trainers/([^/]+)/reviews$#', $path, $m) && $method === 'GET') {
    $key = urldecode($m[1]);
    $t = gf_get_trainer_by_key($key);
    if (!$t) {
        gf_api_error('NOT_FOUND', 'Không tìm thấy huấn luyện viên.', 404);
    }
    $pageLimit = gf_api_page_limit($_GET);
    $u = gf_api_user();
    $data = gf_reviews_list('trainer', (int) $t['id'], $pageLimit['page'], $pageLimit['limit'], $u);
    gf_json(['data' => $data]);
}

if (preg_match('#^/trainers/([^/]+)$#', $path, $m) && $method === 'GET') {
    $key = urldecode($m[1]);
    $t = gf_get_trainer_by_key($key);
    if (!$t) {
        gf_api_error('NOT_FOUND', 'Không tìm thấy huấn luyện viên.', 404);
    }
    $u = gf_api_user();
    $detail = gf_public_trainer_detail($t, $u);
    gf_json(['data' => $detail]);
}

// =========================================================================
// 6. FAVORITES APIS (Sprint 3)
// =========================================================================

if ($path === '/favorites' && $method === 'GET') {
    $u = gf_api_require_user();
    if (($u['role'] ?? '') === 'admin') {
        gf_api_error('FORBIDDEN', 'Admin không dùng danh sách yêu thích.', 403);
    }
    $pageLimit = gf_api_page_limit($_GET);
    $data = gf_favorites_paged((int) $u['id'], $pageLimit['page'], $pageLimit['limit']);
    gf_json(['data' => $data]);
}

if ($path === '/favorites' && $method === 'POST') {
    $u = gf_api_require_user();
    if (($u['role'] ?? '') === 'admin') {
        gf_api_error('FORBIDDEN', 'Admin không dùng danh sách yêu thích.', 403);
    }
    gf_require_known_body($body, ['target_type', 'target_id'], ['id', 'user_id', 'created_at']);
    $type = (string) ($body['target_type'] ?? '');
    if (!in_array($type, ['gym', 'trainer'], true)) {
        gf_api_error('VALIDATION_ERROR', 'target_type chỉ gym|trainer.', 422, ['target_type' => 'gym|trainer']);
    }
    $tid = $body['target_id'] ?? null;
    if (!gf_integer_like($tid) || (int) $tid < 1) {
        gf_api_error('VALIDATION_ERROR', 'target_id không hợp lệ.', 422, ['target_id' => 'ID nguyên dương.']);
    }
    $tid = (int) $tid;
    if (!gf_target_public($type, $tid)) {
        gf_api_error('NOT_FOUND', 'Đối tượng không tồn tại hoặc đang ẩn.', 404);
    }
    if (gf_is_fav((int) $u['id'], $type, $tid)) {
        gf_api_error('DUPLICATE_FAVORITE', 'Đã lưu yêu thích đối tượng này.', 409);
    }
    $created = gf_add_favorite_row((int) $u['id'], $type, $tid);
    gf_json(['data' => $created], 201);
}

if (preg_match('#^/favorites/([^/]+)/([^/]+)$#', $path, $m) && $method === 'DELETE') {
    $u = gf_api_require_user();
    if (($u['role'] ?? '') === 'admin') {
        gf_api_error('FORBIDDEN', 'Admin không dùng danh sách yêu thích.', 403);
    }
    $type = $m[1];
    if (!in_array($type, ['gym', 'trainer'], true)) {
        gf_api_error('VALIDATION_ERROR', 'target_type không hợp lệ.', 422, ['target_type' => 'gym|trainer']);
    }
    if (!ctype_digit($m[2]) || (int) $m[2] < 1) {
        gf_api_error('VALIDATION_ERROR', 'target_id không hợp lệ.', 422, ['target_id' => 'ID nguyên dương.']);
    }
    $tid = (int) $m[2];
    gf_delete_favorite_item((int) $u['id'], $type, $tid);
    http_response_code(204);
    exit;
}

if ($path === '/favorites/sync' && $method === 'POST') {
    $u = gf_api_require_user();
    if (($u['role'] ?? '') === 'admin') {
        gf_api_error('FORBIDDEN', 'Admin không dùng danh sách yêu thích.', 403);
    }
    $data = gf_sync_favorites((int) $u['id'], $body);
    gf_json(['data' => $data]);
}

// =========================================================================
// 7. USER REVIEWS APIS (Sprint 3)
// =========================================================================

if ($path === '/reviews' && $method === 'POST') {
    $u = gf_api_require_user();
    if (($u['role'] ?? '') === 'admin') {
        gf_api_error('FORBIDDEN', 'Admin không gửi review user.', 403);
    }
    gf_require_known_body($body, ['target_type', 'target_id', 'rating', 'comment'], ['id', 'user_id', 'status', 'reviewed_by', 'reviewed_at', 'reject_reason', 'created_at', 'updated_at']);
    $type = (string) ($body['target_type'] ?? '');
    if (!in_array($type, ['gym', 'trainer'], true)) {
        gf_api_error('VALIDATION_ERROR', 'target_type chỉ gym|trainer.', 422, ['target_type' => 'gym|trainer']);
    }
    $tid = $body['target_id'] ?? null;
    if (!gf_integer_like($tid) || (int) $tid < 1) {
        gf_api_error('VALIDATION_ERROR', 'target_id không hợp lệ.', 422, ['target_id' => 'ID nguyên dương.']);
    }
    $tid = (int) $tid;
    if (!gf_target_public($type, $tid)) {
        gf_api_error('NOT_FOUND', 'Đối tượng không tồn tại hoặc đang ẩn.', 404);
    }
    $rate = $body['rating'] ?? null;
    if (!gf_integer_like($rate) || (int) $rate < 1 || (int) $rate > 5) {
        gf_api_error('VALIDATION_ERROR', 'rating phải từ 1 đến 5.', 422, ['rating' => '1–5.']);
    }
    $comment = trim((string) ($body['comment'] ?? ''));
    if ($comment === '' || mb_strlen($comment) > 2000) {
        gf_api_error('VALIDATION_ERROR', 'comment 1–2000 ký tự.', 422, ['comment' => '1–2000 ký tự.']);
    }
    $created = gf_create_review((int) $u['id'], $type, $tid, (int) $rate, $comment);
    if (!$created['ok']) {
        gf_api_error($created['code'], $created['error'], $created['http'], $created['details'] ?? []);
    }
    $r = $created['review'];
    gf_json(['data' => [
        'id' => (int) $r['id'],
        'target_type' => $r['target_type'],
        'target_id' => $r['target_type'] === 'gym' ? (int) $r['gym_id'] : (int) $r['trainer_id'],
        'rating' => (int) $r['rating'],
        'comment' => $r['comment'],
        'status' => 'pending',
        'created_at' => gf_iso($r['created_at']),
        'updated_at' => gf_iso($r['updated_at']),
    ]], 201);
}

if (preg_match('#^/reviews/(\d+)$#', $path, $m)) {
    $revId = (int) $m[1];
    if ($method === 'GET') {
        $u = gf_api_user();
        $detail = gf_get_review_detail($revId, $u);
        gf_json(['data' => $detail]);
    }
    if ($method === 'PATCH') {
        $u = gf_api_require_user();
        if (($u['role'] ?? '') === 'admin') {
            gf_api_error('FORBIDDEN', 'Admin không sửa review qua endpoint này.', 403);
        }
        $updated = gf_patch_review_owner($revId, $body, $u);
        gf_json(['data' => $updated]);
    }
    if ($method === 'DELETE') {
        $u = gf_api_require_user();
        if (($u['role'] ?? '') === 'admin') {
            gf_api_error('FORBIDDEN', 'Admin không xóa review qua endpoint này.', 403);
        }
        gf_delete_review_owner($revId, $u);
        http_response_code(204);
        exit;
    }
}

// =========================================================================
// 8. ADMIN DASHBOARD & CRUD APIS (Sprint 2, 3, 4)
// =========================================================================

if ($path === '/admin/dashboard' && $method === 'GET') {
    gf_api_require_admin();
    gf_json(['data' => gf_admin_stats()]);
}

// Admin Gyms
if ($path === '/admin/gyms' && $method === 'GET') {
    gf_api_require_admin();
    $data = gf_admin_gyms_paged($_GET);
    gf_json(['data' => $data]);
}
if ($path === '/admin/gyms' && $method === 'POST') {
    gf_api_require_admin();
    $res = gf_admin_save_gym($body, null);
    if (!$res['ok']) {
        gf_api_error($res['code'], $res['error'], $res['http'], $res['details'] ?? []);
    }
    gf_json(['data' => gf_admin_gym_payload($res['id'])], 201);
}
if (preg_match('#^/admin/gyms/(\d+)$#', $path, $m)) {
    $gymId = (int) $m[1];
    if ($method === 'GET') {
        gf_api_require_admin();
        $g = gf_admin_gym_payload($gymId);
        if (!$g) {
            gf_api_error('NOT_FOUND', 'Không tìm thấy phòng tập.', 404);
        }
        gf_json(['data' => $g]);
    }
    if ($method === 'PATCH') {
        gf_api_require_admin();
        $res = gf_admin_save_gym($body, $gymId);
        if (!$res['ok']) {
            gf_api_error($res['code'], $res['error'], $res['http'], $res['details'] ?? []);
        }
        gf_json(['data' => gf_admin_gym_payload($gymId)]);
    }
    if ($method === 'DELETE') {
        gf_api_require_admin();
        gf_set_entity_status('gyms', $gymId, 'hidden');
        http_response_code(204);
        exit;
    }
}

// Admin Trainers
if ($path === '/admin/trainers' && $method === 'GET') {
    gf_api_require_admin();
    $data = gf_admin_trainers_paged($_GET);
    gf_json(['data' => $data]);
}
if ($path === '/admin/trainers' && $method === 'POST') {
    gf_api_require_admin();
    $res = gf_admin_save_trainer($body, null);
    if (!$res['ok']) {
        gf_api_error($res['code'], $res['error'], $res['http'], $res['details'] ?? []);
    }
    gf_json(['data' => gf_admin_trainer_payload($res['id'])], 201);
}
if (preg_match('#^/admin/trainers/(\d+)$#', $path, $m)) {
    $trId = (int) $m[1];
    if ($method === 'GET') {
        gf_api_require_admin();
        $t = gf_admin_trainer_payload($trId);
        if (!$t) {
            gf_api_error('NOT_FOUND', 'Không tìm thấy huấn luyện viên.', 404);
        }
        gf_json(['data' => $t]);
    }
    if ($method === 'PATCH') {
        gf_api_require_admin();
        $res = gf_admin_save_trainer($body, $trId);
        if (!$res['ok']) {
            gf_api_error($res['code'], $res['error'], $res['http'], $res['details'] ?? []);
        }
        gf_json(['data' => gf_admin_trainer_payload($trId)]);
    }
    if ($method === 'DELETE') {
        gf_api_require_admin();
        gf_set_entity_status('trainers', $trId, 'hidden');
        http_response_code(204);
        exit;
    }
}

// Admin Categories
if ($path === '/admin/categories' && $method === 'GET') {
    gf_api_require_admin();
    $data = gf_admin_categories_paged($_GET);
    gf_json(['data' => $data]);
}
if ($path === '/admin/categories' && $method === 'POST') {
    gf_api_require_admin();
    $res = gf_admin_save_category($body, null);
    gf_json(['data' => gf_admin_category_payload($res['id'])], 201);
}
if (preg_match('#^/admin/categories/(\d+)$#', $path, $m)) {
    $catId = (int) $m[1];
    if ($method === 'GET') {
        gf_api_require_admin();
        $c = gf_admin_category_payload($catId);
        if (!$c) {
            gf_api_error('NOT_FOUND', 'Không tìm thấy danh mục.', 404);
        }
        gf_json(['data' => $c]);
    }
    if ($method === 'PATCH') {
        gf_api_require_admin();
        gf_admin_save_category($body, $catId);
        gf_json(['data' => gf_admin_category_payload($catId)]);
    }
    if ($method === 'DELETE') {
        gf_api_require_admin();
        gf_admin_delete_category($catId);
        http_response_code(204);
        exit;
    }
}

// Admin Reviews
if ($path === '/admin/reviews' && $method === 'GET') {
    gf_api_require_admin();
    $data = gf_admin_reviews_paged($_GET);
    gf_json(['data' => $data]);
}
if (preg_match('#^/admin/reviews/(\d+)$#', $path, $m)) {
    $revId = (int) $m[1];
    if ($method === 'PATCH') {
        $admin = gf_api_require_admin();
        $updated = gf_admin_moderate_review($revId, $body, $admin);
        gf_json(['data' => $updated]);
    }
    if ($method === 'DELETE') {
        gf_api_require_admin();
        gf_admin_delete_review($revId);
        http_response_code(204);
        exit;
    }
}

// Admin Users
if ($path === '/admin/users' && $method === 'GET') {
    gf_api_require_admin();
    $data = gf_admin_users_paged($_GET);
    gf_json(['data' => $data]);
}
if (preg_match('#^/admin/users/(\d+)$#', $path, $m)) {
    $targetUserId = (int) $m[1];
    if ($method === 'GET') {
        gf_api_require_admin();
        $u = gf_admin_user_payload($targetUserId);
        if (!$u) {
            gf_api_error('NOT_FOUND', 'Không tìm thấy người dùng.', 404);
        }
        gf_json(['data' => $u]);
    }
    if ($method === 'PATCH') {
        $admin = gf_api_require_admin();
        $updated = gf_admin_patch_user($targetUserId, $body, $admin);
        gf_json(['data' => $updated]);
    }
    if ($method === 'DELETE') {
        $admin = gf_api_require_admin();
        gf_admin_delete_user($targetUserId, $admin);
        http_response_code(204);
        exit;
    }
}

// 404 Not Found fallback
gf_api_error('NOT_FOUND', 'Endpoint not found', 404, ['path' => $path]);
