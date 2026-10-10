<?php
$user = gf_current_user();
$home = gf_url('index.php');
$currentScript = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
$gymActive = str_contains($currentScript, '/pages/gyms/');
$trainerActive = str_contains($currentScript, '/pages/trainers/');
$favoriteActive = str_contains($currentScript, '/pages/user/favorites.php');
?>
<nav class="navbar navbar-expand-lg bg-white sticky-top shadow-sm border-bottom">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= gf_h($home) ?>">
            <div class="border rounded d-flex align-items-center justify-content-center text-primary bg-white shadow-sm" style="width: 36px; height: 36px; border-color: #f3f4f6 !important;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="8" y1="12" x2="16" y2="12"></line>
                    <rect x="6" y="6" width="2" height="12" rx="1"></rect>
                    <rect x="3" y="8" width="2" height="8" rx="1"></rect>
                    <path d="M1.5 12h1.5"></path>
                    <rect x="16" y="6" width="2" height="12" rx="1"></rect>
                    <rect x="19" y="8" width="2" height="8" rx="1"></rect>
                    <path d="M21 12h1.5"></path>
                </svg>
            </div>
            <span class="fw-bold" style="font-size: 20px; color: #1e293b;">GYM<span class="text-primary">FINDER</span></span>
        </a>
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
                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center gap-2 text-decoration-none text-dark dropdown-toggle" data-bs-toggle="dropdown">
                            <img src="<?= gf_h(gf_avatar($user['avatar_url'] ?? null, !empty($user['avatar_url']) ? (int) $user['id'] : 0, $user['full_name'])) ?>" alt="" width="36" height="36" class="rounded-circle shadow-sm" style="width:36px;height:36px;object-fit:cover;object-position:center;aspect-ratio:1/1;flex:0 0 36px">
                            <span class="fw-semibold" style="font-size: 14px;"><?= gf_h($user['full_name']) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow mt-2 rounded-3" style="font-size: 14px;">
                            <li><a class="dropdown-item py-2" href="<?= gf_h(gf_url('pages/user/profile.php')) ?>"><i class="fa-regular fa-user me-2 text-secondary"></i>Hồ sơ cá nhân</a></li>
                            <li><a class="dropdown-item py-2" href="<?= gf_h(gf_url('pages/user/favorites.php')) ?>"><i class="fa-regular fa-heart me-2 text-secondary"></i>Đã lưu</a></li>
                            <?php if (($user['role'] ?? '') === 'admin'): ?>
                                <li><a class="dropdown-item py-2" href="<?= gf_h(gf_url('admin/pages/dashboard.php')) ?>"><i class="fa-solid fa-gauge me-2 text-secondary"></i>Trang quản trị</a></li>
                            <?php endif; ?>
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
