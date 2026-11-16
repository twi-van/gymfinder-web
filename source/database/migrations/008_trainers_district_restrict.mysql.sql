-- Khớp đặc tả CSDL: trainers.district_id ON DELETE RESTRICT (không SET NULL).
-- Chạy trên MySQL đã có schema cũ. SQLite seed mới đã RESTRICT trong sqlite_install.
USE gymfinder;

ALTER TABLE trainers DROP FOREIGN KEY fk_trainers_district;
ALTER TABLE trainers
  ADD CONSTRAINT fk_trainers_district
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE RESTRICT;
