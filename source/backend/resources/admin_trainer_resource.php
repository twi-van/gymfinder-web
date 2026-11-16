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

    $stSpec = gf_pdo()->prepare("SELECT s.id, s.name, s.slug FROM specialties s JOIN trainer_specialties ts ON ts.specialty_id = s.id WHERE ts.trainer_id = ? ORDER BY s.name");
    $stSpec->execute([$id]);
    $specialties = $stSpec->fetchAll();
    $specialtyIds = array_map('intval', array_column($specialties, 'id'));

    $stGym = gf_pdo()->prepare("SELECT g.id, g.name, g.slug, g.address, g.cover_image_url, g.district_id FROM gyms g JOIN gym_trainers gt ON gt.gym_id = g.id WHERE gt.trainer_id = ? ORDER BY g.name");
    $stGym->execute([$id]);
    $gyms = $stGym->fetchAll();
    $gymIds = array_map('intval', array_column($gyms, 'id'));
    $primarySpecialty = $specialties[0] ?? null;
    $primaryGym = $gyms[0] ?? null;

    return [
        'id'               => (int) $t['id'],
        'full_name'        => $t['full_name'],
        'slug'             => $t['slug'],
        'avatar_url'       => $t['avatar_url'],
        'specialty_id'     => (int) ($primarySpecialty['id'] ?? 0),
        'specialty_ids'    => $specialtyIds,
        'specialties'      => $specialties,
        'years_experience' => (int) $t['years_experience'],
        'bio'              => $t['bio'],
        'phone'            => $t['phone'],
        'email'            => $t['email'],
        'gym_id'           => (int) ($primaryGym['id'] ?? 0),
        'gym_ids'          => $gymIds,
        'gyms'             => $gyms,
        'district_id'      => (int) ($primaryGym['district_id'] ?? 0),
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
    $allowed = ['full_name','avatar_url','specialty_id','specialty_ids','years_experience','bio','phone','email','gym_id','gym_ids','district_id','is_featured','status'];
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

        $hasSpecSelection = array_key_exists('specialty_ids', $d) || array_key_exists('specialty_id', $d);
        $hasGymSelection = array_key_exists('gym_ids', $d) || array_key_exists('gym_id', $d);

        // Check required fields for creation
        if (!$id) {
            if (empty($d['full_name'])) { $details['full_name'] = 'Bắt buộc.'; }
            if (!$hasSpecSelection) { $details['specialty_ids'] = 'Chọn ít nhất một chuyên môn.'; }
            if (!array_key_exists('years_experience', $d)) { $details['years_experience'] = 'Bắt buộc.'; }
            if (!$hasGymSelection) { $details['gym_ids'] = 'Chọn ít nhất một phòng tập.'; }
            $merged['is_featured'] = array_key_exists('is_featured', $d) ? gf_parse_flag_body($d['is_featured'], 'is_featured') : 0;
            $merged['status']      = $d['status'] ?? 'active';
        } elseif (array_key_exists('is_featured', $d)) {
            $merged['is_featured'] = gf_parse_flag_body($d['is_featured'], 'is_featured');
        }

        $name = trim((string) ($merged['full_name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) { $details['full_name'] = 'full_name 1–100 ký tự.'; }

        // Read/write the N-N relationship tables as the only source of truth.
        $rawSpecs = [];
        if ($hasSpecSelection) {
            $value = array_key_exists('specialty_ids', $d) ? $d['specialty_ids'] : [$d['specialty_id']];
            $rawSpecs = is_array($value) ? $value : explode(',', (string) $value);
            if ($rawSpecs === []) { $details['specialty_ids'] = 'Chọn ít nhất một chuyên môn.'; }
        } elseif ($id) {
            $current = $pdo->prepare('SELECT specialty_id FROM trainer_specialties WHERE trainer_id=? ORDER BY specialty_id');
            $current->execute([$id]);
            $rawSpecs = array_column($current->fetchAll(), 'specialty_id');
        }
        $specIds = [];
        foreach ($rawSpecs as $sid) {
            if (!gf_integer_like($sid) || (int) $sid < 1 || !gf_exists_id('specialties', (int) $sid)) {
                $details['specialty_ids'] = 'Chuyên môn không tồn tại.';
                break;
            }
            $specIds[] = (int) $sid;
        }
        $specIds = array_values(array_unique($specIds));
        if ($specIds === []) { $details['specialty_ids'] ??= 'Mỗi HLV cần ít nhất một chuyên môn.'; }
        $spec = $specIds[0] ?? 0;

        $years = $merged['years_experience'] ?? null;
        if (!gf_integer_like($years) || (int) $years < 0 || (int) $years > 60) { $details['years_experience'] = 'years_experience 0–60.'; }
        if (isset($merged['bio']) && $merged['bio'] !== null && mb_strlen((string) $merged['bio']) > 5000) { $details['bio'] = 'bio tối đa 5000.'; }

        $phone = $merged['phone'] ?? null;
        if ($phone !== null && $phone !== '' && !gf_phone_ok((string) $phone)) { $details['phone'] = 'Số điện thoại không hợp lệ.'; }
        $email = $merged['email'] ?? null;
        if ($email !== null && $email !== '' && !filter_var((string) $email, FILTER_VALIDATE_EMAIL)) { $details['email'] = 'Email không hợp lệ.'; }
        if (isset($merged['status']) && !in_array($merged['status'], ['active', 'hidden'], true)) { $details['status'] = 'status chỉ active|hidden.'; }
        // Existing trainers may still use legacy external avatar URLs. Allow an unchanged
        // URL during profile edits, but only accept system-managed uploads for new/changed URLs.
        if (array_key_exists('avatar_url', $d)
            && (!$existing || $d['avatar_url'] !== ($existing['avatar_url'] ?? null))
            && !gf_uploads_url_ok($d['avatar_url'] === null ? null : (string) $d['avatar_url'])) {
            $details['avatar_url'] = 'Chỉ nhận /uploads/...';
        }

        $rawGyms = [];
        if ($hasGymSelection) {
            $value = array_key_exists('gym_ids', $d) ? $d['gym_ids'] : [$d['gym_id']];
            $rawGyms = is_array($value) ? $value : explode(',', (string) $value);
            if ($rawGyms === []) { $details['gym_ids'] = 'Chọn ít nhất một phòng tập.'; }
        } elseif ($id) {
            $current = $pdo->prepare('SELECT gym_id FROM gym_trainers WHERE trainer_id=? ORDER BY gym_id');
            $current->execute([$id]);
            $rawGyms = array_column($current->fetchAll(), 'gym_id');
        }
        $gymIds = [];
        foreach ($rawGyms as $gid) {
            if (!gf_integer_like($gid) || (int) $gid < 1 || !gf_exists_id('gyms', (int) $gid)) {
                $details['gym_ids'] = 'Phòng tập liên kết không tồn tại.';
                break;
            }
            $gymIds[] = (int) $gid;
        }
        $gymIds = array_values(array_unique($gymIds));
        if ($gymIds === []) { $details['gym_ids'] ??= 'Mỗi HLV cần liên kết ít nhất một phòng tập.'; }
        $gymId = $gymIds[0] ?? 0;
        $gym = $gymId > 0 ? gf_lock_row('gyms', $gymId) : null;

        if (array_key_exists('district_id', $d) && !gf_integer_like($d['district_id'])) {
            $details['district_id'] = 'district_id phải là số nguyên.';
        }

        if ($details) {
            $pdo->rollBack();
            return ['ok' => false, 'http' => 422, 'code' => 'VALIDATION_ERROR', 'error' => 'Dữ liệu không hợp lệ.', 'details' => $details];
        }

        $districtId = $gym ? (int) $gym['district_id'] : (int) ($existing['district_id'] ?? 1);

        if ($id) {
            $cols   = ['full_name','avatar_url','years_experience','bio','phone','email','district_id','is_featured','status'];
            $sets   = [];
            $params = [];
            foreach ($cols as $col) {
                if ($col === 'district_id' && (array_key_exists('gym_id', $d) || array_key_exists('district_id', $d) || array_key_exists('gym_ids', $d))) {
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

            // Sync trainer_specialties
            if ($hasSpecSelection) {
                $pdo->prepare('DELETE FROM trainer_specialties WHERE trainer_id=?')->execute([$id]);
                $insTS = $pdo->prepare('INSERT INTO trainer_specialties (trainer_id, specialty_id) VALUES (?,?)');
                foreach ($specIds as $sid) {
                    $insTS->execute([$id, $sid]);
                }
            }

            // Sync gym_trainers
            if ($hasGymSelection) {
                $pdo->prepare('DELETE FROM gym_trainers WHERE trainer_id=?')->execute([$id]);
                $insGT = $pdo->prepare('INSERT INTO gym_trainers (gym_id, trainer_id) VALUES (?,?)');
                foreach ($gymIds as $gid) {
                    $insGT->execute([$gid, $id]);
                }
            }

            $oldAvatar = $existing['avatar_url'] ?? null;
            $newAvatar = $merged['avatar_url'] ?? null;
            $pdo->commit();
            if (array_key_exists('avatar_url', $d) && $oldAvatar && $oldAvatar !== $newAvatar) {
                gf_delete_unreferenced_files([$oldAvatar]);
            }
            return ['ok' => true, 'id' => $id];
        }

        $slug = gf_unique_slug('trainers', gf_slugify($name, 'trainer'), 130);
        $pdo->prepare('INSERT INTO trainers (full_name, slug, avatar_url, years_experience, bio, phone, email, district_id, is_featured, status) VALUES (?,?,?,?,?,?,?,?,?,?)')
            ->execute([
                $name,
                $slug,
                ($merged['avatar_url'] ?? '') === '' ? null : $merged['avatar_url'],
                (int) $years,
                $merged['bio'] ?? null,
                ($phone === '' ? null : $phone),
                ($email === '' ? null : $email),
                $districtId,
                (int) $merged['is_featured'],
                $merged['status'] ?? 'active',
            ]);
        $newId = (int) $pdo->lastInsertId();

        // Insert trainer_specialties
        $insTS = $pdo->prepare('INSERT INTO trainer_specialties (trainer_id, specialty_id) VALUES (?,?)');
        foreach ($specIds as $sid) {
            $insTS->execute([$newId, $sid]);
        }

        // Insert gym_trainers
        $insGT = $pdo->prepare('INSERT INTO gym_trainers (gym_id, trainer_id) VALUES (?,?)');
        foreach ($gymIds as $gid) {
            $insGT->execute([$gid, $newId]);
        }

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
        $where[]  = 'EXISTS (SELECT 1 FROM gym_trainers gt WHERE gt.trainer_id=t.id AND gt.gym_id=?)';
        $params[] = (int) $gymId;
    }
    $spec = $q['specialty_id'] ?? null;
    if ($spec !== null && $spec !== '') {
        if (!gf_integer_like($spec) || (int) $spec < 1 || !gf_exists_id('specialties', (int) $spec)) { gf_api_error('VALIDATION_ERROR', 'specialty_id không tồn tại.', 422, ['specialty_id' => 'Không tồn tại.']); }
        $where[]  = 'EXISTS (SELECT 1 FROM trainer_specialties ts WHERE ts.trainer_id=t.id AND ts.specialty_id=?)';
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
    $cSt  = gf_pdo()->prepare("SELECT COUNT(DISTINCT t.id) FROM trainers t WHERE {$wSql}");
    $cSt->execute($params);
    $total = (int) $cSt->fetchColumn();

    $offset = ($page - 1) * $limit;
    $st     = gf_pdo()->prepare("SELECT DISTINCT t.* FROM trainers t WHERE {$wSql} ORDER BY {$order} LIMIT {$limit} OFFSET {$offset}");
    $st->execute($params);
    $items = [];

    $tsSt = gf_pdo()->prepare("SELECT s.id, s.name FROM specialties s JOIN trainer_specialties ts ON ts.specialty_id = s.id WHERE ts.trainer_id = ? ORDER BY s.name");
    $gtSt = gf_pdo()->prepare("SELECT g.id, g.name, g.district_id FROM gyms g JOIN gym_trainers gt ON gt.gym_id = g.id WHERE gt.trainer_id = ? ORDER BY g.name");

    foreach ($st->fetchAll() as $t) {
        $tId = (int)$t['id'];

        $tsSt->execute([$tId]);
        $specs = $tsSt->fetchAll();
        $specIds = array_map('intval', array_column($specs, 'id'));
        $specNames = implode(', ', array_column($specs, 'name'));

        $gtSt->execute([$tId]);
        $gyms = $gtSt->fetchAll();
        $gymIds = array_map('intval', array_column($gyms, 'id'));
        $gymNames = implode(', ', array_column($gyms, 'name'));
        $primarySpecialtyId = (int) ($specIds[0] ?? 0);
        $primaryGym = $gyms[0] ?? null;

        $items[] = [
            'id'               => $tId,
            'full_name'        => $t['full_name'],
            'slug'             => $t['slug'],
            'avatar_url'       => $t['avatar_url'],
            'specialty_id'     => $primarySpecialtyId,
            'specialty_ids'    => $specIds,
            'specialty_names'  => $specNames ?: 'Chung',
            'specialties'      => $specs,
            'years_experience' => (int) $t['years_experience'],
            'bio'              => $t['bio'],
            'phone'            => $t['phone'],
            'email'            => $t['email'],
            'gym_id'           => (int) ($primaryGym['id'] ?? 0),
            'gym_ids'          => $gymIds,
            'gym_names'        => $gymNames ?: 'Chưa gán',
            'gyms'             => $gyms,
            'district_id'      => (int) ($primaryGym['district_id'] ?? 0),
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
