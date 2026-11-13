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
- Tìm kiếm / lọc / sắp xếp phòng tập & PT
- Chi tiết gym/PT + modal liên hệ (tự gọi/sao chép SĐT)
- Yêu thích, hồ sơ, đánh giá có kiểm duyệt
- Soft-gate Guest trên yêu thích/hồ sơ (CTA đăng nhập, không redirect cứng)
- Trợ lý AI (widget + `/ai/chat`, Groq, grounding catalog)
- Chuyển ngôn ngữ VI/EN



### Quản trị (Admin Portal)

- Dashboard chỉ số tổng quan
- Phòng tập / HLV: thêm, sửa, tạm ẩn / kích hoạt lại (soft-hide)
- Danh mục: loại phòng tập, chuyên môn, tiện ích, khu vực
- Người dùng: khóa / mở khóa; mời Admin (mật khẩu tạm 5 phút + buộc đổi MK)
- Kiểm duyệt đánh giá: duyệt / từ chối (bắt buộc lý do khi từ chối)



### Ngoài phạm vi

- Bản đồ / Google Maps chỉ đường
- Đặt lịch, thanh toán, ví điện tử
- Chat realtime user–PT; app native

---



## Công nghệ


| Tầng      | Công nghệ                                                       |
| --------- | --------------------------------------------------------------- |
| Giao diện | PHP SSR, HTML5, CSS3, Bootstrap 5.3, JavaScript, Font Awesome 6 |
| Backend   | PHP 8.0+ (thuần, không bắt buộc Composer)                       |
| CSDL      | **MySQL** (mặc định, XAMPP) — SQLite tuỳ chọn                   |
| API       | REST gateway `api/` (favorites, reviews, AI, …)                 |
| AI        | Groq Chat Completions (cấu hình `backend/ai.local.php`)         |
| Công cụ   | Git / GitHub, XAMPP, Docker / Railway (deploy demo)             |


```text
        Guest / User / Admin
                 │
        ┌────────┴────────┐
   User Portal       Admin Portal
   (pages/)          (admin/)
        └────────┬────────┘
           PHP SSR + API
                 │
         MySQL (XAMPP) / SQLite tuỳ chọn
```

---



## Cấu trúc thư mục

```text
GYMFINDER/
├── index.php                 # Trang chủ User Portal
├── router.php                # Router khi chạy: php -S … router.php
├── health.php                # Healthcheck (deploy)
├── favicon.ico
├── README.md
├── Dockerfile                # Build deploy Railway/Docker
├── .htaccess                 # Rewrite Apache (XAMPP)
├── .dockerignore
├── .env.example              # Mẫu biến môi trường (DB, AI, Google, …)
├── .gitignore
│
├── pages/                    # USER PORTAL (SSR)
│   ├── auth/
│   │   ├── login.php
│   │   ├── register.php
│   │   ├── logout.php
│   │   ├── forgot_password.php
│   │   ├── forgot_verify_otp.php
│   │   ├── reset_password.php
│   │   ├── google_start.php
│   │   └── google_callback.php
│   ├── gyms/
│   │   ├── search.php        # Tìm kiếm / lọc phòng tập
│   │   └── detail.php        # Chi tiết phòng tập
│   ├── trainers/
│   │   ├── index.php         # Danh sách HLV
│   │   └── detail.php        # Chi tiết HLV
│   ├── user/
│   │   ├── favorites.php     # Yêu thích (soft-gate Guest)
│   │   ├── favorite_toggle.php
│   │   └── profile.php
│   ├── ai/
│   │   └── chat.php          # Trang chat Trợ lý AI (/ai/chat)
│   └── legal/
│       ├── terms.php
│       ├── privacy.php
│       └── faq.php
│
├── admin/                    # ADMIN PORTAL
│   ├── pages/
│   │   ├── dashboard.php
│   │   ├── gyms.php
│   │   ├── trainers.php
│   │   ├── catalogs.php      # Danh mục hợp nhất
│   │   ├── categories.php    # (legacy / alias danh mục)
│   │   ├── users.php         # Khóa user + mời Admin
│   │   ├── reviews.php       # Duyệt / từ chối đánh giá
│   │   ├── profile.php
│   │   └── force_password.php # Đổi MK tạm lần đầu
│   ├── assets/
│   │   ├── admin.css
│   │   └── admin.js
│   └── components/
│       └── table.php
│
├── api/
│   ├── index.php             # REST entry: auth, favorites, reviews, AI, …
│   └── .htaccess
│
├── backend/                  # Logic + DB
│   ├── bootstrap.php         # Session, config, PDO, CSRF, i18n, admin gate
│   ├── config.php
│   ├── env.php
│   ├── db.php
│   ├── auth.php
│   ├── google_auth.php
│   ├── helpers.php
│   ├── i18n.php
│   ├── mailer.php
│   ├── ai_client.php         # Groq recommend + rate-limit
│   ├── sqlite_install.php    # Cài SQLite + seed (tuỳ chọn)
│   ├── ai.local.php.example  # Mẫu key AI (file thật gitignore)
│   ├── google.local.php.example
│   ├── smtp.local.php.example
│   ├── repositories/         # Truy vấn PDO
│   │   ├── gym_repository.php
│   │   ├── trainer_repository.php
│   │   ├── favorite_repository.php
│   │   ├── review_repository.php
│   │   ├── ai_chat_repository.php
│   │   └── common_repository.php
│   └── resources/            # Nghiệp vụ API / Admin
│       ├── common.php
│       ├── review_resource.php
│       ├── upload_resource.php
│       ├── admin_resource.php
│       ├── admin_dashboard_resource.php
│       ├── admin_gym_resource.php
│       ├── admin_trainer_resource.php
│       ├── admin_category_resource.php
│       ├── admin_user_resource.php
│       └── admin_review_resource.php
│
├── includes/                 # Partial HTML dùng chung
│   ├── layout_head.php
│   ├── header.php
│   ├── footer.php
│   ├── logo.php
│   ├── gym_card.php
│   ├── trainer_card.php
│   ├── trainer_gyms_ui.php
│   ├── pagination.php
│   ├── rating_star_filter.php
│   ├── contact_modal.php
│   ├── ui_feedback.php
│   ├── ai_widget.php         # Widget AI trang chủ
│   └── admin_layout.php
│
├── assets/                   # Tài nguyên tĩnh User Portal
│   ├── css/
│   │   ├── style.css
│   │   ├── components.css
│   │   ├── auth.css
│   │   ├── ai_widget.css
│   │   └── ai_chat.css
│   ├── js/
│   │   ├── auth_validate.js
│   │   ├── form_validate.js
│   │   ├── rating-filter.js
│   │   ├── ui-feedback.js
│   │   ├── ai_widget.js
│   │   └── ai_chat.js
│   └── images/
│       ├── favicon.svg
│       ├── gym-placeholder.svg
│       ├── gyms/             # Ảnh gym seed (runtime/uploads bổ sung)
│       └── trainers/         # Ảnh HLV seed (trainer-1 … trainer-30)
│
├── lang/                     # i18n EN (VI = chuỗi mặc định trong trang)
│   ├── en.php
│   └── en_pages.php
│
├── database/
│   ├── schema.sql            # MySQL 16 bảng
│   ├── seed.sql              # Dữ liệu mẫu TP.HCM
│   ├── migrations/           # Các bản vá schema theo sprint
│   └── deploy_infinityfree/  # Schema/seed rút gọn (hosting phụ)
│
├── docker/
│   └── apache-start.sh       # Entrypoint Apache trên container
│
├── uploads/                  # Ảnh upload runtime (gitignore)
│
└── docs/                     # Tài liệu đồ án (không phải mã chạy)
    ├── PROJECT_STRUCTURE.md
    ├── specs/
    │   ├── GYMFINDER_dacta.docx
    │   ├── GYMFINDER_CSDL.docx
    │   ├── GymFinder_Report.docx          # Báo cáo đồ án hiện tại
    │   ├── GymFinder_HuongDanChay.md
    │   ├── GymFinder_HuongDan_Railway.md
    │   ├── GymFinder_KichBanDemo_Local_Deploy.md
    │   ├── GymFinder_AI_Chat_Sprints.md
    │   ├── GymFinder_QuyDinh_Anh_Va_Form.md
    │   ├── figures/                       # Hình UML + screenshot Ch.5
    │   │   ├── uc_*.png
    │   │   ├── activity_*.png
    │   │   └── ch5/
    │   └── tdtu_logo.jpg|png
    └── qa/
        ├── TEST_PLAN.md
        ├── TEST_CASES.md
        ├── TEST_EXECUTION_REPORT.md
        ├── BUG_REPORT.md
        ├── _ai_smoke.php
        ├── _full_site_smoke.php
        └── _round3_smoke.php
```

Chi tiết thêm: `[docs/PROJECT_STRUCTURE.md](docs/PROJECT_STRUCTURE.md)`.

---



## Khởi chạy nhanh (XAMPP + MySQL)

1. Bật **Apache** + **MySQL** trong XAMPP.
2. phpMyAdmin: tạo DB `gymfinder` → import `database/schema.sql` rồi `database/seed.sql`.
3. Symlink/copy project vào `htdocs/GYMFINDER`.
4. Mở [http://localhost/GYMFINDER/](http://localhost/GYMFINDER/).

`backend/config.php` mặc định `driver = mysql` (user `root`, mật khẩu rỗng).

Hoặc chạy PHP built-in vẫn dùng MySQL XAMPP:

```bash
cd /path/to/GYMFINDER
php -S localhost:8000 router.php
# macOS + PHP từ XAMPP:
# /Applications/XAMPP/xamppfiles/bin/php -S localhost:8000 router.php
```

→ [http://localhost:8000](http://localhost:8000)

Chi tiết, SQLite tuỳ chọn, AI/Google, xử lý sự cố: `[docs/specs/GymFinder_HuongDanChay.md](docs/specs/GymFinder_HuongDanChay.md)`.  
Deploy Railway: `[docs/specs/GymFinder_HuongDan_Railway.md](docs/specs/GymFinder_HuongDan_Railway.md)`.

### Tài khoản demo


| Vai trò | Email                | Mật khẩu   |
| ------- | -------------------- | ---------- |
| Admin   | `u01@gymfinder.test` | `password` |
| User    | `u02@gymfinder.test` | `password` |


---



## Tài liệu dự án


| Tài liệu                                                                               | Mô tả                     |
| -------------------------------------------------------------------------------------- | ------------------------- |
| `[docs/PROJECT_STRUCTURE.md](docs/PROJECT_STRUCTURE.md)`                               | Bản đồ thư mục            |
| `[docs/specs/GymFinder_Report.docx](docs/specs/GymFinder_Report.docx)`                 | Báo cáo đồ án (đủ chương) |
| `[docs/specs/GYMFINDER_dacta.docx](docs/specs/GYMFINDER_dacta.docx)`                   | Đặc tả yêu cầu & phạm vi  |
| `[docs/specs/GYMFINDER_CSDL.docx](docs/specs/GYMFINDER_CSDL.docx)`                     | Thiết kế CSDL             |
| `[docs/specs/GymFinder_HuongDanChay.md](docs/specs/GymFinder_HuongDanChay.md)`         | Hướng dẫn chạy local      |
| `[docs/specs/GymFinder_HuongDan_Railway.md](docs/specs/GymFinder_HuongDan_Railway.md)` | Hướng dẫn deploy Railway  |
| `[docs/qa/TEST_PLAN.md](docs/qa/TEST_PLAN.md)`                                         | Kế hoạch kiểm thử QA      |
| `[docs/qa/TEST_CASES.md](docs/qa/TEST_CASES.md)`                                       | Bộ test case              |
| `[docs/qa/TEST_EXECUTION_REPORT.md](docs/qa/TEST_EXECUTION_REPORT.md)`                 | Báo cáo thực thi          |
| `[docs/qa/BUG_REPORT.md](docs/qa/BUG_REPORT.md)`                                       | Danh sách lỗi / rủi ro    |


---

## Giấy phép / đồ án

Đồ án / dự án học thuật — sử dụng theo quy định của trường và nhóm phát triển.