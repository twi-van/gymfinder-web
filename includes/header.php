<?php
$user = gf_current_user();
$home = gf_url('index.php');
$currentScript = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
$gymActive = str_contains($currentScript, '/pages/gyms/');
$trainerActive = str_contains($currentScript, '/pages/trainers/');
$favoriteActive = str_contains($currentScript, '/pages/user/favorites.php');
$isAdmin = ($user && ($user['role'] ?? '') === 'admin');
?>
<nav class="navbar navbar-expand-lg bg-white sticky-top shadow-sm border-bottom">
    <div class="container">
        <?= gf_logo('header', $home, 'navbar-brand') ?>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link <?= basename($currentScript) === 'index.php' && !$trainerActive ? 'active' : '' ?>" href="<?= gf_h($home) ?>">Trang chủ</a></li>
                <li class="nav-item"><a class="nav-link <?= $gymActive ? 'active' : '' ?>" href="<?= gf_h(gf_url('pages/gyms/search.php')) ?>">Phòng tập</a></li>
                <li class="nav-item"><a class="nav-link <?= $trainerActive ? 'active' : '' ?>" href="<?= gf_h(gf_url('pages/trainers/index.php')) ?>">Huấn luyện viên</a></li>
                <li class="nav-item"><a class="nav-link <?= $favoriteActive ? 'active' : '' ?>" href="<?= gf_h(gf_url('pages/user/favorites.php')) ?>">Yêu thích</a></li>
            </ul>
            <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                <?php if ($user): ?>
                    <?php if ($isAdmin): ?>
                        <a href="<?= gf_h(gf_url('admin/pages/dashboard.php')) ?>" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-2 rounded-pill px-3 py-2 fw-semibold me-2" style="font-size: 13px;">
                            <i class="fa-solid fa-gauge-high"></i>
                            <span>Khu vực quản trị</span>
                        </a>
                    <?php endif; ?>
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center gap-2 text-decoration-none text-dark dropdown-toggle" data-bs-toggle="dropdown">
                            <img src="<?= gf_h(gf_avatar($user['avatar_url'] ?? null, !empty($user['avatar_url']) ? (int) $user['id'] : 0, $user['full_name'])) ?>" alt="" width="36" height="36" class="rounded-circle shadow-sm" style="width:36px;height:36px;object-fit:cover;object-position:center;aspect-ratio:1/1;flex:0 0 36px">
                            <span class="fw-semibold" style="font-size: 14px;"><?= gf_h($user['full_name']) ?></span>
                            <?php if ($isAdmin): ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 11px;">Quản trị viên</span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow mt-2 rounded-3" style="font-size: 14px;">
                            <?php if ($isAdmin): ?>
                                <li class="px-3 py-2 border-bottom bg-light">
                                    <div class="small text-secondary" style="font-size: 11px;">Tài khoản đăng nhập</div>
                                    <div class="fw-bold text-primary small"><i class="fa-solid fa-shield-halved me-1"></i>Quản trị viên</div>
                                </li>
                                <li><a class="dropdown-item py-2 fw-semibold text-primary" href="<?= gf_h(gf_url('admin/pages/dashboard.php')) ?>"><i class="fa-solid fa-gauge-high me-2 text-primary"></i>Khu vực quản trị</a></li>
                                <li><hr class="dropdown-divider my-1"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item py-2" href="<?= gf_h(gf_url('pages/user/profile.php')) ?>"><i class="fa-regular fa-user me-2 text-secondary"></i>Hồ sơ cá nhân</a></li>
                            <li><a class="dropdown-item py-2" href="<?= gf_h(gf_url('pages/user/favorites.php')) ?>"><i class="fa-regular fa-heart me-2 text-secondary"></i>Yêu thích</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="post" action="<?= gf_h(gf_url('pages/auth/logout.php')) ?>">
                                    <?= gf_csrf_field() ?>
                                    <button class="dropdown-item py-2 text-danger" type="submit"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Đăng xuất</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= gf_h(gf_url('pages/auth/login.php')) ?>" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-semibold" style="font-size: 14px;">Đăng nhập</a>
                    <a href="<?= gf_h(gf_url('pages/auth/register.php')) ?>" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold" style="font-size: 14px;">Đăng ký</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
