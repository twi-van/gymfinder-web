-- =====================================================================
-- GYMFINDER – Database Schema
-- MySQL 8.0.16+ (cần để CHECK constraint có hiệu lực) | InnoDB | utf8mb4
-- =====================================================================
CREATE DATABASE IF NOT EXISTS gymfinder
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gymfinder;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS favorites, reviews, gym_amenities, gym_categories,
                     gym_images, trainers, gyms, specialties, amenities,
                     categories, districts, users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. USERS (gồm cả Admin, phân biệt bằng role)
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name     VARCHAR(100) NOT NULL,
  email         VARCHAR(150) NOT NULL,
  phone         VARCHAR(15)  NULL,
  password_hash VARCHAR(255) NOT NULL,                -- password_hash() bcrypt/argon2
  avatar_url    VARCHAR(255) NULL,
  goal          ENUM('giam_can','tang_co','tang_suc_manh','cai_thien_suc_khoe') NULL,
  role          ENUM('user','admin') NOT NULL DEFAULT 'user',
  status        ENUM('active','locked') NOT NULL DEFAULT 'active',
  last_login_at DATETIME NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role_status (role, status),
  CONSTRAINT chk_users_phone CHECK (phone IS NULL OR phone REGEXP '^[0-9+]{9,15}$')
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. BẢNG DANH MỤC (lookup)
-- ---------------------------------------------------------------------
CREATE TABLE districts (                               -- Khu vực / Quận (mô phỏng "Gần bạn")
  id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_districts_name (name),
  UNIQUE KEY uq_districts_slug (slug)
) ENGINE=InnoDB;

CREATE TABLE categories (                              -- Category/Tag do Admin quản lý, dùng làm bộ lọc Gym
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(80)  NOT NULL,
  slug        VARCHAR(100) NOT NULL,
  description VARCHAR(255) NULL,
  is_active   TINYINT(1)   NOT NULL DEFAULT 1,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_name (name),
  UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB;

CREATE TABLE amenities (                               -- Tiện ích: Parking, Wifi, Sauna...
  id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(80)  NOT NULL,
  slug VARCHAR(100) NOT NULL,
  icon VARCHAR(50)  NULL,                              -- class FontAwesome, vd: fa-wifi
  PRIMARY KEY (id),
  UNIQUE KEY uq_amenities_name (name),
  UNIQUE KEY uq_amenities_slug (slug)
) ENGINE=InnoDB;

CREATE TABLE specialties (                             -- Chuyên môn PT
  id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(80)  NOT NULL,
  slug VARCHAR(100) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_specialties_name (name),
  UNIQUE KEY uq_specialties_slug (slug)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. GYMS
-- ---------------------------------------------------------------------
CREATE TABLE gyms (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name            VARCHAR(150) NOT NULL,
  slug            VARCHAR(180) NOT NULL,
  description     TEXT NULL,
  address         VARCHAR(255) NOT NULL,
  district_id     INT UNSIGNED NOT NULL,
  latitude        DECIMAL(10,7) NULL,
  longitude       DECIMAL(10,7) NULL,
  phone           VARCHAR(15)  NULL,
  email           VARCHAR(150) NULL,
  website         VARCHAR(255) NULL,
  price_min       INT UNSIGNED NOT NULL DEFAULT 0,     -- VND / tháng
  price_max       INT UNSIGNED NOT NULL DEFAULT 0,
  opening_hours   VARCHAR(120) NULL,                   -- vd: "05:00 - 22:00 hằng ngày"
  cover_image_url VARCHAR(255) NULL,
  avg_rating      DECIMAL(2,1) NOT NULL DEFAULT 0.0,   -- cache, cập nhật khi duyệt review
  review_count    INT UNSIGNED NOT NULL DEFAULT 0,
  is_featured     TINYINT(1) NOT NULL DEFAULT 0,       -- hiển thị ở Homepage
  status          ENUM('active','hidden') NOT NULL DEFAULT 'active',
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_gyms_slug (slug),
  KEY idx_gyms_district (district_id),
  KEY idx_gyms_price (price_min, price_max),
  KEY idx_gyms_rating (avg_rating),
  KEY idx_gyms_featured (is_featured, status),
  FULLTEXT KEY ft_gyms_search (name, description, address),
  CONSTRAINT fk_gyms_district FOREIGN KEY (district_id) REFERENCES districts(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_gyms_price  CHECK (price_max >= price_min),
  CONSTRAINT chk_gyms_rating CHECK (avg_rating BETWEEN 0 AND 5)
) ENGINE=InnoDB;

CREATE TABLE gym_images (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  gym_id     INT UNSIGNED NOT NULL,
  image_url  VARCHAR(255) NOT NULL,
  caption    VARCHAR(150) NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY idx_gym_images_gym (gym_id, sort_order),
  CONSTRAINT fk_gym_images_gym FOREIGN KEY (gym_id) REFERENCES gyms(id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE gym_categories (                          -- N-N Gym <-> Category (Tag)
  gym_id      INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (gym_id, category_id),
  KEY idx_gc_category (category_id),
  CONSTRAINT fk_gc_gym      FOREIGN KEY (gym_id)      REFERENCES gyms(id)       ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_gc_category FOREIGN KEY (category_id) REFERENCES categories(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE gym_amenities (                           -- N-N Gym <-> Amenity
  gym_id     INT UNSIGNED NOT NULL,
  amenity_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (gym_id, amenity_id),
  KEY idx_ga_amenity (amenity_id),
  CONSTRAINT fk_ga_gym     FOREIGN KEY (gym_id)     REFERENCES gyms(id)      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_ga_amenity FOREIGN KEY (amenity_id) REFERENCES amenities(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. TRAINERS (mỗi PT liên kết 1 Gym chính)
-- ---------------------------------------------------------------------
CREATE TABLE trainers (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name        VARCHAR(100) NOT NULL,
  slug             VARCHAR(130) NOT NULL,
  avatar_url       VARCHAR(255) NULL,
  specialty_id     INT UNSIGNED NOT NULL,
  years_experience TINYINT UNSIGNED NOT NULL DEFAULT 0,
  bio              TEXT NULL,
  phone            VARCHAR(15)  NULL,
  email            VARCHAR(150) NULL,
  gym_id           INT UNSIGNED NOT NULL,              -- Gym chính
  district_id      INT UNSIGNED NOT NULL,              -- Khu vực hoạt động
  avg_rating       DECIMAL(2,1) NOT NULL DEFAULT 0.0,
  review_count     INT UNSIGNED NOT NULL DEFAULT 0,
  is_featured      TINYINT(1) NOT NULL DEFAULT 0,
  status           ENUM('active','hidden') NOT NULL DEFAULT 'active',
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_trainers_slug (slug),
  KEY idx_trainers_gym (gym_id),
  KEY idx_trainers_specialty (specialty_id),
  KEY idx_trainers_district (district_id),
  KEY idx_trainers_exp (years_experience),
  FULLTEXT KEY ft_trainers_search (full_name, bio),
  CONSTRAINT fk_trainers_gym       FOREIGN KEY (gym_id)       REFERENCES gyms(id)        ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_trainers_specialty FOREIGN KEY (specialty_id) REFERENCES specialties(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_trainers_district  FOREIGN KEY (district_id)  REFERENCES districts(id)   ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_trainers_exp    CHECK (years_experience <= 60),
  CONSTRAINT chk_trainers_rating CHECK (avg_rating BETWEEN 0 AND 5)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. REVIEWS (Gym hoặc Trainer; duyệt: pending -> approved/rejected)
-- ---------------------------------------------------------------------
CREATE TABLE reviews (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id       INT UNSIGNED NOT NULL,
  target_type   ENUM('gym','trainer') NOT NULL,
  gym_id        INT UNSIGNED NULL,
  trainer_id    INT UNSIGNED NULL,
  rating        TINYINT UNSIGNED NOT NULL,
  comment       TEXT NOT NULL,
  status        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  reviewed_by   INT UNSIGNED NULL,                     -- admin duyệt
  reviewed_at   DATETIME NULL,
  reject_reason VARCHAR(255) NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_review_user_gym     (user_id, gym_id),      -- 1 user / 1 gym / 1 review
  UNIQUE KEY uq_review_user_trainer (user_id, trainer_id),  -- (NULL không vi phạm UNIQUE)
  KEY idx_reviews_gym     (gym_id, status),
  KEY idx_reviews_trainer (trainer_id, status),
  KEY idx_reviews_status  (status, created_at),
  CONSTRAINT fk_reviews_user     FOREIGN KEY (user_id)     REFERENCES users(id)    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_reviews_gym      FOREIGN KEY (gym_id)      REFERENCES gyms(id)     ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_reviews_trainer  FOREIGN KEY (trainer_id)  REFERENCES trainers(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_reviews_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id)    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT chk_reviews_rating  CHECK (rating BETWEEN 1 AND 5),
  CONSTRAINT chk_reviews_target  CHECK (
    (target_type = 'gym'     AND gym_id IS NOT NULL AND trainer_id IS NULL) OR
    (target_type = 'trainer' AND trainer_id IS NOT NULL AND gym_id IS NULL)
  )
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 6. FAVORITES (đồng bộ theo tài khoản ở giai đoạn Backend)
-- ---------------------------------------------------------------------
CREATE TABLE favorites (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NOT NULL,
  target_type ENUM('gym','trainer') NOT NULL,
  gym_id      INT UNSIGNED NULL,
  trainer_id  INT UNSIGNED NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_fav_user_gym     (user_id, gym_id),
  UNIQUE KEY uq_fav_user_trainer (user_id, trainer_id),
  KEY idx_fav_user (user_id, created_at),
  CONSTRAINT fk_fav_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_fav_gym     FOREIGN KEY (gym_id)     REFERENCES gyms(id)     ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_fav_trainer FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT chk_fav_target CHECK (
    (target_type = 'gym'     AND gym_id IS NOT NULL AND trainer_id IS NULL) OR
    (target_type = 'trainer' AND trainer_id IS NOT NULL AND gym_id IS NULL)
  )
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. TRIGGER cập nhật avg_rating / review_count khi review đổi trạng thái
--    Chỉ review 'approved' mới được tính.
-- ---------------------------------------------------------------------
DELIMITER $$

CREATE PROCEDURE sp_refresh_rating(IN p_type VARCHAR(10), IN p_gym INT UNSIGNED, IN p_trainer INT UNSIGNED)
BEGIN
  IF p_type = 'gym' THEN
    UPDATE gyms g SET
      g.avg_rating   = COALESCE((SELECT ROUND(AVG(rating),1) FROM reviews WHERE gym_id = p_gym AND status='approved'), 0),
      g.review_count = (SELECT COUNT(*) FROM reviews WHERE gym_id = p_gym AND status='approved')
    WHERE g.id = p_gym;
  ELSE
    UPDATE trainers t SET
      t.avg_rating   = COALESCE((SELECT ROUND(AVG(rating),1) FROM reviews WHERE trainer_id = p_trainer AND status='approved'), 0),
      t.review_count = (SELECT COUNT(*) FROM reviews WHERE trainer_id = p_trainer AND status='approved')
    WHERE t.id = p_trainer;
  END IF;
END$$

CREATE TRIGGER trg_reviews_after_insert AFTER INSERT ON reviews FOR EACH ROW
BEGIN
  IF NEW.status = 'approved' THEN CALL sp_refresh_rating(NEW.target_type, NEW.gym_id, NEW.trainer_id); END IF;
END$$

CREATE TRIGGER trg_reviews_after_update AFTER UPDATE ON reviews FOR EACH ROW
BEGIN
  IF OLD.status <> NEW.status OR OLD.rating <> NEW.rating THEN
    CALL sp_refresh_rating(NEW.target_type, NEW.gym_id, NEW.trainer_id);
  END IF;
END$$

CREATE TRIGGER trg_reviews_after_delete AFTER DELETE ON reviews FOR EACH ROW
BEGIN
  IF OLD.status = 'approved' THEN CALL sp_refresh_rating(OLD.target_type, OLD.gym_id, OLD.trainer_id); END IF;
END$$

DELIMITER ;
