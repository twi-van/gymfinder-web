<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
$user = gf_current_user();
gf_reject_locked_session($user);
$msg = '';
$error = '';

if ($user && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    gf_require_csrf();
    $action = $_POST['action'] ?? 'update_profile';

    if ($action === 'change_password') {
        $cur = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $cfm = (string)($_POST['confirm_password'] ?? '');

        if ($cur === '' || $new === '' || $cfm === '') {
            $error = 'Vui lòng nhập đầy đủ các trường mật khẩu.';
        } elseif ($new !== $cfm) {
            $error = 'Xác nhận mật khẩu mới không trùng khớp.';
        } elseif (strlen($new) < 8) {
            $error = 'Mật khẩu mới phải có tối thiểu 8 ký tự.';
        } elseif ($cur === $new) {
            $error = 'Mật khẩu mới phải khác mật khẩu hiện tại.';
        } else {
            $res = gf_change_password((int)$user['id'], $cur, $new);
            if ($res['ok']) {
                $msg = 'Đổi mật khẩu thành công.';
            } else {
                $error = $res['error'] ?? 'Không thể đổi mật khẩu.';
            }
        }
    } else {
        $oldAvatar = $user['avatar_url'] ?? null;
        $avatarUrl = $oldAvatar;
        $avatarChanged = false;

        if (!empty($_POST['remove_avatar'])) {
            $avatarUrl = null;
            $avatarChanged = true;
        }
        if (isset($_FILES['avatar_file']) && ($_FILES['avatar_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $upload = gf_save_upload($_FILES['avatar_file'], 'user_avatar', $user);
            if (!$upload['ok']) {
                $error = $upload['error'];
            } else {
                $avatarUrl = $upload['url'];
                $avatarChanged = true;
            }
        }
        if (!$error) {
            gf_update_profile((int)$user['id'], [
                'full_name' => trim((string)($_POST['full_name'] ?? '')),
                'phone' => trim((string)($_POST['phone'] ?? '')),
                'avatar_url' => $avatarUrl,
            ]);
            if ($avatarChanged && $oldAvatar && $oldAvatar !== $avatarUrl) {
                gf_delete_unreferenced_files([$oldAvatar]);
            }
            $user = gf_current_user();
            $msg = 'Đã lưu thông tin hồ sơ thành công.';
        }
    }
}

$pageTitle = 'Hồ sơ cá nhân - GYMFINDER';
$bodyClass = 'bg-light';

require dirname(__DIR__, 2) . '/includes/layout_head.php';
require dirname(__DIR__, 2) . '/includes/header.php';
?>
<style>
.profile-page {
    width: min(1000px, calc(100% - 32px));
    max-width: 1000px;
    margin: 0 auto;
    padding: 32px 0 72px;
}

.profile-panel {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 36px 42px;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
    margin-bottom: 24px;
}

.profile-panel-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
    padding-bottom: 18px;
    border-bottom: 1px solid #f1f5f9;
}

.profile-icon-badge {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    background-color: var(--primary-light, #f0fdfa);
    color: var(--primary-color, #00c9b1);
    flex-shrink: 0;
}

.profile-hero {
    display: flex;
    align-items: center;
    gap: 24px;
    padding-bottom: 28px;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 28px;
}

.profile-avatar-wrap {
    position: relative;
    flex: 0 0 auto;
}

.profile-avatar {
    width: 110px;
    height: 110px;
    object-fit: cover;
    border-radius: 50%;
    border: 4px solid #ffffff;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.1);
    background: #00c9b1;
}

.avatar-camera {
    position: absolute;
    right: 2px;
    bottom: 2px;
    width: 36px;
    height: 36px;
    align-items: center;
    justify-content: center;
    border: 2px solid #ffffff;
    border-radius: 50%;
    background: var(--primary-color, #00c9b1);
    color: #ffffff;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.2);
    cursor: pointer;
    transition: transform 0.15s ease;
}

.avatar-camera:hover {
    transform: scale(1.08);
}

.btn-remove-avatar {
    margin-top: 8px;
    padding: 0;
    border: 0;
    background: none;
    color: #ef4444;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
}

.btn-remove-avatar:hover {
    text-decoration: underline;
}

.profile-label {
    color: #64748b;
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 6px;
}

.profile-value {
    font-size: 17px;
    font-weight: 600;
    color: #0f172a;
}

.profile-edit-input {
    min-height: 48px;
    border: 1.5px solid #dbe2e8;
    border-radius: 12px;
    background: #f8fafc;
    font-size: 15px;
    padding: 10px 16px;
    transition: all 0.2s ease;
}

.profile-edit-input:focus {
    background: #ffffff;
    border-color: var(--primary-color, #00c9b1);
    box-shadow: 0 0 0 3px rgba(0, 201, 177, 0.15);
}

/* Nút hiện/ẩn mật khẩu */
.btn-toggle-password {
    border: 1.5px solid #dbe2e8;
    border-left: 0;
    background: #f8fafc;
    color: #64748b;
    border-top-right-radius: 12px !important;
    border-bottom-right-radius: 12px !important;
    padding: 0 16px;
    transition: all 0.15s ease;
}

.btn-toggle-password:hover {
    background: #f1f5f9;
    color: #0f172a;
}

.input-password-wrap .profile-edit-input {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.input-password-wrap:focus-within .btn-toggle-password {
    border-color: var(--primary-color, #00c9b1);
    background: #ffffff;
}

/* Điều khiển View/Edit của Thông tin cá nhân */
.profile-edit-grid {
    display: none;
}
.profile-page.editing .profile-view {
    display: none;
}
.profile-page.editing .profile-edit-grid {
    display: block;
}
.edit-only-element {
    display: none;
}
.profile-page.editing .edit-only-element {
    display: inline-flex;
}

/* Panel Đăng xuất */
.profile-account .btn-logout {
    min-width: min(260px, 100%);
    width: 100%;
    max-width: 320px;
    border: 1.5px solid #ef4444;
    color: #ef4444;
    background: #ffffff;
    border-radius: 30px;
    padding: 12px 28px;
    font-size: 16px;
    font-weight: 600;
    transition: all 0.2s ease;
}
.profile-account .btn-logout:hover {
    background: #fef2f2;
    color: #dc2626;
    border-color: #dc2626;
}

@media (max-width: 767px) {
    .profile-panel {
        padding: 24px 20px;
        border-radius: 16px;
    }
    .profile-hero {
        gap: 16px;
    }
    .profile-avatar {
        width: 84px;
        height: 84px;
    }
}
</style>

<?php if (!$user): ?>
<main class="flex-grow-1">
    <div class="container text-center py-5 mt-5">
        <div class="mb-4"><span class="d-inline-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm" style="width:100px;height:100px"><i class="fa-solid fa-user text-secondary" style="font-size:40px;color:#cbd5e1!important"></i></span></div>
        <h2 class="fw-bold mb-3">Đăng nhập để xem hồ sơ</h2>
        <p class="text-secondary mx-auto mb-4" style="max-width:500px">Vui lòng đăng nhập để xem và cập nhật thông tin cá nhân, ảnh đại diện và mật khẩu.</p>
        <a href="<?= gf_h(gf_url('pages/auth/login.php?redirect=' . urlencode(gf_url('pages/user/profile.php')))) ?>" class="btn btn-primary rounded-pill px-5 py-2 fw-semibold">Đăng nhập / Đăng ký</a>
    </div>
</main>
<?php else: ?>
<div class="profile-page" id="profilePage">
    <?php if ($msg): ?>
        <div class="alert alert-success d-flex align-items-center mb-4 rounded-3 shadow-sm">
            <i class="fa-solid fa-circle-check fs-5 me-2"></i>
            <div><?= gf_h($msg) ?></div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center mb-4 rounded-3 shadow-sm">
            <i class="fa-solid fa-circle-exclamation fs-5 me-2"></i>
            <div><?= gf_h($error) ?></div>
        </div>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- KHU VỰC 1: THÔNG TIN CÁ NHÂN -->
    <!-- ========================================================================= -->
    <div class="profile-panel">
        <div class="profile-hero">
            <div class="profile-avatar-wrap">
                <img id="profileAvatar" src="<?= gf_h(gf_avatar($user['avatar_url'] ?? null, !empty($user['avatar_url']) ? (int)$user['id'] : 0, $user['full_name'])) ?>" class="profile-avatar" alt="Avatar">
                <button class="avatar-camera edit-only-element" type="button" aria-label="Chọn ảnh đại diện" onclick="document.getElementById('avatarFile').click()"><i class="fa-solid fa-camera"></i></button>
            </div>
            <div>
                <h2 class="fw-bold mb-1" style="font-size: 24px; color: #0f172a;"><?= gf_h($user['full_name']) ?></h2>
                <div class="text-secondary mb-2" style="font-size: 15px;"><?= gf_h($user['email']) ?></div>
                <button type="button" class="btn-remove-avatar edit-only-element" onclick="removeProfileAvatar()"><i class="fa-solid fa-trash-can me-1"></i>Xóa ảnh đại diện</button>
            </div>
        </div>

        <!-- Chế độ Xem (Read-only View) -->
        <div class="profile-view">
            <div class="profile-panel-header">
                <div class="profile-icon-badge"><i class="fa-regular fa-user"></i></div>
                <div>
                    <h3 class="fw-bold fs-5 mb-0 text-dark">Thông tin cá nhân</h3>
                    <div class="text-secondary small">Chi tiết họ tên, số điện thoại và email liên hệ của bạn</div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="profile-label">Họ và tên</div>
                    <div class="profile-value"><?= gf_h($user['full_name']) ?></div>
                </div>
                <div class="col-md-6">
                    <div class="profile-label">Số điện thoại</div>
                    <div class="profile-value"><?= gf_h($user['phone'] ?: 'Chưa cập nhật') ?></div>
                </div>
                <div class="col-md-12">
                    <div class="profile-label">Email (Không thể thay đổi)</div>
                    <div class="profile-value d-flex align-items-center gap-2">
                        <span><?= gf_h($user['email']) ?></span>
                        <span class="badge bg-secondary-subtle text-secondary fw-normal" style="font-size: 12px;"><i class="fa-solid fa-lock me-1"></i>Cố định</span>
                    </div>
                </div>
            </div>

            <div class="text-end pt-2">
                <button type="button" class="btn btn-outline-primary rounded-pill px-4 fw-semibold" onclick="document.getElementById('profilePage').classList.add('editing')">
                    <i class="fa-solid fa-pen me-2"></i>Chỉnh sửa thông tin
                </button>
            </div>
        </div>

        <!-- Chế độ Chỉnh sửa (Edit Mode Form) -->
        <form id="profileEditForm" method="post" enctype="multipart/form-data" class="profile-edit-grid">
            <?= gf_csrf_field() ?>
            <input type="file" id="avatarFile" name="avatar_file" accept="image/jpeg,image/png,image/webp" class="d-none">
            <input type="hidden" name="remove_avatar" id="removeAvatar" value="0">

            <div class="profile-panel-header">
                <div class="profile-icon-badge"><i class="fa-solid fa-user-pen"></i></div>
                <div>
                    <h3 class="fw-bold fs-5 mb-0 text-dark">Chỉnh sửa thông tin cá nhân</h3>
                    <div class="text-secondary small">Cập nhật họ tên và số điện thoại của tài khoản</div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label class="form-label profile-label" for="profileName">Họ và tên <span class="text-danger">*</span></label>
                    <input id="profileName" class="form-control profile-edit-input" name="full_name" value="<?= gf_h($user['full_name']) ?>" required placeholder="Nhập họ và tên...">
                </div>
                <div class="col-md-6">
                    <label class="form-label profile-label" for="profilePhone">Số điện thoại</label>
                    <input id="profilePhone" class="form-control profile-edit-input" name="phone" value="<?= gf_h($user['phone'] ?? '') ?>" placeholder="Nhập số điện thoại...">
                </div>
                <div class="col-md-12">
                    <label class="form-label profile-label">Email (Không thể thay đổi)</label>
                    <input class="form-control profile-edit-input bg-light text-muted" value="<?= gf_h($user['email']) ?>" disabled readonly>
                </div>
            </div>

            <div class="d-flex gap-3 pt-2">
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
                    <i class="fa-solid fa-check me-2"></i>Lưu thay đổi
                </button>
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold" onclick="cancelProfileEdit()">
                    Hủy
                </button>
            </div>
        </form>
    </div>

    <!-- ========================================================================= -->
    <!-- KHU VỰC 2: BẢO MẬT TÀI KHOẢN (Đổi mật khẩu) -->
    <!-- ========================================================================= -->
    <div class="profile-panel">
        <div class="profile-panel-header">
            <div class="profile-icon-badge"><i class="fa-solid fa-shield-halved"></i></div>
            <div>
                <h3 class="fw-bold fs-5 mb-0 text-dark">Bảo mật tài khoản</h3>
                <div class="text-secondary small">Thay đổi mật khẩu đăng nhập định kỳ để tăng cường an toàn thông tin</div>
            </div>
        </div>

        <div id="passwordAlert" class="alert d-none mb-4 rounded-3" role="alert"></div>

        <form id="passwordChangeForm" method="post" onsubmit="handlePasswordSubmit(event)">
            <?= gf_csrf_field() ?>
            <input type="hidden" name="action" value="change_password">
            <div class="row g-4 mb-4">
                <div class="col-12 col-md-4">
                    <label class="form-label profile-label" for="currentPassword">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                    <div class="input-group input-password-wrap">
                        <input type="password" id="currentPassword" name="current_password" class="form-control profile-edit-input" placeholder="Nhập mật khẩu hiện tại..." required autocomplete="current-password">
                        <button class="btn btn-toggle-password" type="button" aria-label="Hiện hoặc ẩn mật khẩu" onclick="togglePasswordVisibility('currentPassword', this)">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label profile-label" for="newPassword">Mật khẩu mới <span class="text-danger">*</span></label>
                    <div class="input-group input-password-wrap">
                        <input type="password" id="newPassword" name="new_password" class="form-control profile-edit-input" placeholder="Tối thiểu 8 ký tự..." minlength="8" required autocomplete="new-password">
                        <button class="btn btn-toggle-password" type="button" aria-label="Hiện hoặc ẩn mật khẩu" onclick="togglePasswordVisibility('newPassword', this)">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label profile-label" for="confirmPassword">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                    <div class="input-group input-password-wrap">
                        <input type="password" id="confirmPassword" name="confirm_password" class="form-control profile-edit-input" placeholder="Nhập lại mật khẩu mới..." minlength="8" required autocomplete="new-password">
                        <button class="btn btn-toggle-password" type="button" aria-label="Hiện hoặc ẩn mật khẩu" onclick="togglePasswordVisibility('confirmPassword', this)">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between pt-2 flex-wrap gap-3">
                <div class="small text-secondary">
                    <i class="fa-solid fa-circle-info me-1"></i>Mật khẩu mới cần từ 8 đến 72 ký tự và khác mật khẩu hiện tại.
                </div>
                <div class="d-flex gap-2">
                    <button type="reset" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold" onclick="resetPasswordForm()">
                        Làm lại
                    </button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
                        <i class="fa-solid fa-key me-2"></i>Đổi mật khẩu
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ========================================================================= -->
    <!-- KHU VỰC 4: TÀI KHOẢN (Đăng xuất) -->
    <!-- ========================================================================= -->
    <div class="profile-panel profile-account text-center">
        <div class="profile-icon-badge mx-auto mb-3" style="background:#fee2e2; color:#ef4444;"><i class="fa-solid fa-arrow-right-from-bracket"></i></div>
        <h3 class="fw-bold fs-5 mb-1 text-dark">Tài khoản</h3>
        <p class="text-secondary small mb-4">Kết thúc phiên đăng nhập của bạn trên thiết bị này</p>
        <div>
            <form method="post" action="<?= gf_h(gf_url('pages/auth/logout.php')) ?>">
                <?= gf_csrf_field() ?>
                <button class="btn btn-logout" type="submit">
                    <i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Đăng xuất
                </button>
            </form>
        </div>
    </div>
</div>

<script>
const savedProfileAvatar = <?= json_encode(gf_avatar($user['avatar_url'] ?? null, !empty($user['avatar_url']) ? (int)$user['id'] : 0, $user['full_name']), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
const emptyProfileAvatar = <?= json_encode(gf_avatar(null, 0, $user['full_name']), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
let activeObjectUrl = null;

function revokeActiveObjectUrl() {
    if (activeObjectUrl) {
        URL.revokeObjectURL(activeObjectUrl);
        activeObjectUrl = null;
    }
}

function previewProfileAvatar(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        typeof window.gfToast === 'function' ? window.gfToast('File không hợp lệ. Vui lòng chọn ảnh định dạng JPG, PNG hoặc WEBP.', 'warning') : null;
        input.value = '';
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        typeof window.gfToast === 'function' ? window.gfToast('Dung lượng file vượt quá giới hạn 5 MB. Vui lòng chọn ảnh nhỏ hơn.', 'warning') : null;
        input.value = '';
        return;
    }
    revokeActiveObjectUrl();
    activeObjectUrl = URL.createObjectURL(file);
    document.getElementById('profileAvatar').src = activeObjectUrl;
    document.getElementById('removeAvatar').value = '0';
}

function removeProfileAvatar() {
    revokeActiveObjectUrl();
    document.getElementById('profileAvatar').src = emptyProfileAvatar;
    document.getElementById('avatarFile').value = '';
    document.getElementById('removeAvatar').value = '1';
}

function cancelProfileEdit() {
    revokeActiveObjectUrl();
    document.getElementById('profileAvatar').src = savedProfileAvatar;
    document.getElementById('avatarFile').value = '';
    document.getElementById('removeAvatar').value = '0';
    document.getElementById('profilePage').classList.remove('editing');
}

document.getElementById('avatarFile').addEventListener('change', function () { previewProfileAvatar(this); });
window.addEventListener('beforeunload', revokeActiveObjectUrl);

// =============================================================================
// Tương tác giao diện: Đổi mật khẩu & Ẩn/Hiện mật khẩu
// =============================================================================
function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (!input || !icon) return;

    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa-regular fa-eye-slash';
        btn.setAttribute('title', 'Ẩn mật khẩu');
    } else {
        input.type = 'password';
        icon.className = 'fa-regular fa-eye';
        btn.setAttribute('title', 'Hiện mật khẩu');
    }
}

function resetPasswordForm() {
    const alertBox = document.getElementById('passwordAlert');
    if (alertBox) {
        alertBox.className = 'alert d-none mb-4 rounded-3';
        alertBox.innerHTML = '';
    }
    ['currentPassword', 'newPassword', 'confirmPassword'].forEach(id => {
        const input = document.getElementById(id);
        if (input) input.type = 'password';
    });
    document.querySelectorAll('.btn-toggle-password i').forEach(icon => {
        icon.className = 'fa-regular fa-eye';
    });
}

async function handlePasswordSubmit(e) {
    e.preventDefault();
    const cur = document.getElementById('currentPassword').value.trim();
    const np = document.getElementById('newPassword').value;
    const cp = document.getElementById('confirmPassword').value;

    if (!cur) {
        showPasswordAlert('danger', 'Vui lòng nhập mật khẩu hiện tại.');
        return;
    }
    if (np.length < 8) {
        showPasswordAlert('warning', 'Mật khẩu mới phải có tối thiểu 8 ký tự.');
        return;
    }
    if (np === cur) {
        showPasswordAlert('warning', 'Mật khẩu mới phải khác mật khẩu hiện tại.');
        return;
    }
    if (np !== cp) {
        showPasswordAlert('danger', 'Xác nhận mật khẩu mới không trùng khớp với mật khẩu mới.');
        return;
    }

    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalContent = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Đang xử lý...';

    try {
        const res = await fetch('<?= gf_h(gf_url('api/auth/change-password')) ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?= gf_csrf_token() ?>'
            },
            body: JSON.stringify({
                current_password: cur,
                new_password: np
            })
        });

        if (res.status === 204) {
            showPasswordAlert('success', '<i class="fa-solid fa-circle-check me-2"></i>Đổi mật khẩu thành công! Mật khẩu mới đã được cập nhật.');
            resetPasswordForm();
        } else {
            const data = await res.json().catch(() => null);
            const errMsg = data?.error?.message || data?.error || 'Không thể đổi mật khẩu. Vui lòng kiểm tra lại mật khẩu hiện tại.';
            showPasswordAlert('danger', '<i class="fa-solid fa-circle-exclamation me-2"></i>' + errMsg);
        }
    } catch (err) {
        // Fallback submit form thông thường qua PHP POST nếu gặp lỗi mạng
        e.target.submit();
        return;
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalContent;
    }
}

function showPasswordAlert(type, message) {
    const alertBox = document.getElementById('passwordAlert');
    if (!alertBox) return;
    alertBox.className = 'alert alert-' + type + ' mb-4 rounded-3 shadow-sm';
    alertBox.innerHTML = message;
    alertBox.classList.remove('d-none');
    alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
</script>
<?php endif; ?>

<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
