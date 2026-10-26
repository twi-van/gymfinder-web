# GYMFINDER — Test Execution Report

**Quy tắc:** Chỉ Pass/Fail khi đã thực thi. Không Pass chỉ vì đọc code.

---

## Vòng AI Chat (2026-10-26)

**Công cụ:** `php docs/qa/_ai_smoke.php`  
**Môi trường:** `php -S 127.0.0.1:8000 router.php` · SQLite · Groq `qwen/qwen3.8-27b` · `ai.local.php`

| ID | Status | Actual |
| --- | --- | --- |
| AI-010 | **Pass** | Parse Quận 7 + `max_price=500000` + `want_gym` |
| AI-011 | **Pass** | Parse HLV + specialty Yoga |
| AI-012 | **Pass** | Pre-filter gym Quận7/giá → 1 gym |
| AI-013 | **Pass** | Pre-filter trainer Yoga → 17 |
| AI-014 | **Pass** | Catalog chỉ `active` |
| AI-020 | **Pass** | GET `/pages/ai/chat.php` 200 + form |
| AI-021 | **Pass** | POST thiếu CSRF → **403** |
| AI-001 | **Pass** | Recommend gym Q7 → reply + 1 gym |
| AI-002 | **Pass** | Recommend HLV Yoga → reply + 3 trainers |
| AI-003 | **Pass** | Đà Nẵng → reply; gyms=0 trainers=0 |
| AI-022 | **Pass** | POST→302 PRG + GET thấy history (sau khi thêm session history) |
| AI-030 | **Pass** | Rate limit session chặn khi n≥6 |

**Tóm tắt (re-check 2026-10-26):** **12 Pass · 0 Fail · 0 Blocked · 0 Skip.** API widget `/api/ai/recommend` OK; home có `gfAiWidget`.

---

## Vòng 3 — Favorites / Reviews / OTP / Register (2026-10-21)

**Công cụ:** `php docs/qa/_round3_smoke.php` (+ curl vòng 2 còn hiệu lực)  
**Phạm vi:** AUTH-001, FAV-002…006, REV-001…009, AUTH-014/015, ADM-007/010 (vòng trước)

| ID | Status | Actual |
| --- | --- | --- |
| AUTH-001 | **Pass** | Đăng ký email mới → 302 login |
| AUTH-002 / 003 | **Pass** | (vòng probe) không tạo user rỗng; bắt đồng ý ĐK |
| FAV-002 | **Pass** | POST `/api/favorites` gym → 201 |
| FAV-003 | **Pass** | DELETE favorite → 204 |
| FAV-004 | **Pass** | Add trainer → 201 |
| FAV-005 | **Pass** | GET list còn mục vừa thêm |
| FAV-006 | **Pass** | Add trùng → **409** |
| REV-001 | **Pass** | Tạo review → pending 201 |
| REV-002 | **Pass** | comment rỗng → 422 |
| REV-003 | **Pass** | pending không hiện public |
| REV-004 | **Pass** | Sửa → lại pending |
| REV-005 | **Pass** | DELETE review → 204 |
| REV-006 | **Pass** | Admin approve → public hiện |
| REV-007 | **Pass** | Reject không lý do → 422 |
| REV-008 | **Pass** | Reject có lý do; author thấy lý do |
| REV-009 | **Pass** | User khác PATCH → 403 |
| AUTH-015 | **Pass** | OTP sai → báo lỗi |
| AUTH-014 | **Blocked** | SMTP local đang cấu hình đủ → hệ thống **gửi mail**, không hiện `demo_otp` trên UI (đúng `auth.php`). Không đọc được inbox trong vòng test → không hoàn tất reset E2E trên UI. |
| ADM-007 / 010 | **Pass** | (vòng probe trước) gym thiếu tên 422; khóa/mở u04 |

**Tóm tắt vòng 3:** **18 Pass · 0 Fail sản phẩm · 1 Blocked (AUTH-014 E2E UI khi SMTP thật).**

### Fail giả (harness) — không ghi bug

Lần chạy shell đầu: JSON/`target_id` rỗng vì `u02` đã review hết gym active; API path thiếu id → 400/404. Chạy lại bằng user mới + script PHP → hết.

---

## Vòng 2 — Smoke regression (2026-10-21)

**Môi trường:** `http://127.0.0.1:8000` · PHP built-in · SQLite vừa reset seed  
**Công cụ:** `curl` + cookie jar + CSRF form `login`  
**Phạm vi:** P0/P1 đã đổi sau merge N–N, soft-gate, portal-only, R1 locked session

| ID | Status | Actual / evidence |
| --- | --- | --- |
| AUTH-004 | **Pass** | Login `u02` + CSRF → 302 `index.php` |
| AUTH-005 | **Pass** | Login `u01` → 302 `admin/pages/dashboard.php` |
| AUTH-006 | **Pass** | Sai MK → alert “không đúng” |
| AUTH-008 | **Pass** | `u03` locked → không login; thông báo khóa |
| AUTH-010 / PROF-005 | **Pass** | Guest profile **200** + CTA soft-gate (không còn 302) |
| AUTH-012 | **Pass** | User mở admin dashboard → **403** |
| ADM-PORT | **Pass** | Admin mở `/` → 302 dashboard |
| ADM-001 | **Pass** | Dashboard 200 |
| ADM-014 | **Pass** | Không có “Xóa cứng” trên gyms |
| ADM-015 | **Pass*** | “Cập nhật” = giờ render (đúng thiết kế) |
| DEL-USER | **Pass** | `DELETE /api/admin/users/2` → **405** BUSINESS_RULE |
| R1-LOCK | **Pass** | Session `u03` bị khóa giữa chừng → profile 302 `login.php?error=locked` |
| FAV-001 | **Pass** | Guest favorites 200 + CTA |
| SEC-001 | **Pass** | Guest POST `/api/favorites` → **401** AUTH_REQUIRED |
| HOME-001 / GYM-001 | **Pass** | 200 |
| GDET-003 | **Pass** | Gym `hidden` id=3 → 404 |
| GDET-004 | **Pass** | id=99999 → 404 |
| PT-001 / PT-003 | **Pass** | Index 200; filter **0–2 năm** có trên UI |
| PT-007 | **Pass** | Trainer `hidden` id=28 → 404 |
| PT-009 | **Pass** | `gym_trainers=92`, `trainer_specialties=72` |

**Tóm tắt vòng 2:** 22 Pass / 0 Fail (smoke). SQLite N–N OK; soft-gate profile/favorites khớp; admin portal-only + chặn DELETE user + R1 locked session OK.

---

## Vòng 1 (2026-10-19) — lưu sử

| Chỉ số | Số |
| --- | ---: |
| Case trong `TEST_CASES.md` | 86 |
| Đã chạy vòng 1 | 28 |
| Pass | 26 |
| Fail | 0 |
| Blocked | 1 (PT-009 — **đã Pass vòng 2** sau rebuild SQLite) |
| Not Run | phần còn lại |

Một số kết quả vòng 1 **lệch thiết kế hiện tại** (đã supersede vòng 2):

| ID | Vòng 1 | Hiện tại |
| --- | --- | --- |
| AUTH-010 | 302 hard-gate | 200 soft-gate |
| PT-003 | “không còn 0–2” | Filter **0–2** (exp 0–60) |
| PT-009 | Blocked thiếu N–N | Pass sau seed lại |

---

## Module vẫn Not Run (vòng sau)

- Remember-me cookie ~30 ngày (cần chờ/đo cookie)  
- OTP E2E khi SMTP thật (đọc inbox) hoặc tắt SMTP + `demo_otp=true`  
- Admin tạo gym/PT đầy đủ qua UI form (mới test validation thiếu tên)  
- Responsive 4 viewport / XSS sau approve  

---

## Rủi ro demo

1. ~~N–N thiếu trên SQLite~~ — đã rebuild; máy khác nhớ xóa `gymfinder.sqlite*` nếu lệch.  
2. Seed soft-hide: gym hidden **id=3**, trainer hidden **id=28** (không cố định id=7).  
3. Demo OTP: `demo_otp` trong `smtp.local.php` — không bắt buộc SMTP thật.
