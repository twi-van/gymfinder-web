<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
$pageTitle = 'Chính sách bảo mật - GYMFINDER';
$bodyClass = 'bg-light d-flex flex-column min-vh-100';
require dirname(__DIR__, 2) . '/includes/layout_head.php';
require dirname(__DIR__, 2) . '/includes/header.php';
?>
<main class="flex-grow-1">
    <div class="container py-5" style="max-width:820px">
        <p class="text-secondary small mb-2">Cập nhật: tháng 10/2026</p>
        <h1 class="fw-bold mb-3">Chính sách bảo mật</h1>
        <p class="text-secondary mb-4">Chính sách này mô tả cách GYMFINDER thu thập, sử dụng và bảo vệ thông tin cá nhân trên website.</p>

        <h2 class="h5 fw-bold mt-4 mb-2">1. Dữ liệu thu thập</h2>
        <p class="text-secondary">Khi đăng ký hoặc cập nhật hồ sơ, hệ thống có thể lưu họ tên, email, số điện thoại (nếu cung cấp), ảnh đại diện, danh sách yêu thích và nội dung đánh giá. Phiên đăng nhập dùng cookie phiên; nếu bật “Ghi nhớ đăng nhập”, cookie phiên có thể được giữ tối đa khoảng 30 ngày.</p>

        <h2 class="h5 fw-bold mt-4 mb-2">2. Mục đích sử dụng</h2>
        <p class="text-secondary">Dữ liệu dùng để xác thực tài khoản, hiển thị hồ sơ, lưu yêu thích, kiểm duyệt đánh giá và vận hành quản trị. GYMFINDER không bán dữ liệu cá nhân cho bên thứ ba.</p>

        <h2 class="h5 fw-bold mt-4 mb-2">3. Bảo mật</h2>
        <p class="text-secondary">Mật khẩu được lưu dưới dạng mã hóa một chiều. Truy cập quản trị được giới hạn theo vai trò. Bạn nên đăng xuất trên thiết bị dùng chung.</p>

        <h2 class="h5 fw-bold mt-4 mb-2">4. Quyền của bạn</h2>
        <p class="text-secondary">Bạn có thể xem và cập nhật thông tin hồ sơ khi đã đăng nhập. Việc khóa tài khoản (nếu cần) do quản trị viên thực hiện; hệ thống ưu tiên khóa/ẩn thay vì xóa cứng dữ liệu vận hành.</p>

        <h2 class="h5 fw-bold mt-4 mb-2">5. Liên hệ</h2>
        <p class="text-secondary mb-0">Nếu cần hỗ trợ về dữ liệu cá nhân, hãy liên hệ quản trị viên qua kênh hỗ trợ trên website.</p>
    </div>
</main>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
