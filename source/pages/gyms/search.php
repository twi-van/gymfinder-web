<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
$priceRanges = [
    '' => 'Tất cả',
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
    'category_ids' => is_array($_GET['category_ids'] ?? null) ? array_values(array_filter(array_map('intval', $_GET['category_ids']))) : [],
    'amenity_options' => is_array($_GET['amenities'] ?? null) ? array_values(array_map('strval', $_GET['amenities'])) : [],
    'amenity_ids' => is_array($_GET['amenity_ids'] ?? null) ? array_values(array_filter(array_map('intval', $_GET['amenity_ids']))) : [],
    'feature_ids' => is_array($_GET['feature_ids'] ?? null) ? array_values(array_filter(array_map('intval', $_GET['feature_ids']))) : [],
    'featured' => $_GET['featured'] ?? '',
    'price_range' => $selectedPriceRange,
    'price_min' => $selectedPriceRange !== '' ? '' : $customPriceMin,
    'price_max' => $selectedPriceRange !== '' ? '' : $customPriceMax,
    'sort' => $_GET['sort'] ?? '',
    'page' => max(1, (int)($_GET['page'] ?? 1)),
    'limit' => 8,
];
$districts = gf_list_districts();
$categories = gf_list_categories();
$amenities = gf_list_amenities();
$features = gf_list_features();
$legacyAmenitySlugs = [
    'sauna' => 'xong-hoi',
    'steam' => 'xong-hoi',
    'dry_steam' => 'xong-hoi',
    'wet_steam' => 'xong-hoi',
    'locker' => 'tu-khoa',
    'parking' => 'bai-giu-xe',
    'shower' => 'phong-tam',
    'ac' => 'dieu-hoa',
    'water' => 'nuoc-uong-mien-phi',
];
$amenityIdsBySlug = [];
foreach ($amenities as $amenity) {
    $amenityIdsBySlug[(string) $amenity['slug']] = (int) $amenity['id'];
}
foreach ($filters['amenity_options'] as $option) {
    $slug = $legacyAmenitySlugs[$option] ?? $option;
    if (isset($amenityIdsBySlug[$slug])) {
        $filters['amenity_ids'][] = $amenityIdsBySlug[$slug];
    }
}
$knownAmenityIds = array_map(static fn ($amenity) => (int) $amenity['id'], $amenities);
$filters['amenity_ids'] = array_values(array_unique(array_filter(
    $filters['amenity_ids'],
    static fn ($id) => in_array((int) $id, $knownAmenityIds, true)
)));
$knownCategoryIds = array_map(static fn ($cat) => (int) $cat['id'], $categories);
$filters['category_ids'] = array_values(array_filter(
    $filters['category_ids'],
    static fn ($id) => in_array((int) $id, $knownCategoryIds, true)
));
$featureIdsBySlug = [];
foreach ($features as $feature) {
    $featureIdsBySlug[(string) $feature['slug']] = (int) $feature['id'];
}
// Legacy query flags → feature_ids (catalog).
$legacyFeatureMap = [
    'open_247' => 'open-247',
    'has_pt' => 'has-pt',
    'weight_loss' => 'ho-tro-giam-can',
];
foreach ($legacyFeatureMap as $legacyKey => $slug) {
    if (!empty($_GET[$legacyKey]) && isset($featureIdsBySlug[$slug])) {
        $filters['feature_ids'][] = $featureIdsBySlug[$slug];
    }
}
$knownFeatureIds = array_map(static fn ($feature) => (int) $feature['id'], $features);
$filters['feature_ids'] = array_values(array_unique(array_filter(
    $filters['feature_ids'],
    static fn ($id) => in_array((int) $id, $knownFeatureIds, true)
)));
// Legacy ?women=1 → dùng loại phòng tập "Gym cho nữ" (tránh trùng mục Đặc điểm).
if (!empty($_GET['women'])) {
    foreach ($categories as $cat) {
        if ((string) ($cat['slug'] ?? '') === 'gym-cho-nu') {
            $womenCatId = (int) $cat['id'];
            if (!in_array($womenCatId, $filters['category_ids'], true)) {
                $filters['category_ids'][] = $womenCatId;
            }
            break;
        }
    }
}

$gymPage = gf_search_gyms_paged($filters);
// Tự động lùi về trang hợp lệ gần nhất nếu trang hiện tại bị rỗng
if ($filters['page'] > 1 && empty($gymPage['items']) && (int)$gymPage['total'] > 0) {
    $filters['page'] = max(1, (int) ceil((int)$gymPage['total'] / $filters['limit']));
    $gymPage = gf_search_gyms_paged($filters);
}
$gyms = $gymPage['items'];

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
if (!empty($filters['feature_ids'])) {
    foreach ($features as $feature) {
        $featureId = (int) $feature['id'];
        if (!in_array($featureId, $filters['feature_ids'], true)) {
            continue;
        }
        $q = $_GET;
        $q['feature_ids'] = array_values(array_filter($filters['feature_ids'], static fn ($id) => (int) $id !== $featureId));
        if (empty($q['feature_ids'])) {
            unset($q['feature_ids']);
        }
        unset($q['open_247'], $q['has_pt'], $q['weight_loss'], $q['page']);
        $activeChips[] = [
            'label' => (string) $feature['name'],
            'remove_url' => '?' . http_build_query($q),
        ];
    }
}
if (!empty($filters['amenity_ids'])) {
    foreach ($amenities as $amenity) {
        $amenityId = (int) $amenity['id'];
        if (!in_array($amenityId, $filters['amenity_ids'], true)) {
            continue;
        }
        $q = $_GET;
        $q['amenity_ids'] = array_values(array_filter($filters['amenity_ids'], static fn ($id) => (int) $id !== $amenityId));
        if (empty($q['amenity_ids'])) {
            unset($q['amenity_ids']);
        }
        unset($q['amenities'], $q['page']);
        $activeChips[] = [
            'label' => (string) $amenity['name'],
            'remove_url' => '?' . http_build_query($q),
        ];
    }
}
$user = gf_current_user();
$favGymIds = $user ? array_map(static fn ($g) => (int) $g['id'], gf_user_favorites((int)$user['id'])['gyms']) : [];
$pageTitle = gf_t('Tìm kiếm phòng tập - GYMFINDER');
$bodyClass = 'bg-light';
require dirname(__DIR__, 2) . '/includes/layout_head.php';
require dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="page-title-section gf-page-hero" style="background:linear-gradient(135deg,#f0fdfa 0%,#fff 100%);border-bottom:1px solid #e2e8f0;text-align:center;">
    <div class="container">
        <h1 class="hero-title text-uppercase"><?= gf_h(gf_t('TÌM KIẾM')) ?> <span class="text-primary position-relative"><?= gf_h(gf_t('PHÒNG TẬP')) ?>
            <svg class="position-absolute w-100 bottom-0 start-0 translate-middle-y text-primary opacity-50" style="height:10px" viewBox="0 0 100 10" preserveAspectRatio="none"><path d="M0 5 Q 50 10 100 5" stroke="currentColor" stroke-width="4" fill="transparent"></path></svg>
        </span> <?= gf_h(gf_t('CỦA BẠN')) ?></h1>
        <p class="text-secondary mb-5" style="font-size:16px"><?= gf_h(gf_t('Khám phá và tìm phòng tập phù hợp với nhu cầu của bạn tại TP. Hồ Chí Minh.')) ?></p>
        <form method="get" class="gf-keyword-search bg-white shadow-sm mx-auto d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center gap-2 gap-sm-0 p-2" style="max-width:700px;border:1px solid #f1f5f9;">
            <div class="d-flex align-items-center flex-grow-1 min-w-0">
                <i class="fa-solid fa-magnifying-glass text-secondary ms-2 ms-sm-3 flex-shrink-0"></i>
                <input type="text" name="keyword" class="form-control border-0 shadow-none" value="<?= gf_h($filters['keyword']) ?>" placeholder="<?= gf_h(gf_t('Tìm kiếm tên phòng tập, khu vực...')) ?>">
            </div>
            <button class="btn btn-primary rounded-pill px-4 py-2 fw-semibold flex-shrink-0" type="submit"><?= gf_h(gf_t('Tìm kiếm')) ?></button>
        </form>
    </div>
</div>
<div class="container pb-5">
    <div class="row">
        <div class="col-lg-3">
            <button class="btn bg-white border shadow-sm w-100 d-lg-none mb-3 d-flex justify-content-between align-items-center fw-semibold text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#filterSidebar"><span><i class="fa-solid fa-bars me-2"></i><?= gf_h(gf_t('Bộ lọc')) ?></span><i class="fa-solid fa-chevron-down"></i></button>
            <div class="collapse d-lg-block" id="filterSidebar">
            <form method="get" class="filter-section js-filter-empty-toast">
                <div class="filter-title"><i class="fa-solid fa-sliders text-primary"></i> <?= gf_h(gf_t('Bộ lọc')) ?></div>
                <input type="hidden" name="keyword" value="<?= gf_h($filters['keyword']) ?>">
                <?php if ($filters['sort'] !== ''): ?><input type="hidden" name="sort" value="<?= gf_h($filters['sort']) ?>"><?php endif; ?>
                <div class="filter-group gf-filter-field">
                    <div class="filter-group-title"><?= gf_h(gf_t('Khu vực')) ?></div>
                    <div class="gf-select-wrap gf-select-wrap--sidebar">
                        <select name="district_id" id="districtFilter" class="form-select gf-filter-select" aria-label="<?= gf_h(gf_t('Khu vực')) ?>">
                            <option value=""><?= gf_h(gf_t('Tất cả')) ?></option>
                            <?php foreach ($districts as $d): ?>
                                <option value="<?= (int)$d['id'] ?>" <?= (string)$filters['district_id']===(string)$d['id']?'selected':'' ?>><?= gf_h(gf_place_name((string) $d['name'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="filter-group">
                    <div class="filter-group-title"><?= gf_h(gf_t('Khoảng giá (VNĐ/tháng)')) ?></div>
                    <?php foreach ($priceRanges as $key => $label): $pId = $key === '' ? 'price_all' : 'price_' . $key; ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="price_range" id="<?= $pId ?>" value="<?= gf_h($key) ?>" <?= $selectedPriceRange === $key ? 'checked' : '' ?> onchange="if(this.value){document.getElementById('customPriceMin').value='';document.getElementById('customPriceMax').value='';}">
                            <label class="form-check-label" for="<?= $pId ?>"><?= gf_h(gf_t((string) $label)) ?></label>
                        </div>
                    <?php endforeach; ?>
                    <div class="mt-2 pt-2 border-top">
                        <label class="small text-secondary mb-1 d-block" style="font-size:12px"><?= gf_h(gf_t('Hoặc khoảng giá tùy chỉnh:')) ?></label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="number" id="customPriceMin" name="price_min" class="form-control form-control-sm text-center" placeholder="<?= gf_h(gf_t('Từ')) ?>" value="<?= gf_h((string)$customPriceMin) ?>" style="font-size:13px" min="0" step="50000" oninput="document.getElementById('price_all').checked=true">
                            <span class="text-secondary">-</span>
                            <input type="number" id="customPriceMax" name="price_max" class="form-control form-control-sm text-center" placeholder="<?= gf_h(gf_t('Đến')) ?>" value="<?= gf_h((string)$customPriceMax) ?>" style="font-size:13px" min="0" step="50000" oninput="document.getElementById('price_all').checked=true">
                        </div>
                    </div>
                </div>
                <div class="filter-group">
                    <div class="filter-group-title"><?= gf_h(gf_t('Đánh giá')) ?></div>
                    <?php
                    $ratingFilterName = 'rating';
                    $ratingFilterValue = $filters['rating'];
                    $ratingFilterId = 'gymRatingFilter';
                    require dirname(__DIR__, 2) . '/includes/rating_star_filter.php';
                    ?>
                </div>
                <?php if ($categories): ?>
                <div class="filter-group">
                    <div class="filter-group-title"><?= gf_h(gf_t('Loại phòng tập')) ?></div>
                    <?php foreach ($categories as $cat): ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="category_ids[]" id="category_<?= (int) $cat['id'] ?>" value="<?= (int) $cat['id'] ?>" <?= in_array((int) $cat['id'], $filters['category_ids'], true) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="category_<?= (int) $cat['id'] ?>"><?= gf_h(gf_t((string) $cat['name'])) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php if ($features): ?>
                <div class="filter-group">
                    <div class="filter-group-title"><?= gf_h(gf_t('Đặc điểm phòng tập')) ?></div>
                    <?php foreach ($features as $feature): ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="feature_ids[]" id="feature_<?= (int) $feature['id'] ?>" value="<?= (int) $feature['id'] ?>" <?= in_array((int) $feature['id'], $filters['feature_ids'], true) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="feature_<?= (int) $feature['id'] ?>"><?= gf_h(gf_t((string) $feature['name'])) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php if ($amenities): ?>
                <div class="filter-group">
                    <div class="filter-group-title"><?= gf_h(gf_t('Tiện ích')) ?></div>
                    <?php foreach ($amenities as $amenity): ?>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="amenity_ids[]" id="amenity_<?= (int) $amenity['id'] ?>" value="<?= (int) $amenity['id'] ?>" <?= in_array((int) $amenity['id'], $filters['amenity_ids'], true) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="amenity_<?= (int) $amenity['id'] ?>"><?= gf_h(gf_t((string) $amenity['name'])) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <button id="btnFilter" type="submit" class="btn btn-primary w-100 rounded-3 py-2 fw-semibold mb-2" style="font-size:14px"><?= gf_h(gf_t('Lọc kết quả')) ?></button>
                <a href="<?= gf_h(gf_url('pages/gyms/search.php')) ?>" class="btn btn-light w-100 rounded-3 py-2 text-secondary fw-semibold" style="font-size:14px"><?= gf_h(gf_t('Xóa bộ lọc')) ?></a>
            </form>
            </div>
        </div>
        <div class="col-lg-9">
            <?php if (!empty($activeChips)): ?>
                <div class="mb-4 p-3 bg-white rounded-3 shadow-sm border d-flex flex-wrap gap-2 align-items-center">
                    <span class="text-secondary fw-semibold small me-1"><i class="fa-solid fa-filter me-1 text-primary"></i><?= gf_h(gf_t('Đang lọc:')) ?></span>
                    <?php foreach ($activeChips as $chip): ?>
                        <span class="badge bg-primary-light text-primary border border-primary-subtle px-3 py-2 fw-medium rounded-pill d-inline-flex align-items-center gap-2" style="font-size:13px">
                            <?= gf_h(gf_t((string) $chip['label'])) ?>
                            <a href="<?= gf_h($chip['remove_url']) ?>" class="text-primary text-decoration-none" title="<?= gf_h(gf_t('Bỏ điều kiện này')) ?>" style="opacity:0.75;line-height:1"><i class="fa-solid fa-xmark"></i></a>
                        </span>
                    <?php endforeach; ?>
                    <a href="<?= gf_h(gf_url('pages/gyms/search.php' . (!empty($filters['keyword']) ? '?keyword=' . urlencode($filters['keyword']) : ''))) ?>" class="btn btn-sm btn-link text-decoration-none text-danger fw-semibold ms-sm-auto p-0" style="font-size:13px"><i class="fa-solid fa-trash-can me-1"></i><?= gf_h(gf_t('Xóa tất cả')) ?></a>
                </div>
            <?php endif; ?>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3 pb-3 border-bottom">
                <h2 class="fw-bold text-dark m-0" style="font-size:16px"><?= gf_h(gf_t((int) $gymPage['total'] . ' phòng tập được tìm thấy')) ?></h2>
                <form method="get" class="d-flex align-items-center gap-2">
                    <?php foreach ($_GET as $key => $value): if (in_array($key, ['sort', 'page'], true)) continue; if (is_array($value)): foreach ($value as $nestedValue): ?>
                        <input type="hidden" name="<?= gf_h((string)$key) ?>[]" value="<?= gf_h((string)$nestedValue) ?>">
                    <?php endforeach; else: ?>
                        <input type="hidden" name="<?= gf_h((string)$key) ?>" value="<?= gf_h((string)$value) ?>">
                    <?php endif; endforeach; ?>
                    <label for="gymSort" class="text-secondary text-nowrap" style="font-size:14px"><?= gf_h(gf_t('Sắp xếp')) ?></label>
                    <div class="gf-select-wrap gf-select-wrap--sort">
                        <select id="gymSort" name="sort" class="form-select gf-filter-select" onchange="this.form.submit()" aria-label="<?= gf_h(gf_t('Sắp xếp')) ?>">
                            <option value="" <?= $filters['sort']===''?'selected':'' ?>><?= gf_h(gf_t('Phổ biến nhất')) ?></option>
                            <option value="newest" <?= $filters['sort']==='newest'?'selected':'' ?>><?= gf_h(gf_t('Mới nhất')) ?></option>
                            <option value="rating_desc" <?= $filters['sort']==='rating_desc'?'selected':'' ?>><?= gf_h(gf_t('Đánh giá cao nhất')) ?></option>
                            <option value="price_asc" <?= $filters['sort']==='price_asc'?'selected':'' ?>><?= gf_h(gf_t('Giá thấp → cao')) ?></option>
                            <option value="price_desc" <?= $filters['sort']==='price_desc'?'selected':'' ?>><?= gf_h(gf_t('Giá cao → thấp')) ?></option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="row g-4 mb-5">
                <?php if (!$gyms): ?>
                    <div class="col-12 text-center py-5">
                        <div class="text-secondary mb-3"><i class="fa-solid fa-dumbbell" style="font-size:48px"></i></div>
                        <h4 class="fw-bold text-dark"><?= gf_h(gf_t('Không tìm thấy phòng tập')) ?></h4>
                        <p class="text-secondary mb-4"><?= gf_h(gf_t('Vui lòng thay đổi từ khóa hoặc bộ lọc của bạn.')) ?></p>
                        <a class="btn btn-outline-primary rounded-pill px-4" href="<?= gf_h(gf_url('pages/gyms/search.php')) ?>"><?= gf_h(gf_t('Xóa bộ lọc')) ?></a>
                    </div>
                <?php endif; ?>
                <?php foreach ($gyms as $gym): $isFav = in_array((int)$gym['id'], $favGymIds, true); require dirname(__DIR__, 2) . '/includes/gym_card.php'; endforeach; ?>
            </div>
        </div>
    </div>
    <?= gf_render_user_pagination((int)$gymPage['page'], (int)$gymPage['limit'], (int)$gymPage['total'], $_GET) ?>
</div>
<script src="<?= gf_h(gf_url('assets/js/rating-filter.js')) ?>" defer></script>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
