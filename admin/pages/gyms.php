<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
$adminUser = gf_require_admin();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    gf_require_csrf();
    if (!empty($_POST['id']) && isset($_POST['name'])) {
        $data = $_POST;
        $id = (int) $_POST['id'];
        unset($data['_csrf'], $data['id']);
        $data['category_ids'] = array_map('intval', $data['category_ids'] ?? []);
        $data['amenity_ids'] = array_map('intval', $data['amenity_ids'] ?? []);
        $data['price_min'] = (int) ($data['price_min'] ?? 0);
        $data['price_max'] = (int) ($data['price_max'] ?? 0);
        if (!empty($_POST['remove_cover'])) {
            $data['cover_image_url'] = null;
        } elseif (array_key_exists('cover_image_url', $_POST)) {
            $data['cover_image_url'] = trim((string)$_POST['cover_image_url']) ?: null;
        }
        if (isset($_FILES['cover_file']) && ($_FILES['cover_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $up = gf_save_upload($_FILES['cover_file'], 'gym_cover', $adminUser);
            if (!$up['ok']) { $error = $up['error']; }
            else { $data['cover_image_url'] = $up['url']; }
        }
        if (!empty($_POST['images_json'])) {
            $imgs = json_decode($_POST['images_json'], true);
            if (is_array($imgs)) { $data['images'] = $imgs; }
        }
        if (!$error) {
            $result = gf_admin_save_gym($data, $id);
            if (!empty($result['ok'])) gf_redirect(gf_url('admin/pages/gyms.php?updated=1'));
            $error = $result['error'] ?? 'Không thể cập nhật phòng tập.';
        }
    } elseif (isset($_POST['id'], $_POST['status'])) {
        gf_set_entity_status('gyms', (int) $_POST['id'], $_POST['status'] === 'hidden' ? 'hidden' : 'active');
        gf_redirect(gf_url('admin/pages/gyms.php'));
    } elseif (isset($_POST['name'])) {
        $data = $_POST;
        unset($data['_csrf'], $data['id']);
        $data['category_ids'] = array_map('intval', $data['category_ids'] ?? []);
        $data['amenity_ids'] = array_map('intval', $data['amenity_ids'] ?? []);
        $data['price_min'] = (int) ($data['price_min'] ?? 0);
        $data['price_max'] = (int) ($data['price_max'] ?? 0);
        if (!empty($_POST['remove_cover'])) {
            $data['cover_image_url'] = null;
        } elseif (array_key_exists('cover_image_url', $_POST)) {
            $data['cover_image_url'] = trim((string)$_POST['cover_image_url']) ?: null;
        }
        if (isset($_FILES['cover_file']) && ($_FILES['cover_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $up = gf_save_upload($_FILES['cover_file'], 'gym_cover', $adminUser);
            if (!$up['ok']) { $error = $up['error']; }
            else { $data['cover_image_url'] = $up['url']; }
        }
        if (!empty($_POST['images_json'])) {
            $imgs = json_decode($_POST['images_json'], true);
            if (is_array($imgs)) { $data['images'] = $imgs; }
        }
        if (!$error) {
            $result = gf_admin_save_gym($data, null);
            if (!empty($result['ok'])) {
                gf_redirect(gf_url('admin/pages/gyms.php?created=1'));
            }
            $error = $result['error'] ?? 'Không thể lưu phòng tập.';
        }
    }
}
$districts = gf_list_districts();
$districtNames = [];
foreach ($districts as $district) { $districtNames[(int)$district['id']] = $district['name']; }
$gymPage = max(1,(int)($_GET['page']??1));
$gymResult = gf_admin_gyms_paged([
    'limit' => 10,
    'page' => $gymPage,
    'sort' => 'oldest',
    'q' => $_GET['q'] ?? '',
    'status' => $_GET['status'] ?? '',
    'district_id' => $_GET['district_id'] ?? '',
]);
$rows = $gymResult['items'];
$gymCategoryNames = $gymCategoryIds = $gymAmenityIds = $gymDescriptions = $gymGalleries = [];
$categoryQuery = gf_pdo()->prepare('SELECT c.id,c.name FROM gym_categories gc JOIN categories c ON c.id=gc.category_id WHERE gc.gym_id=? ORDER BY c.name');
$amenityQuery = gf_pdo()->prepare('SELECT amenity_id FROM gym_amenities WHERE gym_id=?');
$descriptionQuery = gf_pdo()->prepare('SELECT description FROM gyms WHERE id=?');
$galleryQuery = gf_pdo()->prepare('SELECT id, image_url, caption, sort_order FROM gym_images WHERE gym_id=? ORDER BY sort_order ASC');
foreach ($rows as $gymRow) {
    $categoryQuery->execute([(int)$gymRow['id']]);
    $gymCategories = $categoryQuery->fetchAll();
    $gymCategoryNames[(int)$gymRow['id']] = array_column($gymCategories, 'name');
    $gymCategoryIds[(int)$gymRow['id']] = array_map('intval', array_column($gymCategories, 'id'));
    $amenityQuery->execute([(int)$gymRow['id']]);
    $gymAmenityIds[(int)$gymRow['id']] = array_map('intval', array_column($amenityQuery->fetchAll(), 'amenity_id'));
    $descriptionQuery->execute([(int)$gymRow['id']]);
    $gymDescriptions[(int)$gymRow['id']] = (string)($descriptionQuery->fetchColumn() ?: '');
    $galleryQuery->execute([(int)$gymRow['id']]);
    $gymGalleries[(int)$gymRow['id']] = $galleryQuery->fetchAll();
}

function gf_admin_cat_badge(string $name): string {
    $n = mb_strtolower(trim($name), 'UTF-8');
    if (str_contains($n, 'fit') || str_contains($n, 'gym')) return 'cat-badge-fitness';
    if (str_contains($n, 'prem') || str_contains($n, 'vip') || str_contains($n, 'cao cấp')) return 'cat-badge-premium';
    if (str_contains($n, 'yoga') || str_contains($n, 'pilates')) return 'cat-badge-yoga';
    if (str_contains($n, 'cross') || str_contains($n, 'boxing') || str_contains($n, 'võ')) return 'cat-badge-crossfit';
    return 'cat-badge-default';
}

$categories = gf_list_categories(false);
$amenities = gf_list_amenities();
$pageTitle = 'Phòng tập - Admin GYMFINDER';
$extraCss = ['admin/assets/admin.css'];
$adminActive = 'gyms';
require dirname(__DIR__, 2) . '/includes/layout_head.php';
require dirname(__DIR__, 2) . '/includes/admin_layout.php';
require_once dirname(__DIR__) . '/components/table.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <h2 class="fw-bold mb-0 text-dark" style="font-size: 24px; letter-spacing: -0.3px;">Quản lý phòng tập</h2>
    <button class="btn btn-primary px-3 py-2 rounded-3 d-inline-flex align-items-center gap-2 fw-semibold shadow-sm" onclick="gymModalCreate()" style="height: 42px;">
        <i class="fa-solid fa-plus"></i> Thêm mới
    </button>
</div>
<?php if (!empty($_GET['created'])): ?><div class="alert alert-success border-0 shadow-sm rounded-3"><i class="fa-solid fa-circle-check me-2"></i>Đã thêm phòng tập thành công.</div><?php endif; ?>
<?php if (!empty($_GET['updated'])): ?><div class="alert alert-success border-0 shadow-sm rounded-3"><i class="fa-solid fa-circle-check me-2"></i>Đã cập nhật phòng tập thành công.</div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger border-0 shadow-sm rounded-3"><i class="fa-solid fa-circle-exclamation me-2"></i><?= gf_h($error) ?></div><?php endif; ?>

<form method="get" class="admin-card p-3 mb-4 shadow-sm border">
    <div class="row g-3 align-items-center">
        <div class="col-12 col-md-5">
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass text-secondary position-absolute" style="left: 14px; top: 50%; transform: translateY(-50%); font-size: 14px; pointer-events: none;"></i>
                <input class="form-control admin-input" name="q" value="<?= gf_h($_GET['q'] ?? '') ?>" placeholder="Tìm kiếm phòng tập..." style="padding-left: 38px; height: 42px;">
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <select class="form-select admin-input" name="district_id" style="height: 42px;">
                <option value="">Tất cả khu vực</option>
                <?php foreach ($districts as $d): ?>
                    <option value="<?= (int)$d['id'] ?>" <?= (string)($_GET['district_id'] ?? '') === (string)$d['id'] ? 'selected' : '' ?>><?= gf_h($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-sm-6 col-md-2">
            <select class="form-select admin-input" name="status" style="height: 42px;">
                <option value="">Tất cả trạng thái</option>
                <option value="active" <?= ($_GET['status'] ?? '') === 'active' ? 'selected' : '' ?>>Hoạt động</option>
                <option value="hidden" <?= ($_GET['status'] ?? '') === 'hidden' ? 'selected' : '' ?>>Tạm ẩn</option>
            </select>
        </div>
        <div class="col-12 col-md-2">
            <button type="submit" class="btn btn-filter w-100 d-inline-flex align-items-center justify-content-center gap-2 fw-medium" style="height: 42px;">
                <i class="fa-solid fa-arrows-rotate"></i> Lọc
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
        ['label' => 'THAO TÁC', 'width' => '125px', 'align' => 'right', 'class' => 'pe-3'],
    ]
]);
?>
            <?php foreach ($rows as $r): ?>
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
                                <?php if (!empty($gymGalleries[(int)$r['id']])): ?>
                                    <div class="mt-1">
                                        <span class="badge bg-light text-secondary border px-2 py-0" style="font-size: 10.5px; font-weight: 500;">
                                            <i class="fa-solid fa-images me-1 text-primary"></i><?= count($gymGalleries[(int)$r['id']]) ?> ảnh
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="fw-medium text-secondary"><?= gf_h($districtNames[(int)$r['district_id']] ?? '—') ?></span>
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
                            <?= $r['status'] === 'active' ? 'Hoạt động' : 'Tạm ẩn' ?>
                        </span>
                    </td>
                    <td class="text-end gym-actions pe-3">
                        <div class="d-inline-flex align-items-center justify-content-end gap-2">
                            <button type="button" class="btn btn-action btn-action-view" data-bs-toggle="modal" data-bs-target="#gymView<?= (int)$r['id'] ?>" title="Chi tiết"><i class="fa-solid fa-eye"></i></button>
                            <button type="button" class="btn btn-action btn-action-edit" data-id="<?= (int)$r['id'] ?>" data-name="<?= gf_h($r['name']) ?>" data-address="<?= gf_h($r['address']) ?>" data-district="<?= (int)$r['district_id'] ?>" data-min="<?= (int)$r['price_min'] ?>" data-max="<?= (int)$r['price_max'] ?>" data-description="<?= gf_h($gymDescriptions[(int)$r['id']] ?? '') ?>" data-cover="<?= gf_h($r['cover_image_url'] ?? '') ?>" data-cover-display="<?= gf_h(gf_cover_image($r['cover_image_url'] ?? null, (int)$r['id'])) ?>" data-gallery='<?= htmlspecialchars(json_encode($gymGalleries[(int)$r['id']] ?? []), ENT_QUOTES, 'UTF-8') ?>' data-categories="<?= gf_h(implode(',', $gymCategoryIds[(int)$r['id']] ?? [])) ?>" data-amenities="<?= gf_h(implode(',', $gymAmenityIds[(int)$r['id']] ?? [])) ?>" onclick="gymModalEdit(this)" title="Chỉnh sửa"><i class="fa-solid fa-pen"></i></button>
                            <form method="post" class="d-inline m-0 p-0"><?= gf_csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="status" value="<?= $r['status'] === 'active' ? 'hidden' : 'active' ?>"><button class="btn btn-action <?= $r['status']==='active'?'btn-action-delete':'btn-action-edit' ?>" title="<?= $r['status']==='active'?'Tạm ẩn':'Kích hoạt' ?>"><i class="fa-solid <?= $r['status']==='active'?'fa-trash':'fa-eye' ?>"></i></button></form>
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
                <h5 class="modal-title fw-bold text-dark" id="gymViewTitle<?= (int)$r['id'] ?>"><i class="fa-solid fa-dumbbell text-primary me-2"></i>Chi tiết phòng tập</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-md-5">
                        <!-- Ảnh Cover -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small fw-bold text-dark"><i class="fa-solid fa-image me-1 text-primary"></i>Ảnh Cover</span>
                                <span class="badge badge-success" style="font-size: 10px;">Đại diện</span>
                            </div>
                            <div class="position-relative overflow-hidden rounded-3 shadow-sm border">
                                <img src="<?= gf_h(gf_cover_image($r['cover_image_url'] ?? null, (int)$r['id'])) ?>" 
                                     class="w-100 object-fit-cover d-block" 
                                     style="height:190px;" 
                                     alt="Cover"
                                     onerror="this.onerror=null;this.src='<?= gf_h(gf_cover_image(null, (int)$r['id'])) ?>';">
                            </div>
                        </div>
                        
                        <!-- Bộ sưu tập ảnh (Gallery) -->
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small fw-bold text-dark"><i class="fa-solid fa-images me-1 text-primary"></i>Bộ sưu tập ảnh</span>
                                <span class="badge badge-neutral" style="font-size: 10px;"><?= count($gymGalleries[(int)$r['id']] ?? []) ?> ảnh</span>
                            </div>
                            <?php if (empty($gymGalleries[(int)$r['id']])): ?>
                                <div class="text-center py-3 bg-light rounded-3 text-muted small border">
                                    <i class="fa-regular fa-image me-1"></i>Chưa có ảnh trong gallery
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
                        <p class="text-secondary mb-3"><i class="fa-solid fa-location-dot me-1 text-muted"></i><?= gf_h($r['address']) ?>, <?= gf_h($districtNames[(int)$r['district_id']] ?? '') ?></p>
                        
                        <div class="p-3 bg-light rounded-3 mb-3 border">
                            <div class="row g-2 small">
                                <div class="col-6"><strong>Khu vực:</strong> <?= gf_h($districtNames[(int)$r['district_id']] ?? '—') ?></div>
                                <div class="col-6"><strong>Đánh giá:</strong> <i class="fa-solid fa-star text-warning"></i> <?= number_format((float)($r['avg_rating'] ?? 0), 1) ?> (<?= (int)$r['review_count'] ?> đánh giá)</div>
                                <div class="col-6"><strong>Khoảng giá:</strong> <span class="text-primary fw-bold"><?= gf_h(gf_format_price((int)$r['price_min'], (int)$r['price_max'])) ?></span></div>
                                <div class="col-6"><strong>Trạng thái:</strong> <span class="admin-badge <?= $r['status'] === 'active' ? 'badge-success' : 'badge-neutral' ?>"><?= $r['status'] === 'active' ? 'Hoạt động' : 'Tạm ẩn' ?></span></div>
                            </div>
                        </div>
                        
                        <?php if (!empty($gymDescriptions[(int)$r['id']])): ?>
                            <div class="mb-3">
                                <div class="small fw-bold text-dark mb-1">Mô tả:</div>
                                <p class="small text-muted mb-0" style="line-height:1.5;"><?= nl2br(gf_h($gymDescriptions[(int)$r['id']])) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div>
                            <div class="small fw-bold text-dark mb-1">Danh mục:</div>
                            <div class="d-flex flex-wrap gap-1">
                                <?php foreach ($gymCategoryNames[(int)$r['id']] ?? [] as $categoryName): ?>
                                    <span class="admin-cat-badge <?= gf_admin_cat_badge($categoryName) ?>"><?= gf_h($categoryName) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3 bg-white sticky-bottom">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
<div class="modal fade" id="gymEditor" tabindex="-1" aria-labelledby="gym-editor-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form method="post" id="gymForm" class="modal-content border-0 rounded-4 shadow overflow-hidden" enctype="multipart/form-data" onsubmit="return handleGymFormSubmit(event)">
            <?= gf_csrf_field() ?>
            <input type="hidden" name="id" id="gym-id">
            <input type="hidden" name="cover_image_url" id="gym-cover-url">
            <input type="hidden" name="remove_cover" id="gym-remove-cover" value="0">
            <input type="hidden" name="images_json" id="gym-images-json" value="[]">
            <div class="modal-header border-bottom px-4 py-3 bg-white sticky-top">
                <h5 class="modal-title fw-bold text-dark" id="gym-editor-title"><i class="fa-solid fa-dumbbell text-primary me-2"></i>Thêm phòng tập</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <div id="gym-error-alert" class="alert alert-danger d-none"></div>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Tên phòng tập *</label><input class="form-control admin-input" name="name" id="gym-name" required></div>
                    <div class="col-md-6"><label class="form-label">Địa chỉ *</label><input class="form-control admin-input" name="address" id="gym-address" required></div>
                    <div class="col-md-4"><label class="form-label">Khu vực *</label><select class="form-select admin-input" name="district_id" id="gym-district" required><?php foreach ($districts as $d): ?><option value="<?= (int)$d['id'] ?>"><?= gf_h($d['name']) ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-4"><label class="form-label">Giá từ</label><input class="form-control admin-input" type="number" min="0" name="price_min" id="gym-min" value="200000"></div>
                    <div class="col-md-4"><label class="form-label">Giá đến</label><input class="form-control admin-input" type="number" min="0" name="price_max" id="gym-max" value="800000"></div>
                    <div class="col-12"><label class="form-label">Mô tả</label><textarea class="form-control admin-input" name="description" id="gym-description" rows="3"></textarea></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Danh mục</label><div><?php foreach ($categories as $c): ?><label class="form-check"><input class="form-check-input gym-category" type="checkbox" name="category_ids[]" value="<?= (int)$c['id'] ?>"><span class="form-check-label"><?= gf_h($c['name']) ?></span></label><?php endforeach; ?></div></div>
                    <div class="col-md-6"><label class="form-label fw-semibold">Tiện ích</label><div><?php foreach ($amenities as $a): ?><label class="form-check"><input class="form-check-input gym-amenity" type="checkbox" name="amenity_ids[]" value="<?= (int)$a['id'] ?>"><span class="form-check-label"><?= gf_h($a['name']) ?></span></label><?php endforeach; ?></div></div>

                    <!-- UPLOAD COVER SECTION -->
                    <div class="col-12 pt-3 border-top">
                        <label class="form-label fw-bold d-block mb-2">Ảnh đại diện phòng gym (Cover)</label>
                        <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 border">
                            <img id="gym-cover-preview" src="<?= gf_h(gf_cover_image(null, 1)) ?>" class="rounded-3 shadow-sm object-fit-cover" style="width:110px;height:80px;flex:0 0 110px;" alt="Cover preview">
                            <div class="flex-grow-1">
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <input type="file" id="gym-cover-file" name="cover_file" accept="image/jpeg,image/png,image/webp" class="d-none" onchange="previewGymCover(this)">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="document.getElementById('gym-cover-file').click()"><i class="fa-solid fa-camera me-1"></i>Chọn / Thay ảnh</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-none" id="gym-cover-cancel-btn" onclick="cancelGymCover()"><i class="fa-solid fa-rotate-left me-1"></i>Hủy đổi</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" id="gym-cover-remove-btn" onclick="removeGymCover()"><i class="fa-solid fa-trash me-1"></i>Xóa cover</button>
                                </div>
                                <div class="small text-secondary">Hỗ trợ JPG, PNG, WEBP tối đa 5 MB.</div>
                            </div>
                        </div>
                    </div>

                    <!-- UPLOAD GALLERY SECTION -->
                    <div class="col-12 pt-3 border-top">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-bold mb-0">Bộ sưu tập ảnh (Gallery)</label>
                            <div>
                                <input type="file" id="gym-gallery-files" name="gallery_files[]" multiple accept="image/jpeg,image/png,image/webp" class="d-none" onchange="addGalleryFiles(this)">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="document.getElementById('gym-gallery-files').click()"><i class="fa-solid fa-plus me-1"></i>Thêm ảnh vào gallery</button>
                            </div>
                        </div>
                        <div id="gym-gallery-list" class="row g-2 pt-2">
                            <!-- Rendered dynamically via JavaScript -->
                        </div>
                        <div id="gym-gallery-empty" class="text-center py-4 bg-light rounded-3 border text-secondary small">Chưa có ảnh trong gallery. Bấm "Thêm ảnh vào gallery" để chọn nhiều ảnh.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top px-4 py-3 bg-white sticky-bottom">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4" id="gym-save">Lưu thông tin</button>
            </div>
        </form>
    </div>
</div>
<script>
const uploadApiUrl = '<?= gf_url('api/uploads/images') ?>';
const csrfToken = '<?= gf_csrf_token() ?>';
const defaultCoverPlaceholder = '<?= gf_cover_image(null, 1) ?>';

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

function gymModalCreate(){
    revokeActiveUrls();
    const f=document.getElementById('gymEditor');
    f.querySelector('form').reset();
    document.getElementById('gym-id').value='';
    document.getElementById('gym-cover-url').value='';
    document.getElementById('gym-remove-cover').value='0';
    document.getElementById('gym-images-json').value='[]';
    document.getElementById('gym-error-alert').classList.add('d-none');
    document.getElementById('gym-editor-title').textContent='Thêm phòng tập';
    document.getElementById('gym-save').textContent='Thêm phòng tập';
    document.getElementById('gym-save').disabled = false;
    
    originalCoverUrl = '';
    originalCoverDisplay = defaultCoverPlaceholder;
    document.getElementById('gym-cover-preview').src = defaultCoverPlaceholder;
    document.getElementById('gym-cover-cancel-btn').classList.add('d-none');

    galleryItems = [];
    renderGallery();
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
    document.getElementById('gym-address').value=b.dataset.address;
    document.getElementById('gym-district').value=b.dataset.district;
    document.getElementById('gym-min').value=b.dataset.min;
    document.getElementById('gym-max').value=b.dataset.max;
    document.getElementById('gym-description').value=b.dataset.description;
    document.getElementById('gym-error-alert').classList.add('d-none');
    document.getElementById('gym-save').disabled = false;
    
    const cats=b.dataset.categories.split(',').filter(Boolean),amens=b.dataset.amenities.split(',').filter(Boolean);
    f.querySelectorAll('.gym-category').forEach(x=>x.checked=cats.includes(x.value));
    f.querySelectorAll('.gym-amenity').forEach(x=>x.checked=amens.includes(x.value));
    document.getElementById('gym-editor-title').textContent='Chỉnh sửa phòng tập: ' + b.dataset.name;
    document.getElementById('gym-save').textContent='Lưu thông tin';

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
    const modalBody = f.querySelector('.modal-body');
    if (modalBody) modalBody.scrollTop = 0;
    bootstrap.Modal.getOrCreateInstance(f).show();
}

function previewGymCover(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        alert('Định dạng ảnh không hỗ trợ. Vui lòng chọn JPG, PNG hoặc WEBP.');
        input.value = '';
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        alert('Dung lượng ảnh vượt quá 5 MB.');
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
            alert(`File "${file.name}" không hợp lệ. Chỉ nhận JPG, PNG, WEBP.`);
            continue;
        }
        if (file.size > 5 * 1024 * 1024) {
            alert(`File "${file.name}" vượt quá 5 MB.`);
            continue;
        }
        if (galleryItems.length >= 20) {
            alert('Tối đa 20 ảnh trong gallery.');
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
                        ${item.isNew ? 'Mới' : 'Đã lưu'} #${index + 1}
                    </span>
                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 p-0 rounded-circle d-flex align-items-center justify-content-center" style="width:22px;height:22px;" title="Xóa ảnh" onclick="removeGalleryItem(${index})">
                        <i class="fa-solid fa-xmark" style="font-size:11px;"></i>
                    </button>
                </div>
                <input class="form-control form-control-sm mb-2" placeholder="Chú thích..." value="${item.caption || ''}" onchange="galleryItems[${index}].caption = this.value">
                <button type="button" class="btn btn-sm btn-outline-secondary w-100 mt-auto rounded-pill" style="font-size:11px;" onclick="makeGalleryCover(${index})">
                    <i class="fa-solid fa-star me-1 text-warning"></i>Đặt làm cover
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
    const saveBtn = document.getElementById('gym-save');
    const errAlert = document.getElementById('gym-error-alert');
    errAlert.classList.add('d-none');
    errAlert.textContent = '';

    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Đang tải ảnh & lưu...';

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
                throw new Error(data.error?.message || 'Upload ảnh cover thất bại.');
            }
            document.getElementById('gym-cover-url').value = data.data.url;
            coverInput.value = ''; // Đã upload xong, tránh gửi trùng
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
                    throw new Error(data.error?.message || `Upload ảnh gallery #${i + 1} thất bại.`);
                }
                item.image_url = data.data.url;
                item.isNew = false;
                item.file = null;
            }
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
        saveBtn.textContent = document.getElementById('gym-id').value ? 'Lưu thông tin' : 'Thêm phòng tập';
        errAlert.textContent = err.message || 'Đã xảy ra lỗi khi lưu thông tin.';
        errAlert.classList.remove('d-none');
    }
    return false;
}

window.addEventListener('beforeunload', revokeActiveUrls);
</script>
</main></div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body></html>
