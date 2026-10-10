<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
$error = '';
if (isset($_GET['error']) && $_GET['error'] === 'locked') {
    $error = 'Tài khoản đã bị khóa.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $res = gf_login(trim((string)($_POST['login'] ?? '')), (string)($_POST['password'] ?? ''));
    if ($res['ok']) {
        $to = $_GET['redirect'] ?? $_POST['redirect'] ?? '';
        if (!$to) {
            $to = (($res['user']['role'] ?? '') === 'admin') ? gf_url('admin/pages/dashboard.php') : gf_url('index.php');
        }
        gf_redirect($to);
    }
    $error = $res['error'];
}
$pageTitle = 'Đăng nhập - GYMFINDER';
$bodyClass = 'auth-page';
$extraCss = ['assets/css/auth.css'];
require dirname(__DIR__, 2) . '/includes/layout_head.php';
?>
<div class="auth-wrapper">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <div class="auth-left pe-lg-4">
                    <a class="d-flex align-items-center gap-2 text-decoration-none mb-5" href="<?= gf_h(gf_url('index.php')) ?>">
                        <span class="border rounded d-inline-flex align-items-center justify-content-center text-primary bg-white shadow-sm" style="width:50px;height:50px;border-color:#f3f4f6!important"><i class="fa-solid fa-dumbbell fa-lg"></i></span>
                        <span class="fw-bold" style="font-size:32px;color:#1e293b;letter-spacing:-.5px">GYM<span class="text-primary">FINDER</span></span>
                    </a>
                    <div class="auth-badge"><i class="fa-solid fa-circle" style="font-size:6px"></i> KHÁM PHÁ • KẾT NỐI • TẬP LUYỆN</div>
                    <h1 class="auth-heading">Hành trình <span class="text-primary">khỏe mạnh</span><br>bắt đầu từ hôm nay</h1>
                    <p class="auth-desc">Đăng nhập để lưu phòng tập yêu thích, quản lý thông tin cá nhân và kết nối với huấn luyện viên tại TP.HCM.</p>
                    <div class="auth-features">
                        <div class="auth-feature"><div class="auth-feature-icon"><i class="fa-solid fa-magnifying-glass"></i></div><div><div class="auth-feature-title">Tìm kiếm dễ dàng</div><p class="auth-feature-desc">Hàng trăm phòng tập chất lượng</p></div></div>
                        <div class="auth-feature"><div class="auth-feature-icon"><i class="fa-solid fa-heart"></i></div><div><div class="auth-feature-title">Lưu yêu thích</div><p class="auth-feature-desc">Quản lý danh sách phòng tập của bạn</p></div></div>
                        <div class="auth-feature"><div class="auth-feature-icon"><i class="fa-solid fa-user-group"></i></div><div><div class="auth-feature-title">Kết nối huấn luyện viên</div><p class="auth-feature-desc">Đồng hành cùng bạn trên hành trình tập luyện</p></div></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 ms-auto">
                <div class="auth-card">
                    <h2 class="auth-card-title">Đăng nhập</h2>
                    <p class="auth-card-subtitle">Chào mừng bạn quay trở lại! Vui lòng đăng nhập để tiếp tục.</p>
                    <?php if (!empty($_GET['registered'])): ?><div class="alert alert-success">Đăng ký thành công. Vui lòng đăng nhập.</div><?php endif; ?>
                    <?php if ($error): ?><div class="alert alert-danger"><?= gf_h($error) ?></div><?php endif; ?>
                    <form method="post" id="loginForm">
                        <?= gf_csrf_field() ?>
                        <input type="hidden" name="redirect" value="<?= gf_h($_GET['redirect'] ?? '') ?>">
                        <div class="mb-4">
                            <label class="form-label" for="username">Email / Số điện thoại</label>
                            <div class="auth-input-group"><i class="fa-regular fa-user auth-input-icon"></i><input id="username" class="form-control" name="login" required autocomplete="username" placeholder="Nhập email hoặc số điện thoại" value="<?= gf_h($_POST['login'] ?? '') ?>"></div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" for="password">Mật khẩu</label>
                            <div class="auth-input-group"><i class="fa-solid fa-lock auth-input-icon"></i><input id="password" class="form-control" type="password" name="password" required autocomplete="current-password" placeholder="Nhập mật khẩu"><button type="button" class="auth-toggle-password" id="btnTogglePassword" aria-label="Hiện mật khẩu"><i class="fa-regular fa-eye-slash" id="togglePasswordIcon"></i></button></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-5"><div class="form-check"><input class="form-check-input" type="checkbox" id="rememberMe"><label class="form-check-label text-secondary" for="rememberMe" style="font-size:14px">Ghi nhớ đăng nhập</label></div><a href="#" class="text-primary text-decoration-none fw-semibold" style="font-size:14px" aria-label="Quên mật khẩu (chưa hỗ trợ)">Quên mật khẩu?</a></div>
                        <button class="auth-btn-submit w-100 d-flex justify-content-center align-items-center gap-2" type="submit">Đăng nhập <i class="fa-solid fa-arrow-right"></i></button>
                    </form>
                    <div class="auth-divider"><hr><span>CHỌN NHANH TÀI KHOẢN KIỂM THỬ</span><hr></div>
                    <div class="row g-3 mb-5">
                        <div class="col-6"><button type="button" class="test-card w-100 text-start" data-email="u02@gymfinder.test" data-password="password"><span class="test-status-dot"></span><div class="d-flex align-items-center gap-2 mb-2"><i class="fa-regular fa-user text-primary"></i><span class="fw-bold text-dark">Người dùng</span></div><div class="text-primary fw-semibold small text-break">u02@gymfinder.test</div></button></div>
                        <div class="col-6"><button type="button" class="test-card w-100 text-start" data-email="u01@gymfinder.test" data-password="password"><span class="test-status-dot"></span><div class="d-flex align-items-center gap-2 mb-2"><i class="fa-solid fa-shield-halved text-primary"></i><span class="fw-bold text-dark">Quản trị viên</span></div><div class="text-primary fw-semibold small text-break">u01@gymfinder.test</div></button></div>
                    </div>
                    <div class="text-center mt-auto" style="font-size:15px;color:#64748b">Chưa có tài khoản? <a class="text-primary text-decoration-none fw-bold" href="<?= gf_h(gf_url('pages/auth/register.php')) ?>">Đăng ký ngay</a></div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
