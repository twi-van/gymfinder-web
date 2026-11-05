<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
$error = '';
if (isset($_GET['error']) && $_GET['error'] === 'locked') {
    $error = 'Tài khoản đã bị khóa.';
} elseif (isset($_GET['error'])) {
    $error = gf_google_oauth_error_message((string) $_GET['error']);
}
$googleHref = gf_url('pages/auth/google_start.php?from=login');
if (!empty($_GET['redirect'])) {
    $googleHref .= '&redirect=' . rawurlencode((string) $_GET['redirect']);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $remember = !empty($_POST['remember']);
    $res = gf_login(trim((string)($_POST['login'] ?? '')), (string)($_POST['password'] ?? ''), $remember);
    if ($res['ok']) {
        $to = trim((string)($_GET['redirect'] ?? $_POST['redirect'] ?? ''));
        gf_redirect(gf_post_login_path($res['user'], $to));
    }
    $error = $res['error'];
}
$pageTitle = gf_t('Đăng nhập - GYMFINDER');
$bodyClass = 'auth-page';
$extraCss = ['assets/css/auth.css'];
require dirname(__DIR__, 2) . '/includes/layout_head.php';
?>
<div class="auth-wrapper">
    <div class="auth-lang-switch"><?= gf_lang_switch_html() ?></div>
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <div class="auth-left pe-lg-4">
                    <div class="auth-brand mb-5">
                        <?= gf_logo('login') ?>
                    </div>
                    <div class="auth-badge"><i class="fa-solid fa-circle" style="font-size:6px"></i> <?= gf_h(gf_t('KHÁM PHÁ • KẾT NỐI • TẬP LUYỆN')) ?></div>
                    <h1 class="auth-heading"><?= gf_lang() === 'en' ? 'A <span class="text-primary">healthier</span><br>journey starts today' : 'Hành trình <span class="text-primary">khỏe mạnh</span><br>bắt đầu từ hôm nay' ?></h1>
                    <p class="auth-desc"><?= gf_h(gf_t('Đăng nhập để lưu phòng tập yêu thích, quản lý thông tin cá nhân và kết nối với huấn luyện viên tại TP.HCM.')) ?></p>
                    <div class="auth-features">
                        <div class="auth-feature"><div class="auth-feature-icon"><i class="fa-solid fa-magnifying-glass"></i></div><div><div class="auth-feature-title"><?= gf_h(gf_t('Tìm kiếm dễ dàng')) ?></div><p class="auth-feature-desc"><?= gf_h(gf_t('Hàng trăm phòng tập chất lượng')) ?></p></div></div>
                        <div class="auth-feature"><div class="auth-feature-icon"><i class="fa-solid fa-heart"></i></div><div><div class="auth-feature-title"><?= gf_h(gf_t('Lưu yêu thích')) ?></div><p class="auth-feature-desc"><?= gf_h(gf_t('Quản lý danh sách phòng tập của bạn')) ?></p></div></div>
                        <div class="auth-feature"><div class="auth-feature-icon"><i class="fa-solid fa-user-group"></i></div><div><div class="auth-feature-title"><?= gf_h(gf_t('Kết nối huấn luyện viên')) ?></div><p class="auth-feature-desc"><?= gf_h(gf_t('Đồng hành cùng bạn trên hành trình tập luyện')) ?></p></div></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 ms-auto">
                <div class="auth-card">
                    <h2 class="auth-card-title"><?= gf_h(gf_t('Đăng nhập')) ?></h2>
                    <p class="auth-card-subtitle"><?= gf_h(gf_t('Chào mừng bạn quay trở lại! Vui lòng đăng nhập để tiếp tục.')) ?></p>
                    <?php if (!empty($_GET['registered'])): ?><div class="alert alert-success"><?= gf_h(gf_t('Đăng ký thành công. Vui lòng đăng nhập.')) ?></div><?php endif; ?>
                    <?php if ($error): ?><div class="alert alert-danger"><?= gf_h(gf_t((string) $error)) ?></div><?php endif; ?>
                    <?= gf_google_button($googleHref, 'Đăng nhập bằng Google') ?>
                    <div class="auth-divider auth-divider--tight"><hr><span><?= gf_h(gf_t('HOẶC DÙNG EMAIL')) ?></span><hr></div>
                    <form method="post" id="loginForm" class="js-auth-validate" novalidate>
                        <?= gf_csrf_field() ?>
                        <input type="hidden" name="redirect" value="<?= gf_h($_GET['redirect'] ?? '') ?>">
                        <div class="mb-4" data-auth-field>
                            <label class="form-label" for="username"><?= gf_h(gf_t('Email / Số điện thoại')) ?></label>
                            <div class="auth-input-group"><i class="fa-regular fa-user auth-input-icon"></i><input id="username" class="form-control" type="text" name="login" required data-required-msg="<?= gf_h(gf_t('Vui lòng nhập email hoặc số điện thoại.')) ?>" autocomplete="username" placeholder="<?= gf_h(gf_t('Nhập email hoặc số điện thoại')) ?>" value="<?= gf_h($_POST['login'] ?? '') ?>"></div>
                        </div>
                        <div class="mb-4" data-auth-field>
                            <label class="form-label" for="password"><?= gf_h(gf_t('Mật khẩu')) ?></label>
                            <div class="auth-input-group"><i class="fa-solid fa-lock auth-input-icon"></i><input id="password" class="form-control" type="password" name="password" required data-required-msg="<?= gf_h(gf_t('Vui lòng nhập mật khẩu.')) ?>" autocomplete="current-password" placeholder="<?= gf_h(gf_t('Nhập mật khẩu')) ?>"><button type="button" class="auth-toggle-password" id="btnTogglePassword" aria-label="<?= gf_h(gf_t('Hiện mật khẩu')) ?>"><i class="fa-regular fa-eye-slash" id="togglePasswordIcon"></i></button></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-5">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" value="1" id="rememberMe" <?= !empty($_POST['remember']) ? 'checked' : '' ?>>
                                <label class="form-check-label text-secondary" for="rememberMe" style="font-size:14px"><?= gf_h(gf_t('Ghi nhớ đăng nhập')) ?></label>
                            </div>
                            <a href="<?= gf_h(gf_url('pages/auth/forgot_password.php')) ?>" class="text-primary text-decoration-none fw-semibold" style="font-size:14px"><?= gf_h(gf_t('Quên mật khẩu?')) ?></a>
                        </div>
                        <button class="auth-btn-submit w-100 d-flex justify-content-center align-items-center gap-2" type="submit"><?= gf_h(gf_t('Đăng nhập')) ?> <i class="fa-solid fa-arrow-right"></i></button>
                    </form>
                    <div class="auth-divider"><hr><span><?= gf_h(gf_t('CHỌN NHANH TÀI KHOẢN KIỂM THỬ')) ?></span><hr></div>
                    <div class="row g-3 mb-5">
                        <div class="col-6"><button type="button" class="test-card w-100 text-start" data-email="u02@gymfinder.test" data-password="password"><span class="test-status-dot"></span><div class="d-flex align-items-center gap-2 mb-2"><i class="fa-regular fa-user text-primary"></i><span class="fw-bold text-dark"><?= gf_h(gf_t('Người dùng')) ?></span></div><div class="text-primary fw-semibold small text-break">u02@gymfinder.test</div></button></div>
                        <div class="col-6"><button type="button" class="test-card w-100 text-start" data-email="u01@gymfinder.test" data-password="password"><span class="test-status-dot"></span><div class="d-flex align-items-center gap-2 mb-2"><i class="fa-solid fa-shield-halved text-primary"></i><span class="fw-bold text-dark"><?= gf_h(gf_t('Quản trị viên')) ?></span></div><div class="text-primary fw-semibold small text-break">u01@gymfinder.test</div></button></div>
                    </div>
                    <div class="text-center mt-auto" style="font-size:15px;color:#64748b"><?= gf_h(gf_t('Chưa có tài khoản?')) ?> <a class="text-primary text-decoration-none fw-bold" href="<?= gf_h(gf_url('pages/auth/register.php')) ?>"><?= gf_h(gf_t('Đăng ký ngay')) ?></a></div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= gf_h(gf_url('assets/js/auth_validate.js')) ?>?v=<?= gf_h((string) @filemtime(dirname(__DIR__, 2) . '/assets/js/auth_validate.js')) ?>"></script>
<script>
document.querySelectorAll('.test-card[data-email]').forEach(card => card.addEventListener('click', () => {
    document.querySelectorAll('.test-card[data-email]').forEach(item => item.classList.remove('active'));
    card.classList.add('active');
    document.getElementById('username').value = card.dataset.email;
    document.getElementById('password').value = card.dataset.password;
}));
document.getElementById('btnTogglePassword')?.addEventListener('click', () => {
    const input = document.getElementById('password');
    const icon = document.getElementById('togglePasswordIcon');
    input.type = input.type === 'password' ? 'text' : 'password';
    icon.classList.toggle('fa-eye'); icon.classList.toggle('fa-eye-slash');
});
</script>
</body></html>
