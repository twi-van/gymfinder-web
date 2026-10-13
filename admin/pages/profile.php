<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
$adminUser = gf_require_admin();

$msg = '';
$error = '';
$activeTab = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    gf_require_csrf();
    $action = $_POST['action'] ?? 'update_profile';

    if ($action === 'update_profile') {
        $activeTab = 'info';
        $oldAvatar = $adminUser['avatar_url'] ?? null;
        $avatarUrl = $oldAvatar;
        $avatarChanged = false;

        if (!empty($_POST['remove_avatar'])) {
            $avatarUrl = null;
            $avatarChanged = true;
        }

        if (isset($_FILES['avatar_file']) && ($_FILES['avatar_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $upload = gf_save_upload($_FILES['avatar_file'], 'user_avatar', $adminUser);
            if (!$upload['ok']) {
                $error = $upload['error'];
            } else {
                $avatarUrl = $upload['url'];
                $avatarChanged = true;
            }
        }

        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));

        if (!$error) {
            if ($fullName === '') {
                $error = 'Họ và tên không được để trống.';
            } elseif (mb_strlen($fullName) > 100) {
                $error = 'Họ và tên không được vượt quá 100 ký tự.';
            } elseif ($phone !== '' && !preg_match('/^\+?[0-9]{9,14}$/', $phone)) {
                $error = 'Số điện thoại không hợp lệ (yêu cầu từ 9 đến 14 chữ số).';
            } else {
                // CHỈ cập nhật thông tin cá nhân của chính Admin đang đăng nhập.
                // TUYỆT ĐỐI không thay đổi role, status, email hay can thiệp dữ liệu quản trị khác.
                gf_update_profile((int)$adminUser['id'], [
                    'full_name'  => $fullName,
                    'phone'      => $phone !== '' ? $phone : null,
                    'avatar_url' => $avatarUrl,
                ]);

                if ($avatarChanged && $oldAvatar && $oldAvatar !== $avatarUrl) {
                    gf_delete_unreferenced_files([$oldAvatar]);
                }

                $adminUser = gf_current_user();
                $msg = 'Đã cập nhật thông tin hồ sơ quản trị viên thành công.';
            }
        }
    } elseif ($action === 'change_password') {
        $activeTab = 'security';
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $error = 'Vui lòng nhập đầy đủ các trường mật khẩu.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Xác nhận mật khẩu mới không trùng khớp.';
        } elseif (strlen($newPassword) < 8) {
            $error = 'Mật khẩu mới phải có tối thiểu 8 ký tự.';
        } elseif ($currentPassword === $newPassword) {
            $error = 'Mật khẩu mới phải khác mật khẩu hiện tại.';
        } else {
            // Xác thực và đổi mật khẩu cho chính tài khoản Admin hiện tại
            $res = gf_change_password((int)$adminUser['id'], $currentPassword, $newPassword);
            if ($res['ok']) {
                $msg = 'Đổi mật khẩu tài khoản quản trị thành công.';
            } else {
                $error = $res['error'] ?? 'Không thể đổi mật khẩu.';
            }
        }
    }
}

$pageTitle = 'Hồ sơ cá nhân - Admin GYMFINDER';
$extraCss = ['admin/assets/admin.css'];
$adminActive = 'profile';

require dirname(__DIR__, 2) . '/includes/layout_head.php';
require dirname(__DIR__, 2) . '/includes/admin_layout.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1 text-dark" style="font-size: 24px; letter-spacing: -0.3px;">Hồ sơ cá nhân</h2>
        <p class="text-secondary mb-0 small">Quản lý thông tin tài khoản và bảo mật mật khẩu của Quản trị viên</p>
    </div>
    <div>
        <span class="badge bg-primary-light text-primary px-3 py-2 rounded-pill fw-semibold border border-primary-subtle">
            <i class="fa-solid fa-user-shield me-1"></i> Quản trị viên hệ thống
        </span>
    </div>
</div>

<?php if ($msg): ?>
    <div class="alert alert-success d-flex align-items-center mb-4 rounded-3 shadow-sm border-0 bg-success-subtle text-success">
        <i class="fa-solid fa-circle-check fs-5 me-2"></i>
        <div><?= gf_h($msg) ?></div>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger d-flex align-items-center mb-4 rounded-3 shadow-sm border-0 bg-danger-subtle text-danger">
        <i class="fa-solid fa-circle-exclamation fs-5 me-2"></i>
        <div><?= gf_h($error) ?></div>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- CỘT TRÁI: THẺ THÔNG TIN TÓM TẮT -->
    <div class="col-lg-4 col-xl-3">
        <div class="admin-card text-center p-4">
            <div class="position-relative d-inline-block mb-3">
                <img id="adminAvatarPreview" 
                     src="<?= gf_h(gf_avatar($adminUser['avatar_url'] ?? null, !empty($adminUser['avatar_url']) ? (int)$adminUser['id'] : 0, $adminUser['full_name'])) ?>" 
                     class="rounded-circle shadow-sm" 
                     style="width: 120px; height: 120px; object-fit: cover; border: 4px solid #f8fafc;" 
                     alt="<?= gf_h($adminUser['full_name']) ?>">
            </div>
            <h5 class="fw-bold text-dark mb-1"><?= gf_h($adminUser['full_name']) ?></h5>
            <div class="text-secondary small mb-3"><?= gf_h($adminUser['email']) ?></div>
            
            <hr class="text-secondary opacity-25 my-3">
            
            <div class="text-start small">
                <div class="d-flex justify-content-between py-1">
                    <span class="text-secondary">Vai trò:</span>
                    <span class="badge bg-primary text-white rounded-pill px-2 py-1">Quản trị viên</span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-secondary">Trạng thái:</span>
                    <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1">Hoạt động</span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-secondary">ID tài khoản:</span>
                    <span class="fw-semibold text-dark">#<?= (int)$adminUser['id'] ?></span>
                </div>
                <div class="d-flex justify-content-between py-1">
                    <span class="text-secondary">Ngày tham gia:</span>
                    <span class="fw-semibold text-dark"><?= !empty($adminUser['created_at']) ? date('d/m/Y', strtotime($adminUser['created_at'])) : '—' ?></span>
                </div>
            </div>
        </div>

        <div class="admin-card p-3 mt-3 bg-white">
            <div class="d-flex align-items-center gap-2 text-primary fw-semibold small mb-2">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Quy tắc bảo mật</span>
            </div>
            <p class="text-secondary small mb-0" style="line-height: 1.5;">
                Hệ thống chỉ cho phép cập nhật hồ sơ cá nhân của chính bạn. Không cho phép tự đổi vai trò hoặc cấp thêm quyền thông qua chức năng này.
            </p>
        </div>
    </div>

    <!-- CỘT PHẢI: FORM CHỈNH SỬA & BẢO MẬT -->
    <div class="col-lg-8 col-xl-9">
        <!-- FORM 1: THÔNG TIN CÁ NHÂN -->
        <div class="admin-card p-4 mb-4">
            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                <div class="rounded-3 d-inline-flex align-items-center justify-content-center bg-primary-light text-primary" style="width: 42px; height: 42px; font-size: 18px;">
                    <i class="fa-solid fa-user-pen"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-0">Thông tin cá nhân</h5>
                    <div class="text-secondary small">Cập nhật họ tên, số điện thoại và ảnh đại diện quản trị viên</div>
                </div>
            </div>

            <form method="post" enctype="multipart/form-data">
                <?= gf_csrf_field() ?>
                <input type="hidden" name="action" value="update_profile">
                <input type="file" id="adminAvatarInput" name="avatar_file" accept="image/jpeg,image/png,image/webp" class="d-none" onchange="previewAdminAvatar(this)">
                <input type="hidden" name="remove_avatar" id="adminRemoveAvatarInput" value="0">

                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <label class="form-label fw-semibold small text-secondary">Ảnh đại diện</label>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="document.getElementById('adminAvatarInput').click()">
                                <i class="fa-solid fa-camera me-1"></i>Chọn ảnh mới
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-none" id="adminAvatarCancelBtn" onclick="cancelAdminAvatar()">
                                <i class="fa-solid fa-rotate-left me-1"></i>Hủy
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="removeAdminAvatar()">
                                <i class="fa-solid fa-trash me-1"></i>Xóa ảnh đại diện
                            </button>
                            <span class="small text-secondary ms-md-2">Hỗ trợ JPG, PNG, WEBP tối đa 5 MB.</span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small" for="adminFullName">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" id="adminFullName" name="full_name" class="form-control admin-input" value="<?= gf_h($adminUser['full_name']) ?>" required placeholder="Nhập họ và tên...">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small" for="adminPhone">Số điện thoại</label>
                        <input type="text" id="adminPhone" name="phone" class="form-control admin-input" value="<?= gf_h($adminUser['phone'] ?? '') ?>" placeholder="VD: 0912345678...">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Email (Cố định, không thể thay đổi)</label>
                        <div class="input-group">
                            <input type="email" class="form-control admin-input bg-light text-muted" value="<?= gf_h($adminUser['email']) ?>" disabled readonly>
                            <span class="input-group-text bg-light text-secondary border-start-0"><i class="fa-solid fa-lock"></i></span>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Vai trò tài khoản (Không thể tự thay đổi)</label>
                        <input type="text" class="form-control admin-input bg-light text-muted" value="Quản trị viên (admin)" disabled readonly>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary px-4 rounded-3 fw-semibold">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Lưu thông tin hồ sơ
                    </button>
                </div>
            </form>
        </div>

        <!-- FORM 2: ĐỔI MẬT KHẨU -->
        <div class="admin-card p-4">
            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                <div class="rounded-3 d-inline-flex align-items-center justify-content-center bg-warning-subtle text-warning" style="width: 42px; height: 42px; font-size: 18px;">
                    <i class="fa-solid fa-key"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-0">Đổi mật khẩu quản trị</h5>
                    <div class="text-secondary small">Cần xác nhận mật khẩu hiện tại trước khi thiết lập mật khẩu mới</div>
                </div>
            </div>

            <form method="post" id="adminPasswordForm">
                <?= gf_csrf_field() ?>
                <input type="hidden" name="action" value="change_password">

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small" for="adminCurPass">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" id="adminCurPass" name="current_password" class="form-control admin-input" required placeholder="Mật khẩu đang dùng...">
                            <button class="btn btn-light border border-start-0" type="button" onclick="togglePassVisibility('adminCurPass', this)">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small" for="adminNewPass">Mật khẩu mới <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" id="adminNewPass" name="new_password" class="form-control admin-input" minlength="8" required placeholder="Tối thiểu 8 ký tự...">
                            <button class="btn btn-light border border-start-0" type="button" onclick="togglePassVisibility('adminNewPass', this)">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small" for="adminConfirmPass">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" id="adminConfirmPass" name="confirm_password" class="form-control admin-input" minlength="8" required placeholder="Nhập lại mật khẩu mới...">
                            <button class="btn btn-light border border-start-0" type="button" onclick="togglePassVisibility('adminConfirmPass', this)">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="small text-secondary">
                        <i class="fa-solid fa-circle-info me-1 text-primary"></i> Mật khẩu an toàn phải từ 8 đến 72 ký tự và không được trùng với mật khẩu cũ.
                    </div>
                    <button type="submit" class="btn btn-primary px-4 rounded-3 fw-semibold">
                        <i class="fa-solid fa-lock me-2"></i>Cập nhật mật khẩu
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const originalAdminAvatar = <?= json_encode(gf_avatar($adminUser['avatar_url'] ?? null, !empty($adminUser['avatar_url']) ? (int)$adminUser['id'] : 0, $adminUser['full_name']), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
const defaultAdminAvatar = <?= json_encode(gf_avatar(null, 0, $adminUser['full_name']), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
let adminAvatarBlobUrl = null;

function previewAdminAvatar(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        alert('Vui lòng chọn ảnh định dạng JPG, PNG hoặc WEBP.');
        input.value = '';
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        alert('Dung lượng ảnh vượt quá 5 MB. Vui lòng chọn ảnh nhỏ hơn.');
        input.value = '';
        return;
    }
    if (adminAvatarBlobUrl) URL.revokeObjectURL(adminAvatarBlobUrl);
    adminAvatarBlobUrl = URL.createObjectURL(file);
    document.getElementById('adminAvatarPreview').src = adminAvatarBlobUrl;
    document.getElementById('adminRemoveAvatarInput').value = '0';
    document.getElementById('adminAvatarCancelBtn').classList.remove('d-none');
}

function cancelAdminAvatar() {
    if (adminAvatarBlobUrl) {
        URL.revokeObjectURL(adminAvatarBlobUrl);
        adminAvatarBlobUrl = null;
    }
    document.getElementById('adminAvatarPreview').src = originalAdminAvatar;
    document.getElementById('adminAvatarInput').value = '';
    document.getElementById('adminRemoveAvatarInput').value = '0';
    document.getElementById('adminAvatarCancelBtn').classList.add('d-none');
}

function removeAdminAvatar() {
    if (adminAvatarBlobUrl) {
        URL.revokeObjectURL(adminAvatarBlobUrl);
        adminAvatarBlobUrl = null;
    }
    document.getElementById('adminAvatarPreview').src = defaultAdminAvatar;
    document.getElementById('adminAvatarInput').value = '';
    document.getElementById('adminRemoveAvatarInput').value = '1';
    document.getElementById('adminAvatarCancelBtn').classList.remove('d-none');
}

function togglePassVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (!input || !icon) return;
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa-regular fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fa-regular fa-eye';
    }
}
</script>

</main></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
