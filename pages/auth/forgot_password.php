<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';

$error = '';
$loginValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    gf_require_csrf();
    $loginValue = trim((string) ($_POST['login'] ?? ''));
    $res = gf_password_reset_begin($loginValue);
    if ($res['ok']) {
        gf_redirect(gf_url('pages/auth/forgot_verify_otp.php'));
    }
    $error = $res['error'] ?? 'Không thể gửi yêu cầu.';
}

$pageTitle = 'Quên mật khẩu - GYMFINDER';
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
                    <div class="auth-badge"><i class="fa-solid fa-circle" style="font-size:6px"></i> KHÁM PHÁ • KẾT NỐI • TẬP LUYỆN</div>
                    <h1 class="auth-heading">Lấy lại <span class="text-primary">quyền truy cập</span><br>tài khoản của bạn</h1>
                    <p class="auth-desc">Nhập email đã đăng ký để nhận mã OTP 6 số và đặt lại mật khẩu. Bước gửi email thật sẽ được kích hoạt khi cấu hình máy chủ thư.</p>
                    <div class="auth-features">
                        <div class="auth-feature">
                            <div class="auth-feature-icon"><i class="fa-solid fa-shield-halved"></i></div>
                            <div>
                                <div class="auth-feature-title">Bảo mật bằng OTP</div>
                                <p class="auth-feature-desc">Mã 6 số có hiệu lực trong 10 phút</p>
                            </div>
                        </div>
                        <div class="auth-feature">
                            <div class="auth-feature-icon"><i class="fa-solid fa-envelope-open-text"></i></div>
                            <div>
                                <div class="auth-feature-title">Xác nhận qua email</div>
                                <p class="auth-feature-desc">Chỉ người nhận được mã mới đặt lại được mật khẩu</p>
                            </div>
                        </div>
                        <div class="auth-feature">
                            <div class="auth-feature-icon"><i class="fa-solid fa-key"></i></div>
                            <div>
                                <div class="auth-feature-title">Đặt mật khẩu mới</div>
                                <p class="auth-feature-desc">Sau khi xác thực OTP, tạo mật khẩu mới ngay trên website</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 ms-auto">
                <div class="auth-card">
                    <div class="auth-steps mb-4">
                        <span class="auth-step active">1. Email</span>
                        <span class="auth-step-sep"></span>
                        <span class="auth-step">2. OTP</span>
                        <span class="auth-step-sep"></span>
                        <span class="auth-step">3. Mật khẩu mới</span>
                    </div>
                    <h2 class="auth-card-title">Quên mật khẩu</h2>
                    <p class="auth-card-subtitle">Nhập email hoặc số điện thoại đã dùng khi đăng ký để nhận mã OTP.</p>
                    <?php if ($error): ?><div class="alert alert-danger"><?= gf_h($error) ?></div><?php endif; ?>
                    <form method="post" id="forgotPasswordForm">
                        <?= gf_csrf_field() ?>
                        <div class="mb-4">
                            <label class="form-label" for="forgot-login">Email / Số điện thoại</label>
                            <div class="auth-input-group">
                                <i class="fa-regular fa-envelope auth-input-icon"></i>
                                <input
                                    id="forgot-login"
                                    class="form-control"
                                    name="login"
                                    required
                                    autocomplete="username"
                                    placeholder="you@email.com hoặc số điện thoại"
                                    value="<?= gf_h($loginValue) ?>"
                                >
                            </div>
                        </div>
                        <button class="auth-btn-submit w-100 d-flex justify-content-center align-items-center gap-2" type="submit">
                            Gửi mã OTP <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </form>
                    <p class="text-center mt-5 mb-0" style="font-size:15px;color:#64748b">
                        Nhớ mật khẩu?
                        <a class="text-primary text-decoration-none fw-bold" href="<?= gf_h(gf_url('pages/auth/login.php')) ?>">Đăng nhập ngay</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
