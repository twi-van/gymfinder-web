<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
$user = gf_require_login();
$msg = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $avatarUrl = $user['avatar_url'] ?? null;
    if (!empty($_POST['remove_avatar'])) {
        $avatarUrl = null;
    }
    if (isset($_FILES['avatar_file']) && ($_FILES['avatar_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $upload = gf_save_upload($_FILES['avatar_file'], 'user_avatar', $user);
        if (!$upload['ok']) {
            $error = $upload['error'];
        } else {
            $avatarUrl = $upload['url'];
        }
    }
    if (!$error) {
        gf_update_profile((int)$user['id'], [
            'full_name' => trim((string)($_POST['full_name'] ?? '')),
            'phone' => trim((string)($_POST['phone'] ?? '')),
            'goal' => $_POST['goal'] ?? null,
            'avatar_url' => $avatarUrl,
        ]);
        $user = gf_current_user();
        $msg = 'Đã lưu hồ sơ.';
    }
}
$pageTitle = 'Hồ sơ cá nhân - GYMFINDER';
$bodyClass = 'bg-light';
$goals = ['giam_can'=>'Giảm cân','tang_co'=>'Tăng cơ','tang_suc_manh'=>'Tăng sức mạnh','cai_thien_suc_khoe'=>'Cải thiện sức khỏe'];
require dirname(__DIR__, 2) . '/includes/layout_head.php';
require dirname(__DIR__, 2) . '/includes/header.php';
?>
<style>
.profile-page{width:min(1100px,calc(100% - 40px));max-width:1100px;margin:0 auto;padding:24px 20px 64px}.profile-panel{background:#fff;border:1px solid #dbe2e8;border-radius:22px;padding:48px 56px;box-shadow:0 3px 8px #0f172a0d;margin-bottom:24px}.profile-hero{display:flex;align-items:center;gap:28px;padding-bottom:32px;border-bottom:1px solid #dbe2e8}.profile-avatar{width:144px;height:144px;object-fit:cover;border-radius:50%;background:#00c9b1}.profile-label{color:#64748b;margin-bottom:10px}.profile-value{font-size:18px;color:#0f172a}.profile-goals{display:grid;grid-template-columns:1fr 1fr;gap:14px}.profile-goal{padding:18px 22px;border:2px solid #e2e8f0;border-radius:16px;color:#64748b;font-size:17px}.profile-goal.active{border-color:#36d4c0;color:#36cbbb;background:#f3fdfa}.profile-edit-grid{display:none}.profile-page.editing .profile-view{display:none}.profile-page.editing .profile-edit-grid{display:block}.profile-account{min-height:230px}.profile-account .btn-logout{min-width:270px;border:1px solid #ef4444;color:#ef4444;border-radius:30px;padding:14px 28px;font-size:17px}.profile-account .btn-logout:hover{background:#fff1f2}@media(max-width:767px){.profile-panel{padding:26px 20px}.profile-hero{gap:16px}.profile-avatar{width:88px;height:88px}.profile-goals{grid-template-columns:1fr}}
.profile-avatar-wrap{position:relative;flex:0 0 auto}.profile-page.editing .edit-only-element{display:inline-flex}.edit-only-element{display:none}.avatar-camera{position:absolute;right:0;bottom:0;width:42px;height:42px;align-items:center;justify-content:center;border:0;border-radius:50%;background:#00c9b1;color:#fff;box-shadow:0 2px 8px #0f172a26}.btn-remove-avatar{margin-top:12px;padding:0;border:0;background:none;color:#ef3340;font-size:16px}.profile-edit-input{min-height:56px;border-color:#dbe2e8;border-radius:10px;background:#f8fafc;font-size:18px}.profile-goal-choice{display:flex;align-items:center;gap:16px;min-height:78px;padding:14px 22px;border:2px solid #e2e8f0;border-radius:16px;color:#64748b;font-size:17px;cursor:pointer}.profile-goal-choice input{position:absolute;opacity:0;pointer-events:none}.profile-goal-choice i{font-size:24px}.profile-goal-choice:has(input:checked){border-color:#00c9b1;background:#f0fdfa;color:#00bfae}.profile-goal-choice:focus-within{outline:3px solid #00c9b133}
</style>
<div class="profile-page" id="profilePage">
    <?php if ($msg): ?><div class="alert alert-success"><?= gf_h($msg) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= gf_h($error) ?></div><?php endif; ?>
    <div class="profile-panel">
        <div class="profile-hero">
            <div class="profile-avatar-wrap">
                <img id="profileAvatar" src="<?= gf_h(gf_avatar($user['avatar_url'] ?? null, !empty($user['avatar_url']) ? (int)$user['id'] : 0, $user['full_name'])) ?>" class="profile-avatar" alt="">
                <button class="avatar-camera edit-only-element" type="button" aria-label="Chọn ảnh đại diện" onclick="document.getElementById('avatarFile').click()"><i class="fa-solid fa-camera"></i></button>
            </div>
            <div>
                <h2 class="fw-bold mb-1"><?= gf_h($user['full_name']) ?></h2>
                <div class="text-secondary fs-5"><?= gf_h($user['email']) ?></div>
                <button type="button" class="btn-remove-avatar edit-only-element" onclick="removeProfileAvatar()"><i class="fa-solid fa-trash-can me-1"></i>Xóa ảnh</button>
            </div>
        </div>
        <div class="profile-view py-4">
          <h3 class="fw-bold fs-4 mb-4">Thông tin cá nhân</h3>
          <div class="mb-4"><div class="profile-label">Họ và tên</div><div class="profile-value"><?= gf_h($user['full_name']) ?></div></div>
          <div class="row g-4 mb-5"><div class="col-md-6"><div class="profile-label">Số điện thoại</div><div class="profile-value"><?= gf_h($user['phone'] ?: 'Chưa cập nhật') ?></div></div><div class="col-md-6"><div class="profile-label">Email (Không thể thay đổi)</div><div class="profile-value"><?= gf_h($user['email']) ?></div></div></div>
          <h3 class="fw-bold fs-4 mb-4">Mục tiêu tập luyện</h3>
          <div class="profile-goals mb-4"><?php foreach ($goals as $k=>$v): ?><div class="profile-goal <?= ($user['goal']??'')===$k?'active':'' ?>"><i class="fa-solid fa-dumbbell me-3"></i><?= gf_h($v) ?></div><?php endforeach; ?></div>
          <div class="text-end"><button type="button" class="btn btn-outline-primary rounded-pill px-4" onclick="document.getElementById('profilePage').classList.add('editing')"><i class="fa-solid fa-pen me-2"></i>Chỉnh sửa hồ sơ</button></div>
        </div>
        <form id="profileEditForm" method="post" enctype="multipart/form-data" class="profile-edit-grid py-4">
            <?= gf_csrf_field() ?>
            <input type="file" id="avatarFile" name="avatar_file" accept="image/jpeg,image/png,image/webp" class="d-none" form="profileEditForm">
            <input type="hidden" name="remove_avatar" id="removeAvatar" value="0">
            <h3 class="fw-bold fs-4 mb-4">Thông tin cá nhân</h3>
            <div class="mb-4">
                <label class="form-label profile-label" for="profileName">Họ và tên</label>
                <input id="profileName" class="form-control profile-edit-input" name="full_name" value="<?= gf_h($user['full_name']) ?>" required>
            </div>
            <div class="row g-4 mb-5">
                <div class="col-md-6"><label class="form-label profile-label" for="profilePhone">Số điện thoại</label><input id="profilePhone" class="form-control profile-edit-input" name="phone" value="<?= gf_h($user['phone'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label profile-label">Email (Không thể thay đổi)</label><div class="profile-value pt-2"><?= gf_h($user['email']) ?></div></div>
            </div>
            <h3 class="fw-bold fs-4 mb-4">Mục tiêu tập luyện</h3>
            <div class="profile-goals mb-4">
                <?php foreach ($goals as $k=>$v): ?>
                    <label class="profile-goal-choice <?= ($user['goal']??'')===$k?'active':'' ?>">
                        <input type="radio" name="goal" value="<?= gf_h($k) ?>" <?= ($user['goal']??'')===$k?'checked':'' ?>>
                        <i class="fa-solid <?= $k==='giam_can'?'fa-fire':($k==='tang_co'?'fa-dumbbell':($k==='tang_suc_manh'?'fa-hand-fist':'fa-heart-pulse')) ?>"></i>
                        <span><?= gf_h($v === 'Giảm cân' ? 'Giảm cân / Giảm mỡ' : $v) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <button class="btn btn-primary">Lưu thay đổi</button> <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('profilePage').classList.remove('editing')">Hủy</button>
        </form>
    </div>
    <div class="profile-panel profile-account">
        <h3 class="fw-bold fs-4 mb-4">Tài khoản</h3>
        <div class="text-center"><form method="post" action="<?= gf_h(gf_url('pages/auth/logout.php')) ?>"><?= gf_csrf_field() ?><button class="btn btn-logout"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Đăng xuất</button></form></div>
    </div>
</div>
<script>
const savedProfileAvatar = <?= json_encode(gf_avatar($user['avatar_url'] ?? null, !empty($user['avatar_url']) ? (int)$user['id'] : 0, $user['full_name']), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
const emptyProfileAvatar = <?= json_encode(gf_avatar(null, 0, $user['full_name']), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
function previewProfileAvatar(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
        alert('Chọn ảnh JPG, PNG hoặc WEBP tối đa 5MB.');
        input.value = '';
        return;
    }
    document.getElementById('profileAvatar').src = URL.createObjectURL(file);
    document.getElementById('removeAvatar').value = '0';
}
function removeProfileAvatar() {
    document.getElementById('profileAvatar').src = emptyProfileAvatar;
    document.getElementById('avatarFile').value = '';
    document.getElementById('removeAvatar').value = '1';
}
document.getElementById('avatarFile').addEventListener('change', function () { previewProfileAvatar(this); });
</script>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
