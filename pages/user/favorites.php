<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
$user = gf_current_user();
$pageTitle = 'Yêu thích - GYMFINDER';
$bodyClass = 'bg-light d-flex flex-column min-vh-100';
require dirname(__DIR__, 2) . '/includes/layout_head.php';
require dirname(__DIR__, 2) . '/includes/header.php';
?>
<main class="flex-grow-1">
<?php if (!$user): ?>
    <div class="container text-center py-5 mt-5">
        <div class="mb-4"><span class="d-inline-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm" style="width:100px;height:100px"><i class="fa-solid fa-heart text-secondary" style="font-size:40px;color:#cbd5e1!important"></i></span></div>
        <h2 class="fw-bold mb-3">Đăng nhập để xem yêu thích</h2>
        <p class="text-secondary mx-auto mb-4" style="max-width:500px">Vui lòng đăng nhập để lưu và quản lý các phòng tập, huấn luyện viên yêu thích của bạn.</p>
        <a href="<?= gf_h(gf_url('pages/auth/login.php?redirect=' . urlencode(gf_url('pages/user/favorites.php')))) ?>" class="btn btn-primary rounded-pill px-5 py-2 fw-semibold">Đăng nhập / Đăng ký</a>
    </div>
<?php else:
    $favs = gf_user_favorites((int)$user['id']);
?>
    <div class="container py-5">
        <div class="mb-4 text-center">
            <h1 class="hero-title text-uppercase">DANH SÁCH <span class="text-primary position-relative">YÊU THÍCH<svg class="position-absolute w-100 bottom-0 start-0 translate-middle-y text-primary opacity-50" style="height:10px" viewBox="0 0 100 10" preserveAspectRatio="none"><path d="M0 5 Q 50 10 100 5" stroke="currentColor" stroke-width="4" fill="transparent" /></svg></span></h1>
            <p class="text-secondary">Lưu lại những phòng tập và huấn luyện viên bạn quan tâm.</p>
        </div>
        <ul class="nav nav-pills justify-content-center mb-4">
            <li class="nav-item"><button class="nav-link active" id="gyms-tab" data-bs-toggle="pill" data-bs-target="#fg" type="button" role="tab">Phòng tập (<?= count($favs['gyms']) ?>)</button></li>
            <li class="nav-item"><button class="nav-link" id="trainers-tab" data-bs-toggle="pill" data-bs-target="#ft" type="button" role="tab">Huấn luyện viên (<?= count($favs['trainers']) ?>)</button></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="fg">
                <div class="row g-4">
                    <?php foreach ($favs['gyms'] as $gym): $isFav = true; require dirname(__DIR__, 2).'/includes/gym_card.php'; endforeach; ?>
                    <?php if (!$favs['gyms']): ?><p class="text-center text-secondary">Chưa lưu phòng tập nào.</p><?php endif; ?>
                </div>
            </div>
            <div class="tab-pane fade" id="ft">
                <div class="row g-4">
                    <?php foreach ($favs['trainers'] as $trainer): $isFav = true; require dirname(__DIR__, 2).'/includes/trainer_card.php'; endforeach; ?>
                    <?php if (!$favs['trainers']): ?><p class="text-center text-secondary">Chưa lưu HLV nào.</p><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
</main>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
