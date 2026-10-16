<?php
/** @var string $adminActive */
$adminActive = $adminActive ?? 'dashboard';
$adminUser = gf_require_admin();
function gf_admin_nav(string $key, string $href, string $icon, string $label, string $active): void
{
    $cls = $key === $active ? ' active bg-primary-light text-primary' : '';
    echo '<li class="nav-item"><a class="nav-link rounded-3 px-3 py-2 mb-1' . $cls . '" href="' . gf_h(gf_url($href)) . '"><i class="fa-solid ' . $icon . ' me-3" style="width:20px;"></i>' . gf_h($label) . '</a></li>';
}
?>
<div class="d-flex admin-shell">
<div class="sidebar-overlay" id="sidebarOverlay" hidden></div>
<aside class="sidebar d-flex flex-column" id="sidebar" aria-label="Menu quản trị">
    <div class="p-4 border-bottom d-flex align-items-center justify-content-between gap-2">
        <?= gf_logo('admin', null, 'admin-brand') ?>
        <button type="button" class="btn btn-light border d-lg-none shadow-sm admin-sidebar-close" id="sidebarClose" aria-label="Đóng menu">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <div class="p-3 flex-grow-1 overflow-auto">
        <ul class="nav flex-column gap-1">
            <?php
            gf_admin_nav('dashboard', 'admin/pages/dashboard.php', 'fa-chart-pie', 'Tổng quan', $adminActive);
            gf_admin_nav('gyms', 'admin/pages/gyms.php', 'fa-dumbbell', 'Phòng tập', $adminActive);
            gf_admin_nav('trainers', 'admin/pages/trainers.php', 'fa-user-tie', 'Huấn luyện viên', $adminActive);
            gf_admin_nav('users', 'admin/pages/users.php', 'fa-users', 'Người dùng', $adminActive);
            gf_admin_nav('reviews', 'admin/pages/reviews.php', 'fa-star', 'Đánh giá', $adminActive);
            gf_admin_nav('categories', 'admin/pages/categories.php', 'fa-list', 'Danh mục', $adminActive);
            gf_admin_nav('profile', 'admin/pages/profile.php', 'fa-user-shield', 'Hồ sơ cá nhân', $adminActive);
            ?>
        </ul>
    </div>
    <div class="p-3 border-top mt-auto">
        <div class="d-flex align-items-center gap-2">
            <a href="<?= gf_h(gf_url('admin/pages/profile.php')) ?>" class="d-flex align-items-center gap-2 text-decoration-none min-w-0 flex-grow-1 text-dark" title="Xem và sửa hồ sơ quản trị">
                <img src="<?= gf_h(gf_avatar($adminUser['avatar_url'] ?? null, !empty($adminUser['avatar_url']) ? (int) $adminUser['id'] : 0, $adminUser['full_name'])) ?>" class="rounded-circle shadow-sm" style="width:40px;height:40px;object-fit:cover;aspect-ratio:1/1;flex:0 0 40px;" alt="<?= gf_h($adminUser['full_name']) ?>">
                <div class="min-w-0 text-truncate"><div class="fw-bold small text-truncate"><?= gf_h($adminUser['full_name']) ?></div><div class="text-secondary" style="font-size:11.5px;">Quản trị viên</div></div>
            </a>
            <form method="post" action="<?= gf_h(gf_url('pages/auth/logout.php')) ?>" class="ms-auto"><?= gf_csrf_field() ?><button class="btn btn-action btn-action-view" title="Đăng xuất"><i class="fa-solid fa-arrow-right-from-bracket"></i></button></form>
        </div>
    </div>
</aside>
<main class="main-content flex-grow-1 p-4 p-md-5 bg-light" style="min-width:0;">
    <div class="admin-mobile-bar d-lg-none d-flex align-items-center justify-content-between gap-2 mb-3">
        <button type="button" class="btn btn-light border shadow-sm" id="sidebarOpen" aria-controls="sidebar" aria-expanded="false" aria-label="Mở menu">
            <i class="fa-solid fa-bars"></i>
        </button>
        <?= gf_logo('admin', null, 'admin-brand') ?>
        <span style="width:40px;" aria-hidden="true"></span>
    </div>
    <script src="<?= gf_h(gf_url('admin/assets/admin.js')) ?>" defer></script>
