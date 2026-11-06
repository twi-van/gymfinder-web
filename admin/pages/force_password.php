<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';

$user = gf_require_admin();
if (!gf_user_must_change_password($user)) {
    gf_redirect(gf_url('admin/pages/dashboard.php'));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    gf_require_csrf();
    $res = gf_complete_forced_password(
        (int) $user['id'],
        (string) ($_POST['password'] ?? ''),
        (string) ($_POST['confirm'] ?? '')
    );
    if ($res['ok']) {
        gf_redirect(gf_url('admin/pages/dashboard.php'));
    }
    $error = (string) ($res['error'] ?? 'Không thể đổi mật khẩu.');
}

$pageTitle = 'Đổi mật khẩu - GYMFINDER';
$bodyClass = 'auth-page';
$extraCss = ['assets/css/auth.css'];
require dirname(__DIR__, 2) . '/includes/layout_head.php';
?>
<div class="auth-wrapper">
    <div class="container">
        <div class="row align-items-start">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <div class="auth-left">
                    <div class="auth-brand mb-4">
                        <?= gf_logo('login') ?>
                        <?= gf_lang_switch_html() ?>
                    </div>
                    <div class="auth-badge"><i class="fa-solid fa-circle" style="font-size:6px"></i> <?= gf_h(gf_t('BẢO MẬT • ĐĂNG NHẬP LẠI')) ?></div>
                    <h1 class="auth-heading"><?= gf_h(gf_t('Đặt mật khẩu của bạn')) ?></h1>
                    <p class="auth-desc"><?= gf_h(gf_t('Mật khẩu tạm chỉ dùng để vào lần đầu. Hãy đặt mật khẩu mới trước khi dùng khu vực quản trị.')) ?></p>
                </div>
            </div>
            <div class="col-lg-5 ms-auto">
                <div class="auth-card">
                    <h2 class="auth-card-title"><?= gf_h(gf_t('Đổi mật khẩu để tiếp tục')) ?></h2>
                    <p class="auth-card-subtitle"><?= gf_h(gf_t('Không được dùng mật khẩu do hệ thống cấp. Hãy đặt mật khẩu khác.')) ?></p>
                    <?php if ($error): ?><div class="alert alert-danger"><?= gf_h(gf_t($error)) ?></div><?php endif; ?>
                    <form method="post" class="js-auth-validate" novalidate>
                        <?= gf_csrf_field() ?>
                        <div class="mb-3" data-auth-field>
                            <label class="form-label" for="new-password"><?= gf_h(gf_t('Mật khẩu mới *')) ?></label>
                            <div class="auth-input-group">
                                <i class="fa-solid fa-lock auth-input-icon"></i>
                                <input id="new-password" class="form-control" type="password" name="password" required minlength="8" data-required-msg="<?= gf_h(gf_t('Vui lòng nhập mật khẩu mới.')) ?>" data-minlength-msg="<?= gf_h(gf_t('Mật khẩu tối thiểu 8 ký tự.')) ?>" autocomplete="new-password" placeholder="<?= gf_h(gf_t('Ít nhất 8 ký tự')) ?>">
                                <button type="button" class="auth-toggle-password" aria-label="<?= gf_h(gf_t('Hiện mật khẩu')) ?>"><i class="fa-regular fa-eye-slash"></i></button>
                            </div>
                        </div>
                        <div class="mb-4" data-auth-field>
                            <label class="form-label" for="confirm-password"><?= gf_h(gf_t('Xác nhận mật khẩu *')) ?></label>
                            <div class="auth-input-group">
                                <i class="fa-solid fa-lock-open auth-input-icon"></i>
                                <input id="confirm-password" class="form-control" type="password" name="confirm" required minlength="8" data-required-msg="<?= gf_h(gf_t('Vui lòng xác nhận mật khẩu.')) ?>" data-minlength-msg="<?= gf_h(gf_t('Mật khẩu tối thiểu 8 ký tự.')) ?>" autocomplete="new-password" placeholder="<?= gf_h(gf_t('Nhập lại mật khẩu mới')) ?>">
                                <button type="button" class="auth-toggle-password" aria-label="<?= gf_h(gf_t('Hiện mật khẩu')) ?>"><i class="fa-regular fa-eye-slash"></i></button>
                            </div>
                        </div>
                        <button class="auth-btn-submit w-100 d-flex justify-content-center align-items-center gap-2" type="submit">
                            <?= gf_h(gf_t('Lưu mật khẩu mới')) ?> <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </form>
                    <form method="post" action="<?= gf_h(gf_url('pages/auth/logout.php')) ?>" class="text-center mt-3">
                        <?= gf_csrf_field() ?>
                        <button class="btn btn-link text-secondary" type="submit"><?= gf_h(gf_t('Đăng xuất')) ?></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= gf_h(gf_url('assets/js/auth_validate.js')) ?>?v=<?= gf_h((string) @filemtime(dirname(__DIR__, 2) . '/assets/js/auth_validate.js')) ?>"></script>
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
