# GYMFINDER — Bug Report

**Cập nhật:** 2026-10-21  
**Môi trường:** Local PHP `:8000` + SQLite  
**Nguyên tắc:** Chỉ ghi lỗi/rủi ro có bằng chứng.

---

## Tổng hợp theo mức độ

| Severity | Số | Ghi chú |
| --- | ---: | --- |
| P0 | 0 | DB local đã rebuild (N–N) |
| P1 | 0 | |
| P2 | 0 | Hard-delete user API đã chặn (405 BUSINESS_RULE) |
| P3 | 0 | Soft-gate + docs đã sync; R1 locked-session đã vá |

---

## BUG-001 — SQLite runtime thiếu bảng N–N (code đã có)

| Trường | Nội dung |
| --- | --- |
| **Severity** | ~~P0~~ → **Resolved (local 2026-10-21)** |
| **Module / trang** | Trainers · Admin PT · search gym (`has_pt`) |
| **Root cause** | SQLite tạo trước khi merge N–N; install chỉ chạy khi file mới/rỗng |
| **Fix đã làm** | Xóa `database/gymfinder.sqlite` → bootstrap seed lại |
| **Verify** | Có `gym_trainers` (92), `trainer_specialties` (72); `gf_search_gyms` / `gf_search_trainers` OK; users không còn cột `goal` |
| **Ghi chú** | Máy khác / clone mới: nếu còn sqlite cũ thì xóa rồi chạy lại server. |

---

## BUG-002 — Tài liệu HuongDan vs seed

| Trường | Nội dung |
| --- | --- |
| **Severity** | ~~P3~~ → **Resolved (2026-10-21)** |
| **Module** | Docs |
| **Trước** | HuongDan liệt kê `admin@gymfinder.vn` / `Admin@123456` (không có trong seed) |
| **Sau** | `GymFinder_HuongDanChay.md` khớp seed: `u01`/`u02` + `password`; thêm reset SQLite, MySQL driver, SMTP/`demo_otp` |
| **Files** | `docs/specs/GymFinder_HuongDanChay.md`, `database/seed.sql` |

---

## BUG-003 — Guest Favorites soft-gate (HTTP 200) vs Profile hard-gate (302)

| Trường | Nội dung |
| --- | --- |
| **Severity** | ~~P3~~ → **Resolved (2026-10-21)** |
| **Module** | Favorites / Auth |
| **Steps** | Guest mở `/pages/user/favorites.php` và `/pages/user/profile.php` |
| **Expected** | Cùng chiến lược chặn Guest (theo đặc tả: thao tác cá nhân cần đăng nhập) |
| **Actual (cũ)** | Favorites: **200** + CTA. Profile: **302** login — không thống nhất |
| **Fix (2026-10-21)** | Profile chuyển **soft-gate** giống Favorites (200 + CTA Đăng nhập / Đăng ký) |
| **Verify (vòng 2)** | Guest cả hai trang → HTTP 200 + CTA |
| **Files** | `pages/user/favorites.php`, `pages/user/profile.php` |

---

## BUG-004 — API Favorites: CSRF được check trước AUTH

| Trường | Nội dung |
| --- | --- |
| **Severity** | ~~P3~~ → **Resolved (2026-10-21)** |
| **Module** | API mutating (vd. `/api/favorites`) |
| **Trước** | Guest POST `/api/favorites` không CSRF → **403 CSRF_INVALID**; có CSRF → **401 AUTH_REQUIRED** |
| **Sau** | Guest POST `/api/favorites` (có/không CSRF) → **401 AUTH_REQUIRED** trước; user đã login thiếu CSRF → vẫn **403 CSRF_INVALID** |
| **Fix** | `gf_api_csrf_mutating($method, $path)`: endpoint cần login kiểm tra session trước CSRF; `/auth/login` & `/auth/register` chỉ CSRF |
| **Files** | `api/index.php` |

---

## BUG-005 — API DELETE user (xóa cứng) lệch đặc tả chỉ khóa/mở

| Trường | Nội dung |
| --- | --- |
| **Severity** | ~~P2~~ → **Resolved (2026-10-21)** |
| **Module** | `DELETE /api/admin/users/{id}` |
| **Trước** | Admin API có thể `DELETE` → xóa hẳn user + cascade reviews/favorites; UI chỉ Khóa/Mở |
| **Sau** | `DELETE` trả **405 BUSINESS_RULE**; quản lý tài khoản chỉ qua `PATCH status=locked\|active` |
| **Files** | `api/index.php`, `backend/resources/admin_user_resource.php` |

---

## Không ghi Bug (làm rõ)

| Hạng mục | Kết luận |
| --- | --- |
| Dashboard “Cập nhật: giờ” | **Không phải bug** — `date()` lúc render; refresh = reload trang. Không real-time. |
| Lọc phổ thông không ra Iron/Elite | Đã đúng sau khi tách category (seed exclusive). |
| Soft-hide → public 404 | Đúng đặc tả. |

---

## Đề xuất thứ tự xử lý

1. ~~P0 BUG-001~~ — rebuild SQLite local.  
2. ~~P3 BUG-003~~ — soft-gate Favorites + Profile.  
3. ~~P3 BUG-004~~ — AUTH trước CSRF.  
4. ~~P2 BUG-005~~ — chặn DELETE user (chỉ khóa/mở).  
5. ~~BUG-002 docs tài khoản~~ — HuongDan sync seed + hướng dẫn chạy.

**Đã sửa (2026-10-21):** Sort “Mới nhất”; bỏ `goal`; SMTP untrack; soft-gate Profile; AUTH→CSRF; chặn xóa cứng user; HuongDanChay; R1 `gf_reject_locked_session` (profile/favorites); R2 tooltip khóa admin; FK `trainers.district_id` RESTRICT; sync doc CSDL/dacta.

### BUG-006 — Session locked vẫn sửa profile (web)

| Trường | Nội dung |
| --- | --- |
| **Severity** | ~~P1~~ → **Resolved (2026-10-21)** |
| **Module** | Profile / Auth |
| **Trước** | API chặn `ACCOUNT_LOCKED`; form `profile.php` dùng `gf_current_user()` không kick locked |
| **Sau** | `gf_reject_locked_session()` → logout + `login.php?error=locked` |
| **Verify** | Smoke R1-LOCK Pass (vòng 2) |
| **Files** | `backend/auth.php`, `pages/user/profile.php`, `pages/user/favorites.php` |
