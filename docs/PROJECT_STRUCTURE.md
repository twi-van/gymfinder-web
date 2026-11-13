# GYMFINDER — Cấu trúc thư mục

**Cập nhật:** 2026-11-10

Nhìn theo **vai trò**, không cần nhớ từng file. Cây đầy đủ có chú thích cũng nằm trong [`README.md`](../README.md).

---

## Bản đồ nhanh

| Thư mục | Vai trò |
| --- | --- |
| `pages/` | Trang người dùng (Guest / User) + AI chat + legal |
| `admin/` | Cổng quản trị |
| `api/` | REST API entry |
| `backend/` | Logic + DB (auth, Google, AI, repository, resource) |
| `includes/` | Partial HTML dùng chung (header, card, widget AI, layout) |
| `assets/` | CSS / JS / ảnh tĩnh |
| `lang/` | Từ điển giao diện EN |
| `database/` | Schema, seed, migrations |
| `docker/` | Script khởi động Apache trên container |
| `uploads/` | Ảnh upload runtime (gitignore) |
| `docs/` | Tài liệu đồ án (không chạy app) |

**Entry chạy app:** `index.php` · `router.php` · `.htaccess` · `health.php` (deploy)

---

## Cây thư mục (chi tiết)

```text
GYMFINDER/
├── index.php                 # Trang chủ
├── router.php                # Router PHP built-in server
├── health.php                # Healthcheck deploy
├── favicon.ico
├── README.md
├── Dockerfile
├── .htaccess
├── .env.example
│
├── pages/                    # USER PORTAL
│   ├── auth/                 # login, register, OTP, Google OAuth, logout
│   ├── gyms/                 # search + detail
│   ├── trainers/             # index + detail
│   ├── user/                 # profile, favorites, favorite_toggle
│   ├── ai/                   # chat.php → /ai/chat
│   └── legal/                # terms, privacy, faq
│
├── admin/                    # ADMIN PORTAL
│   ├── pages/                # dashboard, gyms, trainers, catalogs,
│   │                         # users, reviews, profile, force_password
│   ├── assets/               # admin.css / admin.js
│   └── components/           # table.php
│
├── api/                      # /api/* → index.php
│
├── backend/
│   ├── bootstrap.php         # session, config, PDO, CSRF, i18n, admin gate
│   ├── config.php · env.php · db.php · auth.php · helpers.php
│   ├── google_auth.php · mailer.php · i18n.php · ai_client.php
│   ├── sqlite_install.php
│   ├── *.local.php.example   # AI / Google / SMTP (file thật gitignore)
│   ├── repositories/         # SQL PDO
│   └── resources/            # nghiệp vụ API & admin
│
├── includes/                 # header, footer, cards, ai_widget, admin_layout…
├── assets/                   # css/ · js/ · images/
├── lang/                     # en.php · en_pages.php
├── database/                 # schema.sql · seed.sql · migrations/ · deploy_infinityfree/
├── docker/                   # apache-start.sh
├── uploads/                  # runtime (không commit)
│
└── docs/
    ├── PROJECT_STRUCTURE.md
    ├── specs/                # đặc tả, báo cáo, hướng dẫn, figures/
    └── qa/                   # test plan / cases / smoke scripts
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
    → backend/resources/*  (+ ai_client cho /api/ai/*)
    → JSON
```

---

## Stack

| Tầng | Công nghệ |
| --- | --- |
| UI | PHP SSR, Bootstrap 5.3, CSS, JS, Font Awesome 6 |
| Backend | PHP 8.0+ (không bắt buộc Composer) |
| DB | **MySQL** (mặc định, XAMPP) / SQLite tuỳ chọn |
| AI | Groq (OpenAI-compatible), widget + `/ai/chat` |
| Bảo mật | PDO prepared statements, CSRF, password hash, RBAC, OAuth PKCE |
