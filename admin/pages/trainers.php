<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
require_once dirname(__DIR__) . '/components/table.php';
$adminUser = gf_require_admin();
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    gf_require_csrf();
    if (!empty($_POST['id']) && isset($_POST['full_name'])) {
        $data = $_POST;
        $id = (int)$_POST['id'];
        unset($data['_csrf'], $data['id']);
        $data['gym_id'] = (int)($data['gym_id'] ?? 0);
        $data['specialty_id'] = (int)($data['specialty_id'] ?? 0);
        $data['years_experience'] = (int)($data['years_experience'] ?? 0);
        if (!empty($_POST['remove_avatar'])) {
            $data['avatar_url'] = null;
        } elseif (array_key_exists('avatar_url', $_POST)) {
            $data['avatar_url'] = trim((string)$_POST['avatar_url']) ?: null;
        }
        if (isset($_FILES['avatar_file']) && ($_FILES['avatar_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $up = gf_save_upload($_FILES['avatar_file'], 'trainer_avatar', $adminUser);
            if (!$up['ok']) { $err = $up['error']; }
            else { $data['avatar_url'] = $up['url']; }
        }
        if (!$err) {
            $res = gf_admin_save_trainer($data, $id);
            if (!empty($res['ok'])) { gf_redirect(gf_url('admin/pages/trainers.php?updated=1')); }
            $err = $res['error'] ?? 'Không thể cập nhật huấn luyện viên.';
        }
    } elseif (isset($_POST['id'], $_POST['status'])) {
        gf_set_entity_status('trainers', (int)$_POST['id'], $_POST['status'] === 'hidden' ? 'hidden' : 'active');
        gf_redirect(gf_url('admin/pages/trainers.php'));
    } elseif (isset($_POST['full_name'])) {
        $data = $_POST;
        unset($data['_csrf']);
        $data['gym_id'] = (int)($data['gym_id'] ?? 0);
        $data['specialty_id'] = (int)($data['specialty_id'] ?? 0);
        $data['years_experience'] = (int)($data['years_experience'] ?? 0);
        if (!empty($_POST['remove_avatar'])) {
            $data['avatar_url'] = null;
        } elseif (array_key_exists('avatar_url', $_POST)) {
            $data['avatar_url'] = trim((string)$_POST['avatar_url']) ?: null;
        }
        if (isset($_FILES['avatar_file']) && ($_FILES['avatar_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $up = gf_save_upload($_FILES['avatar_file'], 'trainer_avatar', $adminUser);
            if (!$up['ok']) { $err = $up['error']; }
            else { $data['avatar_url'] = $up['url']; }
        }
        if (!$err) {
            $res = gf_admin_save_trainer($data, null);
            if (!empty($res['ok'])) {
                gf_redirect(gf_url('admin/pages/trainers.php?created=1'));
            }
            $err = $res['error'] ?? 'Không thể lưu huấn luyện viên.';
        }
    }
}
$trainerPage = max(1, (int)($_GET['page'] ?? 1));
$trainerLimit = 8;
$filters = [
    'limit'        => $trainerLimit,
    'page'         => $trainerPage,
    'sort'         => 'oldest',
    'q'            => $_GET['q'] ?? '',
    'status'       => $_GET['status'] ?? '',
    'specialty_id' => $_GET['specialty_id'] ?? '',
];
$trainerResult = gf_admin_trainers_paged($filters);

// Tự động lùi về trang hợp lệ gần nhất nếu trang hiện tại bị rỗng (sau khi xóa)
if ($trainerPage > 1 && empty($trainerResult['items']) && (int)$trainerResult['total'] > 0) {
    $trainerPage = max(1, (int) ceil((int)$trainerResult['total'] / $trainerLimit));
    $filters['page'] = $trainerPage;
    $trainerResult = gf_admin_trainers_paged($filters);
}
$rows = $trainerResult['items'];
$gyms = gf_admin_gyms_paged(['limit' => 100])['items'];
$specs = gf_list_specialties();
$pageTitle = 'Quản lý HLV - Admin GYMFINDER';
$extraCss = ['admin/assets/admin.css'];
$adminActive = 'trainers';
require dirname(__DIR__, 2) . '/includes/layout_head.php';
require dirname(__DIR__, 2) . '/includes/admin_layout.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4"><h2 class="fw-bold mb-0">Quản lý huấn luyện viên</h2><button class="btn btn-primary px-3 rounded-3" onclick="trainerModalCreate()" data-bs-toggle="modal" data-bs-target="#trainerCreate"><i class="fa-solid fa-plus me-2"></i>Thêm mới</button></div>
<?php if (!empty($_GET['created'])): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i>Đã thêm huấn luyện viên thành công.</div><?php elseif(!empty($_GET['updated'])): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i>Đã cập nhật huấn luyện viên thành công.</div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i><?= gf_h($err) ?></div><?php endif; ?>
<div class="modal fade" id="trainerCreate" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content border-0 shadow rounded-4"><div class="modal-header border-0"><h5 class="modal-title fw-bold" id="trainerModalTitle">Thêm huấn luyện viên mới</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div id="trainer-error-alert" class="alert alert-danger d-none"></div><form method="post" id="trainerForm" enctype="multipart/form-data" onsubmit="return handleTrainerFormSubmit(event)">
    <?= gf_csrf_field() ?>
    <input type="hidden" name="id" id="trainerId" value="">
    <input type="hidden" name="avatar_url" id="trainerAvatarUrl" value="">
    <input type="hidden" name="remove_avatar" id="trainerRemoveAvatar" value="0">
    <div class="row g-3">
        <!-- AVATAR UPLOAD SECTION -->
        <div class="col-12">
            <label class="form-label fw-bold mb-2">Ảnh đại diện huấn luyện viên</label>
            <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 border">
                <img id="trainerAvatarPreview" src="https://ui-avatars.com/api/?name=Trainer&background=00c9b1&color=fff" class="rounded-circle shadow-sm object-fit-cover" style="width:72px;height:72px;flex:0 0 72px;" alt="Avatar preview">
                <div class="flex-grow-1">
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <input type="file" id="trainerAvatarFile" name="avatar_file" accept="image/jpeg,image/png,image/webp" class="d-none" onchange="previewTrainerAvatar(this)">
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="document.getElementById('trainerAvatarFile').click()"><i class="fa-solid fa-camera me-1"></i>Chọn / Thay ảnh</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-none" id="trainerAvatarCancelBtn" onclick="cancelTrainerAvatar()"><i class="fa-solid fa-rotate-left me-1"></i>Hủy đổi</button>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" id="trainerAvatarRemoveBtn" onclick="removeTrainerAvatar()"><i class="fa-solid fa-trash me-1"></i>Xóa avatar</button>
                    </div>
                    <div class="small text-secondary">Hỗ trợ JPG, PNG, WEBP tối đa 5 MB.</div>
                </div>
            </div>
        </div>
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
    <div class="d-flex justify-content-end gap-2 mt-4"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button><button type="submit" class="btn btn-primary" id="trainerSubmit">Lưu thông tin</button></div>
</form></div></div></div></div>
<form method="get" class="admin-card p-3 mb-4"><div class="row g-3"><div class="col-md-5"><input class="form-control admin-input" name="q" value="<?= gf_h($_GET['q']??'') ?>" placeholder="⌕  Tìm kiếm HLV..."></div><div class="col-md-3"><select class="form-select admin-input" name="specialty_id"><option value="">Tất cả chuyên môn</option><?php foreach($specs as $s): ?><option value="<?= (int)$s['id'] ?>" <?= (string)($_GET['specialty_id']??'')===(string)$s['id']?'selected':'' ?>><?= gf_h($s['name']) ?></option><?php endforeach; ?></select></div><div class="col-md-2"><select class="form-select admin-input" name="status"><option value="">Trạng thái</option><option value="active">Hoạt động</option><option value="hidden">Tạm ẩn</option></select></div><div class="col-md-2"><button class="btn btn-light border w-100">Lọc</button></div></div></form>
<?php
$trainerPaginationHtml = gf_render_admin_pagination(
    $trainerPage,
    $trainerLimit,
    (int)$trainerResult['total'],
    array_diff_key($_GET, ['page' => ''])
);

gf_admin_table_open([
    'id'        => 'trainersTable',
    'class'     => 'admin-trainers-table',
    'min_width' => '1080px',
    'columns'   => [
        ['label' => 'ID', 'width' => '55px', 'align' => 'center'],
        ['label' => 'HUẤN LUYỆN VIÊN', 'width' => '280px'],
        ['label' => 'CHUYÊN MÔN', 'width' => '200px'],
        ['label' => 'PHÒNG TẬP', 'width' => '210px'],
        ['label' => 'ĐÁNH GIÁ', 'width' => '110px', 'align' => 'center'],
        ['label' => 'TRẠNG THÁI', 'width' => '120px', 'align' => 'center'],
        ['label' => 'THAO TÁC', 'width' => '125px', 'align' => 'right', 'th_class' => 'text-end pe-3'],
    ]
]);
?>
<?php foreach ($rows as $r): 
    // Trích xuất chuyên môn hỗ trợ cả 1 chuyên môn hoặc nhiều chuyên môn
    $specNames = [];
    if (!empty($r['specialties']) && is_array($r['specialties'])) {
        foreach ($r['specialties'] as $sp) {
            $specNames[] = is_array($sp) ? ($sp['name'] ?? '') : (string)$sp;
        }
    } elseif (!empty($r['specialties']) && is_string($r['specialties'])) {
        $specNames = array_map('trim', explode(',', $r['specialties']));
    } elseif (!empty($r['specialty_names']) && is_array($r['specialty_names'])) {
        $specNames = $r['specialty_names'];
    } elseif (!empty($r['specialty_names']) && is_string($r['specialty_names'])) {
        $specNames = array_map('trim', explode(',', $r['specialty_names']));
    } elseif (!empty($r['specialty_id'])) {
        foreach ($specs as $sp) {
            if ((int)$sp['id'] === (int)$r['specialty_id']) {
                $specNames[] = $sp['name'];
                break;
            }
        }
    }
    $specNames = array_values(array_filter($specNames));

    $gymName = ''; 
    foreach ($gyms as $g) { 
        if ((int)$g['id'] === (int)$r['gym_id']) { 
            $gymName = $g['name']; 
            break; 
        } 
    } 
?>
<tr>
    <td class="text-center text-muted fw-semibold"><?= (int)$r['id'] ?></td>
    <td>
        <div class="d-flex align-items-center gap-3">
            <img src="<?= gf_h(gf_avatar($r['avatar_url']??null,(int)$r['id'],$r['full_name'])) ?>" class="admin-avatar shadow-sm flex-shrink-0" alt="">
            <span class="admin-cell-title"><?= gf_h($r['full_name']) ?></span>
        </div>
    </td>
    <td>
        <div class="d-flex flex-wrap gap-1 align-items-center" style="max-width: 220px;">
            <?php if (empty($specNames)): ?>
                <span class="text-muted small">—</span>
            <?php else: ?>
                <?php foreach ($specNames as $specItem): ?>
                    <span class="admin-spec-badge"><?= gf_h($specItem) ?></span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </td>
    <td><span class="text-secondary"><?= gf_h($gymName ?: 'Nhiều phòng tập') ?></span></td>
    <td class="text-center admin-cell-rating text-nowrap"><i class="fa-solid fa-star"></i> <?= gf_h((string)($r['avg_rating']??0)) ?></td>
    <td class="text-center"><span class="admin-badge <?= $r['status']==='active'?'badge-success':'badge-neutral' ?>"><?= $r['status']==='active'?'Hoạt động':'Tạm ẩn' ?></span></td>
    <td class="text-end pe-3 text-nowrap">
        <button type="button" class="btn btn-action btn-action-view" data-bs-toggle="modal" data-bs-target="#trainerView<?= (int)$r['id'] ?>" title="Xem"><i class="fa-solid fa-eye"></i></button>
        <button type="button" class="btn btn-action btn-action-edit" title="Chỉnh sửa" data-bs-toggle="modal" data-bs-target="#trainerCreate" onclick="trainerModalEdit(this)" data-id="<?= (int)$r['id'] ?>" data-name="<?= gf_h($r['full_name']) ?>" data-specialty="<?= (int)$r['specialty_id'] ?>" data-gym="<?= (int)$r['gym_id'] ?>" data-years="<?= (int)$r['years_experience'] ?>" data-phone="<?= gf_h($r['phone']??'') ?>" data-status="<?= gf_h($r['status']) ?>" data-bio="<?= gf_h($r['bio']??'') ?>" data-avatar="<?= gf_h($r['avatar_url']??'') ?>" data-avatar-display="<?= gf_h(gf_avatar($r['avatar_url']??null, (int)$r['id'], $r['full_name'])) ?>"><i class="fa-solid fa-pen"></i></button>
        <form method="post" class="d-inline">
            <?= gf_csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <input type="hidden" name="status" value="<?= $r['status']==='active'?'hidden':'active' ?>">
            <button class="btn btn-action <?= $r['status']==='active'?'btn-action-delete':'btn-action-edit' ?>" title="<?= $r['status']==='active'?'Ẩn':'Hiện' ?>"><i class="fa-solid <?= $r['status']==='active'?'fa-trash':'fa-eye' ?>"></i></button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
<?php
gf_admin_table_close([
    'empty'         => empty($rows),
    'empty_message' => 'Không tìm thấy huấn luyện viên nào phù hợp.',
    'colspan'       => 7,
    'footer_html'   => $trainerPaginationHtml,
]);
?>

<!-- Render view modals outside table -->
<?php foreach ($rows as $r): 
    $specNames = [];
    if (!empty($r['specialties']) && is_array($r['specialties'])) {
        foreach ($r['specialties'] as $sp) {
            $specNames[] = is_array($sp) ? ($sp['name'] ?? '') : (string)$sp;
        }
    } elseif (!empty($r['specialties']) && is_string($r['specialties'])) {
        $specNames = array_map('trim', explode(',', $r['specialties']));
    } elseif (!empty($r['specialty_names']) && is_array($r['specialty_names'])) {
        $specNames = $r['specialty_names'];
    } elseif (!empty($r['specialty_names']) && is_string($r['specialty_names'])) {
        $specNames = array_map('trim', explode(',', $r['specialty_names']));
    } elseif (!empty($r['specialty_id'])) {
        foreach ($specs as $sp) {
            if ((int)$sp['id'] === (int)$r['specialty_id']) {
                $specNames[] = $sp['name'];
                break;
            }
        }
    }
    $specNames = array_values(array_filter($specNames));

    $gymName = ''; 
    foreach ($gyms as $g) { 
        if ((int)$g['id'] === (int)$r['gym_id']) { 
            $gymName = $g['name']; 
            break; 
        } 
    } 
?>
<div class="modal fade" id="trainerView<?= (int)$r['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold">Chi tiết huấn luyện viên</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <img src="<?= gf_h(gf_avatar($r['avatar_url']??null,(int)$r['id'],$r['full_name'])) ?>" class="rounded-circle shadow-sm object-fit-cover" style="width:64px;height:64px;" alt="">
                    <div>
                        <p class="fs-5 fw-bold mb-0"><?= gf_h($r['full_name']) ?></p>
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            <?php if (empty($specNames)): ?>
                                <span class="text-muted small">Chưa thiết lập chuyên môn</span>
                            <?php else: ?>
                                <?php foreach ($specNames as $specItem): ?>
                                    <span class="admin-spec-badge"><?= gf_h($specItem) ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="bg-light p-3 rounded-3 mb-3 small">
                    <div class="row g-2">
                        <div class="col-6"><strong>Phòng tập:</strong> <?= gf_h($gymName ?: 'Nhiều phòng tập') ?></div>
                        <div class="col-6"><strong>Kinh nghiệm:</strong> <?= (int)$r['years_experience'] ?> năm</div>
                        <div class="col-6"><strong>Số điện thoại:</strong> <?= gf_h($r['phone'] ?? '—') ?></div>
                        <div class="col-6"><strong>Đánh giá:</strong> <span class="text-warning"><i class="fa-solid fa-star"></i> <?= gf_h((string)$r['avg_rating']) ?> / 5</span></div>
                    </div>
                </div>
                <div>
                    <h6 class="fw-bold text-secondary small text-uppercase mb-2">Tiểu sử giới thiệu</h6>
                    <div class="p-3 bg-white border rounded-3 small text-secondary">
                        <?= nl2br(gf_h($r['bio'] ?: 'Chưa có thông tin giới thiệu.')) ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</main></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const uploadApiUrl = '<?= gf_url('api/uploads/images') ?>';
const csrfToken = '<?= gf_csrf_token() ?>';
const defaultTrainerPlaceholder = 'https://ui-avatars.com/api/?name=Trainer&background=00c9b1&color=fff';

let originalTrainerAvatarUrl = '';
let originalTrainerAvatarDisplay = '';
let activeTrainerObjectUrl = null;

function revokeTrainerObjectUrl() {
    if (activeTrainerObjectUrl) {
        URL.revokeObjectURL(activeTrainerObjectUrl);
        activeTrainerObjectUrl = null;
    }
}

function trainerModalCreate(){
    revokeTrainerObjectUrl();
    const f=document.getElementById('trainerForm');
    f.reset();
    document.getElementById('trainerId').value='';
    document.getElementById('trainerAvatarUrl').value='';
    document.getElementById('trainerRemoveAvatar').value='0';
    document.getElementById('trainerAvatarPreview').src = defaultTrainerPlaceholder;
    document.getElementById('trainerAvatarCancelBtn').classList.add('d-none');
    document.getElementById('trainer-error-alert').classList.add('d-none');
    document.getElementById('trainerModalTitle').textContent='Thêm huấn luyện viên mới';
    document.getElementById('trainerSubmit').textContent='Thêm huấn luyện viên';
    document.getElementById('trainerSubmit').disabled = false;
    originalTrainerAvatarUrl = '';
    originalTrainerAvatarDisplay = defaultTrainerPlaceholder;
}

function trainerModalEdit(button){
    revokeTrainerObjectUrl();
    const f=document.getElementById('trainerForm');
    f.reset();
    document.getElementById('trainerId').value=button.dataset.id;
    document.getElementById('trainerName').value=button.dataset.name;
    document.getElementById('trainerSpecialty').value=button.dataset.specialty;
    document.getElementById('trainerGym').value=button.dataset.gym;
    document.getElementById('trainerYears').value=button.dataset.years;
    document.getElementById('trainerPhone').value=button.dataset.phone;
    document.getElementById('trainerStatus').value=button.dataset.status;
    document.getElementById('trainerBio').value=button.dataset.bio;
    document.getElementById('trainer-error-alert').classList.add('d-none');
    document.getElementById('trainerSubmit').disabled = false;

    originalTrainerAvatarUrl = button.dataset.avatar || '';
    originalTrainerAvatarDisplay = button.dataset.avatarDisplay || defaultTrainerPlaceholder;
    document.getElementById('trainerAvatarUrl').value = originalTrainerAvatarUrl;
    document.getElementById('trainerRemoveAvatar').value = '0';
    document.getElementById('trainerAvatarPreview').src = originalTrainerAvatarDisplay;
    document.getElementById('trainerAvatarCancelBtn').classList.add('d-none');

    document.getElementById('trainerModalTitle').textContent='Sửa HLV: '+button.dataset.name;
    document.getElementById('trainerSubmit').textContent='Lưu thông tin';
}

function previewTrainerAvatar(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        alert('File không hợp lệ. Vui lòng chọn ảnh JPG, PNG hoặc WEBP.');
        input.value = '';
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        alert('Dung lượng ảnh vượt quá 5 MB.');
        input.value = '';
        return;
    }
    revokeTrainerObjectUrl();
    activeTrainerObjectUrl = URL.createObjectURL(file);
    document.getElementById('trainerAvatarPreview').src = activeTrainerObjectUrl;
    document.getElementById('trainerRemoveAvatar').value = '0';
    document.getElementById('trainerAvatarCancelBtn').classList.remove('d-none');
}

function cancelTrainerAvatar() {
    revokeTrainerObjectUrl();
    document.getElementById('trainerAvatarFile').value = '';
    document.getElementById('trainerAvatarPreview').src = originalTrainerAvatarDisplay;
    document.getElementById('trainerAvatarUrl').value = originalTrainerAvatarUrl;
    document.getElementById('trainerRemoveAvatar').value = '0';
    document.getElementById('trainerAvatarCancelBtn').classList.add('d-none');
}

function removeTrainerAvatar() {
    revokeTrainerObjectUrl();
    document.getElementById('trainerAvatarFile').value = '';
    document.getElementById('trainerAvatarPreview').src = defaultTrainerPlaceholder;
    document.getElementById('trainerAvatarUrl').value = '';
    document.getElementById('trainerRemoveAvatar').value = '1';
    document.getElementById('trainerAvatarCancelBtn').classList.remove('d-none');
}

async function handleTrainerFormSubmit(e) {
    e.preventDefault();
    const saveBtn = document.getElementById('trainerSubmit');
    const errAlert = document.getElementById('trainer-error-alert');
    errAlert.classList.add('d-none');
    errAlert.textContent = '';

    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Đang tải ảnh & lưu...';

    try {
        const fileInput = document.getElementById('trainerAvatarFile');
        if (fileInput.files && fileInput.files[0]) {
            const formData = new FormData();
            formData.append('file', fileInput.files[0]);
            formData.append('type', 'trainer_avatar');
            formData.append('_csrf', csrfToken);

            const res = await fetch(uploadApiUrl, {
                method: 'POST',
                headers: { 'X-CSRF-Token': csrfToken },
                body: formData
            });
            const data = await res.json();
            if (!res.ok || !data.data || !data.data.url) {
                throw new Error(data.error?.message || 'Upload ảnh đại diện thất bại.');
            }
            document.getElementById('trainerAvatarUrl').value = data.data.url;
            fileInput.value = ''; // Tránh submit file qua standard post lần 2
        }

        document.getElementById('trainerForm').submit();
    } catch (err) {
        saveBtn.disabled = false;
        saveBtn.textContent = document.getElementById('trainerId').value ? 'Lưu thông tin' : 'Thêm huấn luyện viên';
        errAlert.textContent = err.message || 'Đã xảy ra lỗi khi lưu thông tin.';
        errAlert.classList.remove('d-none');
    }
    return false;
}

window.addEventListener('beforeunload', revokeTrainerObjectUrl);
</script>
</body></html>

