<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
require_once dirname(__DIR__) . '/components/table.php';
$me = gf_require_admin();
$notice = (string) ($_SESSION['admin_users_notice'] ?? '');
$error = (string) ($_SESSION['admin_users_error'] ?? '');
unset($_SESSION['admin_users_notice'], $_SESSION['admin_users_error']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    gf_require_csrf();
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'invite_admin') {
        $res = gf_admin_invite_admin((string) ($_POST['full_name'] ?? ''), (string) ($_POST['email'] ?? ''));
        $_SESSION[$res['ok'] ? 'admin_users_notice' : 'admin_users_error'] = (string) ($res['ok'] ? $res['message'] : $res['error']);
        gf_redirect(gf_url('admin/pages/users.php'));
    }
    if ($action === 'resend_admin_password') {
        $res = gf_admin_resend_temp_password((int) ($_POST['id'] ?? 0));
        $_SESSION[$res['ok'] ? 'admin_users_notice' : 'admin_users_error'] = (string) ($res['ok'] ? $res['message'] : $res['error']);
        gf_redirect(gf_url('admin/pages/users.php'));
    }
    if ($action === 'delete_own_admin') {
        $res = gf_admin_delete_own_account((int) ($_POST['id'] ?? 0), $me);
        if (!$res['ok']) {
            $_SESSION['admin_users_error'] = (string) $res['error'];
            gf_redirect(gf_url('admin/pages/users.php'));
        }
        gf_logout();
        gf_redirect(gf_url('pages/auth/login.php'));
    }
    if (isset($_POST['id'], $_POST['status'])) {
        gf_admin_patch_user((int)$_POST['id'], ['status' => $_POST['status'] === 'locked' ? 'locked' : 'active'], $me);
        gf_redirect(gf_url('admin/pages/users.php'));
    }
}
$userPage = max(1, (int)($_GET['page'] ?? 1));
$userLimit = 8;
$userFilters = [
    'limit'  => $userLimit,
    'page'   => $userPage,
    'sort'   => 'oldest',
    'q'      => $_GET['q'] ?? '',
    'role'   => $_GET['role'] ?? '',
    'status' => $_GET['status'] ?? '',
];
$userResult = gf_admin_users_paged($userFilters);
if ($userPage > 1 && empty($userResult['items']) && (int)$userResult['total'] > 0) {
    $userPage = max(1, (int) ceil((int)$userResult['total'] / $userLimit));
    $userFilters['page'] = $userPage;
    $userResult = gf_admin_users_paged($userFilters);
}
$rows = $userResult['items'];
$userPaginationHtml = gf_render_admin_pagination(
    $userPage,
    $userLimit,
    (int)$userResult['total'],
    array_diff_key($_GET, ['page' => ''])
);

$pageTitle = 'Quản lý người dùng';
$extraCss = ['admin/assets/admin.css'];
$adminActive = 'users';
require dirname(__DIR__, 2) . '/includes/layout_head.php';
require dirname(__DIR__, 2) . '/includes/admin_layout.php';
?>
<div class="mb-4">
    <h2 class="fw-bold mb-1 text-dark" style="font-size: 24px; letter-spacing: -0.3px;"><?= gf_h(gf_t('Quản lý người dùng')) ?></h2>
    <p class="text-secondary mb-0 small"><?= gf_h(gf_t('Theo dõi tài khoản, vai trò và khóa/mở khóa người dùng trên hệ thống')) ?></p>
</div>
<?php if ($notice): ?><div class="alert alert-success"><?= gf_h(gf_t($notice)) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= gf_h(gf_t($error)) ?></div><?php endif; ?>
<div class="admin-card p-3 mb-4">
    <h3 class="fw-bold mb-1" style="font-size:18px;"><?= gf_h(gf_t('Thêm quản trị viên')) ?></h3>
    <p class="text-secondary small mb-3"><?= gf_h(gf_t('Nhập tên tài khoản và email. Thư gửi đúng tên tài khoản đã nhập, cùng mật khẩu tạm có hiệu lực 5 phút. Lần đăng nhập đầu bắt buộc đổi mật khẩu và không được giữ mật khẩu do hệ thống cấp.')) ?></p>
    <form method="post" class="row g-3 align-items-end">
        <?= gf_csrf_field() ?>
        <input type="hidden" name="action" value="invite_admin">
        <div class="col-md-4">
            <label class="form-label small fw-semibold" for="admin-invite-name"><?= gf_h(gf_t('Tên tài khoản')) ?></label>
            <input id="admin-invite-name" class="form-control admin-input" name="full_name" maxlength="100" required placeholder="<?= gf_h(gf_t('Tên tài khoản quản trị viên mới')) ?>">
        </div>
        <div class="col-md-5">
            <label class="form-label small fw-semibold" for="admin-invite-email"><?= gf_h(gf_t('Email')) ?></label>
            <input id="admin-invite-email" class="form-control admin-input" type="email" name="email" maxlength="255" required placeholder="name@gmail.com">
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary w-100" type="submit"><?= gf_h(gf_t('Gửi tài khoản')) ?></button>
        </div>
    </form>
</div>
<form method="get" class="admin-card p-3 mb-4"><div class="row g-3"><div class="col-md-5"><input class="form-control admin-input" name="q" value="<?= gf_h($_GET['q']??'') ?>" placeholder="<?= gf_h(gf_t('⌕  Tìm theo tên, email, số điện thoại...')) ?>"></div><div class="col-md-3"><select class="form-select admin-input" name="role"><option value=""><?= gf_h(gf_t('Tất cả vai trò')) ?></option><option value="admin"<?= (($_GET['role']??'')==='admin')?' selected':'' ?>><?= gf_h(gf_t('Quản trị viên')) ?></option><option value="user"<?= (($_GET['role']??'')==='user')?' selected':'' ?>><?= gf_h(gf_t('Người dùng')) ?></option></select></div><div class="col-md-2"><select class="form-select admin-input" name="status"><option value=""><?= gf_h(gf_t('Trạng thái')) ?></option><option value="active"<?= (($_GET['status']??'')==='active')?' selected':'' ?>><?= gf_h(gf_t('Hoạt động')) ?></option><option value="locked"<?= (($_GET['status']??'')==='locked')?' selected':'' ?>><?= gf_h(gf_t('Bị khóa')) ?></option></select></div><div class="col-md-2"><button class="btn btn-light border w-100"><?= gf_h(gf_t('Lọc')) ?></button></div></div></form>
<?php
gf_admin_table_open([
    'id'        => 'usersTable',
    'class'     => 'admin-users-table',
    'min_width' => '1120px',
    'columns'   => [
        ['label' => 'ID', 'width' => '55px', 'align' => 'center'],
        ['label' => 'HỌ TÊN', 'width' => '180px'],
        ['label' => 'EMAIL', 'width' => '220px'],
        ['label' => 'SỐ ĐIỆN THOẠI', 'width' => '130px'],
        ['label' => 'VAI TRÒ', 'width' => '130px', 'align' => 'center'],
        ['label' => 'NGÀY THAM GIA', 'width' => '130px', 'align' => 'center'],
        ['label' => 'TRẠNG THÁI', 'width' => '120px', 'align' => 'center'],
        ['label' => 'THAO TÁC', 'width' => '180px', 'align' => 'right', 'th_class' => 'text-end pe-3'],
    ]
]);
?>
<?php foreach ($rows as $r): ?>
<tr>
    <td class="text-center text-muted fw-semibold"><?= (int)$r['id'] ?></td>
    <td><span class="admin-cell-title"><?= gf_h($r['full_name']) ?></span></td>
    <td><span class="text-secondary"><?= gf_h($r['email']) ?></span></td>
    <td><span class="text-secondary text-nowrap"><?= gf_h($r['phone']??'—') ?></span></td>
    <td class="text-center"><span class="admin-badge <?= $r['role']==='admin'?'badge-warning':'badge-neutral' ?>"><?= gf_h(gf_role_label($r['role']??'')) ?></span></td>
    <td class="text-center text-secondary text-nowrap"><?= gf_h(date('d/m/Y',strtotime($r['created_at']??'now'))) ?></td>
    <td class="text-center"><?php if (!empty($r['must_change_password'])): ?><span class="admin-badge badge-warning"><?= gf_h(gf_t('Chờ đổi mật khẩu')) ?></span><?php else: ?><span class="admin-badge <?= $r['status']==='active'?'badge-success':'badge-danger' ?>"><?= gf_h(gf_t($r['status']==='active'?'Hoạt động':'Bị khóa')) ?></span><?php endif; ?></td>
    <td class="text-end pe-3 text-nowrap">
        <button type="button" class="btn btn-action btn-action-view" data-bs-toggle="modal" data-bs-target="#userModal<?= (int)$r['id'] ?>" title="<?= gf_h(gf_t('Xem')) ?>"><i class="fa-solid fa-eye"></i></button>
        <?php if (!empty($r['must_change_password'])): ?>
        <form method="post" class="d-inline">
            <?= gf_csrf_field() ?>
            <input type="hidden" name="action" value="resend_admin_password">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn btn-action btn-action-edit" title="<?= gf_h(gf_t('Gửi lại mật khẩu tạm')) ?>"><i class="fa-solid fa-paper-plane"></i></button>
        </form>
        <?php endif; ?>
        <?php if ($r['role'] !== 'admin'): ?>
        <form method="post" class="d-inline js-confirm-submit"
            data-confirm-title="<?= gf_h(gf_t($r['status']==='active'?'Khóa tài khoản':'Mở khóa tài khoản')) ?>"
            data-confirm-message="<?= gf_h(gf_t($r['status']==='active' ? 'Khóa tài khoản này sẽ ngăn người dùng đăng nhập và sử dụng các chức năng trên hệ thống. Lịch sử đánh giá/yêu thích được giữ nguyên.' : 'Mở khóa tài khoản này để người dùng có thể đăng nhập và sử dụng lại các chức năng.')) ?>"
            data-confirm-label="<?= gf_h(gf_t($r['status']==='active'?'Khóa tài khoản':'Mở khóa')) ?>"
            data-confirm-danger="<?= $r['status']==='active'?'1':'0' ?>">
            <?= gf_csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <input type="hidden" name="status" value="<?= $r['status']==='active'?'locked':'active' ?>">
            <button class="btn btn-action <?= $r['status']==='active'?'btn-action-delete':'btn-action-edit' ?>" title="<?= gf_h(gf_t($r['status']==='active'?'Khóa':'Mở khóa')) ?>"><i class="fa-solid <?= $r['status']==='active'?'fa-lock':'fa-lock-open' ?>"></i></button>
        </form>
        <?php elseif ((int) $r['id'] === (int) $me['id']): ?>
        <form method="post" class="d-inline js-confirm-submit"
            data-confirm-title="<?= gf_h(gf_t('Xóa tài khoản quản trị')) ?>"
            data-confirm-message="<?= gf_h(gf_t('Xóa tài khoản quản trị của chính bạn. Sau khi xóa, bạn không đăng nhập bằng tài khoản này được nữa.')) ?>"
            data-confirm-label="<?= gf_h(gf_t('Xóa tài khoản')) ?>"
            data-confirm-danger="1">
            <?= gf_csrf_field() ?>
            <input type="hidden" name="action" value="delete_own_admin">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn btn-action btn-action-delete" title="<?= gf_h(gf_t('Xóa tài khoản của tôi')) ?>"><i class="fa-solid fa-trash"></i></button>
        </form>
        <?php else: ?><button class="btn btn-action btn-action-delete" disabled title="<?= gf_h(gf_t('Chỉ quản trị viên đó được xóa tài khoản của chính mình')) ?>"><i class="fa-solid fa-lock"></i></button>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
<?php
gf_admin_table_close([
    'empty'         => empty($rows),
    'empty_message' => 'Không tìm thấy người dùng nào phù hợp.',
    'colspan'       => 8,
    'footer_html'   => $userPaginationHtml,
]);
?>

<!-- Render user detail modals outside table -->
<?php foreach ($rows as $r): ?>
<div class="modal fade" id="userModal<?= (int)$r['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold"><?= gf_h(gf_t('Thông tin người dùng')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="bg-light p-3 rounded-3 small">
                    <p class="mb-2"><strong><?= gf_h(gf_t('Họ tên:')) ?></strong> <?= gf_h($r['full_name']) ?></p>
                    <p class="mb-2"><strong><?= gf_h(gf_t('Email đăng nhập:')) ?></strong> <?= gf_h($r['email']) ?></p>
                    <p class="mb-2"><strong><?= gf_h(gf_t('Số điện thoại:')) ?></strong> <?= gf_h($r['phone']??'—') ?></p>
                    <p class="mb-2"><strong><?= gf_h(gf_t('Vai trò:')) ?></strong> <span class="admin-badge <?= $r['role']==='admin'?'badge-warning':'badge-neutral' ?>"><?= gf_h(gf_role_label($r['role']??'')) ?></span></p>
                    <p class="mb-0"><strong><?= gf_h(gf_t('Trạng thái:')) ?></strong> <span class="admin-badge <?= $r['status']==='active'?'badge-success':'badge-danger' ?>"><?= gf_h(gf_t($r['status']==='active'?'Hoạt động':'Bị khóa')) ?></span></p>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal"><?= gf_h(gf_t('Đóng')) ?></button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</main></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require dirname(__DIR__, 2) . '/includes/ui_feedback.php'; ?>
</body></html>
