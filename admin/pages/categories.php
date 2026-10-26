<?php
/**
 * Quản lý tất cả danh mục hệ thống trong một trang (theo tab).
 */
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
require_once dirname(__DIR__) . '/components/table.php';
gf_require_admin();

$tabs = [
    'categories'  => ['label' => 'Loại phòng tập', 'icon' => 'fa-tags', 'table' => 'categories'],
    'specialties' => ['label' => 'Chuyên môn huấn luyện viên', 'icon' => 'fa-person-running', 'table' => 'specialties'],
    'amenities'   => ['label' => 'Tiện ích', 'icon' => 'fa-shower', 'table' => 'amenities'],
    'districts'   => ['label' => 'Khu vực', 'icon' => 'fa-map-location-dot', 'table' => 'districts'],
];
$tab = (string) ($_GET['tab'] ?? 'categories');
if (!isset($tabs[$tab])) {
    $tab = 'categories';
}
$table = $tabs[$tab]['table'];
$err = '';

function gf_admin_catalog_redirect(string $tab, string $flag = ''): void
{
    $url = 'admin/pages/categories.php?tab=' . urlencode($tab);
    if ($flag !== '') {
        $url .= '&' . $flag . '=1';
    }
    gf_redirect(gf_url($url));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    gf_require_csrf();
    $action = (string) ($_POST['action'] ?? 'save');
    $id = (int) ($_POST['id'] ?? 0);

    if ($tab === 'categories') {
        try {
            if ($action === 'toggle' && $id > 0) {
                $on = (string) ($_POST['is_active'] ?? '0') === '1';
                gf_admin_save_category(['is_active' => $on ? 1 : 0], $id);
                gf_admin_catalog_redirect('categories');
            }
            $name = trim((string) ($_POST['name'] ?? ''));
            $description = trim((string) ($_POST['description'] ?? ''));
            if ($name === '' || mb_strlen($name) > 100) {
                throw new RuntimeException('Tên loại phòng tập phải từ 1 đến 100 ký tự.');
            }
            if (mb_strlen($description) > 5000) {
                throw new RuntimeException('Mô tả tối đa 5000 ký tự.');
            }
            $dup = gf_pdo()->prepare('SELECT id FROM categories WHERE name=? AND (? = 0 OR id<>?) LIMIT 1');
            $dup->execute([$name, $id, $id]);
            if ($dup->fetchColumn()) {
                throw new RuntimeException('Tên danh mục đã tồn tại. Vui lòng nhập tên khác.');
            }
            if ($id > 0) {
                gf_admin_save_category([
                    'name' => $name,
                    'description' => $description,
                    'is_active' => isset($_POST['is_active']) ? 1 : 0,
                ], $id);
                gf_admin_catalog_redirect('categories', 'updated');
            }
            gf_admin_save_category([
                'name' => $name,
                'description' => $description,
                'is_active' => 1,
            ], null);
            gf_admin_catalog_redirect('categories', 'created');
        } catch (Throwable $e) {
            $err = $e->getMessage() !== '' ? $e->getMessage() : 'Không thể lưu loại phòng tập.';
        }
    } else {
        $name = trim((string) ($_POST['name'] ?? ''));
        $icon = trim((string) ($_POST['icon'] ?? ''));
        try {
            if ($action === 'delete' && $id > 0) {
                if ($table === 'specialties') {
                    // The N–N table is canonical; include the legacy FK while older rows migrate.
                    $st = gf_pdo()->prepare('SELECT COUNT(*) FROM trainers t WHERE t.specialty_id=? OR EXISTS (SELECT 1 FROM trainer_specialties ts WHERE ts.trainer_id=t.id AND ts.specialty_id=?)');
                    $st->execute([$id, $id]);
                    $used = (int) $st->fetchColumn();
                    if ($used > 0) {
                        throw new RuntimeException('Không thể xóa: còn ' . $used . ' huấn luyện viên đang dùng chuyên môn này.');
                    }
                } elseif ($table === 'amenities') {
                    $st = gf_pdo()->prepare('SELECT COUNT(*) FROM gym_amenities WHERE amenity_id=?');
                    $st->execute([$id]);
                    $used = (int) $st->fetchColumn();
                    if ($used > 0) {
                        throw new RuntimeException('Không thể xóa: còn ' . $used . ' phòng tập đang gắn tiện ích này.');
                    }
                } elseif ($table === 'districts') {
                    $st = gf_pdo()->prepare('SELECT COUNT(*) FROM gyms WHERE district_id=?');
                    $st->execute([$id]);
                    $usedGyms = (int) $st->fetchColumn();
                    if ($usedGyms > 0) {
                        throw new RuntimeException('Không thể xóa: còn ' . $usedGyms . ' phòng tập thuộc khu vực này.');
                    }
                    $st = gf_pdo()->prepare('SELECT COUNT(*) FROM trainers WHERE district_id=?');
                    $st->execute([$id]);
                    $usedTrainers = (int) $st->fetchColumn();
                    if ($usedTrainers > 0) {
                        throw new RuntimeException('Không thể xóa: còn ' . $usedTrainers . ' huấn luyện viên gắn khu vực này.');
                    }
                }
                gf_pdo()->prepare("DELETE FROM {$table} WHERE id=?")->execute([$id]);
                gf_admin_catalog_redirect($tab, 'deleted');
            }

            if ($name === '' || mb_strlen($name) > 100) {
                throw new RuntimeException('Tên phải từ 1–100 ký tự.');
            }

            $dupSql = "SELECT id FROM {$table} WHERE name=?";
            $dupParams = [$name];
            if ($id > 0) {
                $dupSql .= ' AND id <> ?';
                $dupParams[] = $id;
            }
            $dupSt = gf_pdo()->prepare($dupSql);
            $dupSt->execute($dupParams);
            if ($dupSt->fetch()) {
                throw new RuntimeException('Tên đã tồn tại.');
            }

            if ($id > 0) {
                if ($table === 'amenities') {
                    gf_pdo()->prepare('UPDATE amenities SET name=?, icon=? WHERE id=?')->execute([$name, $icon !== '' ? $icon : null, $id]);
                } else {
                    gf_pdo()->prepare("UPDATE {$table} SET name=? WHERE id=?")->execute([$name, $id]);
                }
                gf_admin_catalog_redirect($tab, 'updated');
            }

            $prefix = $tab === 'amenities' ? 'amenity' : ($tab === 'districts' ? 'district' : 'specialty');
            $baseSlug = gf_slugify($name, $prefix);
            $slug = $baseSlug;
            $slugSt = gf_pdo()->prepare("SELECT id FROM {$table} WHERE slug=?");
            $i = 2;
            while (true) {
                $slugSt->execute([$slug]);
                if (!$slugSt->fetch()) {
                    break;
                }
                $suffix = '-' . $i;
                $slug = mb_substr($baseSlug, 0, 120 - strlen($suffix)) . $suffix;
                $i++;
                if ($i > 500) {
                    $slug = $baseSlug . '-' . bin2hex(random_bytes(2));
                    break;
                }
            }
            if ($table === 'amenities') {
                gf_pdo()->prepare('INSERT INTO amenities (name, slug, icon) VALUES (?,?,?)')->execute([$name, $slug, $icon !== '' ? $icon : null]);
            } else {
                gf_pdo()->prepare("INSERT INTO {$table} (name, slug) VALUES (?,?)")->execute([$name, $slug]);
            }
            gf_admin_catalog_redirect($tab, 'created');
        } catch (Throwable $e) {
            $err = $e->getMessage();
        }
    }
}

$q = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$limit = $tab === 'categories' ? 8 : 10;
$offset = ($page - 1) * $limit;
$rows = [];
$total = 0;
$usage = [];
$paginationHtml = '';

if ($tab === 'categories') {
    $catFilters = [
        'limit' => $limit,
        'page'  => $page,
        'sort'  => 'oldest',
        'q'     => $q,
    ];
    $categoryResult = gf_admin_categories_paged($catFilters);
    if ($page > 1 && empty($categoryResult['items']) && (int) $categoryResult['total'] > 0) {
        $page = max(1, (int) ceil((int) $categoryResult['total'] / $limit));
        $catFilters['page'] = $page;
        $categoryResult = gf_admin_categories_paged($catFilters);
    }
    $rows = $categoryResult['items'];
    $total = (int) $categoryResult['total'];
    $st = gf_pdo()->query('SELECT category_id AS rid, COUNT(*) AS total FROM gym_categories GROUP BY category_id');
    if ($st) {
        foreach ($st->fetchAll() as $u) {
            $usage[(int) $u['rid']] = (int) $u['total'];
        }
    }
} else {
    $where = '1=1';
    $params = [];
    if ($q !== '') {
        $where .= ' AND name LIKE ?';
        $params[] = '%' . $q . '%';
    }
    $countSt = gf_pdo()->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where}");
    $countSt->execute($params);
    $total = (int) $countSt->fetchColumn();
    $listSt = gf_pdo()->prepare("SELECT * FROM {$table} WHERE {$where} ORDER BY id ASC LIMIT {$limit} OFFSET {$offset}");
    $listSt->execute($params);
    $rows = $listSt->fetchAll();

    if ($table === 'specialties') {
        $st = gf_pdo()->query('SELECT specialty_id AS rid, COUNT(*) AS total FROM trainers GROUP BY specialty_id');
    } elseif ($table === 'amenities') {
        $st = gf_pdo()->query('SELECT amenity_id AS rid, COUNT(*) AS total FROM gym_amenities GROUP BY amenity_id');
    } else {
        $st = gf_pdo()->query('SELECT district_id AS rid, COUNT(*) AS total FROM gyms GROUP BY district_id');
    }
    if ($st) {
        foreach ($st->fetchAll() as $u) {
            $usage[(int) $u['rid']] = (int) $u['total'];
        }
    }
}

$paginationHtml = gf_render_admin_pagination(
    $page,
    $limit,
    $total,
    array_merge(['tab' => $tab], $q !== '' ? ['q' => $q] : [])
);

$pageTitle = 'Danh mục - Admin GYMFINDER';
$extraCss = ['admin/assets/admin.css'];
$adminActive = 'categories';
require dirname(__DIR__, 2) . '/includes/layout_head.php';
require dirname(__DIR__, 2) . '/includes/admin_layout.php';
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1 text-dark" style="font-size: 24px; letter-spacing: -0.3px;">Danh mục</h2>
        <p class="text-secondary mb-0 small">Quản lý loại phòng tập, chuyên môn huấn luyện viên, tiện ích và khu vực</p>
    </div>
    <button class="btn btn-primary px-3 rounded-3" data-bs-toggle="modal" data-bs-target="#catalogCreate">
        <i class="fa-solid fa-plus me-2"></i>Thêm <?= gf_h($tabs[$tab]['label']) ?>
    </button>
</div>

<ul class="nav nav-pills gap-2 mb-4 flex-wrap">
    <?php foreach ($tabs as $key => $meta): ?>
        <li class="nav-item">
            <a class="nav-link rounded-pill px-3 <?= $key === $tab ? 'active' : 'bg-white border text-secondary' ?>"
               href="<?= gf_h(gf_url('admin/pages/categories.php?tab=' . urlencode($key))) ?>">
                <i class="fa-solid <?= gf_h($meta['icon']) ?> me-2"></i><?= gf_h($meta['label']) ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<?php if (!empty($_GET['created'])): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i>Đã thêm thành công.</div><?php endif; ?>
<?php if (!empty($_GET['updated'])): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i>Đã cập nhật thành công.</div><?php endif; ?>
<?php if (!empty($_GET['deleted'])): ?><div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i>Đã xóa thành công.</div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i><?= gf_h($err) ?></div><?php endif; ?>

<div class="modal fade" id="catalogCreate" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <form method="post" class="js-form-validate" novalidate>
                <?= gf_csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Thêm <?= gf_h($tabs[$tab]['label']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Tên *</label>
                    <input class="form-control admin-input mb-3" name="name" required maxlength="100" data-required-msg="Vui lòng nhập tên danh mục." placeholder="Nhập tên">
                    <?php if ($tab === 'categories'): ?>
                        <label class="form-label">Mô tả</label>
                        <textarea class="form-control admin-input" name="description" rows="3"></textarea>
                    <?php elseif ($tab === 'amenities'): ?>
                        <label class="form-label">Icon (tuỳ chọn)</label>
                        <input class="form-control admin-input" name="icon" maxlength="80" placeholder="ví dụ: fa-wifi">
                        <div class="form-text">Mã biểu tượng Font Awesome, ví dụ <code>fa-wifi</code>.</div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button class="btn btn-primary">Thêm</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form method="get" class="admin-card p-3 mb-4">
    <input type="hidden" name="tab" value="<?= gf_h($tab) ?>">
    <div class="position-relative">
        <i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary"></i>
        <input class="form-control admin-input ps-5" name="q" value="<?= gf_h($q) ?>" placeholder="Tìm <?= gf_h(mb_strtolower($tabs[$tab]['label'])) ?>...">
    </div>
</form>

<?php if ($tab === 'categories'): ?>
<?php
gf_admin_table_open([
    'id'        => 'categoriesTable',
    'class'     => 'admin-categories-table',
    'min_width' => '960px',
    'columns'   => [
        ['label' => 'ID', 'width' => '60px', 'align' => 'center'],
        ['label' => 'TÊN', 'width' => '220px'],
        ['label' => 'MÔ TẢ', 'width' => '320px'],
        ['label' => 'SỐ PHÒNG TẬP', 'width' => '140px', 'align' => 'center'],
        ['label' => 'TRẠNG THÁI', 'width' => '120px', 'align' => 'center'],
        ['label' => 'THAO TÁC', 'width' => '110px', 'align' => 'right', 'th_class' => 'text-end pe-3'],
    ],
]);
?>
<?php foreach ($rows as $r): ?>
<tr>
    <td class="text-center text-muted fw-semibold"><?= (int)$r['id'] ?></td>
    <td><span class="admin-cell-title"><?= gf_h($r['name']) ?></span></td>
    <td><span class="text-secondary"><?= gf_h($r['description'] ?? '—') ?></span></td>
    <td class="text-center text-secondary"><?= (int)($usage[(int)$r['id']] ?? 0) ?> phòng</td>
    <td class="text-center"><span class="admin-badge <?= $r['is_active'] ? 'badge-success' : 'badge-neutral' ?>"><?= $r['is_active'] ? 'Hoạt động' : 'Tạm ẩn' ?></span></td>
    <td class="text-end pe-3 text-nowrap">
        <button type="button" class="btn btn-action btn-action-edit" data-bs-toggle="modal" data-bs-target="#catalogEdit<?= (int)$r['id'] ?>" title="Chỉnh sửa"><i class="fa-solid fa-pen"></i></button>
        <form method="post" class="d-inline">
            <?= gf_csrf_field() ?>
            <input type="hidden" name="action" value="toggle">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <input type="hidden" name="is_active" value="<?= $r['is_active'] ? '0' : '1' ?>">
            <button class="btn btn-action <?= $r['is_active'] ? 'btn-action-delete' : 'btn-action-edit' ?>" title="<?= $r['is_active'] ? 'Ẩn' : 'Hiện' ?>">
                <i class="fa-solid <?= $r['is_active'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
            </button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
<?php
gf_admin_table_close([
    'empty'         => empty($rows),
    'empty_message' => 'Không tìm thấy loại phòng tập nào.',
    'colspan'       => 6,
    'footer_html'   => $paginationHtml,
]);
?>
<?php else: ?>
<?php
$columns = [
    ['label' => 'ID', 'width' => '60px', 'align' => 'center'],
    ['label' => 'TÊN', 'width' => '260px'],
    ['label' => 'MÃ ĐƯỜNG DẪN', 'width' => '220px'],
];
if ($tab === 'amenities') {
    $columns[] = ['label' => 'BIỂU TƯỢNG', 'width' => '140px'];
}
$columns[] = ['label' => 'ĐANG DÙNG', 'width' => '120px', 'align' => 'center'];
$columns[] = ['label' => 'THAO TÁC', 'width' => '120px', 'align' => 'right', 'th_class' => 'text-end pe-3'];
gf_admin_table_open([
    'id'        => 'catalogsTable',
    'class'     => 'admin-catalogs-table',
    'min_width' => '900px',
    'columns'   => $columns,
]);
$colspan = count($columns);
?>
<?php foreach ($rows as $r): ?>
<tr>
    <td class="text-center text-muted fw-semibold"><?= (int)$r['id'] ?></td>
    <td><span class="admin-cell-title"><?= gf_h($r['name']) ?></span></td>
    <td><code class="small text-secondary"><?= gf_h($r['slug'] ?? '') ?></code></td>
    <?php if ($tab === 'amenities'): ?>
        <td><span class="text-secondary small"><?= gf_h($r['icon'] ?? '—') ?></span></td>
    <?php endif; ?>
    <td class="text-center text-secondary"><?= (int)($usage[(int)$r['id']] ?? 0) ?></td>
    <td class="text-end pe-3 text-nowrap">
        <button type="button" class="btn btn-action btn-action-edit" data-bs-toggle="modal" data-bs-target="#catalogEdit<?= (int)$r['id'] ?>" title="Sửa"><i class="fa-solid fa-pen"></i></button>
        <form method="post" class="d-inline js-confirm-submit"
              data-confirm-title="Xóa mục này?"
              data-confirm-message="Thao tác không hoàn tác được nếu không còn liên kết."
              data-confirm-label="Xóa"
              data-confirm-danger="1">
            <?= gf_csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button type="submit" class="btn btn-action btn-action-delete" title="Xóa" <?= !empty($usage[(int)$r['id']]) ? 'disabled' : '' ?>><i class="fa-solid fa-trash"></i></button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
<?php
gf_admin_table_close([
    'empty'         => empty($rows),
    'empty_message' => 'Chưa có mục nào.',
    'colspan'       => $colspan,
    'footer_html'   => $paginationHtml,
]);
?>
<?php endif; ?>

<?php foreach ($rows as $r): ?>
<div class="modal fade" id="catalogEdit<?= (int)$r['id'] ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4">
            <form method="post" class="js-form-validate" novalidate>
                <?= gf_csrf_field() ?>
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Sửa <?= gf_h($tabs[$tab]['label']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Tên *</label>
                    <input class="form-control admin-input mb-3" name="name" value="<?= gf_h($r['name']) ?>" required maxlength="100" data-required-msg="Vui lòng nhập tên danh mục.">
                    <?php if ($tab === 'categories'): ?>
                        <label class="form-label">Mô tả</label>
                        <textarea class="form-control admin-input mb-3" name="description" rows="3"><?= gf_h($r['description'] ?? '') ?></textarea>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="category-active-<?= (int)$r['id'] ?>" value="1" <?= !empty($r['is_active']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="category-active-<?= (int)$r['id'] ?>">Đang hoạt động</label>
                        </div>
                    <?php elseif ($tab === 'amenities'): ?>
                        <label class="form-label">Icon (tuỳ chọn)</label>
                        <input class="form-control admin-input" name="icon" value="<?= gf_h($r['icon'] ?? '') ?>" maxlength="80" placeholder="vd: fa-wifi">
                        <div class="form-text mt-2">Mã đường dẫn: <code><?= gf_h($r['slug'] ?? '') ?></code></div>
                    <?php else: ?>
                        <div class="form-text">Mã đường dẫn: <code><?= gf_h($r['slug'] ?? '') ?></code> (giữ nguyên khi đổi tên).</div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
</main></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php require dirname(__DIR__, 2) . '/includes/ui_feedback.php'; ?>
</body></html>
