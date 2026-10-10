<?php
/** @var array $trainer */
$isFav = !empty($isFav);
$href = gf_url('pages/trainers/detail.php?id=' . (int) $trainer['id']);
$heart = $isFav ? '<i class="fa-solid fa-heart text-danger"></i>' : '<i class="fa-regular fa-heart" style="color:#cbd5e1;"></i>';
?>
<div id="favorite-trainer-<?= (int)$trainer['id'] ?>" class="col-12 col-md-6 col-lg-4">
    <div class="trainer-card bg-white h-100 d-flex flex-column rounded-4 border-0 shadow-sm p-4" style="border: 1px solid #f1f5f9 !important; position: relative;">
        <form method="post" action="<?= gf_h(gf_url('pages/user/favorite_toggle.php')) ?>" class="js-favorite-toggle position-absolute" data-api-url="<?= gf_h(gf_url('api/favorites')) ?>" data-login-url="<?= gf_h(gf_url('pages/auth/login.php')) ?>" data-favorited="<?= $isFav ? '1' : '0' ?>" style="top:15px;right:15px;z-index:2;">
            <?= gf_csrf_field() ?>
            <input type="hidden" name="target_type" value="trainer">
            <input type="hidden" name="target_id" value="<?= (int)$trainer['id'] ?>">
            <input type="hidden" name="redirect" value="<?= gf_h($_SERVER['REQUEST_URI'] ?? '') ?>">
            <button class="btn-trainer-favorite border-0 bg-transparent" type="submit" style="font-size:20px;" aria-label="Yêu thích huấn luyện viên" aria-pressed="<?= $isFav ? 'true' : 'false' ?>"><?= $heart ?></button>
        </form>
        <div class="d-flex flex-column align-items-center text-center mb-3">
            <img src="<?= gf_h($trainer['avatar'] ?? '') ?>" alt="<?= gf_h($trainer['full_name']) ?>" class="rounded-circle object-fit-cover mb-3 shadow-sm" style="width:100px;height:100px;border:3px solid #f8fafc;" onerror="this.src='https://placehold.co/100x100?text=Trainer';">
            <h5 class="fw-bold mb-1"><?= gf_h($trainer['full_name']) ?></h5>
            <div class="text-primary fw-medium small mb-2"><?= gf_h($trainer['specialty_name'] ?? '') ?></div>
            <div class="small text-secondary mb-1">
                <i class="fa-solid fa-star text-warning"></i>
                <span class="text-dark fw-bold"><?= gf_h((string)$trainer['avg_rating']) ?></span>
                (<?= (int)$trainer['review_count'] ?>)
            </div>
            <div class="d-flex align-items-center justify-content-center gap-3 small text-secondary">
                <div><i class="fa-solid fa-briefcase opacity-75 me-1"></i> <?= (int)$trainer['years_experience'] ?> năm</div>
                <div><i class="fa-solid fa-location-dot opacity-75 me-1"></i> <?= gf_h($trainer['district_name'] ?? $trainer['gym_name'] ?? '') ?></div>
            </div>
        </div>
        <p class="text-secondary small text-center mb-4 flex-grow-1" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;"><?= gf_h($trainer['bio'] ?? '') ?></p>
        <div class="d-flex gap-2 mt-auto">
            <a href="<?= gf_h($href) ?>" class="btn btn-outline-secondary flex-grow-1 py-2 fw-semibold" style="font-size:14px;border-radius:8px;">Xem hồ sơ</a>
            <button type="button" class="btn btn-primary flex-grow-1 py-2 fw-semibold btn-global-contact" data-phone="<?= gf_h($trainer['phone'] ?? '') ?>" style="font-size:14px;border-radius:8px;"><i class="fa-solid fa-phone me-1"></i> Liên hệ</button>
        </div>
    </div>
</div>
