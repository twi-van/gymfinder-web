USE gymfinder;

INSERT INTO users (full_name, email, phone, password_hash, role, goal) VALUES
('Quản trị viên', 'admin@gymfinder.vn', '0900000000', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL),
('Nguyễn Văn An', 'an@example.com',    '0901111111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',  'tang_co'),
('Trần Thị Bình', 'binh@example.com',  '0902222222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user',  'giam_can');

INSERT INTO districts (name, slug) VALUES
('Quận 1','quan-1'),('Quận 3','quan-3'),('Quận 5','quan-5'),('Quận 7','quan-7'),
('Quận 10','quan-10'),('Quận Bình Thạnh','binh-thanh'),('Quận Phú Nhuận','phu-nhuan'),
('Quận Tân Bình','tan-binh'),('Quận Gò Vấp','go-vap'),('TP. Thủ Đức','thu-duc');

INSERT INTO categories (name, slug, description) VALUES
('Gym truyền thống','gym-truyen-thong','Phòng tập tạ, máy móc đầy đủ'),
('Yoga & Pilates','yoga-pilates','Lớp yoga, pilates, thiền'),
('CrossFit','crossfit','Tập luyện cường độ cao theo nhóm'),
('Boxing / MMA','boxing-mma','Võ thuật và đối kháng'),
('Cao cấp','cao-cap','Phòng tập chuẩn 5 sao'),
('Mở cửa 24/7','mo-cua-24-7','Hoạt động cả ngày lẫn đêm');

INSERT INTO amenities (name, slug, icon) VALUES
('Bãi giữ xe','parking','fa-square-parking'),('Wifi','wifi','fa-wifi'),
('Sauna','sauna','fa-hot-tub-person'),('Phòng tắm','shower','fa-shower'),
('Tủ khóa','locker','fa-lock'),('Hồ bơi','pool','fa-water-ladder'),
('Điều hòa','air-conditioner','fa-snowflake'),('Quầy nước','juice-bar','fa-bottle-water');

INSERT INTO specialties (name, slug) VALUES
('Giảm cân','giam-can'),('Tăng cơ','tang-co'),('Tăng sức mạnh','tang-suc-manh'),
('Yoga','yoga'),('Boxing','boxing'),('Phục hồi chấn thương','phuc-hoi-chan-thuong');

INSERT INTO gyms (name, slug, description, address, district_id, phone, price_min, price_max, opening_hours, is_featured) VALUES
('Iron Temple Gym','iron-temple-gym','Phòng tập tạ chuyên nghiệp với đầy đủ máy móc nhập khẩu.','12 Nguyễn Huệ, Bến Nghé',1,'0281234567',500000,1200000,'05:00 - 22:00',1),
('Zen Yoga Studio','zen-yoga-studio','Không gian yoga yên tĩnh, lớp nhỏ.','45 Võ Văn Tần, Phường 6',2,'0287654321',700000,1500000,'06:00 - 21:00',1),
('Fit24 Sài Gòn','fit24-sai-gon','Mở cửa 24/7, giá sinh viên.','88 Nguyễn Thị Thập, Tân Phong',4,'0289999888',300000,600000,'24/7',0);

INSERT INTO gym_categories (gym_id, category_id) VALUES (1,1),(1,5),(2,2),(3,1),(3,6);
INSERT INTO gym_amenities (gym_id, amenity_id) VALUES
(1,1),(1,2),(1,3),(1,4),(2,2),(2,4),(2,7),(3,1),(3,2),(3,5);

INSERT INTO trainers (full_name, slug, specialty_id, years_experience, bio, phone, gym_id, district_id, is_featured) VALUES
('Lê Minh Tuấn','le-minh-tuan',2,6,'Huấn luyện viên tăng cơ, từng thi đấu thể hình.','0903333333',1,1,1),
('Phạm Thu Hà','pham-thu-ha',4,4,'Chứng chỉ yoga quốc tế RYT-200.','0904444444',2,2,1),
('Võ Hoàng Nam','vo-hoang-nam',1,2,'Chuyên giảm mỡ cho người mới bắt đầu.','0905555555',3,4,0);

INSERT INTO reviews (user_id, target_type, gym_id, trainer_id, rating, comment, status, reviewed_by, reviewed_at, reject_reason) VALUES
(2,'gym',    1,    NULL,5,'Máy móc đầy đủ, huấn luyện viên nhiệt tình.',        'approved',1,NOW(),NULL),
(3,'gym',    1,    NULL,4,'Phòng tập sạch, hơi đông vào buổi tối.',             'approved',1,NOW(),NULL),
(2,'gym',    2,    NULL,5,'Lớp yoga nhỏ, giáo viên rất tận tâm.',               'pending', NULL,NULL,NULL),
(3,'trainer',NULL, 1,   5,'PT Tuấn xây giáo án rất bài bản.',                   'approved',1,NOW(),NULL),
(2,'trainer',NULL, 2,   2,'Nội dung chưa liên quan đến chất lượng huấn luyện.', 'rejected',1,NOW(),'Nội dung không phản ánh trải nghiệm thực tế với Trainer');

INSERT INTO favorites (user_id, target_type, gym_id, trainer_id) VALUES
(2,'gym',    1,    NULL),
(2,'trainer',NULL, 1),
(3,'gym',    2,    NULL);

UPDATE gyms g SET
  g.avg_rating   = COALESCE((SELECT ROUND(AVG(r.rating),1) FROM reviews r WHERE r.gym_id = g.id AND r.status = 'approved'), 0),
  g.review_count = (SELECT COUNT(*) FROM reviews r WHERE r.gym_id = g.id AND r.status = 'approved');

UPDATE trainers t SET
  t.avg_rating   = COALESCE((SELECT ROUND(AVG(r.rating),1) FROM reviews r WHERE r.trainer_id = t.id AND r.status = 'approved'), 0),
  t.review_count = (SELECT COUNT(*) FROM reviews r WHERE r.trainer_id = t.id AND r.status = 'approved');
