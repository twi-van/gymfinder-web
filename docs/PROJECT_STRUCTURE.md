# GYMFINDER — Project Structure

Tài liệu mô tả chức năng các thư mục và file chính trong project GYMFINDER.
**Cập nhật:** 2026-10-13

---

## Cấu trúc thư mục thực tế

```text
GYMFINDER/
├── .gitignore                         # Bỏ qua: .sqlite, uploads/, node_modules/, .DS_Store
├── .htaccess                          # Rewrite rules cho Apache (mod_rewrite)
├── .oxlintrc.json                     # Cấu hình linter JS
├── README.md                          # Giới thiệu dự án, hướng dẫn chạy
├── router.php                         # PHP built-in server router (php -S localhost:8000 router.php)
├── index.php                          # Trang chủ PHP (kết nối DB, hiển thị gym cards)
├── index.html                         # Prototype HTML tĩnh của trang chủ
│
├── api/
│   ├── .htaccess                      # Route tất cả request qua index.php
│   └── index.php                      # REST API: /api/gyms, /api/trainers, /api/favorites, /api/reviews, ...
│
├── backend/
│   ├── bootstrap.php                  # Khởi tạo: require config, db, helpers, auth
│   ├── config.php                     # Cấu hình DB (SQLite mặc định / MySQL qua env vars)
│   ├── db.php                         # Kết nối PDO singleton (SQLite & MySQL)
│   ├── auth.php                       # Session, login, logout, phân quyền user/admin
│   ├── helpers.php                    # Hàm tiện ích: gf_url(), gf_h(), gf_logo(),
│   │                                  #   gf_cover_image(), gf_avatar(), gf_csrf_*(),
│   │                                  #   gf_resolve_image_url(), gf_like_contains(), ...
│   ├── sqlite_install.php             # Tạo bảng + seed data SQLite lần đầu chạy
│   ├── repositories/
│   │   ├── common_repository.php      # Query dùng chung: phân trang, tìm kiếm
│   │   ├── gym_repository.php         # CRUD + filter phòng gym
│   │   ├── trainer_repository.php     # CRUD + filter huấn luyện viên
│   │   ├── favorite_repository.php    # Yêu thích: thêm/xóa/lấy danh sách
│   │   └── review_repository.php      # Đánh giá: tạo mới, duyệt/từ chối, thống kê
│   └── resources/
│       ├── common.php                 # Response helpers: gf_json(), gf_redirect(), ...
│       ├── admin_resource.php         # Middleware xác thực admin
│       ├── admin_dashboard_resource.php   # API thống kê dashboard
│       ├── admin_gym_resource.php     # CRUD gym (admin)
│       ├── admin_trainer_resource.php # CRUD trainer (admin)
│       ├── admin_user_resource.php    # Quản lý user, ban/unban (admin)
│       ├── admin_category_resource.php    # Quản lý danh mục (admin)
│       ├── admin_review_resource.php  # Duyệt / từ chối review (admin)
│       ├── review_resource.php        # Gửi review (user)
│       └── upload_resource.php        # Upload ảnh gym/trainer/avatar
│
├── database/
│   ├── schema.sql                     # DDL MySQL đầy đủ: bảng, FK, index, CHECK
│   ├── seed.sql                       # Dữ liệu mẫu: gym, trainer, user, quận, danh mục, review
│   └── ERD.png                        # Sơ đồ quan hệ thực thể
│
├── includes/
│   ├── layout_head.php                # <head> dùng chung: Bootstrap, Font Awesome, Inter, CSS
│   ├── header.php                     # Thanh điều hướng chính (logo, nav, user dropdown)
│   ├── footer.php                     # Footer chung
│   ├── admin_layout.php               # Layout bọc trang admin: sidebar + main content
│   ├── gym_card.php                   # Component thẻ phòng gym
│   ├── trainer_card.php               # Component thẻ huấn luyện viên
│   └── components/
│       └── logo.php                   # Logo GYMFINDER chuẩn hóa (SVG inline + typography)
│
├── pages/
│   ├── auth/
│   │   ├── login.php                  # Đăng nhập PHP (POST, CSRF, session)
│   │   ├── login.html                 # Prototype HTML tĩnh login
│   │   ├── register.php               # Đăng ký PHP (validate, bcrypt, insert user)
│   │   ├── register.html              # Prototype HTML tĩnh register
│   │   └── logout.php                 # Đăng xuất (hủy session, POST + CSRF)
│   ├── gyms/
│   │   ├── search.php                 # Tìm kiếm & lọc gym PHP (quận, giá, rating, danh mục)
│   │   ├── search.html                # Prototype HTML tĩnh tìm kiếm
│   │   ├── detail.php                 # Chi tiết gym PHP (ảnh, trainer, review)
│   │   └── detail.html                # Prototype HTML tĩnh gym detail
│   ├── trainers/
│   │   ├── index.php                  # Danh sách HLV PHP (lọc, phân trang)
│   │   ├── index.html                 # Prototype HTML tĩnh trainer list
│   │   ├── detail.php                 # Chi tiết HLV PHP (thông tin, chuyên môn, gym)
│   │   └── detail.html                # Prototype HTML tĩnh trainer detail
│   └── user/
│       ├── profile.php                # Hồ sơ cá nhân PHP (xem/sửa, đổi mật khẩu, review)
│       ├── profile.html               # Prototype HTML tĩnh profile
│       ├── favorites.php              # Danh sách yêu thích PHP (gym + trainer)
│       ├── favorites.html             # Prototype HTML tĩnh favorites
│       └── favorite_toggle.php        # Xử lý POST toggle yêu thích
│
├── admin/
│   ├── assets/
│   │   ├── admin.css                  # CSS riêng khu vực Admin
│   │   └── js/
│   │       └── admin.js               # JS riêng Admin (datatable, modal, form)
│   ├── components/
│   │   ├── sidebar.html               # Sidebar điều hướng Admin (HTML prototype)
│   │   ├── mobile_header.html         # Header mobile Admin (HTML prototype)
│   │   └── table.php                  # Component bảng dùng chung trong PHP Admin pages
│   └── pages/
│       ├── dashboard.php              # Dashboard PHP: thống kê, top gym/trainer, review gần đây
│       ├── dashboard.html             # Prototype HTML tĩnh dashboard
│       ├── gyms.php                   # Quản lý gym PHP: danh sách, thêm/sửa/xóa/ẩn
│       ├── gyms.html                  # Prototype HTML tĩnh quản lý gym
│       ├── trainers.php               # Quản lý HLV PHP: CRUD, gán gym, ảnh
│       ├── trainers.html              # Prototype HTML tĩnh quản lý HLV
│       ├── users.php                  # Quản lý người dùng PHP: danh sách, ban/unban
│       ├── users.html                 # Prototype HTML tĩnh quản lý user
│       ├── categories.php             # Quản lý danh mục PHP: CRUD, icon FA
│       ├── categories.html            # Prototype HTML tĩnh quản lý category
│       ├── reviews.php                # Quản lý đánh giá PHP: duyệt, từ chối, xóa
│       └── reviews.html               # Prototype HTML tĩnh quản lý review
│
├── assets/
│   ├── css/
│   │   ├── style.css                  # CSS hệ thống: design tokens, typography, navbar, hero, cards
│   │   ├── components.css             # CSS components tái sử dụng: badges, skeleton, cards
│   │   └── auth.css                   # CSS riêng trang login/register
│   ├── images/
│   │   ├── gyms/                      # Ảnh gym (placeholder)
│   │   └── trainers/                  # Ảnh trainer (placeholder)
│   └── js/
│       ├── load-components.js         # Load HTML components động cho prototype HTML
│       ├── main.js                    # JS chung: khởi tạo Bootstrap, global event
│       ├── core/
│       │   ├── auth.js                # Auth state: login/logout, user dropdown, admin badge
│       │   └── ui.js                  # UI helpers: toast, loading, modal
│       └── pages/
│           ├── auth-login.js          # Logic trang login (HTML prototype)
│           ├── auth-register.js       # Logic trang register (HTML prototype)
│           ├── gym-search.js          # Filter, search, sort gym (PHP & HTML)
│           ├── gym-detail.js          # Gallery, reviews, actions
│           ├── trainer.js             # Filter, list trainer
│           ├── trainer-detail.js      # Chi tiết HLV, yêu thích
│           ├── favorites.js           # Toggle yêu thích, sync localStorage (HTML prototype)
│           ├── home.js                # Logic trang chủ HTML
│           └── profile.js             # Cập nhật hồ sơ, preview avatar (HTML prototype)
│
├── components/                        # HTML components cho prototype HTML tĩnh
│   ├── header.html                    # Header prototype (load bằng load-components.js)
│   ├── footer.html                    # Footer prototype
│   └── login-required-modal.html      # Modal yêu cầu đăng nhập
│
├── data/                              # Mock data JS (prototype HTML tĩnh, không dùng khi có DB)
│   ├── gyms.js
│   ├── trainers.js
│   ├── categories.js
│   ├── reviews.js
│   └── users.js
│
└── uploads/                           # Ảnh user upload (runtime — KHÔNG commit vào git)
```

---

## Luồng hoạt động

### PHP Backend (chính)

```
Browser Request
    → router.php                     (PHP built-in dev server routing)
    → pages/*/[page].php             (UI page — require bootstrap.php)
       → backend/bootstrap.php       (config → db → helpers → auth)
       → backend/repositories/*.php  (SQL queries qua PDO)
       → includes/[component].php    (render HTML)
```

### REST API

```
/api/*  → api/index.php
          → backend/resources/[resource].php
          → backend/repositories/[repository].php
          → JSON response
```

### HTML Prototype (tĩnh)

```
[page].html
    → load-components.js    (inject header.html, footer.html)
    → auth.js               (localStorage auth state)
    → data/[entity].js      (mock data)
    → pages/[page].js       (page logic)
```

---

## Stack công nghệ

| Lớp | Công nghệ |
|---|---|
| Frontend | HTML5, Vanilla CSS, JavaScript (ES6+), jQuery |
| CSS Framework | Bootstrap 5.3 |
| Icons | Font Awesome 6 |
| Fonts | Google Fonts — Inter |
| Backend | PHP 8.0+ (built-in server / Apache) |
| Database | SQLite (dev mặc định) / MySQL (production) |
| ORM/Query | PDO với prepared statements |
| Auth | PHP Session + CSRF token |
| Upload | PHP `move_uploaded_file()`, validate MIME |

---

## Ghi chú quan trọng

| Ký hiệu | Ý nghĩa |
|---|---|
| `*.php` | Trang PHP thực tế, kết nối database, xử lý auth/session |
| `*.html` | Prototype HTML tĩnh, dùng mock data JS, không kết nối DB |
| `includes/` | PHP components tái sử dụng (layout, header, footer, card) |
| `components/` | HTML components cho prototype (load bằng JS) |
| `uploads/` | Thư mục runtime — **không commit vào git** |
| `database/*.sqlite` | File DB local — **không commit vào git** |

- Mọi câu truy vấn động đều dùng **PDO prepared statements** (chống SQL Injection)
- CSRF token bắt buộc với tất cả POST form
- Logo GYMFINDER được chuẩn hóa qua `includes/components/logo.php` (SVG inline)
