<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
$id = (int) ($_GET['id'] ?? 0);
$gym = gf_get_gym($id);
if (!$gym) {
    http_response_code(404);
    $pageTitle = 'Không tìm thấy phòng tập';
    require dirname(__DIR__, 2) . '/includes/layout_head.php';
    require dirname(__DIR__, 2) . '/includes/header.php';
    echo '<div class="container py-5"><h2>' . gf_h(gf_t('Không tìm thấy phòng tập')) . '</h2></div>';
    require dirname(__DIR__, 2) . '/includes/footer.php';
    exit;
}
$user = gf_current_user();
$isAdminUser = $user && (($user['role'] ?? '') === 'admin');
$canFavorite = !$isAdminUser; // Guest vẫn thấy tim (soft-gate login); admin ẩn
$canReview = $user && !$isAdminUser;
$msg = '';
$msgType = 'info';
$myReview = $canReview ? gf_my_review_row((int)$user['id'], 'gym', $id) : null;

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
$isFav = $user && $canFavorite && gf_is_fav((int)$user['id'], 'gym', $id);
$images = $gym['images'] ?? [];
$pageTitle = $gym['name'] . ' - GYMFINDER';
$bodyClass = 'bg-light';
require dirname(__DIR__, 2) . '/includes/layout_head.php';
require dirname(__DIR__, 2) . '/includes/header.php';
?>
<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4"><ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= gf_h(gf_url('index.php')) ?>" class="text-secondary text-decoration-none"><i class="fa-solid fa-house me-1"></i><?= gf_h(gf_t('Trang chủ')) ?></a></li>
        <li class="breadcrumb-item"><a href="<?= gf_h(gf_url('pages/gyms/search.php')) ?>" class="text-secondary text-decoration-none"><?= gf_h(gf_t('Phòng tập')) ?></a></li>
        <li class="breadcrumb-item active text-dark fw-semibold"><?= gf_h($gym['name']) ?></li>
    </ol></nav>
    <?php
    $coverUrl = (string) $gym['cover'];
    $galleryItems = [];
    foreach ($images as $img) {
        $raw = (string) ($img['image_url'] ?? '');
        $resolved = gf_resolve_image_url($raw) ?? $raw;
        if ($resolved === '') {
            continue;
        }
        $galleryItems[] = [
            'url' => $resolved,
            'caption' => trim((string) ($img['caption'] ?? '')),
        ];
    }
    ?>
    <?php
    $galleryEsc = static fn (string $u): string => htmlspecialchars($u, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $galleryLarge = static function (string $url): string {
        // Unsplash demo: ưu tiên bản lớn khi xem hero / lightbox
        if (str_contains($url, 'images.unsplash.com') && str_contains($url, 'w=800')) {
            return str_replace('w=800', 'w=1200', $url);
        }
        return $url;
    };
    // Thumbnail: cover + gallery, tối đa 6 ô (2×3) để không để trống góc lưới.
    $thumbItems = [['url' => $coverUrl, 'caption' => '', 'is_cover' => true]];
    foreach ($galleryItems as $gItem) {
        $thumbItems[] = [
            'url' => $gItem['url'],
            'caption' => $gItem['caption'],
            'is_cover' => false,
        ];
    }
    $thumbExtra = max(0, count($thumbItems) - 6);
    $thumbItems = array_slice($thumbItems, 0, 6);
    ?>
    <div class="row g-3 mb-4 align-items-stretch" id="gymGalleryViewer" data-cover-url="<?= $galleryEsc($coverUrl) ?>">
        <div class="col-lg-8">
            <button type="button"
                    class="gf-gallery-hero-btn border-0 p-0 w-100 rounded-4 overflow-hidden h-100"
                    id="gymHeroOpen"
                    data-full-src="<?= $galleryEsc($galleryLarge($coverUrl)) ?>"
                    aria-label="<?= gf_h(gf_t('Phóng to ảnh đang xem')) ?>">
                <img id="gymHeroImage"
                     src="<?= $galleryEsc($coverUrl) ?>"
                     class="w-100 gf-detail-cover"
                     alt="<?= gf_h($gym['name']) ?>"
                     data-fallback="<?= $galleryEsc(gf_cover_image(null, (int) $gym['id'])) ?>"
                     onerror="if(!this.dataset.failed){this.dataset.failed='1';this.src=this.dataset.fallback||'<?= $galleryEsc(gf_url('assets/images/gym-placeholder.svg')) ?>';}">
            </button>
        </div>
        <div class="col-lg-4">
            <div class="gf-gallery-thumbs">
                <?php foreach ($thumbItems as $idx => $tItem):
                    $isFirst = $idx === 0;
                    $isLast = $idx === count($thumbItems) - 1;
                    $showMore = $isLast && $thumbExtra > 0;
                    $label = !empty($tItem['is_cover'])
                        ? gf_t('Xem ảnh bìa')
                        : gf_t('Xem ảnh thư viện') . ' ' . $idx;
                    $alt = $tItem['caption'] !== ''
                        ? $tItem['caption']
                        : (!empty($tItem['is_cover']) ? gf_t('Ảnh bìa') : gf_t('Ảnh phòng tập ' . $gym['name']));
                    ?>
                    <button type="button"
                            class="gf-gallery-thumb border-0 p-0 rounded-3 overflow-hidden<?= $isFirst ? ' is-active' : '' ?>"
                            data-full-src="<?= $galleryEsc($tItem['url']) ?>"
                            data-large-src="<?= $galleryEsc($galleryLarge($tItem['url'])) ?>"
                            aria-label="<?= gf_h($label) ?>"
                            aria-pressed="<?= $isFirst ? 'true' : 'false' ?>">
                        <img src="<?= $galleryEsc($tItem['url']) ?>"
                             alt="<?= gf_h($alt) ?>"
                             loading="lazy"
                             onerror="if(this.dataset.fallbackStage){this.onerror=null;this.src='<?= $galleryEsc(gf_url('assets/images/gym-placeholder.svg')) ?>';}else{this.dataset.fallbackStage='1';this.src='<?= $galleryEsc($coverUrl) ?>';}">
                        <?php if ($showMore): ?>
                            <span class="gf-gallery-more">+<?= (int) $thumbExtra ?></span>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="gymGalleryLightbox" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content bg-transparent border-0 shadow-none">
                <div class="d-flex justify-content-end mb-2">
                    <button type="button" class="btn btn-light btn-sm rounded-circle" data-bs-dismiss="modal" aria-label="<?= gf_h(gf_t('Đóng')) ?>">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                <img id="gymLightboxImage" src="" alt="" class="w-100 rounded-4 gf-gallery-lightbox-img">
            </div>
        </div>
    </div>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="bg-white rounded-4 p-4 shadow-sm mb-4">
                <div class="d-flex justify-content-between">
                    <div>
                        <?php foreach ($gym['categories'] as $c): ?>
                            <span class="badge bg-primary-light text-primary mb-2"><?= gf_h(gf_t((string) $c['name'])) ?></span>
                        <?php endforeach; ?>
                        <h1 class="fw-bold"><?= gf_h($gym['name']) ?></h1>
                        <div class="mb-2"><i class="fa-solid fa-star text-warning"></i> <strong><?= gf_h((string)$gym['avg_rating']) ?></strong> (<?= gf_h(gf_t((int)$gym['review_count'] . ' đánh giá')) ?>)</div>
                        <div class="text-secondary"><i class="fa-solid fa-location-dot text-primary me-2"></i><?= gf_h(gf_t((string) $gym['address'])) ?></div>
                    </div>
                    <?php if ($canFavorite): ?>
                    <form method="post" action="<?= gf_h(gf_url('pages/user/favorite_toggle.php')) ?>" class="js-favorite-toggle" data-api-url="<?= gf_h(gf_url('api/favorites')) ?>" data-login-url="<?= gf_h(gf_url('pages/auth/login.php')) ?>" data-favorited="<?= $isFav ? '1' : '0' ?>">
                        <?= gf_csrf_field() ?>
                        <input type="hidden" name="target_type" value="gym"><input type="hidden" name="target_id" value="<?= $id ?>">
                        <input type="hidden" name="redirect" value="<?= gf_h($_SERVER['REQUEST_URI']) ?>">
                        <button class="btn btn-light border rounded-circle" type="submit" style="width:48px;height:48px;" aria-label="<?= gf_h(gf_t('Yêu thích phòng tập')) ?>" aria-pressed="<?= $isFav ? 'true' : 'false' ?>"><i class="fa-<?= $isFav?'solid text-danger':'regular' ?> fa-heart"></i></button>
                    </form>
                    <?php endif; ?>
                </div>
                <div class="bg-light p-3 rounded-3 mt-4 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-secondary"><?= gf_h(gf_t('Khoảng giá tham khảo')) ?></div>
                        <div class="fw-bold fs-5"><?= gf_h($gym['price_label']) ?></div>
                    </div>
                    <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold btn-global-contact"
                        data-type="gym"
                        data-type-label="<?= gf_h(gf_t('Phòng tập thể hình')) ?>"
                        data-name="<?= gf_h($gym['name']) ?>"
                        data-avatar="<?= gf_h($gym['cover']) ?>"
                        data-subtitle="<?= gf_h(gf_t((string) ($gym['address'] ?? ($gym['district_name'] ?? '')))) ?>"
                        data-phone="<?= gf_h($gym['phone'] ?? '') ?>">
                        <i class="fa-solid fa-phone me-2"></i><?= gf_h(gf_t('Liên hệ')) ?>
                    </button>
                </div>
            </div>
            <div class="bg-white rounded-4 p-4 shadow-sm mb-4">
                <h3 class="fw-bold"><?= gf_h(gf_t('Giới thiệu')) ?></h3>
                <p class="text-secondary mb-0"><?= gf_h(gf_t((string) ($gym['description'] ?? ''))) ?></p>
            </div>
            <div class="bg-white rounded-4 p-4 shadow-sm mb-4">
                <h3 class="fw-bold"><?= gf_h(gf_t('Huấn luyện viên tại phòng tập')) ?></h3>
                <div class="row g-3">
                    <?php foreach ($gymTrainers as $trainer): $isFav = $user && gf_is_fav((int)$user['id'], 'trainer', (int)$trainer['id']); require dirname(__DIR__, 2) . '/includes/trainer_card.php'; endforeach; ?>
                    <?php if (!$gymTrainers): ?><p class="text-secondary"><?= gf_h(gf_t('Chưa có HLV công khai.')) ?></p><?php endif; ?>
                </div>
            </div>
            <div class="bg-white rounded-4 p-4 shadow-sm mb-4" id="reviewsSection">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="fw-bold mb-0"><?= gf_h(gf_t('Đánh giá từ hội viên')) ?></h3>
                    <span class="text-secondary small">(<?= gf_h($totalReviews . ' đánh giá đã công khai') ?>)</span>
                </div>

                <?php if ($msg): ?>
                    <div class="alert alert-<?= $msgType ?> d-flex align-items-center mb-3 rounded-3 shadow-sm">
                        <i class="fa-solid <?= $msgType === 'success' ? 'fa-circle-check' : ($msgType === 'danger' ? 'fa-circle-exclamation' : 'fa-circle-info') ?> fs-5 me-2"></i>
                        <div><?= gf_h(gf_t((string) $msg)) ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($canReview && $myReview): ?>
                    <!-- KHỐI HIỂN THỊ ĐÁNH GIÁ CỦA CHÍNH USER -->
                    <div class="card border border-primary-subtle bg-light rounded-3 p-3 mb-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary text-white rounded-pill px-3 py-1"><?= gf_h(gf_t('Đánh giá của bạn')) ?></span>
                                <span class="text-warning fs-6"><?= str_repeat('★', (int)$myReview['rating']) ?> (<?= gf_h(gf_t((int)$myReview['rating'] . ' sao')) ?>)</span>
                            </div>
                            <div>
                                <?php if ($myReview['status'] === 'approved'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1"><i class="fa-solid fa-circle-check me-1"></i><?= gf_h(gf_t('Đã công khai')) ?></span>
                                <?php elseif ($myReview['status'] === 'pending'): ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-1"><i class="fa-solid fa-clock me-1"></i><?= gf_h(gf_t('Chờ duyệt')) ?></span>
                                <?php elseif ($myReview['status'] === 'rejected'): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2 py-1"><i class="fa-solid fa-circle-xmark me-1"></i><?= gf_h(gf_t('Bị từ chối')) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="text-dark mb-2" style="white-space: pre-line;"><?= gf_h($myReview['comment']) ?></p>
                        <?php if ($myReview['status'] === 'rejected' && !empty($myReview['reject_reason'])): ?>
                            <div class="small text-danger bg-danger-subtle p-2 rounded-2 mb-2">
                                <i class="fa-solid fa-triangle-exclamation me-1"></i><strong><?= gf_h(gf_t('Lý do từ chối:')) ?></strong> <?= gf_h($myReview['reject_reason']) ?>
                            </div>
                        <?php endif; ?>
                        <div class="d-flex gap-2 justify-content-end pt-2 border-top">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#editGymReviewModal">
                                <i class="fa-solid fa-pen-to-square me-1"></i><?= gf_h(gf_t('Sửa đánh giá')) ?>
                            </button>
                            <form method="post" class="d-inline js-confirm-submit"
                                data-confirm-title="<?= gf_h(gf_t('Xóa đánh giá')) ?>"
                                data-confirm-message="<?= gf_h(gf_t('Bạn có chắc chắn muốn xóa đánh giá của mình không? Thao tác này không thể hoàn tác.')) ?>"
                                data-confirm-label="<?= gf_h(gf_t('Xóa đánh giá')) ?>"
                                data-confirm-danger="1">
                                <?= gf_csrf_field() ?>
                                <input type="hidden" name="action" value="delete_review">
                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                    <i class="fa-solid fa-trash me-1"></i><?= gf_h(gf_t('Xóa')) ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- MODAL CHỈNH SỬA REVIEW -->
                    <div class="modal fade" id="editGymReviewModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow">
                                <form method="post" class="js-form-validate" novalidate>
                                    <?= gf_csrf_field() ?>
                                    <input type="hidden" name="action" value="edit_review">
                                    <div class="modal-header border-0 pb-0">
                                        <h5 class="modal-title fw-bold"><?= gf_h(gf_t('Chỉnh sửa đánh giá của bạn')) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= gf_h(gf_t('Đóng')) ?>"></button>
                                    </div>
                                    <div class="modal-body py-3">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small"><?= gf_h(gf_t('Mức độ hài lòng')) ?></label>
                                            <?php
                                            $ratingFilterName = 'rating';
                                            $ratingFilterValue = (string) (int) $myReview['rating'];
                                            $ratingFilterId = 'editGymReviewRating';
                                            $ratingFilterMode = 'rate';
                                            require dirname(__DIR__, 2) . '/includes/rating_star_filter.php';
                                            ?>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small"><?= gf_h(gf_t('Nhận xét chi tiết')) ?></label>
                                            <textarea name="comment" class="form-control rounded-3" rows="4" required data-required-msg="<?= gf_h(gf_t('Vui lòng nhập nội dung đánh giá.')) ?>" placeholder="<?= gf_h(gf_t('Chia sẻ trải nghiệm của bạn...')) ?>"><?= gf_h($myReview['comment']) ?></textarea>
                                        </div>
                                        <div class="small text-secondary">
                                            <i class="fa-solid fa-circle-info me-1 text-primary"></i><?= gf_h(gf_t('Lưu ý: Sau khi sửa, đánh giá sẽ được gửi để quản trị viên duyệt lại trước khi tiếp tục hiển thị công khai.')) ?>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 pt-0">
                                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal"><?= gf_h(gf_t('Hủy')) ?></button>
                                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold"><?= gf_h(gf_t('Lưu thay đổi')) ?></button>
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
                <?php if (!$pagedReviews): ?><p class="text-secondary py-2"><?= gf_h(gf_t('Chưa có đánh giá nào được công khai.')) ?></p><?php endif; ?>
                <?= gf_render_user_pagination($reviewPage, $reviewLimit, $totalReviews, array_diff_key($_GET, ['r_page' => '']), 'r_page') ?>
                
                <?php if ($canReview && !$myReview): ?>
                    <form method="post" class="mt-4 pt-3 border-top js-form-validate" novalidate>
                        <?= gf_csrf_field() ?>
                        <input type="hidden" name="action" value="create_review">
                        <label class="form-label fw-semibold"><?= gf_h(gf_t('Viết đánh giá')) ?></label>
                        <div class="mb-2">
                            <?php
                            $ratingFilterName = 'rating';
                            $ratingFilterValue = '5';
                            $ratingFilterId = 'createGymReviewRating';
                            $ratingFilterMode = 'rate';
                            require dirname(__DIR__, 2) . '/includes/rating_star_filter.php';
                            ?>
                        </div>
                        <div data-field class="mb-2">
                        <textarea name="comment" class="form-control" rows="3" required data-required-msg="<?= gf_h(gf_t('Vui lòng nhập nội dung đánh giá.')) ?>" placeholder="<?= gf_h(gf_t('Chia sẻ trải nghiệm...')) ?>"></textarea>
                        </div>
                        <button class="btn btn-primary rounded-pill px-4"><?= gf_h(gf_t('Gửi đánh giá')) ?></button>
                    </form>
                <?php elseif ($isAdminUser): ?>
                    <div class="mt-4 pt-3 border-top text-center text-secondary small">
                        <i class="fa-solid fa-shield-halved me-1 text-primary"></i><?= gf_h(gf_t('Admin duyệt đánh giá tại khu vực quản trị — không gửi review hội viên tại đây.')) ?>
                    </div>
                <?php elseif (!$user): ?>
                    <div class="mt-4 pt-3 border-top text-center">
                        <a class="btn btn-outline-primary rounded-pill px-4" href="<?= gf_h(gf_url('pages/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']))) ?>">
                            <i class="fa-regular fa-comment-dots me-2"></i><?= gf_h(gf_t('Đăng nhập để đánh giá')) ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="bg-white rounded-4 p-4 shadow-sm">
                <h3 class="fw-bold mb-3"><?= gf_h(gf_t('Thông tin phòng tập')) ?></h3>
                <p class="d-flex justify-content-between"><span class="text-secondary"><?= gf_h(gf_t('Giờ hoạt động')) ?></span><strong><?= gf_h(gf_t((string) ($gym['opening_hours'] ?? ''))) ?></strong></p>
                <p class="d-flex justify-content-between"><span class="text-secondary"><?= gf_h(gf_t('Khu vực')) ?></span><strong><?= gf_h(gf_place_name((string) ($gym['district_name'] ?? ''))) ?></strong></p>
                <?php if (!empty($gym['features'])): ?>
                <h5 class="fw-bold mt-4"><?= gf_h(gf_t('Đặc điểm')) ?></h5>
                <ul class="list-unstyled">
                    <?php foreach ($gym['features'] as $ft): ?>
                        <li class="mb-2"><i class="fa-solid fa-check text-primary me-2"></i><?= gf_h(gf_t((string) $ft['name'])) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
                <h5 class="fw-bold mt-4"><?= gf_h(gf_t('Tiện ích')) ?></h5>
                <ul class="list-unstyled">
                    <?php foreach ($gym['amenities'] as $a): ?>
                        <li class="mb-2"><i class="fa-solid <?= gf_h($a['icon'] ?: 'fa-check') ?> text-primary me-2"></i><?= gf_h(gf_t((string) $a['name'])) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
    <h3 class="fw-bold mt-5 mb-3"><?= gf_h(gf_t('Có thể bạn cũng quan tâm')) ?></h3>
    <div class="row g-4">
        <?php foreach ($related as $gym): $isFav = $user && gf_is_fav((int)$user['id'], 'gym', (int)$gym['id']); require dirname(__DIR__, 2) . '/includes/gym_card.php'; endforeach; ?>
    </div>
</div>
<script src="<?= gf_h(gf_url('assets/js/rating-filter.js')) ?>" defer></script>
<script>
(function () {
    const root = document.getElementById('gymGalleryViewer');
    const hero = document.getElementById('gymHeroImage');
    const heroOpen = document.getElementById('gymHeroOpen');
    const lightboxEl = document.getElementById('gymGalleryLightbox');
    const lightboxImg = document.getElementById('gymLightboxImage');
    if (!root || !hero) return;

    const thumbs = Array.from(root.querySelectorAll('.gf-gallery-thumb[data-full-src]'));

    function getLightbox() {
        if (!lightboxEl || !window.bootstrap || typeof bootstrap.Modal !== 'function') {
            return null;
        }
        return bootstrap.Modal.getOrCreateInstance(lightboxEl);
    }

    function largeSrc(btn) {
        return (btn && (btn.getAttribute('data-large-src') || btn.getAttribute('data-full-src'))) || '';
    }

    function setActive(btn) {
        thumbs.forEach((t) => {
            const on = t === btn;
            t.classList.toggle('is-active', on);
            t.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
    }

    function showInHero(src, large) {
        if (!src) return;
        const next = large || src;
        hero.dataset.failed = '';
        hero.style.opacity = '0.35';
        const apply = () => {
            hero.setAttribute('src', src);
            hero.src = src;
            hero.style.opacity = '1';
            if (heroOpen) {
                heroOpen.setAttribute('data-full-src', next);
            }
        };
        window.setTimeout(apply, 60);
    }

    function openLightbox(src, alt) {
        if (!src || !lightboxImg) return;
        lightboxImg.src = src;
        lightboxImg.alt = alt || '';
        const show = () => {
            const modal = getLightbox();
            if (modal) modal.show();
        };
        // Bootstrap thường load ở footer sau script này
        if (window.bootstrap && typeof bootstrap.Modal === 'function') {
            show();
        } else {
            window.addEventListener('load', show, { once: true });
            setTimeout(show, 0);
        }
    }

    thumbs.forEach((btn) => {
        btn.addEventListener('click', () => {
            const src = btn.getAttribute('data-full-src') || '';
            const large = largeSrc(btn);
            const alt = (btn.querySelector('img') && btn.querySelector('img').alt) || '';
            setActive(btn);
            // Đổi ảnh lớn (hero) đúng theo thumbnail — không giữ cứng ảnh bìa.
            showInHero(src, large);
            if (hero && typeof hero.scrollIntoView === 'function') {
                hero.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            // Mobile / muốn xem full: mở lightbox đúng ảnh vừa chọn.
            if (window.matchMedia && window.matchMedia('(max-width: 991.98px)').matches) {
                openLightbox(large || src, alt);
            }
        });
    });

    if (heroOpen) {
        heroOpen.addEventListener('click', () => {
            const src = heroOpen.getAttribute('data-full-src') || hero.getAttribute('src') || '';
            openLightbox(src, hero.alt || '');
        });
    }
})();
</script>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
