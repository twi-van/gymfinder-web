<?php
require_once __DIR__ . '/backend/bootstrap.php';
$user = gf_current_user();
$favGymIds = [];
if ($user) {
    $favs = gf_user_favorites((int) $user['id']);
    $favGymIds = array_map(static fn ($g) => (int) $g['id'], $favs['gyms']);
    $favTrainerIds = array_map(static fn ($t) => (int) $t['id'], $favs['trainers']);
} else {
    $favTrainerIds = [];
}
$featuredGyms = array_slice(gf_search_gyms(['featured' => 1, 'limit' => 20]), 0, 4);
if (count($featuredGyms) < 4) {
    $featuredGyms = array_slice(gf_search_gyms(['limit' => 20]), 0, 4);
}
$featuredTrainers = array_slice(gf_search_trainers(['featured' => 1, 'limit' => 20]), 0, 3);
if (count($featuredTrainers) < 3) {
    $featuredTrainers = array_slice(gf_search_trainers(['limit' => 20]), 0, 3);
}
$districts = gf_list_districts();
$pageTitle = 'GYMFINDER - Tìm kiếm phòng tập phù hợp';
require __DIR__ . '/includes/layout_head.php';
require __DIR__ . '/includes/header.php';
?>
<section class="hero-section text-center position-relative" style="padding: 4rem 0 3.5rem 0;">
    <div class="container position-relative z-1">
        <div class="d-inline-flex align-items-center gap-2 px-3 py-1 bg-primary-light text-primary rounded-pill fw-semibold small mb-3">
            <i class="fa-solid fa-location-dot"></i> Tìm kiếm phòng tập tại TP.HCM
        </div>
        <h1 class="hero-title" style="font-size: 2.6rem; margin-bottom: 1rem;">
            TÌM KIẾM <span class="text-primary position-relative">
                PHÒNG GYM PHÙ HỢP
                <svg class="position-absolute w-100 bottom-0 start-0 translate-middle-y text-primary opacity-50"
                    style="height: 10px;" viewBox="0 0 100 10" preserveAspectRatio="none">
                    <path d="M0 5 Q 50 10 100 5" stroke="currentColor" stroke-width="4" fill="transparent" />
                </svg>
            </span> CHO<br class="d-none d-md-block"> HÀNH TRÌNH CỦA BẠN
        </h1>
        <p class="hero-subtitle mx-auto" style="max-width: 600px; margin-bottom: 1.5rem;">
            Tìm kiếm và so sánh hàng trăm phòng tập gym dựa trên vị trí, giá gói tập, tiện ích, bộ môn và đội ngũ huấn luyện viên.
        </p>
        <div class="search-box mx-auto" style="max-width: 1050px;">
            <form action="<?= gf_h(gf_url('pages/gyms/search.php')) ?>" method="get" class="d-flex flex-column flex-md-row align-items-center gap-2 gap-md-0">
                <div class="flex-grow-1 d-flex align-items-center px-3 w-100">
                    <i class="fa-solid fa-magnifying-glass text-secondary"></i>
                    <input type="text" class="form-control" name="keyword" placeholder="Nhập tên phòng tập, bộ môn (VD: Boxing, Yoga...)">
                </div>
                <div class="search-divider d-none d-md-block" style="height: 24px;"></div>
                <div class="d-flex align-items-center px-3 w-100" style="max-width: 200px;">
                    <i class="fa-solid fa-location-dot text-secondary me-2"></i>
                    <select class="form-select px-0" name="district_id">
                        <option value="">Khu vực / Quận</option>
                        <?php foreach ($districts as $d): ?>
                            <option value="<?= (int)$d['id'] ?>"><?= gf_h($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="search-divider d-none d-md-block" style="height: 24px;"></div>
                <div class="d-flex align-items-center px-3 w-100" style="max-width: 200px;">
                    <i class="fa-solid fa-coins text-secondary me-2"></i>
                    <select class="form-select px-0" name="price">
                        <option value="">Khoảng giá</option>
                        <option value="500">Dưới 500k</option>
                        <option value="1000">500k - 1tr</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center gap-2">
                    <i class="fa-solid fa-magnifying-glass"></i> Tìm kiếm
                </button>
            </form>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="section-label"><i class="fa-solid fa-gem me-1"></i> CƠ SỞ ĐƯỢC ĐÁNH GIÁ CAO</div>
        <h2 class="section-title">PHÒNG TẬP NỔI BẬT</h2>
        <p class="section-subtitle">Các phòng tập và câu lạc bộ thể hình hàng đầu được cộng đồng đánh giá tích cực.</p>
        <div class="row g-4">
            <?php foreach ($featuredGyms as $gym): $isFav = in_array((int)$gym['id'], $favGymIds, true); require __DIR__ . '/includes/gym_card.php'; endforeach; ?>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="section-label"><i class="fa-solid fa-user-tie me-1"></i> HUẤN LUYỆN VIÊN</div>
        <h2 class="section-title">Đội ngũ huấn luyện viên</h2>
        <p class="section-subtitle">Khám phá thông tin các huấn luyện viên và tìm người phù hợp với mục tiêu tập luyện của bạn.</p>
        <div class="row g-4">
            <?php foreach ($featuredTrainers as $trainer): $isFav = in_array((int)$trainer['id'], $favTrainerIds, true); require __DIR__ . '/includes/trainer_card.php'; endforeach; ?>
        </div>
        <div class="text-center mt-5">
            <a href="<?= gf_h(gf_url('pages/trainers/index.php')) ?>" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-semibold">
                Xem tất cả Huấn luyện viên <i class="fa-solid fa-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<section class="py-5" id="map">
    <div class="container">
        <div class="map-section">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <div class="section-label"><i class="fa-solid fa-gem me-1"></i> BẢN ĐỒ VỊ TRÍ</div>
                    <h2 class="section-title">Bản đồ phòng tập trực quan</h2>
                    <p class="text-secondary mb-4">
                        Tích hợp công nghệ bản đồ hiện đại giúp bạn nhanh chóng tra cứu khoảng cách, đường đi và vị
                        trí các phòng gym xung quanh khu vực sinh sống.
                    </p>
                    <ul class="list-unstyled mb-4">
                        <li class="d-flex align-items-start mb-3">
                            <i class="fa-solid fa-circle-check text-primary mt-1 me-3 fs-5"></i>
                            <span class="text-secondary">Tìm kiếm phòng tập dễ dàng theo bán kính và quận/huyện.</span>
                        </li>
                        <li class="d-flex align-items-start mb-3">
                            <i class="fa-solid fa-circle-check text-primary mt-1 me-3 fs-5"></i>
                            <span class="text-secondary">Tích hợp xem nhanh bảng giá, số sao ngay trên bản đồ.</span>
                        </li>
                    </ul>
                    <button class="btn btn-primary rounded-pill px-4 py-2 btn-detail-auth" data-url="<?= gf_h(gf_url('index.php#map')) ?>">
                        <i class="fa-regular fa-map me-2"></i> Khám phá bản đồ
                    </button>
                </div>
                <div class="col-lg-6">
                    <div class="map-placeholder bg-light rounded-4 d-flex flex-column align-items-center justify-content-center text-secondary border shadow-sm"
                        style="height: 400px; width: 100%;">
                        <i class="fa-solid fa-map-location-dot mb-3" style="font-size: 48px; color: var(--primary-color);"></i>
                        <h5 class="fw-bold text-dark">Bản đồ TP.HCM</h5>
                        <span class="small">(Chờ tích hợp API Bản đồ)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
