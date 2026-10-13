<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
gf_require_admin();

$stats = gf_admin_stats();

// 1. Thống kê phòng tập theo khu vực (ưu tiên các quận có phòng tập thực tế)
$districtStats = gf_pdo()->query("
    SELECT d.name, COUNT(g.id) AS total 
    FROM districts d 
    LEFT JOIN gyms g ON g.district_id = d.id 
    GROUP BY d.id, d.name 
    HAVING total > 0
    ORDER BY total DESC, d.name 
    LIMIT 7
")->fetchAll();

if (empty($districtStats)) {
    $districtStats = gf_pdo()->query("
        SELECT d.name, COUNT(g.id) AS total 
        FROM districts d 
        LEFT JOIN gyms g ON g.district_id = d.id 
        GROUP BY d.id, d.name 
        ORDER BY total DESC, d.name 
        LIMIT 6
    ")->fetchAll();
}

// 2. Đánh giá gần đây (ưu tiên các đánh giá đang chờ duyệt lên đầu)
$recentReviews = gf_pdo()->query("
    SELECT r.id, r.rating, r.comment, r.status, r.created_at, 
           u.full_name, u.avatar_url,
           g.name AS gym_name, 
           t.full_name AS trainer_name 
    FROM reviews r 
    JOIN users u ON u.id = r.user_id 
    LEFT JOIN gyms g ON g.id = r.gym_id 
    LEFT JOIN trainers t ON t.id = r.trainer_id 
    ORDER BY (r.status = 'pending') DESC, r.created_at DESC, r.id DESC 
    LIMIT 5
")->fetchAll();

// 3. Phòng tập nổi bật (Ưu tiên is_featured=1 đang hoạt động, fallback lấy top đánh giá)
$featuredGyms = gf_pdo()->query("
    SELECT g.id, g.name, g.price_min, g.price_max, g.avg_rating, g.review_count, g.cover_image_url, 
           d.name AS district_name 
    FROM gyms g 
    JOIN districts d ON d.id = g.district_id 
    WHERE g.status = 'active' AND g.is_featured = 1 
    ORDER BY g.avg_rating DESC, g.id DESC 
    LIMIT 4
")->fetchAll();

if (empty($featuredGyms)) {
    $featuredGyms = gf_pdo()->query("
        SELECT g.id, g.name, g.price_min, g.price_max, g.avg_rating, g.review_count, g.cover_image_url, 
               d.name AS district_name 
        FROM gyms g 
        JOIN districts d ON d.id = g.district_id 
        WHERE g.status = 'active' 
        ORDER BY g.avg_rating DESC, g.id DESC 
        LIMIT 4
    ")->fetchAll();
}

$pageTitle = 'Tổng quan - Admin GYMFINDER';
$extraCss = ['admin/assets/admin.css'];
$adminActive = 'dashboard';
require dirname(__DIR__, 2) . '/includes/layout_head.php';
require dirname(__DIR__, 2) . '/includes/admin_layout.php';
?>

<!-- TIÊU ĐỀ TRANG VÀ THỜI GIAN CẬP NHẬT -->
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h2 class="fw-bold mb-1 text-dark" style="font-size: 24px; letter-spacing: -0.3px;">Tổng quan</h2>
        <p class="text-secondary mb-0 small">Theo dõi và quản lý hoạt động toàn hệ thống GYMFINDER</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <div class="text-secondary small bg-white border px-3 py-2 rounded-3 shadow-sm d-flex align-items-center">
            <i class="fa-regular fa-clock me-2 text-primary"></i>
            <span>Cập nhật: <strong class="text-dark"><?= date('H:i - d/m/Y') ?></strong></span>
        </div>
        <button type="button" class="btn bg-white border shadow-sm rounded-3 py-2 px-3 text-secondary" onclick="window.location.reload()" title="Làm mới dữ liệu">
            <i class="fa-solid fa-rotate-right"></i>
        </button>
    </div>
</div>

<!-- THÔNG BÁO HỆ THỐNG / VIỆC CẦN XỬ LÝ (CHỈ HIỂN THỊ KHI CÓ ĐÁNH GIÁ CHỜ DUYỆT THẬT) -->
<?php if ((int)$stats['pending_reviews'] > 0): ?>
<div class="dashboard-action-banner d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 shadow-sm">
    <div class="d-flex align-items-center gap-3">
        <div class="stat-icon stat-icon-warning">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div>
            <h6 class="fw-bold mb-0 text-dark">Có <?= (int)$stats['pending_reviews'] ?> đánh giá mới đang chờ kiểm duyệt</h6>
            <div class="text-secondary small">Vui lòng kiểm tra và phê duyệt hoặc từ chối để cập nhật đánh giá cho phòng tập & huấn luyện viên.</div>
        </div>
    </div>
    <a href="<?= gf_h(gf_url('admin/pages/reviews.php?status=pending')) ?>" class="btn btn-warning btn-sm rounded-pill px-3 fw-semibold">
        <i class="fa-solid fa-clipboard-check me-1"></i>Kiểm duyệt ngay
    </a>
</div>
<?php endif; ?>

<!-- 4 THẺ THỐNG KÊ CHÍNH (ĐỒNG ĐỀU, CĂN CHỈNH THỐNG NHẤT, CÓ LINK ĐIỀU HƯỚNG) -->
<div class="row g-3 g-xl-4 mb-4">
    <!-- Thẻ 1: Tổng số phòng tập -->
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="<?= gf_h(gf_url('admin/pages/gyms.php')) ?>" class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="text-secondary fw-semibold small">Tổng phòng tập</span>
                <div class="stat-icon stat-icon-teal">
                    <i class="fa-solid fa-dumbbell"></i>
                </div>
            </div>
            <div>
                <h3 class="fw-bold mb-1 text-dark"><?= number_format((int)$stats['gyms']) ?></h3>
                <div class="text-secondary small d-flex align-items-center gap-1">
                    <i class="fa-solid fa-circle-check text-success" style="font-size: 11px;"></i>
                    <span><strong><?= (int)$stats['total_gyms_active'] ?></strong> đang hoạt động</span>
                </div>
            </div>
        </a>
    </div>

    <!-- Thẻ 2: Huấn luyện viên -->
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="<?= gf_h(gf_url('admin/pages/trainers.php')) ?>" class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="text-secondary fw-semibold small">Huấn luyện viên</span>
                <div class="stat-icon stat-icon-teal">
                    <i class="fa-solid fa-user-tie"></i>
                </div>
            </div>
            <div>
                <h3 class="fw-bold mb-1 text-dark"><?= number_format((int)$stats['trainers']) ?></h3>
                <div class="text-secondary small d-flex align-items-center gap-1">
                    <i class="fa-solid fa-circle-check text-success" style="font-size: 11px;"></i>
                    <span><strong><?= (int)$stats['total_trainers_active'] ?></strong> đang hoạt động</span>
                </div>
            </div>
        </a>
    </div>

    <!-- Thẻ 3: Người dùng -->
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="<?= gf_h(gf_url('admin/pages/users.php')) ?>" class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="text-secondary fw-semibold small">Người dùng hệ thống</span>
                <div class="stat-icon stat-icon-teal">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div>
                <h3 class="fw-bold mb-1 text-dark"><?= number_format((int)$stats['users']) ?></h3>
                <div class="text-secondary small d-flex align-items-center gap-1">
                    <i class="fa-solid fa-user-check text-primary" style="font-size: 11px;"></i>
                    <span>Tài khoản hội viên</span>
                </div>
            </div>
        </a>
    </div>

    <!-- Thẻ 4: Đánh giá chờ duyệt -->
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="<?= gf_h(gf_url('admin/pages/reviews.php?status=pending')) ?>" class="stat-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <span class="text-secondary fw-semibold small">Đánh giá chờ duyệt</span>
                <div class="stat-icon <?= (int)$stats['pending_reviews'] > 0 ? 'stat-icon-warning' : 'stat-icon-teal' ?>">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>
            <div>
                <h3 class="fw-bold mb-1 <?= (int)$stats['pending_reviews'] > 0 ? 'text-warning' : 'text-dark' ?>">
                    <?= number_format((int)$stats['pending_reviews']) ?>
                </h3>
                <div class="small d-flex align-items-center gap-1">
                    <?php if ((int)$stats['pending_reviews'] > 0): ?>
                        <span class="text-warning fw-semibold"><i class="fa-solid fa-circle-exclamation me-1" style="font-size: 11px;"></i>Cần xử lý</span>
                    <?php else: ?>
                        <span class="text-success"><i class="fa-solid fa-circle-check me-1" style="font-size: 11px;"></i>Đã duyệt hết</span>
                    <?php endif; ?>
                    <span class="text-secondary ms-1">/ <?= (int)$stats['reviews'] ?> tổng</span>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- BIỂU ĐỒ PHÒNG TẬP THEO KHU VỰC VÀ DANH SÁCH ĐÁNH GIÁ GẦN ĐÂY -->
<div class="row g-4 mb-4">
    <!-- Cột 1: Biểu đồ thanh ngang theo khu vực -->
    <div class="col-12 col-lg-7 d-flex flex-column">
        <div class="admin-card flex-grow-1 p-4 d-flex flex-column">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold mb-1 text-dark">Phòng tập theo khu vực</h6>
                    <div class="text-secondary small">Phân bố số lượng phòng theo các quận/huyện TP.HCM</div>
                </div>
                <span class="badge bg-light text-secondary border small px-2 py-1">Top khu vực</span>
            </div>
            
            <div class="flex-grow-1 position-relative" style="min-height: 280px; width: 100%;">
                <?php if (empty($districtStats)): ?>
                    <div class="empty-state py-5 text-center">
                        <i class="fa-regular fa-map fa-2x text-muted mb-2 d-block"></i>
                        <p class="text-secondary small mb-0">Chưa có dữ liệu phân bố phòng tập theo khu vực.</p>
                    </div>
                <?php else: ?>
                    <canvas id="gymRegionChart"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Cột 2: Đánh giá gần đây -->
    <div class="col-12 col-lg-5">
        <div class="admin-card h-100 p-0 overflow-hidden d-flex flex-column">
            <div class="p-4 border-bottom d-flex justify-content-between align-items-center bg-white">
                <div>
                    <h6 class="fw-bold mb-1 text-dark">Đánh giá gần đây</h6>
                    <div class="text-secondary small">Ưu tiên các đánh giá mới gửi</div>
                </div>
                <a href="<?= gf_h(gf_url('admin/pages/reviews.php')) ?>" class="text-primary text-decoration-none small fw-semibold">
                    Xem tất cả <i class="fa-solid fa-angle-right ms-1"></i>
                </a>
            </div>

            <div class="flex-grow-1 overflow-auto" style="max-height: 340px;">
                <?php if (empty($recentReviews)): ?>
                    <div class="p-4 text-center text-secondary small">
                        <i class="fa-regular fa-comment-dots fa-2x text-muted mb-2 d-block"></i>
                        Chưa có đánh giá nào trên hệ thống.
                    </div>
                <?php else: ?>
                    <?php foreach ($recentReviews as $review): ?>
                        <div class="p-3 border-bottom dashboard-review-item d-flex gap-3">
                            <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center flex-shrink-0 text-primary fw-bold" style="width:40px;height:40px;font-size:14px;">
                                <?= gf_h(mb_strtoupper(mb_substr($review['full_name'], 0, 1))) ?>
                            </div>
                            <div class="min-w-0 flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                                    <span class="fw-bold text-dark small text-truncate"><?= gf_h($review['full_name']) ?></span>
                                    <span class="text-secondary text-nowrap" style="font-size: 11.5px;"><?= gf_h(date('d/m/Y', strtotime($review['created_at']))) ?></span>
                                </div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <div class="text-warning small" style="line-height: 1;">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="<?= $i <= (int)$review['rating'] ? 'fa-solid fa-star' : 'fa-regular fa-star text-muted' ?>" style="font-size: 11px;"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <?php if ($review['status'] === 'pending'): ?>
                                        <span class="admin-badge badge-warning" style="font-size: 10.5px; padding: 2px 7px;">Chờ duyệt</span>
                                    <?php elseif ($review['status'] === 'approved'): ?>
                                        <span class="admin-badge badge-success" style="font-size: 10.5px; padding: 2px 7px;">Đã duyệt</span>
                                    <?php else: ?>
                                        <span class="admin-badge badge-danger" style="font-size: 10.5px; padding: 2px 7px;">Từ chối</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-secondary small mb-1 text-truncate" title="<?= gf_h($review['comment']) ?>" style="line-height: 1.4;">
                                    <?= gf_h($review['comment']) ?>
                                </p>
                                <div class="text-muted" style="font-size: 11px;">
                                    <?php if (!empty($review['gym_name'])): ?>
                                        <i class="fa-solid fa-dumbbell text-secondary me-1"></i><span class="text-dark fw-medium"><?= gf_h($review['gym_name']) ?></span>
                                    <?php elseif (!empty($review['trainer_name'])): ?>
                                        <i class="fa-solid fa-user-tie text-secondary me-1"></i><span class="text-dark fw-medium"><?= gf_h($review['trainer_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Đối tượng đã ẩn</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- PHÒNG TẬP NỔI BẬT (CARD ĐỒNG ĐỀU, COVER TỶ LỆ CHUẨN, FALLBACK ẢNH LỖI) -->
<div class="admin-card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h6 class="fw-bold mb-1 text-dark">Phòng tập nổi bật</h6>
            <div class="text-secondary small">Các cơ sở tiêu biểu đang hoạt động trên hệ thống GYMFINDER</div>
        </div>
        <a href="<?= gf_h(gf_url('admin/pages/gyms.php')) ?>" class="text-primary text-decoration-none small fw-semibold">
            Quản lý phòng tập <i class="fa-solid fa-angle-right ms-1"></i>
        </a>
    </div>

    <?php if (empty($featuredGyms)): ?>
        <div class="text-center py-4 text-secondary small">
            <i class="fa-regular fa-building fa-2x text-muted mb-2 d-block"></i>
            Chưa có phòng tập nổi bật nào đang hoạt động.
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($featuredGyms as $gym): ?>
                <div class="col-12 col-sm-6 col-xl-3">
                    <a href="<?= gf_h(gf_url('admin/pages/gyms.php?q=' . urlencode($gym['name']))) ?>" class="dashboard-gym-card">
                        <div class="gym-cover-wrap">
                            <img class="gym-cover-img" 
                                 src="<?= gf_h(gf_cover_image($gym['cover_image_url'], (int)$gym['id'])) ?>" 
                                 alt="<?= gf_h($gym['name']) ?>"
                                 onerror="this.onerror=null;this.src='<?= gf_h(gf_cover_image(null, (int)$gym['id'])) ?>';">
                            <span class="position-absolute bottom-0 start-0 m-2 badge bg-dark bg-opacity-75 text-white" style="font-size: 11px;">
                                <i class="fa-solid fa-star text-warning me-1"></i><?= number_format((float)($gym['avg_rating'] ?? 0), 1) ?>
                            </span>
                        </div>
                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <strong class="text-dark mb-1 text-truncate" title="<?= gf_h($gym['name']) ?>"><?= gf_h($gym['name']) ?></strong>
                            <div class="small text-secondary mb-2 text-truncate">
                                <i class="fa-solid fa-location-dot text-muted me-1"></i><?= gf_h($gym['district_name']) ?>
                            </div>
                            <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-primary small">
                                    <?= gf_h(gf_format_price((int)$gym['price_min'], (int)$gym['price_max'])) ?>
                                </span>
                                <span class="text-secondary" style="font-size: 11px;">
                                    <?= (int)($gym['review_count'] ?? 0) ?> đánh giá
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

</main></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const districtLabels = <?= json_encode(array_column($districtStats, 'name'), JSON_UNESCAPED_UNICODE) ?>;
    const districtValues = <?= json_encode(array_map('intval', array_column($districtStats, 'total'))) ?>;
    const chartCanvas = document.getElementById('gymRegionChart');

    if (chartCanvas && window.Chart && districtLabels.length > 0) {
        new Chart(chartCanvas, {
            type: 'bar',
            data: {
                labels: districtLabels,
                datasets: [{
                    label: 'Số phòng tập',
                    data: districtValues,
                    backgroundColor: 'rgba(0, 201, 177, 0.85)',
                    hoverBackgroundColor: '#00c9b1',
                    borderRadius: 6,
                    borderSkipped: false,
                    barThickness: 18,
                    maxBarThickness: 24
                }]
            },
            options: {
                indexAxis: 'y', // Biểu đồ thanh ngang giúp tên quận hiển thị trọn vẹn, không bị cắt chữ
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleFont: { size: 13, family: 'Inter', weight: '600' },
                        bodyFont: { size: 12, family: 'Inter' },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                return ' ' + context.raw + ' phòng tập';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            color: '#64748b',
                            font: { family: 'Inter', size: 11.5 }
                        },
                        grid: {
                            color: '#f1f5f9',
                            drawBorder: false
                        }
                    },
                    y: {
                        ticks: {
                            color: '#1e293b',
                            font: { family: 'Inter', size: 12, weight: '500' }
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
});
</script>
</body></html>

