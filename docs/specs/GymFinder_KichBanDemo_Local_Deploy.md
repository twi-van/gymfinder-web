# Kịch bản demo GYMFINDER — Local → Deploy

**Dự án:** GYMFINDER — Tìm kiếm & đánh giá phòng tập / PT tại TP.HCM  
**Thời lượng gợi ý:** 8–12 phút  
**Người demo:** Vân (điều phối) + Lợi (BE/DB hỗ trợ nếu cần)  
**Cập nhật:** 2026-11-04

---

## 0. Chuẩn bị (trước giờ demo ~5 phút)

### Local

- Bật **MySQL** trong XAMPP.
- Chạy app:

```bash
cd /path/to/GYMFINDER
php -S localhost:8000 router.php
```

- Mở sẵn: <http://localhost:8000>
- Có `backend/ai.local.php` (Groq key) nếu demo AI.
- Có `backend/smtp.local.php` với `demo_otp: true` nếu demo quên mật khẩu.

### Deploy (InfinityFree)

- Link public (đổi đúng subdomain nhóm): `https://gymfinder.infinityfree.io`
- Kiểm tra trang chủ load được trước giờ demo.

### Tài khoản seed

| Vai trò | Email | Mật khẩu |
| --- | --- | --- |
| User | `u02@gymfinder.test` | `password` |
| Admin | `u01@gymfinder.test` | `password` |

Dùng **cửa sổ ẩn danh** riêng cho Guest / User / Admin để tránh lẫn session.

---

## Phần A — Demo LOCAL (cốt lõi, ~7 phút)

### A1. Mở đầu (~30 giây)

> GYMFINDER là website tìm kiếm và đánh giá phòng tập & huấn luyện viên cá nhân tại TP. Hồ Chí Minh, xây dựng bằng PHP SSR và MySQL. Nhóm demo trước trên môi trường local (XAMPP / PHP built-in server + MySQL), sau đó chuyển sang bản đã deploy để thầy/cô mở bằng link mà không cần cài đặt stack.

### A2. Guest (~2 phút)

1. Trang chủ → giới thiệu + lối tắt tìm gym / PT.
2. **Tìm phòng tập:** lọc quận / giá / tiện ích → mở chi tiết (ảnh, giá, đánh giá đã duyệt, thông tin liên hệ).
3. Bấm **yêu thích** → soft-gate (CTA đăng nhập / đăng ký, không redirect 302 cứng).
4. (Tuỳ chọn) **Trợ lý AI** trên widget: hỏi *“Gym Quận 7 giá dưới 500 nghìn”* → nhận gợi ý bám catalog.

**Lời thoại gợi ý:**

> Guest được xem, tìm kiếm và hỏi Trợ lý AI. Các thao tác thành viên như yêu thích sẽ hiện soft-gate thay vì đá thẳng sang login.

### A3. User (~2 phút)

1. Đăng nhập `u02@gymfinder.test` / `password`.
2. Lưu yêu thích → mở trang Yêu thích.
3. Gửi đánh giá (sao + nội dung) → trạng thái **chờ duyệt (pending)**.
4. (Tuỳ chọn) Quên mật khẩu: nhập email `u02@gymfinder.test` → OTP **hiện trên UI** (chế độ `demo_otp`) → đặt mật khẩu mới.

**Lời thoại gợi ý:**

> Đánh giá của user phải được Admin duyệt mới công khai và tính vào điểm trung bình. OTP quên mật khẩu có hiệu lực 10 phút, tối đa 5 lần thử sai. Đăng ký tài khoản không dùng OTP — OTP chỉ cho luồng quên mật khẩu.

### A4. Admin (~2 phút)

1. Đăng xuất → đăng nhập `u01@gymfinder.test` / `password` → vào `/admin`.
2. Xem **Dashboard**.
3. **Reviews:** duyệt hoặc từ chối (có thể kèm lý do).
4. **Gyms / Trainers:** soft-hide (tạm ẩn / kích hoạt lại, không xóa cứng).
5. **Users:** khóa / mở khóa tài khoản User (không khóa tài khoản Admin khác).

**Lời thoại gợi ý:**

> Admin dùng cổng riêng. Soft-hide và khóa user giúp kiểm soát nội dung / tài khoản nhưng vẫn giữ lịch sử đánh giá và yêu thích.

### A5. Chốt local (~15 giây)

> Phần local đã cover auth, tìm kiếm, yêu thích, đánh giá có duyệt, soft-hide, khóa user và Trợ lý AI. Tiếp theo là bản deploy public để xem trên tên miền free.

---

## Phần B — Demo DEPLOY (~3 phút)

### B1. Mở link public

> Nhóm deploy lên InfinityFree (subdomain free). Người xem chỉ cần trình duyệt và mạng — không cần chạy XAMPP, Docker hay `php -S` trên máy cá nhân.

Mở: `https://gymfinder.infinityfree.io`

### B2. Chạy nhanh 4 bước trên deploy

1. Trang chủ + tìm gym / PT (dữ liệu seed).
2. Login User `u02` → yêu thích / hồ sơ.
3. Login Admin `u01` → dashboard / duyệt hoặc xem danh sách quản trị.
4. Footer: **© 2026 GYMFINDER. Bảo lưu mọi quyền.**

### B3. Nói rõ hạn chế free hosting (bắt buộc)

> Trên InfinityFree free: Trợ lý AI (gọi `/api` / Groq) và gửi email OTP SMTP thật có thể bị hạn chế. Nhóm dùng `demo_otp` trên host để demo quên mật khẩu; AI đầy đủ ổn định hơn khi demo local. Quy trình nhóm: code chung trên GitHub, **một người** chịu trách nhiệm deploy lên InfinityFree để tránh ghi đè file trên File Manager.

---

## Phần C — Q&A nhanh

| Câu hỏi | Trả lời ngắn |
| --- | --- |
| Stack công nghệ? | PHP SSR, MySQL (XAMPP), Bootstrap 5; Trợ lý AI qua Groq API (`ai.local.php`). |
| Vì sao không đặt lịch / thanh toán? | Phạm vi MVP: tra cứu – đánh giá; user tự liên hệ qua thông tin công bố. |
| Soft-hide là gì? | Đổi `status` (ẩn/hiện), không xóa cứng dữ liệu. |
| Ai quản lý hosting? | Một người giữ account InfinityFree; cả nhóm làm việc qua GitHub. |
| Đăng ký có OTP không? | Không. OTP chỉ dùng cho quên mật khẩu. |
| Guest mở yêu thích? | Soft-gate CTA đăng nhập, không 302 cứng. |

---

## Thứ tự trình bày (tóm tắt)

1. Giới thiệu đề tài  
2. Guest (local)  
3. User (local)  
4. Admin (local)  
5. Mở link deploy  
6. Hạn chế free host + cách làm việc nhóm  
7. Q&A  

---

## Checklist phút chót

- [ ] Local `http://localhost:8000` chạy OK  
- [ ] Link deploy mở được  
- [ ] Nhớ mật khẩu seed `password`  
- [ ] AI local OK (nếu có demo AI)  
- [ ] `demo_otp: true` sẵn (nếu demo OTP)  
- [ ] Không mở file chứa API key / mật khẩu DB trước mặt hội đồng  

---

## Phân công lời thoại gợi ý

| Phần | Người nói |
| --- | --- |
| A1 mở đầu, A2 Guest, A3 User (FE/AI), B deploy + hạn chế | Vân |
| A4 Admin (BE/DB), bổ sung kỹ thuật CSDL / bảo mật nếu thầy hỏi | Lợi |
| Q&A | Cả hai |

---

## Tài liệu liên quan

- `docs/specs/GymFinder_HuongDanChay.md` — hướng dẫn chạy  
- `docs/PROJECT_STRUCTURE.md` — cấu trúc thư mục  
- `docs/qa/` — test plan / cases  
