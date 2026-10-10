<?php
declare(strict_types=1);

function gf_sqlite_install(PDO $pdo): void
{
    $pdo->exec('PRAGMA foreign_keys = OFF');
    $pdo->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  full_name TEXT NOT NULL,
  email TEXT NOT NULL UNIQUE,
  phone TEXT,
  password_hash TEXT NOT NULL,
  avatar_url TEXT,
  goal TEXT,
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
  specialty_id INTEGER NOT NULL,
  years_experience INTEGER NOT NULL,
  bio TEXT,
  phone TEXT,
  email TEXT,
  gym_id INTEGER NOT NULL,
  district_id INTEGER NOT NULL,
  avg_rating REAL NOT NULL DEFAULT 0,
  review_count INTEGER NOT NULL DEFAULT 0,
  is_featured INTEGER NOT NULL DEFAULT 0,
  status TEXT NOT NULL DEFAULT 'active',
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (gym_id) REFERENCES gyms(id),
  FOREIGN KEY (specialty_id) REFERENCES specialties(id),
  FOREIGN KEY (district_id) REFERENCES districts(id)
);
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
CREATE UNIQUE INDEX IF NOT EXISTS uq_review_user_gym ON reviews(user_id, gym_id) WHERE gym_id IS NOT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS uq_review_user_trainer ON reviews(user_id, trainer_id) WHERE trainer_id IS NOT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS uq_fav_user_gym ON favorites(user_id, gym_id) WHERE gym_id IS NOT NULL;
CREATE UNIQUE INDEX IF NOT EXISTS uq_fav_user_trainer ON favorites(user_id, trainer_id) WHERE trainer_id IS NOT NULL;
SQL);
    $pdo->exec('PRAGMA foreign_keys = ON');

    $hash = password_hash('password', PASSWORD_BCRYPT);

    $pdo->prepare('INSERT INTO users (id, full_name, email, phone, password_hash, role, status, goal) VALUES (?,?,?,?,?,?,?,?)')->execute(
        [1, 'Admin GymFinder', 'u01@gymfinder.test', '0900000001', $hash, 'admin', 'active', null]
    );
    $users = [
        [2, 'Nguyễn Văn An', 'u02@gymfinder.test', '0900000002', 'user', 'active', 'tang_co'],
        [3, 'Trần Thị Bình', 'u03@gymfinder.test', '0900000003', 'user', 'active', 'giam_can'],
        [4, 'Lê Minh Châu', 'u04@gymfinder.test', '0900000004', 'user', 'active', 'cai_thien_suc_khoe'],
        [5, 'Phạm Quang Dũng', 'u05@gymfinder.test', '0900000005', 'user', 'active', 'tang_suc_manh'],
        [6, 'Võ Thị Em', 'u06@gymfinder.test', '0900000006', 'user', 'locked', null],
    ];
    $insU = $pdo->prepare('INSERT INTO users (id, full_name, email, phone, password_hash, role, status, goal) VALUES (?,?,?,?,?,?,?,?)');
    foreach ($users as $u) {
        $insU->execute([$u[0], $u[1], $u[2], $u[3], $hash, $u[4], $u[5], $u[6]]);
    }

    foreach ([[1, 'Quận 1', 'quan-1'], [2, 'Quận 7', 'quan-7'], [3, 'Thủ Đức', 'thu-duc']] as $d) {
        $pdo->prepare('INSERT INTO districts (id, name, slug) VALUES (?,?,?)')->execute($d);
    }

    $cats = [
        [1, 'Fitness', 'fitness', 'Phòng tập thể hình, tạ và cardio', 1],
        [2, 'Premium', 'premium', 'Phòng tập cao cấp, đầy đủ tiện ích', 1],
        [3, 'CrossFit', 'crossfit', 'Tập luyện cường độ cao theo nhóm', 1],
        [4, 'Yoga', 'yoga', 'Yoga, thiền và các bài tập thư giãn', 1],
        [5, 'Boxing', 'boxing', 'Boxing, MMA và các môn võ thuật đối kháng', 0],
    ];
    $insC = $pdo->prepare('INSERT INTO categories (id, name, slug, description, is_active) VALUES (?,?,?,?,?)');
    foreach ($cats as $c) {
        $insC->execute($c);
    }

    $ams = [
        [1, 'Phòng tắm', 'phong-tam', 'fa-shower'],
        [2, 'Bãi giữ xe', 'bai-giu-xe', 'fa-square-parking'],
        [3, 'Wifi', 'wifi', 'fa-wifi'],
        [4, 'Xông hơi', 'xong-hoi', 'fa-hot-tub-person'],
        [5, 'Tủ khóa', 'tu-khoa', 'fa-lock'],
    ];
    $insA = $pdo->prepare('INSERT INTO amenities (id, name, slug, icon) VALUES (?,?,?,?)');
    foreach ($ams as $a) {
        $insA->execute($a);
    }

    foreach ([[1, 'Strength', 'strength'], [2, 'Cardio & giảm cân', 'cardio-giam-can'], [3, 'Yoga', 'yoga-specialty'], [4, 'Boxing', 'boxing-specialty']] as $s) {
        $pdo->prepare('INSERT INTO specialties (id, name, slug) VALUES (?,?,?)')->execute($s);
    }

    $gyms = [
        [1, 'Iron Fitness Center', 'iron-fitness-center', 'Phòng tập thể hình cao cấp với đầy đủ máy móc nhập khẩu.', '12 Nguyễn Huệ, Quận 1', 1, '+84281234567', 'contact@ironfitness.test', 'https://ironfitness.test', 300000, 900000, '05:30 - 22:30', 1, 'active'],
        [2, 'CrossFit Quận 1', 'crossfit-quan-1', 'Phòng tập CrossFit chuyên nghiệp, lớp nhóm hàng ngày.', '88 Lê Lợi, Quận 1', 1, '+84281234568', 'info@crossfitq1.test', 'https://crossfitq1.test', 200000, 500000, '06:00 - 21:00', 0, 'active'],
        [3, 'Zen Yoga Studio', 'zen-yoga-studio', 'Không gian yoga yên tĩnh, lớp nhỏ, giáo viên chứng chỉ quốc tế.', '45 Nguyễn Thị Thập, Quận 7', 2, '+84287654321', 'hello@zenyoga.test', 'https://zenyoga.test', 250000, 700000, '06:00 - 21:00', 1, 'active'],
        [4, 'Elite Premium Gym', 'elite-premium-gym', 'Trải nghiệm tập luyện đẳng cấp, đầy đủ tiện ích cao cấp.', '200 Nguyễn Văn Linh, Quận 7', 2, '+84287654322', 'vip@elitepremium.test', 'https://elitepremium.test', 800000, 2000000, '05:00 - 23:00', 0, 'active'],
        [5, 'Boxing Club Thủ Đức', 'boxing-club-thu-duc', 'Phòng tập boxing và MMA tại Thủ Đức.', '10 Võ Văn Ngân, Thủ Đức', 3, '+84289999001', null, null, 150000, 400000, '07:00 - 21:00', 0, 'active'],
        [6, 'Gym Thủ Đức Cơ Bản', 'gym-thu-duc-co-ban', 'Phòng tập cơ bản, giá sinh viên.', '55 Kha Vạn Cân, Thủ Đức', 3, '+84289999002', null, null, 100000, 300000, '06:00 - 22:00', 0, 'active'],
        [7, 'Fit Zone Pro', 'fit-zone-pro', 'Phòng tập hiện đại đang tạm ngưng hoạt động.', '99 Đinh Tiên Hoàng, Quận 1', 1, '+84281234569', null, null, 300000, 600000, '06:00 - 22:00', 1, 'hidden'],
        [8, 'Aqua Fitness', 'aqua-fitness', 'Phòng tập kết hợp hồ bơi.', '77 Hoàng Diệu, Quận 7', 2, '+84287654323', null, null, 400000, 800000, '06:00 - 20:00', 0, 'hidden'],
    ];
    $insG = $pdo->prepare('INSERT INTO gyms (id, name, slug, description, address, district_id, phone, email, website, price_min, price_max, opening_hours, is_featured, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    foreach ($gyms as $g) {
        $insG->execute($g);
    }

    foreach ([[1, 1], [1, 2], [2, 1], [2, 3], [3, 4], [4, 1], [4, 2], [5, 5], [7, 1]] as $gc) {
        $pdo->prepare('INSERT INTO gym_categories (gym_id, category_id) VALUES (?,?)')->execute($gc);
    }
    foreach ([[1, 1], [1, 2], [1, 3], [1, 5], [2, 1], [2, 2], [3, 1], [3, 3], [4, 1], [4, 2], [4, 3], [4, 4], [4, 5], [7, 1]] as $ga) {
        $pdo->prepare('INSERT INTO gym_amenities (gym_id, amenity_id) VALUES (?,?)')->execute($ga);
    }
    $pdo->prepare('INSERT INTO gym_images (gym_id, image_url, caption, sort_order) VALUES (?,?,?,?)')->execute([1, 'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?auto=format&fit=crop&w=800&q=80', 'Khu tập tạ', 1]);
    $pdo->prepare('INSERT INTO gym_images (gym_id, image_url, caption, sort_order) VALUES (?,?,?,?)')->execute([1, 'https://images.unsplash.com/photo-1540497077202-7c8a3999166f?auto=format&fit=crop&w=800&q=80', 'Khu cardio', 2]);
    $pdo->prepare('INSERT INTO gym_images (gym_id, image_url, caption, sort_order) VALUES (?,?,?,?)')->execute([3, 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?auto=format&fit=crop&w=800&q=80', 'Studio yoga', 1]);

    $trainers = [
        [1, 'Nguyễn Anh Tuấn', 'nguyen-anh-tuan', 1, 0, 'Chuyên gia Strength & Conditioning.', '+84901000001', 't01@gymfinder.test', 1, 1, 1, 'active'],
        [2, 'Trần Thị Kim Lan', 'tran-thi-kim-lan', 2, 2, 'PT giảm cân và cardio.', '+84901000002', 't02@gymfinder.test', 1, 1, 0, 'active'],
        [3, 'Lê Văn Bình', 'le-van-binh', 1, 3, 'Huấn luyện viên CrossFit chuyên nghiệp.', '+84901000003', 't03@gymfinder.test', 2, 1, 0, 'active'],
        [4, 'Phạm Thu Hà', 'pham-thu-ha', 3, 5, 'Giáo viên Yoga chứng chỉ RYT-500.', '+84901000004', 't04@gymfinder.test', 3, 2, 1, 'active'],
        [5, 'Võ Thị Mai', 'vo-thi-mai', 3, 6, 'Yoga therapy và phục hồi chức năng.', '+84901000005', 't05@gymfinder.test', 3, 2, 0, 'active'],
        [6, 'Hoàng Quốc Cường', 'hoang-quoc-cuong', 1, 60, 'Huyền thoại thể hình với 60 năm kinh nghiệm.', '+84901000006', 't06@gymfinder.test', 4, 2, 1, 'active'],
        [7, 'Ngô Thanh Hà', 'ngo-thanh-ha', 2, 4, 'PT cardio và HIIT.', '+84901000007', 't07@gymfinder.test', 4, 2, 0, 'hidden'],
        [8, 'Đinh Văn Khoa', 'dinh-van-khoa', 1, 8, 'PT Strength thuộc Gym hiện tạm ngưng.', '+84901000008', 't08@gymfinder.test', 7, 1, 0, 'active'],
        [9, 'Bùi Thị Nga', 'bui-thi-nga', 4, 1, 'Mới bắt đầu huấn luyện boxing.', '+84901000009', 't09@gymfinder.test', 6, 3, 0, 'hidden'],
        [10, 'Chu Minh Phúc', 'chu-minh-phuc', 4, 10, 'Võ sĩ boxing chuyên nghiệp.', '+84901000010', 't10@gymfinder.test', 2, 1, 0, 'active'],
    ];
    $insT = $pdo->prepare('INSERT INTO trainers (id, full_name, slug, specialty_id, years_experience, bio, phone, email, gym_id, district_id, is_featured, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
    foreach ($trainers as $t) {
        $insT->execute($t);
    }

    $reviews = [
        [1, 2, 'gym', 1, null, 5, 'Máy móc hiện đại, đội ngũ PT nhiệt tình. Rất hài lòng!', 'approved', 1, '2026-09-01 04:00:00', null],
        [2, 3, 'gym', 1, null, 4, 'Phòng tập sạch sẽ, thoáng mát. Hơi đông vào buổi tối.', 'approved', 1, '2026-09-02 04:00:00', null],
        [3, 4, 'gym', 1, null, 3, 'Tạm được, chờ Admin duyệt.', 'pending', null, null, null],
        [4, 5, 'gym', 1, null, 2, 'Không hài lòng.', 'rejected', 1, '2026-09-03 04:00:00', 'Nội dung không phù hợp'],
        [5, 2, 'gym', 3, null, 4, 'Studio yoga rất yên tĩnh, giáo viên chuyên nghiệp.', 'approved', 1, '2026-09-04 04:00:00', null],
        [6, 3, 'gym', 3, null, 5, 'Lớp yoga nhỏ, tập trung được. Giáo viên tận tâm.', 'approved', 1, '2026-09-05 04:00:00', null],
        [7, 2, 'trainer', null, 1, 5, 'PT Tuấn xây giáo án rất bài bản, hiệu quả rõ rệt.', 'approved', 1, '2026-09-06 04:00:00', null],
        [8, 3, 'trainer', null, 1, 3, 'PT nhiệt tình nhưng đôi khi thiếu linh hoạt về lịch.', 'approved', 1, '2026-09-07 04:00:00', null],
        [9, 4, 'trainer', null, 1, 4, 'Khá tốt, đang chờ duyệt.', 'pending', null, null, null],
        [10, 5, 'trainer', null, 4, 1, 'Spam comment.', 'rejected', 1, '2026-09-08 04:00:00', 'Spam'],
        [11, 2, 'gym', 7, null, 4, 'Phòng tập ổn, tiếc là tạm ngưng hoạt động.', 'approved', 1, '2026-09-09 04:00:00', null],
        [12, 3, 'trainer', null, 8, 5, 'PT Khoa dạy rất tốt dù phòng tập tạm đóng.', 'approved', 1, '2026-09-10 04:00:00', null],
        [13, 6, 'gym', 2, null, 4, 'Crossfit hấp dẫn, lớp nhóm vui.', 'approved', 1, '2026-09-11 04:00:00', null],
    ];
    $insR = $pdo->prepare('INSERT INTO reviews (id, user_id, target_type, gym_id, trainer_id, rating, comment, status, reviewed_by, reviewed_at, reject_reason) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    foreach ($reviews as $r) {
        $insR->execute($r);
    }

    $favs = [
        [1, 2, 'gym', 1, null],
        [2, 2, 'gym', 3, null],
        [3, 2, 'trainer', null, 1],
        [4, 3, 'gym', 1, null],
        [5, 3, 'trainer', null, 4],
        [6, 4, 'gym', 7, null],
        [7, 4, 'trainer', null, 8],
    ];
    $insF = $pdo->prepare('INSERT INTO favorites (id, user_id, target_type, gym_id, trainer_id) VALUES (?,?,?,?,?)');
    foreach ($favs as $f) {
        $insF->execute($f);
    }

    $pdo->exec("UPDATE gyms SET avg_rating = COALESCE((SELECT ROUND(AVG(rating),1) FROM reviews WHERE gym_id = gyms.id AND status='approved'),0), review_count = (SELECT COUNT(*) FROM reviews WHERE gym_id = gyms.id AND status='approved')");
    $pdo->exec("UPDATE trainers SET avg_rating = COALESCE((SELECT ROUND(AVG(rating),1) FROM reviews WHERE trainer_id = trainers.id AND status='approved'),0), review_count = (SELECT COUNT(*) FROM reviews WHERE trainer_id = trainers.id AND status='approved')");
}
