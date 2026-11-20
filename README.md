# GYMFINDER

Website tìm kiếm, đánh giá phòng tập và huấn luyện viên cá nhân (PT) tại **TP. Hồ Chí Minh**.

GYMFINDER là nền tảng **tra cứu và đánh giá** — người dùng tự liên hệ phòng tập/PT qua thông tin được công bố. Hệ thống **không** bao gồm đặt lịch, thanh toán, chat realtime hay bản đồ/chỉ đường.

**Demo online (Railway):** [https://gymfinder-wed.up.railway.app](https://gymfinder-wed.up.railway.app)

---

## Vai trò


| Vai trò   | Quyền chính                                                                                           |
| --------- | ----------------------------------------------------------------------------------------------------- |
| **Guest** | Xem nội dung công khai; tìm kiếm/lọc phòng tập & PT; xem chi tiết và đánh giá đã duyệt; hỏi Trợ lý AI |
| **User**  | Thêm quyền Guest; hồ sơ cá nhân; yêu thích; gửi/sửa/xóa đánh giá của mình; lưu phiên chat AI          |
| **Admin** | Dashboard; quản lý phòng tập, PT, danh mục; mời Admin; khóa/mở khóa user; kiểm duyệt đánh giá         |


---



## Chức năng chính



### Người dùng (User Portal)

- Đăng ký / đăng nhập (email, SĐT hoặc tên tài khoản), ghi nhớ đăng nhập, đăng xuất
- Đăng nhập / đăng ký Google OAuth (khi đã cấu hình)
- Quên mật khẩu OTP qua email (3 bước: email → OTP 5 phút → mật khẩu mới)
- Tìm kiếm / lọc / sắp xếp phòng tập & PT (gồm lọc theo **đặc điểm** phòng tập)
- Chi tiết gym/PT + modal liên hệ (tự gọi/sao chép SĐT)
- Yêu thích, hồ sơ, đánh giá có kiểm duyệt
- Soft-gate Guest trên yêu thích/hồ sơ (CTA đăng nhập, không redirect cứng)
- Trợ lý AI (widget + `/ai/chat`, Groq, grounding catalog)
- Chuyển ngôn ngữ VI/EN



### Quản trị (Admin Portal)

- Dashboard chỉ số tổng quan
- Phòng tập / HLV: thêm, sửa, tạm ẩn / kích hoạt lại (soft-hide); xóa cứng phòng tập khi chưa gắn HLV / xóa cứng HLV khi chưa gắn phòng tập (`gym_trainers` trống); gắn đặc điểm (`features`) cho gym
- Danh mục: loại phòng tập, chuyên môn, tiện ích, **đặc điểm**, khu vực
- Người dùng: khóa / mở khóa; mời Admin (mật khẩu tạm 5 phút + buộc đổi MK)
- Kiểm duyệt đánh giá: duyệt / từ chối (bắt buộc lý do khi từ chối)



### Ngoài phạm vi

- Bản đồ / Google Maps chỉ đường
- Đặt lịch, thanh toán, ví điện tử
- Chat realtime user–PT; app native

---



## Tech Stack


| Tầng                     | Công nghệ                                                                       |
| ------------------------ | ------------------------------------------------------------------------------- |
| **Frontend**             | PHP SSR, HTML5, CSS3, **Bootstrap 5.3**, JavaScript (vanilla), Font Awesome 6   |
| **Backend**              | **PHP 8.0+** (khuyến nghị 8.2), thuần — không bắt buộc Composer                 |
| **CSDL**                 | **MySQL** / MariaDB (mặc định, XAMPP) — SQLite tuỳ chọn (`GF_DB_DRIVER=sqlite`) |
| **Truy cập dữ liệu**     | PDO (`pdo_mysql` / `pdo_sqlite`), repositories + resources                      |
| **API**                  | REST gateway `api/` (auth, favorites, reviews, upload, AI, …)                   |
| **Auth**                 | Session + CSRF; OTP email (SMTP); Google OAuth 2.0 + PKCE; remember-me tokens   |
| **AI**                   | Groq Chat Completions (`GF_AI_`*), grounding catalog gym/PT                     |
| **Internationalization** | VI mặc định trong trang; EN qua `lang/en.php`, `lang/en_pages.php`              |
| **Local**                | XAMPP (Apache + MySQL) hoặc `php -S … router.php`                               |
| **Deploy**               | Docker (`Dockerfile` ở root, PHP 8.2 Apache) → **Railway** + MySQL plugin       |
| **Cấu hình**             | `.env` / Railway Variables (`GF_DB_`*,* `GF_AI_`, `GF_SMTP_`*, `GF_GOOGLE_*`)   |
| **Công cụ**              | Git / GitHub                                                                    |


```text
        Guest / User / Admin
                 │
        ┌────────┴────────┐
   User Portal       Admin Portal
   (pages/)          (admin/)
        └────────┬────────┘
      bootstrap.php → PHP SSR + REST api/
                 │
         MySQL (XAMPP / Railway)  ·  SQLite tuỳ chọn
                 │
         Groq · SMTP · Google OAuth (tuỳ cấu hình)
```

---

## Cấu trúc thư mục

Repo tách **mã nguồn** và **báo cáo**:

```text
GYMFINDER/
├── README.md
├── Dockerfile                # Build Railway/Docker — COPY source/
├── .dockerignore
├── .gitignore
│
├── source/                   # TOÀN BỘ MÃ CHẠY (document root)
│   ├── index.php
│   ├── router.php            # php -S … router.php
│   ├── health.php
│   ├── .htaccess
│   ├── .env.example          # Copy → source/.env
│   ├── pages/                # User Portal
│   ├── admin/                # Admin Portal
│   ├── api/                  # REST gateway
│   ├── backend/              # Logic + PDO + AI + auth
│   ├── includes/
│   ├── assets/
│   ├── lang/
│   ├── database/             # schema.sql, seed.sql, migrations/
│   ├── docker/
│   └── uploads/              # Runtime (gitignore)
│
└── report/                   # BÁO CÁO 
    └── GymFinder_Report.docx
```

---

## Khởi chạy nhanh (XAMPP + MySQL)

1. Bật **Apache** + **MySQL** trong XAMPP.
2. phpMyAdmin: tạo DB `gymfinder` → import `source/database/schema.sql` rồi `source/database/seed.sql`.
3. Symlink **thư mục** `source/` vào htdocs:

```bash
ln -sfn ~/Projects/GYMFINDER/source /Applications/XAMPP/xamppfiles/htdocs/GYMFINDER
```

1. Mở [http://localhost/GYMFINDER/](http://localhost/GYMFINDER/).

Cấu hình: copy `source/.env.example` → `source/.env` (AI / SMTP / Google).  
`backend/config.php` mặc định MySQL `root` / mật khẩu rỗng.

DB cũ thiếu `features` / `gym_features`: chạy `source/database/migrations/010_features.mysql.sql`, hoặc import lại schema + seed.

Hoặc PHP built-in (vẫn dùng MySQL XAMPP):

```bash
cd /path/to/GYMFINDER/source
php -S localhost:8000 router.php
# macOS + PHP từ XAMPP:
# /Applications/XAMPP/xamppfiles/bin/php -S localhost:8000 router.php
```

→ [http://localhost:8000](http://localhost:8000)

Deploy Railway: `Dockerfile` ở root (`COPY source/`); Variables `GF_DB_*`, `GF_AI_API_KEY`, …

### Tài khoản demo


| Vai trò     | Email                | Mật khẩu   | Ghi chú                                   |
| ----------- | -------------------- | ---------- | ----------------------------------------- |
| Admin       | `u01@gymfinder.test` | `password` | Dashboard / quản trị                      |
| User        | `u02@gymfinder.test` | `password` | Favorites, review, hồ sơ                  |
| User (khóa) | `u25@gymfinder.test` | `password` | Tài khoản `locked` — không đăng nhập được |


Đồng bộ nhanh 3 tài khoản: `source/database/seed_demo_users.sql`.

---



## Tài liệu dự án


| Tài liệu                                                       | Mô tả                          |
| -------------------------------------------------------------- | ------------------------------ |
| `[report/GymFinder_Report.docx](report/GymFinder_Report.docx)` | Báo cáo đồ án (đủ chương + PL) |


---



## Giấy phép / đồ án

Đồ án / dự án học thuật — sử dụng theo quy định của trường và nhóm phát triển.