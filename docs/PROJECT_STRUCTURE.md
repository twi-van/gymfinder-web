# GYMFINDER — Project Structure

Tài liệu mô tả chức năng các thư mục và file chính trong project GYMFINDER sau khi tinh gọn cấu trúc.  
**Cập nhật:** 2026-10-13

---

## Cấu trúc thư mục thực tế

```text
GYMFINDER/
├── .gitignore                         # Bỏ qua: .sqlite, uploads/, node_modules/, .DS_Store, .env, file tạm Office
├── .htaccess                          # Rewrite rules cho Apache (mod_rewrite)
├── .oxlintrc.json                     # Cấu hình linter JS
├── README.md                          # Giới thiệu dự án, hướng dẫn chạy
├── router.php                         # PHP built-in server router (php -S localhost:8000 router.php)
├── index.php                          # Trang chủ PHP (kết nối DB, hiển thị gym/trainer nổi bật)
├── favicon.ico                        # Favicon root domain
│
├── api/
│   ├── .htaccess                      # Route tất cả request qua index.php
│   └── index.php                      # REST API Gateway: /api/gyms, /api/trainers, /api/favorites, /api/reviews, ...
│
├── backend/
│   ├── bootstrap.php                  # Khởi tạo: session, require config, db, helpers, auth, repositories, resources
│   ├── config.php                     # Cấu hình DB (SQLite mặc định / MySQL qua env vars)
│   ├── db.php                         # Kết nối PDO singleton (SQLite & MySQL)
│   ├── auth.php                       # Session, login, logout, phân quyền user/admin, password hash
│   ├── helpers.php                    # Hàm tiện ích: gf_url(), gf_h(), gf_logo(), gf_format_price(),
│   │                                  #   gf_csrf_*(), gf_like_contains(), gf_json(), gf_api_error(), ...
│   ├── sqlite_install.php             # Tự động tạo bảng + seed data SQLite khi khởi chạy lần đầu (gồm password_reset_otps)
│   ├── repositories/                  # Tầng Data Access Layer (100% PDO Prepared Statements & Transactions)
│   │   ├── common_repository.php      # Query quận huyện, danh mục, tiện ích, chuyên môn
│   │   ├── gym_repository.php         # CRUD + filter tìm kiếm phòng gym đa tiêu chí
│   │   ├── trainer_repository.php     # CRUD + filter huấn luyện viên
│   │   ├── favorite_repository.php    # Quản lý yêu thích theo User ID
│   │   └── review_repository.php      # Đánh giá: tạo mới, duyệt/từ chối, tính lại avg_rating
│   └── resources/                     # Tầng Business Logic Layer (xử lý nghiệp vụ API & Admin)
│       ├── common.php                 # Validation tham số, phân trang, serialize dữ liệu
│       ├── upload_resource.php        # Upload ảnh: magic bytes, giới hạn 5MB, dọn dẹp file mồ côi
│       ├── review_resource.php        # Nghiệp vụ gửi, sửa, xóa review của người dùng
│       ├── admin_resource.php         # Loader tập trung cho các handler của admin
│       ├── admin_dashboard_resource.php   # Thống kê số liệu dashboard admin
│       ├── admin_gym_resource.php     # CRUD & ẩn phòng gym phía admin
│       ├── admin_trainer_resource.php # CRUD & ẩn huấn luyện viên phía admin
│       ├── admin_category_resource.php    # Quản lý danh mục phía admin
│       ├── admin_review_resource.php  # Kiểm duyệt đánh giá (duyệt, từ chối kèm lý do)
│       └── admin_user_resource.php    # Quản lý người dùng, khóa/mở khóa tài khoản
│
├── database/
│   ├── schema.sql                     # DDL MySQL đầy đủ: bảng cốt lõi + password_reset_otps, FK, chỉ mục, CHECK
│   ├── seed.sql                       # Dữ liệu mẫu khởi tạo cho MySQL
│   ├── ERD.png                        # Sơ đồ quan hệ thực thể CSDL chuẩn
│   └── gymfinder.sqlite               # CSDL SQLite runtime demo (được .gitignore bảo vệ)
│
├── includes/                          # Components giao diện dùng chung cho Web PHP
│   ├── layout_head.php                # <head> dùng chung: Bootstrap, Font Awesome, Inter, CSS, favicon
│   ├── header.php                     # Thanh điều hướng chính (logo chuẩn, menu, user dropdown)
│   ├── footer.php                     # Footer chung + modal đăng nhập + AJAX toggle yêu thích
│   ├── logo.php                       # Component Logo GYMFINDER chuẩn hóa (SVG inline quả tạ teal)
│   ├── gym_card.php                   # Component thẻ phòng gym SSR
│   ├── trainer_card.php               # Component thẻ huấn luyện viên SSR
│   └── admin_layout.php               # Layout khung bao bọc Admin Portal (sidebar + header admin)
│
├── pages/                             # Các trang Web PHP người dùng (SSR + Session)
│   ├── auth/
│   │   ├── login.php                  # Đăng nhập PHP (POST, CSRF, session an toàn)
│   │   ├── register.php               # Đăng ký PHP (validate, password_hash, lưu DB)
│   │   ├── forgot_password.php        # Quên mật khẩu bước 1: yêu cầu OTP
│   │   ├── forgot_verify_otp.php      # Quên mật khẩu bước 2: nhập OTP 6 số
│   │   ├── reset_password.php         # Quên mật khẩu bước 3: đặt mật khẩu mới
│   │   └── logout.php                 # Đăng xuất PHP (hủy session an toàn)
│   ├── gyms/
│   │   ├── search.php                 # Tìm kiếm & lọc gym PHP (quận, giá, rating, tiện ích)
│   │   └── detail.php                 # Chi tiết gym PHP (thông tin, HLV liên kết, reviews đã duyệt)
│   ├── trainers/
│   │   ├── index.php                  # Danh sách & lọc HLV PHP
│   │   └── detail.php                 # Chi tiết HLV PHP & form gửi đánh giá
│   └── user/
│       ├── favorites.php              # Xem danh sách gym & HLV yêu thích từ DB
│       ├── profile.php                # Hồ sơ cá nhân, đổi mật khẩu, upload avatar
│       └── favorite_toggle.php        # Endpoint fallback xử lý toggle yêu thích
│
├── admin/                             # Khu vực quản trị Admin Portal PHP
│   ├── assets/
│   │   └── admin.css                  # CSS giao diện quản trị Admin
│   ├── components/
│   │   └── table.php                  # Component helper render bảng dữ liệu dùng chung
│   └── pages/
│       ├── dashboard.php              # Dashboard admin: 4 chỉ số thực tế, biểu đồ, review mới
│       ├── gyms.php                   # Quản lý phòng gym: danh sách, thêm/sửa, tải ảnh
│       ├── trainers.php               # Quản lý HLV: danh sách, thêm/sửa, phân bổ phòng tập
│       ├── categories.php             # Quản lý danh mục phòng tập
│       ├── reviews.php                # Kiểm duyệt đánh giá (chờ duyệt, duyệt, từ chối, xóa)
│       ├── users.php                  # Quản lý tài khoản người dùng, khóa/mở khóa
│       └── profile.php                # Hồ sơ cá nhân quản trị viên: cập nhật thông tin, đổi mật khẩu
│
├── assets/                            # Tài nguyên tĩnh hoạt động (Production Assets)
│   ├── css/
│   │   ├── style.css                  # CSS toàn hệ thống: typography, navbar, hero, cards
│   │   ├── components.css             # CSS components: badges, buttons, cards, logo
│   │   └── auth.css                   # CSS riêng trang login/register
│   └── images/
│       ├── favicon.svg                # Vector icon quả tạ teal nhận diện thương hiệu
│       ├── favicon-32x32.png          # Favicon kích thước 32x32px
│       ├── gyms/                      # Thư mục ảnh phòng tập
│       └── trainers/                  # Thư mục ảnh huấn luyện viên
│
├── uploads/                           # Ảnh người dùng tải lên runtime (được .gitignore bảo vệ)
├── DOCS/                              # Hồ sơ tài liệu thiết kế & đồ án
│   ├── GYMFINDER_CSDL.docx            # Thiết kế CSDL chi tiết (gồm password_reset_otps)
│   ├── GYMFINDER_dacta.docx           # Đặc tả yêu cầu hệ thống
│   ├── GymFinder_MoTaDuAn.docx        # Mô tả dự án tổng hợp
│   ├── GymFinder_HuongDanChay.md      # Hướng dẫn chạy & kiểm thử
│   └── PROJECT_STRUCTURE.md           # Tài liệu cấu trúc thư mục này
```

---

## Luồng hoạt động hệ thống

### 1. PHP Web Portal (Ứng dụng chính)

```text
Trình duyệt gửi Request (GET/POST)
    → router.php / .htaccess           (Điều hướng request tới file tương ứng)
    → pages/*/[page].php               (Mã nguồn trang — require bootstrap.php)
       → backend/bootstrap.php         (Khởi tạo session, config, PDO, CSRF token)
       → backend/repositories/*.php    (Truy vấn SQL an toàn qua PDO Prepared Statements)
       → includes/[component].php      (Render HTML SSR: layout_head, header, logo, footer)
```

### 2. RESTful API Gateway

```text
Request /api/*
    → api/index.php                    (API Router: validate JSON/Form, kiểm tra CSRF & Role)
       → backend/resources/*.php       (Xử lý Business Logic: upload, review, admin CRUD)
       → backend/repositories/*.php    (Data Access Layer thực thi DB transaction)
       → JSON Response                 (Trả về JSON HTTP 200, 201, 400, 401, 403, 404, 422)
```

---

## Stack công nghệ

| Thành phần | Công nghệ sử dụng |
|---|---|
| Frontend | HTML5, Vanilla CSS, Bootstrap 5.3, Font Awesome 6, Google Fonts Inter |
| Backend | PHP 8.0+ (hỗ trợ cả PHP Built-in Server và Apache/XAMPP) |
| Database | SQLite (chạy mặc định tiện lợi) / MySQL (hỗ trợ sẵn qua schema.sql) |
| Bảo mật | PDO Prepared Statements chống SQL Injection, CSRF Token, Password Hash, Phân quyền Role, Upload Magic Bytes |
| Favicon | Icon quả tạ Teal nền trắng đa kích cỡ (`favicon.svg`, `favicon-32x32.png`, `favicon.ico`) |

---

## Ghi chú các điểm chuẩn hóa kiến trúc

1. **Thống nhất Components:** Toàn bộ components giao diện PHP được tập trung tại [`includes/`](file:///Users/tvan/Projects/GYMFINDER/includes) (`layout_head.php`, `header.php`, `footer.php`, `logo.php`, `gym_card.php`, `trainer_card.php`, `admin_layout.php`). Đã xóa bỏ thư mục con lồng nhau `includes/components/`.
2. **Không còn prototype HTML tĩnh:** Thư mục `prototypes/` đã được gỡ; giao diện chạy thật chỉ dùng PHP SSR (`pages/`, `admin/`, `includes/`).
3. **Không có mã mồ côi:** Loại bỏ các file trùng lặp hoặc không sử dụng (`assets/images/favicon.png`, `assets/js/main.js`).
