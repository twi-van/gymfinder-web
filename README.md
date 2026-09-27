# GYMFINDER

## Nền tảng tìm kiếm phòng tập & Huấn luyện viên

GYMFINDER là ứng dụng web hỗ trợ người dùng tìm kiếm, khám phá và lựa chọn phòng tập Gym/Fitness và Huấn luyện viên cá nhân (PT) phù hợp với nhu cầu.

Hệ thống gồm hai khu vực chính:

- **User Portal:** Tìm kiếm phòng tập, HLV, xem chi tiết, yêu thích và đánh giá.
- **Admin Portal:** Quản lý phòng tập, HLV, người dùng, danh mục và đánh giá.

---

## 1. Chức năng chính

### User

- Tìm kiếm và lọc phòng tập theo tên, khu vực, giá, đánh giá, loại hình và tiện ích.
- Xem danh sách và chi tiết phòng tập.
- Xem vị trí phòng tập trên bản đồ.
- Tìm kiếm và xem thông tin Huấn luyện viên.
- Lưu phòng tập và HLV yêu thích.
- Quản lý thông tin cá nhân.
- Xem và gửi đánh giá phòng tập.
- Đăng ký, đăng nhập và đăng xuất.

### Admin

- Dashboard thống kê tổng quan.
- Quản lý phòng tập: thêm, sửa, xóa, xem chi tiết.
- Quản lý Huấn luyện viên.
- Quản lý người dùng và trạng thái tài khoản.
- Quản lý đánh giá.
- Quản lý danh mục phòng tập.
- Phân quyền truy cập Admin Portal.

---

## 2. Danh mục phòng tập

GYMFINDER hỗ trợ các nhóm nhu cầu:

- Gym phổ thông
- Gym cao cấp
- Gym 24/7
- Gym gần bạn
- Gym có PT
- Gym giá tốt
- Gym cho nữ
- Gym hỗ trợ giảm cân

---

## 3. Công nghệ sử dụng

### Frontend

- HTML5
- CSS3
- Bootstrap 5.3
- JavaScript ES6
- jQuery 3.7.0
- FontAwesome 6
- Google Fonts - Inter

### Backend

- PHP

### Database

- MySQL

### Tools

- Visual Studio Code
- Git / GitHub

---

## 4. Kiến trúc hệ thống

```text
                    GYMFINDER
                        │
             ┌──────────┴──────────┐
             │                     │
        USER PORTAL            ADMIN PORTAL
             │                     │
             └──────────┬──────────┘
                        │
                    FRONTEND
                HTML / CSS / JS
                        │
                     PHP
                    BACKEND
                        │
                    MySQL
                   DATABASE