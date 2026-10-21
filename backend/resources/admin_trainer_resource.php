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
    if (empty($specialties) && !empty($t['specialty_id'])) {
        $stS1 = gf_pdo()->prepare("SELECT id, name, slug FROM specialties WHERE id = ?");
        $stS1->execute([(int)$t['specialty_id']]);
        $rowS = $stS1->fetch();
        if ($rowS) { $specialties = [$rowS]; }
    }
    $specialtyIds = array_map('intval', array_column($specialties, 'id'));

    $stGym = gf_pdo()->prepare("SELECT g.id, g.name, g.slug, g.address, g.cover_image_url FROM gyms g JOIN gym_trainers gt ON gt.gym_id = g.id WHERE gt.trainer_id = ? ORDER BY g.name");
    $stGym->execute([$id]);
    $gyms = $stGym->fetchAll();
    if (empty($gyms) && !empty($t['gym_id'])) {
        $stG1 = gf_pdo()->prepare("SELECT id, name, slug, address, cover_image_url FROM gyms WHERE id = ?");
        $stG1->execute([(int)$t['gym_id']]);
        $rowG = $stG1->fetch();
        if ($rowG) { $gyms = [$rowG]; }
    }
    $gymIds = array_map('intval', array_column($gyms, 'id'));

    return [
        'id'               => (int) $t['id'],
        'full_name'        => $t['full_name'],
        'slug'             => $t['slug'],
        'avatar_url'       => $t['avatar_url'],
        'specialty_id'     => (int) $t['specialty_id'],
        'specialty_ids'    => $specialtyIds,
        'specialties'      => $specialties,
        'years_experience' => (int) $t['years_experience'],
        'bio'              => $t['bio'],
        'phone'            => $t['phone'],
        'email'            => $t['email'],
        'gym_id'           => (int) $t['gym_id'],
        'gym_ids'          => $gymIds,
        'gyms'             => $gyms,
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

        // Check required fields for creation
        if (!$id) {
            if (empty($d['full_name'])) { $details['full_name'] = 'Bắt buộc.'; }
            if (!array_key_exists('specialty_id', $d) && empty($d['specialty_ids'])) { $details['specialty_id'] = 'Bắt buộc.'; }
            if (!array_key_exists('years_experience', $d)) { $details['years_experience'] = 'Bắt buộc.'; }
            if (!array_key_exists('gym_id', $d) && empty($d['gym_ids'])) { $details['gym_id'] = 'Bắt buộc.'; }
            $merged['is_featured'] = array_key_exists('is_featured', $d) ? gf_parse_flag_body($d['is_featured'], 'is_featured') : 0;
            $merged['status']      = $d['status'] ?? 'active';
        } elseif (array_key_exists('is_featured', $d)) {
            $merged['is_featured'] = gf_parse_flag_body($d['is_featured'], 'is_featured');
        }

        $name = trim((string) ($merged['full_name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) { $details['full_name'] = 'full_name 1–100 ký tự.'; }

        // Specialty validation (support specialty_ids or specialty_id)
        $specIds = [];
        if (!empty($merged['specialty_ids'])) {
            $rawSpecs = is_array($merged['specialty_ids']) ? $merged['specialty_ids'] : explode(',', (string)$merged['specialty_ids']);
            foreach ($rawSpecs as $sid) {
                if (!gf_integer_like($sid) || (int)$sid < 1 || !gf_exists_id('specialties', (int)$sid)) {
                    $details['specialty_ids'] = 'Chuyên môn không tồn tại.';
                    break;
                }
                $specIds[] = (int)$sid;
            }
        } elseif (!empty($merged['specialty_id'])) {
            $specVal = (int)$merged['specialty_id'];
            if ($specVal < 1 || !gf_exists_id('specialties', $specVal)) {
                $details['specialty_id'] = 'specialty_id không tồn tại.';
            } else {
                $specIds = [$specVal];
            }
        }
        $spec = $specIds[0] ?? (int)($existing['specialty_id'] ?? 1);

        $years = $merged['years_experience'] ?? null;
        if (!gf_integer_like($years) || (int) $years < 1 || (int) $years > 60) { $details['years_experience'] = 'years_experience 1–60.'; }
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

        // Gym validation (support gym_ids or gym_id)
        $gymIds = [];
        if (!empty($merged['gym_ids'])) {
            $rawGyms = is_array($merged['gym_ids']) ? $merged['gym_ids'] : explode(',', (string)$merged['gym_ids']);
            foreach ($rawGyms as $gid) {
                if (!gf_integer_like($gid) || (int)$gid < 1 || !gf_exists_id('gyms', (int)$gid)) {
                    $details['gym_ids'] = 'Phòng tập liên kết không tồn tại.';
                    break;
                }
                $gymIds[] = (int)$gid;
            }
        } elseif (!empty($merged['gym_id'])) {
            $gymVal = (int)$merged['gym_id'];
            if ($gymVal < 1 || !gf_exists_id('gyms', $gymVal)) {
                $details['gym_id'] = 'gym_id không tồn tại.';
            } else {
                $gymIds = [$gymVal];
            }
        }
        $gymId = $gymIds[0] ?? (int)($existing['gym_id'] ?? 1);
        $gym = gf_lock_row('gyms', $gymId);

        if (array_key_exists('district_id', $d) && !gf_integer_like($d['district_id'])) {
            $details['district_id'] = 'district_id phải là số nguyên.';
        }

        if ($details) {
            $pdo->rollBack();
            return ['ok' => false, 'http' => 422, 'code' => 'VALIDATION_ERROR', 'error' => 'Dữ liệu không hợp lệ.', 'details' => $details];
        }

        $districtId = $gym ? (int) $gym['district_id'] : (int) ($existing['district_id'] ?? 1);

        if ($id) {
            $cols   = ['full_name','avatar_url','specialty_id','years_experience','bio','phone','email','gym_id','district_id','is_featured','status'];
            $sets   = [];
            $params = [];
            foreach ($cols as $col) {
                if ($col === 'district_id' && (array_key_exists('gym_id', $d) || array_key_exists('district_id', $d) || array_key_exists('gym_ids', $d))) {
                    $sets[]   = 'district_id=?';
                    $params[] = $districtId;
                    continue;
                }
                if ($col === 'specialty_id' && (array_key_exists('specialty_ids', $d) || array_key_exists('specialty_id', $d))) {
                    $sets[]   = 'specialty_id=?';
                    $params[] = $spec;
                    continue;
                }
                if ($col === 'gym_id' && (array_key_exists('gym_ids', $d) || array_key_exists('gym_id', $d))) {
                    $sets[]   = 'gym_id=?';
                    $params[] = $gymId;
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
            if (!empty($specIds)) {
                $pdo->prepare('DELETE FROM trainer_specialties WHERE trainer_id=?')->execute([$id]);
                $insTS = $pdo->prepare('INSERT INTO trainer_specialties (trainer_id, specialty_id) VALUES (?,?)');
                foreach ($specIds as $sid) {
                    $insTS->execute([$id, $sid]);
                }
            }

            // Sync gym_trainers
            if (!empty($gymIds)) {
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
        $where[]  = '(t.gym_id = ? OR EXISTS (SELECT 1 FROM gym_trainers gt WHERE gt.trainer_id = t.id AND gt.gym_id = ?))';
        $params[] = (int) $gymId;
        $params[] = (int) $gymId;
    }
    $spec = $q['specialty_id'] ?? null;
    if ($spec !== null && $spec !== '') {
        if (!gf_integer_like($spec) || (int) $spec < 1 || !gf_exists_id('specialties', (int) $spec)) { gf_api_error('VALIDATION_ERROR', 'specialty_id không tồn tại.', 422, ['specialty_id' => 'Không tồn tại.']); }
        $where[]  = '(t.specialty_id = ? OR EXISTS (SELECT 1 FROM trainer_specialties ts WHERE ts.trainer_id = t.id AND ts.specialty_id = ?))';
        $params[] = (int) $spec;
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
    $gtSt = gf_pdo()->prepare("SELECT g.id, g.name FROM gyms g JOIN gym_trainers gt ON gt.gym_id = g.id WHERE gt.trainer_id = ? ORDER BY g.name");

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

        $items[] = [
            'id'               => $tId,
            'full_name'        => $t['full_name'],
            'slug'             => $t['slug'],
            'avatar_url'       => $t['avatar_url'],
            'specialty_id'     => (int) $t['specialty_id'],
            'specialty_ids'    => $specIds,
            'specialty_names'  => $specNames ?: 'Chung',
            'specialties'      => $specs,
            'years_experience' => (int) $t['years_experience'],
            'bio'              => $t['bio'],
            'phone'            => $t['phone'],
            'email'            => $t['email'],
            'gym_id'           => (int) $t['gym_id'],
            'gym_ids'          => $gymIds,
            'gym_names'        => $gymNames ?: 'Chưa gán',
            'gyms'             => $gyms,
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
