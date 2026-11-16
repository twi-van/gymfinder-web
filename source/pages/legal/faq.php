<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';
$pageTitle = gf_t('Hỏi đáp - GYMFINDER');
$bodyClass = 'bg-light d-flex flex-column min-vh-100';
$faqs = [
    [
        'q' => 'GYMFINDER dùng để làm gì?',
        'a' => 'Website giúp bạn tìm và so sánh phòng tập cùng huấn luyện viên tại TP. Hồ Chí Minh theo khu vực, giá, tiện ích, đánh giá và bộ môn. Bạn có thể lưu yêu thích và xem đánh giá của hội viên.',
    ],
    [
        'q' => 'Tôi có đặt lịch hoặc thanh toán trên website được không?',
        'a' => 'Không. GYMFINDER không đặt lịch, không thu phí tập và không thanh toán online. Sau khi chọn phòng tập hoặc huấn luyện viên, hãy liên hệ trực tiếp bằng số điện thoại được công bố.',
    ],
    [
        'q' => 'Giá trên trang có phải giá chính thức không?',
        'a' => 'Đây là khoảng giá tham khảo để so sánh. Giá, lịch và điều kiện tập có thể thay đổi, nên hãy xác nhận lại với phòng tập hoặc huấn luyện viên trước khi đăng ký.',
    ],
    [
        'q' => 'Làm sao tìm phòng tập theo quận hoặc mức giá?',
        'a' => 'Vào mục Phòng tập, nhập tên hoặc bộ môn, rồi lọc theo khu vực, khoảng giá, đánh giá, đặc điểm và tiện ích. Có thể chọn mức có sẵn hoặc nhập giá từ–đến.',
    ],
    [
        'q' => 'Mục “Có thể bạn cũng quan tâm” chọn phòng tập dựa vào đâu?',
        'a' => 'Đó là các phòng tập khác trong cùng quận với phòng bạn đang xem, tối đa 4 phòng. Danh sách không gồm phòng tập hiện tại.',
    ],
    [
        'q' => 'Gym phổ thông và gym cao cấp khác nhau thế nào?',
        'a' => 'Đó là danh mục do quản trị viên gắn cho từng phòng tập, không phải một mức giá cố định. Mỗi phòng có khoảng giá riêng.',
    ],
    [
        'q' => 'Làm sao lưu phòng tập hoặc huấn luyện viên yêu thích?',
        'a' => 'Đăng nhập bằng tài khoản hội viên rồi bấm biểu tượng trái tim trên thẻ hoặc trang chi tiết. Tài khoản quản trị không dùng danh sách yêu thích.',
    ],
    [
        'q' => 'Đánh giá của tôi có hiện ngay không?',
        'a' => 'Chưa. Đánh giá mới ở trạng thái chờ duyệt và chỉ công khai sau khi quản trị viên chấp nhận. Nếu bạn sửa, đánh giá quay lại chờ duyệt. Nếu bị từ chối, lý do hiện trên đánh giá của bạn.',
    ],
    [
        'q' => 'Quên mật khẩu thì làm thế nào?',
        'a' => 'Chọn Quên mật khẩu, nhập email đã đăng ký và xác nhận mã OTP 6 số gửi qua email. Mã có hiệu lực 5 phút và chỉ dùng một lần. Hệ thống không gửi OTP qua SMS.',
    ],
    [
        'q' => 'Trợ lý AI có nhớ cuộc trò chuyện không?',
        'a' => 'Khách xem lịch sử trong phiên trình duyệt hiện tại. Hội viên đã đăng nhập có thể giữ nhiều phiên, mở lại hoặc xóa; mỗi phiên tối đa 30 lượt và mỗi tài khoản lưu tới 20 phiên.',
    ],
    [
        'q' => 'Đổi tiếng Việt và tiếng Anh ở đâu?',
        'a' => 'Bấm VI hoặc EN trên thanh menu (trang đăng nhập thì ở góc trên). Lựa chọn được ghi nhớ cho các lần sau.',
    ],
    [
        'q' => 'Website có phòng tập ngoài TP. Hồ Chí Minh không?',
        'a' => 'Không. Dữ liệu hiện chỉ gồm phòng tập và huấn luyện viên tại TP. Hồ Chí Minh.',
    ],
];
require dirname(__DIR__, 2) . '/includes/layout_head.php';
require dirname(__DIR__, 2) . '/includes/header.php';
?>
<main class="flex-grow-1">
    <div class="container py-5" style="max-width:820px">
        <p class="text-secondary small mb-2"><?= gf_h(gf_t('Cập nhật: tháng 10/2026')) ?></p>
        <h1 class="fw-bold mb-3"><?= gf_h(gf_t('Hỏi đáp')) ?></h1>
        <p class="text-secondary mb-4"><?= gf_h(gf_t('Những câu hỏi người dùng thường gặp khi tìm phòng tập, lưu yêu thích, đánh giá và dùng trợ lý AI.')) ?></p>
        <div class="accordion" id="gfFaq">
            <?php foreach ($faqs as $i => $item): ?>
                <div class="accordion-item border-0 mb-3 shadow-sm rounded-4 overflow-hidden">
                    <h2 class="accordion-header" id="gfFaqH<?= $i ?>">
                        <button class="accordion-button <?= $i === 0 ? '' : 'collapsed' ?> fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#gfFaqC<?= $i ?>" aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>" aria-controls="gfFaqC<?= $i ?>">
                            <?= gf_h(gf_t($item['q'])) ?>
                        </button>
                    </h2>
                    <div id="gfFaqC<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" aria-labelledby="gfFaqH<?= $i ?>" data-bs-parent="#gfFaq">
                        <div class="accordion-body text-secondary"><?= gf_h(gf_t($item['a'])) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>
<?php require dirname(__DIR__, 2) . '/includes/footer.php'; ?>
