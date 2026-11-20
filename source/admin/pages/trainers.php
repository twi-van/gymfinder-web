<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
require_once dirname(__DIR__, 2) . '/includes/trainer_gyms_ui.php';
require_once dirname(__DIR__) . '/components/table.php';
$adminUser = gf_require_admin();
$err = '';

/**
 * Build + validate trainer admin form (all visible fields required except "Nổi bật").
 * @return array{0: array<string,mixed>, 1: string}
 */
$gfBuildTrainerAdminPayload = static function (array $post, array $files, array $adminUser, bool $isCreate): array {
    $errLocal = '';
    [$gymIds] = gf_ui_normalize_gym_ids_post($post);
    [$specIds] = gf_ui_normalize_specialty_ids_post($post);

    $fullName = trim((string) ($post['full_name'] ?? ''));
    $phone = preg_replace('/[\s.\-]+/', '', trim((string) ($post['phone'] ?? ''))) ?? '';
    $bio = trim((string) ($post['bio'] ?? ''));
    $yearsRaw = $post['years_experience'] ?? '';
    $status = (($post['status'] ?? '') === 'hidden') ? 'hidden' : 'active';

    if ($fullName === '') {
        $errLocal = 'Vui lòng nhập họ và tên HLV.';
    } elseif ($yearsRaw === '' || !gf_integer_like($yearsRaw) || (int) $yearsRaw < 0 || (int) $yearsRaw > 60) {
        $errLocal = 'Vui lòng nhập số năm kinh nghiệm (0–60).';
    } elseif ($specIds === []) {
        $errLocal = 'Vui lòng chọn ít nhất một chuyên môn.';
    } elseif ($isCreate && $gymIds === []) {
        $errLocal = 'Vui lòng chọn ít nhất một phòng tập liên kết.';
    } elseif ($phone === '') {
        $errLocal = 'Vui lòng nhập số điện thoại.';
    } elseif (!gf_phone_ok($phone)) {
        $errLocal = 'Số điện thoại không hợp lệ (9–14 chữ số, có thể bắt đầu bằng +).';
    } elseif ($bio === '') {
        $errLocal = 'Vui lòng nhập tiểu sử.';
    }

    // Whitelist only — avoids gf_require_known_body rejecting form extras.
    $data = [
        'full_name'        => $fullName,
        'years_experience' => (int) $yearsRaw,
        'bio'              => $bio,
        'phone'            => $phone,
        'status'           => $status,
        'is_featured'      => !empty($post['is_featured']) ? 1 : 0,
        'gym_ids'          => $gymIds,
        'specialty_ids'    => $specIds,
    ];

    if (!empty($post['remove_avatar'])) {
        $data['avatar_url'] = null;
    } elseif (array_key_exists('avatar_url', $post)) {
        $data['avatar_url'] = trim((string) $post['avatar_url']) ?: null;
    }
    if (isset($files['avatar_file']) && ($files['avatar_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $up = gf_save_upload($files['avatar_file'], 'trainer_avatar', $adminUser);
        if (!$up['ok']) {
            $errLocal = $up['error'];
        } else {
            $data['avatar_url'] = $up['url'];
        }
    }

    if (!$errLocal && $isCreate && empty($data['avatar_url'])) {
        $errLocal = 'Vui lòng chọn ảnh đại diện huấn luyện viên.';
    }
    if (!$errLocal && !$isCreate && !empty($post['remove_avatar']) && empty($data['avatar_url'])) {
        $errLocal = 'Vui lòng chọn ảnh đại diện huấn luyện viên.';
    }

    return [$data, $errLocal];
};

$gfFormatTrainerSaveError = static function (array $res, string $fallback): string {
    $msg = (string) ($res['error'] ?? $fallback);
    if (!empty($res['details']) && is_array($res['details'])) {
        $parts = [];
        foreach ($res['details'] as $field => $why) {
            $parts[] = is_string($field) && $field !== ''
                ? $field . ': ' . (string) $why
                : (string) $why;
        }
        if ($parts !== []) {
            $msg .= ' (' . implode('; ', $parts) . ')';
        }
    }
    return $msg;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    gf_require_csrf();
    if (!empty($_POST['id']) && isset($_POST['full_name'])) {
        $id = (int) $_POST['id'];
        [$data, $err] = $gfBuildTrainerAdminPayload($_POST, $_FILES, $adminUser, false);
        if (!$err) {
            $res = gf_admin_save_trainer($data, $id);
            if (!empty($res['ok'])) {
                gf_flash_redirect(gf_url('admin/pages/trainers.php'), 'Đã cập nhật huấn luyện viên thành công.');
            }
            $err = $gfFormatTrainerSaveError($res, 'Không thể cập nhật huấn luyện viên.');
        }
    } elseif (!empty($_POST['id']) && ($_POST['action'] ?? '') === 'delete' && !isset($_POST['full_name'])) {
        $res = gf_admin_delete_trainer((int) $_POST['id']);
        if (!empty($res['ok'])) {
            gf_flash_redirect(gf_url('admin/pages/trainers.php'), 'Đã xóa huấn luyện viên thành công.');
        }
        $err = (string) ($res['error'] ?? 'Không thể xóa huấn luyện viên.');
    } elseif (!empty($_POST['id']) && isset($_POST['status']) && !isset($_POST['full_name'])) {
        // Toggle ẩn/hiện only — must not catch create/edit form (has empty id + status select).
        $newStatus = $_POST['status'] === 'hidden' ? 'hidden' : 'active';
        gf_set_entity_status('trainers', (int) $_POST['id'], $newStatus);
        $msg = $newStatus === 'hidden'
            ? 'Đã tạm ẩn huấn luyện viên thành công. Hồ sơ sẽ không hiển thị trên các trang công khai.'
            : 'Đã kích hoạt lại huấn luyện viên thành công. Hồ sơ đã sẵn sàng hiển thị công khai.';
        gf_flash_redirect(gf_url('admin/pages/trainers.php'), $msg);
    } elseif (isset($_POST['full_name'])) {
        [$data, $err] = $gfBuildTrainerAdminPayload($_POST, $_FILES, $adminUser, true);
        if (!$err) {
            $res = gf_admin_save_trainer($data, null);
            if (!empty($res['ok'])) {
                gf_flash_redirect(gf_url('admin/pages/trainers.php'), 'Đã thêm huấn luyện viên thành công.');
            }
            $err = $gfFormatTrainerSaveError($res, 'Không thể lưu huấn luyện viên.');
        }
    }
}
$reopenTrainerModal = ($err !== '');
$trainerPage = max(1, (int)($_GET['page'] ?? 1));
$trainerLimit = 8;
$trainerStatusFilter = (string) ($_GET['status'] ?? '');
$trainerGymFilter = trim((string) ($_GET['gym_id'] ?? ''));
if ($trainerGymFilter !== '' && (!gf_integer_like($trainerGymFilter) || (int) $trainerGymFilter < 1 || !gf_exists_id('gyms', (int) $trainerGymFilter))) {
    $trainerGymFilter = '';
}
$gyms = gf_admin_gyms_paged(['limit' => 100, 'sort' => 'name_asc'])['items'];
$filters = [
    'limit'        => $trainerLimit,
    'page'         => $trainerPage,
    // newest first so vừa thêm HLV hiện ngay trang 1 (không bị đẩy xuống trang cuối)
    'sort'         => 'newest',
    'q'            => $_GET['q'] ?? '',
    'specialty_id' => $_GET['specialty_id'] ?? '',
    'gym_id'       => $trainerGymFilter,
    'status'       => in_array($trainerStatusFilter, ['active', 'hidden'], true) ? $trainerStatusFilter : '',
    'is_featured'  => $trainerStatusFilter === 'featured' ? '1' : ($trainerStatusFilter === 'not_featured' ? '0' : ''),
];
$trainerResult = gf_admin_trainers_paged($filters);

// Tự động lùi về trang hợp lệ gần nhất nếu trang hiện tại bị rỗng (sau khi xóa)
if ($trainerPage > 1 && empty($trainerResult['items']) && (int)$trainerResult['total'] > 0) {
    $trainerPage = max(1, (int) ceil((int)$trainerResult['total'] / $trainerLimit));
    $filters['page'] = $trainerPage;
    $trainerResult = gf_admin_trainers_paged($filters);
}
$rows = $trainerResult['items'];
$specs = gf_list_specialties();
$specById = [];
foreach ($specs as $s) {
    $specById[(int)$s['id']] = $s;
}
$pageTitle = 'Quản lý huấn luyện viên - Admin GYMFINDER';
$extraCss = ['admin/assets/admin.css'];
$adminActive = 'trainers';
require dirname(__DIR__, 2) . '/includes/layout_head.php';
require dirname(__DIR__, 2) . '/includes/admin_layout.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1 text-dark" style="font-size: 24px; letter-spacing: -0.3px;"><?= gf_h(gf_t('Quản lý huấn luyện viên')) ?></h2>
        <p class="text-secondary mb-0 small"><?= gf_h(gf_t('Quản lý hồ sơ, chuyên môn, phòng tập liên kết; xóa khi HLV chưa gắn phòng tập nào')) ?></p>
    </div>
    <button class="btn btn-primary px-3 rounded-3" onclick="trainerModalCreate()" data-bs-toggle="modal" data-bs-target="#trainerCreate"><i class="fa-solid fa-plus me-2"></i><?= gf_h(gf_t('Thêm mới')) ?></button>
</div>
<div class="modal fade" id="trainerCreate" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content border-0 shadow rounded-4"><div class="modal-header border-0"><h5 class="modal-title fw-bold" id="trainerModalTitle"><?= gf_h(gf_t('Thêm huấn luyện viên mới')) ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div id="trainer-error-alert" class="alert alert-danger d-none"></div><form method="post" id="trainerForm" class="js-form-validate" data-inline-errors="1" novalidate enctype="multipart/form-data" onsubmit="return handleTrainerFormSubmit(event)">
    <?= gf_csrf_field() ?>
    <input type="hidden" name="id" id="trainerId" value="">
    <input type="hidden" name="avatar_url" id="trainerAvatarUrl" value="">
    <input type="hidden" name="remove_avatar" id="trainerRemoveAvatar" value="0">
    <div class="row g-3">
        <!-- AVATAR UPLOAD SECTION -->
        <div class="col-12" data-field id="trainerAvatarField">
            <label class="form-label fw-bold mb-2"><?= gf_h(gf_t('Ảnh đại diện huấn luyện viên *')) ?></label>
            <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 border" id="trainerAvatarBox">
                <img id="trainerAvatarPreview" src="https://ui-avatars.com/api/?name=HLV&background=00c9b1&color=fff" class="rounded-circle shadow-sm object-fit-cover" style="width:72px;height:72px;flex:0 0 72px;" alt="<?= gf_h(gf_t('Xem trước ảnh đại diện')) ?>" onerror="this.onerror=null;this.src=defaultTrainerPlaceholder;">
                <div class="flex-grow-1">
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <input type="file" id="trainerAvatarFile" name="avatar_file" accept="image/jpeg,image/png,image/webp" class="d-none" onchange="previewTrainerAvatar(this)">
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="document.getElementById('trainerAvatarFile').click()"><i class="fa-solid fa-camera me-1"></i><?= gf_h(gf_t('Chọn / Thay ảnh')) ?></button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-none" id="trainerAvatarCancelBtn" onclick="cancelTrainerAvatar()"><i class="fa-solid fa-rotate-left me-1"></i><?= gf_h(gf_t('Hủy đổi')) ?></button>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" id="trainerAvatarRemoveBtn" onclick="removeTrainerAvatar()"><i class="fa-solid fa-trash me-1"></i><?= gf_h(gf_t('Xóa ảnh đại diện')) ?></button>
                    </div>
                    <div class="small text-secondary"><?= gf_h(gf_t('Hỗ trợ JPG, PNG, WEBP tối đa 5 MB. Bắt buộc khi thêm mới.')) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-6" data-field><label class="form-label"><?= gf_h(gf_t('Họ và tên *')) ?></label><input class="form-control admin-input" name="full_name" id="trainerName" placeholder="<?= gf_h(gf_t('Họ tên')) ?>" required data-required-msg="<?= gf_h(gf_t('Vui lòng nhập họ và tên HLV.')) ?>"></div>
        <div class="col-md-6" data-field><label class="form-label"><?= gf_h(gf_t('Kinh nghiệm (năm) *')) ?></label><input class="form-control admin-input" type="number" name="years_experience" id="trainerYears" value="3" min="0" max="60" required data-required-msg="<?= gf_h(gf_t('Vui lòng nhập số năm kinh nghiệm.')) ?>"></div>
        <div class="col-12" data-field id="trainerSpecialtyField"><label class="form-label"><?= gf_h(gf_t('Chuyên môn *')) ?> <span class="text-secondary fw-normal"><?= gf_h(gf_t('(chọn một hoặc nhiều; tối thiểu 1)')) ?></span></label>
            <div id="trainerSpecialtyList" class="border rounded-3 p-3 bg-light" style="max-height:180px;overflow:auto;">
                <?php foreach ($specs as $s): ?>
                    <label class="form-check d-flex align-items-center gap-2 mb-2">
                        <input class="form-check-input trainer-specialty-check" type="checkbox" name="specialty_ids[]" value="<?= (int)$s['id'] ?>">
                        <span class="form-check-label"><?= gf_h($s['name']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="form-text"><?= gf_h(gf_t('Có thể chọn nhiều chuyên môn; tất cả lựa chọn được lưu trên hồ sơ này.')) ?></div>
        </div>
        <div class="col-12" data-field id="trainerGymField"><label class="form-label"><?= gf_h(gf_t('Phòng tập liên kết')) ?> <span class="text-secondary fw-normal"><?= gf_h(gf_t('(thêm mới: tối thiểu 1; sửa: có thể gỡ hết để xóa HLV)')) ?></span></label>
            <div id="trainerGymList" class="border rounded-3 p-3 bg-light" style="max-height:220px;overflow:auto;">
                <?php foreach ($gyms as $g): ?>
                    <label class="form-check d-flex align-items-center gap-2 mb-2">
                        <input class="form-check-input trainer-gym-check" type="checkbox" name="gym_ids[]" value="<?= (int)$g['id'] ?>">
                        <span class="form-check-label"><?= gf_h($g['name']) ?><?= (($g['status'] ?? '') === 'hidden') ? ' <span class="text-muted">' . gf_h(gf_t('(tạm ẩn)')) . '</span>' : '' ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="form-text"><?= gf_h(gf_t('Một HLV có thể gắn nhiều phòng tập. Gỡ hết liên kết rồi dùng nút xóa trên danh sách nếu cần xóa cứng.')) ?></div>
        </div>
        <div class="col-md-6" data-field><label class="form-label"><?= gf_h(gf_t('Số điện thoại *')) ?></label><input class="form-control admin-input" name="phone" id="trainerPhone" required pattern="^\+?[0-9\s.\-]{9,18}$" data-required-msg="<?= gf_h(gf_t('Vui lòng nhập số điện thoại.')) ?>" data-pattern-msg="<?= gf_h(gf_t('Số điện thoại không hợp lệ (9–14 chữ số).')) ?>" placeholder="<?= gf_h(gf_t('VD: 0901234567')) ?>"></div>
        <div class="col-md-6" data-field><label class="form-label"><?= gf_h(gf_t('Trạng thái *')) ?></label><select class="form-select admin-input" name="status" id="trainerStatus" required><option value="active"><?= gf_h(gf_t('Hoạt động')) ?></option><option value="hidden"><?= gf_h(gf_t('Tạm ẩn')) ?></option></select></div>
        <div class="col-12">
            <div class="form-check">
                <input type="hidden" name="is_featured" value="0">
                <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="trainerFeatured">
                <label class="form-check-label fw-semibold" for="trainerFeatured"><?= gf_h(gf_t('Nổi bật trên trang chủ')) ?></label>
            </div>
        </div>
        <div class="col-12" data-field><label class="form-label"><?= gf_h(gf_t('Tiểu sử *')) ?></label><textarea class="form-control admin-input" rows="3" name="bio" id="trainerBio" required data-required-msg="<?= gf_h(gf_t('Vui lòng nhập tiểu sử.')) ?>"></textarea></div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-4"><button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= gf_h(gf_t('Hủy')) ?></button><button type="submit" class="btn btn-primary" id="trainerSubmit"><?= gf_h(gf_t('Lưu thông tin')) ?></button></div>
</form></div></div></div></div>
<form method="get" class="admin-card admin-filter-bar p-3 mb-4 shadow-sm border">
    <div class="admin-filter-bar__grid admin-filter-bar__grid--5">
        <div class="admin-filter-bar__search position-relative">
            <i class="fa-solid fa-magnifying-glass text-secondary position-absolute" style="left: 14px; top: 50%; transform: translateY(-50%); font-size: 14px; pointer-events: none;"></i>
            <input class="form-control admin-input" name="q" value="<?= gf_h($_GET['q'] ?? '') ?>" placeholder="<?= gf_h(gf_t('Tìm kiếm huấn luyện viên...')) ?>" style="padding-left: 38px;" autocomplete="off">
        </div>
        <div class="admin-filter-bar__field">
            <select class="form-select admin-input" name="gym_id" aria-label="<?= gf_h(gf_t('Lọc theo phòng tập')) ?>">
                <option value=""><?= gf_h(gf_t('Tất cả phòng tập')) ?></option>
                <?php foreach ($gyms as $g): ?>
                    <option value="<?= (int)$g['id'] ?>" <?= $trainerGymFilter === (string)$g['id'] ? 'selected' : '' ?>><?= gf_h($g['name']) ?><?= (($g['status'] ?? '') === 'hidden') ? ' (' . gf_h(gf_t('tạm ẩn')) . ')' : '' ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-filter-bar__field">
            <select class="form-select admin-input" name="specialty_id" aria-label="<?= gf_h(gf_t('Lọc theo chuyên môn')) ?>">
                <option value=""><?= gf_h(gf_t('Tất cả chuyên môn')) ?></option>
                <?php foreach ($specs as $s): ?>
                    <option value="<?= (int)$s['id'] ?>" <?= (string)($_GET['specialty_id'] ?? '') === (string)$s['id'] ? 'selected' : '' ?>><?= gf_h($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="admin-filter-bar__field">
            <select class="form-select admin-input" name="status" aria-label="<?= gf_h(gf_t('Lọc theo trạng thái')) ?>">
                <option value="" <?= $trainerStatusFilter === '' ? 'selected' : '' ?>><?= gf_h(gf_t('Tất cả trạng thái')) ?></option>
                <option value="active" <?= $trainerStatusFilter === 'active' ? 'selected' : '' ?>><?= gf_h(gf_t('Hoạt động')) ?></option>
                <option value="hidden" <?= $trainerStatusFilter === 'hidden' ? 'selected' : '' ?>><?= gf_h(gf_t('Tạm ẩn')) ?></option>
                <option value="featured" <?= $trainerStatusFilter === 'featured' ? 'selected' : '' ?>><?= gf_h(gf_t('Nổi bật')) ?></option>
                <option value="not_featured" <?= $trainerStatusFilter === 'not_featured' ? 'selected' : '' ?>><?= gf_h(gf_t('Không nổi bật')) ?></option>
            </select>
        </div>
        <div class="admin-filter-bar__submit">
            <button type="submit" class="btn btn-filter d-inline-flex align-items-center justify-content-center gap-2 fw-medium">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><?= gf_h(gf_t('Tra cứu')) ?>
            </button>
        </div>
    </div>
</form>
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
        ['label' => 'THAO TÁC', 'width' => '170px', 'align' => 'right', 'th_class' => 'text-end pe-3'],
    ]
]);
?>
<?php
$gymById = [];
foreach ($gyms as $g) {
    $gymById[(int)$g['id']] = $g;
}
foreach ($rows as $r):
    $specNames = gf_ui_trainer_specialty_names($r, $specById);
    $linkedSpecIds = gf_ui_trainer_specialty_ids($r);
    $linkedGyms = gf_ui_trainer_linked_gyms($r, $gymById);
    $linkedGymIds = gf_ui_trainer_gym_ids($r);
    $gymNames = array_values(array_filter(array_map(static fn ($g) => $g['name'] ?? '', $linkedGyms)));
    $gymLinked = count($linkedGymIds);
    $deleteBlocked = $gymLinked > 0
        ? str_replace(
            '%d',
            (string) $gymLinked,
            gf_lang() === 'en'
                ? 'Cannot delete: %d gym(s) are still linked to this trainer. Unlink them first, or hide the trainer instead.'
                : 'Không thể xóa: còn %d phòng tập đang liên kết với HLV này. Hãy gỡ phòng tập khỏi hồ sơ hoặc dùng Tạm ẩn.'
        )
        : '';
?>
<tr>
    <td class="text-center text-muted fw-semibold"><?= (int)$r['id'] ?></td>
    <td>
        <div class="d-flex align-items-center gap-3">
            <img src="<?= gf_h(gf_avatar($r['avatar_url']??null,(int)$r['id'],$r['full_name'])) ?>" class="admin-avatar shadow-sm flex-shrink-0" alt="" onerror="this.onerror=null;this.src='<?= gf_h(gf_avatar_fallback($r['full_name'])) ?>';">
            <span class="admin-cell-title"><?= gf_h($r['full_name']) ?></span>
        </div>
    </td>
    <td>
        <div class="d-flex flex-wrap gap-1 align-items-center" style="max-width: 220px;">
            <?php if (empty($specNames)): ?>
                <span class="text-muted small">—</span>
            <?php else: ?>
                <?php foreach ($specNames as $specItem): ?>
                    <span class="admin-cat-badge <?= gf_admin_cat_badge((string) $specItem) ?>"><?= gf_h($specItem) ?></span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </td>
    <td>
        <?php if ($gymNames === []): ?>
            <span class="text-muted">—</span>
        <?php elseif (count($gymNames) === 1): ?>
            <span class="text-secondary"><?= gf_h($gymNames[0]) ?></span>
        <?php else: ?>
            <div class="small text-secondary">
                <?= gf_h($gymNames[0]) ?>
                <span class="text-muted">+<?= count($gymNames) - 1 ?> <?= gf_h(gf_t('phòng tập')) ?></span>
            </div>
        <?php endif; ?>
    </td>
    <td class="text-center admin-cell-rating text-nowrap align-middle">
        <span class="admin-cell-rating__value"><i class="fa-solid fa-star" aria-hidden="true"></i><?= gf_h((string)($r['avg_rating'] ?? 0)) ?></span>
    </td>
    <td class="text-center align-middle"><span class="admin-badge <?= $r['status']==='active'?'badge-success':'badge-neutral' ?>"><?= gf_h(gf_t($r['status']==='active'?'Hoạt động':'Tạm ẩn')) ?></span></td>
    <td class="text-end pe-3 text-nowrap">
        <button type="button" class="btn btn-action btn-action-view" data-bs-toggle="modal" data-bs-target="#trainerView<?= (int)$r['id'] ?>" title="<?= gf_h(gf_t('Xem chi tiết')) ?>" aria-label="<?= gf_h(gf_t('Xem chi tiết')) ?>"><i class="fa-solid fa-eye"></i></button>
        <button type="button" class="btn btn-action btn-action-edit" title="<?= gf_h(gf_t('Chỉnh sửa')) ?>" aria-label="<?= gf_h(gf_t('Chỉnh sửa')) ?>" data-bs-toggle="modal" data-bs-target="#trainerCreate" onclick="trainerModalEdit(this)" data-id="<?= (int)$r['id'] ?>" data-name="<?= gf_h($r['full_name']) ?>" data-specialty="<?= (int)($linkedSpecIds[0] ?? 0) ?>" data-specialties="<?= gf_h(implode(',', $linkedSpecIds)) ?>" data-gym="<?= (int)($linkedGymIds[0] ?? 0) ?>" data-gyms="<?= gf_h(implode(',', $linkedGymIds)) ?>" data-years="<?= (int)$r['years_experience'] ?>" data-phone="<?= gf_h($r['phone']??'') ?>" data-status="<?= gf_h($r['status']) ?>" data-featured="<?= (int)($r['is_featured'] ?? 0) ?>" data-bio="<?= gf_h($r['bio']??'') ?>" data-avatar="<?= gf_h($r['avatar_url']??'') ?>" data-avatar-display="<?= gf_h(gf_avatar($r['avatar_url']??null, (int)$r['id'], $r['full_name'])) ?>"><i class="fa-solid fa-pen"></i></button>
        <form method="post" class="d-inline" onsubmit="return handleTrainerStatusSubmit(this);">
            <?= gf_csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <input type="hidden" name="status" value="<?= $r['status']==='active'?'hidden':'active' ?>">
            <?php if ($r['status'] === 'active'): ?>
                <button type="submit" class="btn btn-action btn-action-toggle" title="<?= gf_h(gf_t('Tạm ẩn huấn luyện viên')) ?>" aria-label="<?= gf_h(gf_t('Tạm ẩn huấn luyện viên')) ?>"><i class="fa-solid fa-eye-slash"></i></button>
            <?php else: ?>
                <button type="submit" class="btn btn-action btn-action-activate" title="<?= gf_h(gf_t('Kích hoạt lại huấn luyện viên')) ?>" aria-label="<?= gf_h(gf_t('Kích hoạt lại huấn luyện viên')) ?>"><i class="fa-solid fa-rotate-left"></i></button>
            <?php endif; ?>
        </form>
        <?php if ($deleteBlocked !== ''): ?>
            <button type="button" class="btn btn-action btn-action-delete is-blocked" title="<?= gf_h($deleteBlocked) ?>" aria-label="<?= gf_h($deleteBlocked) ?>" data-delete-blocked="<?= gf_h($deleteBlocked) ?>"><i class="fa-solid fa-trash"></i></button>
        <?php else: ?>
            <form method="post" class="d-inline js-confirm-submit"
                  data-confirm-title="<?= gf_h(gf_t('Xóa huấn luyện viên?')) ?>"
                  data-confirm-message="<?= gf_h(gf_t('Xóa vĩnh viễn HLV này? Thao tác không hoàn tác.')) ?>"
                  data-confirm-label="<?= gf_h(gf_t('Xóa')) ?>"
                  data-confirm-danger="1">
                <?= gf_csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="btn btn-action btn-action-delete" title="<?= gf_h(gf_t('Xóa huấn luyện viên')) ?>" aria-label="<?= gf_h(gf_t('Xóa huấn luyện viên')) ?>"><i class="fa-solid fa-trash"></i></button>
            </form>
        <?php endif; ?>
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
    $specNames = gf_ui_trainer_specialty_names($r, $specById);
    $viewGyms = gf_ui_trainer_linked_gyms($r, $gymById);
    $viewGymNames = array_values(array_filter(array_map(static fn ($g) => $g['name'] ?? '', $viewGyms)));
?>
<div class="modal fade" id="trainerView<?= (int)$r['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold"><?= gf_h(gf_t('Chi tiết huấn luyện viên')) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <img src="<?= gf_h(gf_avatar($r['avatar_url']??null,(int)$r['id'],$r['full_name'])) ?>" class="rounded-circle shadow-sm object-fit-cover" style="width:64px;height:64px;" alt="" onerror="this.onerror=null;this.src='<?= gf_h(gf_avatar_fallback($r['full_name'])) ?>';">
                    <div>
                        <p class="fs-5 fw-bold mb-0"><?= gf_h($r['full_name']) ?></p>
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            <?php if (empty($specNames)): ?>
                                <span class="text-muted small"><?= gf_h(gf_t('Chưa thiết lập chuyên môn')) ?></span>
                            <?php else: ?>
                                <?php foreach ($specNames as $specItem): ?>
                                    <span class="admin-cat-badge <?= gf_admin_cat_badge((string) $specItem) ?>"><?= gf_h($specItem) ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="bg-light p-3 rounded-3 mb-3 small">
                    <div class="row g-2">
                        <div class="col-12"><strong><?= gf_h(gf_t('Phòng tập liên kết:')) ?></strong>
                            <?php if ($viewGymNames === []): ?>
                                <span class="text-muted">—</span>
                            <?php else: ?>
                                <?= gf_h(implode(', ', $viewGymNames)) ?>
                            <?php endif; ?>
                        </div>
                        <div class="col-6"><strong><?= gf_h(gf_t('Kinh nghiệm:')) ?></strong> <?= gf_h(gf_t((int) $r['years_experience'] . ' năm')) ?></div>
                        <div class="col-6"><strong><?= gf_h(gf_t('Số điện thoại:')) ?></strong> <?= gf_h($r['phone'] ?? '—') ?></div>
                        <div class="col-6"><strong><?= gf_h(gf_t('Đánh giá:')) ?></strong> <span class="text-warning"><i class="fa-solid fa-star"></i> <?= gf_h((string)$r['avg_rating']) ?> / 5</span></div>
                    </div>
                </div>
                <div>
                    <h6 class="fw-bold text-secondary small text-uppercase mb-2"><?= gf_h(gf_t('Tiểu sử giới thiệu')) ?></h6>
                    <div class="p-3 bg-white border rounded-3 small text-secondary">
                        <?= nl2br(gf_h($r['bio'] ?: 'Chưa có thông tin giới thiệu.')) ?>
                    </div>
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
<script>
const uploadApiUrl = '<?= gf_url('api/uploads/images') ?>';
const csrfToken = '<?= gf_csrf_token() ?>';
const defaultTrainerPlaceholder = 'https://ui-avatars.com/api/?name=HLV&background=00c9b1&color=fff';

let originalTrainerAvatarUrl = '';
let originalTrainerAvatarDisplay = '';
let activeTrainerObjectUrl = null;

function revokeTrainerObjectUrl() {
    if (activeTrainerObjectUrl) {
        URL.revokeObjectURL(activeTrainerObjectUrl);
        activeTrainerObjectUrl = null;
    }
}

function setTrainerGymChecks(selectedIds) {
    const selected = new Set((selectedIds || []).map(String));
    document.querySelectorAll('.trainer-gym-check').forEach((el) => {
        el.checked = selected.has(String(el.value));
    });
}

function getSelectedTrainerGymIds() {
    return Array.from(document.querySelectorAll('.trainer-gym-check:checked')).map((el) => el.value);
}

function setTrainerSpecialtyChecks(selectedIds) {
    const selected = new Set((selectedIds || []).map(String));
    document.querySelectorAll('.trainer-specialty-check').forEach((el) => {
        el.checked = selected.has(String(el.value));
    });
}

function getSelectedTrainerSpecialtyIds() {
    return Array.from(document.querySelectorAll('.trainer-specialty-check:checked')).map((el) => el.value);
}

function clearTrainerFieldErrors() {
    const form = document.getElementById('trainerForm');
    form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
    form.querySelectorAll('.gf-field-error').forEach((el) => {
        el.hidden = true;
        el.textContent = '';
    });
    document.getElementById('trainerAvatarBox')?.classList.remove('border-danger');
    document.getElementById('trainerSpecialtyList')?.classList.remove('border-danger');
    document.getElementById('trainerGymList')?.classList.remove('border-danger');
}

function showTrainerGroupError(wrapId, boxId, msg) {
    const wrap = document.getElementById(wrapId);
    if (!wrap) return;
    let err = wrap.querySelector(':scope > .gf-field-error');
    if (!err) {
        err = document.createElement('div');
        err.className = 'gf-field-error';
        wrap.appendChild(err);
    }
    err.textContent = msg;
    err.hidden = false;
    document.getElementById(boxId)?.classList.add('border-danger');
}

function validateTrainerFormFields() {
    clearTrainerFieldErrors();
    const form = document.getElementById('trainerForm');
    let firstInvalid = null;
    const messages = [];

    const markInput = (el, msg) => {
        if (!el) return;
        el.classList.add('is-invalid');
        const wrap = el.closest('[data-field]') || el.parentElement;
        if (wrap) {
            let err = wrap.querySelector(':scope > .gf-field-error');
            if (!err) {
                err = document.createElement('div');
                err.className = 'gf-field-error';
                wrap.appendChild(err);
            }
            err.textContent = msg;
            err.hidden = false;
        }
        if (!firstInvalid) firstInvalid = el;
        messages.push(msg);
    };

    const nameEl = document.getElementById('trainerName');
    if (!String(nameEl.value || '').trim()) {
        markInput(nameEl, gfT('Vui lòng nhập họ và tên HLV.'));
    }

    const yearsEl = document.getElementById('trainerYears');
    const yearsVal = String(yearsEl.value || '').trim();
    if (yearsVal === '' || Number.isNaN(Number(yearsVal)) || Number(yearsVal) < 0 || Number(yearsVal) > 60) {
        markInput(yearsEl, gfT('Vui lòng nhập số năm kinh nghiệm.'));
    }

    if (getSelectedTrainerSpecialtyIds().length === 0) {
        const msg = gfT('Vui lòng chọn ít nhất một chuyên môn.');
        showTrainerGroupError('trainerSpecialtyField', 'trainerSpecialtyList', msg);
        if (!firstInvalid) firstInvalid = document.querySelector('.trainer-specialty-check');
        messages.push(msg);
    }

    const isCreate = !document.getElementById('trainerId').value;
    if (isCreate && getSelectedTrainerGymIds().length === 0) {
        const msg = gfT('Vui lòng chọn ít nhất một phòng tập liên kết.');
        showTrainerGroupError('trainerGymField', 'trainerGymList', msg);
        if (!firstInvalid) firstInvalid = document.querySelector('.trainer-gym-check');
        messages.push(msg);
    }

    const phoneEl = document.getElementById('trainerPhone');
    const phone = String(phoneEl.value || '').replace(/[\s.\-]/g, '');
    if (!phone) {
        markInput(phoneEl, gfT('Vui lòng nhập số điện thoại.'));
    } else if (!/^\+?[0-9]{9,14}$/.test(phone)) {
        markInput(phoneEl, gfT('Số điện thoại không hợp lệ (9–14 chữ số).'));
    }

    const bioEl = document.getElementById('trainerBio');
    if (!String(bioEl.value || '').trim()) {
        markInput(bioEl, gfT('Vui lòng nhập tiểu sử.'));
    }

    const avatarUrl = String(document.getElementById('trainerAvatarUrl').value || '').trim();
    const fileInput = document.getElementById('trainerAvatarFile');
    const hasNewFile = !!(fileInput.files && fileInput.files[0]);
    const removed = document.getElementById('trainerRemoveAvatar').value === '1';
    if ((isCreate && !avatarUrl && !hasNewFile) || (!isCreate && removed && !hasNewFile)) {
        const msg = gfT('Vui lòng chọn ảnh đại diện huấn luyện viên.');
        showTrainerGroupError('trainerAvatarField', 'trainerAvatarBox', msg);
        if (!firstInvalid) firstInvalid = fileInput;
        messages.push(msg);
    }

    if (typeof window.gfFormValidate?.validateForm === 'function') {
        const bad = window.gfFormValidate.validateForm(form);
        if (bad && !firstInvalid) firstInvalid = bad;
    }

    const hasVisibleFieldError = !!form.querySelector('.gf-field-error:not([hidden])');
    return { ok: messages.length === 0 && !firstInvalid && !hasVisibleFieldError, firstInvalid, messages };
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
    document.getElementById('trainerModalTitle').textContent=gfT('Thêm huấn luyện viên mới');
    document.getElementById('trainerSubmit').textContent=gfT('Thêm huấn luyện viên');
    document.getElementById('trainerSubmit').disabled = false;
    setTrainerGymChecks([]);
    setTrainerSpecialtyChecks([]);
    document.getElementById('trainerFeatured').checked = false;
    originalTrainerAvatarUrl = '';
    originalTrainerAvatarDisplay = defaultTrainerPlaceholder;
    clearTrainerFieldErrors();
}

function trainerModalEdit(button){
    revokeTrainerObjectUrl();
    const f=document.getElementById('trainerForm');
    f.reset();
    document.getElementById('trainerId').value=button.dataset.id;
    document.getElementById('trainerName').value=button.dataset.name;
    const specialtyIds = (button.dataset.specialties || button.dataset.specialty || '')
        .split(',')
        .map((v) => v.trim())
        .filter(Boolean);
    setTrainerSpecialtyChecks(specialtyIds.length ? specialtyIds : (button.dataset.specialty ? [button.dataset.specialty] : []));
    const gymIds = (button.dataset.gyms || button.dataset.gym || '')
        .split(',')
        .map((v) => v.trim())
        .filter(Boolean);
    setTrainerGymChecks(gymIds.length ? gymIds : (button.dataset.gym ? [button.dataset.gym] : []));
    document.getElementById('trainerYears').value=button.dataset.years;
    document.getElementById('trainerPhone').value=button.dataset.phone;
    document.getElementById('trainerStatus').value=button.dataset.status;
    document.getElementById('trainerFeatured').checked = button.dataset.featured === '1';
    document.getElementById('trainerBio').value=button.dataset.bio;
    document.getElementById('trainer-error-alert').classList.add('d-none');
    document.getElementById('trainerSubmit').disabled = false;

    originalTrainerAvatarUrl = button.dataset.avatar || '';
    originalTrainerAvatarDisplay = button.dataset.avatarDisplay || defaultTrainerPlaceholder;
    document.getElementById('trainerAvatarUrl').value = originalTrainerAvatarUrl;
    document.getElementById('trainerRemoveAvatar').value = '0';
    document.getElementById('trainerAvatarPreview').src = originalTrainerAvatarDisplay;
    document.getElementById('trainerAvatarCancelBtn').classList.add('d-none');

    document.getElementById('trainerModalTitle').textContent=gfT('Sửa huấn luyện viên:') + ' ' + button.dataset.name;
    document.getElementById('trainerSubmit').textContent=gfT('Lưu thông tin');
    clearTrainerFieldErrors();
}

function previewTrainerAvatar(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        typeof window.gfToast === 'function' ? window.gfToast(gfT('File không hợp lệ. Vui lòng chọn ảnh JPG, PNG hoặc WEBP.'), 'warning') : null;
        input.value = '';
        return;
    }
    if (file.size > 5 * 1024 * 1024) {
        typeof window.gfToast === 'function' ? window.gfToast(gfT('Dung lượng ảnh vượt quá 5 MB.'), 'warning') : null;
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
    e.stopPropagation();
    const saveBtn = document.getElementById('trainerSubmit');
    const errAlert = document.getElementById('trainer-error-alert');
    errAlert.classList.add('d-none');
    errAlert.textContent = '';

    const check = validateTrainerFormFields();
    if (!check.ok) {
        const msg = check.messages[0] || gfT('Vui lòng điền đầy đủ các trường bắt buộc.');
        errAlert.textContent = msg;
        errAlert.classList.remove('d-none');
        check.firstInvalid?.focus?.();
        return false;
    }

    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>' + gfT('Đang tải ảnh & lưu...');

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
                throw new Error(data.error?.message || gfT('Tải ảnh đại diện thất bại.'));
            }
            document.getElementById('trainerAvatarUrl').value = data.data.url;
            fileInput.value = ''; // Tránh submit file qua standard post lần 2
        }

        // Re-check avatar after optional upload (create must have URL).
        const isCreate = !document.getElementById('trainerId').value;
        if (isCreate && !String(document.getElementById('trainerAvatarUrl').value || '').trim()) {
            throw new Error(gfT('Vui lòng chọn ảnh đại diện huấn luyện viên.'));
        }

        document.getElementById('trainerForm').submit();
    } catch (err) {
        saveBtn.disabled = false;
        saveBtn.textContent = document.getElementById('trainerId').value ? gfT('Lưu thông tin') : gfT('Thêm huấn luyện viên');
        errAlert.textContent = err.message || gfT('Đã xảy ra lỗi khi lưu thông tin.');
        errAlert.classList.remove('d-none');
    }
    return false;
}

function handleTrainerStatusSubmit(form) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
        btn.disabled = true;
    }
    return true;
}

window.addEventListener('beforeunload', revokeTrainerObjectUrl);

<?php if (!empty($reopenTrainerModal) && $err !== ''): ?>
document.addEventListener('DOMContentLoaded', () => {
    const serverMsg = <?= json_encode((string) $err, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    const isEdit = <?= json_encode(!empty($_POST['id'])) ?>;
    if (!isEdit && typeof trainerModalCreate === 'function') {
        trainerModalCreate();
        const modalEl = document.getElementById('trainerCreate');
        if (modalEl && window.bootstrap?.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    } else {
        const modalEl = document.getElementById('trainerCreate');
        if (modalEl && window.bootstrap?.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    }
    if (typeof validateTrainerFormFields === 'function') {
        validateTrainerFormFields();
    }
    const errAlert = document.getElementById('trainer-error-alert');
    if (errAlert) {
        errAlert.textContent = serverMsg;
        errAlert.classList.remove('d-none');
    }
});
<?php endif; ?>
</script>
<script>
document.querySelectorAll('[data-delete-blocked]').forEach((button) => {
    button.addEventListener('click', () => {
        const message = button.getAttribute('data-delete-blocked') || '';
        if (typeof window.gfToast === 'function') {
            window.gfToast(message, 'warning');
        }
    });
});
</script>
</body></html>

