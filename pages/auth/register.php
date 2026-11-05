<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
$error = '';
if (isset($_GET['error'])) {
    $error = gf_google_oauth_error_message((string) $_GET['error']);
}
$googleHref = gf_url('pages/auth/google_start.php?from=register');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['password'] ?? '') !== ($_POST['confirm'] ?? '')) {
        $error = 'Xác nhận mật khẩu không khớp.';
    } elseif (empty($_POST['agree'])) {
        $error = 'Bạn cần đồng ý điều khoản.';
    } else {
        $res = gf_register((string)($_POST['full_name'] ?? ''), (string)($_POST['email'] ?? ''), (string)($_POST['password'] ?? ''), $_POST['phone'] ?? null);
        if ($res['ok']) {
            gf_redirect(gf_url('pages/auth/login.php?registered=1'));
        }
        $error = $res['error'];
    }
}
$pageTitle = gf_t('Đăng ký - GYMFINDER');
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
                <p class="auth-desc"><?= gf_h(gf_t('Tạo tài khoản miễn phí để lưu phòng tập yêu thích, quản lý thông tin cá nhân và kết nối với huấn luyện viên tại TP.HCM.')) ?></p>
                <div class="auth-feature"><div class="auth-feature-icon"><i class="fa-solid fa-magnifying-glass"></i></div><div><div class="auth-feature-title"><?= gf_h(gf_t('Tìm kiếm dễ dàng')) ?></div><p class="auth-feature-desc"><?= gf_h(gf_t('Hàng trăm phòng tập chất lượng')) ?></p></div></div>
                <div class="auth-feature"><div class="auth-feature-icon"><i class="fa-solid fa-heart"></i></div><div><div class="auth-feature-title"><?= gf_h(gf_t('Lưu yêu thích')) ?></div><p class="auth-feature-desc"><?= gf_h(gf_t('Quản lý danh sách phòng tập của bạn')) ?></p></div></div>
                <div class="auth-feature"><div class="auth-feature-icon"><i class="fa-solid fa-user-group"></i></div><div><div class="auth-feature-title"><?= gf_h(gf_t('Kết nối huấn luyện viên')) ?></div><p class="auth-feature-desc"><?= gf_h(gf_t('Đồng hành cùng bạn trên hành trình tập luyện')) ?></p></div></div>
                </div>
            </div>
            <div class="col-lg-5 ms-auto">
                <div class="auth-card">
                    <h2 class="auth-card-title"><?= gf_h(gf_t('Đăng ký tài khoản')) ?></h2><p class="auth-card-subtitle"><?= gf_h(gf_t('Hoàn thiện thông tin bên dưới để tham gia cộng đồng.')) ?></p>
                    <?php if ($error): ?><div class="alert alert-danger"><?= gf_h(gf_t((string) $error)) ?></div><?php endif; ?>
                    <?= gf_google_button($googleHref, 'Đăng ký bằng Google') ?>
                    <div class="auth-divider auth-divider--tight"><hr><span><?= gf_h(gf_t('HOẶC DÙNG EMAIL')) ?></span><hr></div>
                    <form method="post" class="js-auth-validate" novalidate>
                        <?= gf_csrf_field() ?>
                        <div class="mb-3" data-auth-field><label class="form-label" for="reg-name"><?= gf_h(gf_t('Họ và tên *')) ?></label><div class="auth-input-group"><i class="fa-regular fa-id-card auth-input-icon"></i><input id="reg-name" class="form-control" name="full_name" required data-required-msg="<?= gf_h(gf_t('Vui lòng nhập họ và tên.')) ?>" placeholder="Nguyễn Văn A" value="<?= gf_h($_POST['full_name'] ?? '') ?>"></div></div>
                        <div class="mb-3" data-auth-field><label class="form-label" for="reg-email">Email *</label><div class="auth-input-group"><i class="fa-regular fa-envelope auth-input-icon"></i><input id="reg-email" class="form-control" type="email" name="email" required data-required-msg="<?= gf_h(gf_t('Vui lòng nhập email.')) ?>" data-type-msg="<?= gf_h(gf_t('Email không hợp lệ.')) ?>" placeholder="you@email.com" value="<?= gf_h($_POST['email'] ?? '') ?>" autocomplete="email"></div></div>
                        <div class="mb-3"><label class="form-label" for="reg-phone"><?= gf_h(gf_t('Số điện thoại')) ?> <span class="text-secondary fw-normal"><?= gf_h(gf_t('(tuỳ chọn)')) ?></span></label><div class="auth-input-group"><i class="fa-solid fa-phone auth-input-icon"></i><input id="reg-phone" class="form-control" type="tel" name="phone" placeholder="<?= gf_h(gf_t('Ví dụ: 0901234567')) ?>" value="<?= gf_h($_POST['phone'] ?? '') ?>" autocomplete="tel"></div><div class="form-text"><?= gf_h(gf_t('9–14 chữ số, có thể bắt đầu bằng +.')) ?></div></div>
                        <div class="mb-3" data-auth-field><label class="form-label" for="reg-password"><?= gf_h(gf_t('Mật khẩu *')) ?></label><div class="auth-input-group"><i class="fa-solid fa-lock auth-input-icon"></i><input id="reg-password" class="form-control" type="password" name="password" required minlength="8" data-required-msg="<?= gf_h(gf_t('Vui lòng nhập mật khẩu.')) ?>" data-minlength-msg="<?= gf_h(gf_t('Mật khẩu tối thiểu 8 ký tự.')) ?>" placeholder="<?= gf_h(gf_t('Tạo mật khẩu (ít nhất 8 ký tự)')) ?>"><button type="button" class="auth-toggle-password" aria-label="<?= gf_h(gf_t('Hiện mật khẩu')) ?>"><i class="fa-regular fa-eye-slash"></i></button></div></div>
                        <div class="mb-4" data-auth-field><label class="form-label" for="reg-confirm"><?= gf_h(gf_t('Xác nhận mật khẩu *')) ?></label><div class="auth-input-group"><i class="fa-solid fa-lock-open auth-input-icon"></i><input id="reg-confirm" class="form-control" type="password" name="confirm" required data-required-msg="<?= gf_h(gf_t('Vui lòng xác nhận mật khẩu.')) ?>" placeholder="<?= gf_h(gf_t('Nhập lại mật khẩu vừa tạo')) ?>"><button type="button" class="auth-toggle-password" aria-label="<?= gf_h(gf_t('Hiện mật khẩu')) ?>"><i class="fa-regular fa-eye-slash"></i></button></div></div>
                        <div class="form-check mb-4" data-auth-field><input class="form-check-input js-required" type="checkbox" name="agree" id="ag" value="1" required data-required-msg="<?= gf_h(gf_t('Bạn cần đồng ý điều khoản và chính sách bảo mật.')) ?>" <?= !empty($_POST['agree']) ? 'checked' : '' ?>><label class="form-check-label text-secondary" for="ag" style="font-size:13px"><?= gf_h(gf_t('Tôi đồng ý với')) ?> <a href="<?= gf_h(gf_url('pages/legal/terms.php')) ?>" class="text-primary fw-semibold text-decoration-none" target="_blank" rel="noopener"><?= gf_h(gf_t('Điều khoản dịch vụ')) ?></a> <?= gf_h(gf_t('và')) ?> <a href="<?= gf_h(gf_url('pages/legal/privacy.php')) ?>" class="text-primary fw-semibold text-decoration-none" target="_blank" rel="noopener"><?= gf_h(gf_t('Chính sách bảo mật')) ?></a> <?= gf_h(gf_t('của GYMFINDER.')) ?></label></div>
                        <button class="auth-btn-submit w-100 d-flex justify-content-center align-items-center gap-2"><?= gf_h(gf_t('Đăng ký ngay')) ?> <i class="fa-solid fa-arrow-right"></i></button>
                    </form>
                    <p class="text-center mt-5" style="font-size:15px;color:#64748b"><?= gf_h(gf_t('Đã có tài khoản?')) ?> <a class="text-primary text-decoration-none fw-bold" href="<?= gf_h(gf_url('pages/auth/login.php')) ?>"><?= gf_h(gf_t('Đăng nhập ngay')) ?></a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= gf_h(gf_url('assets/js/auth_validate.js')) ?>?v=<?= gf_h((string) @filemtime(dirname(__DIR__, 2) . '/assets/js/auth_validate.js')) ?>"></script>
<script>document.querySelectorAll('.auth-toggle-password').forEach(b=>b.addEventListener('click',()=>{const i=b.parentElement.querySelector('input');const icon=b.querySelector('i');i.type=i.type==='password'?'text':'password';icon.classList.toggle('fa-eye');icon.classList.toggle('fa-eye-slash')}));</script>
</body></html>

