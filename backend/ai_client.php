<?php
declare(strict_types=1);

/**
 * GYMFINDER AI chat client (Groq / OpenAI-compatible / Ollama).
 * AI chỉ được chọn gym / trainer trong catalog lấy từ DB — không bịa id.
 */

function gf_ai_config(): array
{
    return $GLOBALS['GF_CONFIG']['ai'] ?? [];
}

function gf_ai_is_configured(): bool
{
    $ai = gf_ai_config();
    if (empty($ai['enabled'])) {
        return false;
    }
    $provider = (string) ($ai['provider'] ?? 'groq');
    if ($provider === 'ollama') {
        return true;
    }
    return trim((string) ($ai['api_key'] ?? '')) !== '';
}

/**
 * Parse gợi ý thô từ câu hỏi (quận / giá / loại hình) để pre-filter catalog.
 *
 * @return array{
 *   district_ids: list<int>,
 *   district_names: list<string>,
 *   max_price: ?int,
 *   category_keywords: list<string>,
 *   specialty_keywords: list<string>,
 *   want_gym: bool,
 *   want_trainer: bool
 * }
 */
function gf_ai_parse_question_hints(string $message): array
{
    $norm = mb_strtolower($message);
    $norm = str_replace(['đ', 'Đ'], ['d', 'd'], $norm);

    $districtIds = [];
    $districtNames = [];
    $districts = gf_pdo()->query('SELECT id, name FROM districts ORDER BY LENGTH(name) DESC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($districts as $d) {
        $name = (string) $d['name'];
        $nameNorm = mb_strtolower(str_replace(['đ', 'Đ'], ['d', 'd'], $name));
        $matched = false;
        if ($nameNorm !== '' && mb_strpos($norm, $nameNorm) !== false) {
            $matched = true;
        }
        // "q7", "quan 7", "quận 7"
        if (preg_match('/qu[aậ]n\s*(\d{1,2})\b/u', $norm, $m) || preg_match('/\bq\s*(\d{1,2})\b/u', $norm, $m)) {
            $num = (int) $m[1];
            if (preg_match('/\bqu[aậ]n\s*' . $num . '\b/u', $nameNorm) || preg_match('/^qu[aậ]n\s*' . $num . '$/u', $nameNorm)) {
                $matched = true;
            }
        }
        if (str_contains($norm, 'thu duc') && str_contains($nameNorm, 'thu duc')) {
            $matched = true;
        }
        if ($matched) {
            $districtIds[] = (int) $d['id'];
            $districtNames[] = $name;
        }
    }
    $districtIds = array_values(array_unique($districtIds));
    $districtNames = array_values(array_unique($districtNames));

    $maxPrice = null;
    // dưới 500k / dưới 500 nghìn / dưới 500.000 / < 500000
    if (preg_match('/(?:duoi|dưới|<|max|toi da|tối đa)\s*(\d{1,3}(?:[.,]\d{3})*|\d+)\s*(k|nghin|nghìn|ngàn|ngan|vnd|đ|d)?/ui', $message, $pm)
        || preg_match('/(\d{1,3}(?:[.,]\d{3})*|\d+)\s*(k|nghin|nghìn)\b/ui', $message, $pm)
    ) {
        $raw = str_replace(['.', ','], '', $pm[1]);
        $n = (int) $raw;
        $unit = mb_strtolower((string) ($pm[2] ?? ''));
        if ($unit === 'k' || str_contains($unit, 'ngh') || str_contains($unit, 'ngan')) {
            $n *= 1000;
        } elseif ($n > 0 && $n < 10000) {
            // "500" trong ngữ cảnh giá gym thường là 500k
            $n *= 1000;
        }
        if ($n >= 50000 && $n <= 20000000) {
            $maxPrice = $n;
        }
    }

    $categoryKeywords = [];
    $specialtyKeywords = [];
    // [sql LIKE needle, detect needles in question, apply to category?, specialty?]
    $keywordMap = [
        ['Yoga', ['yoga'], true, true],
        ['Boxing', ['boxing'], true, true],
        ['CrossFit', ['crossfit'], true, false],
        ['nữ', ['gym nu', 'gym nữ', 'cho nu', 'cho nữ'], true, false],
        ['cao cấp', ['cao cap', 'cao cấp', 'premium'], true, false],
        ['phổ thông', ['pho thong', 'phổ thông'], true, false],
        ['Giảm cân', ['giam can', 'giảm cân', 'cardio'], false, true],
        ['Tăng cơ', ['tang co', 'tăng cơ', 'suc manh', 'sức mạnh'], false, true],
        ['Phục hồi', ['phuc hoi', 'phục hồi'], false, true],
    ];
    foreach ($keywordMap as [$sqlNeedle, $needles, $forCat, $forSpec]) {
        foreach ($needles as $needle) {
            $n = mb_strtolower(str_replace(['đ', 'Đ'], ['d', 'd'], $needle));
            if ($n !== '' && mb_strpos($norm, $n) !== false) {
                if ($forCat) {
                    $categoryKeywords[] = (string) $sqlNeedle;
                }
                if ($forSpec) {
                    $specialtyKeywords[] = (string) $sqlNeedle;
                }
                break;
            }
        }
    }
    $categoryKeywords = array_values(array_unique($categoryKeywords));
    $specialtyKeywords = array_values(array_unique($specialtyKeywords));

    $wantTrainer = (bool) preg_match('/\b(hlv|pt|trainer|huấn luyện|huan luyen|coach)\b/ui', $message);
    $wantGym = (bool) preg_match('/\b(gym|phòng tập|phong tap|câu lạc bộ|clb)\b/ui', $message);
    if (!$wantGym && !$wantTrainer) {
        $wantGym = true;
        $wantTrainer = true;
    }

    return [
        'district_ids' => $districtIds,
        'district_names' => $districtNames,
        'max_price' => $maxPrice,
        'category_keywords' => $categoryKeywords,
        'specialty_keywords' => $specialtyKeywords,
        'want_gym' => $wantGym,
        'want_trainer' => $wantTrainer,
    ];
}

/**
 * @param array<string,mixed> $filters
 * @return list<array<string,mixed>>
 */
function gf_ai_catalog_gyms(int $limit = 40, array $filters = []): array
{
    $limit = max(5, min(60, $limit));
    $sql = "SELECT g.id, g.name, g.slug, g.address, g.price_min, g.price_max, g.avg_rating, g.review_count,
                   g.cover_image_url, d.name AS district_name
            FROM gyms g
            JOIN districts d ON d.id = g.district_id
            WHERE " . gf_gym_public_where('g');
    $params = [];

    $districtIds = $filters['district_ids'] ?? [];
    if (is_array($districtIds) && $districtIds !== []) {
        $districtIds = array_values(array_filter(array_map('intval', $districtIds), static fn ($id) => $id > 0));
        if ($districtIds !== []) {
            $in = implode(',', array_fill(0, count($districtIds), '?'));
            $sql .= " AND g.district_id IN ($in)";
            foreach ($districtIds as $id) {
                $params[] = $id;
            }
        }
    }

    $maxPrice = isset($filters['max_price']) ? (int) $filters['max_price'] : 0;
    if ($maxPrice > 0) {
        // Gym có ít nhất một đầu giá trong tầm (không chỉ toàn bộ khoảng đắt hơn max)
        $sql .= ' AND g.price_min <= ?';
        $params[] = $maxPrice;
    }

    $catKeywords = $filters['category_keywords'] ?? [];
    if (is_array($catKeywords) && $catKeywords !== []) {
        $likes = [];
        foreach ($catKeywords as $kw) {
            $kw = trim((string) $kw);
            if ($kw === '') {
                continue;
            }
            $likes[] = 'c.name LIKE ?';
            $params[] = '%' . $kw . '%';
        }
        if ($likes !== []) {
            $sql .= ' AND EXISTS (
                SELECT 1 FROM gym_categories gc
                JOIN categories c ON c.id = gc.category_id
                WHERE gc.gym_id = g.id AND (' . implode(' OR ', $likes) . ')
            )';
        }
    }

    $sql .= ' ORDER BY g.is_featured DESC, g.avg_rating DESC, g.review_count DESC, g.id ASC LIMIT ' . $limit;
    $st = gf_pdo()->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $catSt = gf_pdo()->prepare(
        'SELECT c.name FROM gym_categories gc
         JOIN categories c ON c.id = gc.category_id
         WHERE gc.gym_id = ?
         ORDER BY c.name'
    );

    $out = [];
    foreach ($rows as $r) {
        $id = (int) $r['id'];
        $catSt->execute([$id]);
        $cats = $catSt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $out[] = [
            'id' => $id,
            'name' => (string) $r['name'],
            'slug' => (string) ($r['slug'] ?? ''),
            'district' => (string) ($r['district_name'] ?? ''),
            'address' => (string) ($r['address'] ?? ''),
            'price_min' => (int) ($r['price_min'] ?? 0),
            'price_max' => (int) ($r['price_max'] ?? 0),
            'avg_rating' => round((float) ($r['avg_rating'] ?? 0), 1),
            'review_count' => (int) ($r['review_count'] ?? 0),
            'categories' => array_values(array_map('strval', $cats)),
            'cover_image_url' => $r['cover_image_url'] ?? null,
        ];
    }
    return $out;
}

/**
 * @param array<string,mixed> $filters
 * @return list<array<string,mixed>>
 */
function gf_ai_catalog_trainers(int $limit = 25, array $filters = []): array
{
    $limit = max(5, min(50, $limit));
    $sql = "SELECT t.id, t.full_name, t.slug, t.avatar_url, t.years_experience,
                   t.avg_rating, t.review_count
            FROM trainers t
            WHERE t.status = 'active'
              AND EXISTS (
                  SELECT 1 FROM gym_trainers gt
                  JOIN gyms g ON g.id = gt.gym_id
                  WHERE gt.trainer_id = t.id AND g.status = 'active'
              )
              AND EXISTS (SELECT 1 FROM trainer_specialties ts WHERE ts.trainer_id = t.id)";
    $params = [];

    $districtIds = $filters['district_ids'] ?? [];
    if (is_array($districtIds) && $districtIds !== []) {
        $districtIds = array_values(array_filter(array_map('intval', $districtIds), static fn ($id) => $id > 0));
        if ($districtIds !== []) {
            $in = implode(',', array_fill(0, count($districtIds), '?'));
            $sql .= " AND EXISTS (
                SELECT 1 FROM gym_trainers gt
                JOIN gyms g ON g.id = gt.gym_id
                WHERE gt.trainer_id = t.id AND g.status = 'active' AND g.district_id IN ($in)
            )";
            foreach ($districtIds as $id) {
                $params[] = $id;
            }
        }
    }

    $specKeywords = $filters['specialty_keywords'] ?? [];
    if (is_array($specKeywords) && $specKeywords !== []) {
        $likes = [];
        foreach ($specKeywords as $kw) {
            $kw = trim((string) $kw);
            if ($kw === '') {
                continue;
            }
            $likes[] = 's.name LIKE ?';
            $params[] = '%' . $kw . '%';
        }
        if ($likes !== []) {
            $sql .= ' AND EXISTS (
                SELECT 1 FROM trainer_specialties ts
                JOIN specialties s ON s.id = ts.specialty_id
                WHERE ts.trainer_id = t.id AND (' . implode(' OR ', $likes) . ')
            )';
        }
    }

    $sql .= ' ORDER BY t.is_featured DESC, t.avg_rating DESC, t.review_count DESC, t.id ASC LIMIT ' . $limit;
    $st = gf_pdo()->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $specSt = gf_pdo()->prepare(
        'SELECT s.name FROM specialties s
         JOIN trainer_specialties ts ON ts.specialty_id = s.id
         WHERE ts.trainer_id = ?
         ORDER BY s.name'
    );
    $gymSt = gf_pdo()->prepare(
        "SELECT g.id, g.name, d.name AS district_name
         FROM gyms g
         JOIN gym_trainers gt ON gt.gym_id = g.id
         JOIN districts d ON d.id = g.district_id
         WHERE gt.trainer_id = ? AND g.status = 'active'
         ORDER BY g.name
         LIMIT 3"
    );

    $out = [];
    foreach ($rows as $r) {
        $id = (int) $r['id'];
        $specSt->execute([$id]);
        $specs = $specSt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $gymSt->execute([$id]);
        $gymRows = $gymSt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $districts = [];
        $gymNames = [];
        foreach ($gymRows as $gr) {
            $gymNames[] = (string) $gr['name'];
            $dn = (string) ($gr['district_name'] ?? '');
            if ($dn !== '' && !in_array($dn, $districts, true)) {
                $districts[] = $dn;
            }
        }
        $out[] = [
            'id' => $id,
            'full_name' => (string) $r['full_name'],
            'slug' => (string) ($r['slug'] ?? ''),
            'years_experience' => (int) ($r['years_experience'] ?? 0),
            'avg_rating' => round((float) ($r['avg_rating'] ?? 0), 1),
            'review_count' => (int) ($r['review_count'] ?? 0),
            'specialties' => array_values(array_map('strval', $specs)),
            'districts' => $districts,
            'gyms' => $gymNames,
            'avatar_url' => $r['avatar_url'] ?? null,
        ];
    }
    return $out;
}

/** @return array{ok:bool, error?:string} */
function gf_ai_rate_limit_check(int $maxPerMinute = 6): array
{
    $bucket = $_SESSION['ai_chat_rl'] ?? ['t' => time(), 'n' => 0];
    if (!is_array($bucket)) {
        $bucket = ['t' => time(), 'n' => 0];
    }
    if ((time() - (int) ($bucket['t'] ?? 0)) > 60) {
        $bucket = ['t' => time(), 'n' => 0];
    }
    if ((int) ($bucket['n'] ?? 0) >= $maxPerMinute) {
        return [
            'ok' => false,
            'error' => 'Bạn hỏi hơi nhanh (giới hạn ' . $maxPerMinute . ' câu/phút). Đợi khoảng 1 phút hoặc dùng trang Tìm kiếm.',
        ];
    }
    $bucket['n'] = (int) ($bucket['n'] ?? 0) + 1;
    $_SESSION['ai_chat_rl'] = $bucket;
    return ['ok' => true];
}

function gf_ai_public_error(string $raw): string
{
    $rawLower = mb_strtolower($raw);
    if (str_contains($rawLower, 'rate limit')) {
        return 'AI đang quá tải / hết hạn mức tạm thời. Đợi khoảng 30–60 giây rồi thử lại, hoặc dùng trang Tìm kiếm gym/HLV.';
    }
    if (str_contains($rawLower, 'timeout') || str_contains($rawLower, 'timed out')) {
        return 'AI phản hồi chậm. Thử lại sau vài giây hoặc dùng trang Tìm kiếm.';
    }
    if (str_contains($rawLower, 'chưa cấu hình') || str_contains($rawLower, 'api key')) {
        return $raw;
    }
    return 'Không gọi được trợ lý AI lúc này. Thử lại hoặc dùng trang Tìm kiếm gym/HLV.';
}

/**
 * @param list<array{role:string,content:string}> $messages
 * @return array{ok:bool, content?:string, error?:string}
 */
function gf_ai_chat(array $messages, int $maxTokens = 700): array
{
    if (!gf_ai_is_configured()) {
        return ['ok' => false, 'error' => 'AI chưa được cấu hình. Hãy tạo backend/ai.local.php từ example và thêm API key.'];
    }

    $ai = gf_ai_config();
    $provider = (string) ($ai['provider'] ?? 'groq');
    $model = trim((string) ($ai['model'] ?? ''));
    if ($model === '') {
        return ['ok' => false, 'error' => 'Thiếu model AI trong cấu hình.'];
    }

    $baseUrl = rtrim(trim((string) ($ai['base_url'] ?? '')), '/');
    if ($baseUrl === '') {
        $baseUrl = match ($provider) {
            'ollama' => 'http://127.0.0.1:11434/v1',
            'gemini' => '', // handled separately below
            default => 'https://api.groq.com/openai/v1',
        };
    }

    if ($provider === 'gemini') {
        return gf_ai_chat_gemini($messages, $model, (string) ($ai['api_key'] ?? ''), $maxTokens);
    }

    $url = $baseUrl . '/chat/completions';
    $payload = [
        'model' => $model,
        'messages' => $messages,
        'temperature' => 0.3,
        'max_tokens' => $maxTokens,
    ];

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    $apiKey = trim((string) ($ai['api_key'] ?? ''));
    if ($apiKey !== '' && $provider !== 'ollama') {
        $headers[] = 'Authorization: Bearer ' . $apiKey;
    }

    $raw = gf_ai_http_post_json($url, $payload, $headers, 45);
    if (!$raw['ok']) {
        return ['ok' => false, 'error' => $raw['error'] ?? 'Không gọi được AI.'];
    }

    $data = $raw['data'] ?? [];
    $message = $data['choices'][0]['message'] ?? [];
    $content = trim((string) ($message['content'] ?? ''));
    if ($content === '') {
        // Một số model reasoning để trống content; thử lấy từ reasoning nếu có JSON.
        $content = trim((string) ($message['reasoning'] ?? ''));
    }
    if ($content === '') {
        return ['ok' => false, 'error' => 'AI không trả về nội dung hợp lệ. Thử lại hoặc đổi model.'];
    }
    return ['ok' => true, 'content' => $content];
}

/**
 * @param list<array{role:string,content:string}> $messages
 * @return array{ok:bool, content?:string, error?:string}
 */
function gf_ai_chat_gemini(array $messages, string $model, string $apiKey, int $maxTokens): array
{
    if ($apiKey === '') {
        return ['ok' => false, 'error' => 'Thiếu Gemini API key.'];
    }
    if ($model === '') {
        $model = 'gemini-2.0-flash';
    }
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
        . rawurlencode($model) . ':generateContent?key=' . rawurlencode($apiKey);

    $contents = [];
    $system = '';
    foreach ($messages as $m) {
        $role = $m['role'] ?? 'user';
        $text = (string) ($m['content'] ?? '');
        if ($role === 'system') {
            $system .= ($system === '' ? '' : "\n") . $text;
            continue;
        }
        $contents[] = [
            'role' => $role === 'assistant' ? 'model' : 'user',
            'parts' => [['text' => $text]],
        ];
    }
    $payload = [
        'contents' => $contents,
        'generationConfig' => [
            'temperature' => 0.3,
            'maxOutputTokens' => $maxTokens,
        ],
    ];
    if ($system !== '') {
        $payload['systemInstruction'] = ['parts' => [['text' => $system]]];
    }

    $raw = gf_ai_http_post_json($url, $payload, ['Content-Type: application/json', 'Accept: application/json'], 45);
    if (!$raw['ok']) {
        return ['ok' => false, 'error' => $raw['error'] ?? 'Không gọi được Gemini.'];
    }
    $data = $raw['data'] ?? [];
    $content = trim((string) ($data['candidates'][0]['content']['parts'][0]['text'] ?? ''));
    if ($content === '') {
        return ['ok' => false, 'error' => 'Gemini không trả về nội dung hợp lệ.'];
    }
    return ['ok' => true, 'content' => $content];
}

/**
 * @param array<string,mixed> $payload
 * @param list<string> $headers
 * @return array{ok:bool, data?:array, error?:string, http?:int}
 */
function gf_ai_http_post_json(string $url, array $payload, array $headers, int $timeoutSec = 45): array
{
    $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($body === false) {
        return ['ok' => false, 'error' => 'Không encode được JSON gửi AI.'];
    }

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeoutSec,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $resp = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($errno !== 0 || $resp === false) {
            return ['ok' => false, 'error' => 'Lỗi mạng khi gọi AI: ' . ($err !== '' ? $err : 'unknown'), 'http' => $http];
        }
    } else {
        $headerLine = implode("\r\n", $headers);
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => $headerLine,
                'content' => $body,
                'timeout' => $timeoutSec,
                'ignore_errors' => true,
            ],
        ]);
        $resp = @file_get_contents($url, false, $ctx);
        $http = 0;
        $statusHeaders = function_exists('http_get_last_response_headers')
            ? (http_get_last_response_headers() ?: [])
            : [];
        if ($statusHeaders !== [] && preg_match('/\s(\d{3})\s/', (string) $statusHeaders[0], $m)) {
            $http = (int) $m[1];
        }
        if ($resp === false) {
            return ['ok' => false, 'error' => 'Lỗi mạng khi gọi AI (stream).', 'http' => $http];
        }
    }

    $decoded = json_decode((string) $resp, true);
    if (!is_array($decoded)) {
        return ['ok' => false, 'error' => 'AI trả về dữ liệu không phải JSON.', 'http' => $http ?? 0];
    }
    if (($http ?? 0) >= 400) {
        $msg = (string) ($decoded['error']['message'] ?? $decoded['error'] ?? 'HTTP ' . $http);
        return ['ok' => false, 'error' => 'AI lỗi: ' . $msg, 'http' => $http, 'data' => $decoded];
    }
    return ['ok' => true, 'data' => $decoded, 'http' => $http ?? 200];
}

function gf_ai_extract_json_object(string $text): ?array
{
    $text = trim($text);
    if ($text === '') {
        return null;
    }
    $decoded = json_decode($text, true);
    if (is_array($decoded)) {
        return $decoded;
    }
    if (preg_match('/\{.*\}/s', $text, $m)) {
        $decoded = json_decode($m[0], true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }
    return null;
}

/**
 * Gợi ý gym và/hoặc HLV từ câu hỏi user. Luôn map lại DB.
 *
 * @return array{ok:bool, reply?:string, gyms?:list<array>, trainers?:list<array>, error?:string}
 */
function gf_ai_recommend_gyms(string $userMessage): array
{
    $userMessage = trim($userMessage);
    if ($userMessage === '') {
        return ['ok' => false, 'error' => 'Vui lòng nhập câu hỏi.'];
    }
    if (mb_strlen($userMessage) > 500) {
        return ['ok' => false, 'error' => 'Câu hỏi tối đa 500 ký tự.'];
    }

    $rl = gf_ai_rate_limit_check(6);
    if (empty($rl['ok'])) {
        return ['ok' => false, 'error' => $rl['error'] ?? 'Bạn hỏi hơi nhanh. Vui lòng thử lại sau.'];
    }

    if (!gf_ai_is_configured()) {
        return ['ok' => false, 'error' => 'AI chưa cấu hình. Tạo backend/ai.local.php (copy từ ai.local.php.example) và thêm API key.'];
    }

    $hints = gf_ai_parse_question_hints($userMessage);
    $gymFilters = [
        'district_ids' => $hints['district_ids'],
        'max_price' => $hints['max_price'],
        'category_keywords' => $hints['category_keywords'],
    ];
    $trainerFilters = [
        'district_ids' => $hints['district_ids'],
        'specialty_keywords' => $hints['specialty_keywords'],
    ];

    $gymCatalog = [];
    $trainerCatalog = [];
    $usedFallbackCatalog = false;

    if (!empty($hints['want_gym'])) {
        $gymCatalog = gf_ai_catalog_gyms(20, $gymFilters);
        // Pre-filter quá chặt → nới (bỏ category, giữ quận/giá) rồi mới unfiltered
        if ($gymCatalog === [] && ($hints['district_ids'] !== [] || $hints['max_price'] || $hints['category_keywords'] !== [])) {
            $gymCatalog = gf_ai_catalog_gyms(20, [
                'district_ids' => $hints['district_ids'],
                'max_price' => $hints['max_price'],
            ]);
        }
        if ($gymCatalog === []) {
            $gymCatalog = gf_ai_catalog_gyms(15, []);
            $usedFallbackCatalog = true;
        }
    }

    if (!empty($hints['want_trainer'])) {
        $trainerCatalog = gf_ai_catalog_trainers(20, $trainerFilters);
        if ($trainerCatalog === [] && ($hints['district_ids'] !== [] || $hints['specialty_keywords'] !== [])) {
            $trainerCatalog = gf_ai_catalog_trainers(20, [
                'district_ids' => $hints['district_ids'],
            ]);
        }
        if ($trainerCatalog === []) {
            $trainerCatalog = gf_ai_catalog_trainers(15, []);
            $usedFallbackCatalog = true;
        }
    }

    if ($gymCatalog === [] && $trainerCatalog === []) {
        return ['ok' => false, 'error' => 'Chưa có phòng tập / HLV active trong hệ thống để gợi ý.'];
    }

    $gymCompact = array_map(static function (array $g): array {
        return [
            'id' => $g['id'],
            'name' => $g['name'],
            'district' => $g['district'],
            'price_min' => $g['price_min'],
            'price_max' => $g['price_max'],
            'avg_rating' => $g['avg_rating'],
            'categories' => array_slice($g['categories'], 0, 3),
        ];
    }, $gymCatalog);

    $trainerCompact = array_map(static function (array $t): array {
        return [
            'id' => $t['id'],
            'full_name' => $t['full_name'],
            'years_experience' => $t['years_experience'],
            'avg_rating' => $t['avg_rating'],
            'specialties' => array_slice($t['specialties'], 0, 4),
            'districts' => array_slice($t['districts'], 0, 3),
            'gyms' => array_slice($t['gyms'], 0, 2),
        ];
    }, $trainerCatalog);

    $system = <<<'PROMPT'
Bạn là trợ lý tìm phòng tập (gym) và huấn luyện viên cá nhân (PT/trainer) của website GYMFINDER (chỉ TP. Hồ Chí Minh).

## Nhiệm vụ
- Đọc câu hỏi tiếng Việt của user.
- Quyết định cần gợi ý gym, trainer, hoặc cả hai:
  · Hỏi phòng tập / giá / tiện ích / loại hình gym → ưu tiên gym_ids.
  · Hỏi HLV / PT / chuyên môn (Yoga coach, giảm cân 1-1…) → ưu tiên trainer_ids.
  · Hỏi chung (“tập ở đâu”, “gợi ý”) → có thể trả cả hai nếu hợp lý.
- Viết "reply" ngắn, rõ (2–4 câu), giải thích vì sao chọn các mục đó.

## Ràng buộc bắt buộc (harness)
1. CHỈ dùng id trong catalogs.gyms / catalogs.trainers. Cấm bịa id, tên, giá, rating, chuyên môn.
2. gym_ids chỉ lấy từ catalogs.gyms; trainer_ids chỉ lấy từ catalogs.trainers. Không trộn id giữa hai loại.
3. Mỗi mảng tối đa 3 id, không trùng. Có thể một mảng rỗng [].
4. Không khớp: để mảng tương ứng = [] và giải thích; gợi ý user nêu quận / giá / loại hình / chuyên môn.
5. Ngoài phạm vi (không phải TP.HCM, đặt lịch, thanh toán, bản đồ, nhắn tin trong app): từ chối lịch sự, cả hai mảng = [].
6. Ưu tiên khớp: khu vực (district) → giá (gym) / chuyên môn+kinh nghiệm (trainer) → rating cao.
7. Giá gym là VND/tháng. "dưới 500k" → chọn gym có khoảng giá hợp lý quanh ngưỡng đó.
8. Không nhắc API/JSON/schema trong reply trừ khi user hỏi.

## Output format (bắt buộc)
- ĐÚNG 1 JSON object, không markdown, không code fence, không text ngoài JSON.
- Schema:
{"reply":"string tiếng Việt","gym_ids":[number],"trainer_ids":[number]}
- reply không để trống.

## Ví dụ đúng
{"reply":"Gợi ý vài phòng tập Quận 7 mức giá vừa phải.","gym_ids":[4],"trainer_ids":[]}
{"reply":"Một số HLV chuyên Yoga / rating cao trong hệ thống.","gym_ids":[],"trainer_ids":[2,5]}
{"reply":"GYMFINDER chỉ có dữ liệu TP.HCM, chưa hỗ trợ Đà Nẵng.","gym_ids":[],"trainer_ids":[]}
PROMPT;

    $userPayload = json_encode([
        'prefilter' => [
            'districts' => $hints['district_names'],
            'max_price' => $hints['max_price'],
            'category_keywords' => $hints['category_keywords'],
            'specialty_keywords' => $hints['specialty_keywords'],
            'want_gym' => $hints['want_gym'],
            'want_trainer' => $hints['want_trainer'],
            'catalog_fallback' => $usedFallbackCatalog,
        ],
        'catalogs' => [
            'gyms' => $gymCompact,
            'trainers' => $trainerCompact,
        ],
        'question' => $userMessage,
        'instructions' => 'Catalog đã được pre-filter từ DB khi có thể. Chọn gym_ids/trainer_ids từ catalogs rồi trả JSON theo system prompt.',
    ], JSON_UNESCAPED_UNICODE);

    $chat = gf_ai_chat([
        ['role' => 'system', 'content' => $system],
        ['role' => 'user', 'content' => (string) $userPayload],
    ], 1400);

    if (!$chat['ok']) {
        $rawErr = (string) ($chat['error'] ?? 'Không gọi được AI.');
        error_log('GYMFINDER AI recommend failed: ' . mb_substr($rawErr, 0, 300));
        return ['ok' => false, 'error' => gf_ai_public_error($rawErr)];
    }

    $parsed = gf_ai_extract_json_object((string) ($chat['content'] ?? ''));
    if ($parsed === null) {
        return [
            'ok' => true,
            'reply' => trim((string) $chat['content']),
            'gyms' => [],
            'trainers' => [],
        ];
    }

    $reply = trim((string) ($parsed['reply'] ?? ''));
    if ($reply === '') {
        $reply = 'Đây là một số gợi ý phù hợp trên GYMFINDER.';
    }

    $allowedGyms = [];
    foreach ($gymCatalog as $g) {
        $allowedGyms[(int) $g['id']] = $g;
    }
    $allowedTrainers = [];
    foreach ($trainerCatalog as $t) {
        $allowedTrainers[(int) $t['id']] = $t;
    }

    $gyms = gf_ai_pick_ids_from_catalog($parsed['gym_ids'] ?? [], $allowedGyms, 3);
    $trainers = gf_ai_pick_ids_from_catalog($parsed['trainer_ids'] ?? [], $allowedTrainers, 3);

    return [
        'ok' => true,
        'reply' => $reply,
        'gyms' => $gyms,
        'trainers' => $trainers,
    ];
}

/**
 * @param mixed $ids
 * @param array<int, array<string,mixed>> $allowed
 * @return list<array<string,mixed>>
 */
function gf_ai_pick_ids_from_catalog(mixed $ids, array $allowed, int $max): array
{
    if (!is_array($ids)) {
        return [];
    }
    $picked = [];
    foreach ($ids as $id) {
        if (!is_numeric($id)) {
            continue;
        }
        $id = (int) $id;
        if ($id > 0 && isset($allowed[$id]) && !isset($picked[$id])) {
            $picked[$id] = $allowed[$id];
        }
        if (count($picked) >= $max) {
            break;
        }
    }
    return array_values($picked);
}

/** @return list<array<string,mixed>> */
function gf_ai_chat_history_get(): array
{
    $history = $_SESSION['ai_chat_history'] ?? [];
    return is_array($history) ? array_values($history) : [];
}

function gf_ai_chat_history_clear(): void
{
    unset($_SESSION['ai_chat_history']);
}

/**
 * Lưu 1 lượt user + bot vào session (giữ tối đa $maxTurns lượt).
 *
 * @param list<array<string,mixed>> $gyms
 * @param list<array<string,mixed>> $trainers
 */
function gf_ai_chat_history_append(string $userText, string $botText, array $gyms = [], array $trainers = [], bool $isError = false, int $maxTurns = 12): void
{
    $history = gf_ai_chat_history_get();

    $slimGyms = [];
    foreach ($gyms as $g) {
        $slimGyms[] = [
            'id' => (int) ($g['id'] ?? 0),
            'name' => (string) ($g['name'] ?? ''),
            'district' => (string) ($g['district'] ?? ''),
            'price_min' => (int) ($g['price_min'] ?? 0),
            'price_max' => (int) ($g['price_max'] ?? 0),
            'avg_rating' => (float) ($g['avg_rating'] ?? 0),
            'categories' => array_slice(is_array($g['categories'] ?? null) ? $g['categories'] : [], 0, 3),
            'cover_image_url' => $g['cover_image_url'] ?? null,
        ];
    }

    $slimTrainers = [];
    foreach ($trainers as $t) {
        $slimTrainers[] = [
            'id' => (int) ($t['id'] ?? 0),
            'full_name' => (string) ($t['full_name'] ?? ''),
            'years_experience' => (int) ($t['years_experience'] ?? 0),
            'avg_rating' => (float) ($t['avg_rating'] ?? 0),
            'specialties' => array_slice(is_array($t['specialties'] ?? null) ? $t['specialties'] : [], 0, 3),
            'districts' => array_slice(is_array($t['districts'] ?? null) ? $t['districts'] : [], 0, 2),
            'avatar_url' => $t['avatar_url'] ?? null,
        ];
    }

    $history[] = [
        'user' => mb_substr(trim($userText), 0, 500),
        'bot' => mb_substr(trim($botText), 0, 2000),
        'is_error' => $isError,
        'gyms' => $slimGyms,
        'trainers' => $slimTrainers,
        'at' => time(),
    ];

    if (count($history) > $maxTurns) {
        $history = array_slice($history, -$maxTurns);
    }
    $_SESSION['ai_chat_history'] = $history;
}
