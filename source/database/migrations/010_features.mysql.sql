-- Đặc điểm phòng tập (catalog + gắn gym), idempotent cho MySQL đang chạy.
CREATE TABLE IF NOT EXISTS features (
  id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_features_name (name),
  UNIQUE KEY uq_features_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gym_features (
  gym_id     INT UNSIGNED NOT NULL,
  feature_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (gym_id, feature_id),
  KEY idx_gf_feature (feature_id),
  CONSTRAINT fk_gf_gym
    FOREIGN KEY (gym_id) REFERENCES gyms(id) ON DELETE CASCADE,
  CONSTRAINT fk_gf_feature
    FOREIGN KEY (feature_id) REFERENCES features(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO features (id, name, slug) VALUES
  (1, 'Mở cửa 24/7', 'open-247'),
  (2, 'Có huấn luyện viên (PT)', 'has-pt'),
  (3, 'Hỗ trợ giảm cân', 'ho-tro-giam-can');
