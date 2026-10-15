<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';

$error = '';
$submitted = false;
$loginValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    gf_require_csrf();
    $loginValue = trim((string) ($_POST['login'] ?? ''));
    if ($loginValue === '') {
        $error = 'Vui lòng nhập email hoặc số điện thoại đã đăng ký.';
    } elseif (mb_strlen($loginValue) > 120) {
        $error = 'Thông tin đăng nhập không hợp lệ.';
    } else {
        // UI sẵn sàng; gửi email / tạo token đặt lại mật khẩu sẽ bổ sung phía máy chủ.
        $submitted = true;
    }
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
                    <p class="auth-desc">Nhập email hoặc số điện thoại đã đăng ký để nhận hướng dẫn đặt lại mật khẩu và tiếp tục hành trình tập luyện cùng GYMFINDER.</p>
                    <div class="auth-features">
                        <div class="auth-feature">
                            <div class="auth-feature-icon"><i class="fa-solid fa-shield-halved"></i></div>
                            <div>
                                <div class="auth-feature-title">Bảo mật tài khoản</div>
                                <p class="auth-feature-desc">Chỉ bạn mới có thể đặt lại mật khẩu</p>
                            </div>
                        </div>
                        <div class="auth-feature">
                            <div class="auth-feature-icon"><i class="fa-solid fa-envelope-open-text"></i></div>
                            <div>
                                <div class="auth-feature-title">Hướng dẫn rõ ràng</div>
                                <p class="auth-feature-desc">Nhận bước tiếp theo ngay sau khi gửi yêu cầu</p>
                            </div>
                        </div>
                        <div class="auth-feature">
                            <div class="auth-feature-icon"><i class="fa-solid fa-arrow-right-to-bracket"></i></div>
                            <div>
                                <div class="auth-feature-title">Đăng nhập lại nhanh</div>
                                <p class="auth-feature-desc">Quay lại tìm phòng tập và huấn luyện viên</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 ms-auto">
                <div class="auth-card">
                    <?php if ($submitted): ?>
                        <div class="text-center py-2">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-4" style="width:72px;height:72px;background:rgba(0,201,177,0.12);color:var(--primary-color,#00c9b1);">
                                <i class="fa-solid fa-paper-plane fs-3"></i>
                            </div>
                            <h2 class="auth-card-title mb-2">Đã ghi nhận yêu cầu</h2>
                            <p class="auth-card-subtitle mb-4">
                                Nếu <strong><?= gf_h($loginValue) ?></strong> trùng với tài khoản trên hệ thống, hướng dẫn đặt lại mật khẩu sẽ được gửi khi chức năng gửi thư được kích hoạt.
                            </p>
                            <div class="alert alert-info text-start small mb-4">
                                <i class="fa-solid fa-circle-info me-1"></i>
                                Hiện tại hệ thống chưa gửi email tự động. Nếu bạn cần hỗ trợ ngay, hãy liên hệ quản trị viên hoặc đăng nhập lại nếu đã nhớ mật khẩu.
                            </div>
                            <a href="<?= gf_h(gf_url('pages/auth/login.php')) ?>" class="auth-btn-submit w-100 d-inline-flex justify-content-center align-items-center gap-2 text-decoration-none">
                                Quay lại đăng nhập <i class="fa-solid fa-arrow-right"></i>
                            </a>
                            <p class="text-center mt-4 mb-0" style="font-size:15px;color:#64748b">
                                <a class="text-primary text-decoration-none fw-bold" href="<?= gf_h(gf_url('pages/auth/forgot_password.php')) ?>">Gửi lại yêu cầu khác</a>
                            </p>
                        </div>
                    <?php else: ?>
                        <h2 class="auth-card-title">Quên mật khẩu</h2>
                        <p class="auth-card-subtitle">Nhập email hoặc số điện thoại đã dùng khi đăng ký để tiếp tục.</p>
                        <?php if ($error): ?><div class="alert alert-danger"><?= gf_h($error) ?></div><?php endif; ?>
                        <form method="post" id="forgotPasswordForm">
                            <?= gf_csrf_field() ?>
                            <div class="mb-4">
                                <label class="form-label" for="forgot-login">Email / Số điện thoại</label>
                                <div class="auth-input-group">
                                    <i class="fa-regular fa-user auth-input-icon"></i>
                                    <input
                                        id="forgot-login"
                                        class="form-control"
                                        name="login"
                                        required
                                        autocomplete="username"
                                        placeholder="Nhập email hoặc số điện thoại"
                                        value="<?= gf_h($loginValue) ?>"
                                    >
                                </div>
                            </div>
                            <button class="auth-btn-submit w-100 d-flex justify-content-center align-items-center gap-2" type="submit">
                                Gửi yêu cầu <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </form>
                        <p class="text-center mt-5 mb-0" style="font-size:15px;color:#64748b">
                            Nhớ mật khẩu?
                            <a class="text-primary text-decoration-none fw-bold" href="<?= gf_h(gf_url('pages/auth/login.php')) ?>">Đăng nhập ngay</a>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
