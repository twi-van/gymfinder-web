<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['password'] ?? '') !== ($_POST['confirm'] ?? '')) {
        $error = 'Xác nhận mật khẩu không khớp.';
    } elseif (empty($_POST['agree'])) {
        $error = 'Bạn cần đồng ý điều khoản.';
    } else {
        $res = gf_register((string)($_POST['full_name'] ?? ''), (string)($_POST['email'] ?? ''), (string)($_POST['password'] ?? ''), $_POST['phone'] ?? null, $_POST['goal'] ?? null);
        if ($res['ok']) {
            gf_redirect(gf_url('pages/auth/login.php?registered=1'));
        }
        $error = $res['error'];
    }
}
$pageTitle = 'Đăng ký - GYMFINDER';
$bodyClass = 'auth-page';
$extraCss = ['assets/css/auth.css'];
require dirname(__DIR__, 2) . '/includes/layout_head.php';
?>
<div class="auth-wrapper">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <div class="auth-left pe-lg-4">
                <a href="<?= gf_h(gf_url('index.php')) ?>" class="d-flex align-items-center gap-2 text-decoration-none mb-5"><span class="border rounded d-inline-flex align-items-center justify-content-center text-primary bg-white shadow-sm" style="width:50px;height:50px;border-color:#f3f4f6!important"><i class="fa-solid fa-dumbbell fa-lg"></i></span><span class="fw-bold" style="font-size:32px;color:#1e293b;letter-spacing:-.5px">GYM<span class="text-primary">FINDER</span></span></a>
                <div class="auth-badge mt-4"><i class="fa-solid fa-circle" style="font-size:6px"></i> KHÁM PHÁ • KẾT NỐI • TẬP LUYỆN</div>
                <h1 class="auth-heading">Hành trình <span class="text-primary">khỏe mạnh</span><br>bắt đầu từ hôm nay</h1>
                <p class="auth-desc">Tạo tài khoản miễn phí để lưu phòng tập yêu thích, quản lý thông tin cá nhân và kết nối với huấn luyện viên tại TP.HCM.</p>
                <div class="auth-feature"><div class="auth-feature-icon"><i class="fa-solid fa-magnifying-glass"></i></div><div><div class="auth-feature-title">Tìm kiếm dễ dàng</div><p class="auth-feature-desc">Hàng trăm phòng tập chất lượng</p></div></div>
                <div class="auth-feature"><div class="auth-feature-icon"><i class="fa-solid fa-heart"></i></div><div><div class="auth-feature-title">Lưu yêu thích</div><p class="auth-feature-desc">Quản lý danh sách phòng tập của bạn</p></div></div>
                <div class="auth-feature"><div class="auth-feature-icon"><i class="fa-solid fa-user-group"></i></div><div><div class="auth-feature-title">Kết nối huấn luyện viên</div><p class="auth-feature-desc">Đồng hành cùng bạn trên hành trình tập luyện</p></div></div>
                </div>
            </div>
            <div class="col-lg-5 ms-auto">
                <div class="auth-card">
                    <h2 class="auth-card-title">Đăng ký tài khoản</h2><p class="auth-card-subtitle">Hoàn thiện thông tin bên dưới để tham gia cộng đồng.</p>
                    <?php if ($error): ?><div class="alert alert-danger"><?= gf_h($error) ?></div><?php endif; ?>
                    <form method="post">
                        <?= gf_csrf_field() ?>
                        <div class="mb-3"><label class="form-label" for="reg-name">Họ và tên</label><div class="auth-input-group"><i class="fa-regular fa-id-card auth-input-icon"></i><input id="reg-name" class="form-control" name="full_name" required placeholder="Nguyễn Văn A" value="<?= gf_h($_POST['full_name'] ?? '') ?>"></div></div>
                        <div class="mb-3"><label class="form-label" for="reg-email">Email / Số điện thoại</label><div class="auth-input-group"><i class="fa-regular fa-user auth-input-icon"></i><input id="reg-email" class="form-control" type="email" name="email" required placeholder="Nhập email hoặc số điện thoại" value="<?= gf_h($_POST['email'] ?? '') ?>"></div></div>
                        <input type="hidden" name="phone" value="<?= gf_h($_POST['phone'] ?? '') ?>">
                        <div class="mb-3"><label class="form-label" for="reg-password">Mật khẩu</label><div class="auth-input-group"><i class="fa-solid fa-lock auth-input-icon"></i><input id="reg-password" class="form-control" type="password" name="password" required minlength="8" placeholder="Tạo mật khẩu (Ít nhất 8 ký tự)"><button type="button" class="auth-toggle-password" aria-label="Hiện mật khẩu"><i class="fa-regular fa-eye-slash"></i></button></div></div>
                        <div class="mb-4"><label class="form-label" for="reg-confirm">Xác nhận mật khẩu</label><div class="auth-input-group"><i class="fa-solid fa-lock-open auth-input-icon"></i><input id="reg-confirm" class="form-control" type="password" name="confirm" required placeholder="Nhập lại mật khẩu vừa tạo"><button type="button" class="auth-toggle-password" aria-label="Hiện mật khẩu"><i class="fa-regular fa-eye-slash"></i></button></div></div>
                        <div class="form-check mb-4"><input class="form-check-input" type="checkbox" name="agree" id="ag" value="1" <?= !empty($_POST['agree']) ? 'checked' : '' ?>><label class="form-check-label text-secondary" for="ag" style="font-size:13px">Tôi đồng ý với <a href="#" class="text-primary fw-semibold text-decoration-none">Điều khoản dịch vụ</a> và <a href="#" class="text-primary fw-semibold text-decoration-none">Chính sách bảo mật</a> của GYMFINDER.</label></div>
                        <button class="auth-btn-submit w-100 d-flex justify-content-center align-items-center gap-2">Đăng ký ngay <i class="fa-solid fa-arrow-right"></i></button>
                    </form>
                    <p class="text-center mt-5" style="font-size:15px;color:#64748b">Đã có tài khoản? <a class="text-primary text-decoration-none fw-bold" href="<?= gf_h(gf_url('pages/auth/login.php')) ?>">Đăng nhập ngay</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>document.querySelectorAll('.auth-toggle-password').forEach(b=>b.addEventListener('click',()=>{const i=b.parentElement.querySelector('input');const icon=b.querySelector('i');i.type=i.type==='password'?'text':'password';icon.classList.toggle('fa-eye');icon.classList.toggle('fa-eye-slash')}));</script>
</body></html>

