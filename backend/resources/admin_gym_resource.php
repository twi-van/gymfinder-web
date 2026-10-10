<?php
declare(strict_types=1);

// Admin resource: gyms.

/**
 * Lấy payload gym đầy đủ cho admin (bao gồm category_ids, amenity_ids, images).
 */
function gf_admin_gym_payload(int $id): ?array
{
    $g = gf_get_gym_by_key((string) $id, false);
    if (!$g) {
        return null;
    }
    $st = gf_pdo()->prepare('SELECT category_id FROM gym_categories WHERE gym_id=?');
    $st->execute([$id]);
    $catIds = array_map('intval', $st->fetchAll(\PDO::FETCH_COLUMN));

    $st = gf_pdo()->prepare('SELECT amenity_id FROM gym_amenities WHERE gym_id=?');
    $st->execute([$id]);
    $amIds = array_map('intval', $st->fetchAll(\PDO::FETCH_COLUMN));

    $images = [];
    foreach ($g['images'] ?? [] as $img) {
        $images[] = [
            'id'         => (int) $img['id'],
            'image_url'  => $img['image_url'],
            'caption'    => $img['caption'] ?? null,
            'sort_order' => (int) $img['sort_order'],
        ];
    }
    return [
        'id'              => (int) $g['id'],
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
        'status'          => $g['status'],
        'category_ids'    => $catIds,
        'amenity_ids'     => $amIds,
        'images'          => $images,
        'avg_rating'      => gf_num1($g['avg_rating']),
        'review_count'    => (int) $g['review_count'],
        'created_at'      => gf_iso($g['created_at']),
        'updated_at'      => gf_iso($g['updated_at']),
    ];
}


/**
 * Validate các field của gym trước khi insert/update.
 */
function gf_validate_gym_fields(array $d, array $merged): array
{
    $details = [];
    $name    = trim((string) ($merged['name']    ?? ''));
    $address = trim((string) ($merged['address'] ?? ''));

    if ($name === '' || mb_strlen($name) > 150)       { $details['name']    = 'name 1–150 ký tự.'; }
    if ($address === '' || mb_strlen($address) > 255)  { $details['address'] = 'address 1–255 ký tự.'; }

    if (isset($merged['description']) && $merged['description'] !== null && mb_strlen((string) $merged['description']) > 5000) {
        $details['description'] = 'description tối đa 5000 ký tự.';
    }

    $districtValue = $merged['district_id'] ?? null;
    $districtId = (int) $districtValue;
    if (!gf_integer_like($districtValue) || $districtId < 1 || !gf_exists_id('districts', $districtId)) {
        $details['district_id'] = 'district_id không tồn tại.';
    }

    $lat    = $merged['latitude']  ?? null;
    $lng    = $merged['longitude'] ?? null;
    $latSet = !($lat === null || $lat === '');
    $lngSet = !($lng === null || $lng === '');
    if ($latSet !== $lngSet) {
        $details['latitude'] = 'latitude và longitude phải cùng có hoặc cùng null.';
    } elseif ($latSet) {
        if (!is_numeric($lat) || (float) $lat < -90  || (float) $lat > 90)  { $details['latitude']  = 'latitude -90..90.'; }
        if (!is_numeric($lng) || (float) $lng < -180 || (float) $lng > 180) { $details['longitude'] = 'longitude -180..180.'; }
    }

    $phone = $merged['phone'] ?? null;
    if ($phone !== null && $phone !== '' && !gf_phone_ok((string) $phone)) {
        $details['phone'] = 'Số điện thoại không hợp lệ.';
    }
    $email = $merged['email'] ?? null;
    if ($email !== null && $email !== '' && !filter_var((string) $email, FILTER_VALIDATE_EMAIL)) {
        $details['email'] = 'Email không hợp lệ.';
    }
    $website = $merged['website'] ?? null;
    if ($website !== null && $website !== '') {
        if (strlen((string) $website) > 255 || !preg_match('#^https?://#i', (string) $website)) {
            $details['website'] = 'website phải bắt đầu http(s)://.';
        }
    }
    if (isset($merged['opening_hours']) && $merged['opening_hours'] !== null && mb_strlen((string) $merged['opening_hours']) > 120) {
        $details['opening_hours'] = 'opening_hours tối đa 120 ký tự.';
    }

    $pmin = $merged['price_min'] ?? null;
    $pmax = $merged['price_max'] ?? null;
    if (!gf_integer_like($pmin) || (int) $pmin < 0) { $details['price_min'] = 'price_min phải là số nguyên >= 0.'; }
    if (!gf_integer_like($pmax) || (int) $pmax < 0) { $details['price_max'] = 'price_max phải là số nguyên >= 0.'; }
    elseif (gf_integer_like($pmin) && (int) $pmax < (int) $pmin) { $details['price_max'] = 'price_max phải >= price_min.'; }

    if (isset($merged['status']) && !in_array($merged['status'], ['active', 'hidden'], true)) {
        $details['status'] = 'status chỉ active|hidden.';
    }
    if (array_key_exists('cover_image_url', $merged) && !gf_uploads_url_ok($merged['cover_image_url'] === null ? null : (string) $merged['cover_image_url'])) {
        $details['cover_image_url'] = 'Chỉ nhận đường dẫn /uploads/...';
    }

    return $details;
}


/**
 * Admin tạo mới hoặc cập nhật gym (dùng chung POST/PATCH).
 */
function gf_admin_save_gym(array $d, ?int $id): array
{
    $allowed = ['name','description','address','district_id','latitude','longitude','phone','email','website','price_min','price_max','opening_hours','cover_image_url','is_featured','status','category_ids','amenity_ids','images'];
    gf_require_known_body($d, $allowed, ['id','slug','avg_rating','review_count','created_at','updated_at']);

    $pdo = gf_pdo();
    $pdo->beginTransaction();
    try {
        $existing = $id ? gf_lock_row('gyms', $id) : null;
        if ($id && !$existing) {
            $pdo->rollBack();
            return ['ok' => false, 'http' => 404, 'code' => 'NOT_FOUND', 'error' => 'Không tìm thấy phòng tập.'];
        }
        if ($id && $d === []) {
            $pdo->rollBack();
            return ['ok' => false, 'http' => 422, 'code' => 'VALIDATION_ERROR', 'error' => 'PATCH body rỗng.', 'details' => ['body' => 'Cần ít nhất một field.']];
        }

        $merged = $existing ?: [];
        foreach ($d as $k => $v) { $merged[$k] = $v; }

        if (!$id) {
            foreach (['name', 'address', 'district_id', 'price_min', 'price_max'] as $req) {
                if (!array_key_exists($req, $d)) {
                    $pdo->rollBack();
                    return ['ok' => false, 'http' => 422, 'code' => 'VALIDATION_ERROR', 'error' => 'Thiếu field bắt buộc.', 'details' => [$req => 'Bắt buộc.']];
                }
            }
            $merged['is_featured'] = array_key_exists('is_featured', $d) ? gf_parse_flag_body($d['is_featured'], 'is_featured') : 0;
            $merged['status']      = $d['status'] ?? 'active';
        } elseif (array_key_exists('is_featured', $d)) {
            $merged['is_featured'] = gf_parse_flag_body($d['is_featured'], 'is_featured');
        }

        $details = gf_validate_gym_fields($d, $merged);
        if ($details) {
            $pdo->rollBack();
            return ['ok' => false, 'http' => 422, 'code' => 'VALIDATION_ERROR', 'error' => 'Dữ liệu không hợp lệ.', 'details' => $details];
        }

        $cover         = (string) ($merged['cover_image_url'] ?? '');
        $filesToDelete = [];

        if ($id) {
            // UPDATE gym
            $oldDistrict = (int) $existing['district_id'];
            $newDistrict = (int) $merged['district_id'];
            $sets        = [];
            $params      = [];
            $cols        = ['name','description','address','district_id','latitude','longitude','phone','email','website','price_min','price_max','opening_hours','cover_image_url','is_featured','status'];
            foreach ($cols as $col) {
                if (array_key_exists($col, $d) || ($col === 'is_featured' && array_key_exists('is_featured', $d))) {
                    $sets[] = "$col=?";
                    $val    = $merged[$col] ?? null;
                    if (in_array($col, ['phone','email','website','description','opening_hours','cover_image_url'], true) && $val === '') { $val = null; }
                    if (($col === 'latitude' || $col === 'longitude') && ($val === null || $val === '')) { $val = null; }
                    $params[] = $val;
                }
            }
            if ($sets) {
                $params[] = $id;
                $pdo->prepare('UPDATE gyms SET ' . implode(',', $sets) . ' WHERE id=?')->execute($params);
            }
            if (array_key_exists('cover_image_url', $d) && $existing['cover_image_url'] && $existing['cover_image_url'] !== ($merged['cover_image_url'] ?? null)) {
                $filesToDelete[] = $existing['cover_image_url'];
            }
            $filesToDelete = array_merge($filesToDelete, gf_sync_gym_relations($id, $d, $cover));
            if ($newDistrict !== $oldDistrict && array_key_exists('district_id', $d)) {
                $pdo->prepare('UPDATE trainers SET district_id=? WHERE gym_id=?')->execute([$newDistrict, $id]);
            }
            $pdo->commit();
            gf_delete_unreferenced_files($filesToDelete);
            return ['ok' => true, 'id' => $id];
        }

        // INSERT gym
        $slug = gf_unique_slug('gyms', gf_slugify((string) $merged['name'], 'gym'), 180);
        $st   = $pdo->prepare('INSERT INTO gyms (name, slug, description, address, district_id, latitude, longitude, phone, email, website, price_min, price_max, opening_hours, cover_image_url, is_featured, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $st->execute([
            trim((string) $merged['name']),
            $slug,
            $merged['description'] ?? null,
            trim((string) $merged['address']),
            (int) $merged['district_id'],
            ($merged['latitude']        ?? '') === '' ? null : $merged['latitude'],
            ($merged['longitude']       ?? '') === '' ? null : $merged['longitude'],
            ($merged['phone']           ?? '') === '' ? null : $merged['phone'],
            ($merged['email']           ?? '') === '' ? null : $merged['email'],
            ($merged['website']         ?? '') === '' ? null : $merged['website'],
            (int) $merged['price_min'],
            (int) $merged['price_max'],
            $merged['opening_hours'] ?? null,
            ($merged['cover_image_url'] ?? '') === '' ? null : $merged['cover_image_url'],
            (int) $merged['is_featured'],
            $merged['status'] ?? 'active',
        ]);
        $newId = (int) $pdo->lastInsertId();
        if (!isset($d['category_ids'])) { $d['category_ids'] = []; }
        if (!isset($d['amenity_ids']))  { $d['amenity_ids']  = []; }
        if (!isset($d['images']))       { $d['images']       = []; }
        gf_sync_gym_relations($newId, $d, $cover);
        $pdo->commit();
        return ['ok' => true, 'id' => $newId];
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        return ['ok' => false, 'http' => 500, 'code' => 'SERVER_ERROR', 'error' => 'Lỗi máy chủ.'];
    }
}


/**
 * Admin xem danh sách gym, có filter & phân trang.
 */
function gf_admin_gyms_paged(array $q): array
{
    $pageLimit = gf_api_page_limit($q);
    $page  = $pageLimit['page'];
    $limit = $pageLimit['limit'];
    $where  = ['1=1'];
    $params = [];

    $kw = trim((string) ($q['q'] ?? ''));
    if ($kw !== '') {
        $where[] = "(g.name LIKE ? ESCAPE '!' OR g.address LIKE ? ESCAPE '!')";
        $like    = gf_like_contains($kw);
        $params[] = $like;
        $params[] = $like;
    }
    $status = (string) ($q['status'] ?? '');
    if ($status !== '') {
        if (!in_array($status, ['active', 'hidden'], true)) { gf_api_error('VALIDATION_ERROR', 'status chỉ active|hidden.', 422, ['status' => 'active|hidden']); }
        $where[]  = 'g.status = ?';
        $params[] = $status;
    }
    $dist = $q['district_id'] ?? null;
    if ($dist !== null && $dist !== '') {
        if (!gf_integer_like($dist) || (int) $dist < 1 || !gf_exists_id('districts', (int) $dist)) { gf_api_error('VALIDATION_ERROR', 'district_id không tồn tại.', 422, ['district_id' => 'Không tồn tại.']); }
        $where[]  = 'g.district_id = ?';
        $params[] = (int) $dist;
    }
    $feat = $q['is_featured'] ?? null;
    if ($feat !== null && $feat !== '') {
        if ($feat !== '0' && $feat !== '1' && $feat !== 0 && $feat !== 1) { gf_api_error('VALIDATION_ERROR', 'is_featured chỉ nhận 0 hoặc 1.', 422, ['is_featured' => '0|1']); }
        $where[]  = 'g.is_featured = ?';
        $params[] = (int) $feat;
    }
    $sort  = (string) ($q['sort'] ?? 'newest');
    $order = match ($sort) {
        'oldest'    => 'g.id ASC',
        'name_asc'  => 'g.name ASC, g.id DESC',
        'name_desc' => 'g.name DESC, g.id DESC',
        'newest'    => 'g.id DESC',
        default     => gf_api_error('VALIDATION_ERROR', 'sort không hợp lệ.', 422, ['sort' => 'newest|oldest|name_asc|name_desc']),
    };

    $wSql = implode(' AND ', $where);
    $cSt  = gf_pdo()->prepare("SELECT COUNT(*) FROM gyms g WHERE {$wSql}");
    $cSt->execute($params);
    $total = (int) $cSt->fetchColumn();

    $offset = ($page - 1) * $limit;
    $st     = gf_pdo()->prepare("SELECT g.*, d.name AS district_name FROM gyms g JOIN districts d ON d.id=g.district_id WHERE {$wSql} ORDER BY {$order} LIMIT {$limit} OFFSET {$offset}");
    $st->execute($params);
    $items = [];
    foreach ($st->fetchAll() as $g) {
        $items[] = [
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
            'status'          => $g['status'],
            'created_at'      => gf_iso($g['created_at']),
            'updated_at'      => gf_iso($g['updated_at']),
        ];
    }
    return ['items' => $items, 'page' => $page, 'limit' => $limit, 'total' => $total];
}

// ---------------------------------------------------------------------------
// ADMIN TRAINER CRUD
// ---------------------------------------------------------------------------
