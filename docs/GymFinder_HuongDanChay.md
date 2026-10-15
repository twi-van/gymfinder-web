# HƯỚNG DẪN KHỞI CHẠY & VẬN HÀNH WEBSITE

**Dự án:** GYMFINDER — Nền tảng tìm kiếm, đánh giá phòng tập & HLV  
**Phiên bản:** 1.0 (Production SSR PHP)  
**Cập nhật:** 2026-10-13

---

## 1. Yêu cầu môi trường & Cấu hình cần chuẩn bị

Hệ thống **GymFinder** được xây dựng bằng ngôn ngữ **PHP thuần (hỗ trợ PHP 8.0, 8.1, 8.2+)** kết hợp cơ sở dữ liệu **SQLite** (chạy cục bộ tự động) hoặc **MySQL** (khi tích hợp XAMPP). Dự án không yêu cầu cài đặt Node.js hay Composer phức tạp.

* **Hệ điều hành:** macOS, Windows hoặc Linux.
* **Trình duyệt khuyến nghị:** Google Chrome, Microsoft Edge, Safari, Firefox.
* **Máy chủ web cục bộ:** XAMPP, Laragon, WampServer hoặc PHP CLI tích hợp sẵn.

---

## 2. Cách 1: Khởi chạy nhanh bằng PHP Built-in Server (Khuyến nghị)

> **Ưu điểm:** Đây là cách chạy trực tiếp nhanh nhất. Hệ thống sẽ **tự động khởi tạo CSDL SQLite (`database/gymfinder.sqlite`)** và tự nạp toàn bộ bảng cùng dữ liệu mẫu đầy đủ lần đầu chạy mà không cần cấu hình MySQL.

### Bước 1: Mở Terminal (Command Prompt / PowerShell / macOS Terminal)
Di chuyển vào thư mục gốc của dự án:
```bash
cd /Users/tvan/Projects/GYMFINDER
```

### Bước 2: Chạy lệnh khởi động máy chủ
* **Trên máy Mac đã cài XAMPP:**
  ```bash
  /Applications/XAMPP/xamppfiles/bin/php -S localhost:8000 router.php
  ```
* **Hoặc nếu máy tính đã cấu hình lệnh `php` toàn cục:**
  ```bash
  php -S localhost:8000 router.php
  ```

### Bước 3: Mở trình duyệt trải nghiệm
Truy cập đường dẫn: **[http://localhost:8000](http://localhost:8000)**

---

## 3. Cách 2: Khởi chạy bằng XAMPP (Apache & MySQL)

Phù hợp khi cần demo hệ thống sử dụng MySQL theo đúng đề cương đồ án:

* **Bước 1:** Khởi động dịch vụ **Apache** và **MySQL** trên bảng điều khiển **XAMPP Control Panel**.
* **Bước 2:** Mở phpMyAdmin tại địa chỉ [http://localhost/phpmyadmin](http://localhost/phpmyadmin), tạo cơ sở dữ liệu mới có tên:
  * Database: `gymfinder`
  * Collation: `utf8mb4_unicode_ci`
* **Bước 3:** Nhập (Import) 2 file dữ liệu theo đúng thứ tự:
  1. Đầu tiên import file [`database/schema.sql`](file:///Users/tvan/Projects/GYMFINDER/database/schema.sql) để tạo cấu trúc bảng, khóa ngoại, ràng buộc CHECK và chỉ mục (theo thiết kế CSDL hiện tại, gồm các bảng trung gian `gym_trainers` và `trainer_specialties`).
  2. Sau đó import file [`database/seed.sql`](file:///Users/tvan/Projects/GYMFINDER/database/seed.sql) để nạp toàn bộ dữ liệu mẫu (quận huyện, danh mục, tiện ích, phòng tập, HLV, đánh giá, user).
* **Bước 4:** Copy thư mục `GYMFINDER` vào thư mục `htdocs` của XAMPP (hoặc tạo VirtualHost / Symlink).
* **Bước 5:** Truy cập website tại: **[http://localhost/GYMFINDER/](http://localhost/GYMFINDER/)**

---

## 4. Danh sách các đường dẫn & URL chính của hệ thống

| Trang chức năng | Đường dẫn URL (Port 8000) | Mô tả nghiệp vụ |
| :--- | :--- | :--- |
| **Trang chủ (Home)** | [http://localhost:8000/](http://localhost:8000/) | Banner hero, tìm kiếm nhanh, phòng gym & HLV nổi bật. |
| **Tìm kiếm phòng tập** | [http://localhost:8000/pages/gyms/search.php](http://localhost:8000/pages/gyms/search.php) | Bộ lọc nâng cao theo quận huyện, khoảng giá, tiện ích, loại hình. |
| **Chi tiết phòng tập** | [http://localhost:8000/pages/gyms/detail.php?id=1](http://localhost:8000/pages/gyms/detail.php?id=1) | Xem album ảnh, thông tin tiện ích, HLV trực thuộc, gửi review. |
| **Danh sách HLV (PT)** | [http://localhost:8000/pages/trainers/index.php](http://localhost:8000/pages/trainers/index.php) | Danh sách HLV cá nhân, lọc theo chuyên môn và phòng tập. |
| **Chi tiết HLV** | [http://localhost:8000/pages/trainers/detail.php?id=1](http://localhost:8000/pages/trainers/detail.php?id=1) | Hồ sơ HLV, thông tin phòng tập trực thuộc, đánh giá HLV. |
| **Đăng nhập** | [http://localhost:8000/pages/auth/login.php](http://localhost:8000/pages/auth/login.php) | Đăng nhập tài khoản bảo mật bằng session, chống brute-force. |
| **Đăng ký** | [http://localhost:8000/pages/auth/register.php](http://localhost:8000/pages/auth/register.php) | Tạo tài khoản mới, mã hóa mật khẩu `password_hash()`. |
| **Hồ sơ cá nhân** | [http://localhost:8000/pages/user/profile.php](http://localhost:8000/pages/user/profile.php) | Cập nhật thông tin cá nhân, tải ảnh đại diện (avatar), đổi mật khẩu. |
| **Danh sách Yêu thích** | [http://localhost:8000/pages/user/favorites.php](http://localhost:8000/pages/user/favorites.php) | Xem và quản lý các phòng gym và HLV đã lưu yêu thích. |
| **Khu vực Quản trị** | [http://localhost:8000/admin/pages/dashboard.php](http://localhost:8000/admin/pages/dashboard.php) | Dashboard 4 chỉ số thống kê tổng quan (Admin only). |
| **Quản lý phòng tập** | [http://localhost:8000/admin/pages/gyms.php](http://localhost:8000/admin/pages/gyms.php) | CRUD phòng tập, upload cover & gallery, gán tiện ích/danh mục. |
| **Quản lý HLV** | [http://localhost:8000/admin/pages/trainers.php](http://localhost:8000/admin/pages/trainers.php) | CRUD thông tin HLV, phân bổ phòng tập và chuyên môn. |
| **Quản lý người dùng** | [http://localhost:8000/admin/pages/users.php](http://localhost:8000/admin/pages/users.php) | Quản lý danh sách thành viên, khóa/mở khóa tài khoản. |
| **Quản lý danh mục** | [http://localhost:8000/admin/pages/categories.php](http://localhost:8000/admin/pages/categories.php) | Loại phòng tập, chuyên môn HLV, tiện ích, khu vực (theo tab). |
| **Kiểm duyệt Đánh giá** | [http://localhost:8000/admin/pages/reviews.php](http://localhost:8000/admin/pages/reviews.php) | Phê duyệt hoặc từ chối bình luận người dùng, tính lại rating. |
| **Hồ sơ Admin** | [http://localhost:8000/admin/pages/profile.php](http://localhost:8000/admin/pages/profile.php) | Xem và cập nhật thông tin hồ sơ, đổi mật khẩu quản trị viên. |

---

## 5. Tài khoản dùng thử để chấm điểm / kiểm thử (Demo Accounts)

Hệ thống đã tạo sẵn tài khoản mẫu để phục vụ công tác kiểm thử và chấm bài:

| Vai trò (Role) | Tài khoản (Email) | Mật khẩu | Quyền hạn truy cập |
| :--- | :--- | :--- | :--- |
| **Quản trị viên (Admin)** | `admin@gymfinder.vn`<br>hoặc `u01@gymfinder.test` | `Admin@123456`<br>hoặc `password` | Toàn quyền quản trị Dashboard, Gyms, Trainers, Users, Reviews, Categories, Profile. |
| **Thành viên (User)** | `user@gymfinder.vn`<br>hoặc `u02@gymfinder.test` | `User@123456`<br>hoặc `password` | Lưu yêu thích, viết/sửa/xóa đánh giá, đổi mật khẩu và quản lý hồ sơ. |
| **Khách vãng lai (Guest)** | *(Không cần đăng nhập)* | *(Không có)* | Tra cứu, tìm kiếm, lọc phòng tập & HLV công khai. |

---

## 6. Xử lý sự cố thường gặp (Troubleshooting)

### 1. Lỗi cổng 8000 bị chiếm dụng (`Address already in use`)
* **Nguyên nhân:** Đã có một tiến trình web server khác đang chạy ngầm trên cổng 8000.
* **Cách khắc phục:**
  * Tìm và đóng tiến trình cũ:
    ```bash
    lsof -i :8000
    kill -9 <PID_tìm_được>
    ```
  * Hoặc đổi sang chạy cổng khác (ví dụ 8080):
    ```bash
    /Applications/XAMPP/xamppfiles/bin/php -S localhost:8080 router.php
    ```

### 2. Lỗi không upload được ảnh hoặc không hiển thị ảnh
* **Cách khắc phục:** Cấp quyền ghi cho thư mục `uploads/`:
  ```bash
  chmod 777 uploads
  ```

### 3. Trình duyệt Safari hiển thị favicon cũ
* **Nguyên nhân:** Safari trên macOS lưu cache favicon rất sâu.
* **Cách khắc phục:**
  1. Mở cửa sổ ẩn danh bằng tổ hợp phím `Command + Shift + N` và truy cập `http://localhost:8000`.
  2. Hoặc dọn sạch cache favicon của Safari qua Terminal:
     ```bash
     rm -rf ~/Library/Safari/Favicon\ Cache/*
     ```
     Sau đó khởi động lại Safari.
