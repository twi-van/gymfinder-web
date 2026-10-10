<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
$id = (int) ($_GET['id'] ?? 0);
$gym = gf_get_gym($id);
if (!$gym) {
    http_response_code(404);
    $pageTitle = 'Không tìm thấy phòng tập';
    require dirname(__DIR__, 2) . '/includes/layout_head.php';
    require dirname(__DIR__, 2) . '/includes/header.php';
    echo '<div class="container py-5"><h2>Không tìm thấy phòng tập</h2></div>';
    require dirname(__DIR__, 2) . '/includes/footer.php';
    exit;
}
$user = gf_current_user();
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    if (!$user) {
        gf_redirect(gf_url('pages/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'])));
    }
    $res = gf_add_review((int)$user['id'], 'gym', $id, (int)($_POST['rating'] ?? 5), (string)$_POST['comment']);
    $msg = $res['ok'] ? 'Đánh giá đã gửi và chờ quản trị viên duyệt.' : $res['error'];
}
$reviews = gf_gym_reviews($id);
$trainers = gf_search_trainers([]);
$gymTrainers = array_values(array_filter($trainers, static fn ($t) => (int)$t['gym_id'] === $id));
$related = array_slice(array_filter(gf_search_gyms(['district_id' => $gym['district_id']]), static fn ($g) => (int)$g['id'] !== $id), 0, 4);
$isFav = $user && gf_is_fav((int)$user['id'], 'gym', $id);
$images = $gym['images'] ?? [];
$pageTitle = $gym['name'] . ' - GYMFINDER';
$bodyClass = 'bg-light';
require dirname(__DIR__, 2) . '/includes/layout_head.php';
require dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="container py-4">
    <nav class="mb-4"><a href="<?= gf_h(gf_url('index.php')) ?>">Trang chủ</a> / <a href="<?= gf_h(gf_url('pages/gyms/search.php')) ?>">Phòng tập</a> / <strong><?= gf_h($gym['name']) ?></strong></nav>
    <?php if ($msg): ?><div class="alert alert-info"><?= gf_h($msg) ?></div><?php endif; ?>
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <img src="<?= gf_h($gym['cover']) ?>" class="w-100 rounded-4" style="height:400px;object-fit:cover;" alt="">
        </div>
        <div class="col-lg-4">
            <div class="row g-3">
                <?php foreach ($images as $img): ?>
                    <div class="col-6"><img src="<?= gf_h($img['image_url']) ?>" class="w-100 rounded-3" style="height:120px;object-fit:cover;" alt=""></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="bg-white rounded-4 p-4 shadow-sm mb-4">
                <div class="d-flex justify-content-between">
                    <div>
                        <?php foreach ($gym['categories'] as $c): ?>
                            <span class="badge bg-primary-light text-primary mb-2"><?= gf_h($c['name']) ?></span>
                        <?php endforeach; ?>
                        <h1 class="fw-bold"><?= gf_h($gym['name']) ?></h1>
                        <div class="mb-2"><i class="fa-solid fa-star text-warning"></i> <strong><?= gf_h((string)$gym['avg_rating']) ?></strong> (<?= (int)$gym['review_count'] ?> đánh giá)</div>
                        <div class="text-secondary"><i class="fa-solid fa-location-dot text-primary me-2"></i><?= gf_h($gym['address']) ?></div>
                    </div>
                    <form method="post" action="<?= gf_h(gf_url('pages/user/favorite_toggle.php')) ?>" class="js-favorite-toggle" data-api-url="<?= gf_h(gf_url('api/favorites')) ?>" data-login-url="<?= gf_h(gf_url('pages/auth/login.php')) ?>" data-favorited="<?= $isFav ? '1' : '0' ?>">
                        <?= gf_csrf_field() ?>
                        <input type="hidden" name="target_type" value="gym"><input type="hidden" name="target_id" value="<?= $id ?>">
                        <input type="hidden" name="redirect" value="<?= gf_h($_SERVER['REQUEST_URI']) ?>">
                        <button class="btn btn-light border rounded-circle" type="submit" style="width:48px;height:48px;" aria-label="Yêu thích phòng tập" aria-pressed="<?= $isFav ? 'true' : 'false' ?>"><i class="fa-<?= $isFav?'solid text-danger':'regular' ?> fa-heart"></i></button>
                    </form>
                </div>
                <div class="bg-light p-3 rounded-3 mt-4 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-secondary">Khoảng giá tham khảo</div>
                        <div class="fw-bold fs-5"><?= gf_h($gym['price_label']) ?></div>
                    </div>
                    <a class="btn btn-primary" href="tel:<?= gf_h($gym['phone'] ?? '') ?>"><i class="fa-solid fa-phone me-2"></i>Liên hệ</a>
                </div>
            </div>
            <div class="bg-white rounded-4 p-4 shadow-sm mb-4">
                <h3 class="fw-bold">Giới thiệu</h3>
                <p class="text-secondary mb-0"><?= gf_h($gym['description']) ?></p>
            </div>
            <div class="bg-white rounded-4 p-4 shadow-sm mb-4">
                <h3 class="fw-bold">Huấn luyện viên tại phòng tập</h3>
                <div class="row g-3">
                    <?php foreach ($gymTrainers as $trainer): $isFav = $user && gf_is_fav((int)$user['id'], 'trainer', (int)$trainer['id']); require dirname(__DIR__, 2) . '/includes/trainer_card.php'; endforeach; ?>
                    <?php if (!$gymTrainers): ?><p class="text-secondary">Chưa có HLV công khai.</p><?php endif; ?>
                </div>
            </div>
            <div class="bg-white rounded-4 p-4 shadow-sm mb-4">
                <h3 class="fw-bold">Đánh giá từ hội viên</h3>
                <?php foreach ($reviews as $r): ?>
                    <div class="border-bottom py-3">
                        <strong><?= gf_h($r['full_name']) ?></strong>
                        <span class="text-warning ms-2"><?= str_repeat('★', (int)$r['rating']) ?></span>
                        <p class="text-secondary mb-0 mt-1"><?= gf_h($r['comment']) ?></p>
                    </div>
                <?php endforeach; ?>
                <?php if ($user): ?>
                    <form method="post" class="mt-4">
                        <?= gf_csrf_field() ?>
                        <label class="form-label fw-semibold">Viết đánh giá</label>
                        <select name="rating" class="form-select mb-2" style="max-width:160px;">
                            <?php for ($i=5;$i>=1;$i--): ?><option value="<?= $i ?>"><?= $i ?> sao</option><?php endfor; ?>
                        </select>
                        <textarea name="comment" class="form-control mb-2" rows="3" required placeholder="Chia sẻ trải nghiệm..."></textarea>
                        <button class="btn btn-primary">Gửi đánh giá</button>
                    </form>
                <?php else: ?>
                    <a class="btn btn-outline-primary mt-3" href="<?= gf_h(gf_url('pages/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']))) ?>">Đăng nhập để đánh giá</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="bg-white rounded-4 p-4 shadow-sm">
                <h3 class="fw-bold mb-3">Thông tin phòng tập</h3>
                <p class="d-flex justify-content-between"><span class="text-secondary">Giờ hoạt động</span><strong><?= gf_h($gym['opening_hours']) ?></strong></p>
                <p class="d-flex justify-content-between"><span class="text-secondary">Khu vực</span><strong><?= gf_h($gym['district_name']) ?></strong></p>
                <h5 class="fw-bold mt-4">Tiện ích</h5>
                <ul class="list-unstyled">
                    <?php foreach ($gym['amenities'] as $a): ?>
                        <li class="mb-2"><i class="fa-solid <?= gf_h($a['icon'] ?: 'fa-check') ?> text-primary me-2"></i><?= gf_h($a['name']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <h3 class="fw-bold mt-5 mb-3">Có thể bạn cũng quan tâm</h3>
    <div class="row g-4">
        <?php foreach ($related as $gym): $isFav = $user && gf_is_fav((int)$user['id'], 'gym', (int)$gym['id']); require dirname(__DIR__, 2) . '/includes/gym_card.php'; endforeach; ?>
    </div>
</div>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
