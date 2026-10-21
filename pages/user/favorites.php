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
<?php elseif (($user['role'] ?? '') === 'admin'): ?>
    <div class="container text-center py-5 mt-5">
        <div class="mb-4"><span class="d-inline-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm" style="width:100px;height:100px"><i class="fa-solid fa-shield-halved text-primary" style="font-size:40px"></i></span></div>
        <h2 class="fw-bold mb-3">Tài khoản quản trị không dùng Yêu thích</h2>
        <p class="text-secondary mx-auto mb-4" style="max-width:520px">Admin quản lý dữ liệu trong khu vực quản trị. Để thử lưu yêu thích như hội viên, hãy đăng nhập tài khoản user (vd. <code>u02@gymfinder.test</code>).</p>
        <a href="<?= gf_h(gf_url('admin/pages/dashboard.php')) ?>" class="btn btn-primary rounded-pill px-5 py-2 fw-semibold">Vào khu vực quản trị</a>
    </div>
<?php else:
    $favs = gf_user_favorites((int)$user['id']);
    $activeTab = in_array($_GET['tab'] ?? '', ['trainers', 'gyms'], true) ? $_GET['tab'] : 'gyms';
    $limit = 8;

    // Phân trang Phòng tập yêu thích
    $totalGyms = count($favs['gyms']);
    $gymPage = max(1, (int)($_GET['g_page'] ?? ($activeTab === 'gyms' ? ($_GET['page'] ?? 1) : 1)));
    if ($gymPage > 1 && $gymPage > (int)ceil($totalGyms / $limit)) {
        $gymPage = max(1, (int)ceil($totalGyms / $limit));
    }
    $pagedGyms = array_slice($favs['gyms'], ($gymPage - 1) * $limit, $limit);

    // Phân trang Huấn luyện viên yêu thích
    $totalTrainers = count($favs['trainers']);
    $trainerPage = max(1, (int)($_GET['t_page'] ?? ($activeTab === 'trainers' ? ($_GET['page'] ?? 1) : 1)));
    if ($trainerPage > 1 && $trainerPage > (int)ceil($totalTrainers / $limit)) {
        $trainerPage = max(1, (int)ceil($totalTrainers / $limit));
    }
    $pagedTrainers = array_slice($favs['trainers'], ($trainerPage - 1) * $limit, $limit);
?>
    <div class="container py-5">
        <div class="mb-4 text-center">
            <h1 class="hero-title text-uppercase">DANH SÁCH <span class="text-primary position-relative">YÊU THÍCH<svg class="position-absolute w-100 bottom-0 start-0 translate-middle-y text-primary opacity-50" style="height:10px" viewBox="0 0 100 10" preserveAspectRatio="none"><path d="M0 5 Q 50 10 100 5" stroke="currentColor" stroke-width="4" fill="transparent" /></svg></span></h1>
            <p class="text-secondary">Lưu lại những phòng tập và huấn luyện viên bạn quan tâm.</p>
        </div>
        <ul class="nav nav-pills justify-content-center mb-4">
            <li class="nav-item"><button class="nav-link <?= $activeTab === 'gyms' ? 'active' : '' ?>" id="gyms-tab" data-bs-toggle="pill" data-bs-target="#fg" type="button" role="tab">Phòng tập (<?= $totalGyms ?>)</button></li>
            <li class="nav-item"><button class="nav-link <?= $activeTab === 'trainers' ? 'active' : '' ?>" id="trainers-tab" data-bs-toggle="pill" data-bs-target="#ft" type="button" role="tab">Huấn luyện viên (<?= $totalTrainers ?>)</button></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade <?= $activeTab === 'gyms' ? 'show active' : '' ?>" id="fg">
                <div class="row g-4 mb-4">
                    <?php foreach ($pagedGyms as $gym): $isFav = true; require dirname(__DIR__, 2).'/includes/gym_card.php'; endforeach; ?>
                    <?php if (!$pagedGyms): ?><p class="text-center text-secondary py-4">Chưa lưu phòng tập nào.</p><?php endif; ?>
                </div>
                <?= gf_render_user_pagination($gymPage, $limit, $totalGyms, array_merge(array_diff_key($_GET, ['page' => '', 'g_page' => '']), ['tab' => 'gyms']), 'page') ?>
            </div>
            <div class="tab-pane fade <?= $activeTab === 'trainers' ? 'show active' : '' ?>" id="ft">
                <div class="row g-4 mb-4">
                    <?php foreach ($pagedTrainers as $trainer): $isFav = true; require dirname(__DIR__, 2).'/includes/trainer_card.php'; endforeach; ?>
                    <?php if (!$pagedTrainers): ?><p class="text-center text-secondary py-4">Chưa lưu HLV nào.</p><?php endif; ?>
                </div>
                <?= gf_render_user_pagination($trainerPage, $limit, $totalTrainers, array_merge(array_diff_key($_GET, ['page' => '', 't_page' => '']), ['tab' => 'trainers']), 'page') ?>
            </div>
        </div>
    </div>
<?php endif; ?>
</main>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
