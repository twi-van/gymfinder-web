<?php
declare(strict_types=1);

// Admin resource: categories.

/**
 * Lấy payload category cho admin.
 */
function gf_admin_category_payload(int $id): ?array
{
    $st = gf_pdo()->prepare('SELECT * FROM categories WHERE id=?');
    $st->execute([$id]);
    $c = $st->fetch();
    if (!$c) {
        return null;
    }
    return [
        'id'          => (int) $c['id'],
        'name'        => $c['name'],
        'slug'        => $c['slug'],
        'description' => $c['description'],
        'is_active'   => (int) $c['is_active'],
        'created_at'  => gf_iso($c['created_at']),
        'updated_at'  => gf_iso($c['updated_at']),
    ];
}


/**
 * Admin xem danh sách category, có filter & phân trang.
 */
function gf_admin_categories_paged(array $q): array
{
    $pageLimit = gf_api_page_limit($q);
    $page  = $pageLimit['page'];
    $limit = $pageLimit['limit'];
    $where  = ['1=1'];
    $params = [];

    $kw = trim((string) ($q['q'] ?? ''));
    if ($kw !== '') {
        $where[] = "(name LIKE ? ESCAPE '!' OR description LIKE ? ESCAPE '!')";
        $like    = gf_like_contains($kw);
        $params[] = $like;
        $params[] = $like;
    }
    $active = $q['is_active'] ?? null;
    if ($active !== null && $active !== '') {
        if ($active !== '0' && $active !== '1' && $active !== 0 && $active !== 1) { gf_api_error('VALIDATION_ERROR', 'is_active chỉ nhận 0 hoặc 1.', 422, ['is_active' => '0|1']); }
        $where[]  = 'is_active = ?';
        $params[] = (int) $active;
    }
    $sort  = (string) ($q['sort'] ?? 'newest');
    $order = match ($sort) {
        'oldest'    => 'id ASC',
        'name_asc'  => 'name ASC, id DESC',
        'name_desc' => 'name DESC, id DESC',
        'newest'    => 'id DESC',
        default     => gf_api_error('VALIDATION_ERROR', 'sort không hợp lệ.', 422, ['sort' => 'newest|oldest|name_asc|name_desc']),
    };

    $wSql = implode(' AND ', $where);
    $cSt  = gf_pdo()->prepare("SELECT COUNT(*) FROM categories WHERE {$wSql}");
    $cSt->execute($params);
    $total = (int) $cSt->fetchColumn();

    $offset = ($page - 1) * $limit;
    $st     = gf_pdo()->prepare("SELECT * FROM categories WHERE {$wSql} ORDER BY {$order} LIMIT {$limit} OFFSET {$offset}");
    $st->execute($params);
    $items = [];
    foreach ($st->fetchAll() as $c) {
        $items[] = [
            'id'          => (int) $c['id'],
            'name'        => $c['name'],
            'slug'        => $c['slug'],
            'description' => $c['description'],
            'is_active'   => (int) $c['is_active'],
            'created_at'  => gf_iso($c['created_at']),
            'updated_at'  => gf_iso($c['updated_at']),
        ];
    }
    return ['items' => $items, 'page' => $page, 'limit' => $limit, 'total' => $total];
}


/**
 * Admin tạo mới hoặc cập nhật category.
 */
function gf_admin_save_category(array $d, ?int $id): array
{
    $allowed = ['name', 'description', 'is_active'];
    gf_require_known_body($d, $allowed, ['id', 'slug', 'created_at', 'updated_at']);
    if ($id && $d === []) {
        gf_api_error('VALIDATION_ERROR', 'PATCH body rỗng.', 422, ['body' => 'Cần ít nhất một field.']);
    }

    $existing = null;
    if ($id) {
        $st = gf_pdo()->prepare('SELECT * FROM categories WHERE id=?');
        $st->execute([$id]);
        $existing = $st->fetch();
        if (!$existing) { gf_api_error('NOT_FOUND', 'Không tìm thấy danh mục.', 404); }
    }

    $merged = $existing ?: [];
    foreach ($d as $k => $v) { $merged[$k] = $v; }

    $details  = [];
    $name     = trim((string) ($merged['name'] ?? ''));
    if ($name === '' || mb_strlen($name) > 100) { $details['name'] = 'name 1–100 ký tự.'; }
    if (isset($merged['description']) && $merged['description'] !== null && mb_strlen((string) $merged['description']) > 5000) { $details['description'] = 'description tối đa 5000 ký tự.'; }
    $isActive = 1;
    if (array_key_exists('is_active', $merged)) {
        $act = $merged['is_active'];
        if ($act !== 0 && $act !== 1 && $act !== '0' && $act !== '1' && $act !== true && $act !== false) {
            $details['is_active'] = 'is_active chỉ nhận 0 hoặc 1.';
        } else {
            $isActive = ($act === 1 || $act === '1' || $act === true) ? 1 : 0;
        }
    }
    if ($details) { gf_api_error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ.', 422, $details); }

    // Kiểm tra trùng tên
    $dupCheckSql = 'SELECT id FROM categories WHERE name=?';
    $dupParams   = [$name];
    if ($id) { $dupCheckSql .= ' AND id <> ?'; $dupParams[] = $id; }
    $dupSt = gf_pdo()->prepare($dupCheckSql);
    $dupSt->execute($dupParams);
    if ($dupSt->fetch()) { gf_api_error('CONFLICT', 'Tên danh mục đã tồn tại.', 409, ['name' => 'Tên danh mục đã tồn tại.']); }

    if ($id) {
        $sets   = [];
        $params = [];
        if (array_key_exists('name', $d))        { $sets[] = 'name=?';        $params[] = $name; }
        if (array_key_exists('description', $d)) { $sets[] = 'description=?'; $params[] = $merged['description'] !== null && trim((string) $merged['description']) !== '' ? trim((string) $merged['description']) : null; }
        if (array_key_exists('is_active', $d))   { $sets[] = 'is_active=?';   $params[] = $isActive; }
        if ($sets) {
            $params[] = $id;
            gf_pdo()->prepare('UPDATE categories SET ' . implode(',', $sets) . ', updated_at=CURRENT_TIMESTAMP WHERE id=?')->execute($params);
        }
        return ['ok' => true, 'id' => $id];
    }

    $slug = gf_unique_slug('categories', gf_slugify($name, 'category'), 120);
    $desc = isset($merged['description']) && trim((string) $merged['description']) !== '' ? trim((string) $merged['description']) : null;
    $st   = gf_pdo()->prepare('INSERT INTO categories (name, slug, description, is_active) VALUES (?,?,?,?)');
    $st->execute([$name, $slug, $desc, $isActive]);
    return ['ok' => true, 'id' => (int) gf_pdo()->lastInsertId()];
}


/**
 * Admin xóa mềm category (set is_active = 0).
 */
function gf_admin_delete_category(int $id): void
{
    $st = gf_pdo()->prepare('SELECT id FROM categories WHERE id=?');
    $st->execute([$id]);
    if (!$st->fetch()) { gf_api_error('NOT_FOUND', 'Không tìm thấy danh mục.', 404); }
    gf_pdo()->prepare('UPDATE categories SET is_active=0 WHERE id=?')->execute([$id]);
}

// ---------------------------------------------------------------------------
// ADMIN REVIEW MODERATION
// ---------------------------------------------------------------------------
