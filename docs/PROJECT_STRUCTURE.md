# GYMFINDER - Project Structure
Tài liệu này mô tả chức năng của các thư mục và file chính trong project GYMFINDER.
## 1. Root Files

### README.md

→ Giới thiệu tổng quan về project, chức năng, công nghệ và hướng dẫn chạy project.

### index.html

→ Trang chủ (Homepage) của User Portal.

## 2. assets/
Chứa tài nguyên Frontend.

assets/css/
→ CSS dùng chung và CSS component.

assets/images/
→ Hình ảnh Gym, Trainer, logo, screenshots...

assets/js/core/
→ Logic dùng chung như authentication, UI, favorites.

assets/js/pages/
→ Logic riêng từng screen.

## 3. data/
Chứa Mock Data hiện tại.

gyms.js
→ Mock data phòng Gym.

trainers.js
→ Mock data HLV.

categories.js
→ Category dùng cho Gym filter.

reviews.js
→ Mock data đánh giá.

users.js
→ Mock data người dùng.

## 4. pages/
Chứa các screen User.

pages/auth/
→ Login/Register.

pages/gyms/
→ Gym Search và Gym Detail.

pages/trainers/
→ Trainer List và Trainer Detail.

pages/user/
→ Profile và Favorites.

## 5. admin/
Chứa giao diện Admin.

admin/assets/
→ CSS và JS riêng biệt cho khu vực Admin.

admin/components/
→ Các component giao diện dùng chung trong Admin (Header, Sidebar).

admin/pages/dashboard.html
→ Thống kê tổng quan.

admin/pages/gyms.html
→ Quản lý Gym.

admin/pages/trainers.html
→ Quản lý HLV.

admin/pages/users.html
→ Quản lý Người dùng.

admin/pages/categories.html
→ Quản lý Danh mục.

admin/pages/reviews.html
→ Quản lý Đánh giá.

## 6. backend/
→ Backend/API.
→ Không tự ý sửa khi chưa thống nhất với Backend.

## 7. database/
→ Database schema, SQL, ERD.

## 8. docs/
→ Đặc tả, ERD guide, tài liệu bàn giao.

## 9. scripts/
→ Các mã lệnh (script) tiện ích hỗ trợ dự án.

