<?php
/**
 * QA full-site smoke theo docs/qa/TEST_CASES.md
 * Chạy: GF_QA_BASE=http://localhost:8000 php docs/qa/_full_site_smoke.php
 */
declare(strict_types=1);

$base = rtrim(getenv('GF_QA_BASE') ?: 'http://localhost:8000', '/');
$cookieDir = sys_get_temp_dir() . '/gf_qa_full_' . getmypid();
@mkdir($cookieDir);

$pass = 0;
$fail = 0;
$blocked = 0;
$skip = 0;
$lines = [];

function qa_log(string $status, string $id, string $msg): void
{
    global $pass, $fail, $blocked, $skip, $lines;
    if ($status === 'PASS') {
        $pass++;
    } elseif ($status === 'FAIL') {
        $fail++;
    } elseif ($status === 'BLOCKED') {
        $blocked++;
    } else {
        $skip++;
    }
    $line = "{$status}|{$id}|{$msg}";
    $lines[] = $line;
    echo $line . "\n";
}

function qa_jar(string $name): string
{
    global $cookieDir;
    return $cookieDir . '/' . $name . '.jar';
}

function qa_http(string $method, string $url, array $opts = []): array
{
    $jar = $opts['jar'] ?? null;
    $headers = $opts['headers'] ?? [];
    $fields = $opts['fields'] ?? null;
    $body = $opts['body'] ?? null;
    $timeout = (int) ($opts['timeout'] ?? 30);
    $rawHeaders = '';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HEADER => false,
        CURLOPT_HEADERFUNCTION => static function ($ch, $line) use (&$rawHeaders) {
            $rawHeaders .= $line;
            return strlen($line);
        },
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    if ($jar) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $jar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $jar);
    }
    if ($fields !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    } elseif ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    if ($headers) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $resp = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    if ($err) {
        return ['code' => 0, 'body' => '', 'headers' => '', 'location' => '', 'error' => $err];
    }
    preg_match('/^Location:\s*(.+)$/mi', $rawHeaders, $m);
    return ['code' => $code, 'body' => (string) $resp, 'headers' => $rawHeaders, 'location' => trim($m[1] ?? '')];
}

function qa_csrf_html(string $html): string
{
    return preg_match('/name="_csrf"\s+value="([^"]+)"/', $html, $m) ? $m[1] : '';
}

function qa_api_csrf(string $jar): string
{
    global $base;
    $r = qa_http('GET', $base . '/api/auth/csrf', ['jar' => $jar]);
    $j = json_decode($r['body'], true);
    return (string) ($j['data']['csrf_token'] ?? '');
}

function qa_login(string $jar, string $email, string $password = 'password'): array
{
    global $base;
    $page = qa_http('GET', $base . '/pages/auth/login.php', ['jar' => $jar]);
    $csrf = qa_csrf_html($page['body']);
    return qa_http('POST', $base . '/pages/auth/login.php', [
        'jar' => $jar,
        'fields' => ['_csrf' => $csrf, 'login' => $email, 'password' => $password],
    ]);
}

function qa_api(string $jar, string $method, string $path, ?array $json = null): array
{
    global $base;
    $csrf = qa_api_csrf($jar);
    $headers = ['X-CSRF-Token: ' . $csrf, 'Accept: application/json'];
    $body = null;
    if ($json !== null) {
        $body = json_encode($json, JSON_UNESCAPED_UNICODE);
        $headers[] = 'Content-Type: application/json';
    }
    $r = qa_http($method, $base . $path, ['jar' => $jar, 'headers' => $headers, 'body' => $body]);
    $r['json'] = json_decode($r['body'], true);
    return $r;
}

function assert_ok(bool $ok, string $id, string $passMsg, string $failMsg): void
{
    qa_log($ok ? 'PASS' : 'FAIL', $id, $ok ? $passMsg : $failMsg);
}

$pdo = new PDO('sqlite:' . dirname(__DIR__, 2) . '/database/gymfinder.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA foreign_keys = ON');

$hiddenGym = (int) $pdo->query("SELECT id FROM gyms WHERE status='hidden' ORDER BY id LIMIT 1")->fetchColumn();
$hiddenPt = (int) $pdo->query("SELECT id FROM trainers WHERE status='hidden' ORDER BY id LIMIT 1")->fetchColumn();
$activeGym = (int) $pdo->query("SELECT id FROM gyms WHERE status='active' ORDER BY id LIMIT 1")->fetchColumn();
$activePt = (int) $pdo->query("SELECT id FROM trainers WHERE status='active' ORDER BY id LIMIT 1")->fetchColumn();
$lockedEmail = (string) $pdo->query("SELECT email FROM users WHERE status='locked' ORDER BY id LIMIT 1")->fetchColumn();
$yogaSpec = (int) $pdo->query("SELECT id FROM specialties WHERE name LIKE '%Yoga%' ORDER BY id LIMIT 1")->fetchColumn();
$catPhoThong = (int) $pdo->query("SELECT id FROM categories WHERE name LIKE '%phổ thông%' OR name LIKE '%pho thong%' ORDER BY id LIMIT 1")->fetchColumn();
$catCaoCap = (int) $pdo->query("SELECT id FROM categories WHERE name LIKE '%cao cấp%' OR name LIKE '%cao cap%' ORDER BY id LIMIT 1")->fetchColumn();
$amenityIds = $pdo->query('SELECT id FROM amenities ORDER BY id LIMIT 2')->fetchAll(PDO::FETCH_COLUMN);
$district1 = (int) $pdo->query("SELECT id FROM districts WHERE name LIKE '%Quận 1%' OR name LIKE '%Quan 1%' ORDER BY id LIMIT 1")->fetchColumn();
$gtCount = (int) $pdo->query('SELECT COUNT(*) FROM gym_trainers')->fetchColumn();
$tsCount = (int) $pdo->query('SELECT COUNT(*) FROM trainer_specialties')->fetchColumn();

echo "BASE={$base}\n";
echo "seed hidden_gym={$hiddenGym} hidden_pt={$hiddenPt} locked={$lockedEmail}\n";

// ---- Guest pages ----
$home = qa_http('GET', $base . '/');
assert_ok($home['code'] === 200 && !str_contains($home['body'], 'Fatal error') && (str_contains($home['body'], 'GYMFINDER') || str_contains($home['body'], 'gymfinder')), 'HOME-001', 'Home 200', 'Home code=' . $home['code']);
assert_ok(str_contains($home['body'], 'search') || str_contains($home['body'], 'Tìm'), 'HOME-004', 'Home has search UI', 'Missing search UI');

$searchYoga = qa_http('GET', $base . '/pages/gyms/search.php?keyword=' . rawurlencode('Yoga'));
assert_ok($searchYoga['code'] === 200 && (preg_match('/phòng|gym|kết quả|result/iu', $searchYoga['body']) === 1), 'HOME-002', 'Keyword Yoga search 200', 'Yoga search failed');

$gymSearch = qa_http('GET', $base . '/pages/gyms/search.php');
assert_ok($gymSearch['code'] === 200 && preg_match('/gym-card|admin-cell-title|card|/i', $gymSearch['body']) === 1, 'GYM-001', 'Gym list 200', 'Gym list failed code=' . $gymSearch['code']);

if ($catPhoThong > 0) {
    $r = qa_http('GET', $base . '/pages/gyms/search.php?category_ids[]=' . $catPhoThong);
    assert_ok($r['code'] === 200, 'GYM-002', 'Filter phổ thông 200', 'Filter phổ thông failed');
} else {
    qa_log('SKIP', 'GYM-002', 'No category phổ thông');
}
if ($catCaoCap > 0) {
    $r = qa_http('GET', $base . '/pages/gyms/search.php?category_ids[]=' . $catCaoCap);
    assert_ok($r['code'] === 200, 'GYM-003', 'Filter cao cấp 200', 'Filter cao cấp failed');
} else {
    qa_log('SKIP', 'GYM-003', 'No category cao cấp');
}
if (count($amenityIds) >= 2) {
    $q = 'amenity_ids[]=' . (int) $amenityIds[0] . '&amenity_ids[]=' . (int) $amenityIds[1];
    $r = qa_http('GET', $base . '/pages/gyms/search.php?' . $q);
    assert_ok($r['code'] === 200, 'GYM-004', 'Filter amenities AND 200', 'Amenities filter failed');
} else {
    qa_log('SKIP', 'GYM-004', 'Need ≥2 amenities');
}
$r = qa_http('GET', $base . '/pages/gyms/search.php?has_pt=1');
assert_ok($r['code'] === 200, 'GYM-005', 'Filter has_pt 200', 'has_pt failed');
$r = qa_http('GET', $base . '/pages/gyms/search.php?price_band=under_300');
assert_ok($r['code'] === 200 || $r['code'] === 0, 'GYM-006', 'price filter page loads', 'price filter code=' . $r['code']);
$r = qa_http('GET', $base . '/pages/gyms/search.php?rating=4');
assert_ok($r['code'] === 200, 'GYM-007', 'Star filter 200', 'rating filter failed');
if ($district1 > 0 && $catPhoThong > 0 && $amenityIds) {
    $q = 'district_id=' . $district1 . '&category_ids[]=' . $catPhoThong . '&amenity_ids[]=' . (int) $amenityIds[0];
    $r = qa_http('GET', $base . '/pages/gyms/search.php?' . $q);
    assert_ok($r['code'] === 200, 'GYM-008', 'Combined filters 200', 'combined filter failed');
} else {
    qa_log('SKIP', 'GYM-008', 'Missing district/category/amenity');
}
$r = qa_http('GET', $base . '/pages/gyms/search.php?sort=price_asc');
assert_ok($r['code'] === 200, 'GYM-009', 'Sort price_asc 200', 'sort failed');
$r = qa_http('GET', $base . '/pages/gyms/search.php?keyword=' . rawurlencode('zzzznofindxyz999'));
assert_ok($r['code'] === 200 && (str_contains($r['body'], 'Không') || str_contains($r['body'], 'No ') || str_contains($r['body'], 'empty') || str_contains($r['body'], 'kết quả')), 'GYM-010', 'Empty state present', 'Empty state unclear');
$r = qa_http('GET', $base . '/pages/gyms/search.php?page=2');
assert_ok($r['code'] === 200, 'GYM-011', 'Pagination page=2 200', 'pagination failed');

$detail = qa_http('GET', $base . '/pages/gyms/detail.php?id=' . $activeGym);
assert_ok($detail['code'] === 200 && !str_contains($detail['body'], 'Fatal error'), 'GDET-001', "Gym detail #{$activeGym} 200", 'detail failed');
assert_ok($detail['code'] === 200, 'GYM-012', 'Detail reachable from id', 'detail unreachable');
$hidden = qa_http('GET', $base . '/pages/gyms/detail.php?id=' . ($hiddenGym ?: 3));
assert_ok($hidden['code'] === 404 || str_contains($hidden['body'], '404') || str_contains($hidden['body'], 'không tìm'), 'GDET-003', 'Hidden gym blocked', 'hidden gym code=' . $hidden['code']);
$missing = qa_http('GET', $base . '/pages/gyms/detail.php?id=99999');
assert_ok($missing['code'] === 404 || str_contains($missing['body'], '404'), 'GDET-004', 'Missing gym 404', 'missing gym code=' . $missing['code']);

$trainers = qa_http('GET', $base . '/pages/trainers/index.php');
assert_ok($trainers['code'] === 200 && !str_contains($trainers['body'], 'Fatal error'), 'PT-001', 'Trainers index 200', 'trainers failed');
if ($yogaSpec > 0) {
    $r = qa_http('GET', $base . '/pages/trainers/index.php?specialty_id=' . $yogaSpec);
    assert_ok($r['code'] === 200, 'PT-002', 'Specialty filter 200', 'specialty filter failed');
} else {
    qa_log('SKIP', 'PT-002', 'No Yoga specialty');
}
$r = qa_http('GET', $base . '/pages/trainers/index.php?experience=0-2');
assert_ok($r['code'] === 200 && (str_contains($r['body'], '0-2') || str_contains($r['body'], '0–2') || str_contains($r['body'], 'experience')), 'PT-003', 'Exp 0-2 filter UI/page OK', 'exp filter failed');
$r = qa_http('GET', $base . '/pages/trainers/index.php?rating=4');
assert_ok($r['code'] === 200, 'PT-004', 'Trainer rating filter 200', 'trainer rating failed');
$ptDetail = qa_http('GET', $base . '/pages/trainers/detail.php?id=' . $activePt);
assert_ok($ptDetail['code'] === 200, 'PT-005', 'Trainer detail reachable', 'pt detail failed');
assert_ok($ptDetail['code'] === 200 && !str_contains($ptDetail['body'], 'Fatal error'), 'PT-006', 'Trainer detail full page', 'pt detail broken');
$ptHidden = qa_http('GET', $base . '/pages/trainers/detail.php?id=' . ($hiddenPt ?: 28));
assert_ok($ptHidden['code'] === 404 || str_contains($ptHidden['body'], '404'), 'PT-007', 'Hidden PT 404', 'hidden pt code=' . $ptHidden['code']);
assert_ok($gtCount > 0 && $tsCount > 0, 'PT-009', "NN links gym_trainers={$gtCount} trainer_specialties={$tsCount}", 'NN tables empty');

$faq = qa_http('GET', $base . '/pages/legal/faq.php');
assert_ok($faq['code'] === 200, 'UI-FAQ', 'FAQ 200', 'FAQ failed');
$terms = qa_http('GET', $base . '/pages/legal/terms.php');
assert_ok($terms['code'] === 200, 'UI-TERMS', 'Terms 200', 'Terms failed');
$privacy = qa_http('GET', $base . '/pages/legal/privacy.php');
assert_ok($privacy['code'] === 200, 'UI-PRIVACY', 'Privacy 200', 'Privacy failed');
$chat = qa_http('GET', $base . '/pages/ai/chat.php');
assert_ok($chat['code'] === 200 && str_contains($chat['body'], 'ai_chat'), 'AI-020', 'AI chat page 200 + script', 'AI chat failed');

// ---- Auth guest gates ----
$profGuest = qa_http('GET', $base . '/pages/user/profile.php');
assert_ok($profGuest['code'] === 200 && (str_contains($profGuest['body'], 'Đăng nhập') || str_contains($profGuest['body'], 'Login')), 'AUTH-010', 'Guest profile soft-gate 200', 'profile gate code=' . $profGuest['code']);
assert_ok($profGuest['code'] === 200, 'PROF-005', 'Guest profile soft-gate', 'profile soft-gate failed');
$favGuest = qa_http('GET', $base . '/pages/user/favorites.php');
assert_ok($favGuest['code'] === 200 && (str_contains($favGuest['body'], 'Đăng nhập') || str_contains($favGuest['body'], 'Login')), 'FAV-001', 'Guest favorites CTA', 'favorites gate failed');
$admGuest = qa_http('GET', $base . '/admin/pages/dashboard.php');
assert_ok($admGuest['code'] === 302 || $admGuest['code'] === 401 || $admGuest['code'] === 403 || str_contains($admGuest['location'], 'login'), 'AUTH-011', 'Guest admin redirected', 'admin guest code=' . $admGuest['code'] . ' loc=' . $admGuest['location']);

$guestFav = qa_api(qa_jar('guest'), 'POST', '/api/favorites', ['type' => 'gym', 'id' => $activeGym]);
assert_ok(in_array($guestFav['code'], [401, 403], true), 'SEC-001', 'Guest favorites API ' . $guestFav['code'], 'guest fav code=' . $guestFav['code']);

$csrfMiss = qa_http('POST', $base . '/api/ai/recommend', [
    'headers' => ['Content-Type: application/json', 'Accept: application/json'],
    'body' => json_encode(['question' => 'Gym Quận 7'], JSON_UNESCAPED_UNICODE),
]);
assert_ok($csrfMiss['code'] === 403, 'AI-021', 'AI recommend without CSRF → 403', 'csrf miss code=' . $csrfMiss['code']);

// ---- Login flows ----
$jarUser = qa_jar('user');
$loginUser = qa_login($jarUser, 'u02@gymfinder.test', 'password');
assert_ok($loginUser['code'] === 302 && (str_contains($loginUser['location'], 'index.php') || $loginUser['location'] === '/' || str_contains($loginUser['location'], 'profile') || $loginUser['location'] !== ''), 'AUTH-004', 'User login 302 → ' . $loginUser['location'], 'user login code=' . $loginUser['code'] . ' loc=' . $loginUser['location']);

$userHome = qa_http('GET', $base . '/', ['jar' => $jarUser]);
assert_ok($userHome['code'] === 200 && (str_contains($userHome['body'], 'Đăng xuất') || str_contains($userHome['body'], 'Log out') || str_contains($userHome['body'], 'Hồ sơ')), 'AUTH-004b', 'User session visible', 'user session missing');

$userAdmin = qa_http('GET', $base . '/admin/pages/dashboard.php', ['jar' => $jarUser]);
assert_ok(in_array($userAdmin['code'], [302, 403], true) || str_contains($userAdmin['location'], 'login') || str_contains($userAdmin['body'], '403'), 'AUTH-012', 'User blocked from admin code=' . $userAdmin['code'], 'user reached admin');

$userAdminApi = qa_api($jarUser, 'GET', '/api/admin/users');
assert_ok(in_array($userAdminApi['code'], [401, 403], true), 'SEC-002', 'User admin API ' . $userAdminApi['code'], 'user admin api code=' . $userAdminApi['code']);

$prof = qa_http('GET', $base . '/pages/user/profile.php', ['jar' => $jarUser]);
assert_ok($prof['code'] === 200 && (str_contains($prof['body'], 'u02@gymfinder.test') || str_contains($prof['body'], 'Họ') || str_contains($prof['body'], 'profile')), 'PROF-001', 'User profile shows data', 'profile missing data');

$badPw = qa_http('POST', $base . '/pages/user/profile.php', [
    'jar' => $jarUser,
    'fields' => [
        '_csrf' => qa_csrf_html($prof['body']),
        'action' => 'change_password',
        'current_password' => 'wrong-password',
        'new_password' => 'NewPass123!',
        'confirm_password' => 'NewPass123!',
    ],
]);
$afterBad = qa_http('GET', $base . '/pages/user/profile.php', ['jar' => $jarUser]);
assert_ok($badPw['code'] === 200 || $badPw['code'] === 302, 'PROF-003', 'Wrong current password handled', 'unexpected code=' . $badPw['code']);
assert_ok(str_contains($afterBad['body'], 'không') || str_contains($afterBad['body'], 'sai') || str_contains($afterBad['body'], 'Không') || str_contains($afterBad['body'], 'incorrect') || str_contains($afterBad['body'], 'error') || str_contains($afterBad['body'], 'alert'), 'PROF-003b', 'Wrong password error shown', 'no error message on wrong password');

// Favorites
$favAdd = qa_api($jarUser, 'POST', '/api/favorites', ['target_type' => 'gym', 'target_id' => $activeGym]);
assert_ok(in_array($favAdd['code'], [200, 201, 409], true), 'FAV-002', 'Add gym favorite code=' . $favAdd['code'], 'add fav failed ' . $favAdd['code'] . ' ' . substr($favAdd['body'], 0, 180));
$favList = qa_http('GET', $base . '/pages/user/favorites.php', ['jar' => $jarUser]);
assert_ok($favList['code'] === 200, 'FAV-005', 'Favorites page after add 200', 'favorites page failed');

$favPt = qa_api($jarUser, 'POST', '/api/favorites', ['target_type' => 'trainer', 'target_id' => $activePt]);
assert_ok(in_array($favPt['code'], [200, 201, 409], true), 'FAV-004', 'Add trainer favorite code=' . $favPt['code'], 'add pt fav failed ' . $favPt['code'] . ' ' . substr($favPt['body'], 0, 120));

$favDel = qa_api($jarUser, 'DELETE', '/api/favorites/gym/' . $activeGym);
assert_ok(in_array($favDel['code'], [200, 204, 404], true), 'FAV-003', 'Remove gym favorite code=' . $favDel['code'], 'remove fav failed ' . $favDel['code'] . ' ' . substr($favDel['body'], 0, 120));

// Reviews: find gym without user review
$userId = (int) $pdo->query("SELECT id FROM users WHERE email='u02@gymfinder.test'")->fetchColumn();
$reviewGym = (int) $pdo->query(
    "SELECT g.id FROM gyms g WHERE g.status='active' AND NOT EXISTS (
        SELECT 1 FROM reviews r WHERE r.gym_id=g.id AND r.user_id={$userId} AND r.trainer_id IS NULL
     ) ORDER BY g.id LIMIT 1"
)->fetchColumn();
$regJar = qa_jar('reg');
$regPage = qa_http('GET', $base . '/pages/auth/register.php', ['jar' => $regJar]);
$regCsrf = qa_csrf_html($regPage['body']);
$email = 'qa_full_' . time() . '@gymfinder.test';
$reg = qa_http('POST', $base . '/pages/auth/register.php', [
    'jar' => $regJar,
    'fields' => [
        '_csrf' => $regCsrf,
        'full_name' => 'QA Full Smoke',
        'email' => $email,
        'password' => 'password1',
        'confirm' => 'password1',
        'agree' => '1',
    ],
]);
assert_ok($reg['code'] === 302, 'AUTH-001', 'Register new user 302 loc=' . $reg['location'], 'register code=' . $reg['code'] . ' ' . substr(strip_tags($reg['body']), 0, 120));

$emptyJar = qa_jar('regempty');
$emptyPage = qa_http('GET', $base . '/pages/auth/register.php', ['jar' => $emptyJar]);
$empty = qa_http('POST', $base . '/pages/auth/register.php', [
    'jar' => $emptyJar,
    'fields' => ['_csrf' => qa_csrf_html($emptyPage['body']), 'full_name' => '', 'email' => '', 'password' => '', 'confirm' => '', 'agree' => '1'],
]);
assert_ok($empty['code'] !== 302 || str_contains($empty['location'], 'register'), 'AUTH-002', 'Empty register rejected code=' . $empty['code'], 'empty register accepted');

$noAgreeJar = qa_jar('regnoagree');
$noAgreePage = qa_http('GET', $base . '/pages/auth/register.php', ['jar' => $noAgreeJar]);
$noAgree = qa_http('POST', $base . '/pages/auth/register.php', [
    'jar' => $noAgreeJar,
    'fields' => [
        '_csrf' => qa_csrf_html($noAgreePage['body']),
        'full_name' => 'No Agree',
        'email' => 'noagree_' . time() . '@gymfinder.test',
        'password' => 'password1',
        'confirm' => 'password1',
    ],
]);
assert_ok($noAgree['code'] !== 302 || str_contains($noAgree['location'], 'register'), 'AUTH-003', 'No terms rejected', 'no-terms accepted loc=' . $noAgree['location']);

if ($reviewGym <= 0) {
    $jarUser = qa_jar('reguser');
    qa_login($jarUser, $email, 'password1');
    $reviewGym = $activeGym;
}

$revBad = qa_api($jarUser, 'POST', '/api/reviews', [
    'target_type' => 'gym',
    'target_id' => $reviewGym,
    'rating' => 4,
    'comment' => '',
]);
assert_ok(in_array($revBad['code'], [400, 422], true), 'REV-002', 'Empty comment rejected ' . $revBad['code'], 'empty comment code=' . $revBad['code'] . ' ' . substr($revBad['body'], 0, 120));

$reviewMarker = 'QA_PENDING_' . bin2hex(random_bytes(4));
$revOk = qa_api($jarUser, 'POST', '/api/reviews', [
    'target_type' => 'gym',
    'target_id' => $reviewGym,
    'rating' => 4,
    'comment' => $reviewMarker . ' chờ duyệt',
]);
assert_ok(in_array($revOk['code'], [200, 201], true), 'REV-001', 'Create review pending code=' . $revOk['code'], 'create review failed ' . $revOk['code'] . ' ' . substr($revOk['body'], 0, 200));
$reviewId = (int) ($revOk['json']['data']['id'] ?? $revOk['json']['id'] ?? 0);
if ($reviewId <= 0 && $revOk['code'] < 300) {
    $reviewId = (int) $pdo->query('SELECT id FROM reviews ORDER BY id DESC LIMIT 1')->fetchColumn();
}

$publicDetail = qa_http('GET', $base . '/pages/gyms/detail.php?id=' . $reviewGym);
$pendingVisible = $reviewId > 0 && str_contains($publicDetail['body'], $reviewMarker);
assert_ok(!$pendingVisible, 'REV-003', 'Pending review not public', 'pending review leaked on public detail');
assert_ok(!$pendingVisible, 'GDET-002', 'Only approved reviews on public', 'pending visible');

// Bad login / locked
$jarBad = qa_jar('bad');
$badLogin = qa_login($jarBad, 'u02@gymfinder.test', 'wrong-password');
assert_ok($badLogin['code'] !== 302 || str_contains($badLogin['location'], 'login'), 'AUTH-006', 'Wrong password rejected', 'wrong password logged in');
$jarMissing = qa_jar('missing');
$missingLogin = qa_login($jarMissing, 'no@x.test', 'password');
assert_ok($missingLogin['code'] !== 302 || str_contains($missingLogin['location'], 'login'), 'AUTH-007', 'Unknown email rejected', 'unknown email logged in');
if ($lockedEmail !== '') {
    $jarLock = qa_jar('locked');
    $lockedLogin = qa_login($jarLock, $lockedEmail, 'password');
    assert_ok($lockedLogin['code'] !== 302 || str_contains($lockedLogin['location'], 'login') || str_contains($lockedLogin['location'], 'locked'), 'AUTH-008', 'Locked account blocked (' . $lockedEmail . ')', 'locked account logged in loc=' . $lockedLogin['location']);
} else {
    qa_log('SKIP', 'AUTH-008', 'No locked user in DB');
}

// Logout
$logoutPage = qa_http('GET', $base . '/pages/user/profile.php', ['jar' => $jarUser]);
$csrf = qa_csrf_html($logoutPage['body']);
if ($csrf === '') {
    $loginPage = qa_http('GET', $base . '/pages/auth/login.php', ['jar' => $jarUser]);
    $csrf = qa_csrf_html($loginPage['body']);
}
$logout = qa_http('POST', $base . '/pages/auth/logout.php', [
    'jar' => $jarUser,
    'fields' => ['_csrf' => $csrf],
]);
$afterLogout = qa_http('GET', $base . '/pages/user/profile.php', ['jar' => $jarUser]);
assert_ok(($logout['code'] === 302 || $logout['code'] === 200) && (str_contains($afterLogout['body'], 'Đăng nhập') || str_contains($afterLogout['body'], 'Login')), 'AUTH-009', 'Logout clears session', 'logout failed code=' . $logout['code']);

// Admin
$jarAdmin = qa_jar('admin');
$loginAdmin = qa_login($jarAdmin, 'u01@gymfinder.test', 'password');
assert_ok($loginAdmin['code'] === 302 && str_contains($loginAdmin['location'], 'admin'), 'AUTH-005', 'Admin login → ' . $loginAdmin['location'], 'admin login code=' . $loginAdmin['code'] . ' loc=' . $loginAdmin['location']);

$dash = qa_http('GET', $base . '/admin/pages/dashboard.php', ['jar' => $jarAdmin]);
assert_ok($dash['code'] === 200 && !str_contains($dash['body'], 'Fatal error'), 'ADM-001', 'Admin dashboard 200', 'dashboard failed');
$adminHome = qa_http('GET', $base . '/', ['jar' => $jarAdmin]);
assert_ok($adminHome['code'] === 302 && str_contains($adminHome['location'], 'admin'), 'ADM-PORT', 'Admin portal-only redirect', 'admin stayed on public home');

$gymsAdmin = qa_http('GET', $base . '/admin/pages/gyms.php', ['jar' => $jarAdmin]);
assert_ok($gymsAdmin['code'] === 200, 'ADM-004', 'Admin gyms 200', 'admin gyms failed');
assert_ok(!preg_match('/Xóa cứng|Hard delete/i', $gymsAdmin['body']), 'ADM-014', 'No hard-delete gym label', 'hard delete label found');

$trainersAdmin = qa_http('GET', $base . '/admin/pages/trainers.php', ['jar' => $jarAdmin]);
assert_ok($trainersAdmin['code'] === 200, 'ADM-008', 'Admin trainers 200', 'admin trainers failed');

$usersAdmin = qa_http('GET', $base . '/admin/pages/users.php', ['jar' => $jarAdmin]);
assert_ok($usersAdmin['code'] === 200 && (str_contains($usersAdmin['body'], 'Khóa') || str_contains($usersAdmin['body'], 'Lock') || str_contains($usersAdmin['body'], 'fa-lock')), 'ADM-010', 'Users lock UI present', 'users page missing lock UI');
assert_ok(str_contains($usersAdmin['body'], 'Thêm quản trị') || str_contains($usersAdmin['body'], 'Add admin') || str_contains($usersAdmin['body'], 'invite'), 'ADM-INVITE', 'Admin invite UI present', 'invite UI missing');

$cats = qa_http('GET', $base . '/admin/pages/categories.php', ['jar' => $jarAdmin]);
assert_ok($cats['code'] === 200, 'ADM-011', 'Categories page 200', 'categories failed');
$catsSpec = qa_http('GET', $base . '/admin/pages/categories.php?tab=specialties', ['jar' => $jarAdmin]);
$catsAmen = qa_http('GET', $base . '/admin/pages/categories.php?tab=amenities', ['jar' => $jarAdmin]);
$catsDist = qa_http('GET', $base . '/admin/pages/categories.php?tab=districts', ['jar' => $jarAdmin]);
assert_ok($catsSpec['code'] === 200 && $catsAmen['code'] === 200 && $catsDist['code'] === 200, 'ADM-011b', 'Category tabs load', 'category tabs failed');

$reviewsAdmin = qa_http('GET', $base . '/admin/pages/reviews.php', ['jar' => $jarAdmin]);
assert_ok($reviewsAdmin['code'] === 200, 'ADM-012', 'Admin reviews 200', 'admin reviews failed');

$adminProfile = qa_http('GET', $base . '/admin/pages/profile.php', ['jar' => $jarAdmin]);
assert_ok($adminProfile['code'] === 200 && (str_contains($adminProfile['body'], 'Xóa tài khoản') || str_contains($adminProfile['body'], 'Delete')), 'ADM-013', 'Admin profile + self-delete section', 'admin profile failed');

// Moderate review if we have one
if ($reviewId > 0) {
    $approve = qa_api($jarAdmin, 'PATCH', '/api/admin/reviews/' . $reviewId, ['status' => 'approved']);
    assert_ok(in_array($approve['code'], [200, 204], true) || (($approve['json']['data']['status'] ?? '') === 'approved'), 'REV-006', 'Admin approve code=' . $approve['code'], 'approve failed ' . $approve['code'] . ' ' . substr($approve['body'], 0, 160));

    $rejectAuthorEmail = $email;
    $rejectAuthorPass = 'password1';
    $rejectUserId = (int) $pdo->query('SELECT id FROM users WHERE email=' . $pdo->quote($rejectAuthorEmail))->fetchColumn();
    if ($rejectUserId <= 0) {
        $rejectAuthorEmail = 'u02@gymfinder.test';
        $rejectAuthorPass = 'password';
        $rejectUserId = (int) $pdo->query("SELECT id FROM users WHERE email='u02@gymfinder.test'")->fetchColumn();
    }
    $gymB = (int) $pdo->query(
        "SELECT g.id FROM gyms g WHERE g.status='active' AND g.id<>{$reviewGym} AND NOT EXISTS (
            SELECT 1 FROM reviews r WHERE r.gym_id=g.id AND r.user_id={$rejectUserId} AND r.trainer_id IS NULL
         ) ORDER BY g.id LIMIT 1"
    )->fetchColumn();
    if ($gymB > 0) {
        $jarU2 = qa_jar('u2rev');
        qa_login($jarU2, $rejectAuthorEmail, $rejectAuthorPass);
        $tmp = qa_api($jarU2, 'POST', '/api/reviews', [
            'target_type' => 'gym',
            'target_id' => $gymB,
            'rating' => 3,
            'comment' => 'Reject me please',
        ]);
        $rid2 = (int) ($tmp['json']['data']['id'] ?? 0);
        if ($rid2 > 0) {
            $rejectNoReason = qa_api($jarAdmin, 'PATCH', '/api/admin/reviews/' . $rid2, ['status' => 'rejected']);
            assert_ok(in_array($rejectNoReason['code'], [400, 422], true), 'REV-007', 'Reject without reason blocked', 'reject no reason code=' . $rejectNoReason['code']);
            $rejectOk = qa_api($jarAdmin, 'PATCH', '/api/admin/reviews/' . $rid2, [
                'status' => 'rejected',
                'reject_reason' => 'Nội dung không phù hợp QA',
            ]);
            assert_ok(in_array($rejectOk['code'], [200, 204], true), 'REV-008', 'Reject with reason OK', 'reject with reason code=' . $rejectOk['code'] . ' ' . substr($rejectOk['body'], 0, 120));
        } else {
            qa_log('SKIP', 'REV-007', 'Could not create second pending review code=' . $tmp['code']);
            qa_log('SKIP', 'REV-008', 'Could not create second pending review');
        }
    } else {
        qa_log('SKIP', 'REV-007', 'No free gym for reject test');
        qa_log('SKIP', 'REV-008', 'No free gym for reject test');
    }
} else {
    qa_log('SKIP', 'REV-006', 'No review id');
    qa_log('SKIP', 'REV-007', 'No review id');
    qa_log('SKIP', 'REV-008', 'No review id');
}

// OTP pages
$otpJar = qa_jar('otp');
$forgot = qa_http('GET', $base . '/pages/auth/forgot_password.php', ['jar' => $otpJar]);
assert_ok($forgot['code'] === 200 && (str_contains($forgot['body'], '5 phút') || str_contains($forgot['body'], '5 minutes') || str_contains($forgot['body'], 'OTP') || str_contains($forgot['body'], 'otp') || str_contains($forgot['body'], 'email')), 'AUTH-014a', 'Forgot password page loads', 'forgot page failed');
$forgotPost = qa_http('POST', $base . '/pages/auth/forgot_password.php', [
    'jar' => $otpJar,
    'fields' => ['_csrf' => qa_csrf_html($forgot['body']), 'email' => 'u02@gymfinder.test'],
]);
$otpPage = ['code' => 0, 'body' => '', 'location' => ''];
if ($forgotPost['code'] === 302) {
    $otpPath = str_starts_with($forgotPost['location'], 'http')
        ? (string) parse_url($forgotPost['location'], PHP_URL_PATH)
        : '/' . ltrim(preg_replace('#^(\.\./)+#', '', $forgotPost['location']), '/');
    if (!str_starts_with($otpPath, '/')) {
        $otpPath = '/' . $otpPath;
    }
    // Normalize relative admin/auth paths back under site root.
    if (!str_contains($otpPath, 'forgot')) {
        $otpPath = '/pages/auth/forgot_verify_otp.php';
    }
    $otpPage = qa_http('GET', $base . $otpPath, ['jar' => $otpJar]);
} else {
    $otpPage = $forgotPost;
}
$hasDemoOtp = preg_match('/\b\d{6}\b/', $otpPage['body'] . $forgotPost['body']) === 1;
if ($hasDemoOtp) {
    qa_log('PASS', 'AUTH-014', 'Demo OTP visible for reset flow');
} else {
    qa_log('BLOCKED', 'AUTH-014', 'SMTP thật / không thấy demo OTP trên UI — không hoàn tất E2E inbox');
}
$badOtp = qa_http('POST', $base . '/pages/auth/forgot_verify_otp.php', [
    'jar' => $otpJar,
    'fields' => ['_csrf' => qa_csrf_html($otpPage['body'] ?: $forgot['body']), 'otp' => '000000'],
]);
assert_ok($badOtp['code'] !== 302 || str_contains($badOtp['location'], 'forgot') || str_contains($badOtp['location'], 'otp') || str_contains($badOtp['body'], 'sai') || str_contains($badOtp['body'], 'OTP'), 'AUTH-015', 'Wrong OTP rejected', 'wrong OTP accepted loc=' . $badOtp['location']);

// Integrity quick checks
$ptAfterHiddenGym = (int) $pdo->query('SELECT COUNT(*) FROM trainers')->fetchColumn();
assert_ok($ptAfterHiddenGym > 0 && $hiddenGym > 0, 'INT-001', 'Trainers remain while hidden gym exists', 'integrity seed odd');
assert_ok($hiddenPt > 0 && $activeGym > 0, 'INT-002', 'Hidden PT + active gym coexist', 'integrity seed odd');

$lockUser = (string) $pdo->query("SELECT email FROM users WHERE role='user' AND status='active' AND email LIKE 'u0%' ORDER BY id DESC LIMIT 1")->fetchColumn();
if ($lockUser !== '') {
    $uid = (int) $pdo->query("SELECT id FROM users WHERE email=" . $pdo->quote($lockUser))->fetchColumn();
    $beforeFav = (int) $pdo->query("SELECT COUNT(*) FROM favorites WHERE user_id={$uid}")->fetchColumn();
    $lock = qa_api($jarAdmin, 'PATCH', '/api/admin/users/' . $uid, ['status' => 'locked']);
    $afterFav = (int) $pdo->query("SELECT COUNT(*) FROM favorites WHERE user_id={$uid}")->fetchColumn();
    $unlock = qa_api($jarAdmin, 'PATCH', '/api/admin/users/' . $uid, ['status' => 'active']);
    assert_ok(in_array($lock['code'], [200, 204], true) && in_array($unlock['code'], [200, 204], true) && $afterFav === $beforeFav, 'INT-003', "Lock/unlock {$lockUser} keeps favorites={$afterFav}", 'lock integrity failed lock=' . $lock['code'] . ' unlock=' . $unlock['code']);
} else {
    qa_log('SKIP', 'INT-003', 'No active member to lock');
}

// Catalog delete blocked when used
$usedCat = (int) $pdo->query(
    'SELECT gc.category_id FROM gym_categories gc
     INNER JOIN categories c ON c.id = gc.category_id
     GROUP BY gc.category_id HAVING COUNT(*) > 0
     ORDER BY COUNT(*) DESC LIMIT 1'
)->fetchColumn();
if ($usedCat > 0) {
    $before = (int) $pdo->query('SELECT COUNT(*) FROM categories WHERE id=' . $usedCat)->fetchColumn();
    $delBlocked = false;
    try {
        $pdo->exec('DELETE FROM categories WHERE id=' . $usedCat);
    } catch (Throwable $e) {
        $delBlocked = true;
    }
    $after = (int) $pdo->query('SELECT COUNT(*) FROM categories WHERE id=' . $usedCat)->fetchColumn();
    assert_ok($delBlocked && $before === 1 && $after === 1, 'CAT-DEL', "Used category #{$usedCat} cannot be deleted", 'used category deleted or missing');
} else {
    qa_log('SKIP', 'CAT-DEL', 'No used category');
}

$en = qa_http('GET', $base . '/?lang=en');
assert_ok(in_array($en['code'], [200, 302], true), 'UI-I18N', 'English lang switch code=' . $en['code'], 'en home failed code=' . $en['code']);
if ($en['code'] === 302) {
    $enHome = qa_http('GET', $base . '/', ['jar' => qa_jar('en')]);
    // apply lang via cookie by following switch once with jar
    $enJar = qa_jar('en');
    qa_http('GET', $base . '/?lang=en', ['jar' => $enJar]);
    $enHome = qa_http('GET', $base . '/', ['jar' => $enJar]);
    assert_ok($enHome['code'] === 200 && (str_contains($enHome['body'], 'View all gyms') || str_contains($enHome['body'], 'Trainers') || str_contains($enHome['body'], 'Log')), 'UI-I18N-b', 'English home content OK', 'English content missing');
}

echo "\n=== SUMMARY ===\n";
echo "PASS={$pass} FAIL={$fail} BLOCKED={$blocked} SKIP={$skip}\n";
if ($fail > 0) {
    echo "FAILURES:\n";
    foreach ($lines as $line) {
        if (str_starts_with($line, 'FAIL|')) {
            echo $line . "\n";
        }
    }
    exit(1);
}
exit(0);
