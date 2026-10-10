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
<div class="d-flex">
<aside class="sidebar d-flex flex-column" id="sidebar" style="width:285px;min-height:100vh;background:#fff;border-right:1px solid #e2e8f0;">
    <div class="p-4 border-bottom">
        <a class="d-flex align-items-center gap-2 fw-bold text-decoration-none text-dark admin-brand" href="<?= gf_h(gf_url('index.php')) ?>"><span class="admin-brand-mark"><i class="fa-solid fa-dumbbell"></i></span><span>GYM<span class="text-primary">FINDER</span></span></a>
    </div>
    <div class="p-3 flex-grow-1">
        <ul class="nav flex-column">
            <?php
            gf_admin_nav('dashboard', 'admin/pages/dashboard.php', 'fa-chart-pie', 'Tổng quan', $adminActive);
            gf_admin_nav('gyms', 'admin/pages/gyms.php', 'fa-dumbbell', 'Phòng tập', $adminActive);
            gf_admin_nav('trainers', 'admin/pages/trainers.php', 'fa-user-tie', 'Huấn luyện viên', $adminActive);
            gf_admin_nav('users', 'admin/pages/users.php', 'fa-users', 'Người dùng', $adminActive);
            gf_admin_nav('reviews', 'admin/pages/reviews.php', 'fa-star', 'Đánh giá', $adminActive);
            gf_admin_nav('categories', 'admin/pages/categories.php', 'fa-list', 'Danh mục', $adminActive);
            ?>
        </ul>
    </div>
    <div class="p-3 border-top mt-auto">
        <div class="d-flex align-items-center gap-2">
            <span class="rounded-circle d-inline-flex align-items-center justify-content-center text-white fw-bold" style="width:44px;height:44px;background:#00c9b1;flex:0 0 44px">AD</span>
            <div class="min-w-0"><div class="fw-bold"><?= gf_h($adminUser['full_name']) ?></div><div class="small text-secondary">Administrator</div></div>
            <form method="post" action="<?= gf_h(gf_url('pages/auth/logout.php')) ?>" class="ms-auto"><?= gf_csrf_field() ?><button class="btn btn-action btn-action-view" title="Đăng xuất"><i class="fa-solid fa-arrow-right-from-bracket"></i></button></form>
        </div>
    </div>
</aside>
<main class="flex-grow-1 p-4 p-md-5 bg-light" style="min-width:0;">
