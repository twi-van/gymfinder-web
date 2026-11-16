-- Đồng bộ tài khoản demo (giống local). Mật khẩu: password
-- Railway: railway run -s MySQL -- bash -c 'mysql -h "$MYSQLHOST" -P "$MYSQLPORT" -u "$MYSQLUSER" -p"$MYSQLPASSWORD" "$MYSQLDATABASE" < database/seed_demo_users.sql'

SET NAMES utf8mb4;

INSERT INTO users (full_name, email, phone, password_hash, role, status, created_at) VALUES
('Admin GymFinder', 'u01@gymfinder.test', '0900000001', '$2y$10$/FRhOvA.xweR/mKOVrjXwukbYebNt3hanmHZaYIlAZNBlPwBH61R.', 'admin', 'active', '2023-10-01 09:00:00'),
('Nguyễn Văn An', 'u02@gymfinder.test', '0900000002', '$2y$10$/FRhOvA.xweR/mKOVrjXwukbYebNt3hanmHZaYIlAZNBlPwBH61R.', 'user', 'active', '2024-01-15 10:20:00'),
('Võ Thị Em (Khóa)', 'u25@gymfinder.test', '0900000025', '$2y$10$/FRhOvA.xweR/mKOVrjXwukbYebNt3hanmHZaYIlAZNBlPwBH61R.', 'user', 'locked', '2026-07-21 11:30:00')
ON DUPLICATE KEY UPDATE
  full_name = VALUES(full_name),
  phone = VALUES(phone),
  password_hash = VALUES(password_hash),
  role = VALUES(role),
  status = VALUES(status),
  must_change_password = 0,
  temp_password_expires_at = NULL;

SELECT id, email, role, status FROM users
WHERE email IN ('u01@gymfinder.test', 'u02@gymfinder.test', 'u25@gymfinder.test')
ORDER BY email;
