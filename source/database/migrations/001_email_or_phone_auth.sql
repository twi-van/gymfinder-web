-- Chạy một lần trên DB GymFinder hiện có để bật đăng ký/đăng nhập bằng email hoặc số điện thoại.
USE gymfinder;
ALTER TABLE users MODIFY email VARCHAR(255) NULL;
ALTER TABLE users ADD UNIQUE KEY uq_users_phone (phone);