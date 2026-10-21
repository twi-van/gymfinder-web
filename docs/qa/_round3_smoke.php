<?php
/**
 * QA vòng 3 smoke — chạy: php docs/qa/_round3_smoke.php
 * Yêu cầu: php -S localhost:8000 router.php
 */
declare(strict_types=1);

$base = getenv('GF_QA_BASE') ?: 'http://127.0.0.1:8000';
$cookieDir = sys_get_temp_dir() . '/gf_qa3_' . getmypid();
@mkdir($cookieDir);
$results = [];

function qa_log(string $status, string $id, string $msg): void
{
    global $results;
    $results[] = [$status, $id, $msg];
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
    $body = $opts['body'] ?? null;
    $fields = $opts['fields'] ?? null;

    $ch = curl_init($url);
    $hdrFile = tempnam(sys_get_temp_dir(), 'hdr');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HEADER => false,
        CURLOPT_HEADERFUNCTION => static function ($ch, $line) use (&$rawHeaders) {
            $rawHeaders .= $line;
            return strlen($line);
        },
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    $rawHeaders = '';
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
        // curl_close deprecated no-op on PHP 8+
    if ($err) {
        return ['code' => 0, 'body' => '', 'headers' => '', 'error' => $err];
    }
    preg_match('/^Location:\s*(.+)$/mi', $rawHeaders, $m);
    return ['code' => $code, 'body' => (string) $resp, 'headers' => $rawHeaders, 'location' => trim($m[1] ?? '')];
}

function qa_csrf_html(string $html): string
{
    if (preg_match('/name="_csrf"\s+value="([^"]+)"/', $html, $m)) {
        return $m[1];
    }
    return '';
}

function qa_api_csrf(string $jar): string
{
    global $base;
    $r = qa_http('GET', $base . '/api/auth/csrf', ['jar' => $jar]);
    $j = json_decode($r['body'], true);
    return (string) ($j['data']['csrf_token'] ?? '');
}

function qa_login(string $jar, string $email, string $password = 'password'): bool
{
    global $base;
    $page = qa_http('GET', $base . '/pages/auth/login.php', ['jar' => $jar]);
    $csrf = qa_csrf_html($page['body']);
    $r = qa_http('POST', $base . '/pages/auth/login.php', [
        'jar' => $jar,
        'fields' => ['_csrf' => $csrf, 'login' => $email, 'password' => $password],
    ]);
    return $r['code'] === 302;
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

$pdo = new PDO('sqlite:' . dirname(__DIR__, 2) . '/database/gymfinder.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$gyms = $pdo->query("SELECT id FROM gyms WHERE status='active' ORDER BY id LIMIT 2")->fetchAll(PDO::FETCH_COLUMN);
$trainer = (int) $pdo->query("SELECT t.id FROM trainers t WHERE t.status='active' AND EXISTS(SELECT 1 FROM gym_trainers gt WHERE gt.trainer_id=t.id) AND EXISTS(SELECT 1 FROM trainer_specialties ts WHERE ts.trainer_id=t.id) ORDER BY t.id LIMIT 1")->fetchColumn();
$gym = (int) $gyms[0];
$gym2 = (int) $gyms[1];

$email = 'qa3_' . time() . '@gymfinder.test';
$gJar = qa_jar('guest');
$reg = qa_http('GET', $base . '/pages/auth/register.php', ['jar' => $gJar]);
$csrf = qa_csrf_html($reg['body']);
$r = qa_http('POST', $base . '/pages/auth/register.php', [
    'jar' => $gJar,
    'fields' => [
        '_csrf' => $csrf,
        'full_name' => 'QA3 Runner',
        'email' => $email,
        'password' => 'password1',
        'confirm' => 'password1',
        'agree' => '1',
    ],
]);
if ($r['code'] === 302 && str_contains($r['location'], 'login')) {
    qa_log('PASS', 'AUTH-001', "registered {$email}");
} else {
    qa_log('FAIL', 'AUTH-001', "HTTP {$r['code']} {$r['location']}");
}

$uJar = qa_jar('user');
$aJar = qa_jar('admin');
if (!qa_login($uJar, $email, 'password1')) {
    qa_log('FAIL', 'SETUP', 'login new user failed');
    exit(1);
}
if (!qa_login($aJar, 'u01@gymfinder.test')) {
    qa_log('FAIL', 'SETUP', 'login admin failed');
    exit(1);
}
qa_log('PASS', 'SETUP', "gym={$gym} gym2={$gym2} trainer={$trainer}");

// Favorites
$r = qa_api($uJar, 'POST', '/api/favorites', ['target_type' => 'gym', 'target_id' => $gym]);
$r['code'] === 201 ? qa_log('PASS', 'FAV-002', "add gym {$gym}") : qa_log('FAIL', 'FAV-002', "HTTP {$r['code']} {$r['body']}");

$r = qa_api($uJar, 'GET', '/api/favorites');
$items = $r['json']['data']['items'] ?? [];
$found = false;
foreach ($items as $it) {
    $tid = (int) ($it['target_id'] ?? $it['gym_id'] ?? ($it['gym']['id'] ?? 0));
    $type = (string) ($it['target_type'] ?? (($it['gym_id'] ?? null) ? 'gym' : ''));
    if ($type === 'gym' && $tid === $gym) {
        $found = true;
    }
    // list item may nest
    if ((int) ($it['gym']['id'] ?? 0) === $gym) {
        $found = true;
    }
}
if (!$found && $items) {
    // dump keys for debug once
    $sample = json_encode($items[0], JSON_UNESCAPED_UNICODE);
}
$found ? qa_log('PASS', 'FAV-005', 'listed after add') : qa_log('FAIL', 'FAV-005', 'missing in list ' . ($sample ?? 'empty'));

$r = qa_api($uJar, 'POST', '/api/favorites', ['target_type' => 'gym', 'target_id' => $gym]);
$r['code'] === 409 ? qa_log('PASS', 'FAV-006', 'duplicate 409') : qa_log('FAIL', 'FAV-006', "HTTP {$r['code']} {$r['body']}");

$r = qa_api($uJar, 'DELETE', '/api/favorites/gym/' . $gym);
in_array($r['code'], [204, 200], true) ? qa_log('PASS', 'FAV-003', 'removed') : qa_log('FAIL', 'FAV-003', "HTTP {$r['code']}");

$r = qa_api($uJar, 'POST', '/api/favorites', ['target_type' => 'trainer', 'target_id' => $trainer]);
$r['code'] === 201 ? qa_log('PASS', 'FAV-004', "add trainer {$trainer}") : qa_log('FAIL', 'FAV-004', "HTTP {$r['code']} {$r['body']}");
qa_api($uJar, 'DELETE', '/api/favorites/trainer/' . $trainer);

// Reviews
$r = qa_api($uJar, 'POST', '/api/reviews', ['target_type' => 'gym', 'target_id' => $gym, 'rating' => 4, 'comment' => '']);
$r['code'] === 422 ? qa_log('PASS', 'REV-002', 'empty comment') : qa_log('FAIL', 'REV-002', "HTTP {$r['code']} {$r['body']}");

$r = qa_api($uJar, 'POST', '/api/reviews', [
    'target_type' => 'gym', 'target_id' => $gym, 'rating' => 4,
    'comment' => 'QA3 pending review body text.',
]);
$rid = (int) ($r['json']['data']['id'] ?? 0);
($r['code'] === 201 && $rid > 0) ? qa_log('PASS', 'REV-001', "id={$rid}") : qa_log('FAIL', 'REV-001', "HTTP {$r['code']} {$r['body']}");

$pub = qa_http('GET', $base . '/pages/gyms/detail.php?id=' . $gym);
str_contains($pub['body'], 'QA3 pending review body text')
    ? qa_log('FAIL', 'REV-003', 'pending visible public')
    : qa_log('PASS', 'REV-003', 'pending hidden');

$r = qa_api($uJar, 'PATCH', '/api/reviews/' . $rid, ['rating' => 5, 'comment' => 'QA3 edited review body text.']);
$st = (string) ($r['json']['data']['status'] ?? '');
($r['code'] === 200 && $st === 'pending') ? qa_log('PASS', 'REV-004', 'edit→pending') : qa_log('FAIL', 'REV-004', "HTTP {$r['code']} {$r['body']}");

$r = qa_api($aJar, 'PATCH', '/api/admin/reviews/' . $rid, ['status' => 'approved']);
$st = (string) ($r['json']['data']['status'] ?? '');
($r['code'] === 200 && $st === 'approved') ? qa_log('PASS', 'REV-006', 'approved') : qa_log('FAIL', 'REV-006', "HTTP {$r['code']} {$r['body']}");

$pub = qa_http('GET', $base . '/pages/gyms/detail.php?id=' . $gym);
str_contains($pub['body'], 'QA3 edited review body text')
    ? qa_log('PASS', 'REV-006b', 'approved visible')
    : qa_log('FAIL', 'REV-006b', 'approved not on public page');

$r = qa_api($uJar, 'POST', '/api/reviews', [
    'target_type' => 'gym', 'target_id' => $gym2, 'rating' => 2,
    'comment' => 'QA3 reject candidate review.',
]);
$rid2 = (int) ($r['json']['data']['id'] ?? 0);

$r = qa_api($aJar, 'PATCH', '/api/admin/reviews/' . $rid2, ['status' => 'rejected', 'reject_reason' => '']);
$r['code'] === 422 ? qa_log('PASS', 'REV-007', 'empty reason blocked') : qa_log('FAIL', 'REV-007', "HTTP {$r['code']} {$r['body']}");

$r = qa_api($aJar, 'PATCH', '/api/admin/reviews/' . $rid2, [
    'status' => 'rejected', 'reject_reason' => 'Không phù hợp nội dung QA.',
]);
$st = (string) ($r['json']['data']['status'] ?? '');
($r['code'] === 200 && $st === 'rejected') ? qa_log('PASS', 'REV-008', 'rejected') : qa_log('FAIL', 'REV-008', "HTTP {$r['code']} {$r['body']}");

$mine = qa_http('GET', $base . '/pages/gyms/detail.php?id=' . $gym2, ['jar' => $uJar]);
str_contains($mine['body'], 'Không phù hợp nội dung QA')
    ? qa_log('PASS', 'REV-008b', 'author sees reject reason')
    : qa_log('FAIL', 'REV-008b', 'reason not shown to author');

$r = qa_api($uJar, 'DELETE', '/api/reviews/' . $rid);
in_array($r['code'], [200, 204], true) ? qa_log('PASS', 'REV-005', "delete HTTP {$r['code']}") : qa_log('FAIL', 'REV-005', "HTTP {$r['code']} {$r['body']}");

$r = qa_api($uJar, 'POST', '/api/reviews', [
    'target_type' => 'trainer', 'target_id' => $trainer, 'rating' => 4,
    'comment' => 'QA3 trainer review for authz.',
]);
$ridt = (int) ($r['json']['data']['id'] ?? 0);
$u2 = qa_jar('u02');
qa_login($u2, 'u02@gymfinder.test');
$r = qa_api($u2, 'PATCH', '/api/reviews/' . $ridt, ['rating' => 1, 'comment' => 'hacked']);
in_array($r['code'], [403, 404], true) ? qa_log('PASS', 'REV-009', "blocked HTTP {$r['code']}") : qa_log('FAIL', 'REV-009', "HTTP {$r['code']} {$r['body']}");
qa_api($uJar, 'DELETE', '/api/reviews/' . $ridt);

// OTP on u05
$pdo->prepare('UPDATE users SET password_hash=? WHERE email=?')->execute([password_hash('password', PASSWORD_DEFAULT), 'u05@gymfinder.test']);
$oJar = qa_jar('otp');
$page = qa_http('GET', $base . '/pages/auth/forgot_password.php', ['jar' => $oJar]);
$csrf = qa_csrf_html($page['body']);
$r = qa_http('POST', $base . '/pages/auth/forgot_password.php', [
    'jar' => $oJar,
    'fields' => ['_csrf' => $csrf, 'login' => 'u05@gymfinder.test'],
]);
if (!str_contains($r['location'], 'forgot_verify_otp')) {
    qa_log('FAIL', 'AUTH-014', 'forgot redirect fail: ' . $r['location'] . ' body=' . substr(strip_tags($r['body']), 0, 120));
} else {
    $otpPage = qa_http('GET', $base . '/pages/auth/forgot_verify_otp.php', ['jar' => $oJar]);
    $demo = '';
    if (preg_match('/font-monospace[^>]*>\s*([0-9]{6})\s*</', $otpPage['body'], $m)) {
        $demo = $m[1];
    } elseif (preg_match('/Mã OTP tạm thời:.*?([0-9]{6})/s', $otpPage['body'], $m)) {
        $demo = $m[1];
    }
    if ($demo === '') {
        qa_log('FAIL', 'AUTH-014a', 'demo OTP not rendered (check demo_otp)');
    } else {
        qa_log('PASS', 'AUTH-014a', "demo OTP={$demo}");
        $csrf = qa_csrf_html($otpPage['body']);
        // wrong OTP
        $bad = qa_http('POST', $base . '/pages/auth/forgot_verify_otp.php', [
            'jar' => $oJar,
            'fields' => ['_csrf' => $csrf, 'action' => 'verify', 'otp' => ['0','0','0','0','0','0'], 'otp_full' => '000000'],
        ]);
        (str_contains($bad['body'], 'không đúng') || str_contains($bad['body'], 'lần thử'))
            ? qa_log('PASS', 'AUTH-015', 'wrong OTP rejected')
            : qa_log('FAIL', 'AUTH-015', 'no wrong-otp message');

        $otpPage = qa_http('GET', $base . '/pages/auth/forgot_verify_otp.php', ['jar' => $oJar]);
        if (preg_match('/font-monospace[^>]*>\s*([0-9]{6})\s*</', $otpPage['body'], $m)) {
            $demo = $m[1];
        }
        $csrf = qa_csrf_html($otpPage['body']);
        $digits = str_split($demo);
        $ok = qa_http('POST', $base . '/pages/auth/forgot_verify_otp.php', [
            'jar' => $oJar,
            'fields' => ['_csrf' => $csrf, 'action' => 'verify', 'otp' => $digits, 'otp_full' => $demo],
        ]);
        if (str_contains($ok['location'], 'reset_password')) {
            $rp = qa_http('GET', $base . '/pages/auth/reset_password.php', ['jar' => $oJar]);
            $csrf = qa_csrf_html($rp['body']);
            qa_http('POST', $base . '/pages/auth/reset_password.php', [
                'jar' => $oJar,
                'fields' => ['_csrf' => $csrf, 'password' => 'password', 'confirm' => 'password'],
            ]);
            $pdo->prepare('UPDATE users SET password_hash=? WHERE email=?')->execute([password_hash('password', PASSWORD_DEFAULT), 'u05@gymfinder.test']);
            qa_log('PASS', 'AUTH-014', 'OTP reset complete; u05 password restored');
        } else {
            qa_log('FAIL', 'AUTH-014', 'verify not to reset: HTTP ' . $ok['code'] . ' loc=' . $ok['location']);
        }
    }
}

$pass = count(array_filter($results, static fn ($x) => $x[0] === 'PASS'));
$fail = count(array_filter($results, static fn ($x) => $x[0] === 'FAIL'));
echo "==== SUMMARY PASS={$pass} FAIL={$fail} ====\n";
foreach ($results as [$s, $id, $msg]) {
    if ($s === 'FAIL') {
        echo "FAIL - {$id}: {$msg}\n";
    }
}

// cleanup cookie dir lightly
// keep for debug if fails
if ($fail === 0) {
    array_map('unlink', glob($cookieDir . '/*') ?: []);
    @rmdir($cookieDir);
}

exit($fail > 0 ? 1 : 0);
