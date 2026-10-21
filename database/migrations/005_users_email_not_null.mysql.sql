-- Khớp đặc tả: email bắt buộc (NOT NULL + UNIQUE); phone tuỳ chọn (NULL + UNIQUE).
-- Chỉ chạy khi không còn user nào thiếu email:
--   SELECT id, email FROM users WHERE email IS NULL OR email = '';

ALTER TABLE users
  MODIFY COLUMN email VARCHAR(255) NOT NULL;

-- Nếu DB cũ từng có cột goal (ngoài đặc tả), có thể gỡ:
-- ALTER TABLE users DROP COLUMN goal;
