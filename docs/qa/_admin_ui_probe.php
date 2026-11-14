<?php
/**
 * Probe sâu UI admin ↔ BE/DB theo ADM-* (TEST_CASES.md).
 * Chạy: GF_QA_BASE=http://localhost:8000 php docs/qa/_admin_ui_probe.php
 */
declare(strict_types=1);

$base = rtrim(getenv('GF_QA_BASE') ?: 'http://localhost:8000', '/');
$cookieDir = sys_get_temp_dir() . '/gf_qa_adm_' . getmypid();
@mkdir($cookieDir);

$pass = 0;
$fail = 0;

function qa_log(string $status, string $id, string $msg): void
{
    global $pass, $fail;
    if ($status === 'PASS') {
        $pass++;
    } else {
        $fail++;
    }
    echo "{$status}|{$id}|{$msg}\n";
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
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);
    if ($jar) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $jar);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $jar);
    }
    if ($fields !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    }
    if ($headers) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $resp = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    preg_match('/^Location:\s*(.+)$/mi', $rawHeaders, $m);
    return ['code' => $code, 'body' => (string) $resp, 'location' => trim($m[1] ?? '')];
}

function qa_csrf_html(string $html): string
{
    return preg_match('/name="_csrf"\s+value="([^"]+)"/', $html, $m) ? $m[1] : '';
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

require dirname(__DIR__, 2) . '/backend/bootstrap.php';

$jar = qa_jar('admin');
$login = qa_login($jar, 'u01@gymfinder.test');
qa_log(
    ($login['code'] === 302 && str_contains($login['location'], 'admin')) ? 'PASS' : 'FAIL',
    'AUTH-005',
    'Admin login code=' . $login['code'] . ' loc=' . $login['location']
);

$dash = qa_http('GET', $base . '/admin/pages/dashboard.php', ['jar' => $jar]);
$stats = gf_admin_stats();
$html = $dash['body'];
qa_log($dash['code'] === 200 ? 'PASS' : 'FAIL', 'ADM-001', 'Dashboard HTTP ' . $dash['code']);
foreach (['Tổng phòng tập', 'Huấn luyện viên', 'Người dùng hệ thống', 'Đánh giá chờ duyệt'] as $label) {
    qa_log(str_contains($html, $label) ? 'PASS' : 'FAIL', 'ADM-001-card', $label);
}
qa_log(
    str_contains($html, 'hội viên') && str_contains($html, 'quản trị viên') ? 'PASS' : 'FAIL',
    'ADM-001-breakdown',
    'member/admin labels + total=' . (int) $stats['total_users']
);
qa_log(
    (int) $stats['total_users'] === ((int) $stats['users'] + (int) $stats['admins']) ? 'PASS' : 'FAIL',
    'ADM-001-db',
    'stats from MySQL users/admins'
);
qa_log(
    !preg_match('/Cập nhật\s+\d{1,2}:\d{2}/u', $html) ? 'PASS' : 'FAIL',
    'ADM-015',
    'Timestamp live đã gỡ khỏi dashboard (đúng thiết kế hiện tại)'
);

$gymsPage = qa_http('GET', $base . '/admin/pages/gyms.php', ['jar' => $jar]);
qa_log($gymsPage['code'] === 200 ? 'PASS' : 'FAIL', 'ADM-004', 'Gyms list');
qa_log(
    str_contains($gymsPage['body'], 'assets/images/gym-placeholder.svg') ? 'PASS' : 'FAIL',
    'UI-cover',
    'Add-gym cover uses placeholder.svg'
);
qa_log(
    !preg_match('/Xóa cứng|Hard delete/i', $gymsPage['body']) ? 'PASS' : 'FAIL',
    'ADM-014-gym',
    'No hard-delete gym'
);

$csrf = qa_csrf_html($gymsPage['body']);
$pdo = gf_pdo();
$gymId = (int) $pdo->query("SELECT g.id FROM gyms g JOIN gym_trainers gt ON gt.gym_id=g.id WHERE g.status='active' LIMIT 1")->fetchColumn();
$hide = qa_http('POST', $base . '/admin/pages/gyms.php', [
    'jar' => $jar,
    'fields' => ['_csrf' => $csrf, 'id' => $gymId, 'status' => 'hidden'],
]);
$st = (string) $pdo->query("SELECT status FROM gyms WHERE id={$gymId}")->fetchColumn();
qa_log($st === 'hidden' ? 'PASS' : 'FAIL', 'ADM-005', "UI POST hide gym #{$gymId} → {$st} http={$hide['code']}");
$pub = qa_http('GET', $base . '/pages/gyms/detail.php?id=' . $gymId);
qa_log(
    ($pub['code'] === 404 || str_contains($pub['body'], 'Không tìm') || str_contains($pub['body'], 'không tồn tại') || str_contains($pub['body'], 'tạm ẩn')) ? 'PASS' : 'FAIL',
    'ADM-005-public',
    'Hidden gym public code=' . $pub['code']
);
$ptKeep = (int) $pdo->query("SELECT COUNT(*) FROM gym_trainers WHERE gym_id={$gymId}")->fetchColumn();
qa_log($ptKeep > 0 ? 'PASS' : 'FAIL', 'INT-001', "trainers links remain={$ptKeep}");

$csrf = qa_csrf_html(qa_http('GET', $base . '/admin/pages/gyms.php', ['jar' => $jar])['body']);
qa_http('POST', $base . '/admin/pages/gyms.php', [
    'jar' => $jar,
    'fields' => ['_csrf' => $csrf, 'id' => $gymId, 'status' => 'active'],
]);
$st2 = (string) $pdo->query("SELECT status FROM gyms WHERE id={$gymId}")->fetchColumn();
qa_log($st2 === 'active' ? 'PASS' : 'FAIL', 'ADM-006', "Reactivate gym #{$gymId} → {$st2}");

$trainersPage = qa_http('GET', $base . '/admin/pages/trainers.php', ['jar' => $jar]);
qa_log($trainersPage['code'] === 200 ? 'PASS' : 'FAIL', 'ADM-008', 'Trainers list');
qa_log(str_contains($trainersPage['body'], 'admin-cat-badge') ? 'PASS' : 'FAIL', 'UI-spec-badge', 'Specialty badges use admin-cat-badge');
$csrf = qa_csrf_html($trainersPage['body']);
$trId = (int) $pdo->query("SELECT id FROM trainers WHERE status='active' LIMIT 1")->fetchColumn();
qa_http('POST', $base . '/admin/pages/trainers.php', [
    'jar' => $jar,
    'fields' => ['_csrf' => $csrf, 'id' => $trId, 'status' => 'hidden'],
]);
$trSt = (string) $pdo->query("SELECT status FROM trainers WHERE id={$trId}")->fetchColumn();
qa_log($trSt === 'hidden' ? 'PASS' : 'FAIL', 'ADM-009', "Hide trainer #{$trId} → {$trSt}");
$csrf = qa_csrf_html(qa_http('GET', $base . '/admin/pages/trainers.php', ['jar' => $jar])['body']);
qa_http('POST', $base . '/admin/pages/trainers.php', [
    'jar' => $jar,
    'fields' => ['_csrf' => $csrf, 'id' => $trId, 'status' => 'active'],
]);

$usersPage = qa_http('GET', $base . '/admin/pages/users.php', ['jar' => $jar]);
qa_log(
    $usersPage['code'] === 200 && (str_contains($usersPage['body'], 'fa-lock') || str_contains($usersPage['body'], 'Khóa')) ? 'PASS' : 'FAIL',
    'ADM-010',
    'Users lock UI'
);
qa_log(
    str_contains($usersPage['body'], 'Thêm quản trị') ? 'PASS' : 'FAIL',
    'ADM-INVITE',
    'Invite admin UI'
);

$cats = qa_http('GET', $base . '/admin/pages/categories.php?tab=specialties', ['jar' => $jar]);
qa_log($cats['code'] === 200 ? 'PASS' : 'FAIL', 'ADM-011', 'Specialties tab');
qa_log(str_contains($cats['body'], 'is-blocked') ? 'PASS' : 'FAIL', 'ADM-011-blocked', 'Used specialty delete blocked');

$featTab = qa_http('GET', $base . '/admin/pages/categories.php?tab=features', ['jar' => $jar]);
qa_log($featTab['code'] === 200 ? 'PASS' : 'FAIL', 'ADM-011-features', 'Features catalog tab');
qa_log(
    str_contains($featTab['body'], 'Mở cửa 24/7') || str_contains($featTab['body'], 'open-247') || str_contains($featTab['body'], 'Đặc điểm')
        ? 'PASS' : 'FAIL',
    'ADM-011-features-rows',
    'Features catalog has rows/UI'
);
$gymsFeat = qa_http('GET', $base . '/admin/pages/gyms.php', ['jar' => $jar]);
qa_log(
    str_contains($gymsFeat['body'], 'name="feature_ids[]"') || str_contains($gymsFeat['body'], 'gym-feature')
        ? 'PASS' : 'FAIL',
    'ADM-004-features',
    'Gym form has feature_ids checkboxes'
);

$rev = qa_http('GET', $base . '/admin/pages/reviews.php?status=pending', ['jar' => $jar]);
qa_log($rev['code'] === 200 ? 'PASS' : 'FAIL', 'ADM-012', 'Reviews moderation page');
qa_log(
    str_contains($rev['body'], 'validateRejectForm') && str_contains($rev['body'], 'data-required-msg') ? 'PASS' : 'FAIL',
    'REV-007-ui',
    'Reject reason required + toast validate'
);
qa_log(
    !str_contains(file_get_contents(dirname(__DIR__, 2) . '/admin/pages/reviews.php'), "Không phù hợp") ? 'PASS' : 'FAIL',
    'REV-007-be',
    'No default reject reason fallback in UI POST'
);

$prof = qa_http('GET', $base . '/admin/pages/profile.php', ['jar' => $jar]);
qa_log($prof['code'] === 200 ? 'PASS' : 'FAIL', 'ADM-013', 'Admin profile');

$js = qa_http('GET', $base . '/admin/assets/admin.js', ['jar' => $jar]);
qa_log(
    str_contains($js['body'], 'gfFilterToastBound') ? 'PASS' : 'FAIL',
    'UI-filter-toast',
    'Empty Tra cứu toast wired'
);

$logoOk = str_contains($gymsPage['body'], 'admin/pages/dashboard.php') || str_contains($html, 'admin/pages/dashboard.php');
qa_log($logoOk ? 'PASS' : 'FAIL', 'ADM-002', 'Dashboard link present in admin chrome');

echo "\n=== SUMMARY ===\nPASS={$pass} FAIL={$fail}\n";
exit($fail > 0 ? 1 : 0);
