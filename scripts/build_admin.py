import os

# --- 1. CSS ---
css_content = """
/* admin/assets/admin.css */

:root {
    --primary-color: #00c9b1;
    --primary-hover: #00b39d;
    --primary-light: rgba(0, 201, 177, 0.1);
    --secondary-color: #64748b;
    --dark-color: #1e293b;
    --light-bg: #f8fafc;
    --border-color: #e2e8f0;
    --success-color: #10b981;
    --warning-color: #f59e0b;
    --danger-color: #ef4444;
}

body {
    background-color: var(--light-bg);
    color: var(--dark-color);
    font-family: 'Inter', sans-serif;
}

/* Sidebar */
.sidebar {
    min-height: 100vh;
    width: 260px;
    background-color: #ffffff;
    border-right: 1px solid var(--border-color);
    transition: all 0.3s ease;
    z-index: 1050;
}
.main-content {
    flex: 1;
    min-width: 0;
    transition: all 0.3s ease;
}
.nav-link {
    color: var(--secondary-color);
    font-weight: 500;
    transition: all 0.2s ease;
}
.nav-link:hover {
    background-color: var(--light-bg);
    color: var(--dark-color);
}
.nav-link.active {
    background-color: var(--primary-light);
    color: var(--primary-color) !important;
    font-weight: 600;
}

@media (max-width: 768px) {
    .sidebar {
        position: fixed;
        left: -260px;
    }
    .sidebar.show {
        left: 0;
    }
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1040;
    }
    .sidebar-overlay.show {
        display: block;
    }
}

/* Cards & Tables */
.admin-card {
    background: #fff;
    border-radius: 12px;
    border: 1px solid var(--border-color);
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}
.table-responsive {
    border-radius: 8px;
}
.admin-table th {
    background-color: var(--light-bg);
    color: var(--secondary-color);
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--border-color);
    padding: 12px 16px;
    white-space: nowrap;
}
.admin-table td {
    padding: 16px;
    vertical-align: middle;
    border-bottom: 1px solid var(--border-color);
    color: var(--dark-color);
    font-size: 14px;
}
.admin-table tbody tr:hover {
    background-color: #f8fafc;
}

/* Buttons */
.btn-action {
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s;
}
.btn-action-view {
    color: var(--secondary-color);
    background: var(--light-bg);
    border: 1px solid var(--border-color);
}
.btn-action-view:hover {
    background: #e2e8f0;
    color: var(--dark-color);
}
.btn-action-edit {
    color: var(--primary-color);
    background: var(--primary-light);
    border: 1px solid transparent;
}
.btn-action-edit:hover {
    background: var(--primary-color);
    color: #fff;
}
.btn-action-delete {
    color: var(--danger-color);
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid transparent;
}
.btn-action-delete:hover {
    background: var(--danger-color);
    color: #fff;
}

/* Badges */
.admin-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}
.badge-success { background: rgba(16, 185, 129, 0.1); color: var(--success-color); }
.badge-warning { background: rgba(245, 158, 11, 0.1); color: var(--warning-color); }
.badge-danger { background: rgba(239, 68, 68, 0.1); color: var(--danger-color); }
.badge-neutral { background: var(--light-bg); color: var(--secondary-color); border: 1px solid var(--border-color); }

/* Filters & Inputs */
.admin-input {
    border-radius: 8px;
    border: 1px solid var(--border-color);
    padding: 8px 12px;
    font-size: 14px;
}
.admin-input:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px var(--primary-light);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 40px 20px;
}
.empty-state i {
    font-size: 48px;
    color: var(--border-color);
    margin-bottom: 16px;
}
.empty-state h5 {
    color: var(--dark-color);
    font-weight: 600;
}
.empty-state p {
    color: var(--secondary-color);
}

/* Pagination */
.admin-pagination .page-link {
    border: 1px solid var(--border-color);
    color: var(--secondary-color);
    margin: 0 2px;
    border-radius: 6px;
}
.admin-pagination .page-item.active .page-link {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}
"""

with open('/Users/tvan/Projects/GYMFINDER/admin/assets/admin.css', 'w') as f:
    f.write(css_content)

# --- 2. HTML LAYOUT TEMPLATE ---
def get_layout(page_id, page_title, main_content):
    menus = [
        ('dashboard', 'Tổng quan', 'fa-chart-pie', 'dashboard.html'),
        ('gyms', 'Phòng tập', 'fa-dumbbell', 'gyms.html'),
        ('trainers', 'Huấn luyện viên', 'fa-user-tie', 'trainers.html'),
        ('users', 'Người dùng', 'fa-users', 'users.html'),
        ('reviews', 'Đánh giá', 'fa-star', 'reviews.html'),
        ('categories', 'Danh mục', 'fa-list', 'categories.html')
    ]
    
    menu_html = ""
    for mid, mname, micon, mlink in menus:
        active = 'active' if mid == page_id else ''
        menu_html += f"""
                <li class="nav-item">
                    <a class="nav-link rounded-3 px-3 py-2 mb-1 {active}" href="{mlink}">
                        <i class="fa-solid {micon} me-3" style="width: 20px;"></i>{mname}
                    </a>
                </li>"""

    return f"""<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{page_title} - Admin GYMFINDER</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/components.css">
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body class="d-flex">
    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <aside class="sidebar d-flex flex-column" id="sidebar">
        <div class="p-4 border-bottom">
            <a class="d-flex align-items-center gap-2 text-decoration-none" href="../../index.html">
                <div class="border rounded d-flex align-items-center justify-content-center text-primary bg-white shadow-sm" style="width: 32px; height: 32px; border-color: #e2e8f0 !important;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="8" y1="12" x2="16" y2="12"></line>
                        <rect x="6" y="6" width="2" height="12" rx="1"></rect>
                        <rect x="3" y="8" width="2" height="8" rx="1"></rect>
                        <path d="M1.5 12h1.5"></path>
                        <rect x="16" y="6" width="2" height="12" rx="1"></rect>
                        <rect x="19" y="8" width="2" height="8" rx="1"></rect>
                        <path d="M21 12h1.5"></path>
                    </svg>
                </div>
                <span class="fw-bold text-dark" style="font-size: 18px;">GYM<span class="text-primary">FINDER</span></span>
            </a>
        </div>

        <div class="p-3 flex-grow-1 overflow-auto">
            <ul class="nav flex-column">{menu_html}
            </ul>
        </div>

        <div class="p-3 border-top bg-light">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <img src="https://ui-avatars.com/api/?name=Admin&background=00c9b1&color=fff&rounded=true" alt="Admin" width="36" height="36" class="rounded-circle">
                    <div>
                        <div class="fw-bold text-dark" style="font-size: 13px;" id="adminUsername">Admin</div>
                        <div class="text-secondary" style="font-size: 11px;">Administrator</div>
                    </div>
                </div>
                <button class="btn btn-sm btn-light border text-secondary" id="btnAdminLogout" title="Đăng xuất"><i class="fa-solid fa-arrow-right-from-bracket"></i></button>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content d-flex flex-column h-100 min-vh-100">
        <div class="bg-white border-bottom p-3 d-md-none d-flex align-items-center justify-content-between sticky-top shadow-sm">
            <span class="fw-bold text-dark" style="font-size: 18px;">GYM<span class="text-primary">FINDER</span> <span class="text-secondary fw-normal fs-6 ms-1">Admin</span></span>
            <button class="btn btn-light border" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
        </div>

        <div class="p-4 p-md-5 flex-grow-1 overflow-auto">
            {main_content}
        </div>
    </main>

    <!-- Shared Modal -->
    <div class="modal fade" id="sharedAdminModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" id="sharedModalTitle">Modal Title</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="sharedModalBody">
                    ...
                </div>
                <div class="modal-footer border-top-0 pt-0" id="sharedModalFooter">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-primary" id="sharedModalConfirm">Xác nhận</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Data -->
    <script src="../../data/categories.js"></script>
    <script src="../../data/gyms.js"></script>
    <script src="../../data/trainers.js"></script>
    <script src="../../data/users.js"></script>
    
    <!-- Admin Logic -->
    <script src="../assets/js/admin.js"></script>
</body>
</html>"""

def save_html(page_id, title, content):
    path = f'/Users/tvan/Projects/GYMFINDER/admin/pages/{page_id}.html'
    with open(path, 'w') as f:
        f.write(get_layout(page_id, title, content))

# --- DASHBOARD ---
dashboard_content = """
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0">Tổng quan</h2>
    <div class="text-secondary small"><i class="fa-regular fa-calendar me-2"></i>Tháng này</div>
</div>

<!-- Stats -->
<div class="row g-4 mb-4">
    <div class="col-6 col-md-3">
        <div class="admin-card h-100 p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="bg-primary-light text-primary rounded-3 p-2 d-inline-flex">
                    <i class="fa-solid fa-dumbbell fa-lg"></i>
                </div>
                <span class="badge badge-success">+12%</span>
            </div>
            <h6 class="text-secondary mb-1">Tổng phòng tập</h6>
            <h3 class="fw-bold mb-0" id="statGyms">0</h3>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="admin-card h-100 p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="bg-primary-light text-primary rounded-3 p-2 d-inline-flex">
                    <i class="fa-solid fa-user-tie fa-lg"></i>
                </div>
                <span class="badge badge-success">+5%</span>
            </div>
            <h6 class="text-secondary mb-1">Huấn luyện viên</h6>
            <h3 class="fw-bold mb-0" id="statTrainers">0</h3>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="admin-card h-100 p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="bg-primary-light text-primary rounded-3 p-2 d-inline-flex">
                    <i class="fa-solid fa-users fa-lg"></i>
                </div>
                <span class="badge badge-success">+18%</span>
            </div>
            <h6 class="text-secondary mb-1">Người dùng</h6>
            <h3 class="fw-bold mb-0" id="statUsers">0</h3>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="admin-card h-100 p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="bg-primary-light text-primary rounded-3 p-2 d-inline-flex">
                    <i class="fa-solid fa-star fa-lg"></i>
                </div>
                <span class="badge badge-success">+24%</span>
            </div>
            <h6 class="text-secondary mb-1">Đánh giá mới</h6>
            <h3 class="fw-bold mb-0" id="statReviews">0</h3>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Chart -->
    <div class="col-12 col-lg-7 d-flex flex-column">
        <div class="admin-card flex-grow-1 p-4 d-flex flex-column">
            <h6 class="fw-bold mb-4">Phòng tập theo khu vực</h6>
            <div class="position-relative flex-grow-1" style="min-height: 300px; width: 100%;">
                <canvas id="gymRegionChart"></canvas>
            </div>
        </div>
    </div>
    <!-- Recent Reviews -->
    <div class="col-12 col-lg-5">
        <div class="admin-card h-100 p-0 overflow-hidden d-flex flex-column">
            <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Đánh giá gần đây</h6>
                <a href="reviews.html" class="text-primary text-decoration-none small fw-semibold">Xem tất cả</a>
            </div>
            <div class="p-0 flex-grow-1 overflow-auto" id="recentReviewsList">
                <!-- Reviews populated by JS -->
            </div>
        </div>
    </div>
</div>

<!-- Featured Gyms -->
<div class="admin-card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h6 class="fw-bold mb-0">Phòng tập nổi bật</h6>
        <a href="gyms.html" class="text-primary text-decoration-none small fw-semibold">Quản lý</a>
    </div>
    <div class="row g-3" id="featuredGymsList">
        <!-- Gyms populated by JS -->
    </div>
</div>
"""
save_html('dashboard', 'Tổng quan', dashboard_content)

# --- GYMS ---
gyms_content = """
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0">Quản lý phòng tập</h2>
    <button class="btn btn-primary px-3 rounded-3" onclick="showModal('Thêm phòng tập', 'Chức năng thêm phòng tập đang phát triển.')"><i class="fa-solid fa-plus me-2"></i>Thêm mới</button>
</div>

<div class="admin-card p-3 mb-4">
    <div class="row g-3">
        <div class="col-12 col-md-4">
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary"></i>
                <input type="text" class="form-control admin-input ps-5" placeholder="Tìm kiếm phòng tập...">
            </div>
        </div>
        <div class="col-6 col-md-3">
            <select class="form-select admin-input">
                <option value="">Tất cả khu vực</option>
                <option value="Quận 1">Quận 1</option>
                <option value="Quận 3">Quận 3</option>
                <option value="Quận 7">Quận 7</option>
                <option value="Bình Thạnh">Bình Thạnh</option>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <select class="form-select admin-input">
                <option value="">Tất cả trạng thái</option>
                <option value="Hoạt động">Hoạt động</option>
                <option value="Tạm ẩn">Tạm ẩn</option>
            </select>
        </div>
        <div class="col-12 col-md-2">
            <button class="btn btn-light border w-100 text-secondary">Lọc</button>
        </div>
    </div>
</div>

<div class="admin-card overflow-hidden">
    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>Phòng tập</th>
                    <th>Khu vực</th>
                    <th>Danh mục</th>
                    <th>Giá</th>
                    <th>Đánh giá</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody id="gymTableBody">
                <!-- Data populated by JS -->
            </tbody>
        </table>
    </div>
    <div class="p-3 border-top d-flex justify-content-between align-items-center">
        <span class="text-secondary small">Hiển thị 1-10 của 12 phòng tập</span>
        <ul class="pagination pagination-sm admin-pagination mb-0">
            <li class="page-item disabled"><a class="page-link" href="#"><i class="fa-solid fa-angle-left"></i></a></li>
            <li class="page-item active"><a class="page-link" href="#">1</a></li>
            <li class="page-item"><a class="page-link" href="#">2</a></li>
            <li class="page-item"><a class="page-link" href="#"><i class="fa-solid fa-angle-right"></i></a></li>
        </ul>
    </div>
</div>
"""
save_html('gyms', 'Phòng tập', gyms_content)

# --- TRAINERS ---
trainers_content = """
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0">Quản lý huấn luyện viên</h2>
    <button class="btn btn-primary px-3 rounded-3" onclick="showModal('Thêm HLV', 'Chức năng thêm HLV đang phát triển.')"><i class="fa-solid fa-plus me-2"></i>Thêm mới</button>
</div>

<div class="admin-card p-3 mb-4">
    <div class="row g-3">
        <div class="col-12 col-md-5">
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary"></i>
                <input type="text" class="form-control admin-input ps-5" placeholder="Tìm kiếm HLV...">
            </div>
        </div>
        <div class="col-6 col-md-3">
            <select class="form-select admin-input">
                <option value="">Tất cả chuyên môn</option>
                <option value="Yoga">Yoga</option>
                <option value="Kickboxing">Kickboxing</option>
                <option value="Thể hình">Thể hình</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select class="form-select admin-input">
                <option value="">Trạng thái</option>
                <option value="Hoạt động">Hoạt động</option>
            </select>
        </div>
        <div class="col-12 col-md-2">
            <button class="btn btn-light border w-100 text-secondary">Lọc</button>
        </div>
    </div>
</div>

<div class="admin-card overflow-hidden">
    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>Huấn luyện viên</th>
                    <th>Chuyên môn</th>
                    <th>Phòng tập</th>
                    <th>Đánh giá</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody id="trainerTableBody">
                <!-- Data populated by JS -->
            </tbody>
        </table>
    </div>
    <div class="p-3 border-top d-flex justify-content-between align-items-center">
        <span class="text-secondary small">Hiển thị 1-10 của 12 HLV</span>
        <ul class="pagination pagination-sm admin-pagination mb-0">
            <li class="page-item disabled"><a class="page-link" href="#"><i class="fa-solid fa-angle-left"></i></a></li>
            <li class="page-item active"><a class="page-link" href="#">1</a></li>
            <li class="page-item"><a class="page-link" href="#">2</a></li>
            <li class="page-item"><a class="page-link" href="#"><i class="fa-solid fa-angle-right"></i></a></li>
        </ul>
    </div>
</div>
"""
save_html('trainers', 'Huấn luyện viên', trainers_content)

# --- USERS ---
users_content = """
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0">Quản lý người dùng</h2>
</div>

<div class="admin-card p-3 mb-4">
    <div class="row g-3">
        <div class="col-12 col-md-5">
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary"></i>
                <input type="text" class="form-control admin-input ps-5" placeholder="Tìm kiếm theo tên, email, sđt...">
            </div>
        </div>
        <div class="col-6 col-md-3">
            <select class="form-select admin-input">
                <option value="">Tất cả vai trò</option>
                <option value="Admin">Admin</option>
                <option value="User">User</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select class="form-select admin-input">
                <option value="">Trạng thái</option>
                <option value="Hoạt động">Hoạt động</option>
                <option value="Bị khóa">Bị khóa</option>
            </select>
        </div>
        <div class="col-12 col-md-2">
            <button class="btn btn-light border w-100 text-secondary">Lọc</button>
        </div>
    </div>
</div>

<div class="admin-card overflow-hidden">
    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>Họ tên</th>
                    <th>Email</th>
                    <th>Số điện thoại</th>
                    <th>Vai trò</th>
                    <th>Ngày tham gia</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody id="userTableBody">
                <!-- Data populated by JS -->
            </tbody>
        </table>
    </div>
    <div class="p-3 border-top d-flex justify-content-between align-items-center">
        <span class="text-secondary small">Hiển thị 1-10 của 12 người dùng</span>
        <ul class="pagination pagination-sm admin-pagination mb-0">
            <li class="page-item disabled"><a class="page-link" href="#"><i class="fa-solid fa-angle-left"></i></a></li>
            <li class="page-item active"><a class="page-link" href="#">1</a></li>
            <li class="page-item"><a class="page-link" href="#">2</a></li>
            <li class="page-item"><a class="page-link" href="#"><i class="fa-solid fa-angle-right"></i></a></li>
        </ul>
    </div>
</div>
"""
save_html('users', 'Người dùng', users_content)

# --- REVIEWS ---
reviews_content = """
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0">Quản lý đánh giá</h2>
</div>

<div class="admin-card p-3 mb-4">
    <div class="row g-3">
        <div class="col-12 col-md-6">
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary"></i>
                <input type="text" class="form-control admin-input ps-5" placeholder="Tìm kiếm nội dung đánh giá...">
            </div>
        </div>
        <div class="col-6 col-md-4">
            <select class="form-select admin-input">
                <option value="">Tất cả trạng thái</option>
                <option value="Chờ duyệt">Chờ duyệt</option>
                <option value="Đã duyệt">Đã duyệt</option>
                <option value="Từ chối">Từ chối</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <button class="btn btn-light border w-100 text-secondary">Lọc</button>
        </div>
    </div>
</div>

<div class="admin-card overflow-hidden">
    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead>
                <tr>
                    <th>Người dùng</th>
                    <th>Phòng tập</th>
                    <th>Đánh giá</th>
                    <th style="max-width: 300px;">Nội dung</th>
                    <th>Ngày</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody id="reviewTableBody">
                <!-- Data populated by JS -->
            </tbody>
        </table>
    </div>
    <div class="p-3 border-top d-flex justify-content-between align-items-center">
        <span class="text-secondary small">Hiển thị 1-10 của 15 đánh giá</span>
        <ul class="pagination pagination-sm admin-pagination mb-0">
            <li class="page-item disabled"><a class="page-link" href="#"><i class="fa-solid fa-angle-left"></i></a></li>
            <li class="page-item active"><a class="page-link" href="#">1</a></li>
            <li class="page-item"><a class="page-link" href="#">2</a></li>
            <li class="page-item"><a class="page-link" href="#"><i class="fa-solid fa-angle-right"></i></a></li>
        </ul>
    </div>
</div>
"""
save_html('reviews', 'Đánh giá', reviews_content)

# --- CATEGORIES ---
categories_content = """
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold mb-0">Quản lý danh mục</h2>
    <button class="btn btn-primary px-3 rounded-3" onclick="showModal('Thêm danh mục', 'Chức năng thêm danh mục đang phát triển.')"><i class="fa-solid fa-plus me-2"></i>Thêm mới</button>
</div>

<div class="admin-card p-3 mb-4">
    <div class="row g-3">
        <div class="col-12 col-md-6">
            <div class="position-relative">
                <i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-3 text-secondary"></i>
                <input type="text" class="form-control admin-input ps-5" placeholder="Tìm kiếm danh mục...">
            </div>
        </div>
    </div>
</div>

<div class="admin-card overflow-hidden">
    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>Tên danh mục</th>
                    <th>Mô tả</th>
                    <th class="text-center">Số phòng tập</th>
                    <th>Trạng thái</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody id="categoryTableBody">
                <!-- Data populated by JS -->
            </tbody>
        </table>
    </div>
</div>
"""
save_html('categories', 'Danh mục', categories_content)
