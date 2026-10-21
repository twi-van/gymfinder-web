# GYMFINDER — Test Cases

**Ngày:** 2026-10-19  
**Quy ước Status trong file này:** mặc định `Not Run` cho đến khi được ghi trong `TEST_EXECUTION_REPORT.md`.  
**Cột Actual / Evidence / Bug ID:** điền khi thực thi.

---

## A. Authentication & Session

| ID | Module | Test name | Priority | Preconditions | Test data | Steps | Expected |
| --- | --- | --- | --- | --- | --- | --- | --- |
| AUTH-001 | Auth | Đăng ký hợp lệ | P0 | Guest | Email mới, MK ≥8, đồng ý ĐK | Mở register → nhập đủ → Submit | Tạo user / đăng nhập hoặc chuyển login thành công |
| AUTH-002 | Auth | Đăng ký thiếu email/MK | P0 | Guest | Form trống | Submit | Báo lỗi validation, không tạo user |
| AUTH-003 | Auth | Đăng ký không đồng ý điều khoản | P1 | Guest | Bỏ checkbox | Submit | Không cho đăng ký |
| AUTH-004 | Auth | Đăng nhập đúng User | P0 | Guest | u02@gymfinder.test / password | Login | Vào portal User, thấy tên user |
| AUTH-005 | Auth | Đăng nhập đúng Admin | P0 | Guest | u01@gymfinder.test / password | Login | Redirect dashboard admin |
| AUTH-006 | Auth | Sai mật khẩu | P0 | Guest | u02 + sai MK | Login | Thông báo lỗi, không vào session |
| AUTH-007 | Auth | Email không tồn tại | P0 | Guest | no@x.test / password | Login | Thông báo lỗi |
| AUTH-008 | Auth | Tài khoản bị khóa | P0 | Admin khóa u02 trước | u02 / password | Login | Không đăng nhập được |
| AUTH-009 | Auth | Đăng xuất | P0 | Đã login User | — | Logout | Session hết; Guest menu |
| AUTH-010 | Auth | Guest mở profile URL | P0 | Guest | `/pages/user/profile.php` | Mở URL trực tiếp | Soft-gate HTTP 200 + CTA Đăng nhập/Đăng ký (không bắt buộc 302) |
| AUTH-011 | Auth | Guest mở admin dashboard | P0 | Guest | `/admin/pages/dashboard.php` | Mở URL | Redirect login (không thấy admin) |
| AUTH-012 | Auth | User mở admin dashboard | P0 | Login User | admin URL | Mở URL | Bị từ chối / redirect, không vào admin |
| AUTH-013 | Auth | Remember-me | P1 | Guest | Tick ghi nhớ | Login → đóng cookie session theo thiết kế | Vẫn đăng nhập trong thời hạn (~30 ngày) nếu cookie còn |
| AUTH-014 | Auth | OTP quên MK — email hợp lệ | P1 | User tồn tại | u02 email | Forgot → nhận/hiện OTP demo → verify → reset | Đặt MK mới thành công |
| AUTH-015 | Auth | OTP sai / hết hạn | P1 | Đã gửi OTP | OTP sai | Verify | Báo lỗi, không qua bước reset |
| AUTH-016 | Auth | Double submit login | P2 | Guest | Form hợp lệ | Click login nhanh 2 lần | Không lỗi lạ; 1 session hợp lệ |

---

## B. Trang chủ & tìm kiếm phòng tập

| ID | Module | Test name | Priority | Preconditions | Test data | Steps | Expected |
| --- | --- | --- | --- | --- | --- | --- | --- |
| HOME-001 | Home | Trang chủ load | P0 | Guest | `/` | Mở trang | Hero, search, gym/PT nổi bật, không lỗi PHP |
| HOME-002 | Home | Search nhanh keyword | P0 | Guest | Keyword “Yoga” | Submit search box | Vào search với kết quả liên quan |
| HOME-003 | Home | Search khu vực + giá | P1 | Guest | Quận 1 + khoảng giá | Submit | Kết quả lọc đúng |
| HOME-004 | Home | Chữ “Khoảng giá” không bị cắt | P1 | Desktop | — | Quan sát search bar | Hiển thị đủ “Khoảng giá” |
| GYM-001 | Gym search | Danh sách mặc định | P0 | Guest | search.php | Mở | Có gym active; đếm > 0 |
| GYM-002 | Gym search | Lọc Gym phổ thông | P0 | Guest | category phổ thông | Tick + Lọc | Không hiện gym chỉ cao cấp; chip đúng |
| GYM-003 | Gym search | Lọc Gym cao cấp | P0 | Guest | category cao cấp | Lọc | Chỉ gym có premium; badge Cao cấp hợp lý |
| GYM-004 | Gym search | Lọc tiện ích AND | P1 | Guest | ≥2 amenities | Lọc | Gym phải có đủ tiện ích đã chọn |
| GYM-005 | Gym search | Lọc có PT | P1 | Guest | has_pt=1 | Lọc | Chỉ gym có ≥1 PT active |
| GYM-006 | Gym search | Lọc giá preset | P1 | Guest | under_300… | Lọc | price_min/max khớp khoảng |
| GYM-007 | Gym search | Lọc sao (star picker) | P1 | Guest | Chọn 4★ | Lọc | avg_rating ≥ 4 |
| GYM-008 | Gym search | Kết hợp nhiều filter | P1 | Guest | Quận + category + amenity | Lọc | Giao các điều kiện |
| GYM-009 | Gym search | Sort giá tăng | P1 | Guest | sort price_asc | Chọn sort | Thứ tự giá tăng |
| GYM-010 | Gym search | Không có kết quả | P2 | Guest | Keyword vô nghĩa | Search | Empty state rõ |
| GYM-011 | Gym search | Phân trang | P2 | Guest | Nhiều kết quả | Next page | Trang hợp lệ; filter giữ nếu có |
| GYM-012 | Gym search | Click card → detail | P0 | Guest | Card gym | Click tên/card | Đúng `detail.php?id=` |

---

## C. Chi tiết phòng tập

| ID | Module | Test name | Priority | Preconditions | Test data | Steps | Expected |
| --- | --- | --- | --- | --- | --- | --- | --- |
| GDET-001 | Gym detail | Hiển thị đầy đủ | P0 | Guest | id=1 | Mở | Tên, ảnh, giá, địa chỉ, tiện ích, giờ |
| GDET-002 | Gym detail | Chỉ review approved | P0 | Guest | Gym có review | Xem block đánh giá | Không thấy pending/rejected |
| GDET-003 | Gym detail | Gym hidden → 404 | P0 | Guest | id gym `status=hidden` (seed hiện tại vd. 3) | Mở URL | 404 |
| GDET-004 | Gym detail | ID không tồn tại | P0 | Guest | id=99999 | Mở | 404 |
| GDET-005 | Gym detail | Favorite guest | P0 | Guest | Nút ♥ | Click | Modal/CTA đăng nhập |
| GDET-006 | Gym detail | Liên hệ | P1 | Guest | Nút liên hệ | Click | Modal hiện SĐT/thông tin |
| GDET-007 | Gym detail | HLV liên kết | P1 | Guest | Gym có PT | Xem list PT | Chỉ PT active liên kết |

---

## D. Huấn luyện viên

| ID | Module | Test name | Priority | Preconditions | Test data | Steps | Expected |
| --- | --- | --- | --- | --- | --- | --- | --- |
| PT-001 | Trainers | Danh sách load | P0 | Guest | index | Mở | Chỉ PT active |
| PT-002 | Trainers | Lọc chuyên môn | P1 | Guest | Yoga | Áp dụng | Kết quả đúng chuyên môn |
| PT-003 | Trainers | Lọc kinh nghiệm 0–2 | P1 | Guest | exp=0-2 (UI: 0–2 năm) | Áp dụng | years 0–2 (đặc tả 0–60) |
| PT-004 | Trainers | Star filter rating | P1 | Guest | 4★ | Áp dụng | avg ≥ 4 |
| PT-005 | Trainers | Click card → detail | P0 | Guest | Card | Click body card | Vào detail, không cần chỉ nút |
| PT-006 | Trainers | Detail đầy đủ | P0 | Guest | id active | Mở | Tên, chuyên môn, KN, bio, gym, review |
| PT-007 | Trainers | PT hidden → 404 | P0 | Guest | id trainer `status=hidden` (seed hiện tại vd. 28) | Mở | 404 |
| PT-008 | Trainers | Gym chính hidden | P1 | Seed T08 gym 7 hidden | Detail T08 nếu public | Theo đặc tả | Không lộ nơi làm việc gym hidden trên public |
| PT-009 | Trainers | N–N nhiều gym | P1 | Spec vs DB | Admin + public | Gán ≥2 gym / ≥2 specialty; public không nhân bản PT | Có dòng `gym_trainers` / `trainer_specialties`; list không trùng PT |

---

## E. Hồ sơ người dùng

| ID | Module | Test name | Priority | Preconditions | Test data | Steps | Expected |
| --- | --- | --- | --- | --- | --- | --- | --- |
| PROF-001 | Profile | Xem hồ sơ | P0 | Login User | — | Mở profile | Hiện họ tên/email/SĐT |
| PROF-002 | Profile | Cập nhật họ tên | P0 | Login User | Tên mới | Lưu | Thành công, dữ liệu giữ |
| PROF-003 | Profile | Đổi MK sai MK cũ | P0 | Login User | MK cũ sai | Submit | Lỗi, MK không đổi |
| PROF-004 | Profile | Đổi MK hợp lệ | P1 | Login User | MK mới ≥8 | Submit | Thành công; login lại được |
| PROF-005 | Profile | Guest bị chặn | P0 | Guest | URL profile | Mở | Soft-gate 200 + CTA (giống Favorites) |

---

## F. Yêu thích

| ID | Module | Test name | Priority | Preconditions | Test data | Steps | Expected |
| --- | --- | --- | --- | --- | --- | --- | --- |
| FAV-001 | Favorites | Guest CTA | P0 | Guest | favorites.php | Mở | Yêu cầu đăng nhập (CTA/redirect) |
| FAV-002 | Favorites | Thêm gym | P0 | Login User | Gym active | Click ♥ | Trạng thái filled; có trong list |
| FAV-003 | Favorites | Bỏ gym | P0 | Đã fav | Cùng gym | Click lại | Bỏ khỏi list |
| FAV-004 | Favorites | Thêm/bỏ PT | P0 | Login User | PT active | Toggle ♥ | Không trùng bản ghi |
| FAV-005 | Favorites | Reload giữ trạng thái | P1 | Đã fav | Reload | Nút vẫn active |
| FAV-006 | Favorites | Double click | P2 | Login | Click nhanh | Không lỗi; state nhất quán |

---

## G. Đánh giá

| ID | Module | Test name | Priority | Preconditions | Test data | Steps | Expected |
| --- | --- | --- | --- | --- | --- | --- | --- |
| REV-001 | Reviews | Gửi đánh giá star UI | P0 | Login User, chưa review target | 4★ + comment | Submit | pending; thông báo chờ duyệt |
| REV-002 | Reviews | Thiếu comment | P0 | Login | Chỉ sao | Submit | Validation fail |
| REV-003 | Reviews | Public chỉ approved | P0 | Có pending | Guest xem detail | Không thấy pending |
| REV-004 | Reviews | User sửa review của mình | P1 | Có myReview | Sửa nội dung/sao | Thành công → lại pending |
| REV-005 | Reviews | User xóa review | P1 | Có myReview | Xóa | Xóa được; có thể gửi mới theo rule |
| REV-006 | Reviews | Admin duyệt | P0 | Login Admin | pending | Approve | Hiện public; avg cập nhật |
| REV-007 | Reviews | Admin từ chối không lý do | P0 | Login Admin | reject trống | Submit | Không cho; bắt buộc lý do |
| REV-008 | Reviews | Admin từ chối có lý do | P0 | Login Admin | lý do hợp lệ | Reject | Không public; user thấy lý do |
| REV-009 | Reviews | Không sửa review người khác | P1 | User A | Thử API/ID B | Fail 403/validation |

---

## H. Admin

| ID | Module | Test name | Priority | Preconditions | Test data | Steps | Expected |
| --- | --- | --- | --- | --- | --- | --- | --- |
| ADM-001 | Admin | Dashboard số liệu | P0 | Admin | — | Mở | 4 thẻ thống kê; không lỗi |
| ADM-002 | Admin | Breadcrumb + logo → Tổng quan | P1 | Admin ở gyms | Click logo / crumb | Về dashboard |
| ADM-003 | Admin | Sidebar mobile mở/đóng | P0 | Viewport &lt;992 | ☰ / overlay / ✕ | Drawer hoạt động |
| ADM-004 | Admin | Gym list + filter | P0 | Admin | gyms.php | Mở/lọc | Bảng cuộn ngang OK |
| ADM-005 | Admin | Gym tạm ẩn | P0 | Admin | Gym active | Tạm ẩn | Public 404; admin vẫn thấy |
| ADM-006 | Admin | Gym kích hoạt lại | P0 | Gym hidden | Kích hoạt | Public hiện lại |
| ADM-007 | Admin | Gym thêm thiếu tên | P1 | Admin | Form trống | Submit | Validation |
| ADM-008 | Admin | PT list/edit | P0 | Admin | trainers | Mở/sửa | Lưu được; KN 0–60; ≥1 gym + ≥1 chuyên môn |
| ADM-009 | Admin | PT tạm ẩn | P0 | Admin | PT active | Ẩn | Public 404 |
| ADM-010 | Admin | Users khóa/mở | P0 | Admin | User | Khóa rồi mở | Login bị chặn khi khóa |
| ADM-011 | Admin | Categories tabs | P1 | Admin | categories | Đổi tab | CRUD theo tab |
| ADM-012 | Admin | Reviews moderation UI | P0 | Admin | reviews | Duyệt/từ chối | Như REV-006/007/008 |
| ADM-013 | Admin | Profile admin | P1 | Admin | profile | Đổi tên/MK | Thành công theo rule |
| ADM-014 | Admin | Không có nút xóa cứng gym/PT/user | P1 | Admin | UI | Quan sát | Chỉ ẩn/khóa theo đặc tả |
| ADM-015 | Admin | Timestamp “Cập nhật” | P2 | Admin | dashboard | Quan sát + refresh | = giờ render; reload đổi giờ |

---

## I. Toàn vẹn & trạng thái

| ID | Module | Test name | Priority | Preconditions | Test data | Steps | Expected |
| --- | --- | --- | --- | --- | --- | --- | --- |
| INT-001 | Integrity | Ẩn gym ≠ xóa PT | P0 | Admin | Gym có PT | Ẩn gym | PT bản ghi còn; public gym 404 |
| INT-002 | Integrity | Ẩn PT ≠ ẩn gym | P0 | Admin | — | Ẩn PT | Gym vẫn public |
| INT-003 | Integrity | Khóa user giữ review/fav | P1 | Admin | User có data | Khóa | Data còn trong DB |
| INT-004 | Integrity | Rejected không vào avg public | P0 | Có rejected | Guest xem | avg chỉ approved |

---

## J. UI/UX

| ID | Module | Test name | Priority | Preconditions | Test data | Steps | Expected |
| --- | --- | --- | --- | --- | --- | --- | --- |
| UI-001 | UI | Header/nav active | P1 | Guest | Các trang | Điều hướng | Active đúng mục |
| UI-002 | UI | Modal confirm không native alert (admin) | P1 | Admin | Thao tác ẩn | Quan sát | Modal dự án, không `confirm()` nếu đã thống nhất |
| UI-003 | UI | Không lộ null/undefined/stack | P0 | Nhiều trang | — | Quét visual | Không hiện raw error |
| UI-004 | UI | Form review star picker | P0 | Login + detail | Viết đánh giá | Quan sát | Bấm sao, không select “X sao” |
| UI-005 | UI | Trainer card clickable | P0 | Guest | Card | Click ngoài nút Liên hệ | Vào detail |

---

## K. Responsive

| ID | Module | Test name | Priority | Preconditions | Test data | Steps | Expected |
| --- | --- | --- | --- | --- | --- | --- | --- |
| RESP-001 | Responsive | Home 390px | P1 | Emulation | 390×844 | Mở `/` | Không tràn ngang; menu hamburger |
| RESP-002 | Responsive | Gym search 390px | P1 | Emulation | — | Bộ lọc collapse | Mở/đóng được |
| RESP-003 | Responsive | Admin 768px | P1 | Admin | 768×1024 | Sidebar drawer | Mở/đóng OK |
| RESP-004 | Responsive | Desktop 1366 | P2 | — | — | Layout chuẩn | Sidebar admin cố định |

---

## L. Bảo mật cơ bản

| ID | Module | Test name | Priority | Preconditions | Test data | Steps | Expected |
| --- | --- | --- | --- | --- | --- | --- | --- |
| SEC-001 | Security | API favorites Guest | P0 | Guest | POST favorites | Gọi API | 401/403 |
| SEC-002 | Security | User gọi admin API | P0 | Login User | Admin endpoint | Gọi | 403 |
| SEC-003 | Security | CSRF form POST | P1 | Login | Bỏ/sai CSRF | POST | Từ chối |
| SEC-004 | Security | XSS comment | P1 | Login | `<script>` trong review | Submit + duyệt | Escape khi hiển thị |

---

## Ghi chú kỹ thuật kiểm thử

- **Star filter (search):** mode `filter` — “từ X★ trở lên”, có Bỏ chọn.  
- **Star input (review form):** mode `rate` — chọn đúng X sao.  
- **Kinh nghiệm:** 0–60; filter UI `0-2` / `3-5` / `6plus` (và alias `1-2` nếu còn).  
- **Không map/booking/payment/chat.**
