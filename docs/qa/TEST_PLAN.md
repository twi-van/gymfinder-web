# GYMFINDER — Test Plan

**Phiên bản:** 1.0  
**Ngày lập:** 2026-10-19  
**Nguồn đối chiếu:** `docs/specs/GYMFINDER_dacta.docx`, `docs/specs/GYMFINDER_CSDL.docx`, `README.md`, `docs/specs/GymFinder_HuongDanChay.md`, mã nguồn `pages/`, `admin/`, `backend/`, `api/`  
**Phạm vi:** Đồ án web cuối kỳ — UI, functional, phân quyền, luồng nghiệp vụ trong đặc tả. **Không** kiểm thử bản đồ / đặt lịch / thanh toán / chat (ngoài phạm vi).

---

## 1. Mục tiêu

1. Xác nhận các chức năng P0/P1 trong đặc tả hoạt động đúng trên môi trường local.
2. Phát hiện lỗi UI/UX, validation, phân quyền và tính toàn vẹn dữ liệu trước demo/nộp bài.
3. Tạo bộ test case tái sử dụng được; chỉ đánh **Pass/Fail** khi đã chạy thực tế.

---

## 2. Môi trường kiểm thử

| Hạng mục | Giá trị |
| --- | --- |
| OS | macOS (darwin) |
| App server | PHP built-in: `php -S localhost:8000 router.php` (đang lắng nghe `:8000`) |
| Base URL | `http://127.0.0.1:8000` |
| DB | SQLite `database/gymfinder.sqlite` (auto-seed) |
| Trình duyệt | Cursor IDE Browser + Chrome DevTools device emulation |
| Viewport | Mobile 390×844; Tablet 768×1024; Desktop ~1366×768 |

### Tài khoản demo (seed)

| Vai trò | Email | Mật khẩu |
| --- | --- | --- |
| Admin | `u01@gymfinder.test` | `password` |
| User | `u02@gymfinder.test` | `password` |
| Guest | — | — |

> Tài khoản `admin@gymfinder.vn` / `Admin@123456` trong HuongDan có thể **không khớp** seed SQLite hiện tại — ưu tiên `u01`/`u02` + `password`.

---

## 3. Ma trận phạm vi (Spec ↔ Code)

| Module | Trong đặc tả | Có trong code | Kiểm thử |
| --- | --- | --- | --- |
| Auth đăng ký/đăng nhập/đăng xuất | Có | Có | Có |
| Remember-me | Có | Có | Có (P1) |
| Quên MK OTP email | Có | Có (UI + bảng OTP; SMTP demo) | Có |
| Trang chủ + tìm nhanh | Có | Có | Có |
| Tìm/lọc/sắp xếp gym | Có | Có | Có |
| Chi tiết gym | Có | Có | Có |
| Bản đồ | Ngoài phạm vi | Không | Không |
| Danh sách/chi tiết PT | Có | Có | Có |
| PT N–N nhiều gym / nhiều chuyên môn | Có | Code + schema + seed đã có `gym_trainers` / `trainer_specialties`; DB local cũ cần rebuild sqlite | Có — sau seed lại |
| Yêu thích gym/PT | Có | Có | Có |
| Đánh giá + duyệt/từ chối + lý do | Có | Có | Có |
| Hồ sơ User / đổi MK | Có | Có | Có |
| Admin dashboard | Có | Có | Có |
| Admin gym/PT soft-hide | Có | Có | Có |
| Admin users khóa/mở | Có | Có | Có |
| Admin catalogs | Có | Có | Có |
| Admin profile | Có | Có | Có |

---

## 4. Vai trò & màn hình

### Guest / User Portal

- `/` Trang chủ  
- `/pages/gyms/search.php`, `detail.php`  
- `/pages/trainers/index.php`, `detail.php`  
- `/pages/auth/*` (login, register, forgot_*, reset, logout)  
- `/pages/user/profile.php`, `favorites.php`  
- `/pages/legal/terms.php`, `privacy.php`

### Admin Portal

- `/admin/pages/dashboard.php`  
- `gyms.php`, `trainers.php`, `users.php`, `reviews.php`, `categories.php`, `profile.php`  
- `catalogs.php` (redirect/legacy nếu còn)

---

## 5. Chiến lược kiểm thử

| Loại | Áp dụng |
| --- | --- |
| Positive / Negative | Auth, form, admin CRUD |
| Boundary | Giá, sao 1–5, kinh nghiệm 1–60, OTP |
| Equivalence | Bộ lọc khu vực/danh mục/tiện ích |
| Role/permission | Guest vs User vs Admin; URL trực tiếp |
| State transition | Review pending→approved/rejected; gym/PT active↔hidden; user active↔locked |
| UI/Responsive | Layout, modal, sidebar admin, overflow |
| Regression | Sau mỗi nhóm sửa (Phase 6 — **chưa chạy** đến khi được duyệt) |

**Thứ tự thực thi:** P0 → P1 → P2. Ưu tiên luồng demo giảng viên.

**Tự động hóa:** Ưu tiên thủ công + HTTP probe (`curl`) + browser automation sẵn có. **Không** cài Playwright/framework mới trong vòng này.

---

## 6. Dữ liệu / rủi ro đã biết (pre-test)

1. Soft-hide gym/PT → public 404 (đã thấy trong code/seed).  
2. “Cập nhật giờ” dashboard = `date()` lúc render, **không** real-time backend.  
3. Quan hệ PT–Gym / PT–Specialty theo seed hiện tại là cột FK chính, không bảng trung gian trên SQLite đang chạy.  
4. Favorites guest: soft-gate (HTTP 200 + CTA đăng nhập), profile: hard redirect 302 — ghi nhận khi test phân quyền.

---

## 7. Tiêu chí Pass/Fail / Blocked / Not Run

- **Pass:** Đã thực hiện bước + kết quả khớp Expected + có evidence.  
- **Fail:** Tái hiện được + lệch Expected + có evidence.  
- **Blocked:** Thiếu môi trường/tài khoản/SMTP/dữ liệu.  
- **Not Run:** Chưa kịp chạy trong vòng này.

Không Pass chỉ vì “đọc code thấy đúng”.

---

## 8. Deliverables

| File | Nội dung |
| --- | --- |
| `docs/qa/TEST_PLAN.md` | File này |
| `docs/qa/TEST_CASES.md` | Test case chi tiết |
| `docs/qa/TEST_EXECUTION_REPORT.md` | Kết quả chạy vòng 1 |
| `docs/qa/BUG_REPORT.md` | Lỗi phát hiện |

---

## 9. Ngoài phạm vi vòng này

- Sửa code hàng loạt (Phase 6 chờ duyệt).  
- Load/performance/penetration sâu.  
- MySQL/XAMPP path (chỉ ghi chú nếu Blocked).  
- SMTP thật gửi OTP email (demo OTP trên UI).
