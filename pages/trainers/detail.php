<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
$id = (int)($_GET['id'] ?? 0);
$t = gf_get_trainer($id);
if (!$t) {
    http_response_code(404);
    echo 'Không tìm thấy HLV';
    exit;
}
$user = gf_current_user();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    if (!$user) {
        gf_redirect(gf_url('pages/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'])));
    }
    $res = gf_add_review((int)$user['id'], 'trainer', $id, (int)($_POST['rating'] ?? 5), (string)$_POST['comment']);
    $msg = $res['ok'] ? 'Đánh giá đã gửi, chờ duyệt.' : $res['error'];
}
$reviews = gf_trainer_reviews($id);
$isFav = $user && gf_is_fav((int)$user['id'], 'trainer', $id);
$pageTitle = $t['full_name'] . ' - GYMFINDER';
$bodyClass = 'bg-light';
require dirname(__DIR__, 2) . '/includes/layout_head.php';
require dirname(__DIR__, 2) . '/includes/header.php';
?>
<main class="flex-grow-1">
    <div class="container py-4">
        <nav aria-label="breadcrumb" class="mb-4"><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= gf_h(gf_url('index.php')) ?>" class="text-secondary text-decoration-none"><i class="fa-solid fa-house me-1"></i>Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="<?= gf_h(gf_url('pages/trainers/index.php')) ?>" class="text-secondary text-decoration-none">Huấn luyện viên</a></li>
            <li class="breadcrumb-item active text-dark fw-semibold"><?= gf_h($t['full_name']) ?></li>
        </ol></nav>
        <?php if ($msg): ?><div class="alert alert-info"><?= gf_h($msg) ?></div><?php endif; ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <section class="info-card mb-4 position-relative">
                    <form method="post" action="<?= gf_h(gf_url('pages/user/favorite_toggle.php')) ?>" class="js-favorite-toggle position-absolute top-0 end-0 m-3" data-api-url="<?= gf_h(gf_url('api/favorites')) ?>" data-login-url="<?= gf_h(gf_url('pages/auth/login.php')) ?>" data-favorited="<?= $isFav ? '1' : '0' ?>">
                        <?= gf_csrf_field() ?><input type="hidden" name="target_type" value="trainer"><input type="hidden" name="target_id" value="<?= $id ?>"><input type="hidden" name="redirect" value="<?= gf_h($_SERVER['REQUEST_URI']) ?>">
                        <button id="btnDetailFav" class="border-0 bg-transparent text-secondary" type="submit" style="font-size:24px" aria-label="Yêu thích" aria-pressed="<?= $isFav ? 'true' : 'false' ?>"><?= $isFav ? '<i class="fa-solid fa-heart text-danger"></i>' : '<i class="fa-regular fa-heart"></i>' ?></button>
                    </form>
                    <div class="d-flex flex-column flex-md-row gap-4 align-items-center align-items-md-start mb-4">
                        <img src="<?= gf_h($t['avatar']) ?>" alt="<?= gf_h($t['full_name']) ?>" class="rounded-circle object-fit-cover shadow" style="width:150px;height:150px;border:4px solid #fff" onerror="this.src='https://placehold.co/150x150?text=Trainer'">
                        <div class="text-center text-md-start">
                            <span class="badge bg-primary-light text-primary mb-2 rounded-pill px-3 py-1 fw-bold">Huấn luyện viên cá nhân</span>
                            <h1 class="fw-bold mb-2" style="font-size:28px"><?= gf_h($t['full_name']) ?></h1>
                            <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-md-start gap-3 mb-2 small">
                                <span><i class="fa-solid fa-star text-warning me-1"></i><strong><?= gf_h((string)$t['avg_rating']) ?></strong> <span class="text-secondary">(<?= (int)$t['review_count'] ?> đánh giá)</span></span>
                                <span class="text-secondary"><i class="fa-solid fa-location-dot me-1"></i><?= gf_h($t['district_name']) ?></span>
                                <span class="text-secondary"><i class="fa-solid fa-briefcase me-1"></i><?= (int)$t['years_experience'] ?> năm kinh nghiệm</span>
                            </div>
                            <div class="text-primary fw-medium"><?= gf_h($t['specialty_name']) ?></div>
                        </div>
                    </div>
                    <hr class="text-secondary opacity-25 my-4">
                    <h5 class="fw-bold mb-3">Giới thiệu</h5><p class="text-secondary" style="line-height:1.6"><?= gf_h($t['bio'] ?: 'Huấn luyện viên đang cập nhật thông tin giới thiệu.') ?></p>
                    <h5 class="fw-bold mb-3 mt-4">Kinh nghiệm &amp; phong cách huấn luyện</h5><p class="text-secondary" style="line-height:1.6"><?= (int)$t['years_experience'] ?> năm kinh nghiệm trong lĩnh vực <?= gf_h($t['specialty_name']) ?>.</p>
                    <h5 class="fw-bold mb-3 mt-4">Các bộ môn chuyên sâu</h5><span class="badge bg-light text-secondary border me-1"><?= gf_h($t['specialty_name']) ?></span>
                </section>
                <section class="info-card" id="reviews">
                    <h5 class="fw-bold mb-4 d-flex align-items-center justify-content-between">Đánh giá từ học viên <a href="#reviewForm" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold">Viết đánh giá</a></h5>
                    <?php if (!$reviews): ?><p class="text-secondary">Chưa có đánh giá được công khai.</p><?php endif; ?>
                    <?php foreach ($reviews as $r): ?><div class="border-bottom py-3"><div class="d-flex justify-content-between"><strong><?= gf_h($r['full_name']) ?></strong><span class="text-warning"><?= str_repeat('★', (int)$r['rating']) ?></span></div><p class="text-secondary m-0 mt-2"><?= gf_h($r['comment']) ?></p></div><?php endforeach; ?>
                    <?php if ($user): ?><form method="post" class="mt-4" id="reviewForm"><?= gf_csrf_field() ?><label class="form-label fw-semibold">Viết đánh giá</label><select name="rating" class="form-select mb-2" style="max-width:160px"><?php for($i=5;$i>=1;$i--): ?><option value="<?= $i ?>"><?= $i ?> sao</option><?php endfor; ?></select><textarea name="comment" class="form-control mb-2" rows="3" required placeholder="Chia sẻ trải nghiệm..."></textarea><button class="btn btn-primary">Gửi đánh giá</button></form><?php else: ?><a class="btn btn-outline-primary mt-3" href="<?= gf_h(gf_url('pages/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] . '#reviewForm'))) ?>">Đăng nhập để đánh giá</a><?php endif; ?>
                </section>
            </div>
            <aside class="col-lg-4">
                <div class="info-card position-sticky" style="top:100px">
                    <h5 class="fw-bold mb-3">Thông tin liên hệ</h5><p class="text-secondary small mb-4">Kết nối để được tư vấn lộ trình tập luyện cá nhân.</p>
                    <button class="btn btn-primary w-100 rounded-pill py-3 fw-bold mb-3 shadow-sm btn-global-contact" data-phone="<?= gf_h($t['phone'] ?? '') ?>"><i class="fa-solid fa-phone me-2"></i>Liên hệ</button>
                    <hr class="text-secondary opacity-25 my-4"><div class="d-flex align-items-center"><i class="fa-solid fa-shield-halved text-success me-3 fs-4"></i><div><div class="fw-bold small">Thông tin hồ sơ</div><div class="text-secondary" style="font-size:12px">Được cung cấp bởi GYMFINDER</div></div></div>
                </div>
                <div class="info-card mt-4"><h5 class="fw-bold mb-3">Phòng tập đang làm việc</h5><h6 class="fw-bold"><?= gf_h($t['gym_name']) ?></h6><p class="text-secondary small"><i class="fa-solid fa-location-dot me-1"></i><?= gf_h($t['district_name']) ?></p><a href="<?= gf_h(gf_url('pages/gyms/detail.php?id='.(int)$t['gym_id'])) ?>" class="btn btn-outline-primary w-100 rounded-pill">Xem phòng tập</a></div>
            </aside>
        </div>
    </div>
</main>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
