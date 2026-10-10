<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
$filters = [
    'keyword' => trim((string) ($_GET['keyword'] ?? '')),
    'district_id' => $_GET['district_id'] ?? '',
    'rating' => $_GET['rating'] ?? '',
    'category_ids' => is_array($_GET['category_ids'] ?? null) ? array_values(array_map('intval', $_GET['category_ids'])) : [],
    'amenity_options' => is_array($_GET['amenities'] ?? null) ? array_values(array_map('strval', $_GET['amenities'])) : [],
    'amenity_ids' => [],
    'has_pt' => $_GET['has_pt'] ?? '',
    'open_247' => $_GET['open_247'] ?? '',
    'weight_loss' => $_GET['weight_loss'] ?? '',
    'featured' => $_GET['featured'] ?? '',
    'price_min' => $_GET['price_min'] ?? '',
    'price_max' => $_GET['price_max'] ?? '',
    'sort' => $_GET['sort'] ?? '',
    'page' => max(1, (int)($_GET['page'] ?? 1)),
    'limit' => 4,
];
$amenityIdMap = ['dry_steam' => 4, 'wet_steam' => 4, 'locker' => 5, 'parking' => 2, 'shower' => 1];
$filters['amenity_ids'] = array_values(array_unique(array_filter(array_map(
    static fn ($option) => $amenityIdMap[$option] ?? 0,
    $filters['amenity_options']
))));
$price = $_GET['price'] ?? '';
if ($price === '500') {
    $filters['price_max'] = 500000;
}
if ($price === '1000') {
    $filters['price_min'] = 500000;
    $filters['price_max'] = 1000000;
}
$gymPage = gf_search_gyms_paged($filters);
$gyms = $gymPage['items'];
$districts = gf_list_districts();
$categories = gf_list_categories();
$amenities = gf_list_amenities();
$figmaFeatures = [
    ['id' => 'feature_fitness', 'label' => 'Gym phổ thông', 'category_id' => 1],
    ['id' => 'feature_premium', 'label' => 'Gym cao cấp', 'category_id' => 2],
    ['id' => 'feature_247', 'label' => 'Mở cửa 24/7', 'name' => 'open_247'],
    ['id' => 'feature_pt', 'label' => 'Có huấn luyện viên (PT)', 'name' => 'has_pt'],
    ['id' => 'feature_women', 'label' => 'Dành cho nữ', 'disabled' => true],
    ['id' => 'feature_weight_loss', 'label' => 'Hỗ trợ giảm cân', 'name' => 'weight_loss'],
];
$figmaAmenities = [
    ['id' => 'amenity_dry_steam', 'key' => 'dry_steam', 'label' => 'Xông hơi khô', 'amenity_id' => 4],
    ['id' => 'amenity_wet_steam', 'key' => 'wet_steam', 'label' => 'Xông hơi ướt', 'amenity_id' => 4],
    ['id' => 'amenity_locker', 'key' => 'locker', 'label' => 'Locker', 'amenity_id' => 5],
    ['id' => 'amenity_parking', 'key' => 'parking', 'label' => 'Bãi đỗ xe', 'amenity_id' => 2],
    ['id' => 'amenity_ac', 'label' => 'Điều hòa', 'disabled' => true],
    ['id' => 'amenity_shower', 'key' => 'shower', 'label' => 'Phòng tắm', 'amenity_id' => 1],
    ['id' => 'amenity_water', 'label' => 'Nước uống miễn phí', 'disabled' => true],
];
$user = gf_current_user();
$favGymIds = $user ? array_map(static fn ($g) => (int) $g['id'], gf_user_favorites((int)$user['id'])['gyms']) : [];
$pageTitle = 'Tìm kiếm phòng tập - GYMFINDER';
$bodyClass = 'bg-light';
require dirname(__DIR__, 2) . '/includes/layout_head.php';
require dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="page-title-section" style="background:linear-gradient(135deg,#f0fdfa 0%,#fff 100%);padding:60px 0 40px;border-bottom:1px solid #e2e8f0;margin-bottom:40px;text-align:center;">
    <div class="container">
        <h1 class="hero-title text-uppercase">TÌM KIẾM <span class="text-primary position-relative">PHÒNG TẬP
            <svg class="position-absolute w-100 bottom-0 start-0 translate-middle-y text-primary opacity-50" style="height:10px" viewBox="0 0 100 10" preserveAspectRatio="none"><path d="M0 5 Q 50 10 100 5" stroke="currentColor" stroke-width="4" fill="transparent"></path></svg>
        </span> CỦA BẠN</h1>
        <p class="text-secondary mb-5" style="font-size:16px">Khám phá và tìm phòng tập phù hợp với nhu cầu của bạn tại TP. Hồ Chí Minh.</p>
        <form method="get" class="bg-white rounded-pill p-2 shadow-sm mx-auto d-flex align-items-center" style="max-width:700px;border:1px solid #f1f5f9;">
            <i class="fa-solid fa-magnifying-glass text-secondary ms-3"></i>
            <input type="text" name="keyword" class="form-control border-0 shadow-none" value="<?= gf_h($filters['keyword']) ?>" placeholder="Tìm kiếm tên phòng tập, khu vực...">
            <button class="btn btn-primary rounded-pill px-4 py-2 fw-semibold" type="submit">Tìm kiếm</button>
        </form>
    </div>
</div>
<div class="container pb-5">
    <div class="row">
        <div class="col-lg-3">
            <button class="btn bg-white border shadow-sm w-100 d-lg-none mb-3 d-flex justify-content-between align-items-center fw-semibold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#filterSidebar"><span><i class="fa-solid fa-bars me-2"></i>Bộ lọc</span><i class="fa-solid fa-chevron-down"></i></button>
            <div class="collapse d-lg-block" id="filterSidebar">
            <form method="get" class="filter-section">
                <div class="filter-title"><i class="fa-solid fa-sliders text-primary"></i> Bộ lọc</div>
                <input type="hidden" name="keyword" value="<?= gf_h($filters['keyword']) ?>">
                <?php if ($filters['sort'] !== ''): ?><input type="hidden" name="sort" value="<?= gf_h($filters['sort']) ?>"><?php endif; ?>
                <div class="filter-group">
                    <div class="filter-group-title">Khu vực</div>
                    <select name="district_id" id="districtFilter" class="form-select form-select-sm border-0 bg-light" style="font-size:14px">
                        <option value="">Tất cả khu vực</option>
                        <?php foreach ($districts as $d): ?>
                            <option value="<?= (int)$d['id'] ?>" <?= (string)$filters['district_id']===(string)$d['id']?'selected':'' ?>><?= gf_h($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <div class="filter-group-title">Khoảng giá (VNĐ/tháng)</div>
                    <div class="d-flex align-items-center gap-2">
                        <input type="number" name="price_min" class="form-control form-control-sm text-center" placeholder="Từ" value="<?= gf_h((string)$filters['price_min']) ?>" style="font-size:13px">
                        <span class="text-secondary">-</span>
                        <input type="number" name="price_max" class="form-control form-control-sm text-center" placeholder="Đến" value="<?= gf_h((string)$filters['price_max']) ?>" style="font-size:13px">
                    </div>
                </div>
                <div class="filter-group">
                    <div class="filter-group-title">Đánh giá</div>
                    <?php foreach (['' => 'Tất cả', '4.5' => '4.5', '4.0' => '4.0', '3.0' => '3.0'] as $value => $label): $ratingId = $value === '' ? 'rating0' : 'rating' . str_replace('.', '', $value); ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="rating" id="<?= $ratingId ?>" value="<?= gf_h($value) ?>" <?= (string)$filters['rating']===(string)$value?'checked':'' ?>>
                            <label class="form-check-label" for="<?= $ratingId ?>"><?= gf_h($label) ?><?php if ($value !== ''): ?> <i class="fa-solid fa-star text-warning" style="font-size:10px"></i> trở lên<?php endif; ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="filter-group">
                    <div class="filter-group-title">Đặc điểm phòng tập</div>
                    <?php foreach ($figmaFeatures as $feature): ?>
                        <div class="form-check mb-2">
                            <?php if (isset($feature['category_id'])): ?>
                                <input class="form-check-input" type="checkbox" name="category_ids[]" id="<?= gf_h($feature['id']) ?>" value="<?= (int)$feature['category_id'] ?>" <?= in_array((int)$feature['category_id'], $filters['category_ids'], true)?'checked':'' ?>>
                            <?php else: $field = $feature['name'] ?? ''; $checked = $field !== '' && !empty($filters[$field]); ?>
                                <input class="form-check-input" type="checkbox" <?= $field !== '' ? 'name="' . gf_h($field) . '"' : '' ?> value="1" id="<?= gf_h($feature['id']) ?>" <?= $checked?'checked':'' ?> <?= !empty($feature['disabled']) || $field === ''?'disabled title="Cơ sở dữ liệu hiện chưa có trường dữ liệu cho lựa chọn này"':'' ?>>
                            <?php endif; ?>
                            <label class="form-check-label" for="<?= gf_h($feature['id']) ?>"><?= gf_h($feature['label']) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="filter-group">
                    <div class="filter-group-title">Tiện ích</div>
                    <?php foreach ($figmaAmenities as $amenity): ?>
                        <div class="form-check mb-2">
                            <?php if (isset($amenity['amenity_id'])): ?>
                                <input class="form-check-input" type="checkbox" name="amenities[]" id="<?= gf_h($amenity['id']) ?>" value="<?= gf_h($amenity['key']) ?>" <?= in_array($amenity['key'], $filters['amenity_options'], true)?'checked':'' ?>>
                            <?php else: ?>
                                <input class="form-check-input" type="checkbox" id="<?= gf_h($amenity['id']) ?>" disabled title="Cơ sở dữ liệu hiện chưa có trường dữ liệu cho lựa chọn này">
                            <?php endif; ?>
                            <label class="form-check-label" for="<?= gf_h($amenity['id']) ?>"><?= gf_h($amenity['label']) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button id="btnFilter" type="submit" class="btn btn-primary w-100 rounded-3 py-2 fw-semibold mb-2" style="font-size:14px">Lọc kết quả</button>
                <a href="<?= gf_h(gf_url('pages/gyms/search.php')) ?>" class="btn btn-light w-100 rounded-3 py-2 text-secondary fw-semibold" style="font-size:14px">Xóa bộ lọc</a>
            </form>
            </div>
        </div>
        <div class="col-lg-9">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3 pb-3 border-bottom">
                <h2 class="fw-bold text-dark m-0" style="font-size:16px"><?= (int)$gymPage['total'] ?> phòng tập được tìm thấy</h2>
                <form method="get" class="d-flex align-items-center gap-2">
                    <?php foreach ($_GET as $key => $value): if (in_array($key, ['sort', 'page'], true)) continue; if (is_array($value)): foreach ($value as $nestedValue): ?>
                        <input type="hidden" name="<?= gf_h((string)$key) ?>[]" value="<?= gf_h((string)$nestedValue) ?>">
                    <?php endforeach; else: ?>
                        <input type="hidden" name="<?= gf_h((string)$key) ?>" value="<?= gf_h((string)$value) ?>">
                    <?php endif; endforeach; ?>
                    <label for="gymSort" class="text-secondary text-nowrap" style="font-size:14px">Sắp xếp:</label>
                    <select id="gymSort" name="sort" class="form-select form-select-sm border-0 bg-white shadow-sm fw-medium text-dark" style="font-size:14px;width:auto;border-radius:8px" onchange="this.form.submit()">
                        <option value="" <?= $filters['sort']===''?'selected':'' ?>>Phổ biến nhất</option>
                        <option value="rating_desc" <?= $filters['sort']==='rating_desc'?'selected':'' ?>>Đánh giá cao nhất</option>
                        <option value="price_asc" <?= $filters['sort']==='price_asc'?'selected':'' ?>>Giá thấp → cao</option>
                        <option value="price_desc" <?= $filters['sort']==='price_desc'?'selected':'' ?>>Giá cao → thấp</option>
                    </select>
                </form>
            </div>
            <div class="row g-4 mb-5">
                <?php if (!$gyms): ?>
                    <p class="text-secondary">Không có phòng tập phù hợp bộ lọc.</p>
                <?php endif; ?>
                <?php foreach ($gyms as $gym): $isFav = in_array((int)$gym['id'], $favGymIds, true); require dirname(__DIR__, 2) . '/includes/gym_card.php'; endforeach; ?>
            </div>
        </div>
    </div>
    <?php $totalPages = (int)ceil($gymPage['total'] / $gymPage['limit']); if ($totalPages > 1): $currentPage = (int)$gymPage['page']; $prevQuery = $_GET; $prevQuery['page'] = max(1, $currentPage - 1); $nextQuery = $_GET; $nextQuery['page'] = min($totalPages, $currentPage + 1); ?>
        <nav aria-label="Page navigation" class="mb-5"><ul class="pagination justify-content-center">
            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?<?= gf_h(http_build_query($prevQuery)) ?>" <?= $currentPage <= 1 ? 'tabindex="-1" aria-disabled="true"' : '' ?>><i class="fa-solid fa-arrow-left me-2"></i> Trước</a></li>
            <?php for ($page = 1; $page <= $totalPages; $page++): $pageQuery = $_GET; $pageQuery['page'] = $page; ?>
                <li class="page-item <?= $page === $currentPage ? 'active' : '' ?>"><a class="page-link" href="?<?= gf_h(http_build_query($pageQuery)) ?>"><?= $page ?></a></li>
            <?php endfor; ?>
            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>"><a class="page-link" href="?<?= gf_h(http_build_query($nextQuery)) ?>" <?= $currentPage >= $totalPages ? 'tabindex="-1" aria-disabled="true"' : '' ?>>Sau <i class="fa-solid fa-arrow-right ms-2"></i></a></li>
        </ul></nav>
    <?php endif; ?>
</div>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
