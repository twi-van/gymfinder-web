CREATE DATABASE IF NOT EXISTS gymfinder
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gymfinder;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS favorites, reviews, gym_amenities, gym_categories,
                     gym_images, trainers, gyms, specialties, amenities,
                     categories, districts, users;
SET FOREIGN_KEY_CHECKS = 1;

DROP PROCEDURE IF EXISTS sp_refresh_rating;

CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name     VARCHAR(100) NOT NULL,
  email         VARCHAR(255) NOT NULL,
  phone         VARCHAR(15)  NULL,
  password_hash VARCHAR(255) NOT NULL,
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
  CONSTRAINT chk_users_phone CHECK (phone IS NULL OR phone REGEXP '^[+]?[0-9]{9,14}$')
) ENGINE=InnoDB;

CREATE TABLE districts (
  id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_districts_name (name),
  UNIQUE KEY uq_districts_slug (slug)
) ENGINE=InnoDB;

CREATE TABLE categories (
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

CREATE TABLE amenities (
  id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(80)  NOT NULL,
  slug VARCHAR(100) NOT NULL,
  icon VARCHAR(50)  NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_amenities_name (name),
  UNIQUE KEY uq_amenities_slug (slug)
) ENGINE=InnoDB;

CREATE TABLE specialties (
  id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(80)  NOT NULL,
  slug VARCHAR(100) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_specialties_name (name),
  UNIQUE KEY uq_specialties_slug (slug)
) ENGINE=InnoDB;

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
  price_min       INT UNSIGNED NOT NULL DEFAULT 0,
  price_max       INT UNSIGNED NOT NULL DEFAULT 0,
  opening_hours   VARCHAR(120) NULL,
  cover_image_url VARCHAR(255) NULL,
  avg_rating      DECIMAL(2,1) NOT NULL DEFAULT 0.0,
  review_count    INT UNSIGNED NOT NULL DEFAULT 0,
  is_featured     TINYINT(1) NOT NULL DEFAULT 0,
  status          ENUM('active','hidden') NOT NULL DEFAULT 'active',
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_gyms_slug (slug),
  KEY idx_gyms_district (district_id),
  KEY idx_gyms_price (price_min, price_max),
  KEY idx_gyms_rating (avg_rating),
  KEY idx_gyms_featured (is_featured, status),
  CONSTRAINT fk_gyms_district FOREIGN KEY (district_id) REFERENCES districts(id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_gyms_price  CHECK (price_max >= price_min),
  CONSTRAINT chk_gyms_rating CHECK (avg_rating BETWEEN 0 AND 5),
  CONSTRAINT chk_gyms_phone  CHECK (phone IS NULL OR phone REGEXP '^[+]?[0-9]{9,14}$'),
  CONSTRAINT chk_gyms_slug   CHECK (slug NOT REGEXP '^[0-9]+$')
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

CREATE TABLE gym_categories (
  gym_id      INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (gym_id, category_id),
  KEY idx_gc_category (category_id),
  CONSTRAINT fk_gc_gym      FOREIGN KEY (gym_id)      REFERENCES gyms(id)       ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_gc_category FOREIGN KEY (category_id) REFERENCES categories(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE gym_amenities (
  gym_id     INT UNSIGNED NOT NULL,
  amenity_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (gym_id, amenity_id),
  KEY idx_ga_amenity (amenity_id),
  CONSTRAINT fk_ga_gym     FOREIGN KEY (gym_id)     REFERENCES gyms(id)      ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_ga_amenity FOREIGN KEY (amenity_id) REFERENCES amenities(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

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
  gym_id           INT UNSIGNED NOT NULL,
  district_id      INT UNSIGNED NOT NULL,
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
  CONSTRAINT fk_trainers_gym       FOREIGN KEY (gym_id)       REFERENCES gyms(id)        ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_trainers_specialty FOREIGN KEY (specialty_id) REFERENCES specialties(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_trainers_district  FOREIGN KEY (district_id)  REFERENCES districts(id)   ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_trainers_exp    CHECK (years_experience <= 60),
  CONSTRAINT chk_trainers_rating CHECK (avg_rating BETWEEN 0 AND 5),
  CONSTRAINT chk_trainers_phone  CHECK (phone IS NULL OR phone REGEXP '^[+]?[0-9]{9,14}$'),
  CONSTRAINT chk_trainers_slug   CHECK (slug NOT REGEXP '^[0-9]+$')
) ENGINE=InnoDB;

CREATE TABLE reviews (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id       INT UNSIGNED NOT NULL,
  target_type   ENUM('gym','trainer') NOT NULL,
  gym_id        INT UNSIGNED NULL,
  trainer_id    INT UNSIGNED NULL,
  rating        TINYINT UNSIGNED NOT NULL,
  comment       TEXT NOT NULL,
  status        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  reviewed_by   INT UNSIGNED NULL,
  reviewed_at   DATETIME NULL,
  reject_reason VARCHAR(255) NULL,
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_review_user_gym     (user_id, gym_id),
  UNIQUE KEY uq_review_user_trainer (user_id, trainer_id),
  KEY idx_reviews_gym     (gym_id, status),
  KEY idx_reviews_trainer (trainer_id, status),
  KEY idx_reviews_status  (status, created_at),
  CONSTRAINT fk_reviews_user     FOREIGN KEY (user_id)     REFERENCES users(id)    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_reviews_gym      FOREIGN KEY (gym_id)      REFERENCES gyms(id)     ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_reviews_trainer  FOREIGN KEY (trainer_id)  REFERENCES trainers(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_reviews_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id)    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT chk_reviews_rating  CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

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
  CONSTRAINT fk_fav_trainer FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
