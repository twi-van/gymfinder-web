<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
$me = gf_require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['status'])) {
    gf_admin_patch_user((int)$_POST['id'], ['status' => $_POST['status'] === 'locked' ? 'locked' : 'active'], $me);
    gf_redirect(gf_url('admin/pages/users.php'));
}
$rows = gf_admin_users_paged(['limit'=>100,'sort'=>'oldest','q'=>$_GET['q']??'','role'=>$_GET['role']??'','status'=>$_GET['status']??''])['items'];
$pageTitle = 'Quản lý người dùng';
$extraCss = ['admin/assets/admin.css'];
$adminActive = 'users';
require dirname(__DIR__, 2) . '/includes/layout_head.php';
require dirname(__DIR__, 2) . '/includes/admin_layout.php';
?>
<h2 class="fw-bold mb-4">Quản lý người dùng</h2>
<form method="get" class="admin-card p-3 mb-4"><div class="row g-3"><div class="col-md-5"><input class="form-control admin-input" name="q" value="<?= gf_h($_GET['q']??'') ?>" placeholder="⌕  Tìm kiếm theo tên, email, sđt..."></div><div class="col-md-3"><select class="form-select admin-input" name="role"><option value="">Tất cả vai trò</option><option value="admin">Admin</option><option value="user">User</option></select></div><div class="col-md-2"><select class="form-select admin-input" name="status"><option value="">Trạng thái</option><option value="active">Hoạt động</option><option value="locked">Bị khóa</option></select></div><div class="col-md-2"><button class="btn btn-light border w-100">Lọc</button></div></div></form>
<div class="admin-card overflow-hidden"><div class="table-responsive"><table class="table admin-table admin-users-table mb-0">
<colgroup><col style="width:6%"><col style="width:17%"><col style="width:21%"><col style="width:15%"><col style="width:9%"><col style="width:14%"><col style="width:10%"><col style="width:8%"></colgroup>
<thead><tr><th>ID</th><th>HỌ TÊN</th><th>EMAIL</th><th>SỐ ĐIỆN THOẠI</th><th>VAI TRÒ</th><th>NGÀY THAM GIA</th><th>TRẠNG THÁI</th><th>THAO TÁC</th></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr>
    <td><?= (int)$r['id'] ?></td>
    <td><strong><?= gf_h($r['full_name']) ?></strong></td>
    <td><?= gf_h($r['email']) ?></td>
    <td><?= gf_h($r['phone']??'—') ?></td>
    <td><span class="admin-badge <?= $r['role']==='admin'?'badge-warning':'badge-neutral' ?>"><?= gf_h($r['role']==='admin'?'Admin':'User') ?></span></td>
    <td><?= gf_h(date('Y-m-d',strtotime($r['created_at']??'now'))) ?></td>
    <td><span class="admin-badge <?= $r['status']==='active'?'badge-success':'badge-danger' ?>"><?= $r['status']==='active'?'Hoạt động':'Bị khóa' ?></span></td>
    <td>
        <button type="button" class="btn btn-action btn-action-view" data-bs-toggle="modal" data-bs-target="#userModal<?= (int)$r['id'] ?>" title="Xem"><i class="fa-solid fa-eye"></i></button>
        <div class="modal fade" id="userModal<?= (int)$r['id'] ?>" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow rounded-4"><div class="modal-header"><h5 class="modal-title fw-bold">Thông tin người dùng</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p><strong>Họ tên:</strong> <?= gf_h($r['full_name']) ?></p><p><strong>Email:</strong> <?= gf_h($r['email']) ?></p><p><strong>Số điện thoại:</strong> <?= gf_h($r['phone']??'—') ?></p><p><strong>Vai trò:</strong> <?= gf_h($r['role']) ?></p><p><strong>Trạng thái:</strong> <?= gf_h($r['status']) ?></p></div></div></div></div>
        <?php if ($r['role'] !== 'admin'): ?>
        <form method="post" class="d-inline">
            <?= gf_csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <input type="hidden" name="status" value="<?= $r['status']==='active'?'locked':'active' ?>">
            <button class="btn btn-action <?= $r['status']==='active'?'btn-action-delete':'btn-action-edit' ?>" title="<?= $r['status']==='active'?'Khóa':'Mở khóa' ?>"><i class="fa-solid <?= $r['status']==='active'?'fa-lock':'fa-lock-open' ?>"></i></button>
        </form>
        <?php else: ?><button class="btn btn-action btn-action-delete" disabled title="Không thể tự khóa tài khoản admin"><i class="fa-solid fa-lock"></i></button>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
</main></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
