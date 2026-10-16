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
$msgType = 'info';
$myReview = $user ? gf_my_review_row((int)$user['id'], 'gym', $id) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['comment']) || isset($_POST['action']))) {
    gf_require_csrf();
    if (!$user) {
        gf_redirect(gf_url('pages/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'])));
    }
    $action = $_POST['action'] ?? 'create_review';
    if ($action === 'create_review') {
        $res = gf_create_review((int)$user['id'], 'gym', $id, (int)($_POST['rating'] ?? 5), (string)$_POST['comment']);
        if ($res['ok']) {
            $msg = 'Đánh giá của bạn đã gửi và đang chờ quản trị viên duyệt.';
            $msgType = 'success';
        } else {
            $msg = $res['error'] ?? 'Lỗi khi gửi đánh giá.';
            $msgType = 'danger';
        }
    } elseif ($action === 'edit_review' && $myReview) {
        try {
            gf_patch_review_owner((int)$myReview['id'], [
                'rating' => (int)($_POST['rating'] ?? 5),
                'comment' => (string)($_POST['comment'] ?? ''),
            ], $user);
            $msg = 'Đã cập nhật đánh giá của bạn (chuyển sang trạng thái chờ duyệt lại).';
            $msgType = 'success';
        } catch (\Throwable $e) {
            $msg = 'Không thể cập nhật đánh giá. Vui lòng kiểm tra lại.';
            $msgType = 'danger';
        }
    } elseif ($action === 'delete_review' && $myReview) {
        try {
            gf_delete_review_owner((int)$myReview['id'], $user);
            $msg = 'Đã xóa đánh giá của bạn thành công.';
            $msgType = 'success';
        } catch (\Throwable $e) {
            $msg = 'Không thể xóa đánh giá.';
            $msgType = 'danger';
        }
    }
    $myReview = gf_my_review_row((int)$user['id'], 'gym', $id);
    $gym = gf_get_gym($id);
}
$reviews = gf_gym_reviews($id);
$reviewLimit = 8;
$reviewPage = max(1, (int)($_GET['r_page'] ?? 1));
$totalReviews = count($reviews);
if ($reviewPage > 1 && $reviewPage > (int)ceil($totalReviews / $reviewLimit)) {
    $reviewPage = max(1, (int)ceil($totalReviews / $reviewLimit));
}
$pagedReviews = array_slice($reviews, ($reviewPage - 1) * $reviewLimit, $reviewLimit);
require_once dirname(__DIR__, 2) . '/includes/trainer_gyms_ui.php';
$trainers = gf_search_trainers([]);
$gymTrainers = array_values(array_filter(
    $trainers,
    static fn ($t) => gf_ui_trainer_linked_to_gym($t, $id)
));
$related = array_slice(array_filter(gf_search_gyms(['district_id' => $gym['district_id']]), static fn ($g) => (int)$g['id'] !== $id), 0, 4);
$isFav = $user && gf_is_fav((int)$user['id'], 'gym', $id);
$images = $gym['images'] ?? [];
$pageTitle = $gym['name'] . ' - GYMFINDER';
$bodyClass = 'bg-light';
require dirname(__DIR__, 2) . '/includes/layout_head.php';
require dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= gf_h(gf_url('index.php')) ?>" class="text-secondary text-decoration-none"><i class="fa-solid fa-house me-1"></i>Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="<?= gf_h(gf_url('pages/gyms/search.php')) ?>" class="text-secondary text-decoration-none">Phòng tập</a></li>
        <li class="breadcrumb-item active text-dark fw-semibold"><?= gf_h($gym['name']) ?></li>
    </ol></nav>
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <img src="<?= gf_h($gym['cover']) ?>" class="w-100 rounded-4 gf-detail-cover" alt="<?= gf_h($gym['name']) ?>" onerror="this.onerror=null;this.src='<?= gf_h(gf_cover_image(null, (int)$gym['id'])) ?>';">
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
                    <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold btn-global-contact"
                        data-type="gym"
                        data-type-label="Phòng tập thể hình"
                        data-name="<?= gf_h($gym['name']) ?>"
                        data-avatar="<?= gf_h($gym['cover']) ?>"
                        data-subtitle="<?= gf_h($gym['address'] ?? ($gym['district_name'] ?? '')) ?>"
                        data-phone="<?= gf_h($gym['phone'] ?? '') ?>">
                        <i class="fa-solid fa-phone me-2"></i>Liên hệ
                    </button>
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
            <div class="bg-white rounded-4 p-4 shadow-sm mb-4" id="reviewsSection">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="fw-bold mb-0">Đánh giá từ hội viên</h3>
                    <span class="text-secondary small">(<?= $totalReviews ?> đánh giá đã công khai)</span>
                </div>

                <?php if ($msg): ?>
                    <div class="alert alert-<?= $msgType ?> d-flex align-items-center mb-3 rounded-3 shadow-sm">
                        <i class="fa-solid <?= $msgType === 'success' ? 'fa-circle-check' : ($msgType === 'danger' ? 'fa-circle-exclamation' : 'fa-circle-info') ?> fs-5 me-2"></i>
                        <div><?= gf_h($msg) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($user && $myReview): ?>
                    <!-- KHỐI HIỂN THỊ ĐÁNH GIÁ CỦA CHÍNH USER -->
                    <div class="card border border-primary-subtle bg-light rounded-3 p-3 mb-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary text-white rounded-pill px-3 py-1">Đánh giá của bạn</span>
                                <span class="text-warning fs-6"><?= str_repeat('★', (int)$myReview['rating']) ?> (<?= (int)$myReview['rating'] ?> sao)</span>
                            </div>
                            <div>
                                <?php if ($myReview['status'] === 'approved'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1"><i class="fa-solid fa-circle-check me-1"></i>Đã công khai</span>
                                <?php elseif ($myReview['status'] === 'pending'): ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-1"><i class="fa-solid fa-clock me-1"></i>Chờ duyệt</span>
                                <?php elseif ($myReview['status'] === 'rejected'): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1"><i class="fa-solid fa-circle-xmark me-1"></i>Bị từ chối</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="text-dark mb-2" style="white-space: pre-line;"><?= gf_h($myReview['comment']) ?></p>
                        <?php if ($myReview['status'] === 'rejected' && !empty($myReview['reject_reason'])): ?>
                            <div class="small text-danger bg-danger-subtle p-2 rounded-2 mb-2">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i><strong>Lý do từ chối:</strong> <?= gf_h($myReview['reject_reason']) ?>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex gap-2 justify-content-end pt-2 border-top">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#editGymReviewModal">
                                <i class="fa-solid fa-pen-to-square me-1"></i>Sửa đánh giá
                            </button>
                            <form method="post" class="d-inline js-confirm-submit"
                                data-confirm-title="Xóa đánh giá"
                                data-confirm-message="Bạn có chắc chắn muốn xóa đánh giá của mình không? Thao tác này không thể hoàn tác."
                                data-confirm-label="Xóa đánh giá"
                                data-confirm-danger="1">
                                <?= gf_csrf_field() ?>
                                <input type="hidden" name="action" value="delete_review">
                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                    <i class="fa-solid fa-trash me-1"></i>Xóa
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- MODAL CHỈNH SỬA REVIEW -->
                    <div class="modal fade" id="editGymReviewModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow">
                                <form method="post">
                                    <?= gf_csrf_field() ?>
                                    <input type="hidden" name="action" value="edit_review">
                                    <div class="modal-header border-0 pb-0">
                                        <h5 class="modal-title fw-bold">Chỉnh sửa đánh giá của bạn</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body py-3">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small">Mức độ hài lòng</label>
                                            <select name="rating" class="form-select rounded-3">
                                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                                    <option value="<?= $i ?>" <?= (int)$myReview['rating'] === $i ? 'selected' : '' ?>><?= $i ?> sao</option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small">Nhận xét chi tiết</label>
                                            <textarea name="comment" class="form-control rounded-3" rows="4" required placeholder="Chia sẻ trải nghiệm của bạn..."><?= gf_h($myReview['comment']) ?></textarea>
                                        </div>
                                        <div class="small text-secondary">
                                            <i class="fa-solid fa-circle-info me-1 text-primary"></i>Lưu ý: Sau khi sửa, đánh giá sẽ được gửi để quản trị viên duyệt lại trước khi tiếp tục hiển thị công khai.
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 pt-0">
                                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Lưu thay đổi</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php foreach ($pagedReviews as $r): ?>
                    <div class="border-bottom py-3">
                        <div class="d-flex align-items-center gap-2">
                            <strong><?= gf_h($r['full_name']) ?></strong>
                            <span class="text-warning"><?= str_repeat('★', (int)$r['rating']) ?></span>
                        </div>
                        <p class="text-secondary mb-0 mt-1"><?= gf_h($r['comment']) ?></p>
                    </div>
                <?php endforeach; ?>
                <?php if (!$pagedReviews): ?><p class="text-secondary py-2">Chưa có đánh giá nào được công khai.</p><?php endif; ?>
                <?= gf_render_user_pagination($reviewPage, $reviewLimit, $totalReviews, array_diff_key($_GET, ['r_page' => '']), 'r_page') ?>
                
                <?php if ($user && !$myReview): ?>
                    <form method="post" class="mt-4 pt-3 border-top">
                        <?= gf_csrf_field() ?>
                        <input type="hidden" name="action" value="create_review">
                        <label class="form-label fw-semibold">Viết đánh giá</label>
                        <select name="rating" class="form-select mb-2" style="max-width:160px;">
                            <?php for ($i=5;$i>=1;$i--): ?><option value="<?= $i ?>"><?= $i ?> sao</option><?php endfor; ?>
                        </select>
                        <textarea name="comment" class="form-control mb-2" rows="3" required placeholder="Chia sẻ trải nghiệm..."></textarea>
                        <button class="btn btn-primary rounded-pill px-4">Gửi đánh giá</button>
                    </form>
                <?php elseif (!$user): ?>
                    <div class="mt-4 pt-3 border-top text-center">
                        <a class="btn btn-outline-primary rounded-pill px-4" href="<?= gf_h(gf_url('pages/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']))) ?>">
                            <i class="fa-regular fa-comment-dots me-2"></i>Đăng nhập để đánh giá
                        </a>
                    </div>
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
