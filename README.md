# GYMFINDER

Website tìm kiếm, đánh giá phòng tập và huấn luyện viên cá nhân (PT) tại **TP. Hồ Chí Minh**.

GYMFINDER là nền tảng **tra cứu và đánh giá** — người dùng tự liên hệ phòng tập/PT qua thông tin được công bố. Hệ thống **không** bao gồm đặt lịch, thanh toán, chat hay bản đồ/chỉ đường.

---

## Vai trò

| Vai trò | Quyền chính |
| --- | --- |
| **Guest** | Xem nội dung công khai; tìm kiếm/lọc phòng tập & PT; xem chi tiết và đánh giá đã duyệt |
| **User** | Thêm quyền Guest; hồ sơ cá nhân; yêu thích; gửi/sửa/xóa đánh giá của mình |
| **Admin** | Dashboard; quản lý phòng tập, PT, danh mục; khóa/mở khóa user; kiểm duyệt đánh giá |

---

## Chức năng chính

### Người dùng (User Portal)

- Đăng ký (email bắt buộc, SĐT tuỳ chọn; đồng ý Điều khoản & Chính sách), đăng nhập bằng email/SĐT, ghi nhớ đăng nhập, đăng xuất
- Quên mật khẩu OTP qua email (không SMS/SĐT): 3 bước UI (email → OTP → mật khẩu mới), lưu bảng `password_reset_otps` (gửi SMTP thuộc bổ sung)
- Tìm kiếm / lọc / sắp xếp phòng tập (khu vực, giá, đánh giá, loại hình, tiện ích)
- Xem chi tiết phòng tập: mô tả, địa chỉ văn bản, ảnh, giá, giờ hoạt động, tiện ích, HLV liên kết, đánh giá đã duyệt
- Tìm kiếm và xem hồ sơ PT; hỗ trợ nhiều chuyên môn và nhiều phòng tập theo thiết kế (UI sẵn sàng nhận nhiều liên kết)
- Lưu yêu thích phòng tập & PT
- Hồ sơ cá nhân, avatar, đổi mật khẩu
- Gửi đánh giá (chờ Admin duyệt trước khi công khai)

### Quản trị (Admin Portal)

- Dashboard chỉ số tổng quan
- Phòng tập / HLV: thêm, sửa, **tạm ẩn / kích hoạt lại** (không xóa cứng trên giao diện)
- Gán chuyên môn và liên kết phòng tập cho HLV trên cùng hồ sơ
- Danh mục hợp nhất: loại phòng tập, chuyên môn HLV, tiện ích, khu vực
- Người dùng: **khóa / mở khóa** (không xóa tài khoản trên UI)
- Kiểm duyệt đánh giá: duyệt / từ chối (bắt buộc nhập lý do khi từ chối)

### Ngoài phạm vi (theo đặc tả)

- Bản đồ, “xem đường đi”, Google Maps
- Đặt lịch, thanh toán, ví điện tử, đơn hàng
- Chat / nhắn tin trong ứng dụng
- Ứng dụng mobile native; chatbot / AI phức tạp

---

## Công nghệ

| Tầng | Công nghệ |
| --- | --- |
| Giao diện | PHP SSR, HTML5, CSS3, Bootstrap 5.3, JavaScript, Font Awesome 6, Google Fonts (Inter) |
| Backend | PHP 8.0+ (thuần, không bắt buộc Composer) |
| CSDL | SQLite (mặc định khi chạy local) hoặc MySQL |
| API | REST gateway `api/` (favorites, reviews, …) |
| Công cụ | Git / GitHub, VS Code / Cursor, XAMPP (tuỳ chọn) |

```text
        Guest / User / Admin
                 │
        ┌────────┴────────┐
   User Portal       Admin Portal
   (pages/)          (admin/)
        └────────┬────────┘
              PHP SSR + API
                 │
         SQLite / MySQL
```

---

## Khởi chạy nhanh

```bash
cd /path/to/GYMFINDER
php -S localhost:8000 router.php
# macOS + XAMPP:
# /Applications/XAMPP/xamppfiles/bin/php -S localhost:8000 router.php
```

Mở [http://localhost:8000](http://localhost:8000). Lần đầu chạy, hệ thống tự tạo SQLite và nạp dữ liệu mẫu.

Chi tiết MySQL/XAMPP, URL trang, tài khoản demo và xử lý sự cố: xem [`docs/specs/GymFinder_HuongDanChay.md`](docs/specs/GymFinder_HuongDanChay.md).

### Tài khoản demo

| Vai trò | Email | Mật khẩu |
| --- | --- | --- |
| Admin | `u01@gymfinder.test` | `password` |
| User | `u02@gymfinder.test` | `password` |

---

## Tài liệu dự án

| Tài liệu | Mô tả |
| --- | --- |
| [`docs/PROJECT_STRUCTURE.md`](docs/PROJECT_STRUCTURE.md) | Bản đồ thư mục (đọc trước) |
| [`docs/specs/GYMFINDER_dacta.docx`](docs/specs/GYMFINDER_dacta.docx) | Đặc tả yêu cầu & phạm vi |
| [`docs/specs/GYMFINDER_CSDL.docx`](docs/specs/GYMFINDER_CSDL.docx) | Thiết kế CSDL |
| [`docs/specs/GymFinder_MoTaDuAn.docx`](docs/specs/GymFinder_MoTaDuAn.docx) | Mô tả dự án (cho giảng viên / Google Docs) |
| [`docs/specs/GymFinder_BaoCao_Khung.docx`](docs/specs/GymFinder_BaoCao_Khung.docx) | Khung báo cáo đồ án (6 chương — điền dần) |
| [`docs/specs/GymFinder_HuongDanChay.md`](docs/specs/GymFinder_HuongDanChay.md) | Hướng dẫn chạy & kiểm thử |
| [`docs/qa/TEST_PLAN.md`](docs/qa/TEST_PLAN.md) | Kế hoạch kiểm thử QA |
| [`docs/qa/TEST_CASES.md`](docs/qa/TEST_CASES.md) | Bộ test case |
| [`docs/qa/TEST_EXECUTION_REPORT.md`](docs/qa/TEST_EXECUTION_REPORT.md) | Báo cáo thực thi |
| [`docs/qa/BUG_REPORT.md`](docs/qa/BUG_REPORT.md) | Danh sách lỗi / rủi ro |

---

## Giấy phép / đồ án

Đồ án / dự án học thuật — sử dụng theo quy định của trường và nhóm phát triển.
