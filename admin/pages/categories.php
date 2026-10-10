<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
gf_require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id']) && isset($_POST['name'])) {
    $data = ['name' => trim((string)$_POST['name']), 'description' => trim((string)($_POST['description'] ?? '')), 'is_active' => isset($_POST['is_active']) ? 1 : 0];
    gf_admin_save_category($data, (int)$_POST['id']);
    gf_redirect(gf_url('admin/pages/categories.php?updated=1'));
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
    $data = $_POST;
    unset($data['_csrf']);
    gf_admin_save_category($data, null);
    gf_redirect(gf_url('admin/pages/categories.php?created=1'));
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $on = (string)($_POST['is_active'] ?? '0') === '1';
    gf_admin_save_category(['is_active' => $on ? 1 : 0], (int)$_POST['id']);
    gf_redirect(gf_url('admin/pages/categories.php'));
}
$rows = gf_admin_categories_paged(['limit'=>100,'sort'=>'oldest','q'=>$_GET['q']??''])['items'];
$pageTitle = 'Quản lý danh mục';
$extraCss = ['admin/assets/admin.css'];
$adminActive = 'categories';
require dirname(__DIR__, 2) . '/includes/layout_head.php';
require dirname(__DIR__, 2) . '/includes/admin_layout.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4"><h2 class="fw-bold mb-0">Quản lý danh mục</h2><button class="btn btn-primary px-3 rounded-3" data-bs-toggle="modal" data-bs-target="#categoryCreate"><i class="fa-solid fa-plus me-2"></i>Thêm mới</button></div>
<?php if (!empty($_GET['created'])): ?><div class="alert alert-success">Đã thêm danh mục.</div><?php endif; ?>
<?php if (!empty($_GET['updated'])): ?><div class="alert alert-success">Đã cập nhật danh mục.</div><?php endif; ?>
<div class="modal fade" id="categoryCreate" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 rounded-4"><form method="post"><?= gf_csrf_field() ?><div class="modal-header border-0"><h5 class="modal-title fw-bold">Thêm danh mục</h5><button class="btn-close" data-bs-dismiss="modal" type="button"></button></div><div class="modal-body">
<label class="form-label">Tên danh mục</label><input class="form-control admin-input mb-3" name="name" placeholder="Tên danh mục mới" required><label class="form-label">Mô tả</label><textarea class="form-control admin-input" name="description" rows="3"></textarea></div><div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button><button class="btn btn-primary">Thêm</button></div></form></div></div></div>
<form method="get" class="admin-card p-3 mb-4"><div class="position-relative"><i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary"></i><input class="form-control admin-input ps-5" name="q" value="<?= gf_h($_GET['q']??'') ?>" placeholder="Tìm kiếm danh mục..."></div></form>
<div class="admin-card overflow-hidden"><div class="table-responsive"><table class="table admin-table admin-categories-table mb-0">
<colgroup><col style="width:7%"><col style="width:20%"><col style="width:31%"><col style="width:15%"><col style="width:14%"><col style="width:13%"></colgroup>
<thead><tr><th>ID</th><th>TÊN DANH MỤC</th><th>MÔ TẢ</th><th>SỐ PHÒNG TẬP</th><th>TRẠNG THÁI</th><th>THAO TÁC</th></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr>
    <td><?= (int)$r['id'] ?></td>
    <td><?= gf_h($r['name']) ?></td>
    <td><?= gf_h($r['description'] ?? '') ?></td>
    <td><span class="badge-neutral"><?php $countQuery=gf_pdo()->prepare('SELECT COUNT(*) FROM gym_categories WHERE category_id=?');$countQuery->execute([(int)$r['id']]);echo (int)$countQuery->fetchColumn(); ?> phòng</span></td>
    <td><span class="admin-badge <?= $r['is_active'] ? 'badge-success' : 'badge-neutral' ?>"><?= $r['is_active'] ? 'Hoạt động' : 'Tạm ẩn' ?></span></td>
    <td><div class="d-inline-flex gap-1"><button type="button" class="btn btn-action btn-action-edit" data-bs-toggle="modal" data-bs-target="#categoryEdit<?= (int)$r['id'] ?>" title="Chỉnh sửa"><i class="fa-solid fa-pen"></i></button><form method="post"><?= gf_csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="is_active" value="<?= $r['is_active'] ? '0' : '1' ?>"><button class="btn btn-action <?= $r['is_active']?'btn-action-delete':'btn-action-edit' ?>" title="<?= $r['is_active']?'Ẩn':'Hiện' ?>"><i class="fa-solid <?= $r['is_active']?'fa-trash':'fa-eye' ?>"></i></button></form></div>
    <div class="modal fade" id="categoryEdit<?= (int)$r['id'] ?>" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 rounded-4"><form method="post"><?= gf_csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><div class="modal-header border-0"><h5 class="modal-title fw-bold">Sửa danh mục</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Tên danh mục</label><input class="form-control admin-input mb-3" name="name" value="<?= gf_h($r['name']) ?>" required><label class="form-label">Mô tả</label><textarea class="form-control admin-input mb-3" name="description" rows="3"><?= gf_h($r['description'] ?? '') ?></textarea><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" id="category-active-<?= (int)$r['id'] ?>" value="1" <?= $r['is_active'] ? 'checked' : '' ?>><label class="form-check-label" for="category-active-<?= (int)$r['id'] ?>">Đang hoạt động</label></div></div><div class="modal-footer border-0"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button><button class="btn btn-primary">Lưu thông tin</button></div></form></div></div></div></td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
</main></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
