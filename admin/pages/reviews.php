<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
$me = gf_require_admin();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['action'])) {
    if ($_POST['action'] === 'delete') {
        gf_admin_delete_review((int)$_POST['id']);
        gf_redirect(gf_url('admin/pages/reviews.php?deleted=1'));
    }
    $status = $_POST['action'] === 'reject' ? 'rejected' : 'approved';
    $body = ['status' => $status];
    if ($status === 'rejected') { $body['reject_reason'] = trim((string)($_POST['reason'] ?? 'Không phù hợp')); }
    gf_admin_moderate_review((int)$_POST['id'], $body, $me);
    gf_redirect(gf_url('admin/pages/reviews.php'));
}
$reviewQuery = gf_pdo()->prepare("SELECT r.*, u.full_name, g.name AS gym_name, t.full_name AS trainer_name FROM reviews r JOIN users u ON u.id=r.user_id LEFT JOIN gyms g ON g.id=r.gym_id LEFT JOIN trainers t ON t.id=r.trainer_id WHERE (?='' OR r.status=?) AND (?='' OR r.comment LIKE ? OR u.full_name LIKE ?) ORDER BY r.created_at DESC, r.id DESC LIMIT 100");
$reviewStatus = (string)($_GET['status'] ?? ''); $reviewSearch = trim((string)($_GET['q'] ?? ''));
$reviewLike = '%'.$reviewSearch.'%';
$reviewQuery->execute([$reviewStatus,$reviewStatus,$reviewSearch,$reviewLike,$reviewLike]);
$rows = $reviewQuery->fetchAll();
$pageTitle = 'Quản lý đánh giá';
$extraCss = ['admin/assets/admin.css'];
$adminActive = 'reviews';
require dirname(__DIR__, 2) . '/includes/layout_head.php';
require dirname(__DIR__, 2) . '/includes/admin_layout.php';
?>
<h2 class="fw-bold mb-4">Quản lý đánh giá</h2>
<?php if (!empty($_GET['deleted'])): ?><div class="alert alert-success">Đã xóa đánh giá.</div><?php endif; ?>
<form method="get" class="admin-card p-3 mb-4"><div class="row g-3"><div class="col-md-7"><input class="form-control admin-input" name="q" value="<?= gf_h($reviewSearch) ?>" placeholder="⌕  Tìm kiếm nội dung đánh giá..."></div><div class="col-md-3"><select name="status" class="form-select admin-input"><option value="">Tất cả trạng thái</option><?php foreach(['pending'=>'Chờ duyệt','approved'=>'Đã duyệt','rejected'=>'Từ chối'] as $sv=>$sl): ?><option value="<?= $sv ?>" <?= $reviewStatus===$sv?'selected':'' ?>><?= $sl ?></option><?php endforeach; ?></select></div><div class="col-md-2"><button class="btn btn-light border w-100">Lọc</button></div></div></form>
<div class="admin-card overflow-hidden"><div class="table-responsive admin-drag-scroll" id="reviewTableScroll" aria-label="Bảng đánh giá, kéo ngang để xem các cột còn lại"><table class="table admin-table admin-reviews-table mb-0">
<colgroup><col style="width:14%"><col style="width:18%"><col style="width:13%"><col style="width:20%"><col style="width:11%"><col style="width:11%"><col style="width:13%"></colgroup>
<thead><tr><th>NGƯỜI DÙNG</th><th>PHÒNG TẬP</th><th>ĐÁNH GIÁ</th><th>NỘI DUNG</th><th>NGÀY</th><th>TRẠNG THÁI</th><th>THAO TÁC</th></tr></thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr>
    <td><div class="d-flex align-items-center gap-2"><span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-light text-secondary" style="width:40px;height:40px;flex:0 0 40px"><i class="fa-regular fa-user"></i></span><strong><?= gf_h($r['full_name']) ?></strong></div></td>
    <td><?= gf_h($r['gym_name'] ?: $r['trainer_name']) ?></td>
    <td class="text-warning text-nowrap"><?php for($i=1;$i<=5;$i++): ?><i class="fa-solid fa-star <?= $i>(int)$r['rating']?'text-secondary opacity-25':'' ?>"></i><?php endfor; ?></td>
    <td><?= gf_h(mb_strlen($r['comment'], 'UTF-8') > 60 ? mb_substr($r['comment'], 0, 57, 'UTF-8') . '…' : $r['comment']) ?></td>
    <td><?= gf_h(date('d/m/Y',strtotime($r['created_at']))) ?></td>
    <td><span class="admin-badge <?= $r['status']==='approved'?'badge-success':($r['status']==='pending'?'badge-warning':'badge-danger') ?>"><?= ['approved'=>'Đã duyệt','pending'=>'Chờ duyệt','rejected'=>'Từ chối'][$r['status']]??gf_h($r['status']) ?></span></td>
    <td>
        <button type="button" class="btn btn-action btn-action-view" data-bs-toggle="modal" data-bs-target="#reviewModal<?= (int)$r['id'] ?>" title="Xem"><i class="fa-solid fa-eye"></i></button>
        <div class="modal fade" id="reviewModal<?= (int)$r['id'] ?>" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 shadow rounded-4"><div class="modal-header"><h5 class="modal-title fw-bold">Nội dung đánh giá</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p><strong><?= gf_h($r['full_name']) ?></strong> · <?= (int)$r['rating'] ?>/5 sao</p><p><?= nl2br(gf_h($r['comment'])) ?></p></div></div></div></div>
        <?php if ($r['status']==='pending'): ?>
        <form method="post" class="d-inline"><?= gf_csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="approve"><button class="btn btn-action btn-outline-success" title="Duyệt"><i class="fa-solid fa-check"></i></button></form>
        <form method="post" class="d-inline"><?= gf_csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="reject"><input type="hidden" name="reason" value="Không phù hợp"><button class="btn btn-action btn-action-delete" title="Từ chối"><i class="fa-solid fa-xmark"></i></button></form>
        <?php else: ?><form method="post" class="d-inline" onsubmit="return confirm('Xóa đánh giá này?')"><?= gf_csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-action btn-action-delete" title="Xóa đánh giá"><i class="fa-solid fa-trash"></i></button></form><?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table></div></div>
</main></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    const scroller = document.getElementById('reviewTableScroll');
    if (!scroller) return;
    // Start at the first column; the scrollable width is still available to the right.
    requestAnimationFrame(() => { scroller.scrollLeft = 0; });
    let startX = 0;
    let startScroll = 0;
    let dragged = false;
    let suppressClick = false;
    scroller.addEventListener('pointerdown', (event) => {
        if (event.button !== 0 || scroller.scrollWidth <= scroller.clientWidth) return;
        if (event.target.closest('button, a, input, select, textarea, form')) return;
        startX = event.clientX;
        startScroll = scroller.scrollLeft;
        dragged = false;
        scroller.setPointerCapture(event.pointerId);
        scroller.classList.add('is-dragging');
    });
    scroller.addEventListener('pointermove', (event) => {
        if (!scroller.hasPointerCapture(event.pointerId)) return;
        const delta = event.clientX - startX;
        if (Math.abs(delta) > 4) dragged = true;
        if (dragged) {
            event.preventDefault();
            scroller.scrollLeft = startScroll - delta;
        }
    });
    const finishDrag = (event) => {
        if (scroller.hasPointerCapture(event.pointerId)) scroller.releasePointerCapture(event.pointerId);
        scroller.classList.remove('is-dragging');
        if (dragged) {
            suppressClick = true;
            window.setTimeout(() => { suppressClick = false; }, 0);
        }
        dragged = false;
    };
    scroller.addEventListener('pointerup', finishDrag);
    scroller.addEventListener('pointercancel', finishDrag);
    scroller.addEventListener('click', (event) => {
        if (suppressClick) {
            event.preventDefault();
            event.stopPropagation();
        }
    }, true);
})();
</script>
</body></html>
