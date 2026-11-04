# Hướng dẫn deploy GYMFINDER lên Railway (có AI)

**Mục tiêu:** Web public + MySQL + gọi được Groq (Trợ lý AI).  
**Cập nhật:** 2026-11-04

Repo đã có `Dockerfile` + `docker/apache-start.sh` cho PHP 8.2 Apache.

---

## 0. Chuẩn bị

- Tài khoản [Railway](https://railway.app) (đăng nhập GitHub).
- Repo GitHub có code GYMFINDER (đã push `Dockerfile`).
- Groq API key (`gsk_…`).
- Máy có thể mở [https://railway.app](https://railway.app).

> Railway **không** giống InfinityFree (upload File Manager). Deploy bằng Git + Docker + Variables.

**Cấu hình secret:** ưu tiên Railway **Variables** (`GF_AI_API_KEY`, `GF_DB_*`…).  
Local có thể copy `.env.example` → `.env`, hoặc dùng `backend/ai.local.php` / `smtp.local.php`.  
Thứ tự: env hệ thống / Variables → file `.env` → `*.local.php` → mặc định trong `config.php`.

---

## 1. Đẩy code lên GitHub

Trên máy:

```bash
cd /path/to/GYMFINDER
git add Dockerfile docker/apache-start.sh .dockerignore docs/specs/GymFinder_HuongDan_Railway.md
git commit -m "Add Docker setup for Railway deploy"
git push origin main
```

(Không commit `ai.local.php` / `smtp.local.php`.)

---

## 2. Tạo project trên Railway

1. Vào [https://railway.app/new](https://railway.app/new)
2. **Deploy from GitHub repo** → chọn repo `gymfinder-web` (hoặc tên repo nhóm)
3. Railway detect Dockerfile → tạo **service web**

Nếu hỏi Root Directory: để trống (repo root).

---

## 3. Thêm MySQL

1. Trong project → **+ New** → **Database** → **Add MySQL**
2. Đợi MySQL **Online**
3. Mở tab MySQL → **Variables** (hoặc **Connect**) — ghi lại / sẽ reference:
   - `MYSQLHOST` (hoặc `MYSQL_HOST`)
   - `MYSQLPORT`
   - `MYSQLDATABASE`
   - `MYSQLUSER`
   - `MYSQLPASSWORD`

Tên biến đúng theo panel Railway (có thể hơi khác theo phiên bản UI).

---

## 4. Gắn biến môi trường cho service Web

Mở service **web** (Dockerfile) → **Variables** → thêm:

### Database (trỏ sang MySQL plugin)

Dùng **Variable Reference** của Railway (nút chọn biến từ MySQL), hoặc điền tay:

| Variable | Giá trị gợi ý |
| --- | --- |
| `GF_DB_DRIVER` | `mysql` |
| `GF_DB_HOST` | *(Reference)* `MYSQLHOST` của MySQL |
| `GF_DB_PORT` | *(Reference)* `MYSQLPORT` |
| `GF_DB_NAME` | *(Reference)* `MYSQLDATABASE` |
| `GF_DB_USER` | *(Reference)* `MYSQLUSER` |
| `GF_DB_PASS` | *(Reference)* `MYSQLPASSWORD` |

### AI (bắt buộc nếu muốn chat AI)

| Variable | Giá trị |
| --- | --- |
| `GF_AI_ENABLED` | `true` |
| `GF_AI_PROVIDER` | `groq` |
| `GF_AI_API_KEY` | `gsk_...` (key Groq của bạn) |
| `GF_AI_MODEL` | `qwen/qwen3.8-27b` (hoặc model đang dùng) |

### OTP (tuỳ chọn — demo không gửi mail)

| Variable | Giá trị |
| --- | --- |
| `GF_SMTP_DEMO_OTP` | `true` |

Hoặc cấu hình SMTP thật bằng `GF_SMTP_HOST`, `GF_SMTP_PORT`, `GF_SMTP_USERNAME`, `GF_SMTP_PASSWORD`, `GF_SMTP_FROM_EMAIL`, `GF_SMTP_DEMO_OTP=false`.

Sau khi Save Variables → Railway **Redeploy** service web.

---

## 5. Import schema + seed vào MySQL

### Cách A — Railway CLI / Query (nếu panel có Query)

1. Copy nội dung `database/deploy_infinityfree/schema_infinityfree.sql` (đã bỏ `CREATE DATABASE` / `USE gymfinder`)
2. Chạy trên DB Railway
3. Tiếp `database/deploy_infinityfree/seed_infinityfree.sql`

### Cách B — MySQL client từ máy

Trong tab MySQL Railway lấy **public URL** / connection string (nếu bật), rồi:

```bash
mysql -h <HOST> -P <PORT> -u <USER> -p <DATABASE> < database/deploy_infinityfree/schema_infinityfree.sql
mysql -h <HOST> -P <PORT> -u <USER> -p <DATABASE> < database/deploy_infinityfree/seed_infinityfree.sql
```

> Dùng bản trong `database/deploy_infinityfree/` — **không** dùng `schema.sql` gốc (có `CREATE DATABASE gymfinder`).

---

## 6. Bật domain public

1. Service web → **Settings** → **Networking** → **Generate Domain**
2. Được URL dạng: `https://gymfinder-production-xxxx.up.railway.app`
3. Mở URL → kiểm tra trang chủ

---

## 7. Checklist kiểm thử

- [ ] Trang chủ load
- [ ] Tìm gym / PT có data seed
- [ ] Login `u02@gymfinder.test` / `password`
- [ ] Admin `u01@gymfinder.test` / `password`
- [ ] **Trợ lý AI** hỏi: *Gym Quận 7 giá dưới 500 nghìn* → có câu trả lời (không còn lỗi InfinityFree)
- [ ] (Tuỳ) Quên MK với `GF_SMTP_DEMO_OTP=true` → hiện OTP trên UI

---

## 8. Lỗi hay gặp

| Lỗi | Cách xử |
| --- | --- |
| Build fail Dockerfile | Xem Build Logs; đảm bảo `Dockerfile` ở root repo |
| **CRASHED** (đỏ) | Mở deployment → **View Logs**. Hay gặp: (1) `AH00534 More than one MPM loaded` → Dockerfile phải `a2dismod mpm_event/mpm_worker` + `a2enmod mpm_prefork`; (2) Apache không Listen đúng `PORT` → `docker/apache-start.sh` |
| Unexposed service | Settings → Networking → **Generate Domain** + đảm bảo service listen `$PORT` |
| Trắng / 500 DB | Sai `GF_DB_*` — check Variables + MySQL Online; nhớ import schema/seed |
| AI vẫn lỗi | Thiếu `GF_AI_API_KEY` hoặc key sai; xem Deploy Logs |
| `/api` 404 | Apache chưa `mod_rewrite` — image đã `a2enmod rewrite`; Redeploy |
| Upload ảnh mất sau deploy | Disk ephemeral free — bình thường với demo; cần volume trả phí nếu giữ lâu |
| Hết credit Railway | Nâng gói hoặc quay lại demo AI trên local |

---

## 9. So với InfinityFree

| | InfinityFree | Railway |
| --- | --- | --- |
| Upload File Manager | Có | Không (Git/Docker) |
| AI Groq | Hay fail | **Thường OK** |
| Key AI | `ai.local.php` trên host | **Variables** `GF_AI_API_KEY` |
| Phù hợp | Link phụ / UI | Demo AI trên web |

Khuyến nghị đồ án: **local** = AI chắc; **Railway** = link public có AI; InfinityFree có thể giữ làm backup UI.

---

## 10. Tài liệu liên quan

- `Dockerfile`, `docker/apache-start.sh`, `.dockerignore`
- `backend/config.php` — đọc `GF_DB_*`, `GF_AI_*`, `GF_SMTP_*`
- `docs/specs/GymFinder_HuongDanChay.md` — chạy local
- `docs/specs/GymFinder_KichBanDemo_Local_Deploy.md` — kịch bản demo
