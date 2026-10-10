<?php
/** @var array $gym */
$isFav = !empty($isFav);
$href = gf_url('pages/gyms/detail.php?id=' . (int) $gym['id']);
$cats = $gym['categories'] ?? [];
$badge = '';
foreach ($cats as $c) {
    if (($c['slug'] ?? '') === 'premium') {
        $badge = '<div class="position-absolute badge bg-dark text-warning px-2 py-1 rounded" style="top: 10px; left: 10px; font-size: 11px; font-weight: 600;"><i class="fa-solid fa-crown me-1"></i>Cao cấp</div>';
    }
}
$heart = $isFav ? '<i class="fa-solid fa-heart text-danger"></i>' : '<i class="fa-regular fa-heart" style="color:#cbd5e1;"></i>';
$rating = (float) ($gym['avg_rating'] ?? 0);
$reviews = (int) ($gym['review_count'] ?? 0);
?>
<div id="favorite-gym-<?= (int)$gym['id'] ?>" class="col-12 col-md-6 col-lg-4 col-xl-3">
    <div class="gym-card bg-white h-100 d-flex flex-column rounded-4 border-0 shadow-sm position-relative" style="border: 1px solid #f1f5f9 !important;">
        <div class="card-img-wrapper position-relative" style="height: 180px;">
            <img src="<?= gf_h($gym['cover'] ?? gf_cover_image(null, (int)$gym['id'])) ?>" class="card-img-top w-100 h-100 object-fit-cover rounded-top-4" alt="<?= gf_h($gym['name']) ?>">
            <?= $badge ?>
            <form method="post" action="<?= gf_h(gf_url('pages/user/favorite_toggle.php')) ?>" class="js-favorite-toggle position-absolute" data-api-url="<?= gf_h(gf_url('api/favorites')) ?>" data-login-url="<?= gf_h(gf_url('pages/auth/login.php')) ?>" data-favorited="<?= $isFav ? '1' : '0' ?>" style="top:10px;right:10px;z-index:2;">
                <?= gf_csrf_field() ?>
                <input type="hidden" name="target_type" value="gym">
                <input type="hidden" name="target_id" value="<?= (int)$gym['id'] ?>">
                <input type="hidden" name="redirect" value="<?= gf_h($_SERVER['REQUEST_URI'] ?? '') ?>">
                <button class="btn-favorite bg-white d-flex align-items-center justify-content-center border-0 shadow-sm" type="submit" style="width:32px;height:32px;border-radius:50%;" aria-label="Favorite" aria-pressed="<?= $isFav ? 'true' : 'false' ?>"><?= $heart ?></button>
            </form>
        </div>
        <div class="p-3 d-flex flex-column flex-grow-1">
            <h5 class="fw-bold mb-2 text-dark" style="font-size: 16px; min-height: 44px;">
                <a href="<?= gf_h($href) ?>" class="text-decoration-none text-dark stretched-link"><?= gf_h($gym['name']) ?></a>
            </h5>
            <div class="d-flex align-items-center mb-2" style="font-size: 13px;">
                <?php if ($reviews > 0): ?>
                    <i class="fa-solid fa-star text-warning me-1"></i><strong><?= gf_h((string)$rating) ?></strong>
                    <span class="text-secondary ms-1">(<?= $reviews ?> đánh giá)</span>
                <?php else: ?>
                    <span class="text-secondary">Chưa có đánh giá</span>
                <?php endif; ?>
            </div>
            <p class="text-secondary mb-3" style="font-size: 13px;">
                <i class="fa-solid fa-location-dot me-2 text-primary opacity-75"></i><?= gf_h($gym['district_name'] ?? '') ?>, TP.HCM
            </p>
            <div class="fw-bold text-dark mb-3" style="font-size: 14px;"><?= gf_h($gym['price_label'] ?? '') ?></div>
            <div class="mb-3 d-flex flex-wrap gap-1 position-relative" style="z-index:2;">
                <?php foreach (array_slice($gym['amenities'] ?? [], 0, 3) as $a): ?>
                    <span class="badge bg-light text-secondary border fw-normal" style="font-size:11px;"><?= gf_h($a['name']) ?></span>
                <?php endforeach; ?>
                <?php if (!empty($gym['has_pt'])): ?>
                    <span class="badge bg-light text-secondary border fw-normal" style="font-size:11px;">Có PT</span>
                <?php endif; ?>
            </div>
            <div class="mt-auto pt-3 border-top position-relative" style="z-index:2;">
                <a href="<?= gf_h($href) ?>" class="btn btn-outline-primary w-100 rounded-3 py-2 fw-semibold" style="font-size:13px;">Xem chi tiết</a>
            </div>
        </div>
    </div>
</div>
