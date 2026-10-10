<?php
declare(strict_types=1);

// Admin resource: trainers.

/**
 * Lấy payload trainer đầy đủ cho admin.
 */
function gf_admin_trainer_payload(int $id): ?array
{
    $st = gf_pdo()->prepare('SELECT * FROM trainers WHERE id=?');
    $st->execute([$id]);
    $t = $st->fetch();
    if (!$t) {
        return null;
    }
    return [
        'id'               => (int) $t['id'],
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
        'status'           => $t['status'],
        'avg_rating'       => gf_num1($t['avg_rating']),
        'review_count'     => (int) $t['review_count'],
        'created_at'       => gf_iso($t['created_at']),
        'updated_at'       => gf_iso($t['updated_at']),
    ];
}


/**
 * Admin tạo mới hoặc cập nhật trainer (dùng chung POST/PATCH).
 */
function gf_admin_save_trainer(array $d, ?int $id): array
{
    $allowed = ['full_name','avatar_url','specialty_id','years_experience','bio','phone','email','gym_id','district_id','is_featured','status'];
    gf_require_known_body($d, $allowed, ['id','slug','avg_rating','review_count','created_at','updated_at']);

    $pdo = gf_pdo();
    $pdo->beginTransaction();
    try {
        $existing = $id ? gf_lock_row('trainers', $id) : null;
        if ($id && !$existing) {
            $pdo->rollBack();
            return ['ok' => false, 'http' => 404, 'code' => 'NOT_FOUND', 'error' => 'Không tìm thấy HLV.'];
        }
        if ($id && $d === []) {
            $pdo->rollBack();
            return ['ok' => false, 'http' => 422, 'code' => 'VALIDATION_ERROR', 'error' => 'PATCH body rỗng.', 'details' => ['body' => 'Cần ít nhất một field.']];
        }

        $merged  = $existing ?: [];
        foreach ($d as $k => $v) { $merged[$k] = $v; }
        $details = [];

        if (!$id) {
            foreach (['full_name', 'specialty_id', 'years_experience', 'gym_id'] as $req) {
                if (!array_key_exists($req, $d)) { $details[$req] = 'Bắt buộc.'; }
            }
            $merged['is_featured'] = array_key_exists('is_featured', $d) ? gf_parse_flag_body($d['is_featured'], 'is_featured') : 0;
            $merged['status']      = $d['status'] ?? 'active';
        } elseif (array_key_exists('is_featured', $d)) {
            $merged['is_featured'] = gf_parse_flag_body($d['is_featured'], 'is_featured');
        }

        $name  = trim((string) ($merged['full_name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100)       { $details['full_name'] = 'full_name 1–100 ký tự.'; }

        $specValue = $merged['specialty_id'] ?? null;
        $spec  = (int) $specValue;
        if (!gf_integer_like($specValue) || $spec < 1 || !gf_exists_id('specialties', $spec)) { $details['specialty_id'] = 'specialty_id không tồn tại.'; }

        $years = $merged['years_experience'] ?? null;
        if (!gf_integer_like($years) || (int) $years < 0 || (int) $years > 60) { $details['years_experience'] = 'years_experience 0–60.'; }
        if (isset($merged['bio']) && $merged['bio'] !== null && mb_strlen((string) $merged['bio']) > 5000) { $details['bio'] = 'bio tối đa 5000.'; }

        $phone = $merged['phone'] ?? null;
        if ($phone !== null && $phone !== '' && !gf_phone_ok((string) $phone)) { $details['phone'] = 'Số điện thoại không hợp lệ.'; }
        $email = $merged['email'] ?? null;
        if ($email !== null && $email !== '' && !filter_var((string) $email, FILTER_VALIDATE_EMAIL)) { $details['email'] = 'Email không hợp lệ.'; }
        if (isset($merged['status']) && !in_array($merged['status'], ['active', 'hidden'], true)) { $details['status'] = 'status chỉ active|hidden.'; }
        if (array_key_exists('avatar_url', $merged) && !gf_uploads_url_ok($merged['avatar_url'] === null ? null : (string) $merged['avatar_url'])) { $details['avatar_url'] = 'Chỉ nhận /uploads/...'; }

        $gymValue = $merged['gym_id'] ?? null;
        $gymId = (int) $gymValue;
        $gym   = $gymId ? gf_lock_row('gyms', $gymId) : null;
        if (!gf_integer_like($gymValue) || !$gym) { $details['gym_id'] = 'gym_id không tồn tại.'; }

        if (array_key_exists('district_id', $d) && !gf_integer_like($d['district_id'])) {
            $details['district_id'] = 'district_id phải là số nguyên.';
        }

        if ($details) {
            $pdo->rollBack();
            return ['ok' => false, 'http' => 422, 'code' => 'VALIDATION_ERROR', 'error' => 'Dữ liệu không hợp lệ.', 'details' => $details];
        }

        $gymDistrict = (int) $gym['district_id'];
        if (array_key_exists('district_id', $d) && (int) $d['district_id'] !== $gymDistrict) {
            $pdo->rollBack();
            return ['ok' => false, 'http' => 422, 'code' => 'BUSINESS_RULE', 'error' => 'district_id phải trùng district của Gym chính.'];
        }
        $districtId = $gymDistrict;

        if ($id) {
            $cols   = ['full_name','avatar_url','specialty_id','years_experience','bio','phone','email','gym_id','district_id','is_featured','status'];
            $sets   = [];
            $params = [];
            foreach ($cols as $col) {
                if ($col === 'district_id' && (array_key_exists('gym_id', $d) || array_key_exists('district_id', $d))) {
                    $sets[]   = 'district_id=?';
                    $params[] = $districtId;
                    continue;
                }
                if (array_key_exists($col, $d) || ($col === 'is_featured' && array_key_exists('is_featured', $d))) {
                    $sets[] = "$col=?";
                    $val    = $merged[$col] ?? null;
                    if (in_array($col, ['phone','email','bio','avatar_url'], true) && $val === '') { $val = null; }
                    $params[] = $val;
                }
            }
            if ($sets) {
                $params[] = $id;
                $pdo->prepare('UPDATE trainers SET ' . implode(',', $sets) . ' WHERE id=?')->execute($params);
            }
            $pdo->commit();
            return ['ok' => true, 'id' => $id];
        }

        $slug = gf_unique_slug('trainers', gf_slugify($name, 'trainer'), 130);
        $pdo->prepare('INSERT INTO trainers (full_name, slug, avatar_url, specialty_id, years_experience, bio, phone, email, gym_id, district_id, is_featured, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)')
            ->execute([
                $name,
                $slug,
                ($merged['avatar_url'] ?? '') === '' ? null : $merged['avatar_url'],
                $spec,
                (int) $years,
                $merged['bio'] ?? null,
                ($phone === '' ? null : $phone),
                ($email === '' ? null : $email),
                $gymId,
                $districtId,
                (int) $merged['is_featured'],
                $merged['status'] ?? 'active',
            ]);
        $newId = (int) $pdo->lastInsertId();
        $pdo->commit();
        return ['ok' => true, 'id' => $newId];
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        return ['ok' => false, 'http' => 500, 'code' => 'SERVER_ERROR', 'error' => 'Lỗi máy chủ.'];
    }
}


/**
 * Admin xem danh sách trainer, có filter & phân trang.
 */
function gf_admin_trainers_paged(array $q): array
{
    $pageLimit = gf_api_page_limit($q);
    $page  = $pageLimit['page'];
    $limit = $pageLimit['limit'];
    $where  = ['1=1'];
    $params = [];

    $kw = trim((string) ($q['q'] ?? ''));
    if ($kw !== '') {
        $where[]  = "t.full_name LIKE ? ESCAPE '!'";
        $params[] = gf_like_contains($kw);
    }
    $status = (string) ($q['status'] ?? '');
    if ($status !== '') {
        if (!in_array($status, ['active', 'hidden'], true)) { gf_api_error('VALIDATION_ERROR', 'status chỉ active|hidden.', 422, ['status' => 'active|hidden']); }
        $where[]  = 't.status = ?';
        $params[] = $status;
    }
    $gymId = $q['gym_id'] ?? null;
    if ($gymId !== null && $gymId !== '') {
        if (!gf_integer_like($gymId) || (int) $gymId < 1 || !gf_exists_id('gyms', (int) $gymId)) { gf_api_error('VALIDATION_ERROR', 'gym_id không tồn tại.', 422, ['gym_id' => 'Không tồn tại.']); }
        $where[]  = 't.gym_id = ?';
        $params[] = (int) $gymId;
    }
    $spec = $q['specialty_id'] ?? null;
    if ($spec !== null && $spec !== '') {
        if (!gf_integer_like($spec) || (int) $spec < 1 || !gf_exists_id('specialties', (int) $spec)) { gf_api_error('VALIDATION_ERROR', 'specialty_id không tồn tại.', 422, ['specialty_id' => 'Không tồn tại.']); }
        $where[]  = 't.specialty_id = ?';
        $params[] = (int) $spec;
    }
    $feat = $q['is_featured'] ?? null;
    if ($feat !== null && $feat !== '') {
        if ($feat !== '0' && $feat !== '1' && $feat !== 0 && $feat !== 1) { gf_api_error('VALIDATION_ERROR', 'is_featured chỉ nhận 0 hoặc 1.', 422, ['is_featured' => '0|1']); }
        $where[]  = 't.is_featured = ?';
        $params[] = (int) $feat;
    }
    $sort  = (string) ($q['sort'] ?? 'newest');
    $order = match ($sort) {
        'oldest'    => 't.id ASC',
        'name_asc'  => 't.full_name ASC, t.id DESC',
        'name_desc' => 't.full_name DESC, t.id DESC',
        'newest'    => 't.id DESC',
        default     => gf_api_error('VALIDATION_ERROR', 'sort không hợp lệ.', 422, ['sort' => 'newest|oldest|name_asc|name_desc']),
    };

    $wSql = implode(' AND ', $where);
    $cSt  = gf_pdo()->prepare("SELECT COUNT(*) FROM trainers t WHERE {$wSql}");
    $cSt->execute($params);
    $total = (int) $cSt->fetchColumn();

    $offset = ($page - 1) * $limit;
    $st     = gf_pdo()->prepare("SELECT t.* FROM trainers t WHERE {$wSql} ORDER BY {$order} LIMIT {$limit} OFFSET {$offset}");
    $st->execute($params);
    $items = [];
    foreach ($st->fetchAll() as $t) {
        $items[] = [
            'id'               => (int) $t['id'],
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
            'status'           => $t['status'],
            'avg_rating'       => gf_num1($t['avg_rating']),
            'review_count'     => (int) $t['review_count'],
            'created_at'       => gf_iso($t['created_at']),
            'updated_at'       => gf_iso($t['updated_at']),
        ];
    }
    return ['items' => $items, 'page' => $page, 'limit' => $limit, 'total' => $total];
}

// ---------------------------------------------------------------------------
// ADMIN CATEGORY CRUD
// ---------------------------------------------------------------------------
