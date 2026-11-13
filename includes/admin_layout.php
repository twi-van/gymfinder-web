<?php
/** @var string $adminActive */
$adminActive = $adminActive ?? 'dashboard';
$adminUser = gf_require_admin();
$adminDashboardUrl = gf_url('admin/pages/dashboard.php');

$adminBreadcrumbMap = [
    'dashboard' => gf_t('Tổng quan'),
    'gyms' => gf_t('Phòng tập'),
    'trainers' => gf_t('Huấn luyện viên'),
    'users' => gf_t('Người dùng'),
    'reviews' => gf_t('Đánh giá'),
    'categories' => gf_t('Danh mục'),
    'profile' => gf_t('Hồ sơ cá nhân'),
];
$adminBreadcrumbCurrent = $adminBreadcrumbMap[$adminActive] ?? gf_t('Tổng quan');

function gf_admin_nav(string $key, string $href, string $icon, string $label, string $active): void
{
    $cls = $key === $active ? ' active bg-primary-light text-primary' : '';
    echo '<li class="nav-item"><a class="nav-link rounded-3 px-3 py-2 mb-1' . $cls . '" href="' . gf_h(gf_url($href)) . '" data-label="' . gf_h($label) . '"><i class="fa-solid ' . $icon . ' me-3" style="width:20px;"></i><span class="admin-nav-label">' . gf_h($label) . '</span></a></li>';
}
?>
<script>
try {
    if (window.matchMedia('(min-width: 992px)').matches && localStorage.getItem('gfAdminSidebarCollapsed') === '1') {
        document.documentElement.classList.add('admin-sidebar-collapsed');
    }
} catch (e) {}
</script>
<div class="d-flex admin-shell">
<div class="sidebar-overlay" id="sidebarOverlay" hidden></div>
<aside class="sidebar d-flex flex-column" id="sidebar" aria-label="<?= gf_h(gf_t('Menu quản trị')) ?>">
    <div class="sidebar-head p-4 border-bottom d-flex align-items-center justify-content-between gap-2">
        <?= gf_logo('admin', $adminDashboardUrl, 'admin-brand') ?>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="admin-sidebar-collapse d-none d-lg-inline-flex" id="sidebarCollapse" aria-controls="sidebar" aria-expanded="true" aria-label="<?= gf_h(gf_t('Thu gọn menu')) ?>" title="<?= gf_h(gf_t('Thu gọn menu')) ?>" data-label-collapse="<?= gf_h(gf_t('Thu gọn menu')) ?>" data-label-expand="<?= gf_h(gf_t('Mở rộng menu')) ?>">
                <i class="fa-solid fa-bars" aria-hidden="true"></i>
            </button>
            <button type="button" class="btn btn-light border d-lg-none shadow-sm admin-sidebar-close" id="sidebarClose" aria-label="<?= gf_h(gf_t('Đóng menu')) ?>">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>
    <div class="p-3 flex-grow-1 overflow-auto">
        <ul class="nav flex-column gap-1">
            <?php
            gf_admin_nav('dashboard', 'admin/pages/dashboard.php', 'fa-chart-pie', gf_t('Tổng quan'), $adminActive);
            gf_admin_nav('gyms', 'admin/pages/gyms.php', 'fa-dumbbell', gf_t('Phòng tập'), $adminActive);
            gf_admin_nav('trainers', 'admin/pages/trainers.php', 'fa-user-tie', gf_t('Huấn luyện viên'), $adminActive);
            gf_admin_nav('users', 'admin/pages/users.php', 'fa-users', gf_t('Người dùng'), $adminActive);
            gf_admin_nav('reviews', 'admin/pages/reviews.php', 'fa-star', gf_t('Đánh giá'), $adminActive);
            gf_admin_nav('categories', 'admin/pages/categories.php', 'fa-list', gf_t('Danh mục'), $adminActive);
            gf_admin_nav('profile', 'admin/pages/profile.php', 'fa-user-shield', gf_t('Hồ sơ cá nhân'), $adminActive);
            ?>
        </ul>
    </div>
    <div class="p-3 border-top mt-auto">
        <div class="admin-user-row d-flex align-items-center gap-2">
            <a href="<?= gf_h(gf_url('admin/pages/profile.php')) ?>" class="d-flex align-items-center gap-2 text-decoration-none min-w-0 flex-grow-1 text-dark" title="<?= gf_h(gf_t('Xem và sửa hồ sơ quản trị')) ?>">
                <img src="<?= gf_h(gf_avatar($adminUser['avatar_url'] ?? null, !empty($adminUser['avatar_url']) ? (int) $adminUser['id'] : 0, $adminUser['full_name'])) ?>" class="rounded-circle shadow-sm" style="width:40px;height:40px;object-fit:cover;aspect-ratio:1/1;flex:0 0 40px;" alt="<?= gf_h($adminUser['full_name']) ?>" onerror="this.onerror=null;this.src='<?= gf_h(gf_avatar_fallback($adminUser['full_name'])) ?>';">
                <div class="admin-user-meta min-w-0 text-truncate"><div class="fw-bold small text-truncate"><?= gf_h($adminUser['full_name']) ?></div><div class="text-secondary" style="font-size:11.5px;"><?= gf_h(gf_t('Quản trị viên')) ?></div></div>
            </a>
            <form method="post" action="<?= gf_h(gf_url('pages/auth/logout.php')) ?>" class="ms-auto"><?= gf_csrf_field() ?><button class="btn btn-action btn-action-view" title="<?= gf_h(gf_t('Đăng xuất')) ?>" aria-label="<?= gf_h(gf_t('Đăng xuất')) ?>"><i class="fa-solid fa-arrow-right-from-bracket"></i></button></form>
        </div>
    </div>
</aside>
<main class="main-content flex-grow-1 p-4 p-md-5 bg-light" style="min-width:0;">
    <div class="admin-mobile-bar d-lg-none mb-3">
        <button type="button" class="admin-mobile-toggle" id="sidebarOpen" aria-controls="sidebar" aria-expanded="false" aria-label="<?= gf_h(gf_t('Mở menu')) ?>">
            <i class="fa-solid fa-bars"></i>
        </button>
        <?= gf_logo('admin', $adminDashboardUrl, 'admin-brand admin-brand--bar') ?>
        <span class="admin-mobile-bar-spacer" aria-hidden="true"></span>
    </div>
    <div class="d-flex justify-content-end mb-2"><?= gf_lang_switch_html() ?></div>
    <nav class="admin-breadcrumb" aria-label="Breadcrumb">
        <ol class="admin-breadcrumb-list">
            <li class="admin-breadcrumb-item">
                <a href="<?= gf_h($adminDashboardUrl) ?>" class="admin-breadcrumb-link"><?= gf_h(gf_t('Quản trị viên')) ?></a>
            </li>
            <li class="admin-breadcrumb-sep" aria-hidden="true"><i class="fa-solid fa-chevron-right"></i></li>
            <li class="admin-breadcrumb-item">
                <span class="admin-breadcrumb-current" aria-current="page"><?= gf_h($adminBreadcrumbCurrent) ?></span>
            </li>
        </ol>
    </nav>
    <script src="<?= gf_h(gf_url('admin/assets/admin.js')) ?>" defer></script>
