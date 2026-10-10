<?php
declare(strict_types=1);

// =============================================================================
// COMMON RESOURCE — Helpers dùng chung cho tất cả API endpoints
// =============================================================================

/**
 * Đọc và parse request body (JSON / form-data / urlencoded).
 */
function gf_api_read_body(): array
{
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $ct     = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? ''));
    $raw    = file_get_contents('php://input') ?: '';

    if (!in_array($method, ['POST', 'PATCH', 'PUT', 'DELETE'], true)) {
        return [];
    }
    if (str_contains($ct, 'multipart/form-data')) {
        return $_POST;
    }
    if ($raw !== '') {
        if ($ct !== '' && !str_contains($ct, 'application/json') && !str_contains($ct, 'application/x-www-form-urlencoded')) {
            gf_api_error('BAD_REQUEST', 'Sai Content-Type.', 400);
        }
        if (str_contains($ct, 'application/json') || str_starts_with(ltrim($raw), '{') || str_starts_with(ltrim($raw), '[')) {
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                gf_api_error('BAD_REQUEST', 'JSON không hợp lệ.', 400);
            }
            return $decoded;
        }
        parse_str($raw, $parsed);
        return is_array($parsed) ? $parsed : [];
    }
    return $_POST ?: [];
}

/**
 * Validate và trả về page + limit từ query params.
 */
function gf_api_page_limit(array $q): array
{
    $details  = [];
    $pageRaw  = $q['page']  ?? '1';
    $limitRaw = $q['limit'] ?? '20';

    if (!is_numeric($pageRaw) || (int) $pageRaw != $pageRaw || (int) $pageRaw < 1) {
        $details['page'] = 'page phải là số nguyên >= 1.';
    }
    if (!is_numeric($limitRaw) || (int) $limitRaw != $limitRaw || (int) $limitRaw < 1 || (int) $limitRaw > 100) {
        $details['limit'] = 'limit phải từ 1 đến 100.';
    }
    if ($details) {
        gf_api_error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ.', 422, $details);
    }

    return ['page' => (int) $pageRaw, 'limit' => (int) $limitRaw];
}

/**
 * Validate flag 0/1 (boolean-like).
 */
function gf_api_flag01(mixed $v, string $field): ?int
{
    if ($v === null || $v === '') {
        return null;
    }
    if ($v === true  || $v === 1 || $v === '1') {
        return 1;
    }
    if ($v === false || $v === 0 || $v === '0') {
        return 0;
    }
    gf_api_error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ.', 422, [$field => 'Chỉ nhận 0 hoặc 1.']);
}

/**
 * Parse và validate flag 0/1 từ body (default 0 nếu null/rỗng).
 */
function gf_parse_flag_body(mixed $v, string $field): int
{
    if ($v === null || $v === '') {
        return 0;
    }
    $n = gf_api_flag01($v, $field);
    return $n ?? 0;
}

/**
 * Validate ID nguyên dương.
 */
function gf_api_int_id(mixed $v, string $field, bool $required = false): ?int
{
    if ($v === null || $v === '') {
        if ($required) {
            gf_api_error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ.', 422, [$field => 'Bắt buộc.']);
        }
        return null;
    }
    if (!gf_integer_like($v) || (int) $v < 1) {
        gf_api_error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ.', 422, [$field => 'Phải là ID nguyên dương.']);
    }
    return (int) $v;
}

/**
 * Parse CSV số nguyên.
 */
function gf_csv_ids(?string $raw, string $field): array
{
    if ($raw === null || $raw === '') {
        return [];
    }
    $parsed = gf_csv_ints($raw);
    if (isset($parsed['_invalid'])) {
        gf_api_error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ.', 422, [$field => 'CSV số nguyên không hợp lệ.']);
    }
    return $parsed;
}

/**
 * Kiểm tra danh sách IDs có tồn tại trong bảng không.
 */
function gf_assert_ids_exist(array $ids, string $table, string $field): void
{
    foreach ($ids as $id) {
        if (!gf_exists_id($table, (int) $id)) {
            gf_api_error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ.', 422, [$field => "ID {$id} không tồn tại."]);
        }
    }
}

/**
 * Từ chối các field không được phép trong request body.
 */
function gf_require_known_body(array $body, array $allowed, array $readonly = []): void
{
    $details = array_merge(gf_readonly_sent($body, $readonly), gf_unknown_fields($body, array_merge($allowed, $readonly)));
    if ($details) {
        gf_api_error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ.', 422, $details);
    }
}

/**
 * Kiểm tra target (gym/trainer) có đang public không.
 */
function gf_target_public(string $type, int $id): bool
{
    if ($type === 'gym') {
        return (bool) gf_get_gym($id, true);
    }
    return (bool) gf_get_trainer($id);
}

// =============================================================================
// FORMATTERS — Format dữ liệu trả về cho public API
// =============================================================================

/**
 * Format một gym cho danh sách (ít field, nhanh).
 */
function gf_gym_list_item(array $g): array
{
    return [
        'id'              => (int) $g['id'],
        'name'            => $g['name'],
        'slug'            => $g['slug'],
        'address'         => $g['address'],
        'district_id'     => (int) $g['district_id'],
        'price_min'       => (int) $g['price_min'],
        'price_max'       => (int) $g['price_max'],
        'avg_rating'      => gf_num1($g['avg_rating']),
        'review_count'    => (int) $g['review_count'],
        'cover_image_url' => $g['cover_image_url'] ?? null,
        'is_featured'     => (int) $g['is_featured'],
        'has_pt'          => !empty($g['has_pt']) ? 1 : 0,
    ];
}

/**
 * Format chi tiết gym public (đầy đủ thông tin cho trang detail).
 */
function gf_public_gym_detail(array $g, ?array $user): array
{
    $id   = (int) $g['id'];
    $cats = [];
    foreach ($g['categories'] ?? [] as $c) {
        if ((int) $c['is_active'] === 1) {
            $cats[] = ['id' => (int) $c['id'], 'name' => $c['name'], 'slug' => $c['slug']];
        }
    }
    $ams = [];
    foreach ($g['amenities'] ?? [] as $a) {
        $ams[] = ['id' => (int) $a['id'], 'name' => $a['name'], 'slug' => $a['slug'], 'icon' => $a['icon'] ?? null];
    }
    $images = [];
    foreach ($g['images'] ?? [] as $img) {
        $images[] = [
            'id'         => (int) $img['id'],
            'image_url'  => $img['image_url'],
            'caption'    => $img['caption'] ?? null,
            'sort_order' => (int) $img['sort_order'],
        ];
    }
    $data = [
        'id'              => $id,
        'name'            => $g['name'],
        'slug'            => $g['slug'],
        'description'     => $g['description'],
        'address'         => $g['address'],
        'district_id'     => (int) $g['district_id'],
        'latitude'        => $g['latitude']  !== null ? (float) $g['latitude']  : null,
        'longitude'       => $g['longitude'] !== null ? (float) $g['longitude'] : null,
        'phone'           => $g['phone'],
        'email'           => $g['email'],
        'website'         => $g['website'],
        'price_min'       => (int) $g['price_min'],
        'price_max'       => (int) $g['price_max'],
        'opening_hours'   => $g['opening_hours'],
        'cover_image_url' => $g['cover_image_url'],
        'is_featured'     => (int) $g['is_featured'],
        'avg_rating'      => gf_num1($g['avg_rating']),
        'review_count'    => (int) $g['review_count'],
        'district'        => ['id' => (int) $g['district_id'], 'name' => $g['district_name'], 'slug' => $g['district_slug']],
        'categories'      => $cats,
        'amenities'       => $ams,
        'images'          => $images,
        'trainers'        => gf_gym_active_trainers($id),
        'reviews'         => gf_preview_reviews('gym', $id),
        'is_favorited'    => $user && ($user['role'] ?? '') !== 'admin' ? gf_is_fav((int) $user['id'], 'gym', $id) : false,
    ];
    if ($user && ($user['role'] ?? '') !== 'admin') {
        $data['my_review'] = gf_my_review_row((int) $user['id'], 'gym', $id);
    }
    return $data;
}

/**
 * Format chi tiết trainer public.
 */
function gf_public_trainer_detail(array $t, ?array $user): array
{
    $id   = (int) $t['id'];
    $data = [
        'id'               => $id,
        'full_name'        => $t['full_name'],
        'slug'             => $t['slug'],
        'avatar_url'       => $t['avatar_url'],
        'specialty_id'     => (int) $t['specialty_id'],
        'years_experience' => (int) $t['years_experience'],
        'bio'              => $t['bio'],
        'phone'            => $t['phone'],
        'email'            => $t['email'],
        'gym_id'           => (int) $t['gym_id'],
        'district_id'      => (int) $t['district_id'],
        'is_featured'      => (int) $t['is_featured'],
        'avg_rating'       => gf_num1($t['avg_rating']),
        'review_count'     => (int) $t['review_count'],
        'specialty'        => ['id' => (int) $t['specialty_id'], 'name' => $t['specialty_name'], 'slug' => $t['specialty_slug']],
        'gym'              => [
            'id'              => (int) $t['gym_id'],
            'name'            => $t['gym_name'],
            'slug'            => $t['gym_slug'],
            'address'         => $t['gym_address'],
            'cover_image_url' => $t['gym_cover'],
        ],
        'district'         => ['id' => (int) $t['district_id'], 'name' => $t['district_name'], 'slug' => $t['district_slug']],
        'reviews'          => gf_preview_reviews('trainer', $id),
        'is_favorited'     => $user && ($user['role'] ?? '') !== 'admin' ? gf_is_fav((int) $user['id'], 'trainer', $id) : false,
    ];
    if ($user && ($user['role'] ?? '') !== 'admin') {
        $data['my_review'] = gf_my_review_row((int) $user['id'], 'trainer', $id);
    }
    return $data;
}

// =============================================================================
// VALIDATORS — Validate params tìm kiếm
// =============================================================================

/**
 * Validate các params tìm kiếm gym từ query string.
 */
function gf_validate_gym_search_params(array $get, bool $throw = true): array
{
    $details  = [];
    $pageRaw  = $get['page']  ?? '1';
    $limitRaw = $get['limit'] ?? '20';
    $page  = 1;
    $limit = 20;

    if (!is_numeric($pageRaw)  || (int) $pageRaw  != $pageRaw  || (int) $pageRaw  < 1)           { $details['page']  = 'page phải là số nguyên >= 1.'; } else { $page  = (int) $pageRaw;  }
    if (!is_numeric($limitRaw) || (int) $limitRaw != $limitRaw || (int) $limitRaw < 1 || (int) $limitRaw > 100) { $details['limit'] = 'limit phải từ 1 đến 100.';  } else { $limit = (int) $limitRaw; }
    $params = ['page' => $page, 'limit' => $limit];

    $sort = (string) ($get['sort'] ?? '');
    if ($sort !== '') {
        if (!in_array($sort, ['rating_desc', 'rating_asc', 'price_asc', 'price_desc'], true)) {
            $details['sort'] = 'sort phải thuộc: rating_desc, rating_asc, price_asc, price_desc.';
        } else {
            $params['sort'] = $sort;
        }
    }

    $pFrom = $get['price_from'] ?? $get['min_price'] ?? $get['price_min'] ?? null;
    $pTo   = $get['price_to']   ?? $get['max_price'] ?? $get['price_max'] ?? null;
    if ($pFrom !== null && $pFrom !== '') {
        if (!gf_integer_like($pFrom) || (int) $pFrom < 0) { $details['price_from'] = 'price_from phải là số nguyên >= 0.'; }
        else { $params['price_from'] = (int) $pFrom; }
    }
    if ($pTo !== null && $pTo !== '') {
        if (!gf_integer_like($pTo) || (int) $pTo < 0) { $details['price_to'] = 'price_to phải là số nguyên >= 0.'; }
        else { $params['price_to'] = (int) $pTo; }
    }
    if (isset($params['price_from'], $params['price_to']) && $params['price_from'] > $params['price_to']) {
        $details['price_range'] = 'price_from không được lớn hơn price_to.';
    }

    $minR = $get['min_rating'] ?? $get['rating'] ?? null;
    if ($minR !== null && $minR !== '') {
        if (!is_numeric($minR) || (float) $minR < 0 || (float) $minR > 5) { $details['min_rating'] = 'min_rating phải từ 0 đến 5.'; }
        else { $params['min_rating'] = (float) $minR; }
    }

    $dist = $get['district_id'] ?? null;
    if ($dist !== null && $dist !== '') {
        if (!gf_integer_like($dist) || (int) $dist < 1 || !gf_exists_id('districts', (int) $dist)) { $details['district_id'] = 'district_id không tồn tại.'; }
        else { $params['district_id'] = (int) $dist; }
    }

    $hasPt = $get['has_pt'] ?? null;
    if ($hasPt !== null && $hasPt !== '') {
        if ($hasPt !== '0' && $hasPt !== '1' && $hasPt !== 0 && $hasPt !== 1) { $details['has_pt'] = 'has_pt chỉ nhận 0 hoặc 1.'; }
        else { $params['has_pt'] = (int) $hasPt; }
    }

    $feat = $get['featured'] ?? null;
    if ($feat !== null && $feat !== '') {
        if ($feat !== '0' && $feat !== '1' && $feat !== 0 && $feat !== 1) { $details['featured'] = 'featured chỉ nhận 0 hoặc 1.'; }
        else { $params['featured'] = (int) $feat; }
    }

    $catIds = $get['category_ids'] ?? $get['category_id'] ?? null;
    if ($catIds !== null && trim((string) $catIds) !== '') {
        $parsedCats = gf_csv_ids((string) $catIds, 'category_ids');
        gf_assert_ids_exist($parsedCats, 'categories', 'category_ids');
        $params['category_ids'] = $parsedCats;
    }

    $amIds = $get['amenity_ids'] ?? $get['amenities'] ?? null;
    if ($amIds !== null && (is_array($amIds) ? !empty($amIds) : trim((string) $amIds) !== '')) {
        $parsedAms = is_array($amIds) ? array_map('intval', $amIds) : gf_csv_ids((string) $amIds, 'amenity_ids');
        gf_assert_ids_exist($parsedAms, 'amenities', 'amenity_ids');
        $params['amenity_ids'] = $parsedAms;
    }

    $q = trim((string) ($get['q'] ?? ''));
    if ($q !== '') {
        if (mb_strlen($q) > 100) { $details['q'] = 'q tối đa 100 ký tự.'; }
        else { $params['q'] = $q; }
    }

    if ($details) {
        if ($throw) { gf_api_error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ.', 422, $details); }
        return ['valid' => false, 'details' => $details];
    }
    return $throw ? $params : ['valid' => true, 'params' => $params];
}

/**
 * Validate các params tìm kiếm trainer từ query string.
 */
function gf_validate_trainer_search_params(array $get, bool $throw = true): array
{
    $details  = [];
    $pageRaw  = $get['page']  ?? '1';
    $limitRaw = $get['limit'] ?? '20';
    $page  = 1;
    $limit = 20;

    if (!is_numeric($pageRaw)  || (int) $pageRaw  != $pageRaw  || (int) $pageRaw  < 1)           { $details['page']  = 'page phải là số nguyên >= 1.'; } else { $page  = (int) $pageRaw;  }
    if (!is_numeric($limitRaw) || (int) $limitRaw != $limitRaw || (int) $limitRaw < 1 || (int) $limitRaw > 100) { $details['limit'] = 'limit phải từ 1 đến 100.';  } else { $limit = (int) $limitRaw; }
    $params = ['page' => $page, 'limit' => $limit];

    $sort = (string) ($get['sort'] ?? '');
    if ($sort !== '') {
        if (!in_array($sort, ['rating_desc', 'rating_asc', 'experience_desc', 'experience_asc', 'name_asc'], true)) {
            $details['sort'] = 'sort phải thuộc: rating_desc, rating_asc, experience_desc, experience_asc, name_asc.';
        } else {
            $params['sort'] = $sort;
        }
    }

    $spec = $get['specialty_id'] ?? null;
    if ($spec !== null && $spec !== '') {
        if (!gf_integer_like($spec) || (int) $spec < 1 || !gf_exists_id('specialties', (int) $spec)) { $details['specialty_id'] = 'specialty_id không tồn tại.'; }
        else { $params['specialty_id'] = (int) $spec; }
    }

    $dist = $get['district_id'] ?? null;
    if ($dist !== null && $dist !== '') {
        if (!gf_integer_like($dist) || (int) $dist < 1 || !gf_exists_id('districts', (int) $dist)) { $details['district_id'] = 'district_id không tồn tại.'; }
        else { $params['district_id'] = (int) $dist; }
    }

    $gymId = $get['gym_id'] ?? null;
    if ($gymId !== null && $gymId !== '') {
        if (!gf_integer_like($gymId) || (int) $gymId < 1 || !gf_exists_id('gyms', (int) $gymId)) { $details['gym_id'] = 'gym_id không tồn tại.'; }
        else { $params['gym_id'] = (int) $gymId; }
    }

    $exp = $get['exp'] ?? $get['experience'] ?? null;
    if ($exp !== null && (string) $exp !== '') {
        $expStr = (string) $exp;
        if (!in_array($expStr, ['0-2', '3-5', '6plus'], true)) { $details['exp'] = 'exp chỉ nhận: 0-2 | 3-5 | 6plus.'; }
        else { $params['exp'] = $expStr; }
    }

    $feat = $get['featured'] ?? null;
    if ($feat !== null && $feat !== '') {
        if ($feat !== '0' && $feat !== '1' && $feat !== 0 && $feat !== 1) { $details['featured'] = 'featured chỉ nhận 0 hoặc 1.'; }
        else { $params['featured'] = (int) $feat; }
    }

    $q = trim((string) ($get['q'] ?? ''));
    if ($q !== '') {
        if (mb_strlen($q) > 100) { $details['q'] = 'q tối đa 100 ký tự.'; }
        else { $params['q'] = $q; }
    }

    if ($details) {
        if ($throw) { gf_api_error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ.', 422, $details); }
        return ['valid' => false, 'details' => $details];
    }
    return $throw ? $params : ['valid' => true, 'params' => $params];
}
