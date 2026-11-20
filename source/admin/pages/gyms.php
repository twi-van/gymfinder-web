<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
$adminUser = gf_require_admin();
$error = '';

/**
 * Build + validate gym admin form (mọi field hiện trên UI bắt buộc trừ "Nổi bật").
 * Whitelist only — tránh gf_require_known_body reject field form thừa.
 * @return array{0: array<string,mixed>, 1: string}
 */
$gfBuildGymAdminPayload = static function (array $post, array $files, array $adminUser, bool $isCreate): array {
    $errLocal = '';
    $name = trim((string) ($post['name'] ?? ''));
    $address = trim((string) ($post['address'] ?? ''));
    $phone = preg_replace('/[\s.\-]+/', '', trim((string) ($post['phone'] ?? ''))) ?? '';
    $hours = trim((string) ($post['opening_hours'] ?? ''));
    $description = trim((string) ($post['description'] ?? ''));
    $districtRaw = $post['district_id'] ?? '';
    $priceMinRaw = $post['price_min'] ?? '';
    $priceMaxRaw = $post['price_max'] ?? '';
    $catIds = array_values(array_unique(array_filter(array_map('intval', $post['category_ids'] ?? []), static fn ($id) => $id > 0)));
    $amenityIds = array_values(array_unique(array_filter(array_map('intval', $post['amenity_ids'] ?? []), static fn ($id) => $id > 0)));
    $featureIds = array_values(array_unique(array_filter(array_map('intval', $post['feature_ids'] ?? []), static fn ($id) => $id > 0)));

    if ($name === '') {
        $errLocal = 'Vui lòng nhập tên phòng tập.';
    } elseif ($phone === '') {
        $errLocal = 'Vui lòng nhập số điện thoại liên hệ.';
    } elseif (!gf_phone_ok($phone)) {
        $errLocal = 'Số điện thoại không hợp lệ (9–14 chữ số, có thể bắt đầu bằng +).';
    } elseif ($address === '') {
        $errLocal = 'Vui lòng nhập địa chỉ.';
    } elseif (!gf_integer_like($districtRaw) || (int) $districtRaw < 1 || !gf_exists_id('districts', (int) $districtRaw)) {
        $errLocal = 'Vui lòng chọn khu vực.';
    } elseif ($priceMinRaw === '' || !gf_integer_like($priceMinRaw) || (int) $priceMinRaw < 0) {
        $errLocal = 'Vui lòng nhập giá từ (số nguyên ≥ 0).';
    } elseif ($priceMaxRaw === '' || !gf_integer_like($priceMaxRaw) || (int) $priceMaxRaw < 0) {
        $errLocal = 'Vui lòng nhập giá đến (số nguyên ≥ 0).';
    } elseif ((int) $priceMaxRaw < (int) $priceMinRaw) {
        $errLocal = 'Giá đến phải lớn hơn hoặc bằng giá từ.';
    } elseif ($hours === '') {
        $errLocal = 'Vui lòng nhập giờ mở cửa.';
    } elseif (mb_strlen($hours) > 120) {
        $errLocal = 'Giờ mở cửa tối đa 120 ký tự.';
    } elseif ($description === '') {
        $errLocal = 'Vui lòng nhập mô tả.';
    } elseif ($catIds === []) {
        $errLocal = 'Vui lòng chọn ít nhất một danh mục.';
    } elseif ($amenityIds === []) {
        $errLocal = 'Vui lòng chọn ít nhất một tiện ích.';
    } elseif ($featureIds === []) {
        $errLocal = 'Vui lòng chọn ít nhất một đặc điểm.';
    }

    $data = [
        'name'            => $name,
        'phone'           => $phone,
        'address'         => $address,
        'district_id'     => (int) $districtRaw,
        'price_min'       => (int) $priceMinRaw,
        'price_max'       => (int) $priceMaxRaw,
        'opening_hours'   => $hours,
        'description'     => $description,
        'is_featured'     => !empty($post['is_featured']) ? 1 : 0,
        'category_ids'    => $catIds,
        'amenity_ids'     => $amenityIds,
        'feature_ids'     => $featureIds,
    ];
    if ($isCreate) {
        $data['status'] = 'active';
    }

    if (!empty($post['remove_cover'])) {
        $data['cover_image_url'] = null;
    } elseif (array_key_exists('cover_image_url', $post)) {
        $data['cover_image_url'] = trim((string) $post['cover_image_url']) ?: null;
    }
    if (isset($files['cover_file']) && ($files['cover_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $up = gf_save_upload($files['cover_file'], 'gym_cover', $adminUser);
        if (!$up['ok']) {
            $errLocal = $up['error'];
        } else {
            $data['cover_image_url'] = $up['url'];
        }
    }

    $images = null;
    if (array_key_exists('images_json', $post)) {
        $decoded = json_decode((string) $post['images_json'], true);
        if (!is_array($decoded)) {
            $errLocal = $errLocal !== '' ? $errLocal : 'Danh sách ảnh thư viện không hợp lệ. Vui lòng tải lại form và thử lại.';
        } else {
            $images = $decoded;
            $data['images'] = $images;
        }
    } else {
        $errLocal = $errLocal !== '' ? $errLocal : 'Thiếu dữ liệu thư viện ảnh. Vui lòng tải lại form và thử lại.';
    }

    if (!$errLocal && empty($data['cover_image_url'])) {
        $errLocal = 'Vui lòng chọn một ảnh bìa cho phòng tập.';
    }
    if (!$errLocal && is_array($images)) {
        $n = count($images);
        if ($n < 3 || $n > 6) {
            $errLocal = 'Thư viện cần từ 3 đến 6 ảnh.';
        }
    }

    return [$data, $errLocal];
};

$gfFormatGymSaveError = static function (array $result, string $fallback): string {
    $msg = (string) ($result['error'] ?? $fallback);
    if (!empty($result['details']) && is_array($result['details'])) {
        $parts = [];
        foreach ($result['details'] as $field => $why) {
            $parts[] = is_string($field) && $field !== ''
                ? $field . ': ' . (string) $why
                : (string) $why;
        }
        if ($parts !== []) {
            $msg .= ' (' . implode('; ', $parts) . ')';
        }
    }
    return $msg;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    gf_require_csrf();
    if (!empty($_POST['id']) && isset($_POST['name'])) {
        $id = (int) $_POST['id'];
        [$data, $error] = $gfBuildGymAdminPayload($_POST, $_FILES, $adminUser, false);
        if (!$error) {
            $result = gf_admin_save_gym($data, $id);
            if (!empty($result['ok'])) {
                gf_flash_redirect(gf_url('admin/pages/gyms.php'), 'Đã cập nhật phòng tập thành công.');
            }
            $error = $gfFormatGymSaveError($result, 'Không thể cập nhật phòng tập.');
        }
    } elseif (!empty($_POST['id']) && ($_POST['action'] ?? '') === 'delete' && !isset($_POST['name'])) {
        $result = gf_admin_delete_gym((int) $_POST['id']);
        if (!empty($result['ok'])) {
            gf_flash_redirect(gf_url('admin/pages/gyms.php'), 'Đã xóa phòng tập thành công.');
        }
        gf_flash_redirect(
            gf_url('admin/pages/gyms.php'),
            (string) ($result['error'] ?? 'Không thể xóa phòng tập.'),
            'danger'
        );
    } elseif (!empty($_POST['id']) && isset($_POST['status']) && !isset($_POST['name'])) {
        // Toggle ẩn/hiện only — must not catch create/edit form (has empty id + optional status).
        $newStatus = $_POST['status'] === 'hidden' ? 'hidden' : 'active';
        gf_set_entity_status('gyms', (int) $_POST['id'], $newStatus);
        $msg = $newStatus === 'hidden'
            ? 'Đã tạm ẩn phòng tập thành công. Phòng tập sẽ không hiển thị trên các trang công khai.'
            : 'Đã kích hoạt lại phòng tập thành công. Phòng tập đã sẵn sàng hiển thị công khai.';
        gf_flash_redirect(gf_url('admin/pages/gyms.php'), $msg);
    } elseif (isset($_POST['name'])) {
        [$data, $error] = $gfBuildGymAdminPayload($_POST, $_FILES, $adminUser, true);
        if (!$error) {
            $result = gf_admin_save_gym($data, null);
            if (!empty($result['ok'])) {
                gf_flash_redirect(gf_url('admin/pages/gyms.php'), 'Đã thêm phòng tập thành công.');
            }
            $error = $gfFormatGymSaveError($result, 'Không thể lưu phòng tập.');
        }
    }
}
$districts = gf_list_districts();
$districtNames = [];
foreach ($districts as $district) { $districtNames[(int)$district['id']] = $district['name']; }
$gymPage = max(1, (int)($_GET['page'] ?? 1));
$gymLimit = 8;
$gymStatusFilter = (string) ($_GET['status'] ?? '');
$gymFilters = [
    'limit'       => $gymLimit,
    'page'        => $gymPage,
    // newest first so vừa thêm phòng tập hiện ngay trang 1
    'sort'        => 'newest',
    'q'           => $_GET['q'] ?? '',
    'district_id' => $_GET['district_id'] ?? '',
    'status'      => in_array($gymStatusFilter, ['active', 'hidden'], true) ? $gymStatusFilter : '',
    'is_featured' => $gymStatusFilter === 'featured' ? '1' : ($gymStatusFilter === 'not_featured' ? '0' : ''),
];
$gymResult = gf_admin_gyms_paged($gymFilters);
if ($gymPage > 1 && empty($gymResult['items']) && (int)$gymResult['total'] > 0) {
    $gymPage = max(1, (int) ceil((int)$gymResult['total'] / $gymLimit));
    $gymFilters['page'] = $gymPage;
    $gymResult = gf_admin_gyms_paged($gymFilters);
}
$rows = $gymResult['items'];
$gymCategoryNames = $gymCategoryIds = $gymAmenityIds = $gymFeatureIds = $gymFeatureNames = $gymDescriptions = $gymGalleries = [];
$gymTrainerCounts = [];
if ($rows !== []) {
    $gymIds = array_map(static fn($g) => (int) $g['id'], $rows);
    $placeholders = implode(',', array_fill(0, count($gymIds), '?'));
    $countSt = gf_pdo()->prepare("SELECT gym_id, COUNT(*) AS c FROM gym_trainers WHERE gym_id IN ($placeholders) GROUP BY gym_id");
    $countSt->execute($gymIds);
    foreach ($countSt->fetchAll() as $countRow) {
        $gymTrainerCounts[(int) $countRow['gym_id']] = (int) $countRow['c'];
    }
}
$categoryQuery = gf_pdo()->prepare('SELECT c.id,c.name FROM gym_categories gc JOIN categories c ON c.id=gc.category_id WHERE gc.gym_id=? ORDER BY c.name');
$amenityQuery = gf_pdo()->prepare('SELECT amenity_id FROM gym_amenities WHERE gym_id=?');
$featureQuery = gf_pdo()->prepare('SELECT f.id, f.name FROM gym_features gf JOIN features f ON f.id=gf.feature_id WHERE gf.gym_id=? ORDER BY f.name');
$descriptionQuery = gf_pdo()->prepare('SELECT description FROM gyms WHERE id=?');
$galleryQuery = gf_pdo()->prepare('SELECT id, image_url, caption, sort_order FROM gym_images WHERE gym_id=? ORDER BY sort_order ASC');
foreach ($rows as $gymRow) {
    $categoryQuery->execute([(int)$gymRow['id']]);
    $gymCategories = $categoryQuery->fetchAll();
    $gymCategoryNames[(int)$gymRow['id']] = array_column($gymCategories, 'name');
    $gymCategoryIds[(int)$gymRow['id']] = array_map('intval', array_column($gymCategories, 'id'));
    $amenityQuery->execute([(int)$gymRow['id']]);
    $gymAmenityIds[(int)$gymRow['id']] = array_map('intval', array_column($amenityQuery->fetchAll(), 'amenity_id'));
    try {
        $featureQuery->execute([(int)$gymRow['id']]);
        $gymFeatures = $featureQuery->fetchAll();
        $gymFeatureIds[(int)$gymRow['id']] = array_map('intval', array_column($gymFeatures, 'id'));
        $gymFeatureNames[(int)$gymRow['id']] = array_column($gymFeatures, 'name');
    } catch (Throwable $e) {
        $gymFeatureIds[(int)$gymRow['id']] = [];
        $gymFeatureNames[(int)$gymRow['id']] = [];
    }
    $descriptionQuery->execute([(int)$gymRow['id']]);
    $gymDescriptions[(int)$gymRow['id']] = (string)($descriptionQuery->fetchColumn() ?: '');
    $galleryQuery->execute([(int)$gymRow['id']]);
    $gymGalleries[(int)$gymRow['id']] = $galleryQuery->fetchAll();
}

$categories = gf_list_categories(false);
$amenities = gf_list_amenities();
$features = gf_list_features();
$reopenGymEditor = ($error !== '');
$pageTitle = 'Phòng tập - Admin GYMFINDER';
$extraCss = ['admin/assets/admin.css'];
$adminActive = 'gyms';
require dirname(__DIR__, 2) . '/includes/layout_head.php';
require dirname(__DIR__, 2) . '/includes/admin_layout.php';
require_once dirname(__DIR__) . '/components/table.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1 text-dark" style="font-size: 24px; letter-spacing: -0.3px;"><?= gf_h(gf_t('Quản lý phòng tập')) ?></h2>
        <p class="text-secondary mb-0 small"><?= gf_h(gf_t('Thêm, chỉnh sửa, tạm ẩn; xóa khi phòng tập chưa gắn HLV; theo dõi thông tin phòng tập trên hệ thống')) ?></p>
    </div>
    <button class="btn btn-primary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2 fw-semibold shadow-sm" onclick="gymModalCreate()" style="height: 42px;">
        <i class="fa-solid fa-plus"></i> <?= gf_h(gf_t('Thêm mới')) ?>
    </button>
</div>

<form method="get" class="admin-card admin-filter-bar p-3 mb-4 shadow-sm border">
    <div class="admin-filter-bar__grid admin-filter-bar__grid--4">
        <div class="admin-filter-bar__search position-relative">
            <i class="fa-solid fa-magnifying-glass text-secondary position-absolute" style="left: 14px; top: 50%; transform: translateY(-50%); font-size: 14px; pointer-events: none;"></i>
            <input class="form-control admin-input" name="q" value="<?= gf_h($_GET['q'] ?? '') ?>" placeholder="<?= gf_h(gf_t('Tìm kiếm phòng tập...')) ?>" style="padding-left: 38px;" autocomplete="off">
        </div>
        <div class="admin-filter-bar__field">
            <select class="form-select admin-input" name="district_id" aria-label="<?= gf_h(gf_t('Lọc theo khu vực')) ?>">
                <option value=""><?= gf_h(gf_t('Tất cả khu vực')) ?></option>
                <?php foreach ($districts as $d): ?>
                    <option value="<?= (int)$d['id'] ?>" <?= (string)($_GET['district_id'] ?? '') === (string)$d['id'] ? 'selected' : '' ?>><?= gf_h(gf_place_name((string) $d['name'])) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-filter-bar__field">
            <select class="form-select admin-input" name="status" aria-label="<?= gf_h(gf_t('Lọc theo trạng thái')) ?>">
                <option value=""><?= gf_h(gf_t('Tất cả trạng thái')) ?></option>
                <option value="active" <?= $gymStatusFilter === 'active' ? 'selected' : '' ?>><?= gf_h(gf_t('Hoạt động')) ?></option>
                <option value="hidden" <?= $gymStatusFilter === 'hidden' ? 'selected' : '' ?>><?= gf_h(gf_t('Tạm ẩn')) ?></option>
                <option value="featured" <?= $gymStatusFilter === 'featured' ? 'selected' : '' ?>><?= gf_h(gf_t('Nổi bật')) ?></option>
                <option value="not_featured" <?= $gymStatusFilter === 'not_featured' ? 'selected' : '' ?>><?= gf_h(gf_t('Không nổi bật')) ?></option>
            </select>
        </div>
        <div class="admin-filter-bar__submit">
            <button type="submit" class="btn btn-filter d-inline-flex align-items-center justify-content-center gap-2 fw-medium">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><?= gf_h(gf_t('Tra cứu')) ?>
            </button>
        </div>
    </div>
</form>

<?php
$gymPaginationHtml = gf_render_admin_pagination(
    $gymPage,
    $gymResult['limit'],
    (int)$gymResult['total'],
    array_diff_key($_GET, ['page' => ''])
);

gf_admin_table_open([
    'id'        => 'gymsTable',
    'class'     => 'admin-gyms-table',
    'min_width' => '1140px',
    'columns'   => [
        ['label' => 'ID', 'width' => '55px', 'align' => 'center'],
        ['label' => 'PHÒNG TẬP', 'width' => '330px'],
        ['label' => 'KHU VỰC', 'width' => '110px'],
        ['label' => 'DANH MỤC', 'width' => '150px'],
        ['label' => 'GIÁ', 'width' => '195px'],
        ['label' => 'ĐÁNH GIÁ', 'width' => '95px'],
        ['label' => 'TRẠNG THÁI', 'width' => '115px'],
        ['label' => 'THAO TÁC', 'width' => '170px', 'align' => 'right', 'class' => 'pe-3'],
    ]
]);
?>
            <?php foreach ($rows as $r): ?>
                <?php
                $ptLinked = (int) ($gymTrainerCounts[(int) $r['id']] ?? 0);
                $deleteBlocked = $ptLinked > 0
                    ? str_replace(
                        '%d',
                        (string) $ptLinked,
                        gf_lang() === 'en'
                            ? 'Cannot delete: %d trainer(s) are still linked to this gym. Unlink them first, or hide the gym instead.'
                            : 'Không thể xóa: còn %d HLV đang liên kết với phòng tập này. Hãy gỡ HLV khỏi phòng tập hoặc dùng Tạm ẩn.'
                    )
                    : '';
                ?>
                <tr>
                    <td class="text-center fw-medium text-secondary"><?= (int)$r['id'] ?></td>
                    <td class="gym-info-cell">
                        <div class="d-flex align-items-center gap-3">
                            <div class="position-relative flex-shrink-0" style="width:68px;height:68px;">
                                <img src="<?= gf_h(gf_cover_image($r['cover_image_url'] ?? null, (int)$r['id'])) ?>" 
                                     width="68" height="68" 
                                     style="width:68px;height:68px;object-fit:cover;aspect-ratio:1/1;" 
                                     class="rounded-3 shadow-sm border d-block" 
                                     alt="<?= gf_h($r['name']) ?>"
                                     onerror="this.onerror=null;this.src='<?= gf_h(gf_cover_image(null, (int)$r['id'])) ?>';">
                            </div>
                            <div class="min-w-0 flex-grow-1">
                                <span class="gym-name"><?= gf_h($r['name']) ?></span>
                                <div class="gym-address"><i class="fa-solid fa-location-dot me-1 text-muted" style="font-size: 11px;"></i><?= gf_h($r['address']) ?></div>
                                <div class="small text-muted mt-1" style="font-size: 11px;">
                                    <i class="fa-solid fa-phone me-1 text-primary" style="font-size: 10px;"></i><?= !empty($r['phone']) ? gf_h($r['phone']) : '<span class="text-secondary fst-italic">' . gf_h(gf_t('Chưa có SĐT')) . '</span>' ?>
                                </div>
                                <?php if (!empty($gymGalleries[(int)$r['id']])): ?>
                                    <div class="mt-1">
                                        <span class="badge bg-light text-secondary border px-2 py-0" style="font-size: 10.5px; font-weight: 500;">
                                            <i class="fa-solid fa-images me-1 text-primary"></i><?= count($gymGalleries[(int)$r['id']]) ?> <?= gf_h(gf_t('ảnh')) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="fw-medium text-secondary"><?= gf_h(gf_place_name((string) ($districtNames[(int)$r['district_id']] ?? '—'))) ?></span>
                    </td>
                    <td>
                        <div class="d-flex flex-wrap gap-1">
                            <?php 
                            $cNames = $gymCategoryNames[(int)$r['id']] ?? [];
                            if (empty($cNames)): ?>
                                <span class="text-muted small">—</span>
                            <?php else:
                                foreach ($cNames as $categoryName): ?>
                                    <span class="admin-cat-badge <?= gf_admin_cat_badge($categoryName) ?>"><?= gf_h($categoryName) ?></span>
                            <?php endforeach; endif; ?>
                        </div>
                    </td>
                    <td>
                        <span class="gym-price"><?= gf_h(gf_format_price((int)$r['price_min'], (int)$r['price_max'])) ?></span>
                    </td>
                    <td>
                        <div class="gym-rating">
                            <i class="fa-solid fa-star text-warning" style="font-size: 13px;"></i>
                            <span class="fw-semibold text-dark"><?= number_format((float)($r['avg_rating'] ?? 0), 1) ?></span>
                        </div>
                    </td>
                    <td>
                        <span class="admin-badge <?= $r['status'] === 'active' ? 'badge-success' : 'badge-neutral' ?>">
                            <?= gf_h(gf_t($r['status'] === 'active' ? 'Hoạt động' : 'Tạm ẩn')) ?>
                        </span>
                    </td>
                    <td class="text-end gym-actions pe-3 text-nowrap">
                        <div class="d-inline-flex align-items-center justify-content-end gap-2">
                            <button type="button" class="btn btn-action btn-action-view" data-bs-toggle="modal" data-bs-target="#gymView<?= (int)$r['id'] ?>" title="<?= gf_h(gf_t('Xem chi tiết')) ?>" aria-label="<?= gf_h(gf_t('Xem chi tiết')) ?>"><i class="fa-solid fa-eye"></i></button>
                            <button type="button" class="btn btn-action btn-action-edit" data-id="<?= (int)$r['id'] ?>" data-name="<?= gf_h($r['name']) ?>" data-phone="<?= gf_h($r['phone'] ?? '') ?>" data-address="<?= gf_h($r['address']) ?>" data-district="<?= (int)$r['district_id'] ?>" data-min="<?= (int)$r['price_min'] ?>" data-max="<?= (int)$r['price_max'] ?>" data-hours="<?= gf_h($r['opening_hours'] ?? '') ?>" data-featured="<?= (int)($r['is_featured'] ?? 0) ?>" data-description="<?= gf_h($gymDescriptions[(int)$r['id']] ?? '') ?>" data-cover="<?= gf_h($r['cover_image_url'] ?? '') ?>" data-cover-display="<?= gf_h(gf_cover_image($r['cover_image_url'] ?? null, (int)$r['id'])) ?>" data-gallery='<?= htmlspecialchars(json_encode($gymGalleries[(int)$r['id']] ?? []), ENT_QUOTES, 'UTF-8') ?>' data-categories="<?= gf_h(implode(',', $gymCategoryIds[(int)$r['id']] ?? [])) ?>" data-amenities="<?= gf_h(implode(',', $gymAmenityIds[(int)$r['id']] ?? [])) ?>" data-features="<?= gf_h(implode(',', $gymFeatureIds[(int)$r['id']] ?? [])) ?>" onclick="gymModalEdit(this)" title="<?= gf_h(gf_t('Chỉnh sửa')) ?>" aria-label="<?= gf_h(gf_t('Chỉnh sửa')) ?>"><i class="fa-solid fa-pen"></i></button>
                            <form method="post" class="d-inline m-0 p-0" onsubmit="return handleGymStatusSubmit(this);">
                                <?= gf_csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <input type="hidden" name="status" value="<?= $r['status'] === 'active' ? 'hidden' : 'active' ?>">
                                <?php if ($r['status'] === 'active'): ?>
                                    <button type="submit" class="btn btn-action btn-action-toggle" title="<?= gf_h(gf_t('Tạm ẩn phòng tập')) ?>" aria-label="<?= gf_h(gf_t('Tạm ẩn phòng tập')) ?>"><i class="fa-solid fa-eye-slash"></i></button>
                                <?php else: ?>
                                    <button type="submit" class="btn btn-action btn-action-activate" title="<?= gf_h(gf_t('Kích hoạt lại phòng tập')) ?>" aria-label="<?= gf_h(gf_t('Kích hoạt lại phòng tập')) ?>"><i class="fa-solid fa-rotate-left"></i></button>
                                <?php endif; ?>
                            </form>
                            <?php if ($deleteBlocked !== ''): ?>
                                <button type="button" class="btn btn-action btn-action-delete is-blocked" title="<?= gf_h($deleteBlocked) ?>" aria-label="<?= gf_h($deleteBlocked) ?>" data-delete-blocked="<?= gf_h($deleteBlocked) ?>"><i class="fa-solid fa-trash"></i></button>
                            <?php else: ?>
                                <form method="post" class="d-inline m-0 p-0 js-confirm-submit"
                                      data-confirm-title="<?= gf_h(gf_t('Xóa phòng tập?')) ?>"
                                      data-confirm-message="<?= gf_h(gf_t('Xóa vĩnh viễn phòng tập này? Thao tác không hoàn tác.')) ?>"
                                      data-confirm-label="<?= gf_h(gf_t('Xóa')) ?>"
                                      data-confirm-danger="1">
                                    <?= gf_csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    <button type="submit" class="btn btn-action btn-action-delete" title="<?= gf_h(gf_t('Xóa phòng tập')) ?>" aria-label="<?= gf_h(gf_t('Xóa phòng tập')) ?>"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
<?php
gf_admin_table_close([
    'empty'         => empty($rows),
    'empty_message' => 'Không tìm thấy phòng tập phù hợp.',
    'colspan'       => 8,
    'footer_html'   => $gymPaginationHtml
]);
?>

<!-- Modals Chi tiết Phòng tập -->
<?php foreach ($rows as $r): ?>
<div class="modal fade" id="gymView<?= (int)$r['id'] ?>" tabindex="-1" aria-labelledby="gymViewTitle<?= (int)$r['id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 shadow overflow-hidden">
            <div class="modal-header border-bottom px-4 py-3 bg-white sticky-top">
                <h5 class="modal-title fw-bold text-dark" id="gymViewTitle<?= (int)$r['id'] ?>"><i class="fa-solid fa-dumbbell text-primary me-2"></i><?= gf_h(gf_t('Chi tiết phòng tập')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= gf_h(gf_t('Đóng')) ?>"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-md-5">
                        <!-- Ảnh Cover -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small fw-bold text-dark"><i class="fa-solid fa-image me-1 text-primary"></i><?= gf_h(gf_t('Ảnh bìa')) ?></span>
                                <span class="admin-badge badge-success" style="font-size: 10px;"><?= gf_h(gf_t('Ảnh đại diện')) ?></span>
                            </div>
                            <div class="position-relative overflow-hidden rounded-3 shadow-sm border">
                                <img src="<?= gf_h(gf_cover_image($r['cover_image_url'] ?? null, (int)$r['id'])) ?>" 
                                     class="w-100 object-fit-cover d-block" 
                                     style="height:190px;" 
                                     alt="<?= gf_h(gf_t('Ảnh bìa')) ?>"
                                     onerror="this.onerror=null;this.src='<?= gf_h(gf_cover_image(null, (int)$r['id'])) ?>';">
                            </div>
                        </div>
                        
                        <!-- Bộ sưu tập ảnh (Gallery) -->
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small fw-bold text-dark"><i class="fa-solid fa-images me-1 text-primary"></i><?= gf_h(gf_t('Bộ sưu tập ảnh')) ?></span>
                                <span class="admin-badge badge-neutral" style="font-size: 10px;"><?= count($gymGalleries[(int)$r['id']] ?? []) ?> <?= gf_h(gf_t('ảnh')) ?></span>
                            </div>
                            <?php if (empty($gymGalleries[(int)$r['id']])): ?>
                                <div class="text-center py-3 bg-light rounded-3 text-muted small border">
                                    <i class="fa-regular fa-image me-1"></i><?= gf_h(gf_t('Chưa có ảnh trong thư viện')) ?>
                                </div>
                            <?php else: ?>
                                <div class="row g-2">
                                    <?php foreach ($gymGalleries[(int)$r['id']] ?? [] as $idx => $gimg): ?>
                                        <div class="col-4">
                                            <div class="position-relative overflow-hidden rounded-2 shadow-sm border">
                                                <img src="<?= gf_h(gf_gym_image_url($gimg['image_url'])) ?>" 
                                                     class="w-100 object-fit-cover d-block" 
                                                     style="height:70px;" 
                                                     title="<?= gf_h($gimg['caption'] ?: 'Ảnh #' . ($idx+1)) ?>" 
                                                     alt=""
                                                     onerror="this.onerror=null;this.src='<?= gf_h(gf_cover_image(null, (int)$r['id'])) ?>';">
                                                <span class="position-absolute bottom-0 start-0 m-1 badge bg-dark bg-opacity-75 text-white" style="font-size:9px;padding:2px 5px;">#<?= $idx + 1 ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-7">
                        <h4 class="fw-bold mb-1 text-dark"><?= gf_h($r['name']) ?></h4>
                        <p class="text-secondary mb-3"><i class="fa-solid fa-location-dot me-1 text-muted"></i><?= gf_h((string) ($r['address'] !== '' ? $r['address'] : gf_place_name((string) ($districtNames[(int)$r['district_id']] ?? '')))) ?></p>
                        
                        <div class="p-3 bg-light rounded-3 mb-3 border">
                            <div class="row g-2 small">
                                <div class="col-6"><strong><?= gf_h(gf_t('Khu vực:')) ?></strong> <?= gf_h(gf_place_name((string) ($districtNames[(int)$r['district_id']] ?? '—'))) ?></div>
                                <div class="col-6"><strong><?= gf_h(gf_t('Số điện thoại:')) ?></strong> <?= !empty($r['phone']) ? gf_h($r['phone']) : '<span class="text-muted fst-italic">' . gf_h(gf_t('Chưa cập nhật')) . '</span>' ?></div>
                                <div class="col-6"><strong><?= gf_h(gf_t('Đánh giá:')) ?></strong> <i class="fa-solid fa-star text-warning"></i> <?= number_format((float)($r['avg_rating'] ?? 0), 1) ?> (<?= gf_h(gf_t((int) $r['review_count'] . ' đánh giá')) ?>)</div>
                                <div class="col-6"><strong><?= gf_h(gf_t('Khoảng giá:')) ?></strong> <span class="text-primary fw-bold"><?= gf_h(gf_format_price((int)$r['price_min'], (int)$r['price_max'])) ?></span></div>
                                <div class="col-6"><strong><?= gf_h(gf_t('Trạng thái:')) ?></strong> <span class="admin-badge <?= $r['status'] === 'active' ? 'badge-success' : 'badge-neutral' ?>"><?= gf_h(gf_t($r['status'] === 'active' ? 'Hoạt động' : 'Tạm ẩn')) ?></span></div>
                            </div>
                        </div>
                        
                        <?php if (!empty($gymDescriptions[(int)$r['id']])): ?>
                            <div class="mb-3">
                                <div class="small fw-bold text-dark mb-1"><?= gf_h(gf_t('Mô tả:')) ?></div>
                                <p class="small text-muted mb-0" style="line-height:1.5;"><?= nl2br(gf_h($gymDescriptions[(int)$r['id']])) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <div class="small fw-bold text-dark mb-1"><?= gf_h(gf_t('Danh mục:')) ?></div>
                            <div class="d-flex flex-wrap gap-1">
                                <?php foreach ($gymCategoryNames[(int)$r['id']] ?? [] as $categoryName): ?>
                                    <span class="admin-cat-badge <?= gf_admin_cat_badge($categoryName) ?>"><?= gf_h($categoryName) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php if (!empty($gymFeatureNames[(int)$r['id']])): ?>
                        <div>
                            <div class="small fw-bold text-dark mb-1"><?= gf_h(gf_t('Đặc điểm:')) ?></div>
                            <div class="d-flex flex-wrap gap-1">
                                <?php foreach ($gymFeatureNames[(int)$r['id']] as $featureName): ?>
                                    <span class="admin-badge badge-neutral"><?= gf_h($featureName) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3 bg-white sticky-bottom">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal"><?= gf_h(gf_t('Đóng')) ?></button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
<div class="modal fade" id="gymEditor" tabindex="-1" aria-labelledby="gym-editor-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form method="post" id="gymForm" class="modal-content border-0 rounded-4 shadow overflow-hidden js-form-validate" data-inline-errors="1" novalidate enctype="multipart/form-data" onsubmit="return handleGymFormSubmit(event)">
            <?= gf_csrf_field() ?>
            <input type="hidden" name="id" id="gym-id">
            <input type="hidden" name="cover_image_url" id="gym-cover-url">
            <input type="hidden" name="remove_cover" id="gym-remove-cover" value="0">
            <input type="hidden" name="images_json" id="gym-images-json" value="[]">
            <div class="modal-header border-bottom px-4 py-3 bg-white sticky-top">
                <h5 class="modal-title fw-bold text-dark" id="gym-editor-title"><i class="fa-solid fa-dumbbell text-primary me-2"></i><?= gf_h(gf_t('Thêm phòng tập')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= gf_h(gf_t('Đóng')) ?>"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <div id="gym-error-alert" class="alert alert-danger d-none"></div>
                <div class="row g-3">
                    <div class="col-md-6" data-field><label class="form-label"><?= gf_h(gf_t('Tên phòng tập *')) ?></label><input class="form-control admin-input" name="name" id="gym-name" required data-required-msg="<?= gf_h(gf_t('Vui lòng nhập tên phòng tập.')) ?>"></div>
                    <div class="col-md-6" data-field><label class="form-label"><?= gf_h(gf_t('Số điện thoại liên hệ *')) ?></label><input class="form-control admin-input" name="phone" id="gym-phone" required placeholder="<?= gf_h(gf_t('VD: +84281234567 hoặc 0901234567')) ?>" pattern="^\+?[0-9\s.\-]{9,18}$" data-required-msg="<?= gf_h(gf_t('Vui lòng nhập số điện thoại liên hệ.')) ?>" data-pattern-msg="<?= gf_h(gf_t('Số điện thoại không hợp lệ (9–14 chữ số).')) ?>"></div>
                    <div class="col-md-6" data-field><label class="form-label"><?= gf_h(gf_t('Địa chỉ *')) ?></label><input class="form-control admin-input" name="address" id="gym-address" required data-required-msg="<?= gf_h(gf_t('Vui lòng nhập địa chỉ.')) ?>"></div>
                    <div class="col-md-6" data-field><label class="form-label"><?= gf_h(gf_t('Khu vực *')) ?></label><select class="form-select admin-input" name="district_id" id="gym-district" required data-required-msg="<?= gf_h(gf_t('Vui lòng chọn khu vực.')) ?>"><?php foreach ($districts as $d): ?><option value="<?= (int)$d['id'] ?>"><?= gf_h(gf_place_name((string) $d['name'])) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-6" data-field><label class="form-label"><?= gf_h(gf_t('Giá từ *')) ?></label><input class="form-control admin-input" type="number" min="0" name="price_min" id="gym-min" value="200000" required data-required-msg="<?= gf_h(gf_t('Vui lòng nhập giá từ.')) ?>"></div>
                    <div class="col-md-6" data-field><label class="form-label"><?= gf_h(gf_t('Giá đến *')) ?></label><input class="form-control admin-input" type="number" min="0" name="price_max" id="gym-max" value="800000" required data-required-msg="<?= gf_h(gf_t('Vui lòng nhập giá đến.')) ?>"></div>
                    <div class="col-md-6" data-field>
                        <label class="form-label"><?= gf_h(gf_t('Giờ mở cửa *')) ?></label>
                        <input class="form-control admin-input" name="opening_hours" id="gym-hours" maxlength="120" required data-required-msg="<?= gf_h(gf_t('Vui lòng nhập giờ mở cửa.')) ?>" placeholder="<?= gf_h(gf_t('VD: 06:00–22:00 hoặc 24/7')) ?>">
                        <div class="form-text"><?= gf_h(gf_t('Ghi rõ giờ; dùng')) ?> <code>24/7</code> <?= gf_h(gf_t('nếu mở cả ngày (lọc tìm kiếm nhận dạng chuỗi này).')) ?></div>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input type="hidden" name="is_featured" value="0">
                            <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="gym-featured">
                            <label class="form-check-label fw-semibold" for="gym-featured"><?= gf_h(gf_t('Nổi bật trên trang chủ')) ?></label>
                        </div>
                    </div>
                    <div class="col-12" data-field><label class="form-label"><?= gf_h(gf_t('Mô tả *')) ?></label><textarea class="form-control admin-input" name="description" id="gym-description" rows="3" required data-required-msg="<?= gf_h(gf_t('Vui lòng nhập mô tả.')) ?>"></textarea></div>
                    <div class="col-md-4" data-field id="gymCategoryField"><label class="form-label fw-semibold"><?= gf_h(gf_t('Danh mục *')) ?></label><div id="gymCategoryList"><?php foreach ($categories as $c): ?><label class="form-check"><input class="form-check-input gym-category" type="checkbox" name="category_ids[]" value="<?= (int)$c['id'] ?>"><span class="form-check-label"><?= gf_h($c['name']) ?></span></label><?php endforeach; ?></div></div>
                    <div class="col-md-4" data-field id="gymAmenityField"><label class="form-label fw-semibold"><?= gf_h(gf_t('Tiện ích *')) ?></label><div id="gymAmenityList"><?php foreach ($amenities as $a): ?><label class="form-check"><input class="form-check-input gym-amenity" type="checkbox" name="amenity_ids[]" value="<?= (int)$a['id'] ?>"><span class="form-check-label"><?= gf_h($a['name']) ?></span></label><?php endforeach; ?></div></div>
                    <div class="col-md-4" data-field id="gymFeatureField"><label class="form-label fw-semibold"><?= gf_h(gf_t('Đặc điểm *')) ?></label><div id="gymFeatureList"><?php foreach ($features as $ft): ?><label class="form-check"><input class="form-check-input gym-feature" type="checkbox" name="feature_ids[]" value="<?= (int)$ft['id'] ?>"><span class="form-check-label"><?= gf_h($ft['name']) ?></span></label><?php endforeach; ?></div></div>

                    <!-- UPLOAD COVER SECTION -->
                    <div class="col-12 pt-3 border-top" data-field id="gymCoverField">
                        <label class="form-label fw-bold d-block mb-2"><?= gf_h(gf_t('Ảnh bìa phòng tập *')) ?></label>
                        <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 border" id="gymCoverBox">
                            <img id="gym-cover-preview" src="<?= gf_h(gf_url('assets/images/gym-placeholder.svg')) ?>" class="rounded-3 shadow-sm object-fit-cover" style="width:110px;height:80px;flex:0 0 110px;background:#eef2f6;" alt="<?= gf_h(gf_t('Chưa chọn ảnh bìa')) ?>">
                            <div class="flex-grow-1">
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <input type="file" id="gym-cover-file" name="cover_file" accept="image/jpeg,image/png,image/webp" class="d-none" onchange="previewGymCover(this)">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="document.getElementById('gym-cover-file').click()"><i class="fa-solid fa-camera me-1"></i><?= gf_h(gf_t('Chọn / Thay ảnh')) ?></button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-none" id="gym-cover-cancel-btn" onclick="cancelGymCover()"><i class="fa-solid fa-rotate-left me-1"></i><?= gf_h(gf_t('Hủy đổi')) ?></button>
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" id="gym-cover-remove-btn" onclick="removeGymCover()"><i class="fa-solid fa-trash me-1"></i><?= gf_h(gf_t('Xóa ảnh bìa')) ?></button>
                                </div>
                                <div class="small text-secondary"><?= gf_h(gf_t('Hỗ trợ JPG, PNG, WEBP tối đa 5 MB. Bắt buộc.')) ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- UPLOAD GALLERY SECTION -->
                    <div class="col-12 pt-3 border-top" data-field id="gymGalleryField">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold mb-0"><?= gf_h(gf_t('Thư viện ảnh *')) ?></label>
                            <div>
                                <input type="file" id="gym-gallery-files" name="gallery_files[]" multiple accept="image/jpeg,image/png,image/webp" class="d-none" onchange="addGalleryFiles(this)">
                                <button type="button" id="gym-gallery-add-btn" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="document.getElementById('gym-gallery-files').click()"><i class="fa-solid fa-plus me-1"></i><?= gf_h(gf_t('Thêm ảnh vào thư viện')) ?></button>
                            </div>
                        </div>
                        <div id="gym-gallery-list" class="row g-2 pt-2">
                            <!-- Rendered dynamically via JavaScript -->
                        </div>
                        <div id="gym-gallery-empty" class="text-center py-4 bg-light rounded-3 border text-secondary small"><?= gf_h(gf_t('Chưa có ảnh trong thư viện. Bấm "Thêm ảnh vào thư viện" để chọn ít nhất 3 ảnh.')) ?></div>
                        <div class="form-text mt-2"><?= gf_h(gf_t('Thư viện cần từ 3 đến 6 ảnh. Mỗi ảnh JPG, PNG hoặc WEBP, tối đa 5 MB.')) ?></div>
                        <div id="gym-gallery-count" class="small text-secondary mt-1" aria-live="polite"><?= gf_h(gf_t('0/6 ảnh')) ?></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3 bg-white sticky-bottom">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal"><?= gf_h(gf_t('Hủy')) ?></button>
                <button type="submit" class="btn btn-primary rounded-pill px-4" id="gym-save"><?= gf_h(gf_t('Lưu thông tin')) ?></button>
            </div>
        </form>
    </div>
</div>

<script>
const uploadApiUrl = '<?= gf_url('api/uploads/images') ?>';
const csrfToken = '<?= gf_csrf_token() ?>';
// Placeholder rõ ràng (không dùng ảnh demo Unsplash — dễ nhầm là ảnh bìa thật).
const defaultCoverPlaceholder = '<?= gf_url('assets/images/gym-placeholder.svg') ?>';

let originalCoverUrl = '';
let originalCoverDisplay = '';
let activeCoverObjectUrl = null;
let galleryItems = []; // Array of { id, image_url, caption, sort_order, isNew, file, objectUrl }

function revokeActiveUrls() {
    if (activeCoverObjectUrl) {
        URL.revokeObjectURL(activeCoverObjectUrl);
        activeCoverObjectUrl = null;
    }
    galleryItems.forEach(item => {
        if (item.objectUrl) {
            URL.revokeObjectURL(item.objectUrl);
            item.objectUrl = null;
        }
    });
}

function clearGymFieldErrors() {
    const form = document.getElementById('gymForm');
    form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
    form.querySelectorAll('.gf-field-error').forEach((el) => {
        el.hidden = true;
        el.textContent = '';
    });
    ['gymCoverBox', 'gymCategoryList', 'gymAmenityList', 'gymFeatureList', 'gym-gallery-empty'].forEach((id) => {
        document.getElementById(id)?.classList.remove('border-danger');
    });
}

function showGymGroupError(wrapId, boxId, msg) {
    const wrap = document.getElementById(wrapId);
    if (!wrap) return;
    let err = wrap.querySelector(':scope > .gf-field-error');
    if (!err) {
        err = document.createElement('div');
        err.className = 'gf-field-error';
        wrap.appendChild(err);
    }
    err.textContent = msg;
    err.hidden = false;
    if (boxId) document.getElementById(boxId)?.classList.add('border-danger');
}

function validateGymFormFields() {
    clearGymFieldErrors();
    const form = document.getElementById('gymForm');
    let firstInvalid = null;
    const messages = [];

    const markInput = (el, msg) => {
        if (!el) return;
        el.classList.add('is-invalid');
        const wrap = el.closest('[data-field]') || el.parentElement;
        if (wrap) {
            let err = wrap.querySelector(':scope > .gf-field-error');
            if (!err) {
                err = document.createElement('div');
                err.className = 'gf-field-error';
                wrap.appendChild(err);
            }
            err.textContent = msg;
            err.hidden = false;
        }
        if (!firstInvalid) firstInvalid = el;
        messages.push(msg);
    };

    const nameEl = document.getElementById('gym-name');
    if (!String(nameEl.value || '').trim()) markInput(nameEl, gfT('Vui lòng nhập tên phòng tập.'));

    const phoneEl = document.getElementById('gym-phone');
    const phone = String(phoneEl.value || '').replace(/[\s.\-]/g, '');
    if (!phone) markInput(phoneEl, gfT('Vui lòng nhập số điện thoại liên hệ.'));
    else if (!/^\+?[0-9]{9,14}$/.test(phone)) markInput(phoneEl, gfT('Số điện thoại không hợp lệ (9–14 chữ số).'));

    const addressEl = document.getElementById('gym-address');
    if (!String(addressEl.value || '').trim()) markInput(addressEl, gfT('Vui lòng nhập địa chỉ.'));

    const districtEl = document.getElementById('gym-district');
    if (!String(districtEl.value || '').trim()) markInput(districtEl, gfT('Vui lòng chọn khu vực.'));

    const minEl = document.getElementById('gym-min');
    const maxEl = document.getElementById('gym-max');
    const minVal = String(minEl.value || '').trim();
    const maxVal = String(maxEl.value || '').trim();
    if (minVal === '' || Number.isNaN(Number(minVal)) || Number(minVal) < 0) {
        markInput(minEl, gfT('Vui lòng nhập giá từ.'));
    }
    if (maxVal === '' || Number.isNaN(Number(maxVal)) || Number(maxVal) < 0) {
        markInput(maxEl, gfT('Vui lòng nhập giá đến.'));
    } else if (minVal !== '' && Number(maxVal) < Number(minVal)) {
        markInput(maxEl, gfT('Giá đến phải lớn hơn hoặc bằng giá từ.'));
    }

    const hoursEl = document.getElementById('gym-hours');
    if (!String(hoursEl.value || '').trim()) markInput(hoursEl, gfT('Vui lòng nhập giờ mở cửa.'));

    const descEl = document.getElementById('gym-description');
    if (!String(descEl.value || '').trim()) markInput(descEl, gfT('Vui lòng nhập mô tả.'));

    if (form.querySelectorAll('.gym-category:checked').length === 0) {
        const msg = gfT('Vui lòng chọn ít nhất một danh mục.');
        showGymGroupError('gymCategoryField', 'gymCategoryList', msg);
        if (!firstInvalid) firstInvalid = form.querySelector('.gym-category');
        messages.push(msg);
    }
    if (form.querySelectorAll('.gym-amenity:checked').length === 0) {
        const msg = gfT('Vui lòng chọn ít nhất một tiện ích.');
        showGymGroupError('gymAmenityField', 'gymAmenityList', msg);
        if (!firstInvalid) firstInvalid = form.querySelector('.gym-amenity');
        messages.push(msg);
    }
    if (form.querySelectorAll('.gym-feature:checked').length === 0) {
        const msg = gfT('Vui lòng chọn ít nhất một đặc điểm.');
        showGymGroupError('gymFeatureField', 'gymFeatureList', msg);
        if (!firstInvalid) firstInvalid = form.querySelector('.gym-feature');
        messages.push(msg);
    }

    const coverFile = document.getElementById('gym-cover-file').files?.[0];
    const hasCover = !!coverFile || (document.getElementById('gym-cover-url').value.trim() !== ''
        && document.getElementById('gym-remove-cover').value !== '1');
    if (!hasCover) {
        const msg = gfT('Vui lòng chọn một ảnh bìa cho phòng tập.');
        showGymGroupError('gymCoverField', 'gymCoverBox', msg);
        if (!firstInvalid) firstInvalid = document.getElementById('gym-cover-file');
        messages.push(msg);
    }

    if (galleryItems.length < 3 || galleryItems.length > 6) {
        const msg = gfT('Thư viện cần từ 3 đến 6 ảnh.');
        showGymGroupError('gymGalleryField', 'gym-gallery-empty', msg);
        if (!firstInvalid) firstInvalid = document.getElementById('gym-gallery-add-btn');
        messages.push(msg);
    }

    if (typeof window.gfFormValidate?.validateForm === 'function') {
        const bad = window.gfFormValidate.validateForm(form);
        if (bad && !firstInvalid) firstInvalid = bad;
    }

    const hasVisibleFieldError = !!form.querySelector('.gf-field-error:not([hidden])');
    return { ok: messages.length === 0 && !firstInvalid && !hasVisibleFieldError, firstInvalid, messages };
}

function gymModalCreate(){
    revokeActiveUrls();
    const f=document.getElementById('gymEditor');
    f.querySelector('form').reset();
    document.getElementById('gym-id').value='';
    document.getElementById('gym-phone').value='';
    document.getElementById('gym-hours').value='';
    document.getElementById('gym-featured').checked = false;
    document.getElementById('gym-cover-url').value='';
    document.getElementById('gym-remove-cover').value='0';
    document.getElementById('gym-images-json').value='[]';
    document.getElementById('gym-error-alert').classList.add('d-none');
    document.getElementById('gym-editor-title').textContent=gfT('Thêm phòng tập');
    document.getElementById('gym-save').textContent=gfT('Thêm phòng tập');
    document.getElementById('gym-save').disabled = false;
    
    originalCoverUrl = '';
    originalCoverDisplay = defaultCoverPlaceholder;
    document.getElementById('gym-cover-preview').src = defaultCoverPlaceholder;
    document.getElementById('gym-cover-cancel-btn').classList.add('d-none');

    galleryItems = [];
    renderGallery();
    clearGymFieldErrors();
    const modalBody = f.querySelector('.modal-body');
    if (modalBody) modalBody.scrollTop = 0;
    bootstrap.Modal.getOrCreateInstance(f).show();
}

function gymModalEdit(b){
    revokeActiveUrls();
    const f=document.getElementById('gymEditor');
    f.querySelector('form').reset();
    document.getElementById('gym-id').value=b.dataset.id;
    document.getElementById('gym-name').value=b.dataset.name;
    document.getElementById('gym-phone').value=b.dataset.phone || '';
    document.getElementById('gym-address').value=b.dataset.address;
    document.getElementById('gym-district').value=b.dataset.district;
    document.getElementById('gym-min').value=b.dataset.min;
    document.getElementById('gym-max').value=b.dataset.max;
    document.getElementById('gym-hours').value=b.dataset.hours || '';
    document.getElementById('gym-featured').checked = b.dataset.featured === '1';
    document.getElementById('gym-description').value=b.dataset.description;
    document.getElementById('gym-error-alert').classList.add('d-none');
    document.getElementById('gym-save').disabled = false;
    
    const cats=b.dataset.categories.split(',').filter(Boolean),amens=b.dataset.amenities.split(',').filter(Boolean),feats=(b.dataset.features||'').split(',').filter(Boolean);
    f.querySelectorAll('.gym-category').forEach(x=>x.checked=cats.includes(x.value));
    f.querySelectorAll('.gym-amenity').forEach(x=>x.checked=amens.includes(x.value));
    f.querySelectorAll('.gym-feature').forEach(x=>x.checked=feats.includes(x.value));
    document.getElementById('gym-editor-title').textContent=gfT('Chỉnh sửa phòng tập:') + ' ' + b.dataset.name;
    document.getElementById('gym-save').textContent=gfT('Lưu thông tin');

    // Cover
    originalCoverUrl = b.dataset.cover || '';
    originalCoverDisplay = b.dataset.coverDisplay || defaultCoverPlaceholder;
    document.getElementById('gym-cover-url').value = originalCoverUrl;
    document.getElementById('gym-remove-cover').value = '0';
    document.getElementById('gym-cover-preview').src = originalCoverDisplay;
    document.getElementById('gym-cover-cancel-btn').classList.add('d-none');

    // Gallery
    galleryItems = [];
    if (b.dataset.gallery) {
        try {
            const raw = JSON.parse(b.dataset.gallery);
            raw.forEach((g, idx) => {
                galleryItems.push({
                    id: g.id,
                    image_url: g.image_url,
                    caption: g.caption || '',
                    sort_order: g.sort_order || (idx + 1),
                    isNew: false,
                    file: null,
                    objectUrl: null
                });
            });
        } catch(e) {}
    }
    renderGallery();
    clearGymFieldErrors();
    const modalBody = f.querySelector('.modal-body');
    if (modalBody) modalBody.scrollTop = 0;
    bootstrap.Modal.getOrCreateInstance(f).show();
}

function previewGymCover(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        typeof window.gfToast === 'function' ? window.gfToast(gfT('Định dạng ảnh không hỗ trợ. Vui lòng chọn JPG, PNG hoặc WEBP.'), 'warning') : null;
        input.value = '';
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        typeof window.gfToast === 'function' ? window.gfToast(gfT('Dung lượng ảnh vượt quá 5 MB.'), 'warning') : null;
        input.value = '';
        return;
    }
    if (activeCoverObjectUrl) {
        URL.revokeObjectURL(activeCoverObjectUrl);
    }
    activeCoverObjectUrl = URL.createObjectURL(file);
    document.getElementById('gym-cover-preview').src = activeCoverObjectUrl;
    document.getElementById('gym-remove-cover').value = '0';
    document.getElementById('gym-cover-cancel-btn').classList.remove('d-none');
}

function cancelGymCover() {
    if (activeCoverObjectUrl) {
        URL.revokeObjectURL(activeCoverObjectUrl);
        activeCoverObjectUrl = null;
    }
    document.getElementById('gym-cover-file').value = '';
    document.getElementById('gym-cover-preview').src = originalCoverDisplay;
    document.getElementById('gym-cover-url').value = originalCoverUrl;
    document.getElementById('gym-remove-cover').value = '0';
    document.getElementById('gym-cover-cancel-btn').classList.add('d-none');
}

function removeGymCover() {
    if (activeCoverObjectUrl) {
        URL.revokeObjectURL(activeCoverObjectUrl);
        activeCoverObjectUrl = null;
    }
    document.getElementById('gym-cover-file').value = '';
    document.getElementById('gym-cover-preview').src = defaultCoverPlaceholder;
    document.getElementById('gym-cover-url').value = '';
    document.getElementById('gym-remove-cover').value = '1';
    document.getElementById('gym-cover-cancel-btn').classList.remove('d-none');
}

function addGalleryFiles(input) {
    if (!input.files || !input.files.length) return;
    for (let i = 0; i < input.files.length; i++) {
        const file = input.files[i];
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            typeof window.gfToast === 'function' ? window.gfToast(gfT('Định dạng ảnh không hỗ trợ. Vui lòng chọn JPG, PNG hoặc WEBP.') + ' (' + file.name + ')', 'warning') : null;
            continue;
        }
        if (file.size > 5 * 1024 * 1024) {
            typeof window.gfToast === 'function' ? window.gfToast(gfT('Dung lượng ảnh vượt quá 5 MB.') + ' (' + file.name + ')', 'warning') : null;
            continue;
        }
        if (galleryItems.length >= 6) {
            typeof window.gfToast === 'function' ? window.gfToast(gfT('Tối đa 6 ảnh trong thư viện.'), 'warning') : null;
            break;
        }
        const objUrl = URL.createObjectURL(file);
        galleryItems.push({
            id: null,
            image_url: '',
            caption: '',
            sort_order: galleryItems.length + 1,
            isNew: true,
            file: file,
            objectUrl: objUrl
        });
    }
    input.value = '';
    renderGallery();
}

function renderGallery() {
    const list = document.getElementById('gym-gallery-list');
    const emptyNotice = document.getElementById('gym-gallery-empty');
    const countNotice = document.getElementById('gym-gallery-count');
    const addButton = document.getElementById('gym-gallery-add-btn');
    countNotice.textContent = galleryItems.length + '/6 ' + gfT('ảnh');
    addButton.disabled = galleryItems.length >= 6;
    list.innerHTML = '';
    if (!galleryItems.length) {
        emptyNotice.classList.remove('d-none');
        return;
    }
    emptyNotice.classList.add('d-none');

    galleryItems.forEach((item, index) => {
        const col = document.createElement('div');
        col.className = 'col-6 col-md-4 col-lg-3';
        const displaySrc = item.isNew ? item.objectUrl : item.image_url;
        col.innerHTML = `
            <div class="border rounded-3 p-2 bg-white h-100 d-flex flex-column shadow-sm position-relative">
                <div class="position-relative mb-2">
                    <img src="${displaySrc}" class="w-100 rounded-2 object-fit-cover" style="height:90px;" alt="">
                    <span class="position-absolute top-0 start-0 m-1 badge ${item.isNew ? 'bg-warning text-dark' : 'bg-success'} font-monospace" style="font-size:10px;">
                        ${item.isNew ? gfT('Mới') : gfT('Đã lưu')} #${index + 1}
                    </span>
                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 p-0 rounded-circle d-flex align-items-center justify-content-center" style="width:22px;height:22px;" title="<?= gf_h(gf_t('Xóa ảnh')) ?>" onclick="removeGalleryItem(${index})">
                        <i class="fa-solid fa-xmark" style="font-size:11px;"></i>
                    </button>
                </div>
                <input class="form-control form-control-sm mb-2" placeholder="<?= gf_h(gf_t('Chú thích...')) ?>" value="${gfT(item.caption || '')}" onchange="galleryItems[${index}].caption = this.value">
                <button type="button" class="btn btn-sm btn-outline-secondary w-100 mt-auto rounded-pill" style="font-size:11px;" onclick="makeGalleryCover(${index})">
                    <i class="fa-solid fa-star me-1 text-warning"></i><?= gf_h(gf_t('Đặt làm ảnh bìa')) ?>
                </button>
            </div>
        `;
        list.appendChild(col);
    });
}

function removeGalleryItem(index) {
    const item = galleryItems[index];
    if (item && item.objectUrl) {
        URL.revokeObjectURL(item.objectUrl);
    }
    galleryItems.splice(index, 1);
    galleryItems.forEach((it, idx) => it.sort_order = idx + 1);
    renderGallery();
}

function makeGalleryCover(index) {
    const item = galleryItems[index];
    if (!item) return;

    if (item.isNew) {
        // Chuyển file mới này làm cover file
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(item.file);
        document.getElementById('gym-cover-file').files = dataTransfer.files;
        if (activeCoverObjectUrl) URL.revokeObjectURL(activeCoverObjectUrl);
        activeCoverObjectUrl = URL.createObjectURL(item.file);
        document.getElementById('gym-cover-preview').src = activeCoverObjectUrl;
        document.getElementById('gym-remove-cover').value = '0';
        document.getElementById('gym-cover-cancel-btn').classList.remove('d-none');
    } else {
        // Đặt URL đã lưu làm cover
        if (activeCoverObjectUrl) {
            URL.revokeObjectURL(activeCoverObjectUrl);
            activeCoverObjectUrl = null;
        }
        document.getElementById('gym-cover-file').value = '';
        document.getElementById('gym-cover-url').value = item.image_url;
        document.getElementById('gym-cover-preview').src = item.image_url;
        document.getElementById('gym-remove-cover').value = '0';
        document.getElementById('gym-cover-cancel-btn').classList.remove('d-none');
    }

    // Xóa khỏi gallery để tránh vi phạm ràng buộc trùng cover
    removeGalleryItem(index);
}

async function handleGymFormSubmit(e) {
    e.preventDefault();
    e.stopPropagation();
    const saveBtn = document.getElementById('gym-save');
    const errAlert = document.getElementById('gym-error-alert');
    errAlert.classList.add('d-none');
    errAlert.textContent = '';

    const check = validateGymFormFields();
    if (!check.ok) {
        const msg = check.messages[0] || gfT('Vui lòng điền đầy đủ các trường bắt buộc.');
        errAlert.textContent = msg;
        errAlert.classList.remove('d-none');
        check.firstInvalid?.focus?.();
        return false;
    }

    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>' + gfT('Đang tải ảnh & lưu...');

    try {
        // 1. Upload Cover nếu có file mới chọn
        const coverInput = document.getElementById('gym-cover-file');
        if (coverInput.files && coverInput.files[0]) {
            const formData = new FormData();
            formData.append('file', coverInput.files[0]);
            formData.append('type', 'gym_cover');
            formData.append('_csrf', csrfToken);

            const res = await fetch(uploadApiUrl, {
                method: 'POST',
                headers: { 'X-CSRF-Token': csrfToken },
                body: formData
            });
            const data = await res.json();
            if (!res.ok || !data.data || !data.data.url) {
                throw new Error(data.error?.message || gfT('Tải ảnh bìa thất bại.'));
            }
            document.getElementById('gym-cover-url').value = data.data.url;
            coverInput.value = ''; // Đã upload xong, tránh gửi trùng
        }

        if (!String(document.getElementById('gym-cover-url').value || '').trim()
            || document.getElementById('gym-remove-cover').value === '1') {
            throw new Error(gfT('Vui lòng chọn một ảnh bìa cho phòng tập.'));
        }

        // 2. Upload các ảnh gallery mới
        for (let i = 0; i < galleryItems.length; i++) {
            const item = galleryItems[i];
            if (item.isNew && item.file) {
                const formData = new FormData();
                formData.append('file', item.file);
                formData.append('type', 'gym_image');
                formData.append('_csrf', csrfToken);

                const res = await fetch(uploadApiUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': csrfToken },
                    body: formData
                });
                const data = await res.json();
                if (!res.ok || !data.data || !data.data.url) {
                    throw new Error(data.error?.message || (gfT('Tải ảnh thư viện thất bại.') + ' #' + (i + 1)));
                }
                item.image_url = data.data.url;
                item.isNew = false;
                item.file = null;
            }
        }

        if (galleryItems.length < 3 || galleryItems.length > 6) {
            throw new Error(gfT('Thư viện cần từ 3 đến 6 ảnh.'));
        }

        // 3. Gom danh sách gallery hợp lệ
        const finalImages = galleryItems.map((item, idx) => {
            const payload = {
                image_url: item.image_url,
                caption: item.caption || '',
                sort_order: idx + 1
            };
            if (item.id) payload.id = item.id;
            return payload;
        });
        document.getElementById('gym-images-json').value = JSON.stringify(finalImages);

        // 4. Submit form
        document.getElementById('gymForm').submit();
    } catch (err) {
        saveBtn.disabled = false;
        saveBtn.textContent = document.getElementById('gym-id').value ? gfT('Lưu thông tin') : gfT('Thêm phòng tập');
        errAlert.textContent = err.message || gfT('Đã xảy ra lỗi khi lưu thông tin.');
        errAlert.classList.remove('d-none');
    }
    return false;
}

function handleGymStatusSubmit(form) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
    }
    return true;
}

window.addEventListener('beforeunload', revokeActiveUrls);

<?php if (!empty($reopenGymEditor) && $error !== ''): ?>
document.addEventListener('DOMContentLoaded', () => {
    const serverMsg = <?= json_encode((string) $error, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    const posted = <?= json_encode([
        'id' => (string) ($_POST['id'] ?? ''),
        'name' => (string) ($_POST['name'] ?? ''),
        'phone' => (string) ($_POST['phone'] ?? ''),
        'address' => (string) ($_POST['address'] ?? ''),
        'district_id' => (string) ($_POST['district_id'] ?? ''),
        'price_min' => (string) ($_POST['price_min'] ?? ''),
        'price_max' => (string) ($_POST['price_max'] ?? ''),
        'opening_hours' => (string) ($_POST['opening_hours'] ?? ''),
        'description' => (string) ($_POST['description'] ?? ''),
        'is_featured' => !empty($_POST['is_featured']),
        'cover_image_url' => (string) ($_POST['cover_image_url'] ?? ''),
        'category_ids' => array_map('strval', (array) ($_POST['category_ids'] ?? [])),
        'amenity_ids' => array_map('strval', (array) ($_POST['amenity_ids'] ?? [])),
        'feature_ids' => array_map('strval', (array) ($_POST['feature_ids'] ?? [])),
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;

    const modalEl = document.getElementById('gymEditor');
    document.getElementById('gym-id').value = posted.id || '';
    document.getElementById('gym-name').value = posted.name || '';
    document.getElementById('gym-phone').value = posted.phone || '';
    document.getElementById('gym-address').value = posted.address || '';
    document.getElementById('gym-district').value = posted.district_id || '';
    document.getElementById('gym-min').value = posted.price_min || '';
    document.getElementById('gym-max').value = posted.price_max || '';
    document.getElementById('gym-hours').value = posted.opening_hours || '';
    document.getElementById('gym-description').value = posted.description || '';
    document.getElementById('gym-featured').checked = !!posted.is_featured;
    document.getElementById('gym-cover-url').value = posted.cover_image_url || '';
    document.getElementById('gym-remove-cover').value = '0';
    if (posted.cover_image_url) {
        document.getElementById('gym-cover-preview').src = posted.cover_image_url;
    }
    const catSet = new Set(posted.category_ids || []);
    const amSet = new Set(posted.amenity_ids || []);
    const ftSet = new Set(posted.feature_ids || []);
    document.querySelectorAll('.gym-category').forEach((el) => { el.checked = catSet.has(String(el.value)); });
    document.querySelectorAll('.gym-amenity').forEach((el) => { el.checked = amSet.has(String(el.value)); });
    document.querySelectorAll('.gym-feature').forEach((el) => { el.checked = ftSet.has(String(el.value)); });
    document.getElementById('gym-editor-title').textContent = posted.id ? (gfT('Chỉnh sửa phòng tập:') + ' ' + posted.name) : gfT('Thêm phòng tập');
    document.getElementById('gym-save').textContent = posted.id ? gfT('Lưu thông tin') : gfT('Thêm phòng tập');

    if (modalEl && window.bootstrap?.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
    if (typeof validateGymFormFields === 'function') {
        validateGymFormFields();
    }
    const errAlert = document.getElementById('gym-error-alert');
    if (errAlert) {
        errAlert.textContent = typeof gfT === 'function' ? gfT(serverMsg) : serverMsg;
        errAlert.classList.remove('d-none');
    }
    if (serverMsg && typeof window.gfToast === 'function') {
        window.gfToast(typeof gfT === 'function' ? gfT(serverMsg) : serverMsg, 'danger');
    }
});
<?php endif; ?>
</script>
<script>
document.querySelectorAll('[data-delete-blocked]').forEach((button) => {
    button.addEventListener('click', () => {
        const message = button.getAttribute('data-delete-blocked') || '';
        if (typeof window.gfToast === 'function') {
            window.gfToast(typeof gfT === 'function' ? gfT(message) : message, 'warning');
        }
    });
});
</script>
</main></div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require dirname(__DIR__, 2) . '/includes/ui_feedback.php'; ?></body></html>
