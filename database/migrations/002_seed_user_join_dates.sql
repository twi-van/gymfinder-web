-- Chạy một lần trên database demo hiện có để sửa ngày tham gia của tài khoản seed.
UPDATE users SET created_at = CASE email
  WHEN 'u01@gymfinder.test' THEN '2023-10-01 09:00:00'
  WHEN 'u02@gymfinder.test' THEN '2024-01-15 10:20:00'
  WHEN 'u03@gymfinder.test' THEN '2024-02-20 14:10:00'
  WHEN 'u04@gymfinder.test' THEN '2024-04-05 08:45:00'
  WHEN 'u05@gymfinder.test' THEN '2024-05-18 16:30:00'
  WHEN 'u06@gymfinder.test' THEN '2024-07-02 11:15:00'
  WHEN 'u07@gymfinder.test' THEN '2024-08-12 13:40:00'
  WHEN 'u08@gymfinder.test' THEN '2024-10-09 09:25:00'
  WHEN 'u09@gymfinder.test' THEN '2024-11-21 17:05:00'
  WHEN 'u10@gymfinder.test' THEN '2025-01-06 10:00:00'
  WHEN 'u11@gymfinder.test' THEN '2025-02-15 15:20:00'
  WHEN 'u12@gymfinder.test' THEN '2025-03-27 08:35:00'
  WHEN 'u13@gymfinder.test' THEN '2025-05-10 12:10:00'
  WHEN 'u14@gymfinder.test' THEN '2025-06-18 16:45:00'
  WHEN 'u15@gymfinder.test' THEN '2025-07-30 09:50:00'
  WHEN 'u16@gymfinder.test' THEN '2025-09-11 14:25:00'
  WHEN 'u17@gymfinder.test' THEN '2025-10-22 11:05:00'
  WHEN 'u18@gymfinder.test' THEN '2025-12-03 17:30:00'
  WHEN 'u19@gymfinder.test' THEN '2026-01-14 08:20:00'
  WHEN 'u20@gymfinder.test' THEN '2026-02-25 13:15:00'
  WHEN 'u21@gymfinder.test' THEN '2026-03-17 10:40:00'
  WHEN 'u22@gymfinder.test' THEN '2026-04-29 15:55:00'
  WHEN 'u23@gymfinder.test' THEN '2026-05-30 09:10:00'
  WHEN 'u24@gymfinder.test' THEN '2026-06-19 16:05:00'
  WHEN 'u25@gymfinder.test' THEN '2026-07-21 11:30:00'
  ELSE created_at
END
WHERE email IN (
  'u01@gymfinder.test','u02@gymfinder.test','u03@gymfinder.test','u04@gymfinder.test','u05@gymfinder.test',
  'u06@gymfinder.test','u07@gymfinder.test','u08@gymfinder.test','u09@gymfinder.test','u10@gymfinder.test',
  'u11@gymfinder.test','u12@gymfinder.test','u13@gymfinder.test','u14@gymfinder.test','u15@gymfinder.test',
  'u16@gymfinder.test','u17@gymfinder.test','u18@gymfinder.test','u19@gymfinder.test','u20@gymfinder.test',
  'u21@gymfinder.test','u22@gymfinder.test','u23@gymfinder.test','u24@gymfinder.test','u25@gymfinder.test'
);
