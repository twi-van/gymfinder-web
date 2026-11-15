<?php
declare(strict_types=1);

function gf_sqlite_install(PDO $pdo): void
{
    $pdo->exec('PRAGMA foreign_keys = OFF');
    $pdo->exec(<<<'SQL'
DROP TABLE IF EXISTS favorites;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS gym_features;
DROP TABLE IF EXISTS gym_amenities;
DROP TABLE IF EXISTS gym_categories;
DROP TABLE IF EXISTS gym_images;
DROP TABLE IF EXISTS gym_trainers;
DROP TABLE IF EXISTS trainer_specialties;
DROP TABLE IF EXISTS trainers;
DROP TABLE IF EXISTS gyms;
DROP TABLE IF EXISTS specialties;
DROP TABLE IF EXISTS features;
DROP TABLE IF EXISTS amenities;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS districts;
DROP TABLE IF EXISTS users;

CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  full_name TEXT NOT NULL,
  email TEXT NOT NULL UNIQUE,
  phone TEXT UNIQUE,
  password_hash TEXT NOT NULL,
  avatar_url TEXT,
  role TEXT NOT NULL DEFAULT 'user',
  status TEXT NOT NULL DEFAULT 'active',
  must_change_password INTEGER NOT NULL DEFAULT 0,
  temp_password_expires_at TEXT,
  last_login_at TEXT,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS districts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL UNIQUE,
  slug TEXT NOT NULL UNIQUE
);
CREATE TABLE IF NOT EXISTS categories (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL UNIQUE,
  slug TEXT NOT NULL UNIQUE,
  description TEXT,
  is_active INTEGER NOT NULL DEFAULT 1,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS amenities (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL UNIQUE,
  slug TEXT NOT NULL UNIQUE,
  icon TEXT
);
CREATE TABLE IF NOT EXISTS specialties (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL UNIQUE,
  slug TEXT NOT NULL UNIQUE
);
CREATE TABLE IF NOT EXISTS features (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL UNIQUE,
  slug TEXT NOT NULL UNIQUE
);
CREATE TABLE IF NOT EXISTS gyms (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  slug TEXT NOT NULL UNIQUE,
  description TEXT,
  address TEXT NOT NULL,
  district_id INTEGER NOT NULL,
  latitude REAL,
  longitude REAL,
  phone TEXT,
  email TEXT,
  website TEXT,
  price_min INTEGER NOT NULL,
  price_max INTEGER NOT NULL,
  opening_hours TEXT,
  cover_image_url TEXT,
  avg_rating REAL NOT NULL DEFAULT 0,
  review_count INTEGER NOT NULL DEFAULT 0,
  is_featured INTEGER NOT NULL DEFAULT 0,
  status TEXT NOT NULL DEFAULT 'active',
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (district_id) REFERENCES districts(id)
);
CREATE TABLE IF NOT EXISTS gym_images (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  gym_id INTEGER NOT NULL,
  image_url TEXT NOT NULL,
  caption TEXT,
  sort_order INTEGER NOT NULL DEFAULT 0,
  FOREIGN KEY (gym_id) REFERENCES gyms(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS gym_categories (
  gym_id INTEGER NOT NULL,
  category_id INTEGER NOT NULL,
  PRIMARY KEY (gym_id, category_id),
  FOREIGN KEY (gym_id) REFERENCES gyms(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
);
CREATE TABLE IF NOT EXISTS gym_amenities (
  gym_id INTEGER NOT NULL,
  amenity_id INTEGER NOT NULL,
  PRIMARY KEY (gym_id, amenity_id),
  FOREIGN KEY (gym_id) REFERENCES gyms(id) ON DELETE CASCADE,
  FOREIGN KEY (amenity_id) REFERENCES amenities(id) ON DELETE RESTRICT
);
CREATE TABLE IF NOT EXISTS gym_features (
  gym_id INTEGER NOT NULL,
  feature_id INTEGER NOT NULL,
  PRIMARY KEY (gym_id, feature_id),
  FOREIGN KEY (gym_id) REFERENCES gyms(id) ON DELETE CASCADE,
  FOREIGN KEY (feature_id) REFERENCES features(id) ON DELETE RESTRICT
);
CREATE TABLE IF NOT EXISTS trainers (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  full_name TEXT NOT NULL,
  slug TEXT NOT NULL UNIQUE,
  avatar_url TEXT,
  specialty_id INTEGER,
  years_experience INTEGER NOT NULL,
  bio TEXT,
  phone TEXT,
  email TEXT,
  gym_id INTEGER,
  district_id INTEGER,
  avg_rating REAL NOT NULL DEFAULT 0,
  review_count INTEGER NOT NULL DEFAULT 0,
  is_featured INTEGER NOT NULL DEFAULT 0,
  status TEXT NOT NULL DEFAULT 'active',
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (gym_id) REFERENCES gyms(id) ON DELETE SET NULL,
  FOREIGN KEY (specialty_id) REFERENCES specialties(id) ON DELETE SET NULL,
  FOREIGN KEY (district_id) REFERENCES districts(id) ON DELETE RESTRICT,
  CHECK (years_experience BETWEEN 0 AND 60)
);
CREATE TABLE IF NOT EXISTS trainer_specialties (
  trainer_id INTEGER NOT NULL,
  specialty_id INTEGER NOT NULL,
  PRIMARY KEY (trainer_id, specialty_id),
  FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE RESTRICT,
  FOREIGN KEY (specialty_id) REFERENCES specialties(id) ON DELETE RESTRICT
);
CREATE TABLE IF NOT EXISTS gym_trainers (
  gym_id INTEGER NOT NULL,
  trainer_id INTEGER NOT NULL,
  PRIMARY KEY (gym_id, trainer_id),
  FOREIGN KEY (gym_id) REFERENCES gyms(id) ON DELETE RESTRICT,
  FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE RESTRICT
);
CREATE INDEX IF NOT EXISTS idx_ts_specialty ON trainer_specialties(specialty_id);
CREATE INDEX IF NOT EXISTS idx_gt_trainer ON gym_trainers(trainer_id);
CREATE TABLE IF NOT EXISTS reviews (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  target_type TEXT NOT NULL,
  gym_id INTEGER,
  trainer_id INTEGER,
  rating INTEGER NOT NULL,
  comment TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'pending',
  reviewed_by INTEGER,
  reviewed_at TEXT,
  reject_reason TEXT,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (gym_id) REFERENCES gyms(id) ON DELETE CASCADE,
  FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS favorites (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  target_type TEXT NOT NULL,
  gym_id INTEGER,
  trainer_id INTEGER,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (gym_id) REFERENCES gyms(id) ON DELETE CASCADE,
  FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS password_reset_otps (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  otp_hash TEXT NOT NULL,
  attempts INTEGER NOT NULL DEFAULT 0,
  expires_at TEXT NOT NULL,
  verified_at TEXT,
  consumed_at TEXT,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS remember_tokens (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  selector TEXT NOT NULL UNIQUE,
  token_hash TEXT NOT NULL,
  expires_at TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE UNIQUE INDEX IF NOT EXISTS uq_review_user_gym ON reviews(user_id, gym_id) WHERE gym_id IS NOT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS uq_review_user_trainer ON reviews(user_id, trainer_id) WHERE trainer_id IS NOT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS uq_fav_user_gym ON favorites(user_id, gym_id) WHERE gym_id IS NOT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS uq_fav_user_trainer ON favorites(user_id, trainer_id) WHERE trainer_id IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_pwreset_user ON password_reset_otps(user_id);
CREATE INDEX IF NOT EXISTS idx_pwreset_expires ON password_reset_otps(expires_at);
CREATE INDEX IF NOT EXISTS idx_remember_user ON remember_tokens(user_id);
CREATE INDEX IF NOT EXISTS idx_remember_expires ON remember_tokens(expires_at);
SQL);
    $pdo->exec('PRAGMA foreign_keys = ON');

    $hash = password_hash('password', PASSWORD_BCRYPT);
    $hashAdmin = password_hash('Admin@123456', PASSWORD_BCRYPT);
    $hashUser = password_hash('User@123456', PASSWORD_BCRYPT);

    // Seed account registration dates are staggered so the admin list reflects
    // a plausible account history instead of assigning every demo user install time.
    $userCreatedAt = [
        1 => '2023-10-01 09:00:00', 2 => '2024-01-15 10:20:00', 3 => '2024-02-20 14:10:00',
        4 => '2024-04-05 08:45:00', 5 => '2024-05-18 16:30:00', 6 => '2024-07-02 11:15:00',
        7 => '2024-08-12 13:40:00', 8 => '2024-10-09 09:25:00', 9 => '2024-11-21 17:05:00',
        10 => '2025-01-06 10:00:00', 11 => '2025-02-15 15:20:00', 12 => '2025-03-27 08:35:00',
        13 => '2025-05-10 12:10:00', 14 => '2025-06-18 16:45:00', 15 => '2025-07-30 09:50:00',
        16 => '2025-09-11 14:25:00', 17 => '2025-10-22 11:05:00', 18 => '2025-12-03 17:30:00',
        19 => '2026-01-14 08:20:00', 20 => '2026-02-25 13:15:00', 21 => '2026-03-17 10:40:00',
        22 => '2026-04-29 15:55:00', 23 => '2026-05-30 09:10:00', 24 => '2026-06-19 16:05:00',
        25 => '2026-07-21 11:30:00',
    ];

    // 1. Users (Admin u01, Active Users u02-u24, Locked User u25)
    $pdo->prepare('INSERT INTO users (id, full_name, email, phone, password_hash, role, status, created_at) VALUES (?,?,?,?,?,?,?,?)')->execute(
        [1, 'Admin GymFinder', 'u01@gymfinder.test', '0900000001', $hash, 'admin', 'active', $userCreatedAt[1]]
    );

    $userNames = [
        2 => 'Nguyễn Văn An',
        3 => 'Trần Thị Bình',
        4 => 'Lê Minh Châu',
        5 => 'Phạm Quang Dũng',
        6 => 'Võ Quốc Đạt',
        7 => 'Bùi Phương Giang',
        8 => 'Đặng Hữu Hiếu',
        9 => 'Trịnh Mỹ Linh',
        10 => 'Phan Tuấn Kiệt',
        11 => 'Hoàng Khánh Ly',
        12 => 'Lý Gia Huy',
        13 => 'Đỗ Thanh Thảo',
        14 => 'Ngô Bảo Nam',
        15 => 'Vũ Kim Oanh',
        16 => 'Hồ Đức Phát',
        17 => 'Dương Như Quỳnh',
        18 => 'Lâm Quốc Thịnh',
        19 => 'Mai Thu Uyên',
        20 => 'Trương Việt Vỹ',
        21 => 'Đoàn Phương Vy',
        22 => 'Tạ Minh Khang',
        23 => 'Cao Bích Thủy',
        24 => 'Bạch Gia Phúc',
        25 => 'Võ Thị Em (Khóa)', // locked user
    ];

    $insU = $pdo->prepare('INSERT INTO users (id, full_name, email, phone, password_hash, role, status, created_at) VALUES (?,?,?,?,?,?,?,?)');
    foreach ($userNames as $uId => $fullName) {
        $email = sprintf("u%02d@gymfinder.test", $uId);
        $phone = sprintf("090%07d", $uId);
        $status = ($uId === 25) ? 'locked' : 'active';
        $insU->execute([$uId, $fullName, $email, $phone, $hash, 'user', $status, $userCreatedAt[$uId]]);
    }

    // 2. Districts (22 districts of HCMC)
    $districts = [
        [1, 'Quận 1', 'quan-1'],
        [2, 'Quận 7', 'quan-7'],
        [3, 'TP Thủ Đức', 'tp-thu-duc'],
        [4, 'Quận 3', 'quan-3'],
        [5, 'Quận 4', 'quan-4'],
        [6, 'Quận 5', 'quan-5'],
        [7, 'Quận 6', 'quan-6'],
        [8, 'Quận 8', 'quan-8'],
        [9, 'Quận 10', 'quan-10'],
        [10, 'Quận 11', 'quan-11'],
        [11, 'Quận 12', 'quan-12'],
        [12, 'Bình Tân', 'binh-tan'],
        [13, 'Bình Thạnh', 'binh-thanh'],
        [14, 'Gò Vấp', 'go-vap'],
        [15, 'Phú Nhuận', 'phu-nhuan'],
        [16, 'Tân Bình', 'tan-binh'],
        [17, 'Tân Phú', 'tan-phu'],
        [18, 'Huyện Bình Chánh', 'huyen-binh-chanh'],
        [19, 'Huyện Cần Giờ', 'huyen-can-gio'],
        [20, 'Huyện Củ Chi', 'huyen-cu-chi'],
        [21, 'Huyện Hóc Môn', 'huyen-hoc-mon'],
        [22, 'Huyện Nhà Bè', 'huyen-nha-be'],
        [23, 'Quận 2', 'quan-2'],
    ];
    $insDist = $pdo->prepare('INSERT INTO districts (id, name, slug) VALUES (?,?,?)');
    foreach ($districts as $d) {
        $insDist->execute($d);
    }

    // 3. Categories
    $cats = [
        [1, 'Gym phổ thông', 'fitness', 'Phòng tập thể hình, tạ và cardio', 1],
        [2, 'Gym cao cấp', 'premium', 'Phòng tập cao cấp, đầy đủ tiện ích chuẩn quốc tế', 1],
        [3, 'CrossFit', 'crossfit', 'Tập luyện cường độ cao theo nhóm', 1],
        [4, 'Yoga', 'yoga', 'Yoga, thiền và các bài tập thư giãn cân bằng', 1],
        [5, 'Boxing', 'boxing', 'Boxing, Kickboxing, Muay Thái và võ thuật đối kháng', 1],
        [6, 'Gym cho nữ', 'gym-cho-nu', 'Không gian tập luyện riêng tư, an toàn và thoải mái dành cho phái nữ.', 1],
    ];
    $insC = $pdo->prepare('INSERT INTO categories (id, name, slug, description, is_active) VALUES (?,?,?,?,?)');
    foreach ($cats as $c) {
        $insC->execute($c);
    }

    // 4. Amenities
    $ams = [
        [1, 'Phòng tắm', 'phong-tam', 'fa-shower'],
        [2, 'Bãi giữ xe', 'bai-giu-xe', 'fa-square-parking'],
        [3, 'Wifi', 'wifi', 'fa-wifi'],
        [4, 'Xông hơi', 'xong-hoi', 'fa-hot-tub-person'],
        [5, 'Tủ khóa', 'tu-khoa', 'fa-lock'],
        [6, 'Điều hòa', 'dieu-hoa', 'fa-snowflake'],
        [7, 'Nước uống miễn phí', 'nuoc-uong-mien-phi', 'fa-bottle-water'],
    ];
    $insA = $pdo->prepare('INSERT INTO amenities (id, name, slug, icon) VALUES (?,?,?,?)');
    foreach ($ams as $a) {
        $insA->execute($a);
    }

    // 4b. Features
    $feats = [
        [1, 'Mở cửa 24/7', 'open-247'],
        [2, 'Có huấn luyện viên (PT)', 'has-pt'],
        [3, 'Hỗ trợ giảm cân', 'ho-tro-giam-can'],
    ];
    $insF = $pdo->prepare('INSERT INTO features (id, name, slug) VALUES (?,?,?)');
    foreach ($feats as $f) {
        $insF->execute($f);
    }

    // 5. Specialties
    $specs = [
        [1, 'Sức mạnh & Tăng cơ', 'strength'],
        [2, 'Cardio & Giảm cân', 'cardio-giam-can'],
        [3, 'Yoga & Phục hồi', 'yoga-specialty'],
        [4, 'Boxing & Đối kháng', 'boxing-specialty'],
    ];
    foreach ($specs as $s) {
        $pdo->prepare('INSERT INTO specialties (id, name, slug) VALUES (?,?,?)')->execute($s);
    }

    // 6. Gyms (64 gyms distributed across all 23 available districts)

    $demoGyms = [
        1 => ['The New Gym', 'the-new-gym-nguyen-thi-minh-khai-quan-1-1', 'Số 13 Đường Nguyễn Thị Minh Khai, Quận 1', 1],
        2 => ['California Centuryon', 'california-centuryon-le-thanh-ton-quan-1-2', 'Số 16 Đường Lê Thánh Tôn, Quận 1', 1],
        3 => ['Fit Center', 'fit-center-ham-nghi-quan-1-3', 'Số 19 Đường Hàm Nghi, Quận 1', 1],
        4 => ['MMA Gym Fitness', 'mma-gym-fitness-nguyen-thi-thap-quan-7-4', 'Số 22 Đường Nguyễn Thị Thập, Quận 7', 2],
        5 => ['Advance Fitness & Gym', 'advance-fitness-gym-huynh-tan-phat-quan-7-5', 'Số 25 Đường Huỳnh Tấn Phát, Quận 7', 2],
        6 => ['Curves', 'curves-nguyen-luong-bang-quan-7-6', 'Số 28 Đường Nguyễn Lương Bằng, Quận 7', 2],
        7 => ['S Fitness', 's-fitness-vo-van-ngan-tp-thu-duc-7', 'Số 31 Đường Võ Văn Ngân, TP Thủ Đức', 3],
        8 => ['Diamond Fitness Center', 'diamond-fitness-center-kha-van-can-tp-thu-duc-8', 'Số 34 Đường Kha Vạn Cân, TP Thủ Đức', 3],
        9 => ['CityGym', 'citygym-dang-van-bi-tp-thu-duc-9', 'Số 37 Đường Đặng Văn Bi, TP Thủ Đức', 3],
        10 => ['Ares Gym & Fitness', 'ares-gym-fitness-cao-thang-quan-3-10', 'Số 40 Đường Cao Thắng, Quận 3', 4],
        11 => ['Power Gym', 'power-gym-vo-van-tan-quan-3-11', 'Số 43 Đường Võ Văn Tần, Quận 3', 4],
        12 => ['Boutique Fitness', 'boutique-fitness-hoang-dieu-quan-4-12', 'Số 46 Đường Hoàng Diệu, Quận 4', 5],
        13 => ['Victory Gym & Yoga', 'victory-gym-yoga-khanh-hoi-quan-4-13', 'Số 49 Đường Khánh Hội, Quận 4', 5],
        14 => ['ProGym', 'progym-ben-van-don-quan-4-14', 'Số 52 Đường Bến Vân Đồn, Quận 4', 5],
        15 => ['Gym Tài Nguyên', 'gym-tai-nguyen-tran-hung-dao-quan-5-15', 'Số 55 Đường Trần Hưng Đạo, Quận 5', 6],
        16 => ['V-Fitness', 'v-fitness-an-duong-vuong-quan-5-16', 'Số 58 Đường An Dương Vương, Quận 5', 6],
        17 => ['CLB Thể Hình', 'clb-the-hinh-hong-bang-quan-5-17', 'Số 61 Đường Hồng Bàng, Quận 5', 6],
        18 => ['Olympia Gym', 'olympia-gym-hau-giang-quan-6-18', 'Số 64 Đường Hậu Giang, Quận 6', 7],
        19 => ['KingSport Fitness', 'kingsport-fitness-binh-phu-quan-6-19', 'Số 67 Đường Bình Phú, Quận 6', 7],
        20 => ['Fit24 Fitness & Yoga', 'fit24-fitness-yoga-pham-van-chi-quan-6-20', 'Số 70 Đường Phạm Văn Chí, Quận 6', 7],
        21 => ['UFC Gym', 'ufc-gym-pham-hung-quan-8-21', 'Số 73 Đường Phạm Hùng, Quận 8', 8],
        22 => ['Bodyfit Fitness & Yoga', 'bodyfit-fitness-yoga-ta-quang-buu-quan-8-22', 'Số 76 Đường Tạ Quang Bửu, Quận 8', 8],
        23 => ['B-Fit Center', 'b-fit-center-3-thang-2-quan-10-23', 'Số 79 Đường 3 Tháng 2, Quận 10', 9],
        24 => ['Elite Fitness', 'elite-fitness-su-van-hanh-quan-10-24', 'Số 82 Đường Sư Vạn Hạnh, Quận 10', 9],
        25 => ['Lion Gym', 'lion-gym-to-hien-thanh-quan-10-25', 'Số 85 Đường Tô Hiến Thành, Quận 10', 9],
        26 => ['Star Fitness', 'star-fitness-lac-long-quan-quan-11-26', 'Số 88 Đường Lạc Long Quân, Quận 11', 10],
        27 => ['Zen Yoga & Fitness', 'zen-yoga-fitness-le-dai-hanh-quan-11-27', 'Số 91 Đường Lê Đại Hành, Quận 11', 10],
        28 => ['California Fitness', 'california-fitness-hoa-binh-quan-11-28', 'Số 94 Đường Hòa Bình, Quận 11', 10],
        29 => ['K-Gym', 'k-gym-le-van-khuong-quan-12-29', 'Số 97 Đường Lê Văn Khương, Quận 12', 11],
        30 => ['Fit Center', 'fit-center-to-ky-quan-12-30', 'Số 100 Đường Tô Ký, Quận 12', 11],
        31 => ['CLB Thể Dục', 'clb-the-duc-ten-lua-binh-tan-31', 'Số 103 Đường Tên Lửa, Bình Tân', 12],
        32 => ['MegaFit', 'megafit-kinh-duong-vuong-binh-tan-32', 'Số 106 Đường Kinh Dương Vương, Bình Tân', 12],
        33 => ['Gold Gym', 'gold-gym-ma-lo-binh-tan-33', 'Số 109 Đường Mã Lò, Bình Tân', 12],
        34 => ['Titan Gym', 'titan-gym-dien-bien-phu-binh-thanh-34', 'Số 112 Đường Điện Biên Phủ, Bình Thạnh', 13],
        35 => ['The New Gym', 'the-new-gym-bach-dang-binh-thanh-35', 'Số 115 Đường Bạch Đằng, Bình Thạnh', 13],
        36 => ['Level Fitness & Yoga', 'level-fitness-yoga-phan-van-tri-binh-thanh-36', 'Số 118 Đường Phan Văn Trị, Bình Thạnh', 13],
        37 => ['Vinhomes Fitness Club', 'vinhomes-fitness-club-quang-trung-go-vap-37', 'Số 121 Đường Quang Trung, Gò Vấp', 14],
        38 => ['Stronger Gym', 'stronger-gym-phan-van-tri-go-vap-38', 'Số 124 Đường Phan Văn Trị, Gò Vấp', 14],
        39 => ['CLB Thể Hình', 'clb-the-hinh-le-duc-tho-go-vap-39', 'Số 127 Đường Lê Đức Thọ, Gò Vấp', 14],
        40 => ['Titan Fitness', 'titan-fitness-phan-xich-long-phu-nhuan-40', 'Số 130 Đường Phan Xích Long, Phú Nhuận', 15],
        41 => ['Aura Gym', 'aura-gym-nguyen-van-troi-phu-nhuan-41', 'Số 133 Đường Nguyễn Văn Trỗi, Phú Nhuận', 15],
        42 => ['California Fitness', 'california-fitness-cong-hoa-tan-binh-42', 'Số 136 Đường Cộng Hòa, Tân Bình', 16],
        43 => ['The New Gym', 'the-new-gym-truong-chinh-tan-binh-43', 'Số 139 Đường Trường Chinh, Tân Bình', 16],
        44 => ['Fit Center', 'fit-center-hoang-hoa-tham-tan-binh-44', 'Số 142 Đường Hoàng Hoa Thám, Tân Bình', 16],
        45 => ['Hoàng Long Fitness', 'hoang-long-fitness-luy-ban-bich-tan-phu-45', 'Số 145 Đường Lũy Bán Bích, Tân Phú', 17],
        46 => ['CLB Gym Thống Nhất', 'clb-gym-thong-nhat-thoai-ngoc-hau-tan-phu-46', 'Số 148 Đường Thoại Ngọc Hầu, Tân Phú', 17],
        47 => ['Gym New Life', 'gym-new-life-tan-son-nhi-tan-phu-47', 'Số 151 Đường Tân Sơn Nhì, Tân Phú', 17],
        48 => ['Phòng Gym Hoàng Gia', 'phong-gym-hoang-gia-quoc-lo-50-huyen-binh-chanh-48', 'Số 154 Đường Quốc Lộ 50, Huyện Bình Chánh', 18],
        49 => ['Fit Center', 'fit-center-nguyen-huu-tri-huyen-binh-chanh-49', 'Số 157 Đường Nguyễn Hữu Trí, Huyện Bình Chánh', 18],
        50 => ['The New Gym', 'the-new-gym-dinh-duc-thien-huyen-binh-chanh-50', 'Số 160 Đường Đinh Đức Thiện, Huyện Bình Chánh', 18],
        51 => ['Body Shape Fitness', 'body-shape-fitness-duyen-hai-huyen-can-gio-51', 'Số 163 Đường Duyên Hải, Huyện Cần Giờ', 19],
        52 => ['Khang Gym', 'khang-gym-rung-sac-huyen-can-gio-52', 'Số 166 Đường Rừng Sác, Huyện Cần Giờ', 19],
        53 => ['Thủ Đức Fitness Center', 'thu-duc-fitness-center-tinh-lo-8-huyen-cu-chi-53', 'Số 169 Đường Tỉnh Lộ 8, Huyện Củ Chi', 20],
        54 => ['Rạng Đông Gym', 'rang-dong-gym-lieu-binh-huong-huyen-cu-chi-54', 'Số 172 Đường Liêu Bình Hương, Huyện Củ Chi', 20],
        55 => ['The Gym House', 'the-gym-house-nguyen-thi-ranh-huyen-cu-chi-55', 'Số 175 Đường Nguyễn Thị Rành, Huyện Củ Chi', 20],
        56 => ['Sparta Gym & Boxing', 'sparta-gym-boxing-quang-trung-huyen-hoc-mon-56', 'Số 178 Đường Quang Trung, Huyện Hóc Môn', 21],
        57 => ['S-Fitness', 's-fitness-le-loi-huyen-hoc-mon-57', 'Số 181 Đường Lê Lợi, Huyện Hóc Môn', 21],
        58 => ['King Gym', 'king-gym-dang-thuc-vinh-huyen-hoc-mon-58', 'Số 184 Đường Đặng Thúc Vịnh, Huyện Hóc Môn', 21],
        59 => ['Aeon Mall Fitness Club', 'aeon-mall-fitness-club-nguyen-huu-tho-huyen-nha-be-59', 'Số 187 Đường Nguyễn Hữu Thọ, Huyện Nhà Bè', 22],
        60 => ['Strong Gym', 'strong-gym-huynh-tan-phat-huyen-nha-be-60', 'Số 190 Đường Huỳnh Tấn Phát, Huyện Nhà Bè', 22],
        61 => ['The New Gym', 'the-new-gym-xuan-thuy-quan-2-61', 'Số 193 Đường Xuân Thủy, Quận 2', 23],
        62 => ['CityGym', 'citygym-song-hanh-quan-2-62', 'Số 196 Đường Song Hành, Quận 2', 23],
        63 => ['Fit24', 'fit24-mai-chi-tho-quan-2-63', 'Số 199 Đường Mai Chí Thọ, Quận 2', 23],
        64 => ['Elite Fitness', 'elite-fitness-nguyen-co-thach-quan-2-64', 'Số 202 Đường Nguyễn Cơ Thạch, Quận 2', 23],
    ];
    $districtNamesById = array_column($districts, 1, 0);

    $gymCoverImages = gf_demo_gym_cover_photos();
    $gymGalleryImages = gf_demo_gym_photos();

    $priceRanges = [
        ['min' => 200000, 'max' => 450000],
        ['min' => 250000, 'max' => 500000],
        ['min' => 300000, 'max' => 600000],
        ['min' => 500000, 'max' => 1000000],
        ['min' => 600000, 'max' => 1200000],
        ['min' => 700000, 'max' => 1500000],
        ['min' => 1200000, 'max' => 2500000],
        ['min' => 1500000, 'max' => 3500000],
    ];

    // Hide one extra gym only in six districts with three entries. Keep at
    // least two active gyms per district so every price filter has a match.
    $hiddenGymIds = [3, 6, 9, 14, 47, 55];
    $featuredGymIds = [1, 2, 9, 15, 20, 24, 28, 36, 42, 49, 54, 57]; // 12 featured gyms

    $insGym = $pdo->prepare('INSERT INTO gyms (id, name, slug, description, address, district_id, latitude, longitude, phone, email, website, price_min, price_max, opening_hours, cover_image_url, is_featured, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $insGC = $pdo->prepare('INSERT INTO gym_categories (gym_id, category_id) VALUES (?,?)');
    $insGA = $pdo->prepare('INSERT INTO gym_amenities (gym_id, amenity_id) VALUES (?,?)');
    $insGI = $pdo->prepare('INSERT INTO gym_images (gym_id, image_url, caption, sort_order) VALUES (?,?,?,?)');

    $gymsMeta = [];

    $districtPriceRank = [];
    foreach ($demoGyms as $gymId => [$name, $slug, $address, $distId]) {
            $info = ['name' => $districtNamesById[$distId]];
            $q2PriceRanges = [
                61 => ['min' => 200000, 'max' => 450000],
                62 => ['min' => 350000, 'max' => 700000],
                63 => ['min' => 700000, 'max' => 1200000],
                64 => ['min' => 1500000, 'max' => 3500000],
            ];
            $isFeatured = in_array($gymId, $featuredGymIds, true) ? 1 : 0;
            $status = in_array($gymId, $hiddenGymIds, true) ? 'hidden' : 'active';
            if (isset($q2PriceRanges[$gymId])) {
                $pRange = $q2PriceRanges[$gymId];
            } elseif ($status === 'active') {
                $districtPriceRank[$distId] = ($districtPriceRank[$distId] ?? 0) + 1;
                $pRange = match ($districtPriceRank[$distId]) {
                    1 => ['min' => 200000, 'max' => 450000],
                    2 => ['min' => 700000, 'max' => 1500000],
                    default => $priceRanges[$gymId % count($priceRanges)],
                };
            } else {
                $pRange = $priceRanges[$gymId % count($priceRanges)];
            }
            $cover = str_replace('w=800', 'w=1200', $gymCoverImages[($gymId - 1) % count($gymCoverImages)]);

            $phone = sprintf("+8428%07d", 1000000 + $gymId);
            $email = "contact.gym{$gymId}@gymfinder.test";
            $website = "https://gym{$gymId}.gymfinder.test";
            $hours = ($gymId % 6 === 0) ? '24/7' : (($gymId % 3 === 0) ? "05:00 - 23:00" : (($gymId % 2 === 0) ? "06:00 - 22:00" : "05:30 - 21:30"));
            $desc = "Phòng tập {$name} tại {$info['name']} sở hữu không gian tập luyện chuyên nghiệp, trang bị máy tập cao cấp, không gian rộng rãi thoáng mát cùng nhiều tiện ích đẳng cấp.";

            $insGym->execute([
                $gymId, $name, $slug, $desc, $address, $distId,
                null, null, $phone, $email, $website,
                $pRange['min'], $pRange['max'], $hours, $cover,
                $isFeatured, $status
            ]);

            $gymsMeta[$gymId] = ['district_id' => $distId, 'name' => $name];

            // Categories
            $catCount = 1 + ($gymId % 3);
            for ($c = 1; $c <= $catCount; $c++) {
                $catId = (($gymId + $c) % 6) + 1;
                $insGC->execute([$gymId, $catId]);
            }

            // Amenities
            $amCount = 3 + ($gymId % 4);
            for ($a = 1; $a <= $amCount; $a++) {
                $amId = (($gymId + $a) % 7) + 1;
                $insGA->execute([$gymId, $amId]);
            }

            // Seed a complete gallery with distinct images, separate from the cover.
            // Retain the gym's initial 'Khu tập luyện đa năng' photo as slot 1.
            $galleryTarget = $isFeatured ? 6 : 5;
            $galleryUrls = [];
            $initialPhoto = gf_demo_gym_initial_gallery_photo($gymId);
            $initialPhotoId = preg_match('#/photo-([^?]+)#', $initialPhoto, $ipm) ? $ipm[1] : '';
            $coverPhotoId = preg_match('#/photo-([^?]+)#', $cover, $cpm) ? $cpm[1] : '';
            if ($initialPhotoId !== $coverPhotoId) {
                $galleryUrls[] = ['url' => $initialPhoto, 'caption' => 'Khu tập luyện đa năng'];
            }

            $galleryPoolCount = count($gymGalleryImages);
            $galleryStart = (($gymId - 1) * 13) % $galleryPoolCount;
            for ($step = 0; $step < $galleryPoolCount && count($galleryUrls) < $galleryTarget; $step++) {
                $galleryImage = $gymGalleryImages[($galleryStart + $step * 7) % $galleryPoolCount];
                $gPhotoId = preg_match('#/photo-([^?]+)#', $galleryImage, $gpm) ? $gpm[1] : '';
                if ($gPhotoId === $coverPhotoId) {
                    continue;
                }
                $existingUrls = array_column($galleryUrls, 'url');
                if (in_array($galleryImage, $existingUrls, true)) {
                    continue;
                }
                $galleryUrls[] = ['url' => $galleryImage, 'caption' => 'Không gian phòng tập'];
            }
            foreach ($galleryUrls as $galleryIndex => $item) {
                $insGI->execute([$gymId, $item['url'], $item['caption'], $galleryIndex + 1]);
            }

    }

    // 7. Trainers (30 trainers: 18 with 2 specialties, 12 with 3 specialties)
    // Assigned to 22 gyms (leaving 38 gyms with no PT)
    // 1 PT can work at multiple gyms, 1 gym can have multiple PTs (N-N)
    $activeGymsWithPT = [1, 2, 3, 4, 5, 9, 10, 11, 15, 16, 20, 21, 24, 25, 29, 30, 36, 42, 45, 50, 54, 57]; // 22 gyms

    $trainerNames = [
        1 => 'Nguyễn Anh Tuấn', 2 => 'Trần Thị Kim Lan', 3 => 'Lê Văn Bình', 4 => 'Phạm Thu Hà',
        5 => 'Võ Thị Mai', 6 => 'Hoàng Quốc Cường', 7 => 'Ngô Thanh Hà', 8 => 'Đinh Văn Khoa',
        9 => 'Bùi Thị Nga', 10 => 'Chu Minh Phúc', 11 => 'Trương Gia Bảo', 12 => 'Đặng Thảo Vy',
        13 => 'Phan Thành Long', 14 => 'Lý Quốc Hưng', 15 => 'Dương Thúy Kiều', 16 => 'Vũ Đình Trọng',
        17 => 'Đỗ Hoài Nam', 18 => 'Hồ Ngọc Ánh', 19 => 'Lâm Khắc Tiệp', 20 => 'Mai Hải Đăng',
        21 => 'Nguyễn Bích Ngọc', 22 => 'Trần Quang Khải', 23 => 'Lê Hoàng Yến', 24 => 'Phạm Minh Đức',
        25 => 'Võ Hữu Tín', 26 => 'Hoàng Thùy Linh', 27 => 'Đoàn Nhật Minh', 28 => 'Tạ Anh Khoa',
        29 => 'Cao Mỹ Duyên', 30 => 'Bạch Trọng Hiếu'
    ];

    $femaleTrainerIds = [2, 4, 5, 7, 9, 12, 15, 18, 21, 23, 26, 29];
    $trainerPortraitOverrides = gf_demo_trainer_avatars();

    $insTr = $pdo->prepare('INSERT INTO trainers (id, full_name, slug, avatar_url, specialty_id, years_experience, bio, phone, email, gym_id, district_id, is_featured, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $insTS = $pdo->prepare('INSERT INTO trainer_specialties (trainer_id, specialty_id) VALUES (?,?)');
    $insGT = $pdo->prepare('INSERT INTO gym_trainers (gym_id, trainer_id) VALUES (?,?)');

    for ($tId = 1; $tId <= 30; $tId++) {
        $name = $trainerNames[$tId];
        $slug = gf_slugify($name) . '-' . $tId;
        $exp = ($tId % 15) + 1;
        $bio = "Huấn luyện viên {$name} có {$exp} năm kinh nghiệm thực chiến trong lĩnh vực thể hình và fitness, chuyên sâu xây dựng giáo án cá nhân hóa và hướng dẫn kỹ thuật chuẩn xác.";
        $phone = sprintf("+84901%06d", $tId);
        $email = "trainer{$tId}@gymfinder.test";
        $avatarGender = in_array($tId, $femaleTrainerIds, true) ? 'women' : 'men';
        $avatar = $trainerPortraitOverrides[$tId] ?? "https://randomuser.me/api/portraits/{$avatarGender}/{$tId}.jpg";
        $isFeatured = ($tId % 5 === 0) ? 1 : 0;
        $status = ($tId === 28 || $tId === 29) ? 'hidden' : 'active';

        // Every trainer has multiple specialties: 18 have 2, 12 have 3.
        $sCount = ($tId <= 18) ? 2 : 3;
        $assignedSpecs = [];
        for ($sc = 0; $sc < $sCount; $sc++) {
            $specId = (($tId + $sc) % 4) + 1;
            if (!in_array($specId, $assignedSpecs, true)) {
                $assignedSpecs[] = $specId;
            }
        }
        $primSpecId = $assignedSpecs[0];

        // Gyms: each trainer works at 1 to 3 gyms
        $gCount = ($tId <= 15) ? 1 : (($tId <= 25) ? 2 : 3);
        $assignedGyms = [];
        $primGymId = $activeGymsWithPT[($tId - 1) % count($activeGymsWithPT)];
        $assignedGyms[] = $primGymId;

        if ($gCount >= 2) {
            $secondGymId = $activeGymsWithPT[($tId + 5) % count($activeGymsWithPT)];
            if (!in_array($secondGymId, $assignedGyms, true)) {
                $assignedGyms[] = $secondGymId;
            }
        }
        if ($gCount >= 3) {
            $thirdGymId = $activeGymsWithPT[($tId + 11) % count($activeGymsWithPT)];
            if (!in_array($thirdGymId, $assignedGyms, true)) {
                $assignedGyms[] = $thirdGymId;
            }
        }

        $districtId = $gymsMeta[$primGymId]['district_id'];

        // Insert trainer first
        $insTr->execute([
            $tId, $name, $slug, $avatar, $primSpecId, $exp, $bio,
            $phone, $email, $primGymId, $districtId, $isFeatured, $status
        ]);

        // Insert trainer_specialties
        foreach ($assignedSpecs as $specId) {
            $insTS->execute([$tId, $specId]);
        }

        // Insert gym_trainers
        foreach ($assignedGyms as $gId) {
            $insGT->execute([$gId, $tId]);
        }
    }

    // Keep an active trainer available for every seeded gym.
    $activeTrainerIds = $pdo->query("SELECT t.id FROM trainers t WHERE t.status='active' AND EXISTS (SELECT 1 FROM trainer_specialties ts WHERE ts.trainer_id=t.id) ORDER BY t.id")->fetchAll(PDO::FETCH_COLUMN);
    $hasActiveTrainer = $pdo->prepare("SELECT 1 FROM trainers t JOIN gym_trainers gt ON gt.trainer_id=t.id WHERE gt.gym_id=? AND t.status='active' AND EXISTS (SELECT 1 FROM trainer_specialties ts WHERE ts.trainer_id=t.id) LIMIT 1");
    foreach (range(1, count($allGymNames)) as $gymId) {
        $hasActiveTrainer->execute([$gymId]);
        if (!$hasActiveTrainer->fetchColumn() && $activeTrainerIds) {
            $trainerId = (int) $activeTrainerIds[($gymId - 1) % count($activeTrainerIds)];
            $insGT->execute([$gymId, $trainerId]);
        }
    }

    // 8. Reviews (210 reviews: 135 gyms, 75 trainers)
    // 75% approved (158), 15% pending (31), 10% rejected (21)
    $commentsGoodGym = [
        'Không gian tập cực kỳ sạch sẽ, máy móc hiện đại và mới toanh!',
        'Rất hài lòng với dịch vụ tại đây, nhân viên và HLV luôn niềm nở, hỗ trợ nhiệt tình.',
        'Trải nghiệm tuyệt vời, giáo án bài bản và theo sát từng buổi tập.',
        'Phòng tập thoáng mát, không bị quá tải vào giờ cao điểm, rất đáng tiền!',
        'Thiết bị tạ và cardio rất đa dạng, cơ sở vật chất chuẩn 5 sao.',
        'Đã tập ở đây được 6 tháng và thấy sức khỏe cải thiện rõ rệt, 10/10.',
        'Môi trường năng động, âm nhạc truyền cảm hứng, phòng tắm và xông hơi rất sạch.'
    ];
    $commentsMediumGym = [
        'Phòng tập nhìn chung ổn trong tầm giá, giờ cao điểm hơi đông một chút.',
        'Cơ sở vật chất tốt, tuy nhiên bãi giữ xe hơi hẹp vào khung giờ tan tầm.',
        'Máy móc phong phú nhưng một số máy cardio cần được bảo dưỡng thường xuyên hơn.',
        'HLV hướng dẫn nhiệt tình, giá vé tháng hơi cao nhưng chấp nhận được.'
    ];
    $commentsPendingGym = [
        'Vừa đăng ký gói tập 3 tháng, ấn tượng ban đầu khá tốt, đang chờ theo dõi thêm.',
        'Mới trải nghiệm buổi đầu tiên, phòng tập sạch sẽ và HLV khá nhiệt tình.',
        'Cần cải thiện thêm hệ thống điều hòa ở khu vực tạ nặng vào buổi trưa.'
    ];
    $commentsRejected = [
        'Quảng cáo bán thực phẩm chức năng giá rẻ, liên hệ Zalo 0988xxxxxx!',
        'Dịch vụ tệ hại lừa đảo đừng ai vào đây nhé!',
        'Vào trang web xyz.com để nhận voucher giảm 90% ngay hôm nay!'
    ];
    $reasons = [
        'Nội dung chứa liên kết hoặc thông tin quảng cáo spam.',
        'Đánh giá chứa ngôn từ thiếu chuẩn mực và xúc phạm.',
        'Nội dung không liên quan đến trải nghiệm tập luyện thực tế.'
    ];

    $insRev = $pdo->prepare('INSERT INTO reviews (id, user_id, target_type, gym_id, trainer_id, rating, comment, status, reviewed_by, reviewed_at, reject_reason, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');

    $revId = 1;

    // Gym reviews: 135 (101 approved, 20 pending, 14 rejected)
    $gymStatusPool = array_merge(
        array_fill(0, 101, 'approved'),
        array_fill(0, 20, 'pending'),
        array_fill(0, 14, 'rejected')
    );
    shuffle($gymStatusPool);

    $userGymPairs = [];
    for ($i = 0; $i < 135; $i++) {
        $st = $gymStatusPool[$i];
        if ($i < 65) {
            $targetGym = ($i % 18) + 1; // 1..18 (frequent)
        } elseif ($i < 125) {
            $targetGym = 19 + (($i - 65) % 30); // 19..48 (medium)
        } else {
            $targetGym = 49 + (($i - 125) % 12); // 49..60 (few)
        }

        $assignedUser = 2;
        for ($u = 2; $u <= 24; $u++) {
            $pairKey = "{$u}-{$targetGym}";
            if (!isset($userGymPairs[$pairKey])) {
                $assignedUser = $u;
                $userGymPairs[$pairKey] = true;
                break;
            }
        }

        $rating = ($st === 'rejected') ? 1 : (($i % 4 === 0) ? 4 : 5);
        $dateStr = sprintf("2026-%02d-%02d 10:00:00", 8 + ($i % 2), 1 + ($i % 28));

        if ($st === 'rejected') {
            $comment = $commentsRejected[$i % count($commentsRejected)];
            $reason = $reasons[$i % count($reasons)];
            $insRev->execute([$revId, $assignedUser, 'gym', $targetGym, null, $rating, $comment, 'rejected', 1, $dateStr, $reason, $dateStr]);
        } elseif ($st === 'pending') {
            $comment = $commentsPendingGym[$i % count($commentsPendingGym)];
            $insRev->execute([$revId, $assignedUser, 'gym', $targetGym, null, $rating, $comment, 'pending', null, null, null, $dateStr]);
        } else {
            $comment = ($rating === 5) ? $commentsGoodGym[$i % count($commentsGoodGym)] : $commentsMediumGym[$i % count($commentsMediumGym)];
            $insRev->execute([$revId, $assignedUser, 'gym', $targetGym, null, $rating, $comment, 'approved', 1, $dateStr, null, $dateStr]);
        }
        $revId++;
    }

    // Ensure every seeded gym has an approved review for ratings and suggestions.
    $approvedReviewCheck = $pdo->prepare("SELECT 1 FROM reviews WHERE gym_id=? AND status='approved' LIMIT 1");
    $userReviewCheck = $pdo->prepare('SELECT 1 FROM reviews WHERE user_id=? AND gym_id=? LIMIT 1');
    for ($gymId = 1; $gymId <= count($allGymNames); $gymId++) {
        $approvedReviewCheck->execute([$gymId]);
        if ($approvedReviewCheck->fetchColumn()) {
            continue;
        }
        for ($userId = 2; $userId <= 24; $userId++) {
            $userReviewCheck->execute([$userId, $gymId]);
            if (!$userReviewCheck->fetchColumn()) {
                $dateStr = sprintf('2026-09-%02d 10:00:00', (($gymId - 1) % 27) + 1);
                $rating = ($gymId % 2) ? 4 : 5;
                $insRev->execute([
                    $revId, $userId, 'gym', $gymId, null, $rating,
                    'Phòng tập sạch sẽ, tiện ích tốt và có HLV hỗ trợ nhiệt tình.',
                    'approved', 1, $dateStr, null, $dateStr,
                ]);
                $revId++;
                break;
            }
        }
    }

    // Trainer reviews: 75 (57 approved, 11 pending, 7 rejected)
    $trStatusPool = array_merge(
        array_fill(0, 57, 'approved'),
        array_fill(0, 11, 'pending'),
        array_fill(0, 7, 'rejected')
    );
    shuffle($trStatusPool);

    $commentsGoodTr = [
        'PT hướng dẫn rất tận tình, chỉnh từng động tác form chuẩn, tạo động lực rất lớn.',
        'Giáo án giảm cân hiệu quả cực kỳ, sau 2 tháng mình đã giảm được 4kg mỡ thừa.',
        'Thầy dạy rất chuyên nghiệp, luôn đúng giờ và giải đáp mọi thắc mắc dinh dưỡng.',
        'Phong cách huấn luyện kiên nhẫn, luôn động viên học viên vượt qua giới hạn bản thân.'
    ];

    $userTrPairs = [];
    for ($j = 0; $j < 75; $j++) {
        $st = $trStatusPool[$j];
        if ($j < 45) {
            $targetTr = ($j % 10) + 1; // 1..10 (frequent)
        } elseif ($j < 65) {
            $targetTr = 11 + (($j - 45) % 12); // 11..22 (medium)
        } else {
            $targetTr = 23 + (($j - 65) % 8); // 23..30 (few)
        }

        $assignedUser = 2;
        for ($u = 2; $u <= 24; $u++) {
            $pairKey = "{$u}-{$targetTr}";
            if (!isset($userTrPairs[$pairKey])) {
                $assignedUser = $u;
                $userTrPairs[$pairKey] = true;
                break;
            }
        }

        $rating = ($st === 'rejected') ? 1 : (($j % 3 === 0) ? 4 : 5);
        $dateStr = sprintf("2026-%02d-%02d 14:00:00", 8 + ($j % 2), 1 + ($j % 28));

        if ($st === 'rejected') {
            $comment = $commentsRejected[$j % count($commentsRejected)];
            $reason = $reasons[$j % count($reasons)];
            $insRev->execute([$revId, $assignedUser, 'trainer', null, $targetTr, $rating, $comment, 'rejected', 1, $dateStr, $reason, $dateStr]);
        } elseif ($st === 'pending') {
            $comment = 'Mới đăng ký học kèm thử, HLV khá nhiệt tình, cần thời gian đánh giá tiếp.';
            $insRev->execute([$revId, $assignedUser, 'trainer', null, $targetTr, $rating, $comment, 'pending', null, null, null, $dateStr]);
        } else {
            $comment = $commentsGoodTr[$j % count($commentsGoodTr)];
            $insRev->execute([$revId, $assignedUser, 'trainer', null, $targetTr, $rating, $comment, 'approved', 1, $dateStr, null, $dateStr]);
        }
        $revId++;
    }

    // 9. Recalculate avg_rating and review_count (ONLY approved reviews count!)
    $pdo->exec("UPDATE gyms SET avg_rating = COALESCE((SELECT ROUND(AVG(rating),1) FROM reviews WHERE gym_id = gyms.id AND status='approved'), 0), review_count = (SELECT COUNT(*) FROM reviews WHERE gym_id = gyms.id AND status='approved')");
    $pdo->exec("UPDATE trainers SET avg_rating = COALESCE((SELECT ROUND(AVG(rating),1) FROM reviews WHERE trainer_id = trainers.id AND status='approved'), 0), review_count = (SELECT COUNT(*) FROM reviews WHERE trainer_id = trainers.id AND status='approved')");

    // 10. Sample Favorites
    $favs = [
        [1, 2, 'gym', 1, null],
        [2, 2, 'gym', 3, null],
        [3, 2, 'trainer', null, 1],
        [4, 3, 'gym', 1, null],
        [5, 3, 'trainer', null, 4],
        [6, 4, 'gym', 9, null],
        [7, 4, 'trainer', null, 8],
    ];
    $insF = $pdo->prepare('INSERT INTO favorites (id, user_id, target_type, gym_id, trainer_id) VALUES (?,?,?,?,?)');
    foreach ($favs as $f) {
        $insF->execute($f);
    }
}
