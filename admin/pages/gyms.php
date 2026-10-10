<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
gf_require_admin();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['id']) && isset($_POST['name'])) {
        $data = $_POST;
        unset($data['_csrf'], $data['id']);
        $data['category_ids'] = array_map('intval', $data['category_ids'] ?? []);
        $data['amenity_ids'] = array_map('intval', $data['amenity_ids'] ?? []);
        $data['price_min'] = (int) ($data['price_min'] ?? 0);
        $data['price_max'] = (int) ($data['price_max'] ?? 0);
        $result = gf_admin_save_gym($data, (int) $_POST['id']);
        if (!empty($result['ok'])) gf_redirect(gf_url('admin/pages/gyms.php?updated=1'));
        $error = $result['error'] ?? 'Không thể cập nhật phòng tập.';
    } elseif (isset($_POST['id'], $_POST['status'])) {
        gf_set_entity_status('gyms', (int) $_POST['id'], $_POST['status'] === 'hidden' ? 'hidden' : 'active');
        gf_redirect(gf_url('admin/pages/gyms.php'));
    }
    $data = $_POST;
    unset($data['_csrf'], $data['id']);
    $data['category_ids'] = array_map('intval', $data['category_ids'] ?? []);
    $data['amenity_ids'] = array_map('intval', $data['amenity_ids'] ?? []);
    $data['price_min'] = (int) ($data['price_min'] ?? 0);
    $data['price_max'] = (int) ($data['price_max'] ?? 0);
    $result = gf_admin_save_gym($data, null);
    if (!empty($result['ok'])) {
        gf_redirect(gf_url('admin/pages/gyms.php?created=1'));
    }
    $error = $result['error'] ?? 'Không thể lưu phòng tập.';
}
$districts = gf_list_districts();
$districtNames = [];
foreach ($districts as $district) { $districtNames[(int)$district['id']] = $district['name']; }
$gymPage = max(1,(int)($_GET['page']??1));
$gymResult = gf_admin_gyms_paged([
    'limit' => 10,
    'page' => $gymPage,
    'sort' => 'oldest',
    'q' => $_GET['q'] ?? '',
    'status' => $_GET['status'] ?? '',
    'district_id' => $_GET['district_id'] ?? '',
]);
$rows = $gymResult['items'];
$gymCategoryNames = $gymCategoryIds = $gymAmenityIds = $gymDescriptions = [];
$categoryQuery = gf_pdo()->prepare('SELECT c.id,c.name FROM gym_categories gc JOIN categories c ON c.id=gc.category_id WHERE gc.gym_id=? ORDER BY c.name');
$amenityQuery = gf_pdo()->prepare('SELECT amenity_id FROM gym_amenities WHERE gym_id=?');
$descriptionQuery = gf_pdo()->prepare('SELECT description FROM gyms WHERE id=?');
foreach ($rows as $gymRow) {
    $categoryQuery->execute([(int)$gymRow['id']]);
    $gymCategories = $categoryQuery->fetchAll();
    $gymCategoryNames[(int)$gymRow['id']] = array_column($gymCategories, 'name');
    $gymCategoryIds[(int)$gymRow['id']] = array_map('intval', array_column($gymCategories, 'id'));
    $amenityQuery->execute([(int)$gymRow['id']]);
    $gymAmenityIds[(int)$gymRow['id']] = array_map('intval', array_column($amenityQuery->fetchAll(), 'amenity_id'));
    $descriptionQuery->execute([(int)$gymRow['id']]);
    $gymDescriptions[(int)$gymRow['id']] = (string)($descriptionQuery->fetchColumn() ?: '');
}
$categories = gf_list_categories(false);
$amenities = gf_list_amenities();
$pageTitle = 'Phòng tập - Admin GYMFINDER';
$extraCss = ['admin/assets/admin.css'];
$adminActive = 'gyms';
require dirname(__DIR__, 2) . '/includes/layout_head.php';
require dirname(__DIR__, 2) . '/includes/admin_layout.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0">Quản lý phòng tập</h2>
    <button class="btn btn-primary px-3 rounded-3" onclick="gymModalCreate()"><i class="fa-solid fa-plus me-2"></i>Thêm mới</button>
</div>
<?php if (!empty($_GET['created'])): ?><div class="alert alert-success">Đã thêm phòng tập.</div><?php endif; ?>
<?php if (!empty($_GET['updated'])): ?><div class="alert alert-success">Đã cập nhật phòng tập.</div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger"><?= gf_h($error) ?></div><?php endif; ?>
<form method="get" class="admin-card p-3 mb-4"><div class="row g-3"><div class="col-md-5"><input class="form-control admin-input" name="q" value="<?= gf_h($_GET['q'] ?? '') ?>" placeholder="Tìm kiếm phòng tập..."></div><div class="col-md-3"><select class="form-select admin-input" name="district_id"><option value="">Tất cả khu vực</option><?php foreach ($districts as $d): ?><option value="<?= (int)$d['id'] ?>" <?= (string)($_GET['district_id'] ?? '') === (string)$d['id'] ? 'selected' : '' ?>><?= gf_h($d['name']) ?></option><?php endforeach; ?></select></div><div class="col-md-2"><select class="form-select admin-input" name="status"><option value="">Tất cả trạng thái</option><option value="active" <?= ($_GET['status'] ?? '') === 'active' ? 'selected' : '' ?>>Hoạt động</option><option value="hidden" <?= ($_GET['status'] ?? '') === 'hidden' ? 'selected' : '' ?>>Tạm ẩn</option></select></div><div class="col-md-2"><button class="btn btn-light border w-100">Lọc</button></div></div></form>
<div class="admin-card overflow-hidden"><div class="table-responsive"><table class="table admin-table admin-gyms-table mb-0"><colgroup><col style="width:5%"><col style="width:24%"><col style="width:10%"><col style="width:14%"><col style="width:16%"><col style="width:8%"><col style="width:11%"><col style="width:12%"></colgroup><thead><tr><th>ID</th><th>PHÒNG TẬP</th><th>KHU VỰC</th><th>DANH MỤC</th><th>GIÁ</th><th>ĐÁNH GIÁ</th><th>TRẠNG THÁI</th><th class="text-end">THAO TÁC</th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr>
<td><?= (int)$r['id'] ?></td><td><div class="d-flex align-items-center gap-3">
<?php if (!empty($r['cover_image_url'])): ?><img src="<?= gf_h($r['cover_image_url']) ?>" width="56" height="56" style="object-fit:cover;aspect-ratio:1/1" class="rounded-3" alt=""><?php else: ?><span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-light text-secondary" style="width:56px;height:56px"><i class="fa-solid fa-dumbbell"></i></span><?php endif; ?>
<div><strong><?= gf_h($r['name']) ?></strong><div class="small text-secondary"><?= gf_h($r['address']) ?></div></div></div></td>
<td><?= gf_h($districtNames[(int)$r['district_id']] ?? '') ?></td><td><?php foreach ($gymCategoryNames[(int)$r['id']] ?? [] as $categoryName): ?><span class="badge badge-neutral me-1"><?= gf_h($categoryName) ?></span><?php endforeach; ?></td>
<td class="text-primary fw-semibold"><?= gf_h(gf_format_price((int)$r['price_min'], (int)$r['price_max'])) ?></td><td class="text-warning"><i class="fa-solid fa-star"></i> <?= gf_h((string)$r['avg_rating']) ?></td>
<td><span class="admin-badge <?= $r['status'] === 'active' ? 'badge-success' : 'badge-neutral' ?>"><?= $r['status'] === 'active' ? 'Hoạt động' : 'Tạm ẩn' ?></span></td>
<td class="text-end"><div class="d-inline-flex gap-1">
<button type="button" class="btn btn-action btn-action-view" data-bs-toggle="modal" data-bs-target="#gymView<?= (int)$r['id'] ?>" title="Chi tiết"><i class="fa-solid fa-eye"></i></button>
<button type="button" class="btn btn-action btn-action-edit" data-id="<?= (int)$r['id'] ?>" data-name="<?= gf_h($r['name']) ?>" data-address="<?= gf_h($r['address']) ?>" data-district="<?= (int)$r['district_id'] ?>" data-min="<?= (int)$r['price_min'] ?>" data-max="<?= (int)$r['price_max'] ?>" data-description="<?= gf_h($gymDescriptions[(int)$r['id']] ?? '') ?>" data-categories="<?= gf_h(implode(',', $gymCategoryIds[(int)$r['id']] ?? [])) ?>" data-amenities="<?= gf_h(implode(',', $gymAmenityIds[(int)$r['id']] ?? [])) ?>" onclick="gymModalEdit(this)" title="Chỉnh sửa"><i class="fa-solid fa-pen"></i></button>
<form method="post"><?= gf_csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="status" value="<?= $r['status'] === 'active' ? 'hidden' : 'active' ?>"><button class="btn btn-action <?= $r['status']==='active'?'btn-action-delete':'btn-action-edit' ?>" title="<?= $r['status']==='active'?'Ẩn':'Hiện' ?>"><i class="fa-solid <?= $r['status']==='active'?'fa-trash':'fa-eye' ?>"></i></button></form>
</div></td></tr>
<div class="modal fade" id="gymView<?= (int)$r['id'] ?>" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 rounded-4"><div class="modal-header border-0"><h5 class="modal-title fw-bold">Chi tiết phòng tập</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><h5><?= gf_h($r['name']) ?></h5><p class="text-secondary mb-2"><?= gf_h($r['address']) ?>, <?= gf_h($districtNames[(int)$r['district_id']] ?? '') ?></p><p><?= nl2br(gf_h($gymDescriptions[(int)$r['id']] ?? '')) ?></p><p class="mb-1"><strong>Giá:</strong> <?= gf_h(gf_format_price((int)$r['price_min'], (int)$r['price_max'])) ?></p><p class="mb-0"><strong>Danh mục:</strong> <?= gf_h(implode(', ', $gymCategoryNames[(int)$r['id']] ?? [])) ?></p></div></div></div></div>
<?php endforeach; ?>
</tbody></table></div><div class="p-3 border-top d-flex justify-content-between align-items-center"><span class="small text-secondary">Hiển thị <?= $gymResult['total'] ? (($gymPage-1)*$gymResult['limit']+1) : 0 ?>–<?= min($gymPage*$gymResult['limit'],$gymResult['total']) ?> của <?= (int)$gymResult['total'] ?> phòng tập</span><div class="d-flex gap-2"><?php if($gymPage>1): ?><a class="btn btn-sm btn-light border" href="?<?= http_build_query(array_merge($_GET,['page'=>$gymPage-1])) ?>">← Trước</a><?php endif; ?><?php if($gymPage*$gymResult['limit']<$gymResult['total']): ?><a class="btn btn-sm btn-light border" href="?<?= http_build_query(array_merge($_GET,['page'=>$gymPage+1])) ?>">Sau →</a><?php endif; ?></div></div></div>
<div class="modal fade" id="gymEditor" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content border-0 rounded-4"><form method="post"><?= gf_csrf_field() ?><input type="hidden" name="id" id="gym-id"><div class="modal-header border-0 px-4 pt-4"><h5 class="modal-title fw-bold" id="gym-editor-title">Thêm phòng tập</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body px-4"><div class="row g-3">
<div class="col-md-6"><label class="form-label">Tên phòng tập *</label><input class="form-control admin-input" name="name" id="gym-name" required></div><div class="col-md-6"><label class="form-label">Địa chỉ *</label><input class="form-control admin-input" name="address" id="gym-address" required></div>
<div class="col-md-4"><label class="form-label">Khu vực *</label><select class="form-select admin-input" name="district_id" id="gym-district" required><?php foreach ($districts as $d): ?><option value="<?= (int)$d['id'] ?>"><?= gf_h($d['name']) ?></option><?php endforeach; ?></select></div><div class="col-md-4"><label class="form-label">Giá từ</label><input class="form-control admin-input" type="number" min="0" name="price_min" id="gym-min" value="200000"></div><div class="col-md-4"><label class="form-label">Giá đến</label><input class="form-control admin-input" type="number" min="0" name="price_max" id="gym-max" value="800000"></div>
<div class="col-12"><label class="form-label">Mô tả</label><textarea class="form-control admin-input" name="description" id="gym-description" rows="3"></textarea></div>
<div class="col-md-6"><label class="form-label fw-semibold">Danh mục</label><div><?php foreach ($categories as $c): ?><label class="form-check"><input class="form-check-input gym-category" type="checkbox" name="category_ids[]" value="<?= (int)$c['id'] ?>"><span class="form-check-label"><?= gf_h($c['name']) ?></span></label><?php endforeach; ?></div></div>
<div class="col-md-6"><label class="form-label fw-semibold">Tiện ích</label><div><?php foreach ($amenities as $a): ?><label class="form-check"><input class="form-check-input gym-amenity" type="checkbox" name="amenity_ids[]" value="<?= (int)$a['id'] ?>"><span class="form-check-label"><?= gf_h($a['name']) ?></span></label><?php endforeach; ?></div></div>
</div></div><div class="modal-footer border-0 px-4 pb-4"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button><button class="btn btn-primary" id="gym-save">Lưu thông tin</button></div></form></div></div></div>
<script>
function gymModalCreate(){const f=document.getElementById('gymEditor');f.querySelector('form').reset();document.getElementById('gym-id').value='';document.getElementById('gym-editor-title').textContent='Thêm phòng tập';document.getElementById('gym-save').textContent='Thêm phòng tập';bootstrap.Modal.getOrCreateInstance(f).show();}
function gymModalEdit(b){const f=document.getElementById('gymEditor');f.querySelector('form').reset();document.getElementById('gym-id').value=b.dataset.id;document.getElementById('gym-name').value=b.dataset.name;document.getElementById('gym-address').value=b.dataset.address;document.getElementById('gym-district').value=b.dataset.district;document.getElementById('gym-min').value=b.dataset.min;document.getElementById('gym-max').value=b.dataset.max;document.getElementById('gym-description').value=b.dataset.description;const cats=b.dataset.categories.split(',').filter(Boolean),amens=b.dataset.amenities.split(',').filter(Boolean);f.querySelectorAll('.gym-category').forEach(x=>x.checked=cats.includes(x.value));f.querySelectorAll('.gym-amenity').forEach(x=>x.checked=amens.includes(x.value));document.getElementById('gym-editor-title').textContent='Chỉnh sửa phòng tập';document.getElementById('gym-save').textContent='Lưu thông tin';bootstrap.Modal.getOrCreateInstance(f).show();}
</script>
</main></div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></body></html>
