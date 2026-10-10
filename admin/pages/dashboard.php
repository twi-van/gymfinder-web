<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
$stats = gf_admin_stats();
$districtStats = gf_pdo()->query("SELECT d.name, COUNT(g.id) AS total FROM districts d LEFT JOIN gyms g ON g.district_id=d.id GROUP BY d.id, d.name ORDER BY total DESC, d.name LIMIT 8")->fetchAll();
$recentReviews = gf_pdo()->query("SELECT r.id, r.rating, r.comment, r.created_at, u.full_name, g.name AS gym_name, t.full_name AS trainer_name FROM reviews r JOIN users u ON u.id=r.user_id LEFT JOIN gyms g ON g.id=r.gym_id LEFT JOIN trainers t ON t.id=r.trainer_id ORDER BY r.created_at DESC, r.id DESC LIMIT 5")->fetchAll();
$featuredGyms = gf_pdo()->query("SELECT g.id, g.name, g.price_min, g.price_max, g.avg_rating, g.cover_image_url, d.name AS district_name FROM gyms g JOIN districts d ON d.id=g.district_id WHERE g.status='active' AND g.is_featured=1 ORDER BY g.avg_rating DESC, g.id DESC LIMIT 4")->fetchAll();
$pageTitle = 'Tổng quan - Admin GYMFINDER';
$extraCss = ['admin/assets/admin.css'];
$adminActive = 'dashboard';
require dirname(__DIR__, 2) . '/includes/layout_head.php';
require dirname(__DIR__, 2) . '/includes/admin_layout.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4"><h2 class="fw-bold mb-0">Tổng quan</h2><div class="text-secondary small"><i class="fa-regular fa-calendar me-2"></i>Tháng này</div></div>
<div class="row g-4 mb-4">
<?php foreach ([['Tổng phòng tập',$stats['gyms'],'fa-dumbbell','+12%'],['Huấn luyện viên',$stats['trainers'],'fa-user-tie','+5%'],['Người dùng',$stats['users'],'fa-users','+18%'],['Đánh giá mới',$stats['reviews'],'fa-star','+24%']] as $card): ?>
<div class="col-6 col-md-3"><div class="admin-card h-100 p-4"><div class="d-flex justify-content-between align-items-start mb-3"><div class="bg-primary-light text-primary rounded-3 p-2 d-inline-flex"><i class="fa-solid <?= $card[2] ?> fa-lg"></i></div><span class="badge badge-success"><?= gf_h($card[3]) ?></span></div><h6 class="text-secondary mb-1"><?= gf_h($card[0]) ?></h6><h3 class="fw-bold mb-0"><?= (int)$card[1] ?></h3><?php if ($card[0] === 'Đánh giá mới'): ?><small class="text-secondary">Chờ duyệt: <?= (int)$stats['pending_reviews'] ?></small><?php endif; ?></div></div>
<?php endforeach; ?>
</div>
<div class="row g-4 mb-4">
    <div class="col-12 col-lg-7 d-flex flex-column"><div class="admin-card flex-grow-1 p-4 d-flex flex-column"><h6 class="fw-bold mb-4">Phòng tập theo khu vực</h6><div style="height:320px;width:100%"><canvas id="gymRegionChart"></canvas></div></div></div>
    <div class="col-12 col-lg-5"><div class="admin-card h-100 p-0 overflow-hidden d-flex flex-column"><div class="p-4 border-bottom d-flex justify-content-between align-items-center"><h6 class="fw-bold mb-0">Đánh giá gần đây</h6><a href="<?= gf_h(gf_url('admin/pages/reviews.php')) ?>" class="text-primary text-decoration-none small fw-semibold">Xem tất cả</a></div><div class="flex-grow-1">
        <?php if (!$recentReviews): ?><p class="text-secondary p-4 mb-0">Chưa có đánh giá.</p><?php endif; ?>
        <?php foreach ($recentReviews as $review): ?><div class="p-3 border-bottom d-flex gap-3"><div class="rounded-circle bg-light d-flex align-items-center justify-content-center flex-shrink-0" style="width:44px;height:44px"><?= gf_h(mb_strtoupper(mb_substr($review['full_name'], 0, 1))) ?></div><div class="min-w-0 flex-grow-1"><div class="d-flex justify-content-between gap-2"><strong><?= gf_h($review['full_name']) ?></strong><small class="text-secondary text-nowrap"><?= gf_h(date('d/m/Y', strtotime($review['created_at']))) ?></small></div><div class="text-warning"><?= str_repeat('★', (int)$review['rating']) ?></div><p class="text-secondary small mb-1 text-truncate"><?= gf_h($review['comment']) ?></p><span class="badge bg-light text-secondary border"><?= gf_h($review['gym_name'] ?: $review['trainer_name'] ?: 'Đối tượng đã ẩn') ?></span></div></div><?php endforeach; ?>
    </div></div></div>
</div>
<div class="admin-card p-4 mb-4"><div class="d-flex justify-content-between align-items-center mb-4"><h6 class="fw-bold mb-0">Phòng tập nổi bật</h6><a href="<?= gf_h(gf_url('admin/pages/gyms.php')) ?>" class="text-primary text-decoration-none small fw-semibold">Quản lý</a></div><div class="row g-3">
<?php if (!$featuredGyms): ?><p class="text-secondary">Chưa có phòng tập nổi bật.</p><?php endif; ?>
<?php foreach ($featuredGyms as $gym): ?><div class="col-12 col-md-6 col-xl-3"><div class="border rounded-3 p-3 h-100"><img class="w-100 rounded-3 object-fit-cover mb-3" style="height:130px" src="<?= gf_h(gf_cover_image($gym['cover_image_url'], (int)$gym['id'])) ?>" alt="<?= gf_h($gym['name']) ?>"><strong><?= gf_h($gym['name']) ?></strong><div class="small text-secondary"><?= gf_h($gym['district_name']) ?> · <?= gf_h(gf_format_price((int)$gym['price_min'], (int)$gym['price_max'])) ?></div><div class="text-warning mt-2">★ <?= gf_h((string)$gym['avg_rating']) ?></div></div></div><?php endforeach; ?>
</div></div>
</main></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const districtLabels = <?= json_encode(array_column($districtStats, 'name'), JSON_UNESCAPED_UNICODE) ?>;
const districtValues = <?= json_encode(array_map('intval', array_column($districtStats, 'total'))) ?>;
const chartCanvas = document.getElementById('gymRegionChart');
if (chartCanvas && window.Chart) new Chart(chartCanvas, {type:'bar',data:{labels:districtLabels,datasets:[{label:'Phòng tập',data:districtValues,backgroundColor:'#2ec4b6',borderRadius:8,maxBarThickness:48}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});
</script>
</body></html>
