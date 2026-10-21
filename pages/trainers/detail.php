<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/trainer_gyms_ui.php';
$id = (int)($_GET['id'] ?? 0);
$t = gf_get_trainer($id);
if (!$t) {
    http_response_code(404);
    $pageTitle = 'Không tìm thấy HLV';
    $bodyClass = 'bg-light d-flex flex-column min-vh-100';
    require dirname(__DIR__, 2) . '/includes/layout_head.php';
    require dirname(__DIR__, 2) . '/includes/header.php';
    echo '<main class="flex-grow-1"><div class="container py-5"><h2 class="fw-bold">Không tìm thấy HLV</h2><p class="text-secondary">Hồ sơ không tồn tại hoặc đang tạm ẩn.</p><a class="btn btn-outline-primary rounded-pill mt-2" href="' . gf_h(gf_url('pages/trainers/index.php')) . '">Quay lại danh sách HLV</a></div></main>';
    require dirname(__DIR__, 2) . '/includes/footer.php';
    exit;
}
$user = gf_current_user();
$isAdminUser = $user && (($user['role'] ?? '') === 'admin');
$canFavorite = !$isAdminUser; // Guest vẫn thấy tim (soft-gate login); admin ẩn
$canReview = $user && !$isAdminUser;
$msg = '';
$msgType = 'info';
$myReview = $canReview ? gf_my_review_row((int)$user['id'], 'trainer', $id) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['comment']) || isset($_POST['action']))) {
    gf_require_csrf();
    if (!$user) {
        gf_redirect(gf_url('pages/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'])));
    }
    if ($isAdminUser) {
        $msg = 'Tài khoản quản trị không gửi đánh giá hội viên. Hãy dùng tài khoản user để thử.';
        $msgType = 'warning';
    } else {
    $action = $_POST['action'] ?? 'create_review';
    if ($action === 'create_review') {
        $res = gf_create_review((int)$user['id'], 'trainer', $id, (int)($_POST['rating'] ?? 5), (string)$_POST['comment']);
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
    $myReview = gf_my_review_row((int)$user['id'], 'trainer', $id);
    $t = gf_get_trainer($id);
    }
}
$reviews = gf_trainer_reviews($id);
$reviewLimit = 8;
$reviewPage = max(1, (int)($_GET['r_page'] ?? 1));
$totalReviews = count($reviews);
if ($reviewPage > 1 && $reviewPage > (int)ceil($totalReviews / $reviewLimit)) {
    $reviewPage = max(1, (int)ceil($totalReviews / $reviewLimit));
}
$pagedReviews = array_slice($reviews, ($reviewPage - 1) * $reviewLimit, $reviewLimit);
$isFav = $user && $canFavorite && gf_is_fav((int)$user['id'], 'trainer', $id);
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
        <?php if ($msg): ?>
            <div class="alert alert-<?= $msgType ?> d-flex align-items-center mb-4 rounded-3 shadow-sm">
                <i class="fa-solid <?= $msgType === 'success' ? 'fa-circle-check' : ($msgType === 'danger' ? 'fa-circle-exclamation' : 'fa-circle-info') ?> fs-5 me-2"></i>
                <div><?= gf_h($msg) ?></div>
            </div>
        <?php endif; ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <section class="info-card mb-4 position-relative">
                    <?php if ($canFavorite): ?>
                    <form method="post" action="<?= gf_h(gf_url('pages/user/favorite_toggle.php')) ?>" class="js-favorite-toggle position-absolute top-0 end-0 m-3" data-api-url="<?= gf_h(gf_url('api/favorites')) ?>" data-login-url="<?= gf_h(gf_url('pages/auth/login.php')) ?>" data-favorited="<?= $isFav ? '1' : '0' ?>">
                        <?= gf_csrf_field() ?><input type="hidden" name="target_type" value="trainer"><input type="hidden" name="target_id" value="<?= $id ?>"><input type="hidden" name="redirect" value="<?= gf_h($_SERVER['REQUEST_URI']) ?>">
                        <button id="btnDetailFav" class="border-0 bg-transparent text-secondary" type="submit" style="font-size:24px" aria-label="Yêu thích" aria-pressed="<?= $isFav ? 'true' : 'false' ?>"><?= $isFav ? '<i class="fa-solid fa-heart text-danger"></i>' : '<i class="fa-regular fa-heart"></i>' ?></button>
                    </form>
                    <?php endif; ?>
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
                            <?php
                            $detailSpecNames = gf_ui_trainer_specialty_names($t);
                            if ($detailSpecNames === [] && !empty($t['specialty_name'])) {
                                $detailSpecNames = [(string) $t['specialty_name']];
                            }
                            $detailSpecLabel = $detailSpecNames !== [] ? implode(' · ', $detailSpecNames) : '';
                            ?>
                            <?php if ($detailSpecLabel !== ''): ?>
                                <div class="text-primary fw-medium"><?= gf_h($detailSpecLabel) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <hr class="text-secondary opacity-25 my-4">
                    <h5 class="fw-bold mb-3">Giới thiệu</h5><p class="text-secondary" style="line-height:1.6"><?= gf_h($t['bio'] ?: 'Huấn luyện viên đang cập nhật thông tin giới thiệu.') ?></p>
                    <h5 class="fw-bold mb-3 mt-4">Kinh nghiệm &amp; phong cách huấn luyện</h5><p class="text-secondary" style="line-height:1.6"><?= (int)$t['years_experience'] ?> năm kinh nghiệm<?= $detailSpecLabel !== '' ? ' trong lĩnh vực ' . gf_h($detailSpecLabel) : '' ?>.</p>
                    <h5 class="fw-bold mb-3 mt-4">Chuyên môn</h5>
                    <?php if ($detailSpecNames === []): ?>
                        <span class="text-secondary small">Đang cập nhật.</span>
                    <?php else: ?>
                        <?php foreach ($detailSpecNames as $specName): ?>
                            <span class="badge bg-light text-secondary border me-1 mb-1"><?= gf_h($specName) ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </section>
                <section class="info-card" id="reviews">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <h5 class="fw-bold mb-0">Đánh giá từ học viên <span class="text-secondary small fw-normal">(<?= $totalReviews ?> đã công khai)</span></h5>
                        <?php if ($canReview && !$myReview): ?>
                            <a href="#reviewForm" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-semibold">Viết đánh giá</a>
                        <?php endif; ?>
                    </div>

                    <?php if ($canReview && $myReview): ?>
                        <!-- KHỐI ĐÁNH GIÁ CỦA CHÍNH USER -->
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
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#editTrainerReviewModal">
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

                        <!-- MODAL SỬA ĐÁNH GIÁ HLV -->
                        <div class="modal fade" id="editTrainerReviewModal" tabindex="-1" aria-hidden="true">
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
                                                <?php
                                                $ratingFilterName = 'rating';
                                                $ratingFilterValue = (string) (int) $myReview['rating'];
                                                $ratingFilterId = 'editTrainerReviewRating';
                                                $ratingFilterMode = 'rate';
                                                require dirname(__DIR__, 2) . '/includes/rating_star_filter.php';
                                                ?>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold small">Nhận xét chi tiết</label>
                                                <textarea name="comment" class="form-control rounded-3" rows="4" required placeholder="Chia sẻ trải nghiệm học cùng HLV..."><?= gf_h($myReview['comment']) ?></textarea>
                                            </div>
                                            <div class="small text-secondary">
                                                <i class="fa-solid fa-circle-info me-1 text-primary"></i>Lưu ý: Sau khi sửa, đánh giá sẽ được gửi để quản trị viên duyệt lại trước khi hiển thị công khai.
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

                    <?php if (!$pagedReviews): ?><p class="text-secondary py-2">Chưa có đánh giá nào được công khai.</p><?php endif; ?>
                    <?php foreach ($pagedReviews as $r): ?>
                        <div class="border-bottom py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong><?= gf_h($r['full_name']) ?></strong>
                                <span class="text-warning"><?= str_repeat('★', (int)$r['rating']) ?></span>
                            </div>
                            <p class="text-secondary m-0 mt-2"><?= gf_h($r['comment']) ?></p>
                        </div>
                    <?php endforeach; ?>
                    <?= gf_render_user_pagination($reviewPage, $reviewLimit, $totalReviews, array_diff_key($_GET, ['r_page' => '']), 'r_page') ?>
                    
                    <?php if ($canReview && !$myReview): ?>
                        <form method="post" class="mt-4 pt-3 border-top" id="reviewForm">
                            <?= gf_csrf_field() ?>
                            <input type="hidden" name="action" value="create_review">
                            <label class="form-label fw-semibold">Viết đánh giá</label>
                            <div class="mb-2">
                                <?php
                                $ratingFilterName = 'rating';
                                $ratingFilterValue = '5';
                                $ratingFilterId = 'createTrainerReviewRating';
                                $ratingFilterMode = 'rate';
                                require dirname(__DIR__, 2) . '/includes/rating_star_filter.php';
                                ?>
                            </div>
                            <textarea name="comment" class="form-control mb-2" rows="3" required placeholder="Chia sẻ trải nghiệm..."></textarea>
                            <button class="btn btn-primary rounded-pill px-4">Gửi đánh giá</button>
                        </form>
                    <?php elseif ($isAdminUser): ?>
                        <div class="mt-4 pt-3 border-top text-center text-secondary small">
                            <i class="fa-solid fa-shield-halved me-1 text-primary"></i>Admin duyệt đánh giá tại khu vực quản trị — không gửi review hội viên tại đây.
                        </div>
                    <?php elseif (!$user): ?>
                        <div class="mt-4 pt-3 border-top text-center">
                            <a class="btn btn-outline-primary mt-3 rounded-pill px-4" href="<?= gf_h(gf_url('pages/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] . '#reviews'))) ?>">
                                <i class="fa-regular fa-comment-dots me-2"></i>Đăng nhập để đánh giá
                            </a>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
            <aside class="col-lg-4">
                <div class="info-card position-sticky" style="top:100px">
                    <h5 class="fw-bold mb-3">Thông tin liên hệ</h5><p class="text-secondary small mb-4">Kết nối để được tư vấn lộ trình tập luyện cá nhân.</p>
                    <button type="button" class="btn btn-primary w-100 rounded-pill py-3 fw-bold mb-3 shadow-sm btn-global-contact"
                        data-type="trainer"
                        data-type-label="Huấn luyện viên cá nhân"
                        data-name="<?= gf_h($t['full_name']) ?>"
                        data-avatar="<?= gf_h($t['avatar'] ?? '') ?>"
                        data-subtitle="<?= gf_h(($detailSpecLabel !== '' ? $detailSpecLabel : ($t['specialty_name'] ?? '')) . (!empty($t['district_name']) ? ' • ' . $t['district_name'] : '')) ?>"
                        data-phone="<?= gf_h($t['phone'] ?? '') ?>">
                        <i class="fa-solid fa-phone me-2"></i>Liên hệ
                    </button>
                    <hr class="text-secondary opacity-25 my-4"><div class="d-flex align-items-center"><i class="fa-solid fa-shield-halved text-success me-3 fs-4"></i><div><div class="fw-bold small">Thông tin hồ sơ</div><div class="text-secondary" style="font-size:12px">Được cung cấp bởi GYMFINDER</div></div></div>
                </div>
                <?php
                $publicGyms = gf_ui_trainer_public_gyms($t);
                ?>
                <div class="info-card mt-4">
                    <h5 class="fw-bold mb-3">Phòng tập đang làm việc</h5>
                    <?php if ($publicGyms === []): ?>
                        <p class="text-secondary small mb-0">Chưa có phòng tập đang hoạt động được liên kết công khai.</p>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-3">
                            <?php foreach ($publicGyms as $pg): ?>
                                <div class="pb-3 border-bottom border-light">
                                    <h6 class="fw-bold mb-1"><?= gf_h($pg['name']) ?></h6>
                                    <?php if (!empty($pg['district_name']) || !empty($pg['address'])): ?>
                                        <p class="text-secondary small mb-2">
                                            <i class="fa-solid fa-location-dot me-1"></i><?= gf_h($pg['address'] ?: $pg['district_name']) ?>
                                        </p>
                                    <?php endif; ?>
                                    <a href="<?= gf_h(gf_url('pages/gyms/detail.php?id='.(int)$pg['id'])) ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">Xem phòng tập</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </div>
</main>
<script src="<?= gf_h(gf_url('assets/js/rating-filter.js')) ?>" defer></script>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
