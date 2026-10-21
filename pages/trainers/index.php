<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
$filters = [
    'keyword' => trim((string)($_GET['keyword'] ?? '')),
    'specialty_id' => $_GET['specialty_id'] ?? '',
    'district_id' => $_GET['district_id'] ?? '',
    'gym_id' => $_GET['gym_id'] ?? '',
    'featured' => $_GET['featured'] ?? '',
    'exp' => $_GET['exp'] ?? '',
    'min_rating' => $_GET['rating'] ?? '',
    'sort' => $_GET['sort'] ?? '',
    'page' => max(1, (int)($_GET['page'] ?? 1)),
    'limit' => 8,
];
$trainerPage = gf_search_trainers_paged($filters);
// Tự động lùi về trang hợp lệ gần nhất nếu trang hiện tại bị rỗng
if ($filters['page'] > 1 && empty($trainerPage['items']) && (int)$trainerPage['total'] > 0) {
    $filters['page'] = max(1, (int) ceil((int)$trainerPage['total'] / $filters['limit']));
    $trainerPage = gf_search_trainers_paged($filters);
}
$trainers = $trainerPage['items'];
$specialties = gf_list_specialties();
$districts = gf_list_districts();
$gymOptions = gf_search_gyms(['limit' => 100]);
$user = gf_current_user();
$pageTitle = 'Huấn luyện viên - GYMFINDER';
$bodyClass = 'bg-light';
require dirname(__DIR__, 2) . '/includes/layout_head.php';
require dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="text-center gf-page-hero" style="background:linear-gradient(135deg,#f0fdfa 0%,#fff 100%);border-bottom:1px solid #e2e8f0;">
    <div class="container">
        <h1 class="hero-title text-uppercase">ĐỘI NGŨ <span class="text-primary position-relative">HUẤN LUYỆN VIÊN
            <svg class="position-absolute w-100 bottom-0 start-0 translate-middle-y text-primary opacity-50" style="height:10px" viewBox="0 0 100 10" preserveAspectRatio="none"><path d="M0 5 Q 50 10 100 5" stroke="currentColor" stroke-width="4" fill="transparent" /></svg>
        </span> TẠI CÁC PHÒNG TẬP</h1>
        <p class="text-secondary mb-5" style="font-size:16px">Khám phá các huấn luyện viên giàu kinh nghiệm đang làm việc tại các hệ thống phòng tập để giúp bạn đạt hiệu quả tập luyện tốt nhất.</p>
        <form method="get" class="bg-white rounded-4 p-4 shadow-sm mx-auto border gf-filter-panel" style="max-width:1100px;">
            <?php if ($filters['sort'] !== ''): ?><input type="hidden" name="sort" value="<?= gf_h($filters['sort']) ?>"><?php endif; ?>
            <div class="d-flex align-items-center bg-light rounded-pill px-3 py-2 mb-4 border">
                <i class="fa-solid fa-magnifying-glass text-secondary mx-2"></i>
                <input class="form-control border-0 shadow-none bg-transparent px-2" name="keyword" value="<?= gf_h($filters['keyword']) ?>" placeholder="Tìm kiếm tên, chuyên môn...">
            </div>
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4 col-xl gf-filter-field">
                    <label for="filter-specialty">Chuyên môn</label>
                    <div class="gf-select-wrap">
                        <select id="filter-specialty" name="specialty_id" class="form-select gf-filter-select">
                            <option value="">Tất cả</option>
                            <?php foreach ($specialties as $s): ?>
                                <option value="<?= (int)$s['id'] ?>" <?= (string)$filters['specialty_id']===(string)$s['id']?'selected':'' ?>><?= gf_h($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl gf-filter-field">
                    <label for="filter-district">Khu vực</label>
                    <div class="gf-select-wrap">
                        <select id="filter-district" name="district_id" class="form-select gf-filter-select">
                            <option value="">Tất cả</option>
                            <?php foreach ($districts as $d): ?>
                                <option value="<?= (int)$d['id'] ?>" <?= (string)$filters['district_id']===(string)$d['id']?'selected':'' ?>><?= gf_h($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-xl gf-filter-field">
                    <label for="filter-gym">Phòng tập</label>
                    <div class="gf-select-wrap">
                        <select id="filter-gym" name="gym_id" class="form-select gf-filter-select">
                            <option value="">Tất cả</option>
                            <?php foreach ($gymOptions as $g): ?>
                                <option value="<?= (int)$g['id'] ?>" data-district-id="<?= (int)$g['district_id'] ?>" <?= (string)$filters['gym_id']===(string)$g['id']?'selected':'' ?>><?= gf_h($g['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-6 col-md-6 col-xl gf-filter-field">
                    <label for="filter-exp">Kinh nghiệm</label>
                    <div class="gf-select-wrap">
                        <select id="filter-exp" name="exp" class="form-select gf-filter-select">
                            <option value="">Tất cả</option>
                            <option value="0-2" <?= in_array($filters['exp'], ['1-2', '0-2'], true)?'selected':'' ?>>0–2 năm</option>
                            <option value="3-5" <?= $filters['exp']==='3-5'?'selected':'' ?>>3–5 năm</option>
                            <option value="6plus" <?= $filters['exp']==='6plus'?'selected':'' ?>>6+ năm</option>
                        </select>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-xl gf-filter-field">
                    <label>Đánh giá</label>
                    <?php
                    $ratingFilterName = 'rating';
                    $ratingFilterValue = $filters['min_rating'];
                    $ratingFilterId = 'trainerRatingFilter';
                    require dirname(__DIR__, 2) . '/includes/rating_star_filter.php';
                    ?>
                </div>
            </div>
            <div class="d-flex flex-column flex-sm-row justify-content-center justify-content-md-end gap-2 gap-sm-3 pt-2 border-top">
                <a class="btn btn-light text-secondary rounded-pill px-4 py-2 fw-semibold border shadow-sm mt-3 w-100 w-sm-auto" href="<?= gf_h(gf_url('pages/trainers/index.php')) ?>">Xóa bộ lọc</a>
                <button class="btn btn-primary rounded-pill px-5 py-2 fw-semibold shadow-sm mt-3 w-100 w-sm-auto">Áp dụng lọc</button>
            </div>
        </form>
    </div>
</div>
<div class="container py-5">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4 pb-3 border-bottom">
        <p class="text-secondary mb-0"><?= (int)$trainerPage['total'] ?> huấn luyện viên</p>
        <form method="get" class="d-flex align-items-center gap-2">
            <?php foreach ($_GET as $key => $value): if (in_array($key, ['sort', 'page'], true)) continue; if (is_array($value)): foreach ($value as $nestedValue): ?>
                <input type="hidden" name="<?= gf_h((string)$key) ?>[]" value="<?= gf_h((string)$nestedValue) ?>">
            <?php endforeach; else: ?>
                <input type="hidden" name="<?= gf_h((string)$key) ?>" value="<?= gf_h((string)$value) ?>">
            <?php endif; endforeach; ?>
            <label for="trainerSort" class="text-secondary text-nowrap" style="font-size:14px">Sắp xếp</label>
            <div class="gf-select-wrap gf-select-wrap--sort">
                <select id="trainerSort" name="sort" class="form-select gf-filter-select" onchange="this.form.submit()" aria-label="Sắp xếp">
                    <option value="" <?= $filters['sort']===''?'selected':'' ?>>Phổ biến nhất</option>
                    <option value="rating_desc" <?= $filters['sort']==='rating_desc'?'selected':'' ?>>Đánh giá cao nhất</option>
                    <option value="experience_desc" <?= $filters['sort']==='experience_desc'?'selected':'' ?>>Kinh nghiệm nhiều → ít</option>
                    <option value="experience_asc" <?= $filters['sort']==='experience_asc'?'selected':'' ?>>Kinh nghiệm ít → nhiều</option>
                    <option value="name_asc" <?= $filters['sort']==='name_asc'?'selected':'' ?>>Tên A → Z</option>
                </select>
            </div>
        </form>
    </div>
    <div class="row g-4">
        <?php if (!$trainers): ?>
            <div class="col-12 text-center py-5">
                <div class="text-secondary mb-3"><i class="fa-solid fa-users-slash" style="font-size:48px"></i></div>
                <h4 class="fw-bold text-dark">Không tìm thấy huấn luyện viên</h4>
                <p class="text-secondary mb-4">Vui lòng thay đổi từ khóa hoặc bộ lọc của bạn.</p>
                <a class="btn btn-outline-primary rounded-pill px-4" href="<?= gf_h(gf_url('pages/trainers/index.php')) ?>">Xóa bộ lọc</a>
            </div>
        <?php endif; ?>
        <?php foreach ($trainers as $trainer): $isFav = $user && gf_is_fav((int)$user['id'], 'trainer', (int)$trainer['id']); require dirname(__DIR__, 2).'/includes/trainer_card.php'; endforeach; ?>
    </div>
    <?= gf_render_user_pagination((int)$trainerPage['page'], (int)$trainerPage['limit'], (int)$trainerPage['total'], $_GET) ?>
</div>
<script src="<?= gf_h(gf_url('assets/js/rating-filter.js')) ?>" defer></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const district = document.getElementById('filter-district');
    const gym = document.getElementById('filter-gym');
    if (!district || !gym) return;

    const filterGymsByDistrict = () => {
        const districtId = district.value;
        const selected = gym.selectedOptions[0];
        for (const option of gym.options) {
            if (!option.value) {
                option.hidden = false;
                continue;
            }
            option.hidden = districtId !== '' && option.dataset.districtId !== districtId;
        }
        if (selected?.value && selected.hidden) gym.value = '';
    };

    district.addEventListener('change', filterGymsByDistrict);
    filterGymsByDistrict();
});
</script>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
