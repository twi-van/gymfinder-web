# HƯỚNG DẪN KHỞI CHẠY & VẬN HÀNH

**Dự án:** GYMFINDER — Tìm kiếm, đánh giá phòng tập & HLV  
**Cập nhật:** 2026-10-21  
**Phạm vi:** Website SSR PHP — **không** maps / đặt lịch / chat / thanh toán.

---

## 1. Yêu cầu môi trường

| Thành phần | Yêu cầu |
| --- | --- |
| PHP | **8.0+** (khuyến nghị 8.1 / 8.2) — extension `pdo_sqlite` (mặc định) hoặc `pdo_mysql` |
| CSDL | **SQLite** (tự tạo khi chạy local) hoặc **MySQL** / MariaDB |
| Khác | Không bắt buộc Node.js / Composer |
| Trình duyệt | Chrome, Edge, Safari, Firefox |
| OS | macOS, Windows, Linux |

---

## 2. Cách 1 — PHP built-in server + SQLite (khuyến nghị)

Ưu điểm: không cần XAMPP/MySQL. Lần đầu (hoặc khi chưa có file DB) hệ thống **tự tạo** `database/gymfinder.sqlite`, đủ **15 bảng** (gồm `gym_trainers`, `trainer_specialties`, `password_reset_otps`) và nạp dữ liệu mẫu.

### Bước 1 — Vào thư mục gốc dự án

```bash
cd /path/to/GYMFINDER
```

### Bước 2 — Chạy server

```bash
php -S localhost:8000 router.php
```

macOS + PHP từ XAMPP:

```bash
/Applications/XAMPP/xamppfiles/bin/php -S localhost:8000 router.php
```

### Bước 3 — Mở trình duyệt

[http://localhost:8000](http://localhost:8000)

### Reset DB SQLite (khi schema đổi / lỗi thiếu bảng N–N)

SQLite **chỉ seed khi file mới hoặc gần rỗng**. Nếu máy còn file cũ lệch schema:

```bash
# Dừng server (Ctrl+C), rồi:
rm -f database/gymfinder.sqlite database/gymfinder.sqlite-*
php -S localhost:8000 router.php
```

Lần request tiếp theo sẽ tạo lại DB + seed.

---

## 3. Cách 2 — XAMPP (Apache) + MySQL

Phù hợp khi cần demo đúng MySQL theo đồ án.

### Bước 1 — Apache + MySQL

Bật **Apache** và **MySQL** trong XAMPP Control Panel.

### Bước 2 — Tạo database

phpMyAdmin → [http://localhost/phpmyadmin](http://localhost/phpmyadmin)

- Tên: `gymfinder`
- Collation: `utf8mb4_unicode_ci`

### Bước 3 — Import (đúng thứ tự)

1. `database/schema.sql` — cấu trúc (15 bảng, CHECK, FK, N–N)
2. `database/seed.sql` — dữ liệu mẫu

Nếu DB MySQL đã có từ bản cũ, xem thêm `database/migrations/` (ví dụ `005_users_email_not_null.mysql.sql`).

### Bước 4 — Đặt mã nguồn

Copy / symlink thư mục dự án vào `htdocs` (ví dụ `htdocs/GYMFINDER`).

### Bước 5 — Bật driver MySQL

Mặc định `backend/config.php` dùng **SQLite**. Với XAMPP + MySQL cần chuyển driver:

**Tuỳ chọn A — biến môi trường** (khuyến nghị khi chạy built-in server với MySQL):

```bash
export GF_DB_DRIVER=mysql
export GF_DB_HOST=127.0.0.1
export GF_DB_NAME=gymfinder
export GF_DB_USER=root
export GF_DB_PASS=
php -S localhost:8000 router.php
```

**Tuỳ chọn B — Apache/XAMPP:** trong `backend/config.php` tạm đổi:

```php
'driver' => getenv('GF_DB_DRIVER') ?: 'mysql',  // local demo MySQL
```

(Không commit thay đổi này nếu nhóm đang mặc định SQLite.)

### Bước 6 — Truy cập

- Apache: [http://localhost/GYMFINDER/](http://localhost/GYMFINDER/)  
- Hoặc vẫn dùng `php -S … router.php` với `GF_DB_DRIVER=mysql`.

---

## 4. Trợ lý AI chat (bổ sung)

Gợi ý **phòng tập** và **HLV (PT)** bằng chat tiếng Việt. **Không bắt buộc** cho đặc tả lõi.

```bash
cp backend/ai.local.php.example backend/ai.local.php
# Mở ai.local.php → dán API key Groq/Gemini (không commit file này)
```

| URL | Mô tả |
| --- | --- |
| [/pages/ai/chat.php](http://localhost:8000/pages/ai/chat.php) | Hỏi → câu trả lời + card gym và/hoặc HLV thật trong DB |
| Trang chủ | Nút chat AI góc phải (widget) → panel hỏi nhanh |

Menu **Trợ lý AI** trên navbar (user/guest). Admin portal-only không thấy menu này.

Câu demo: “Gym Quận 7 giá dưới 500 nghìn” · “HLV Yoga rating cao” · “PT giảm cân cho người mới”.

---

## 5. Quên mật khẩu / OTP email

Luồng OTP (3 bước) thuộc chức năng chính. **Demo/chấm local:** `'demo_otp' => true` → hiện mã trên UI, **không gửi mail** (ưu tiên hơn SMTP). **Gửi mail thật:** cấu hình SMTP và `'demo_otp' => false`.

```bash
cp backend/smtp.local.php.example backend/smtp.local.php
```

Chỉnh `backend/smtp.local.php` (file này **đã gitignore — không commit**):

| Mục | Ý nghĩa |
| --- | --- |
| `demo_otp` => `true` | Hiện OTP trên trang verify (không cần đọc inbox) |
| `demo_otp` => `false` + SMTP đủ | Gửi OTP qua email thật |

---

## 6. Tài khoản demo (khớp `seed.sql` / SQLite install)

Mật khẩu chung seed: **`password`**

| Vai trò | Email | Mật khẩu | Ghi chú |
| --- | --- | --- | --- |
| **Admin** | `u01@gymfinder.test` | `password` | Dashboard, gym/PT, catalogs, users, reviews |
| **User** | `u02@gymfinder.test` | `password` | Favorites, review, hồ sơ |
| **User (khóa)** | `u25@gymfinder.test` | `password` | Kiểm thử tài khoản `locked` |
| **Guest** | — | — | Không đăng nhập: chỉ xem công khai |

User khác trong seed: `u03@…` … `u24@…` — cùng mật khẩu `password`.

> Không dùng `admin@gymfinder.vn` / `Admin@123456` trừ khi máy bạn đã tạo tay — **không có trong seed hiện tại**.

---

## 7. URL chính (port 8000)

| Chức năng | URL |
| --- | --- |
| Trang chủ | [/](http://localhost:8000/) |
| Tìm phòng tập | [/pages/gyms/search.php](http://localhost:8000/pages/gyms/search.php) |
| Chi tiết gym | [/pages/gyms/detail.php?id=1](http://localhost:8000/pages/gyms/detail.php?id=1) |
| Danh sách HLV | [/pages/trainers/index.php](http://localhost:8000/pages/trainers/index.php) |
| Chi tiết HLV | [/pages/trainers/detail.php?id=1](http://localhost:8000/pages/trainers/detail.php?id=1) |
| Đăng nhập / Đăng ký | [/pages/auth/login.php](http://localhost:8000/pages/auth/login.php) · [/pages/auth/register.php](http://localhost:8000/pages/auth/register.php) |
| Trợ lý AI (bổ sung) | [/pages/ai/chat.php](http://localhost:8000/pages/ai/chat.php) |
| Quên MK (3 bước) | `forgot_password.php` → `forgot_verify_otp.php` → `reset_password.php` |
| Hồ sơ / Yêu thích | [/pages/user/profile.php](http://localhost:8000/pages/user/profile.php) · [/pages/user/favorites.php](http://localhost:8000/pages/user/favorites.php) |
| Điều khoản / Chính sách | [/pages/legal/terms.php](http://localhost:8000/pages/legal/terms.php) · [/pages/legal/privacy.php](http://localhost:8000/pages/legal/privacy.php) |
| Admin dashboard | [/admin/pages/dashboard.php](http://localhost:8000/admin/pages/dashboard.php) |
| Admin gyms / trainers / users / reviews / categories / profile | `/admin/pages/gyms.php`, `trainers.php`, `users.php`, `reviews.php`, `categories.php`, `profile.php` |

**Lưu ý nghiệp vụ:**

- Gym / HLV: **ẩn / hiện** (không xóa cứng trên UI).
- Chỉ khóa/mở tài khoản **User** (`role=user`); không khóa Admin; không xóa tài khoản trên UI.
- User đang `locked` (kể cả session cũ) bị đá về login — không sửa hồ sơ / yêu thích.
- Đánh giá user: chờ admin duyệt; từ chối cần lý do.
- Guest mở Favorites / Profile: soft-gate (CTA đăng nhập), không bắt buộc 302.
- **Admin** sau login chỉ ở `/admin/*`. Mở trang chủ / portal công khai → dashboard — kiểm UI public bằng **Guest** hoặc **User** (`u02`).
- Liên kết HLV–gym / HLV–chuyên môn: bảng N–N (`gym_trainers`, `trainer_specialties`) là nguồn chuẩn; cột `trainers.gym_id` / `specialty_id` chỉ legacy.

---

## 8. Checklist demo nhanh

1. Guest: tìm gym → mở chi tiết → xem review đã duyệt → thử heart → modal đăng nhập.  
2. Login `u02@gymfinder.test` / `password` → yêu thích → gửi review (pending).  
3. Login `u01@gymfinder.test` / `password` → Admin → duyệt/từ chối review → ẩn/hiện gym → khóa/mở user.  
4. (Tuỳ chọn) Quên mật khẩu với `demo_otp: true` hoặc SMTP thật.

---

## 9. Xử lý sự cố

### Cổng 8000 đang dùng

```bash
lsof -i :8000
kill <PID>
# hoặc:
php -S localhost:8080 router.php
```

### Thiếu bảng / lỗi PT–gym / filter `has_pt`

Thường do SQLite cũ. Xóa `database/gymfinder.sqlite*` rồi chạy lại server (§2).

### Không upload / không hiện ảnh

```bash
mkdir -p uploads
chmod 775 uploads
```

(`uploads/` đã gitignore.)

### Quên mật khẩu báo chưa cấu hình SMTP

Tạo `backend/smtp.local.php` từ example; hoặc đặt `'demo_otp' => true` để hiện OTP trên UI.

### MySQL kết nối fail / vẫn thấy SQLite

Kiểm tra `GF_DB_DRIVER=mysql` (hoặc `'driver' => 'mysql'` trong config) và thông tin `GF_DB_*` / phpMyAdmin.

### Safari favicon cũ

Incognito (`Cmd+Shift+N`) hoặc xóa cache favicon Safari.

---

## 10. Tài liệu liên quan

| File | Nội dung |
| --- | --- |
| [`README.md`](../../README.md) | Tổng quan + tài khoản demo ngắn |
| [`docs/PROJECT_STRUCTURE.md`](../PROJECT_STRUCTURE.md) | Bản đồ thư mục |
| [`docs/specs/GYMFINDER_dacta.docx`](GYMFINDER_dacta.docx) | Đặc tả |
| [`docs/specs/GYMFINDER_CSDL.docx`](GYMFINDER_CSDL.docx) | Thiết kế CSDL |
| [`docs/qa/`](../qa/) | Test plan / cases / bug report |
