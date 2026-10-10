<?php
declare(strict_types=1);

// =============================================================================
// GYM REPOSITORY — tất cả query liên quan đến phòng tập
// =============================================================================

/**
 * Đính kèm thông tin phụ (categories, amenities, images) vào một gym.
 */
function gf_attach_gym_extras(array $gym): array
{
    $id = (int) $gym['id'];

    $st = gf_pdo()->prepare('SELECT c.* FROM categories c JOIN gym_categories gc ON gc.category_id = c.id WHERE gc.gym_id = ?');
    $st->execute([$id]);
    $gym['categories'] = $st->fetchAll();

    $st = gf_pdo()->prepare('SELECT a.* FROM amenities a JOIN gym_amenities ga ON ga.amenity_id = a.id WHERE ga.gym_id = ?');
    $st->execute([$id]);
    $gym['amenities'] = $st->fetchAll();

    $st = gf_pdo()->prepare('SELECT * FROM gym_images WHERE gym_id = ? ORDER BY sort_order');
    $st->execute([$id]);
    $gym['images'] = $st->fetchAll();

    $gym['cover']       = gf_cover_image($gym['cover_image_url'] ?? null, $id);
    $gym['price_label'] = gf_format_price((int) $gym['price_min'], (int) $gym['price_max']);

    $st = gf_pdo()->prepare("SELECT COUNT(*) FROM trainers t JOIN gyms g ON g.id = t.gym_id WHERE t.gym_id = ? AND t.status='active' AND g.status='active'");
    $st->execute([$id]);
    $gym['has_pt'] = (int) $st->fetchColumn() > 0;

    return $gym;
}

/**
 * Tìm kiếm gym có phân trang.
 */
function gf_search_gyms_paged(array $f): array
{
    $sql    = "FROM gyms g JOIN districts d ON d.id = g.district_id WHERE " . gf_gym_public_where();
    $params = [];

    $q = trim((string) ($f['q'] ?? $f['keyword'] ?? ''));
    if ($q !== '') {
        $sql .= " AND (g.name LIKE ? ESCAPE '!' OR g.address LIKE ? ESCAPE '!' OR g.description LIKE ? ESCAPE '!')";
        $kw   = gf_like_contains($q);
        array_push($params, $kw, $kw, $kw);
    }
    if (!empty($f['district_id'])) {
        $sql     .= ' AND g.district_id = ?';
        $params[] = (int) $f['district_id'];
    }

    $priceFrom = $f['price_from'] ?? $f['price_min'] ?? '';
    $priceTo   = $f['price_to']   ?? $f['price_max'] ?? '';
    if ($priceFrom !== '' && $priceFrom !== null) {
        $sql     .= ' AND g.price_max >= ?';
        $params[] = (int) $priceFrom;
    }
    if ($priceTo !== '' && $priceTo !== null) {
        $sql     .= ' AND g.price_min <= ?';
        $params[] = (int) $priceTo;
    }
    if (!empty($f['price_max_cap'])) {
        $sql     .= ' AND g.price_min <= ?';
        $params[] = (int) $f['price_max_cap'];
    }

    $minRating = $f['min_rating'] ?? $f['rating'] ?? '';
    if ($minRating !== '' && $minRating !== null) {
        $sql     .= ' AND g.avg_rating >= ?';
        $params[] = (float) $minRating;
    }
    if (!empty($f['featured'])) {
        $sql .= ' AND g.is_featured = 1';
    }

    // Lọc theo category
    $catIds = [];
    if (!empty($f['category_ids'])) {
        $catIds = is_array($f['category_ids']) ? $f['category_ids'] : array_map('intval', explode(',', (string) $f['category_ids']));
    } elseif (!empty($f['category_id'])) {
        $catIds = [(int) $f['category_id']];
    }
    if ($catIds) {
        $ph   = implode(',', array_fill(0, count($catIds), '?'));
        $sql .= " AND EXISTS (SELECT 1 FROM gym_categories gc JOIN categories c ON c.id=gc.category_id WHERE gc.gym_id=g.id AND c.is_active=1 AND gc.category_id IN ($ph))";
        foreach ($catIds as $id) {
            $params[] = (int) $id;
        }
    }

    // Lọc theo amenity
    $amIds = [];
    if (!empty($f['amenity_ids'])) {
        $amIds = is_array($f['amenity_ids']) ? $f['amenity_ids'] : array_map('intval', explode(',', (string) $f['amenity_ids']));
    } elseif (!empty($f['amenity_id'])) {
        $amIds = [(int) $f['amenity_id']];
    }
    foreach ($amIds as $aid) {
        $sql     .= ' AND EXISTS (SELECT 1 FROM gym_amenities ga WHERE ga.gym_id = g.id AND ga.amenity_id = ?)';
        $params[] = (int) $aid;
    }

    if (!empty($f['has_pt'])) {
        $sql .= " AND EXISTS (SELECT 1 FROM trainers t WHERE t.gym_id = g.id AND t.status='active')";
    }
    if (!empty($f['open_247'])) {
        $sql .= " AND g.opening_hours LIKE '%24/7%'";
    }
    if (!empty($f['weight_loss'])) {
        $sql .= " AND EXISTS (SELECT 1 FROM trainers t JOIN specialties s ON s.id=t.specialty_id WHERE t.gym_id=g.id AND t.status='active' AND s.slug='cardio-giam-can')";
    }

    $sort  = (string) ($f['sort'] ?? '');
    $order = match ($sort) {
        'rating_asc'  => 'g.avg_rating ASC, g.id DESC',
        'price_asc'   => 'g.price_min ASC, g.id DESC',
        'price_desc'  => 'g.price_min DESC, g.id DESC',
        'rating_desc' => 'g.avg_rating DESC, g.id DESC',
        default       => 'g.is_featured DESC, g.avg_rating DESC, g.id DESC',
    };

    $page  = max(1, (int) ($f['page']  ?? 1));
    $limit = min(100, max(1, (int) ($f['limit'] ?? 20)));

    $countSt = gf_pdo()->prepare("SELECT COUNT(*) $sql");
    $countSt->execute($params);
    $total = (int) $countSt->fetchColumn();

    $offset = ($page - 1) * $limit;
    $st     = gf_pdo()->prepare("SELECT g.*, d.name AS district_name, d.slug AS district_slug $sql ORDER BY $order LIMIT $limit OFFSET $offset");
    $st->execute($params);
    $items = array_map('gf_attach_gym_extras', $st->fetchAll());

    return ['items' => $items, 'page' => $page, 'limit' => $limit, 'total' => $total];
}

/**
 * Wrapper trả về chỉ items (không phân trang).
 */
function gf_search_gyms(array $f): array
{
    return gf_search_gyms_paged($f)['items'];
}

/**
 * Lấy gym theo ID hoặc slug.
 */
function gf_get_gym_by_key(string $idOrSlug, bool $publicOnly = true): ?array
{
    $sql = "SELECT g.*, d.name AS district_name, d.slug AS district_slug FROM gyms g JOIN districts d ON d.id = g.district_id WHERE ";
    if (ctype_digit($idOrSlug)) {
        $sql .= 'g.id = ?';
        $arg  = (int) $idOrSlug;
    } else {
        $sql .= 'g.slug = ?';
        $arg  = $idOrSlug;
    }
    if ($publicOnly) {
        $sql .= ' AND ' . gf_gym_public_where();
    }
    $st = gf_pdo()->prepare($sql);
    $st->execute([$arg]);
    $gym = $st->fetch();
    return $gym ? gf_attach_gym_extras($gym) : null;
}

/**
 * Lấy gym theo ID (wrapper).
 */
function gf_get_gym(int $id, bool $publicOnly = true): ?array
{
    return gf_get_gym_by_key((string) $id, $publicOnly);
}

/**
 * Lấy danh sách trainer đang active của một gym.
 */
function gf_gym_active_trainers(int $gymId): array
{
    $st = gf_pdo()->prepare("SELECT t.* FROM trainers t WHERE t.gym_id=? AND t.status='active' ORDER BY t.is_featured DESC, t.id DESC");
    $st->execute([$gymId]);
    return array_map('gf_trainer_list_item', $st->fetchAll());
}

/**
 * Lấy đánh giá đã approved của một gym.
 */
function gf_gym_reviews(int $gymId): array
{
    $st = gf_pdo()->prepare("SELECT r.*, u.full_name FROM reviews r JOIN users u ON u.id = r.user_id WHERE r.gym_id = ? AND r.status='approved' ORDER BY r.created_at DESC");
    $st->execute([$gymId]);
    return $st->fetchAll();
}

/**
 * Đồng bộ relations (categories, amenities, images) khi admin save gym.
 * Trả về danh sách file cần xóa (không còn được reference).
 */
function gf_sync_gym_relations(int $gymId, array $d, string $cover): array
{
    $deleteFiles = [];

    if (array_key_exists('category_ids', $d)) {
        if (!is_array($d['category_ids'])) {
            gf_api_error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ.', 422, ['category_ids' => 'Phải là mảng.']);
        }
        $ids = array_values(array_unique(array_map('intval', $d['category_ids'])));
        gf_assert_ids_exist($ids, 'categories', 'category_ids');
        gf_pdo()->prepare('DELETE FROM gym_categories WHERE gym_id=?')->execute([$gymId]);
        $ins = gf_pdo()->prepare('INSERT INTO gym_categories (gym_id, category_id) VALUES (?,?)');
        foreach ($ids as $cid) {
            $ins->execute([$gymId, $cid]);
        }
    }

    if (array_key_exists('amenity_ids', $d)) {
        if (!is_array($d['amenity_ids'])) {
            gf_api_error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ.', 422, ['amenity_ids' => 'Phải là mảng.']);
        }
        $ids = array_values(array_unique(array_map('intval', $d['amenity_ids'])));
        gf_assert_ids_exist($ids, 'amenities', 'amenity_ids');
        gf_pdo()->prepare('DELETE FROM gym_amenities WHERE gym_id=?')->execute([$gymId]);
        $ins = gf_pdo()->prepare('INSERT INTO gym_amenities (gym_id, amenity_id) VALUES (?,?)');
        foreach ($ids as $aid) {
            $ins->execute([$gymId, $aid]);
        }
    }

    if (array_key_exists('images', $d)) {
        if (!is_array($d['images']) || count($d['images']) > 20) {
            gf_api_error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ.', 422, ['images' => 'Tối đa 20 ảnh.']);
        }
        $keepIds = [];
        $urls    = [];
        foreach ($d['images'] as $i => $img) {
            if (!is_array($img)) {
                gf_api_error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ.', 422, ['images' => 'Phần tử không hợp lệ.']);
            }
            $url = (string) ($img['image_url'] ?? '');
            if ($url === '' || !gf_uploads_url_ok($url)) {
                gf_api_error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ.', 422, ['images' => 'image_url không hợp lệ.']);
            }
            if ($cover !== '' && $url === $cover) {
                gf_api_error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ.', 422, ['images' => 'Không trùng cover_image_url.']);
            }
            if (in_array($url, $urls, true)) {
                gf_api_error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ.', 422, ['images' => 'image_url trùng.']);
            }
            $urls[]  = $url;
            $caption = $img['caption'] ?? null;
            if ($caption !== null && mb_strlen((string) $caption) > 150) {
                gf_api_error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ.', 422, ['images' => 'caption ≤ 150.']);
            }
            $sort = (int) ($img['sort_order'] ?? 0);
            if ($sort < 0 || $sort > 65535) {
                gf_api_error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ.', 422, ['images' => 'sort_order 0..65535.']);
            }
            if (isset($img['id'])) {
                $imgId = (int) $img['id'];
                $cur   = gf_pdo()->prepare('SELECT * FROM gym_images WHERE id=? AND gym_id=?');
                $cur->execute([$imgId, $gymId]);
                $row = $cur->fetch();
                if (!$row) {
                    gf_api_error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ.', 422, ['images' => "id {$imgId} không thuộc Gym này."]);
                }
                if (isset($img['image_url']) && $img['image_url'] !== $row['image_url']) {
                    gf_api_error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ.', 422, ['images' => 'Không đổi image_url khi có id.']);
                }
                gf_pdo()->prepare('UPDATE gym_images SET caption=?, sort_order=? WHERE id=?')->execute([$caption, $sort, $imgId]);
                $keepIds[] = $imgId;
            } else {
                gf_pdo()->prepare('INSERT INTO gym_images (gym_id, image_url, caption, sort_order) VALUES (?,?,?,?)')
                    ->execute([$gymId, $url, $caption, $sort]);
                $keepIds[] = (int) gf_pdo()->lastInsertId();
            }
            unset($i);
        }
        $old = gf_pdo()->prepare('SELECT id, image_url FROM gym_images WHERE gym_id=?');
        $old->execute([$gymId]);
        foreach ($old->fetchAll() as $row) {
            if (!in_array((int) $row['id'], $keepIds, true)) {
                $deleteFiles[] = $row['image_url'];
                gf_pdo()->prepare('DELETE FROM gym_images WHERE id=?')->execute([(int) $row['id']]);
            }
        }
    }

    return $deleteFiles;
}

/**
 * Lưu file upload đã không còn được reference.
 */
function gf_delete_unreferenced_files(array $urls): void
{
    $root = $GLOBALS['GF_ROOT'] ?? dirname(__DIR__, 2);
    foreach (array_unique(array_filter($urls)) as $url) {
        if (!gf_uploads_url_ok((string) $url)) {
            continue;
        }
        $path = $root . str_replace('/', DIRECTORY_SEPARATOR, (string) $url);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
