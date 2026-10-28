<?php
declare(strict_types=1);

function gf_sqlite_install(PDO $pdo): void
{
    $pdo->exec('PRAGMA foreign_keys = OFF');
    $pdo->exec(<<<'SQL'
DROP TABLE IF EXISTS favorites;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS gym_amenities;
DROP TABLE IF EXISTS gym_categories;
DROP TABLE IF EXISTS gym_images;
DROP TABLE IF EXISTS gym_trainers;
DROP TABLE IF EXISTS trainer_specialties;
DROP TABLE IF EXISTS trainers;
DROP TABLE IF EXISTS gyms;
DROP TABLE IF EXISTS specialties;
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
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS gym_amenities (
  gym_id INTEGER NOT NULL,
  amenity_id INTEGER NOT NULL,
  PRIMARY KEY (gym_id, amenity_id),
  FOREIGN KEY (gym_id) REFERENCES gyms(id) ON DELETE CASCADE,
  FOREIGN KEY (amenity_id) REFERENCES amenities(id) ON DELETE CASCADE
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
CREATE UNIQUE INDEX IF NOT EXISTS uq_review_user_gym ON reviews(user_id, gym_id) WHERE gym_id IS NOT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS uq_review_user_trainer ON reviews(user_id, trainer_id) WHERE trainer_id IS NOT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS uq_fav_user_gym ON favorites(user_id, gym_id) WHERE gym_id IS NOT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS uq_fav_user_trainer ON favorites(user_id, trainer_id) WHERE trainer_id IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_pwreset_user ON password_reset_otps(user_id);
CREATE INDEX IF NOT EXISTS idx_pwreset_expires ON password_reset_otps(expires_at);
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

    $gymNamesByDist = [
        1 => ['The New Gym Nguyễn Thị Minh Khai', 'California Centuryon Saigon Centre', 'Fit Center Bến Thành', 'MMA Gym Fitness Cống Quỳnh', 'Advance Fitness & Gym Hàm Nghi', 'Curves Lê Thánh Tôn', 'S Fitness Pasteur', 'Diamond Fitness Center Đồng Khởi'],
        4 => ['CityGym Cao Thắng', 'Ares Gym & Fitness Lê Văn Sỹ', 'Power Gym Nam Kỳ Khởi Nghĩa', 'Boutique Fitness Võ Văn Tần', 'Victory Gym & Yoga Điện Biên Phủ', 'ProGym CMT8'],
        6 => ['Gym Tài Nguyên Trần Hưng Đạo', 'V-Fitness Nguyễn Trãi', 'CLB Thể Hình Hùng Vương', 'Olympia Gym An Dương Vương', 'KingSport Fitness Hồng Bàng'],
        2 => ['Fit24 Fitness & Yoga Phú Mỹ Hưng', 'UFC Gym Nguyễn Thị Thập', 'Bodyfit Fitness & Yoga Him Lam', 'B-Fit Center Huỳnh Tấn Phát', 'Elite Fitness Crescent Mall', 'Lion Gym Tân Phú Q7', 'Star Fitness Sky Garden', 'Zen Yoga & Fitness Riverside'],
        9 => ['California Fitness Vạn Hạnh Mall', 'K-Gym Tô Hiến Thành', 'Fit Center 3 Tháng 2', 'CLB Thể Dục Kỳ Hòa', 'MegaFit Sư Vạn Hạnh', 'Gold Gym Lý Thường Kiệt', 'Titan Gym Thành Thái'],
        13 => ['The New Gym Điện Biên Phủ', 'Level Fitness & Yoga Pearl Plaza', 'Vinhomes Central Park Fitness Club', 'Stronger Gym Bạch Đằng', 'CLB Thể Hình Phan Văn Trị', 'Titan Fitness D2 Bình Thạnh', 'Aura Gym Nơ Trang Long'],
        14 => ['California Fitness Quang Trung', 'The New Gym Quang Trung', 'Fit Center Cityland Phan Văn Trị', 'Hoàng Long Fitness Lê Đức Thọ', 'CLB Gym Thống Nhất Gò Vấp', 'Gym New Life Nguyễn Văn Lượng'],
        16 => ['Phòng Gym Hoàng Gia Cộng Hòa', 'Fit Center Trường Chinh', 'The New Gym Hoàng Hoa Thám', 'Body Shape Fitness Bạch Đằng', 'Khang Gym Phổ Quang'],
        3 => ['Thủ Đức Fitness Center Võ Văn Ngân', 'Rạng Đông Gym Kha Vạn Cân', 'The Gym House Đặng Văn Bi', 'Sparta Gym & Boxing Hiệp Bình Phước', 'S-Fitness Song Hành Thảo Điền', 'King Gym Linh Trung'],
        12 => ['Aeon Mall Fitness Club Bình Tân', 'Bình Tân Strong Gym Tên Lửa'],
        23 => ['The New Gym Thảo Điền', 'CityGym An Phú', 'Fit24 Sala', 'Elite Fitness Thủ Thiêm']
    ];
    $allGymNames = array_merge(...array_values($gymNamesByDist));
    $districtNamesById = array_column($districts, 1, 0);

    $gymImages = [
        'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1579758629938-03607ccdbaba?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1583454110551-21f2fa2afe61?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1599058917212-d750089bc07e?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1599058945522-28d584b6f0ff?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1558611848-73f7eb4001a1?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1590487988256-9ed24133863e?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1577221084712-45b0449d2d3f?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1571902943202-507ec2618e8f?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1534258936925-c58bed479fcb?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1540497077202-7c8a3999166f?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1574680096145-d05b474e2155?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1593079831268-3381b0db4a77?auto=format&fit=crop&w=800&q=80'
    ];
    $gymCoverImages = array_merge($gymImages, [
        'https://images.unsplash.com/photo-1728486145245-d4cb0c9c3470?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1738321791421-232f9ee2c487?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1545612036-2872840642dc?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1758448756350-3d0eec02ba37?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1778828494354-9b717d36dc99?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1671970922029-0430d2ae122c?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1775993719568-290840203239?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1775993167276-743bbcde77e1?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1546920120-d60663fd4965?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1778828450059-f39d5bbb01af?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1689877020200-403d8542d95d?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1561214078-f3247647fc5e?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1757924284732-4189190321cf?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1728486887318-398f1448f7ae?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1775993703558-e7afab02b7bd?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1754258167836-6878c54e316c?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1771586791190-97ed536c54af?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1649068618811-9f3547ef98fc?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1741156229623-da94e6d7977d?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1765728617086-5d16a7bac916?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1775993167571-cd1ff4cadada?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1778828494365-c798ff9cce2c?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1689514226761-336eaf77e311?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1623874514711-0f321325f318?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1775993699105-4d18bcac7e54?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1761971974992-6df33df97c3a?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1770493895453-4f758c40d11d?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1687350119840-3e2cc5977e92?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1716367840427-4c148eb7bf15?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1790094181041-add24d34ccfd?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1649847727298-27a50354bd46?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1714181871329-1392197011c2?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1790094180961-8396479c6542?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1632758243488-7e6f9173cfa1?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1675026482188-8102367ecc16?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1761839258420-5c3e2f2e2a74?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1771270786606-f5a0e57db762?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1434682772747-f16d3ea162c3?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1630703178161-1e2f9beddbf8?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1624513764372-a4eb7b334c62?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1521804906057-1df8fdb718b7?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1789629455545-6418611bb5a2?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1770513649465-2c60c8039806?auto=format&fit=crop&w=800&q=80',
        'https://images.unsplash.com/photo-1729156638758-6fa6405b289b?auto=format&fit=crop&w=800&q=80',
    ]);
    $gymGalleryImages = $gymCoverImages;
    $newGymGalleryImages = array_slice($gymCoverImages, count($gymImages));

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
    foreach ($allGymNames as $gymIndex => $name) {
            $gymId = $gymIndex + 1;
            $distId = $gymIndex >= count($allGymNames) - 4
                ? 23
                : (int) floor($gymIndex * 22 / (count($allGymNames) - 4)) + 1;
            $info = ['name' => $districtNamesById[$distId]];
            $slug = gf_slugify($name) . '-' . $gymId;
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
            $address = "Số " . (10 + $gymId * 3) . " Đường " . $name . ", " . $info['name'];
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
            $galleryTarget = $isFeatured ? 6 : 5;
            $galleryUrls = [];
            $galleryPoolCount = count($gymGalleryImages);
            $galleryStart = (($gymId - 1) * 13) % $galleryPoolCount;
            for ($step = 0; $step < $galleryPoolCount && count($galleryUrls) < $galleryTarget; $step++) {
                $galleryImage = $gymGalleryImages[($galleryStart + $step * 7) % $galleryPoolCount];
                if (str_replace('w=1200', 'w=800', $cover) === $galleryImage || in_array($galleryImage, $galleryUrls, true)) {
                    continue;
                }
                $galleryUrls[] = $galleryImage;
            }
            if ($galleryUrls && !array_intersect($galleryUrls, $newGymGalleryImages)) {
                foreach ($newGymGalleryImages as $galleryImage) {
                    if (str_replace('w=1200', 'w=800', $cover) !== $galleryImage && !in_array($galleryImage, $galleryUrls, true)) {
                        $galleryUrls[count($galleryUrls) - 1] = $galleryImage;
                        break;
                    }
                }
            }
            foreach ($galleryUrls as $galleryIndex => $galleryImage) {
                $insGI->execute([$gymId, $galleryImage, 'Không gian phòng tập', $galleryIndex + 1]);
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
    $trainerPortraitOverrides = [
        1 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2502497.webp?update_time=2025-11-27+03%3A30%3A05.737',
        2 => 'https://images.squarespace-cdn.com/content/v1/62e39313a3615b384cc19035/52a6f1e4-8e7c-40a3-b0c6-14a8bb0c05a2/xiu%2Bting.jpg',
        3 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2300326.webp?update_time=2025-06-21+08%3A48%3A01.600',
        4 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/700096.webp?update_time=2025-06-14+03%3A31%3A55.310',
        5 => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=400&q=80',
        6 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2405184.webp?update_time=2025-06-14+03%3A31%3A55.120',
        7 => 'https://tsquaredlab.com/images/trainers/haz.webp',
        8 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2101491.webp?update_time=2025-05-31+03%3A30%3A36.813',
        9 => 'https://static.wixstatic.com/media/3712cb_75431a0dff05471281ca426eefc1f808~mv2.jpg/v1/fill/w_980%2Ch_1307%2Cal_c%2Cq_85%2Cusm_0.66_1.00_0.01/3712cb_75431a0dff05471281ca426eefc1f808~mv2.jpg',
        10 => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=400&q=80',
        11 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2103599.webp?update_time=2025-05-29+03%3A31%3A37.583',
        12 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2405272.webp?update_time=2025-06-21+08%3A48%3A02.017',
        13 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2002233.webp?update_time=2025-07-31+03%3A30%3A07.820',
        14 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2502551.webp?update_time=2025-08-05+03%3A30%3A09.267',
        15 => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=400&q=80',
        16 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/790154.webp?update_time=2025-11-06+03%3A30%3A16.210',
        17 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2500506.webp?update_time=2025-05-31+03%3A30%3A36.857',
        18 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2402622.webp?update_time=2025-06-19+22%3A05%3A07.700',
        19 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2504511.webp?update_time=2026-02-15+03%3A30%3A11.880',
        20 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2302959.webp?update_time=2025-06-05+03%3A33%3A39.200',
        21 => 'https://images.squarespace-cdn.com/content/v1/588754e7bebafbcb2d477cbf/1759978920243-J0GHN0IZ0LQKLMY4Z3WN/RACH1.jpg',
        22 => 'https://www.workband.com.tw/store_image/forcefitness/P1174540170056.webp',
        23 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/370451.webp?update_time=2026-04-22+03%3A30%3A08.133',
        24 => 'https://files.worldgymtaiwan.com/pt_coach/portrait/2500840.webp?update_time=2026-04-22+03%3A30%3A08.127',
        25 => 'https://pure360-public.s3.ap-southeast-1.amazonaws.com/teacher_image_dev/1900.jpg?t=1663933548000',
        26 => 'https://stringssg-assets.s3.ap-southeast-1.amazonaws.com/static/personal-trainer/generated/Christabelle.jpeg',
        27 => 'https://images.squarespace-cdn.com/content/v1/63d9d5adb0b1063f7fa463a2/40365611-db3b-4e8c-9566-f14d5d3e2e6f/copy_AF8E7F50-5222-492E-A210-BFB5553B71CF.JPEG',
        28 => 'https://unrealpt.sg/cdn/shop/files/Hwa_Yang_Soh_Senior_Trainer.jpg?v=1726637029&width=2000',
        29 => 'https://images.squarespace-cdn.com/content/v1/66668442d380464892620714/97994512-76a2-433b-8e6c-dcc2dbc6d6e3/IMG_8221.jpg',
        30 => 'https://www.stringssg.com/_next/image?q=75&url=https%3A%2F%2Fstringssg-assets.s3-ap-southeast-1.amazonaws.com%2Fstatic%2Fpersonal-trainer%2Fgenerated%2FGerald.jpeg&w=1920',
    ];

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
