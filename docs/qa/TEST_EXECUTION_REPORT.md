# GYMFINDER — Test Execution Report (Vòng 1)

**Ngày chạy:** 2026-10-19  
**Môi trường:** `http://127.0.0.1:8000` · PHP 8.0.28 built-in · SQLite `database/gymfinder.sqlite`  
**Công cụ:** `curl` (HTTP/API) + kiểm tra HTML; Cursor browser (viewport emulation các vòng trước)  
**Quy tắc:** Chỉ Pass/Fail khi đã thực thi. Không Pass chỉ vì đọc code.

---

## 1. Tổng quan

| Chỉ số | Số lượng |
| --- | ---: |
| Tổng test case đã viết (`TEST_CASES.md`) | **86** |
| Đã thực thi vòng 1 | **28** |
| Pass | **26** |
| Fail | **0** |
| Blocked | **1** |
| Not Run (còn lại) | **58** |
| **Pass rate (trên đã thực thi)** | **26/28 ≈ 92.9%** (tính cả Blocked trong mẫu thực thi; nếu chỉ Pass/(Pass+Fail) = **100%**) |

> Pass rate báo cáo dựa trên **test đã chạy thực tế**, không phải toàn bộ 86 case.

---

## 2. Kết quả chi tiết (đã chạy)

| ID | Status | Actual result | Evidence |
| --- | --- | --- | --- |
| AUTH-004 | **Pass** | Login `u02` → 302 `index.php`; profile 200 với cookie | curl + cookie jar |
| AUTH-005 | **Pass** | Login `u01` → 302 `admin/.../dashboard.php`; dash 200 | curl |
| AUTH-006 | **Pass** | Sai MK → 200 + alert “Email/số điện thoại hoặc mật khẩu không đúng.” | HTML |
| AUTH-009 | **Pass** | POST logout → 302; profile sau đó 302 login | curl |
| AUTH-010 | **Pass** | Guest profile → 302 login + redirect | `Location` header |
| AUTH-011 | **Pass** | Guest admin dash → 302 login | header |
| AUTH-012 | **Pass** | User session gọi admin dash → **403** | curl |
| HOME-001 | **Pass** | `/` HTTP 200, có hero/search | curl + browser trước đó |
| GYM-001 | **Pass** | search.php 200, có danh sách | curl |
| GYM-002 | **Pass** | `category_ids[]=1` → **1** phòng tập (CrossFit) | HTML count |
| GYM-003 | **Pass** | `category_ids[]=2` → **2** phòng tập cao cấp | HTML count |
| GDET-003 | **Pass** | Gym hidden id=7 → **404** | curl |
| GDET-004 | **Pass** | id=99999 → **404** | curl |
| GDET-002 / REV-003 | **Pass** | Review pending “Tạm được…” **không** hiện trên detail gym 1 | rg count 0 |
| PT-001 | **Pass** | trainers index 200 | curl |
| PT-003 | **Pass** | Option filter **1–2 năm** (không còn 0–2) | HTML |
| PT-005 / UI-005 | **Pass** | Card có `stretched-link` → detail | HTML |
| PT-007 | **Pass** | Trainer hidden id=7 → **404** | curl |
| PT-009 | **Blocked** | Spec N–N vs DB runtime thiếu bảng trung gian | sqlite `.tables` — xem BUG-001 |
| UI-004 | **Pass** | Form “Viết đánh giá” dùng `gf-star-filter` mode=rate | HTML detail |
| FAV-001 | **Pass** | Guest favorites 200 + CTA “Đăng nhập để xem yêu thích” | HTML |
| SEC-001 | **Pass** | Guest POST `/api/favorites` + CSRF → **401 AUTH_REQUIRED** | JSON |
| ADM-001 | **Pass** | Admin dashboard 200, có 4 KPI / breadcrumb | HTML |
| ADM-002 | **Pass** | Breadcrumb “Quản trị viên / Tổng quan” | HTML |
| ADM-014 | **Pass** | Gym admin: Tạm ẩn / Kích hoạt; không thấy “Xóa cứng” | HTML rg |
| ADM-015 | **Pass*** | “Cập nhật: H:i - d/m/Y” = giờ render (đúng thiết kế hiện tại, **không** real-time) | HTML; *Pass theo hành vi hiện hữu |
| AUTH-008 | **Not Run** | Cần khóa user rồi login — tránh đổi state demo trong vòng báo cáo | — |
| RESP-* / nhiều P1 | **Not Run** | Chưa cover hết trong vòng 1 | Một phần đã kiểm ở chat responsive trước |

\*ADM-015: Pass “đúng với implementation”; không Pass như “real-time backend”.

---

## 3. Module chưa cover (Not Run — ưu tiên vòng 2)

- Đăng ký mới / OTP full 3 bước / Remember-me cookie 30 ngày  
- Lọc amenity AND, has_PT, sort, phân trang  
- Favorite toggle + persistence + double-click  
- Gửi/sửa/xóa review end-to-end + reject bắt buộc lý do (UI modal)  
- Admin CRUD tạo gym/PT mới, khóa user E2E  
- XSS sau khi approve  
- Responsive checklist đầy đủ 4 viewport (một phần đã làm trước)

---

## 4. Rủi ro trước demo

1. **N–N Gym–Trainer / Trainer–Specialty** trong đặc tả vs SQLite runtime 1 FK — dễ hỏi khi chấm (BUG-001).  
2. Nhiều case P0 review moderation chưa chạy E2E trong vòng này.  
3. Tài khoản HuongDan: `admin@gymfinder.vn` / `Admin@123456` **hoạt động**; đồng thời `u01`/`u02`+`password` vẫn dùng được.

---

## 5. Kết luận vòng 1

Luồng auth cơ bản, soft-hide 404, lọc danh mục phổ thông/cao cấp, star UI review/filter, phân quyền admin, và soft-hide admin UI **ổn định trên môi trường local**.  

**Chưa sửa code trong vòng audit này** (theo yêu cầu Phase 6). Chi tiết lỗi/rủi ro: `BUG_REPORT.md`.
