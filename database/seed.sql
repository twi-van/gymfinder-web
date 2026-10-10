USE gymfinder;

-- =====================================================================
-- 1. users
-- U01 admin; U02–U05 user active; U06 user locked
-- email dạng uXX@gymfinder.test (chữ thường)
-- password_hash là bcrypt của 'password' (chỉ dùng test)
-- =====================================================================
INSERT INTO users (id, full_name, email, phone, password_hash, role, status, goal) VALUES
(1, 'Admin GymFinder',  'u01@gymfinder.test', '0900000001',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',  'active', NULL),
(2, 'Nguyễn Văn An',   'u02@gymfinder.test', '0900000002',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',   'active', 'tang_co'),
(3, 'Trần Thị Bình',   'u03@gymfinder.test', '0900000003',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',   'active', 'giam_can'),
(4, 'Lê Minh Châu',    'u04@gymfinder.test', '0900000004',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',   'active', 'cai_thien_suc_khoe'),
(5, 'Phạm Quang Dũng', 'u05@gymfinder.test', '0900000005',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',   'active', 'tang_suc_manh'),
(6, 'Võ Thị Em',       'u06@gymfinder.test', '0900000006',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',   'locked', NULL);

-- =====================================================================
-- 2. districts
-- D01 Quận 1 (quan-1); D02 Quận 7 (quan-7); D03 Thủ Đức (thu-duc)
-- =====================================================================
INSERT INTO districts (id, name, slug) VALUES
(1, 'Quận 1',   'quan-1'),
(2, 'Quận 7',   'quan-7'),
(3, 'Thủ Đức',  'thu-duc');

-- =====================================================================
-- 3. categories
-- C01 Fitness (active); C02 Premium (active); C03 CrossFit (active)
-- C04 Yoga (active); C05 Boxing (is_active=0)
-- slug sinh theo quy tắc 
-- =====================================================================
INSERT INTO categories (id, name, slug, description, is_active) VALUES
(1, 'Fitness',  'fitness',  'Phòng tập thể hình, tạ và cardio',       1),
(2, 'Premium',  'premium',  'Phòng tập cao cấp, đầy đủ tiện ích',     1),
(3, 'CrossFit', 'crossfit', 'Tập luyện cường độ cao theo nhóm',        1),
(4, 'Yoga',     'yoga',     'Yoga, thiền và các bài tập thư giãn',     1),
(5, 'Boxing',   'boxing',   'Boxing, MMA và các môn võ thuật đối kháng', 0); -- inactive

-- =====================================================================
-- 4. amenities
-- A01 Phòng tắm; A02 Bãi giữ xe; A03 Wifi; A04 Xông hơi; A05 Tủ khóa
-- icon: FontAwesome class
-- =====================================================================
INSERT INTO amenities (id, name, slug, icon) VALUES
(1, 'Phòng tắm',  'phong-tam',  'fa-shower'),
(2, 'Bãi giữ xe', 'bai-giu-xe', 'fa-square-parking'),
(3, 'Wifi',       'wifi',       'fa-wifi'),
(4, 'Xông hơi',   'xong-hoi',   'fa-hot-tub-person'),
(5, 'Tủ khóa',    'tu-khoa',    'fa-lock');

-- =====================================================================
-- 5. specialties
-- S01 Strength; S02 Cardio & giảm cân; S03 Yoga; S04 Boxing
-- =====================================================================
INSERT INTO specialties (id, name, slug) VALUES
(1, 'Strength',         'strength'),
(2, 'Cardio & giảm cân','cardio-giam-can'),
(3, 'Yoga',             'yoga-specialty'),
(4, 'Boxing',           'boxing-specialty');

-- =====================================================================
-- 6. gyms
-- Giá tính bằng VND
-- Slug sinh từ name theo quy tắc
-- G01–G06: active; G07–G08: hidden
-- =====================================================================
INSERT INTO gyms (id, name, slug, description, address, district_id,
                  phone, email, website,
                  price_min, price_max, opening_hours, cover_image_url,
                  avg_rating, review_count, is_featured, status) VALUES
-- G01: D01, active, featured, 300k–900k, Cat: C01+C02, Amenity: A01+A02+A03+A05
(1, 'Iron Fitness Center', 'iron-fitness-center',
    'Phòng tập thể hình cao cấp với đầy đủ máy móc nhập khẩu.',
    '12 Nguyễn Huệ, Quận 1', 1,
    '+84281234567', 'contact@ironfitness.test', 'https://ironfitness.test',
    300000, 900000, '05:30 - 22:30', '/uploads/g01-cover.jpg',
    0, 0, 1, 'active'),

-- G02: D01, active, not featured, 200k–500k, Cat: C01+C03, Amenity: A01+A02
(2, 'CrossFit Quận 1', 'crossfit-quan-1',
    'Phòng tập CrossFit chuyên nghiệp, lớp nhóm hàng ngày.',
    '88 Lê Lợi, Quận 1', 1,
    '+84281234568', 'info@crossfitq1.test', 'https://crossfitq1.test',
    200000, 500000, '06:00 - 21:00', '/uploads/g02-cover.jpg',
    0, 0, 0, 'active'),

-- G03: D02, active, featured, 250k–700k, Cat: C04, Amenity: A01+A03
(3, 'Zen Yoga Studio', 'zen-yoga-studio',
    'Không gian yoga yên tĩnh, lớp nhỏ, giáo viên chứng chỉ quốc tế.',
    '45 Nguyễn Thị Thập, Quận 7', 2,
    '+84287654321', 'hello@zenyoga.test', 'https://zenyoga.test',
    250000, 700000, '06:00 - 21:00', '/uploads/g03-cover.jpg',
    0, 0, 1, 'active'),

-- G04: D02, active, not featured, 800k–2000k, Cat: C01+C02, Amenity: A01–A05
(4, 'Elite Premium Gym', 'elite-premium-gym',
    'Trải nghiệm tập luyện đẳng cấp, đầy đủ tiện ích cao cấp.',
    '200 Nguyễn Văn Linh, Quận 7', 2,
    '+84287654322', 'vip@elitepremium.test', 'https://elitepremium.test',
    800000, 2000000, '05:00 - 23:00', '/uploads/g04-cover.jpg',
    0, 0, 0, 'active'),

-- G05: D03, active, not featured, 150k–400k, Cat: C05 (inactive), không Trainer
(5, 'Boxing Club Thủ Đức', 'boxing-club-thu-duc',
    'Phòng tập boxing và MMA tại Thủ Đức.',
    '10 Võ Văn Ngân, Thủ Đức', 3,
    '+84289999001', NULL, NULL,
    150000, 400000, '07:00 - 21:00', '/uploads/g05-cover.jpg',
    0, 0, 0, 'active'),

-- G06: D03, active, not featured, 100k–300k, không Category, không Amenity
-- Chỉ có Trainer hidden → has_pt=0
(6, 'Gym Thủ Đức Cơ Bản', 'gym-thu-duc-co-ban',
    'Phòng tập cơ bản, giá sinh viên.',
    '55 Kha Vạn Cân, Thủ Đức', 3,
    '+84289999002', NULL, NULL,
    100000, 300000, '06:00 - 22:00', '/uploads/g06-cover.jpg',
    0, 0, 0, 'active'),

-- G07: D01, hidden, featured, 300k–600k, Cat: C01, Amenity: A01
-- Gym hidden + featured; không public
(7, 'Fit Zone Pro', 'fit-zone-pro',
    'Phòng tập hiện đại đang tạm ngưng hoạt động.',
    '99 Đinh Tiên Hoàng, Quận 1', 1,
    '+84281234569', NULL, NULL,
    300000, 600000, '06:00 - 22:00', '/uploads/g07-cover.jpg',
    0, 0, 1, 'hidden'),

-- G08: D02, hidden, not featured, 400k–800k, không Category, không Amenity
(8, 'Aqua Fitness', 'aqua-fitness',
    'Phòng tập kết hợp hồ bơi.',
    '77 Hoàng Diệu, Quận 7', 2,
    '+84287654323', NULL, NULL,
    400000, 800000, '06:00 - 20:00', '/uploads/g08-cover.jpg',
    0, 0, 0, 'hidden');

-- =====================================================================
-- 7. gym_categories
-- =====================================================================
INSERT INTO gym_categories (gym_id, category_id) VALUES
(1, 1), (1, 2),      -- G01: C01 Fitness, C02 Premium
(2, 1), (2, 3),      -- G02: C01 Fitness, C03 CrossFit
(3, 4),              -- G03: C04 Yoga
(4, 1), (4, 2),      -- G04: C01 Fitness, C02 Premium
(5, 5),              -- G05: C05 Boxing (inactive)
(7, 1);              -- G07: C01 Fitness (hidden gym)

-- =====================================================================
-- 8. gym_amenities
-- =====================================================================
INSERT INTO gym_amenities (gym_id, amenity_id) VALUES
(1, 1), (1, 2), (1, 3), (1, 5),          -- G01: A01,A02,A03,A05
(2, 1), (2, 2),                           -- G02: A01,A02
(3, 1), (3, 3),                           -- G03: A01,A03
(4, 1), (4, 2), (4, 3), (4, 4), (4, 5),  -- G04: A01–A05
(7, 1);                                   -- G07: A01

-- =====================================================================
-- 9. gym_images
-- G01: 2 ảnh bổ sung (sort_order 1,2); G03: 1 ảnh
-- cover_image_url không trùng ảnh trong gym_images
-- =====================================================================
INSERT INTO gym_images (gym_id, image_url, caption, sort_order) VALUES
(1, '/uploads/g01-img1.jpg', 'Khu tập tạ',    1),
(1, '/uploads/g01-img2.jpg', 'Khu cardio',    2),
(3, '/uploads/g03-img1.jpg', 'Studio yoga',   1);

-- =====================================================================
-- 10. trainers
-- district_id luôn = district của Gym chính
-- Slug sinh từ full_name 
-- =====================================================================
INSERT INTO trainers (id, full_name, slug, avatar_url,
                       specialty_id, years_experience, bio, phone, email,
                       gym_id, district_id,
                       avg_rating, review_count, is_featured, status) VALUES
-- T01: G01/D01, S01 Strength, 0 năm (biên 0 – nhóm 0–2), active, featured
(1,  'Nguyễn Anh Tuấn',    'nguyen-anh-tuan',    '/uploads/t01.jpg',
     1, 0,  'Chuyên gia Strength & Conditioning.', '+84901000001', 't01@gymfinder.test',
     1, 1, 0, 0, 1, 'active'),

-- T02: G01/D01, S02 Cardio, 2 năm (biên 2 – nhóm 0–2), active, not featured
(2,  'Trần Thị Kim Lan',   'tran-thi-kim-lan',   '/uploads/t02.jpg',
     2, 2,  'PT giảm cân và cardio.', '+84901000002', 't02@gymfinder.test',
     1, 1, 0, 0, 0, 'active'),

-- T03: G02/D01, S01 Strength, 3 năm (biên 3 – nhóm 3–5), active, not featured
(3,  'Lê Văn Bình',        'le-van-binh',        '/uploads/t03.jpg',
     1, 3,  'Huấn luyện viên CrossFit chuyên nghiệp.', '+84901000003', 't03@gymfinder.test',
     2, 1, 0, 0, 0, 'active'),

-- T04: G03/D02, S03 Yoga, 5 năm (biên 5 – CHỈ nhóm 3–5), active, featured
(4,  'Phạm Thu Hà',        'pham-thu-ha',        '/uploads/t04.jpg',
     3, 5,  'Giáo viên Yoga chứng chỉ RYT-500.', '+84901000004', 't04@gymfinder.test',
     2, 2, 0, 0, 1, 'active'),

-- T05: G03/D02, S03 Yoga, 6 năm (biên 6 – CHỈ nhóm 6+), active, not featured
(5,  'Võ Thị Mai',         'vo-thi-mai',         '/uploads/t05.jpg',
     3, 6,  'Yoga therapy và phục hồi chức năng.', '+84901000005', 't05@gymfinder.test',
     2, 2, 0, 0, 0, 'active'),

-- T06: G04/D02, S01 Strength, 60 năm (biên tối đa), active, featured
(6,  'Hoàng Quốc Cường',   'hoang-quoc-cuong',   '/uploads/t06.jpg',
     1, 60, 'Huyền thoại thể hình với 60 năm kinh nghiệm.', '+84901000006', 't06@gymfinder.test',
     2, 2, 0, 0, 1, 'active'),

-- T07: G04/D02, S02 Cardio, 4 năm, HIDDEN
(7,  'Ngô Thanh Hà',       'ngo-thanh-ha',       '/uploads/t07.jpg',
     2, 4,  'PT cardio và HIIT.', '+84901000007', 't07@gymfinder.test',
     2, 2, 0, 0, 0, 'hidden'),

-- T08: G07/D01, S01 Strength, 8 năm, active nhưng Gym chính hidden → không public
(8,  'Đinh Văn Khoa',      'dinh-van-khoa',      '/uploads/t08.jpg',
     1, 8,  'PT Strength thuộc Gym hiện tạm ngưng.', '+84901000008', 't08@gymfinder.test',
     7, 1, 0, 0, 0, 'active'),

-- T09: G06/D03, S04 Boxing, 1 năm, HIDDEN → G06 không có Trainer active (has_pt=0)
(9,  'Bùi Thị Nga',        'bui-thi-nga',        '/uploads/t09.jpg',
     4, 1,  'Mới bắt đầu huấn luyện boxing.', '+84901000009', 't09@gymfinder.test',
     6, 3, 0, 0, 0, 'hidden'),

-- T10: G02/D01, S04 Boxing, 10 năm (nhóm 6+), active, not featured
(10, 'Chu Minh Phúc',      'chu-minh-phuc',      '/uploads/t10.jpg',
     4, 10, 'Võ sĩ boxing chuyên nghiệp.', '+84901000010', 't10@gymfinder.test',
     2, 1, 0, 0, 0, 'active');

-- =====================================================================
-- 11. reviews
-- reviewed_by: dùng id của U01 (Admin) = 1
-- Cache kỳ vọng:
--   G01 = 4.5/2 (R01+R02); G02 = 4.0/1 (R13); G03 = 4.5/2 (R05+R06)
--   G07 = 4.0/1 (R11); T01 = 4.0/2 (R07+R08); T08 = 5.0/1 (R12)
--   T04 = 0/0; các target còn lại = 0/0
-- =====================================================================
INSERT INTO reviews (id, user_id, target_type, gym_id, trainer_id,
                      rating, comment, status, reviewed_by, reviewed_at, reject_reason) VALUES

-- R01: U02→G01, 5, approved
(1,  2, 'gym',     1,    NULL, 5, 'Máy móc hiện đại, đội ngũ PT nhiệt tình. Rất hài lòng!',
     'approved', 1, '2026-09-01 04:00:00', NULL),

-- R02: U03→G01, 4, approved
(2,  3, 'gym',     1,    NULL, 4, 'Phòng tập sạch sẽ, thoáng mát. Hơi đông vào buổi tối.',
     'approved', 1, '2026-09-02 04:00:00', NULL),

-- R03: U04→G01, 3, pending → không tính
(3,  4, 'gym',     1,    NULL, 3, 'Tạm được, chờ Admin duyệt.',
     'pending',  NULL, NULL, NULL),

-- R04: U05→G01, 2, rejected → không tính
(4,  5, 'gym',     1,    NULL, 2, 'Không hài lòng.',
     'rejected', 1, '2026-09-03 04:00:00', 'Nội dung không phù hợp'),

-- R05: U02→G03, 4, approved
(5,  2, 'gym',     3,    NULL, 4, 'Studio yoga rất yên tĩnh, giáo viên chuyên nghiệp.',
     'approved', 1, '2026-09-04 04:00:00', NULL),

-- R06: U03→G03, 5, approved
(6,  3, 'gym',     3,    NULL, 5, 'Lớp yoga nhỏ, tập trung được. Giáo viên tận tâm.',
     'approved', 1, '2026-09-05 04:00:00', NULL),

-- R07: U02→T01, 5, approved
(7,  2, 'trainer', NULL, 1,   5, 'PT Tuấn xây giáo án rất bài bản, hiệu quả rõ rệt.',
     'approved', 1, '2026-09-06 04:00:00', NULL),

-- R08: U03→T01, 3, approved
(8,  3, 'trainer', NULL, 1,   3, 'PT nhiệt tình nhưng đôi khi thiếu linh hoạt về lịch.',
     'approved', 1, '2026-09-07 04:00:00', NULL),

-- R09: U04→T01, 4, pending → không tính
(9,  4, 'trainer', NULL, 1,   4, 'Khá tốt, đang chờ duyệt.',
     'pending',  NULL, NULL, NULL),

-- R10: U05→T04, 1, rejected → không tính; T04 cache = 0/0
(10, 5, 'trainer', NULL, 4,   1, 'Spam comment.',
     'rejected', 1, '2026-09-08 04:00:00', 'Spam'),

-- R11: U02→G07, 4, approved; target hidden → User vẫn sửa/xóa được
(11, 2, 'gym',     7,    NULL, 4, 'Phòng tập ổn, tiếc là tạm ngưng hoạt động.',
     'approved', 1, '2026-09-09 04:00:00', NULL),

-- R12: U03→T08, 5, approved; T08 có Gym chính hidden
(12, 3, 'trainer', NULL, 8,   5, 'PT Khoa dạy rất tốt dù phòng tập tạm đóng.',
     'approved', 1, '2026-09-10 04:00:00', NULL),

-- R13: U06→G02, 4, approved; tác giả là User locked → vẫn tính cache
(13, 6, 'gym',     2,    NULL, 4, 'Crossfit hấp dẫn, lớp nhóm vui.',
     'approved', 1, '2026-09-11 04:00:00', NULL);

-- =====================================================================
-- 12. favorites (7 bản ghi – spec §14)
-- F01 U02→G01; F02 U02→G03; F03 U02→T01; F04 U03→G01
-- F05 U03→T04; F06 U04→G07 (hidden); F07 U04→T08 (Gym chính hidden)
-- =====================================================================
INSERT INTO favorites (id, user_id, target_type, gym_id, trainer_id) VALUES
(1, 2, 'gym',     1,    NULL),  -- F01 U02→G01
(2, 2, 'gym',     3,    NULL),  -- F02 U02→G03
(3, 2, 'trainer', NULL, 1),     -- F03 U02→T01
(4, 3, 'gym',     1,    NULL),  -- F04 U03→G01
(5, 3, 'trainer', NULL, 4),     -- F05 U03→T04
(6, 4, 'gym',     7,    NULL),  -- F06 U04→G07 (target hidden: bị bỏ qua khi đọc)
(7, 4, 'trainer', NULL, 8);     -- F07 U04→T08 (Gym chính hidden)

-- =====================================================================
-- 13. Cập nhật rating cache 
-- Chỉ tính review status='approved'
-- avg_rating = ROUND(AVG(rating), 1); review_count = COUNT(*)
-- Kỳ vọng: G01=4.5/2; G02=4.0/1; G03=4.5/2; G07=4.0/1
--           T01=4.0/2; T08=5.0/1; T04=0/0
-- =====================================================================
UPDATE gyms g
SET
  g.avg_rating   = COALESCE(
    (SELECT ROUND(AVG(r.rating), 1)
     FROM reviews r
     WHERE r.gym_id = g.id AND r.status = 'approved'), 0),
  g.review_count = (
    SELECT COUNT(*)
    FROM reviews r
    WHERE r.gym_id = g.id AND r.status = 'approved');

UPDATE trainers t
SET
  t.avg_rating   = COALESCE(
    (SELECT ROUND(AVG(r.rating), 1)
     FROM reviews r
     WHERE r.trainer_id = t.id AND r.status = 'approved'), 0),
  t.review_count = (
    SELECT COUNT(*)
    FROM reviews r
    WHERE r.trainer_id = t.id AND r.status = 'approved');

-- =====================================================================
-- Kiểm tra toàn vẹn district Trainer–Gym
-- Truy vấn sau phải trả 0 dòng:
-- SELECT t.id FROM trainers t JOIN gyms g ON g.id=t.gym_id
-- WHERE t.district_id <> g.district_id;
-- =====================================================================
