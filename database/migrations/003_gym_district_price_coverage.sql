-- Chạy một lần trên database hiện có để thêm Quận 2 và dữ liệu demo đủ giá,
-- đánh giá, HLV. Không xóa hoặc sửa gym do người dùng tự tạo.
INSERT INTO districts (name, slug)
SELECT 'Quận 2', 'quan-2'
WHERE NOT EXISTS (SELECT 1 FROM districts WHERE slug='quan-2');

INSERT INTO gyms (name, slug, description, address, district_id, phone, email, website, price_min, price_max, opening_hours, cover_image_url, is_featured, status)
SELECT 'The New Gym Thảo Điền', 'the-new-gym-thao-dien-61', 'Phòng tập Thảo Điền có thiết bị hiện đại, lớp nhóm và HLV hỗ trợ hội viên.', 'Số 193 Đường Xuân Thủy, Thảo Điền, Quận 2', (SELECT id FROM districts WHERE slug='quan-2'), '+84281000061', 'contact.gym61@gymfinder.test', 'https://gym61.gymfinder.test', 200000, 450000, '05:30 - 22:00', 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?auto=format&fit=crop&w=800&q=80', 1, 'active'
WHERE NOT EXISTS (SELECT 1 FROM gyms WHERE slug='the-new-gym-thao-dien-61');
INSERT INTO gyms (name, slug, description, address, district_id, phone, email, website, price_min, price_max, opening_hours, cover_image_url, is_featured, status)
SELECT 'CityGym An Phú', 'citygym-an-phu-62', 'Phòng tập An Phú có khu tạ, cardio và nhiều lớp huấn luyện theo nhóm.', 'Số 12 Đường Song Hành, An Phú, Quận 2', (SELECT id FROM districts WHERE slug='quan-2'), '+84281000062', 'contact.gym62@gymfinder.test', 'https://gym62.gymfinder.test', 350000, 700000, '06:00 - 22:00', 'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?auto=format&fit=crop&w=800&q=80', 0, 'active'
WHERE NOT EXISTS (SELECT 1 FROM gyms WHERE slug='citygym-an-phu-62');
INSERT INTO gyms (name, slug, description, address, district_id, phone, email, website, price_min, price_max, opening_hours, cover_image_url, is_featured, status)
SELECT 'Fit24 Sala', 'fit24-sala-63', 'Phòng tập Sala có không gian rộng, lớp yoga và khu tập chức năng.', 'Số 10 Đường Mai Chí Thọ, Khu đô thị Sala, Quận 2', (SELECT id FROM districts WHERE slug='quan-2'), '+84281000063', 'contact.gym63@gymfinder.test', 'https://gym63.gymfinder.test', 700000, 1200000, '05:00 - 23:00', 'https://images.unsplash.com/photo-1540497077202-7c8a3999166f?auto=format&fit=crop&w=800&q=80', 0, 'active'
WHERE NOT EXISTS (SELECT 1 FROM gyms WHERE slug='fit24-sala-63');
INSERT INTO gyms (name, slug, description, address, district_id, phone, email, website, price_min, price_max, opening_hours, cover_image_url, is_featured, status)
SELECT 'Elite Fitness Thủ Thiêm', 'elite-fitness-thu-thiem-64', 'Phòng tập Thủ Thiêm có dịch vụ cao cấp và HLV cá nhân.', 'Số 20 Đường Nguyễn Cơ Thạch, Thủ Thiêm, Quận 2', (SELECT id FROM districts WHERE slug='quan-2'), '+84281000064', 'contact.gym64@gymfinder.test', 'https://gym64.gymfinder.test', 1500000, 3500000, '05:00 - 23:00', 'https://images.unsplash.com/photo-1517838277536-f5f99be501cd?auto=format&fit=crop&w=800&q=80', 1, 'active'
WHERE NOT EXISTS (SELECT 1 FROM gyms WHERE slug='elite-fitness-thu-thiem-64');

INSERT INTO gym_images (gym_id, image_url, caption, sort_order)
SELECT g.id, 'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?auto=format&fit=crop&w=800&q=80', 'Khu tập luyện đa năng', 1
FROM gyms g WHERE g.slug='the-new-gym-thao-dien-61' AND NOT EXISTS (SELECT 1 FROM gym_images i WHERE i.gym_id=g.id);
INSERT INTO gym_images (gym_id, image_url, caption, sort_order)
SELECT g.id, g.cover_image_url, 'Khu tập luyện đa năng', 1
FROM gyms g WHERE g.slug IN ('citygym-an-phu-62','fit24-sala-63','elite-fitness-thu-thiem-64')
AND NOT EXISTS (SELECT 1 FROM gym_images i WHERE i.gym_id=g.id);

INSERT INTO gym_categories (gym_id, category_id)
SELECT g.id, c.category_id FROM gyms g
JOIN (SELECT 'the-new-gym-thao-dien-61' slug, 1 category_id UNION ALL SELECT 'the-new-gym-thao-dien-61',4 UNION ALL SELECT 'citygym-an-phu-62',1 UNION ALL SELECT 'citygym-an-phu-62',5 UNION ALL SELECT 'fit24-sala-63',1 UNION ALL SELECT 'fit24-sala-63',4 UNION ALL SELECT 'elite-fitness-thu-thiem-64',1 UNION ALL SELECT 'elite-fitness-thu-thiem-64',2) c ON c.slug=g.slug
WHERE NOT EXISTS (SELECT 1 FROM gym_categories gc WHERE gc.gym_id=g.id AND gc.category_id=c.category_id);
INSERT INTO gym_amenities (gym_id, amenity_id)
SELECT g.id, a.amenity_id FROM gyms g
JOIN (SELECT 'the-new-gym-thao-dien-61' slug, 1 amenity_id UNION ALL SELECT 'the-new-gym-thao-dien-61',2 UNION ALL SELECT 'the-new-gym-thao-dien-61',3 UNION ALL SELECT 'citygym-an-phu-62',1 UNION ALL SELECT 'citygym-an-phu-62',3 UNION ALL SELECT 'citygym-an-phu-62',6 UNION ALL SELECT 'fit24-sala-63',1 UNION ALL SELECT 'fit24-sala-63',4 UNION ALL SELECT 'fit24-sala-63',6 UNION ALL SELECT 'elite-fitness-thu-thiem-64',1 UNION ALL SELECT 'elite-fitness-thu-thiem-64',4 UNION ALL SELECT 'elite-fitness-thu-thiem-64',6) a ON a.slug=g.slug
WHERE NOT EXISTS (SELECT 1 FROM gym_amenities ga WHERE ga.gym_id=g.id AND ga.amenity_id=a.amenity_id);

-- Add an active trainer to seeded gyms that do not yet have one.
INSERT INTO gym_trainers (gym_id, trainer_id)
SELECT g.id, ((g.id - 1) % 27) + 1
FROM gyms g
WHERE (g.id BETWEEN 1 AND 60 OR g.slug IN ('the-new-gym-thao-dien-61','citygym-an-phu-62','fit24-sala-63','elite-fitness-thu-thiem-64'))
AND NOT EXISTS (
  SELECT 1 FROM trainers t LEFT JOIN gym_trainers gt ON gt.trainer_id=t.id
  WHERE (t.gym_id=g.id OR gt.gym_id=g.id) AND t.status='active'
);

-- Make sure every seeded gym has an approved rating; preserve existing reviews.
INSERT INTO reviews (user_id, target_type, gym_id, trainer_id, rating, comment, status, reviewed_by, reviewed_at, reject_reason, created_at)
SELECT
  (SELECT u.id FROM users u WHERE u.role='user'
   AND NOT EXISTS (SELECT 1 FROM reviews prior WHERE prior.user_id=u.id AND prior.gym_id=g.id)
   ORDER BY u.id LIMIT 1),
  'gym', g.id, NULL, CASE WHEN g.id % 2=0 THEN 5 ELSE 4 END,
  'Phòng tập sạch sẽ, tiện ích tốt và có HLV hỗ trợ nhiệt tình.',
  'approved', 1, '2026-09-25 10:00:00', NULL, '2026-09-25 10:00:00'
FROM gyms g
WHERE (g.id BETWEEN 1 AND 60 OR g.slug IN ('the-new-gym-thao-dien-61','citygym-an-phu-62','fit24-sala-63','elite-fitness-thu-thiem-64'))
AND NOT EXISTS (SELECT 1 FROM reviews r WHERE r.gym_id=g.id AND r.status='approved')
AND EXISTS (SELECT 1 FROM users u WHERE u.role='user'
  AND NOT EXISTS (SELECT 1 FROM reviews prior WHERE prior.user_id=u.id AND prior.gym_id=g.id));

UPDATE gyms SET
  avg_rating=COALESCE((SELECT ROUND(AVG(rating),1) FROM reviews WHERE reviews.gym_id=gyms.id AND status='approved'),0),
  review_count=(SELECT COUNT(*) FROM reviews WHERE reviews.gym_id=gyms.id AND status='approved')
WHERE id BETWEEN 1 AND 60 OR slug IN ('the-new-gym-thao-dien-61','citygym-an-phu-62','fit24-sala-63','elite-fitness-thu-thiem-64');

-- Normalize the seeded catalog so every district has active gyms in all four
-- price filters. The low tier overlaps the first two filters, the high tier
-- overlaps the latter two. Leave user-created gyms untouched.
UPDATE gyms SET status=CASE WHEN id IN (3,6,9,14,47,55) THEN 'hidden' ELSE 'active' END
WHERE id BETWEEN 1 AND 60;
UPDATE gyms SET price_min=200000, price_max=450000
WHERE id IN (1,4,7,10,12,15,18,21,23,26,29,31,34,37,40,42,45,48,51,53,56,59);
UPDATE gyms SET price_min=700000, price_max=1500000
WHERE id IN (2,5,8,11,13,16,19,22,24,27,30,32,35,38,41,43,46,49,52,54,57,60);
