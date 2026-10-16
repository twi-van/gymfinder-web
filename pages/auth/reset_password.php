<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';

$state = gf_password_reset_state();
if (!$state || empty($state['verified']) || empty($state['user_id']) || (($state['expires'] ?? 0) < time())) {
    gf_password_reset_clear();
    gf_redirect(gf_url('pages/auth/forgot_password.php'));
}

$error = '';
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    gf_require_csrf();
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm'] ?? '');
    $res = gf_password_reset_complete($password, $confirm);
    if ($res['ok']) {
        $done = true;
    } else {
        $error = $res['error'] ?? 'Không thể đặt lại mật khẩu.';
        if (($res['code'] ?? '') === 'EXPIRED' || ($res['code'] ?? '') === 'INVALID') {
            gf_redirect(gf_url('pages/auth/forgot_password.php'));
        }
    }
}

$pageTitle = 'Đặt lại mật khẩu - GYMFINDER';
$bodyClass = 'auth-page';
$extraCss = ['assets/css/auth.css'];
require dirname(__DIR__, 2) . '/includes/layout_head.php';
?>
<div class="auth-wrapper">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <div class="auth-left pe-lg-4">
                    <div class="auth-brand mb-5">
                        <?= gf_logo('login') ?>
                    </div>
                    <div class="auth-badge"><i class="fa-solid fa-circle" style="font-size:6px"></i> BẢO MẬT • ĐĂNG NHẬP LẠI</div>
                    <h1 class="auth-heading">Tạo <span class="text-primary">mật khẩu mới</span><br>cho tài khoản</h1>
                    <p class="auth-desc">Chọn mật khẩu mạnh (ít nhất 8 ký tự). Sau khi lưu, bạn có thể đăng nhập lại bằng mật khẩu mới.</p>
                </div>
            </div>
            <div class="col-lg-5 ms-auto">
                <div class="auth-card">
                    <div class="auth-steps mb-4">
                        <span class="auth-step done">1. Email</span>
                        <span class="auth-step-sep"></span>
                        <span class="auth-step done">2. OTP</span>
                        <span class="auth-step-sep"></span>
                        <span class="auth-step active">3. Mật khẩu mới</span>
                    </div>

                    <?php if ($done): ?>
                        <div class="text-center py-2">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-4" style="width:72px;height:72px;background:rgba(0,201,177,0.12);color:var(--primary-color,#00c9b1);">
                                <i class="fa-solid fa-circle-check fs-3"></i>
                            </div>
                            <h2 class="auth-card-title mb-2">Đặt lại mật khẩu thành công</h2>
                            <p class="auth-card-subtitle mb-4">Bạn có thể đăng nhập bằng mật khẩu mới.</p>
                            <a href="<?= gf_h(gf_url('pages/auth/login.php')) ?>" class="auth-btn-submit w-100 d-inline-flex justify-content-center align-items-center gap-2 text-decoration-none">
                                Đăng nhập ngay <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <h2 class="auth-card-title">Đặt mật khẩu mới</h2>
                        <p class="auth-card-subtitle">OTP đã được xác thực. Hãy tạo mật khẩu mới cho tài khoản.</p>
                        <?php if ($error): ?><div class="alert alert-danger"><?= gf_h($error) ?></div><?php endif; ?>
                        <form method="post">
                            <?= gf_csrf_field() ?>
                            <div class="mb-3">
                                <label class="form-label" for="new-password">Mật khẩu mới *</label>
                                <div class="auth-input-group">
                                    <i class="fa-solid fa-lock auth-input-icon"></i>
                                    <input id="new-password" class="form-control" type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="Ít nhất 8 ký tự">
                                    <button type="button" class="auth-toggle-password" aria-label="Hiện mật khẩu"><i class="fa-regular fa-eye-slash"></i></button>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label" for="confirm-password">Xác nhận mật khẩu *</label>
                                <div class="auth-input-group">
                                    <i class="fa-solid fa-lock-open auth-input-icon"></i>
                                    <input id="confirm-password" class="form-control" type="password" name="confirm" required minlength="8" autocomplete="new-password" placeholder="Nhập lại mật khẩu mới">
                                    <button type="button" class="auth-toggle-password" aria-label="Hiện mật khẩu"><i class="fa-regular fa-eye-slash"></i></button>
                                </div>
                            </div>
                            <button class="auth-btn-submit w-100 d-flex justify-content-center align-items-center gap-2" type="submit">
                                Lưu mật khẩu mới <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.auth-toggle-password').forEach((b) => b.addEventListener('click', () => {
    const i = b.parentElement.querySelector('input');
    const icon = b.querySelector('i');
    i.type = i.type === 'password' ? 'text' : 'password';
    icon.classList.toggle('fa-eye');
    icon.classList.toggle('fa-eye-slash');
}));
</script>
</body></html>
