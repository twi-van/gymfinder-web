# GYMFINDER — Cấu trúc thư mục

**Cập nhật:** 2026-10-19

Nhìn theo **vai trò**, không cần nhớ từng file.

---

## Bản đồ nhanh

| Thư mục | Vai trò |
| --- | --- |
| `pages/` | Trang người dùng (Guest / User) |
| `admin/` | Cổng quản trị |
| `api/` | REST API entry |
| `backend/` | Logic + DB (auth, repository, resource) |
| `includes/` | Partial HTML dùng chung (header, card, layout) |
| `assets/` | CSS / JS / ảnh tĩnh |
| `database/` | Schema, seed, ERD |
| `uploads/` | Ảnh upload runtime (gitignore) |
| `docs/` | Tài liệu đồ án (không chạy app) |

**Entry chạy app:** `index.php` · `router.php` · `.htaccess`

---

## Cây thư mục

```text
GYMFINDER/
├── index.php              # Trang chủ
├── router.php             # Router PHP built-in server
├── .htaccess              # Rewrite Apache
├── README.md
├── favicon.ico
│
├── pages/                 # USER PORTAL
│   ├── auth/              # login, register, quên MK (OTP)
│   ├── gyms/              # tìm kiếm + chi tiết phòng tập
│   ├── trainers/          # danh sách + chi tiết HLV
│   ├── user/              # hồ sơ, yêu thích
│   └── legal/             # điều khoản, chính sách
│
├── admin/                 # ADMIN PORTAL
│   ├── pages/             # dashboard, gyms, trainers, …
│   ├── assets/            # admin.css / admin.js
│   └── components/        # helper bảng
│
├── api/                   # /api/* → index.php
│
├── backend/               # TẦNG XỬ LÝ
│   ├── bootstrap.php      # khởi tạo session, config, PDO
│   ├── config.php · db.php · auth.php · helpers.php
│   ├── sqlite_install.php # tạo DB + seed lần đầu (SQLite)
│   ├── repositories/      # truy vấn SQL (PDO)
│   └── resources/         # nghiệp vụ API & admin
│
├── includes/              # partial UI dùng chung
├── assets/                # css/ · js/ · images/
├── database/              # schema.sql · seed.sql · ERD.png
├── uploads/               # runtime (không commit)
│
└── docs/                  # TÀI LIỆU (không phải mã chạy)
    ├── PROJECT_STRUCTURE.md   # file này
    ├── specs/                 # đặc tả, CSDL, mô tả, hướng dẫn chạy
    └── qa/                    # test plan, cases, báo cáo, bugs
```

---

## Luồng request

```text
Trình duyệt
    → router.php / .htaccess
    → pages/*  hoặc  admin/pages/*  hoặc  api/
         → backend/bootstrap.php
         → backend/repositories/*   (đọc/ghi DB)
         → includes/*               (render HTML, trừ API)
```

```text
/api/*
    → api/index.php
    → backend/resources/*
    → JSON
```

---

## `docs/` chi tiết

| Đường dẫn | Nội dung |
| --- | --- |
| `docs/specs/GYMFINDER_dacta.docx` | Đặc tả yêu cầu |
| `docs/specs/GYMFINDER_CSDL.docx` | Thiết kế CSDL |
| `docs/specs/GymFinder_MoTaDuAn.docx` | Mô tả dự án |
| `docs/specs/GymFinder_BaoCao_Khung.docx` | Khung báo cáo đồ án (điền dần) |
| `docs/specs/GymFinder_HuongDanChay.md` | Hướng dẫn chạy |
| `docs/specs/GymFinder_QuyDinh_Anh_Va_Form.md` | Quy định ảnh gym/PT + validate form (cho team) |
| `docs/qa/` | Kế hoạch / case / báo cáo thực thi / bug |
| `docs/PROJECT_STRUCTURE.md` | Cấu trúc thư mục |

---

## Stack

| Tầng | Công nghệ |
| --- | --- |
| UI | PHP SSR, Bootstrap 5.3, CSS, JS, Font Awesome 6 |
| Backend | PHP 8.0+ (không bắt buộc Composer) |
| DB | SQLite (mặc định) / MySQL (`database/schema.sql`) |
| Bảo mật | PDO prepared statements, CSRF, password hash, phân quyền role |
