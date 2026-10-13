<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
$priceRanges = [
    '' => 'Tất cả mức giá',
    'under_300' => 'Dưới 300.000đ',
    '300_500' => '300.000đ - 500.000đ',
    '500_1000' => '500.000đ - 1.000.000đ',
    'over_1000' => 'Trên 1.000.000đ',
];

$rawPriceRange = trim((string)($_GET['price_range'] ?? $_GET['price'] ?? ''));
if ($rawPriceRange === '500') {
    $rawPriceRange = '300_500';
} elseif ($rawPriceRange === '1000') {
    $rawPriceRange = '500_1000';
}
$selectedPriceRange = array_key_exists($rawPriceRange, $priceRanges) ? $rawPriceRange : '';

$rawMin = isset($_GET['price_min']) && $_GET['price_min'] !== '' ? filter_var($_GET['price_min'], FILTER_VALIDATE_INT) : false;
$rawMax = isset($_GET['price_max']) && $_GET['price_max'] !== '' ? filter_var($_GET['price_max'], FILTER_VALIDATE_INT) : false;
$customPriceMin = $rawMin !== false && $rawMin >= 0 ? (string)$rawMin : '';
$customPriceMax = $rawMax !== false && $rawMax >= 0 ? (string)$rawMax : '';

$filters = [
    'keyword' => trim((string) ($_GET['keyword'] ?? '')),
    'district_id' => $_GET['district_id'] ?? '',
    'rating' => $_GET['rating'] ?? '',
    'category_ids' => is_array($_GET['category_ids'] ?? null) ? array_values(array_map('intval', $_GET['category_ids'])) : [],
    'amenity_options' => is_array($_GET['amenities'] ?? null) ? array_values(array_map('strval', $_GET['amenities'])) : [],
    'amenity_ids' => [],
    'has_pt' => $_GET['has_pt'] ?? '',
    'open_247' => $_GET['open_247'] ?? '',
    'women' => $_GET['women'] ?? '',
    'weight_loss' => $_GET['weight_loss'] ?? '',
    'featured' => $_GET['featured'] ?? '',
    'price_range' => $selectedPriceRange,
    'price_min' => $selectedPriceRange !== '' ? '' : $customPriceMin,
    'price_max' => $selectedPriceRange !== '' ? '' : $customPriceMax,
    'sort' => $_GET['sort'] ?? '',
    'page' => max(1, (int)($_GET['page'] ?? 1)),
    'limit' => 4,
];
$amenityIdMap = ['sauna' => 4, 'steam' => 4, 'dry_steam' => 4, 'wet_steam' => 4, 'locker' => 5, 'parking' => 2, 'shower' => 1, 'ac' => 6, 'water' => 7];
$filters['amenity_ids'] = array_values(array_unique(array_filter(array_map(
    static fn ($option) => $amenityIdMap[$option] ?? 0,
    $filters['amenity_options']
))));

$gymPage = gf_search_gyms_paged($filters);
$gyms = $gymPage['items'];
$districts = gf_list_districts();
$categories = gf_list_categories();
$amenities = gf_list_amenities();

// Xây dựng danh sách Active Filter Chips
$activeChips = [];
if ($selectedPriceRange !== '') {
    $q = $_GET;
    unset($q['price_range'], $q['price'], $q['page']);
    $activeChips[] = [
        'label' => $priceRanges[$selectedPriceRange],
        'remove_url' => '?' . http_build_query($q),
    ];
} elseif ($customPriceMin !== '' || $customPriceMax !== '') {
    $pLabel = '';
    if ($customPriceMin !== '' && $customPriceMax !== '') {
        $pLabel = gf_format_price((int)$customPriceMin, (int)$customPriceMax);
    } elseif ($customPriceMin !== '') {
        $pLabel = 'Từ ' . number_format((int)$customPriceMin, 0, ',', '.') . 'đ';
    } else {
        $pLabel = 'Đến ' . number_format((int)$customPriceMax, 0, ',', '.') . 'đ';
    }
    $q = $_GET;
    unset($q['price_min'], $q['price_max'], $q['page']);
    $activeChips[] = [
        'label' => $pLabel,
        'remove_url' => '?' . http_build_query($q),
    ];
}
if (!empty($filters['district_id'])) {
    $distName = '';
    foreach ($districts as $d) {
        if ((int)$d['id'] === (int)$filters['district_id']) {
            $distName = $d['name'];
            break;
        }
    }
    if ($distName !== '') {
        $q = $_GET;
        unset($q['district_id'], $q['page']);
        $activeChips[] = [
            'label' => $distName,
            'remove_url' => '?' . http_build_query($q),
        ];
    }
}
if (!empty($filters['rating'])) {
    $q = $_GET;
    unset($q['rating'], $q['page']);
    $activeChips[] = [
        'label' => $filters['rating'] . '★ trở lên',
        'remove_url' => '?' . http_build_query($q),
    ];
}
if (!empty($filters['category_ids'])) {
    foreach ($categories as $cat) {
        if (in_array((int)$cat['id'], $filters['category_ids'], true)) {
            $q = $_GET;
            $q['category_ids'] = array_values(array_filter($filters['category_ids'], static fn($id) => $id !== (int)$cat['id']));
            if (empty($q['category_ids'])) unset($q['category_ids']);
            unset($q['page']);
            $activeChips[] = [
                'label' => $cat['name'],
                'remove_url' => '?' . http_build_query($q),
            ];
        }
    }
}
if (!empty($filters['has_pt'])) {
    $q = $_GET;
    unset($q['has_pt'], $q['page']);
    $activeChips[] = [
        'label' => 'Có PT',
        'remove_url' => '?' . http_build_query($q),
    ];
}
if (!empty($filters['open_247'])) {
    $q = $_GET;
    unset($q['open_247'], $q['page']);
    $activeChips[] = [
        'label' => 'Mở cửa 24/7',
        'remove_url' => '?' . http_build_query($q),
    ];
}
if (!empty($filters['women'])) {
    $q = $_GET;
    unset($q['women'], $q['page']);
    $activeChips[] = [
        'label' => 'Dành cho nữ',
        'remove_url' => '?' . http_build_query($q),
    ];
}
if (!empty($filters['amenity_options'])) {
    $amenityNames = [
        'sauna' => 'Xông hơi',
        'steam' => 'Xông hơi',
        'dry_steam' => 'Xông hơi',
        'wet_steam' => 'Xông hơi',
        'locker' => 'Locker',
        'parking' => 'Bãi đỗ xe',
        'shower' => 'Phòng tắm',
        'ac' => 'Điều hòa',
        'water' => 'Nước uống miễn phí',
    ];
    foreach ($filters['amenity_options'] as $opt) {
        $q = $_GET;
        $q['amenities'] = array_values(array_filter($filters['amenity_options'], static fn($o) => $o !== $opt));
        if (empty($q['amenities'])) unset($q['amenities']);
        unset($q['page']);
        $activeChips[] = [
            'label' => $amenityNames[$opt] ?? $opt,
            'remove_url' => '?' . http_build_query($q),
        ];
    }
}
$figmaFeatures = [
    ['id' => 'feature_fitness', 'label' => 'Gym phổ thông', 'category_id' => 1],
    ['id' => 'feature_premium', 'label' => 'Gym cao cấp', 'category_id' => 2],
    ['id' => 'feature_247', 'label' => 'Mở cửa 24/7', 'name' => 'open_247'],
    ['id' => 'feature_pt', 'label' => 'Có huấn luyện viên (PT)', 'name' => 'has_pt'],
    ['id' => 'feature_women', 'label' => 'Dành cho nữ', 'name' => 'women'],
];
$figmaAmenities = [
    ['id' => 'amenity_sauna', 'key' => 'sauna', 'label' => 'Xông hơi', 'amenity_id' => 4],
    ['id' => 'amenity_locker', 'key' => 'locker', 'label' => 'Locker', 'amenity_id' => 5],
    ['id' => 'amenity_parking', 'key' => 'parking', 'label' => 'Bãi đỗ xe', 'amenity_id' => 2],
    ['id' => 'amenity_ac', 'key' => 'ac', 'label' => 'Điều hòa', 'amenity_id' => 6],
    ['id' => 'amenity_shower', 'key' => 'shower', 'label' => 'Phòng tắm', 'amenity_id' => 1],
    ['id' => 'amenity_water', 'key' => 'water', 'label' => 'Nước uống miễn phí', 'amenity_id' => 7],
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
                    <?php foreach ($priceRanges as $key => $label): $pId = $key === '' ? 'price_all' : 'price_' . $key; ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="price_range" id="<?= $pId ?>" value="<?= gf_h($key) ?>" <?= $selectedPriceRange === $key ? 'checked' : '' ?> onchange="if(this.value){document.getElementById('customPriceMin').value='';document.getElementById('customPriceMax').value='';}">
                            <label class="form-check-label" for="<?= $pId ?>"><?= gf_h($label) ?></label>
                        </div>
                    <?php endforeach; ?>
                    <div class="mt-2 pt-2 border-top">
                        <label class="small text-secondary mb-1 d-block" style="font-size:12px">Hoặc khoảng giá tùy chỉnh:</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="number" id="customPriceMin" name="price_min" class="form-control form-control-sm text-center" placeholder="Từ" value="<?= gf_h((string)$customPriceMin) ?>" style="font-size:13px" min="0" step="50000" oninput="document.getElementById('price_all').checked=true">
                            <span class="text-secondary">-</span>
                            <input type="number" id="customPriceMax" name="price_max" class="form-control form-control-sm text-center" placeholder="Đến" value="<?= gf_h((string)$customPriceMax) ?>" style="font-size:13px" min="0" step="50000" oninput="document.getElementById('price_all').checked=true">
                        </div>
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
            <?php if (!empty($activeChips)): ?>
                <div class="mb-4 p-3 bg-white rounded-3 shadow-sm border d-flex flex-wrap gap-2 align-items-center">
                    <span class="text-secondary fw-semibold small me-1"><i class="fa-solid fa-filter me-1 text-primary"></i>Đang lọc:</span>
                    <?php foreach ($activeChips as $chip): ?>
                        <span class="badge bg-primary-light text-primary border border-primary-subtle px-3 py-2 fw-medium rounded-pill d-inline-flex align-items-center gap-2" style="font-size:13px">
                            <?= gf_h($chip['label']) ?>
                            <a href="<?= gf_h($chip['remove_url']) ?>" class="text-primary text-decoration-none" title="Bỏ điều kiện này" style="opacity:0.75;line-height:1"><i class="fa-solid fa-xmark"></i></a>
                        </span>
                    <?php endforeach; ?>
                    <a href="<?= gf_h(gf_url('pages/gyms/search.php' . (!empty($filters['keyword']) ? '?keyword=' . urlencode($filters['keyword']) : ''))) ?>" class="btn btn-sm btn-link text-decoration-none text-danger fw-semibold ms-auto p-0" style="font-size:13px"><i class="fa-solid fa-trash-can me-1"></i>Xóa tất cả</a>
                </div>
            <?php endif; ?>
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
