-- Khớp đặc tả: email bắt buộc (NOT NULL + UNIQUE); phone tuỳ chọn (NULL + UNIQUE).
-- PRECONDITION: inspect the query result and stop if any rows are returned.
-- Supply a real email before continuing; do not invent addresses. The existing
-- UNIQUE index prevents duplicates; the ALTER below enforces NOT NULL.
SELECT id, email FROM users WHERE email IS NULL OR TRIM(email) = '';

ALTER TABLE users
  MODIFY COLUMN email VARCHAR(255) NOT NULL;
-- Cột users.goal được gỡ riêng bằng 007_drop_users_goal.mysql.sql.
