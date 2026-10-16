<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';

$state = gf_password_reset_state();
if (!$state || (($state['expires'] ?? 0) < time())) {
    gf_password_reset_clear();
    gf_redirect(gf_url('pages/auth/forgot_password.php'));
}

$error = '';
$otpValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    gf_require_csrf();
    $action = (string) ($_POST['action'] ?? 'verify');
    if ($action === 'resend') {
        $res = gf_password_reset_begin((string) ($state['login_display'] ?? ''));
        if ($res['ok']) {
            gf_redirect(gf_url('pages/auth/forgot_verify_otp.php?resent=1'));
        }
        $error = $res['error'] ?? 'Không thể gửi lại mã.';
        $state = gf_password_reset_state() ?? $state;
    } else {
        $digits = [];
        for ($i = 0; $i < 6; $i++) {
            $digits[] = preg_replace('/\D/', '', (string) ($_POST['otp'][$i] ?? '')) ?? '';
        }
        $otpValue = implode('', $digits);
        if ($otpValue === '' && isset($_POST['otp_full'])) {
            $otpValue = preg_replace('/\D/', '', (string) $_POST['otp_full']) ?? '';
        }
        $res = gf_password_reset_verify_otp($otpValue);
        if ($res['ok']) {
            gf_redirect(gf_url('pages/auth/reset_password.php'));
        }
        $error = $res['error'] ?? 'Mã OTP không hợp lệ.';
        if (($res['code'] ?? '') === 'EXPIRED' || ($res['code'] ?? '') === 'LOCKED') {
            gf_redirect(gf_url('pages/auth/forgot_password.php'));
        }
        $state = gf_password_reset_state() ?? $state;
    }
}

$mask = (string) ($state['mask'] ?? $state['login_display'] ?? '');
$demoOtp = $state['demo_otp'] ?? null;
$resent = !empty($_GET['resent']);

$pageTitle = 'Nhập mã OTP - GYMFINDER';
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
                    <div class="auth-badge"><i class="fa-solid fa-circle" style="font-size:6px"></i> XÁC THỰC • BẢO MẬT</div>
                    <h1 class="auth-heading">Nhập <span class="text-primary">mã OTP</span><br>để tiếp tục</h1>
                    <p class="auth-desc">Mã gồm 6 chữ số được gửi tới email gắn với tài khoản. Mã có hiệu lực trong 10 phút và chỉ dùng một lần.</p>
                </div>
            </div>
            <div class="col-lg-5 ms-auto">
                <div class="auth-card">
                    <div class="auth-steps mb-4">
                        <span class="auth-step done">1. Email</span>
                        <span class="auth-step-sep"></span>
                        <span class="auth-step active">2. OTP</span>
                        <span class="auth-step-sep"></span>
                        <span class="auth-step">3. Mật khẩu mới</span>
                    </div>
                    <h2 class="auth-card-title">Xác thực OTP</h2>
                    <p class="auth-card-subtitle">
                        Đã gửi mã tới tài khoản liên quan
                        <?php if ($mask !== ''): ?>(<strong><?= gf_h($mask) ?></strong>)<?php endif; ?>.
                    </p>
                    <?php if ($resent): ?>
                        <div class="alert alert-success small">Đã tạo mã OTP mới. Vui lòng kiểm tra và nhập lại.</div>
                    <?php endif; ?>
                    <?php if ($demoOtp): ?>
                        <div class="alert alert-warning small mb-3">
                            <i class="fa-solid fa-flask me-1"></i>
                            <strong>Chế độ UI / demo:</strong> chưa gửi email SMTP.
                            Mã OTP tạm thời: <span class="fw-bold font-monospace fs-5"><?= gf_h((string) $demoOtp) ?></span>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info small mb-3">
                            Nếu thông tin đăng nhập trùng tài khoản trên hệ thống, mã OTP đã được tạo.
                            (Gửi email thật sẽ bổ sung khi cấu hình máy chủ thư.)
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?><div class="alert alert-danger"><?= gf_h($error) ?></div><?php endif; ?>

                    <form method="post" id="otpForm" autocomplete="one-time-code">
                        <?= gf_csrf_field() ?>
                        <input type="hidden" name="action" value="verify">
                        <label class="form-label">Mã OTP (6 chữ số)</label>
                        <div class="otp-boxes mb-2" id="otpBoxes">
                            <?php for ($i = 0; $i < 6; $i++): ?>
                                <input
                                    type="text"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    maxlength="1"
                                    class="otp-input form-control"
                                    name="otp[]"
                                    aria-label="OTP chữ số <?= $i + 1 ?>"
                                    value="<?= gf_h($otpValue[$i] ?? '') ?>"
                                >
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="otp_full" id="otpFull" value="">
                        <button class="auth-btn-submit w-100 d-flex justify-content-center align-items-center gap-2 mt-4" type="submit">
                            Xác nhận OTP <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </form>

                    <form method="post" class="mt-3">
                        <?= gf_csrf_field() ?>
                        <input type="hidden" name="action" value="resend">
                        <button type="submit" class="btn btn-link text-primary text-decoration-none fw-semibold w-100 p-0" style="font-size:14px">
                            Gửi lại mã OTP
                        </button>
                    </form>

                    <p class="text-center mt-4 mb-0" style="font-size:15px;color:#64748b">
                        <a class="text-secondary text-decoration-none" href="<?= gf_h(gf_url('pages/auth/forgot_password.php')) ?>">← Đổi email / số điện thoại</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    const inputs = Array.from(document.querySelectorAll('.otp-input'));
    const full = document.getElementById('otpFull');
    const form = document.getElementById('otpForm');
    if (!inputs.length) return;

    function syncFull() {
        full.value = inputs.map((i) => i.value.replace(/\D/g, '')).join('');
    }

    inputs.forEach((input, idx) => {
        input.addEventListener('input', () => {
            input.value = input.value.replace(/\D/g, '').slice(0, 1);
            if (input.value && idx < inputs.length - 1) inputs[idx + 1].focus();
            syncFull();
        });
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !input.value && idx > 0) {
                inputs[idx - 1].focus();
            }
        });
        input.addEventListener('paste', (e) => {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            text.split('').forEach((ch, i) => { if (inputs[i]) inputs[i].value = ch; });
            syncFull();
            const next = inputs[Math.min(text.length, inputs.length - 1)];
            if (next) next.focus();
        });
    });

    form.addEventListener('submit', syncFull);
    if (inputs[0]) inputs[0].focus();
})();
</script>
</body></html>
