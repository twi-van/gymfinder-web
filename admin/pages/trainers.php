<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
gf_require_admin();
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id']) && isset($_POST['full_name'])) {
    $data = $_POST; unset($data['_csrf'], $data['id']);
    $data['gym_id'] = (int)($data['gym_id'] ?? 0);
    $data['specialty_id'] = (int)($data['specialty_id'] ?? 0);
    $data['years_experience'] = (int)($data['years_experience'] ?? 0);
    $res = gf_admin_save_trainer($data, (int)$_POST['id']);
    if (!empty($res['ok'])) { gf_redirect(gf_url('admin/pages/trainers.php?updated=1')); }
    $err = $res['error'] ?? 'Không thể cập nhật huấn luyện viên.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['status'])) {
    gf_set_entity_status('trainers', (int)$_POST['id'], $_POST['status'] === 'hidden' ? 'hidden' : 'active');
    gf_redirect(gf_url('admin/pages/trainers.php'));
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['full_name'])) {
    $data = $_POST;
    unset($data['_csrf']);
    $data['gym_id'] = (int)($data['gym_id'] ?? 0);
    $data['specialty_id'] = (int)($data['specialty_id'] ?? 0);
    $data['years_experience'] = (int)($data['years_experience'] ?? 0);
    $res = gf_admin_save_trainer($data, null);
    if (!empty($res['ok'])) {
        gf_redirect(gf_url('admin/pages/trainers.php?created=1'));
    }
    $err = $res['error'] ?? 'Không thể lưu huấn luyện viên.';
}
$filters = ['limit'=>100, 'sort'=>'oldest', 'q'=>$_GET['q'] ?? '', 'status'=>$_GET['status'] ?? '', 'specialty_id'=>$_GET['specialty_id'] ?? ''];
$rows = gf_admin_trainers_paged($filters)['items'];
$gyms = gf_admin_gyms_paged(['limit' => 100])['items'];
$specs = gf_list_specialties();
$pageTitle = 'Quản lý HLV';
$extraCss = ['admin/assets/admin.css'];
$adminActive = 'trainers';
require dirname(__DIR__, 2) . '/includes/layout_head.php';
require dirname(__DIR__, 2) . '/includes/admin_layout.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4"><h2 class="fw-bold mb-0">Quản lý huấn luyện viên</h2><button class="btn btn-primary px-3 rounded-3" onclick="trainerModalCreate()" data-bs-toggle="modal" data-bs-target="#trainerCreate"><i class="fa-solid fa-plus me-2"></i>Thêm mới</button></div>
<?php if (!empty($_GET['created'])): ?><div class="alert alert-success">Đã thêm huấn luyện viên.</div><?php elseif(!empty($_GET['updated'])): ?><div class="alert alert-success">Đã cập nhật huấn luyện viên.</div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= gf_h($err) ?></div><?php endif; ?>
<div class="modal fade" id="trainerCreate" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content border-0 shadow rounded-4"><div class="modal-header border-0"><h5 class="modal-title fw-bold" id="trainerModalTitle">Thêm huấn luyện viên mới</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form method="post" id="trainerForm">
    <?= gf_csrf_field() ?>
    <input type="hidden" name="id" id="trainerId" value="">
    <div class="row g-2">
        <div class="col-md-6"><label class="form-label">Họ và tên *</label><input class="form-control admin-input" name="full_name" id="trainerName" placeholder="Họ tên" required></div>
        <div class="col-md-6"><label class="form-label">Chuyên môn *</label>
            <select class="form-select admin-input" name="specialty_id" id="trainerSpecialty" required><?php foreach ($specs as $s): ?><option value="<?= (int)$s['id'] ?>"><?= gf_h($s['name']) ?></option><?php endforeach; ?></select>
        </div>
        <div class="col-md-6"><label class="form-label">Phòng tập liên kết *</label>
            <select class="form-select admin-input" name="gym_id" id="trainerGym" required><?php foreach ($gyms as $g): ?><option value="<?= (int)$g['id'] ?>"><?= gf_h($g['name']) ?></option><?php endforeach; ?></select>
        </div>
        <div class="col-md-6"><label class="form-label">Kinh nghiệm (năm)</label><input class="form-control admin-input" type="number" name="years_experience" id="trainerYears" value="3" min="0" max="60"></div>
        <div class="col-md-6"><label class="form-label">Số điện thoại</label><input class="form-control admin-input" name="phone" id="trainerPhone"></div>
        <div class="col-md-6"><label class="form-label">Trạng thái</label><select class="form-select admin-input" name="status" id="trainerStatus"><option value="active">Hoạt động</option><option value="hidden">Tạm ẩn</option></select></div>
        <div class="col-12"><label class="form-label">Tiểu sử</label><textarea class="form-control admin-input" rows="3" name="bio" id="trainerBio"></textarea></div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-4"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button><button class="btn btn-primary" id="trainerSubmit">Lưu thông tin</button></div>
</form></div></div></div></div>
<form method="get" class="admin-card p-3 mb-4"><div class="row g-3"><div class="col-md-5"><input class="form-control admin-input" name="q" value="<?= gf_h($_GET['q']??'') ?>" placeholder="⌕  Tìm kiếm HLV..."></div><div class="col-md-3"><select class="form-select admin-input" name="specialty_id"><option value="">Tất cả chuyên môn</option><?php foreach($specs as $s): ?><option value="<?= (int)$s['id'] ?>" <?= (string)($_GET['specialty_id']??'')===(string)$s['id']?'selected':'' ?>><?= gf_h($s['name']) ?></option><?php endforeach; ?></select></div><div class="col-md-2"><select class="form-select admin-input" name="status"><option value="">Trạng thái</option><option value="active">Hoạt động</option><option value="hidden">Tạm ẩn</option></select></div><div class="col-md-2"><button class="btn btn-light border w-100">Lọc</button></div></div></form>
<div class="admin-card overflow-hidden"><div class="table-responsive">
<table class="table admin-table admin-trainers-table mb-0"><colgroup><col style="width:6%"><col style="width:25%"><col style="width:18%"><col style="width:17%"><col style="width:9%"><col style="width:12%"><col style="width:13%"></colgroup>
<thead><tr><th>ID</th><th>HUẤN LUYỆN VIÊN</th><th>CHUYÊN MÔN</th><th>PHÒNG TẬP</th><th>ĐÁNH GIÁ</th><th>TRẠNG THÁI</th><th>THAO TÁC</th></tr></thead>
<tbody>
<?php foreach ($rows as $r): $specName = ''; foreach ($specs as $sp) { if ((int)$sp['id'] === (int)$r['specialty_id']) { $specName = $sp['name']; break; } } $gymName = ''; foreach ($gyms as $g) { if ((int)$g['id'] === (int)$r['gym_id']) { $gymName = $g['name']; break; } } ?>
<tr>
    <td><?= (int)$r['id'] ?></td>
    <td><div class="d-flex align-items-center gap-3"><img src="<?= gf_h(gf_avatar($r['avatar_url']??null,(int)$r['id'],$r['full_name'])) ?>" class="admin-avatar shadow-sm" alt=""><strong><?= gf_h($r['full_name']) ?></strong></div></td>
    <td><span class="badge-neutral"><?= gf_h($specName) ?></span></td>
    <td><?= gf_h($gymName ?: 'Nhiều phòng tập') ?></td>
    <td class="text-warning"><i class="fa-solid fa-star"></i> <?= gf_h((string)($r['avg_rating']??0)) ?></td>
    <td><span class="admin-badge <?= $r['status']==='active'?'badge-success':'badge-neutral' ?>"><?= $r['status']==='active'?'Hoạt động':'Tạm ẩn' ?></span></td>
    <td>
        <button type="button" class="btn btn-action btn-action-view" data-bs-toggle="modal" data-bs-target="#trainerView<?= (int)$r['id'] ?>" title="Xem"><i class="fa-solid fa-eye"></i></button>
        <div class="modal fade" id="trainerView<?= (int)$r['id'] ?>" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow rounded-4"><div class="modal-header"><h5 class="modal-title fw-bold">Chi tiết huấn luyện viên</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p class="fs-5 fw-semibold"><?= gf_h($r['full_name']) ?></p><p>Chuyên môn: <?= gf_h($specName) ?></p><p>Phòng tập: <?= gf_h($gymName) ?></p><p>Kinh nghiệm: <?= (int)$r['years_experience'] ?> năm</p><p>Đánh giá: <?= gf_h((string)$r['avg_rating']) ?> / 5</p><p><?= nl2br(gf_h($r['bio']??'')) ?></p></div></div></div></div>
        <button type="button" class="btn btn-action btn-action-edit" title="Chỉnh sửa" data-bs-toggle="modal" data-bs-target="#trainerCreate" onclick="trainerModalEdit(this)" data-id="<?= (int)$r['id'] ?>" data-name="<?= gf_h($r['full_name']) ?>" data-specialty="<?= (int)$r['specialty_id'] ?>" data-gym="<?= (int)$r['gym_id'] ?>" data-years="<?= (int)$r['years_experience'] ?>" data-phone="<?= gf_h($r['phone']??'') ?>" data-status="<?= gf_h($r['status']) ?>" data-bio="<?= gf_h($r['bio']??'') ?>"><i class="fa-solid fa-pen"></i></button>
        <form method="post" class="d-inline">
            <?= gf_csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <input type="hidden" name="status" value="<?= $r['status']==='active'?'hidden':'active' ?>">
            <button class="btn btn-action <?= $r['status']==='active'?'btn-action-delete':'btn-action-edit' ?>" title="<?= $r['status']==='active'?'Ẩn':'Hiện' ?>"><i class="fa-solid <?= $r['status']==='active'?'fa-trash':'fa-eye' ?>"></i></button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
</main></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function trainerModalCreate(){document.getElementById('trainerForm').reset();document.getElementById('trainerId').value='';document.getElementById('trainerModalTitle').textContent='Thêm huấn luyện viên mới';document.getElementById('trainerSubmit').textContent='Thêm huấn luyện viên';}
function trainerModalEdit(button){const f=document.getElementById('trainerForm');f.reset();document.getElementById('trainerId').value=button.dataset.id;document.getElementById('trainerName').value=button.dataset.name;document.getElementById('trainerSpecialty').value=button.dataset.specialty;document.getElementById('trainerGym').value=button.dataset.gym;document.getElementById('trainerYears').value=button.dataset.years;document.getElementById('trainerPhone').value=button.dataset.phone;document.getElementById('trainerStatus').value=button.dataset.status;document.getElementById('trainerBio').value=button.dataset.bio;document.getElementById('trainerModalTitle').textContent='Sửa HLV: '+button.dataset.name;document.getElementById('trainerSubmit').textContent='Lưu thông tin';}
</script>
</body></html>
