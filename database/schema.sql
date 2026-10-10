-- =====================================================================
-- GYMFINDER – Database Schema
-- MySQL 8.0.16+  |  InnoDB  |  utf8mb4_unicode_ci  |  UTC
-- Spec: ĐẶC TẢ CƠ SỞ DỮ LIỆU v1.0
-- =====================================================================

CREATE DATABASE IF NOT EXISTS gymfinder
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gymfinder;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS
  favorites, reviews,
  gym_amenities, gym_categories, gym_images,
  trainers, gyms,
  specialties, amenities, categories, districts,
  users;
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- 1. users
-- Guest không lưu trong users; Admin là role='admin'
-- =====================================================================
CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name     VARCHAR(100) NOT NULL,
  email         VARCHAR(255) NOT NULL,
  phone         VARCHAR(20)  NULL,
  password_hash VARCHAR(255) NOT NULL,
  avatar_url    VARCHAR(255) NULL,
  goal          ENUM('giam_can','tang_co','tang_suc_manh','cai_thien_suc_khoe') NULL,
  role          ENUM('user','admin')    NOT NULL DEFAULT 'user',
  status        ENUM('active','locked') NOT NULL DEFAULT 'active',
  last_login_at DATETIME NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role_status (role, status),

  CONSTRAINT chk_users_phone
    CHECK (phone IS NULL OR phone REGEXP '^\\+?[0-9]{9,14}$')
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 2. districts
-- =====================================================================
CREATE TABLE districts (
  id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL,

  PRIMARY KEY (id),
  UNIQUE KEY uq_districts_name (name),
  UNIQUE KEY uq_districts_slug (slug)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 3. categories
-- description là TEXT (spec §4.2); is_active CHECK (0,1)
-- =====================================================================
CREATE TABLE categories (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name        VARCHAR(100) NOT NULL,
  slug        VARCHAR(120) NOT NULL,
  description TEXT         NULL,
  is_active   TINYINT(1)   NOT NULL DEFAULT 1,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_name (name),
  UNIQUE KEY uq_categories_slug (slug),

  CONSTRAINT chk_categories_active CHECK (is_active IN (0, 1))
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 4. amenities
-- icon VARCHAR(100): FontAwesome class (spec §4.2)
-- =====================================================================
CREATE TABLE amenities (
  id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL,
  icon VARCHAR(100) NULL,

  PRIMARY KEY (id),
  UNIQUE KEY uq_amenities_name (name),
  UNIQUE KEY uq_amenities_slug (slug)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 5. specialties
-- =====================================================================
CREATE TABLE specialties (
  id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL,

  PRIMARY KEY (id),
  UNIQUE KEY uq_specialties_name (name),
  UNIQUE KEY uq_specialties_slug (slug)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 6. gyms
-- ON DELETE RESTRICT cho FK districts (spec §3)
-- Không khai báo ON UPDATE (spec §3)
-- =====================================================================
CREATE TABLE gyms (
  id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  name            VARCHAR(150)  NOT NULL,
  slug            VARCHAR(180)  NOT NULL,
  description     TEXT          NULL,
  address         VARCHAR(255)  NOT NULL,
  district_id     INT UNSIGNED  NOT NULL,
  latitude        DECIMAL(10,7) NULL,
  longitude       DECIMAL(10,7) NULL,
  phone           VARCHAR(20)   NULL,
  email           VARCHAR(255)  NULL,
  website         VARCHAR(255)  NULL,
  price_min       INT UNSIGNED  NOT NULL,
  price_max       INT UNSIGNED  NOT NULL,
  opening_hours   VARCHAR(120)  NULL,
  cover_image_url VARCHAR(255)  NULL,
  avg_rating      DECIMAL(2,1)  NOT NULL DEFAULT 0,
  review_count    INT UNSIGNED  NOT NULL DEFAULT 0,
  is_featured     TINYINT(1)    NOT NULL DEFAULT 0,
  status          ENUM('active','hidden') NOT NULL DEFAULT 'active',
  created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_gyms_slug (slug),

  -- Indexes (spec §4.9)
  KEY idx_gyms_district        (district_id),
  KEY idx_gyms_price           (price_min, price_max),
  KEY idx_gyms_rating          (avg_rating),
  KEY idx_gyms_featured_status (is_featured, status),
  KEY idx_gyms_created_at      (created_at),

  -- FK: không khai báo ON UPDATE (spec §3)
  CONSTRAINT fk_gyms_district
    FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE RESTRICT,

  -- CHECK (spec §9) – chỉ cột không tham gia FK
  CONSTRAINT chk_gyms_slug
    CHECK (slug NOT REGEXP '^[0-9]+$'),
  CONSTRAINT chk_gyms_price
    CHECK (price_max >= price_min),
  CONSTRAINT chk_gyms_geo_lat
    CHECK (latitude  IS NULL OR (latitude  BETWEEN -90  AND 90)),
  CONSTRAINT chk_gyms_geo_lng
    CHECK (longitude IS NULL OR (longitude BETWEEN -180 AND 180)),
  CONSTRAINT chk_gyms_geo_pair
    CHECK ((latitude IS NULL) = (longitude IS NULL)),
  CONSTRAINT chk_gyms_phone
    CHECK (phone IS NULL OR phone REGEXP '^\\+?[0-9]{9,14}$'),
  CONSTRAINT chk_gyms_rating
    CHECK (avg_rating BETWEEN 0 AND 5),
  CONSTRAINT chk_gyms_featured
    CHECK (is_featured IN (0, 1))
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 7. gym_images
-- UNIQUE (gym_id, image_url) – spec §4.4
-- ON DELETE CASCADE – spec §3
-- =====================================================================
CREATE TABLE gym_images (
  id         INT UNSIGNED      NOT NULL AUTO_INCREMENT,
  gym_id     INT UNSIGNED      NOT NULL,
  image_url  VARCHAR(255)      NOT NULL,
  caption    VARCHAR(150)      NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,

  PRIMARY KEY (id),
  UNIQUE KEY uq_gym_images_url   (gym_id, image_url),
  KEY        idx_gym_images_order (gym_id, sort_order),

  CONSTRAINT fk_gym_images_gym
    FOREIGN KEY (gym_id) REFERENCES gyms(id) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 8. gym_categories  (N-N; cả hai ON DELETE CASCADE – spec §3)
-- =====================================================================
CREATE TABLE gym_categories (
  gym_id      INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,

  PRIMARY KEY (gym_id, category_id),
  KEY idx_gc_category (category_id),

  CONSTRAINT fk_gc_gym
    FOREIGN KEY (gym_id)      REFERENCES gyms(id)       ON DELETE CASCADE,
  CONSTRAINT fk_gc_category
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 9. gym_amenities  (N-N; cả hai ON DELETE CASCADE – spec §3)
-- =====================================================================
CREATE TABLE gym_amenities (
  gym_id     INT UNSIGNED NOT NULL,
  amenity_id INT UNSIGNED NOT NULL,

  PRIMARY KEY (gym_id, amenity_id),
  KEY idx_ga_amenity (amenity_id),

  CONSTRAINT fk_ga_gym
    FOREIGN KEY (gym_id)     REFERENCES gyms(id)      ON DELETE CASCADE,
  CONSTRAINT fk_ga_amenity
    FOREIGN KEY (amenity_id) REFERENCES amenities(id) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 10. trainers
-- gym_id NOT NULL: mỗi Trainer có đúng một Gym chính
-- district_id denormalized = Gym chính district (spec §10)
-- =====================================================================
CREATE TABLE trainers (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name        VARCHAR(100) NOT NULL,
  slug             VARCHAR(130) NOT NULL,
  avatar_url       VARCHAR(255) NULL,
  specialty_id     INT UNSIGNED NOT NULL,
  years_experience TINYINT UNSIGNED NOT NULL,
  bio              TEXT         NULL,
  phone            VARCHAR(20)  NULL,
  email            VARCHAR(255) NULL,
  gym_id           INT UNSIGNED NOT NULL,
  district_id      INT UNSIGNED NOT NULL,
  avg_rating       DECIMAL(2,1) NOT NULL DEFAULT 0,
  review_count     INT UNSIGNED NOT NULL DEFAULT 0,
  is_featured      TINYINT(1)   NOT NULL DEFAULT 0,
  status           ENUM('active','hidden') NOT NULL DEFAULT 'active',
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_trainers_slug (slug),

  -- Indexes (spec §4.9)
  KEY idx_trainers_status_featured (status, is_featured),
  KEY idx_trainers_exp             (years_experience),
  KEY idx_trainers_specialty       (specialty_id),
  KEY idx_trainers_gym             (gym_id),
  KEY idx_trainers_district        (district_id),

  -- FK: không khai báo ON UPDATE (spec §3)
  CONSTRAINT fk_trainers_gym
    FOREIGN KEY (gym_id)       REFERENCES gyms(id)        ON DELETE RESTRICT,
  CONSTRAINT fk_trainers_specialty
    FOREIGN KEY (specialty_id) REFERENCES specialties(id) ON DELETE RESTRICT,
  CONSTRAINT fk_trainers_district
    FOREIGN KEY (district_id)  REFERENCES districts(id)   ON DELETE RESTRICT,

  -- CHECK (spec §9)
  CONSTRAINT chk_trainers_slug
    CHECK (slug NOT REGEXP '^[0-9]+$'),
  CONSTRAINT chk_trainers_exp
    CHECK (years_experience BETWEEN 0 AND 60),
  CONSTRAINT chk_trainers_rating
    CHECK (avg_rating BETWEEN 0 AND 5),
  CONSTRAINT chk_trainers_phone
    CHECK (phone IS NULL OR phone REGEXP '^\\+?[0-9]{9,14}$'),
  CONSTRAINT chk_trainers_featured
    CHECK (is_featured IN (0, 1))
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 11. reviews
-- gym_id / trainer_id nullable; target integrity enforced by Backend
-- reviewed_by ON DELETE SET NULL (Admin bị xóa → NULL, không CHECK)
-- CHECK chỉ dùng cột không thuộc FK (lý do MySQL – spec §3)
-- =====================================================================
CREATE TABLE reviews (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id       INT UNSIGNED NOT NULL,
  target_type   ENUM('gym','trainer') NOT NULL,
  gym_id        INT UNSIGNED NULL,
  trainer_id    INT UNSIGNED NULL,
  rating        TINYINT UNSIGNED NOT NULL,
  comment       TEXT         NOT NULL,
  status        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  reviewed_by   INT UNSIGNED NULL,
  reviewed_at   DATETIME     NULL,
  reject_reason VARCHAR(255) NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),

  -- Mỗi User chỉ review mỗi Gym/Trainer một lần (spec §4.7)
  UNIQUE KEY uq_review_user_gym     (user_id, gym_id),
  UNIQUE KEY uq_review_user_trainer (user_id, trainer_id),

  -- Indexes (spec §4.9)
  KEY idx_reviews_gym     (gym_id,     status, created_at),
  KEY idx_reviews_trainer (trainer_id, status, created_at),
  KEY idx_reviews_user    (user_id),

  -- FK: không khai báo ON UPDATE (spec §3)
  CONSTRAINT fk_reviews_user
    FOREIGN KEY (user_id)     REFERENCES users(id)    ON DELETE CASCADE,
  CONSTRAINT fk_reviews_gym
    FOREIGN KEY (gym_id)      REFERENCES gyms(id)     ON DELETE CASCADE,
  CONSTRAINT fk_reviews_trainer
    FOREIGN KEY (trainer_id)  REFERENCES trainers(id) ON DELETE CASCADE,
  CONSTRAINT fk_reviews_reviewer
    FOREIGN KEY (reviewed_by) REFERENCES users(id)    ON DELETE SET NULL,

  -- CHECK chỉ trên cột không tham gia FK (spec §3)
  CONSTRAINT chk_reviews_rating
    CHECK (rating BETWEEN 1 AND 5),
  CONSTRAINT chk_reviews_comment
    CHECK (CHAR_LENGTH(comment) BETWEEN 1 AND 2000),
  -- rejected → reject_reason bắt buộc; non-rejected → NULL
  CONSTRAINT chk_review_reject
    CHECK (
      (status = 'rejected'  AND reject_reason IS NOT NULL AND CHAR_LENGTH(reject_reason) >= 1)
      OR (status <> 'rejected' AND reject_reason IS NULL)
    ),
  -- pending → reviewed_at phải NULL (spec §4.7)
  CONSTRAINT chk_review_pending
    CHECK (status <> 'pending' OR reviewed_at IS NULL)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 12. favorites
-- gym_id / trainer_id nullable; target integrity enforced by Backend
-- spec §4.8
-- =====================================================================
CREATE TABLE favorites (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NOT NULL,
  target_type ENUM('gym','trainer') NOT NULL,
  gym_id      INT UNSIGNED NULL,
  trainer_id  INT UNSIGNED NULL,
  created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (id),

  UNIQUE KEY uq_fav_user_gym     (user_id, gym_id),
  UNIQUE KEY uq_fav_user_trainer (user_id, trainer_id),

  -- Indexes (spec §4.9)
  KEY idx_fav_user    (user_id),
  KEY idx_fav_gym     (gym_id),
  KEY idx_fav_trainer (trainer_id),

  -- FK: không khai báo ON UPDATE (spec §3)
  CONSTRAINT fk_fav_user
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
  CONSTRAINT fk_fav_gym
    FOREIGN KEY (gym_id)     REFERENCES gyms(id)     ON DELETE CASCADE,
  CONSTRAINT fk_fav_trainer
    FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
