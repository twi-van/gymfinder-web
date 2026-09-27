const gymsData = [
    {
        "id": 1,
        "name": "FitZone Luxury Quận 7",
        "rating": "4.8",
        "reviewCount": "184",
        "address": "12 Nguyễn Thị Thập, P. Tân Hưng, Q.7",
        "district": "Quận 7",
        "price": "350.000 - 650.000đ/tháng",
        "categoryId": 1,
        "description": "FitZone Luxury Quận 7 là không gian tập luyện hiện đại với đầy đủ thiết bị dành cho gym, fitness và strength training. Phòng tập phù hợp cho người mới bắt đầu và người tập luyện chuyên sâu.",
        "facilities": [
            "Điều hòa",
            "Phòng thay đồ",
            "Tủ đồ",
            "Wifi",
            "Bãi giữ xe",
            "Xông hơi khô",
            "Nước uống miễn phí"
        ],
        "openingHours": "06:00 - 22:00",
        "image": "https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=1470&auto=format&fit=crop",
        "images": [
            "https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1540497077202-7c8a3999166f?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1593079831268-3381b0c13c75?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?q=80&w=1470&auto=format&fit=crop"
        ],
        "trainers": [
            {
                "id": 1,
                "name": "Nguyễn Lê Tuấn",
                "role": "Master Trainer",
                "rating": 4.9,
                "avatar": "https://images.unsplash.com/photo-1568602471122-7832951cc4c5?q=80&w=1470&auto=format&fit=crop",
                "exp": "5 năm"
            },
            {
                "id": 2,
                "name": "Trần Minh Huy",
                "role": "Personal Trainer",
                "rating": 4.8,
                "avatar": "https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?q=80&w=1480&auto=format&fit=crop",
                "exp": "7 năm"
            }
        ],
        "reviews": [
            {
                "name": "Nguyễn Văn A",
                "rating": 5,
                "date": "12/09/2026",
                "text": "Phòng tập sạch sẽ, thiết bị đầy đủ. Rất thích không gian ở đây."
            },
            {
                "name": "Trần Minh B",
                "rating": 4,
                "date": "05/09/2026",
                "text": "Không gian rộng, nhân viên thân thiện. Giá cả hợp lý."
            }
        ],
        "badge": "<span class=\"gym-card-tag\"><i class=\"fa-solid fa-circle-check text-primary\"></i> Xác minh</span>",
        "tags": [
            "Mở 24/7",
            "Tạ tự do",
            "Xông hơi khô"
        ],
        "distance": "1.2 km"
    },
    {
        "id": 2,
        "name": "Saigon Barbell Club",
        "rating": "4.9",
        "reviewCount": "240",
        "address": "48 Điện Biên Phủ, P.25, Bình Thạnh",
        "district": "Bình Thạnh",
        "price": "400.000 - 800.000đ/tháng",
        "categoryId": 5,
        "description": "Saigon Barbell Club là không gian tập luyện hiện đại với đầy đủ thiết bị dành cho gym, fitness và strength training. Phòng tập phù hợp cho người mới bắt đầu và người tập luyện chuyên sâu.",
        "facilities": [
            "Điều hòa",
            "Phòng thay đồ",
            "Tủ đồ",
            "Wifi",
            "Bãi giữ xe",
            "Điều hòa",
            "Nước uống miễn phí"
        ],
        "openingHours": "06:00 - 22:00",
        "image": "https://images.unsplash.com/photo-1540497077202-7c8a3999166f?q=80&w=1470&auto=format&fit=crop",
        "images": [
            "https://images.unsplash.com/photo-1540497077202-7c8a3999166f?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1540497077202-7c8a3999166f?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1593079831268-3381b0c13c75?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?q=80&w=1470&auto=format&fit=crop"
        ],
        "trainers": [
            {
                "id": 1,
                "name": "Nguyễn Lê Tuấn",
                "role": "Master Trainer",
                "rating": 4.9,
                "avatar": "https://images.unsplash.com/photo-1568602471122-7832951cc4c5?q=80&w=1470&auto=format&fit=crop",
                "exp": "5 năm"
            },
            {
                "id": 2,
                "name": "Trần Minh Huy",
                "role": "Personal Trainer",
                "rating": 4.8,
                "avatar": "https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?q=80&w=1480&auto=format&fit=crop",
                "exp": "7 năm"
            }
        ],
        "reviews": [
            {
                "name": "Nguyễn Văn A",
                "rating": 5,
                "date": "12/09/2026",
                "text": "Phòng tập sạch sẽ, thiết bị đầy đủ. Rất thích không gian ở đây."
            },
            {
                "name": "Trần Minh B",
                "rating": 4,
                "date": "05/09/2026",
                "text": "Không gian rộng, nhân viên thân thiện. Giá cả hợp lý."
            }
        ],
        "badge": "<span class=\"gym-card-tag\"><i class=\"fa-solid fa-dumbbell text-secondary\"></i> Chuyên sâu</span>",
        "tags": [
            "Tạ Eleiko chuẩn",
            "Dùng phấn tập",
            "Điều hòa"
        ],
        "distance": "2.5 km"
    },
    {
        "id": 3,
        "name": "Zenith Yoga & Pilates",
        "rating": "4.9",
        "reviewCount": "112",
        "address": "88 Lê Lợi, P. Bến Nghé, Quận 1",
        "district": "Quận 1",
        "price": "600.000 - 1.200.000đ/tháng",
        "categoryId": 3,
        "description": "Zenith Yoga & Pilates là không gian tập luyện hiện đại với đầy đủ thiết bị dành cho gym, fitness và strength training. Phòng tập phù hợp cho người mới bắt đầu và người tập luyện chuyên sâu.",
        "facilities": [
            "Điều hòa",
            "Phòng thay đồ",
            "Tủ đồ",
            "Wifi",
            "Bãi giữ xe",
            "Thảm cá nhân",
            "Nước uống miễn phí"
        ],
        "openingHours": "06:00 - 22:00",
        "image": "https://images.unsplash.com/photo-1599901860904-17e6ed7083a0?q=80&w=1470&auto=format&fit=crop",
        "images": [
            "https://images.unsplash.com/photo-1599901860904-17e6ed7083a0?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1540497077202-7c8a3999166f?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1593079831268-3381b0c13c75?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?q=80&w=1470&auto=format&fit=crop"
        ],
        "trainers": [
            {
                "id": 1,
                "name": "Nguyễn Lê Tuấn",
                "role": "Master Trainer",
                "rating": 4.9,
                "avatar": "https://images.unsplash.com/photo-1568602471122-7832951cc4c5?q=80&w=1470&auto=format&fit=crop",
                "exp": "5 năm"
            },
            {
                "id": 2,
                "name": "Trần Minh Huy",
                "role": "Personal Trainer",
                "rating": 4.8,
                "avatar": "https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?q=80&w=1480&auto=format&fit=crop",
                "exp": "7 năm"
            }
        ],
        "reviews": [
            {
                "name": "Nguyễn Văn A",
                "rating": 5,
                "date": "12/09/2026",
                "text": "Phòng tập sạch sẽ, thiết bị đầy đủ. Rất thích không gian ở đây."
            },
            {
                "name": "Trần Minh B",
                "rating": 4,
                "date": "05/09/2026",
                "text": "Không gian rộng, nhân viên thân thiện. Giá cả hợp lý."
            }
        ],
        "badge": "<span class=\"gym-card-tag\"><i class=\"fa-solid fa-spa text-secondary\"></i> Boutique</span>",
        "tags": [
            "Máy Reformer",
            "Phòng tắm nóng",
            "Thảm cá nhân"
        ],
        "distance": "0.8 km"
    },
    {
        "id": 4,
        "name": "Iron Temple Hardcore",
        "rating": "4.7",
        "reviewCount": "195",
        "address": "102 Võ Văn Ngân, TP. Thủ Đức",
        "district": "Thủ Đức",
        "price": "300.000 - 550.000đ/tháng",
        "categoryId": 5,
        "description": "Iron Temple Hardcore là không gian tập luyện hiện đại với đầy đủ thiết bị dành cho gym, fitness và strength training. Phòng tập phù hợp cho người mới bắt đầu và người tập luyện chuyên sâu.",
        "facilities": [
            "Điều hòa",
            "Phòng thay đồ",
            "Tủ đồ",
            "Wifi",
            "Bãi giữ xe",
            "Powerlifting",
            "Nước uống miễn phí"
        ],
        "openingHours": "06:00 - 22:00",
        "image": "https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?q=80&w=1470&auto=format&fit=crop",
        "images": [
            "https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1540497077202-7c8a3999166f?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1593079831268-3381b0c13c75?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?q=80&w=1470&auto=format&fit=crop"
        ],
        "trainers": [
            {
                "id": 1,
                "name": "Nguyễn Lê Tuấn",
                "role": "Master Trainer",
                "rating": 4.9,
                "avatar": "https://images.unsplash.com/photo-1568602471122-7832951cc4c5?q=80&w=1470&auto=format&fit=crop",
                "exp": "5 năm"
            },
            {
                "id": 2,
                "name": "Trần Minh Huy",
                "role": "Personal Trainer",
                "rating": 4.8,
                "avatar": "https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?q=80&w=1480&auto=format&fit=crop",
                "exp": "7 năm"
            }
        ],
        "reviews": [
            {
                "name": "Nguyễn Văn A",
                "rating": 5,
                "date": "12/09/2026",
                "text": "Phòng tập sạch sẽ, thiết bị đầy đủ. Rất thích không gian ở đây."
            },
            {
                "name": "Trần Minh B",
                "rating": 4,
                "date": "05/09/2026",
                "text": "Không gian rộng, nhân viên thân thiện. Giá cả hợp lý."
            }
        ],
        "badge": "<span class=\"gym-card-tag\"><i class=\"fa-solid fa-graduation-cap text-secondary\"></i> Giá sinh viên</span>",
        "tags": [
            "Giữ xe free",
            "Mở 24/7",
            "Powerlifting"
        ],
        "distance": "3.4 km"
    },
    {
        "id": 5,
        "name": "The New Gym Tân Bình",
        "rating": "4.6",
        "reviewCount": "310",
        "address": "32 Cộng Hòa, P.4, Q. Tân Bình",
        "district": "Tân Bình",
        "price": "299.000 - 499.000đ/tháng",
        "categoryId": 6,
        "description": "The New Gym Tân Bình là không gian tập luyện hiện đại với đầy đủ thiết bị dành cho gym, fitness và strength training. Phòng tập phù hợp cho người mới bắt đầu và người tập luyện chuyên sâu.",
        "facilities": [
            "Điều hòa",
            "Phòng thay đồ",
            "Tủ đồ",
            "Wifi",
            "Bãi giữ xe",
            "LifeFitness",
            "Nước uống miễn phí"
        ],
        "openingHours": "06:00 - 22:00",
        "image": "https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=1470&auto=format&fit=crop",
        "images": [
            "https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1540497077202-7c8a3999166f?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1593079831268-3381b0c13c75?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?q=80&w=1470&auto=format&fit=crop"
        ],
        "trainers": [
            {
                "id": 1,
                "name": "Nguyễn Lê Tuấn",
                "role": "Master Trainer",
                "rating": 4.9,
                "avatar": "https://images.unsplash.com/photo-1568602471122-7832951cc4c5?q=80&w=1470&auto=format&fit=crop",
                "exp": "5 năm"
            },
            {
                "id": 2,
                "name": "Trần Minh Huy",
                "role": "Personal Trainer",
                "rating": 4.8,
                "avatar": "https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?q=80&w=1480&auto=format&fit=crop",
                "exp": "7 năm"
            }
        ],
        "reviews": [
            {
                "name": "Nguyễn Văn A",
                "rating": 5,
                "date": "12/09/2026",
                "text": "Phòng tập sạch sẽ, thiết bị đầy đủ. Rất thích không gian ở đây."
            },
            {
                "name": "Trần Minh B",
                "rating": 4,
                "date": "05/09/2026",
                "text": "Không gian rộng, nhân viên thân thiện. Giá cả hợp lý."
            }
        ],
        "badge": "<span class=\"gym-card-tag\"><i class=\"fa-solid fa-clock text-secondary\"></i> 24/7 Chuẩn</span>",
        "tags": [
            "Mở cửa 24/7",
            "Không cam kết",
            "LifeFitness"
        ],
        "distance": "4.1 km"
    },
    {
        "id": 6,
        "name": "Aura Fitness & Wellness",
        "rating": "4.8",
        "reviewCount": "145",
        "address": "18 Võ Thị Sáu, P. Đa Kao, Quận 3",
        "district": "Quận 3",
        "price": "800.000 - 1.500.000đ/tháng",
        "categoryId": 3,
        "description": "Aura Fitness & Wellness là không gian tập luyện hiện đại với đầy đủ thiết bị dành cho gym, fitness và strength training. Phòng tập phù hợp cho người mới bắt đầu và người tập luyện chuyên sâu.",
        "facilities": [
            "Điều hòa",
            "Phòng thay đồ",
            "Tủ đồ",
            "Wifi",
            "Bãi giữ xe",
            "HLV 1-1",
            "Nước uống miễn phí"
        ],
        "openingHours": "06:00 - 22:00",
        "image": "https://images.unsplash.com/photo-1540497077202-7c8a3999166f?q=80&w=1470&auto=format&fit=crop",
        "images": [
            "https://images.unsplash.com/photo-1540497077202-7c8a3999166f?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1540497077202-7c8a3999166f?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1593079831268-3381b0c13c75?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?q=80&w=1470&auto=format&fit=crop"
        ],
        "trainers": [
            {
                "id": 1,
                "name": "Nguyễn Lê Tuấn",
                "role": "Master Trainer",
                "rating": 4.9,
                "avatar": "https://images.unsplash.com/photo-1568602471122-7832951cc4c5?q=80&w=1470&auto=format&fit=crop",
                "exp": "5 năm"
            },
            {
                "id": 2,
                "name": "Trần Minh Huy",
                "role": "Personal Trainer",
                "rating": 4.8,
                "avatar": "https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?q=80&w=1480&auto=format&fit=crop",
                "exp": "7 năm"
            }
        ],
        "reviews": [
            {
                "name": "Nguyễn Văn A",
                "rating": 5,
                "date": "12/09/2026",
                "text": "Phòng tập sạch sẽ, thiết bị đầy đủ. Rất thích không gian ở đây."
            },
            {
                "name": "Trần Minh B",
                "rating": 4,
                "date": "05/09/2026",
                "text": "Không gian rộng, nhân viên thân thiện. Giá cả hợp lý."
            }
        ],
        "badge": "<span class=\"gym-card-tag\"><i class=\"fa-solid fa-star text-warning\"></i> 5 Sao</span>",
        "tags": [
            "Hồ bơi trần",
            "Xông hơi thảo dược",
            "HLV 1-1"
        ],
        "distance": "1.5 km"
    },
    {
        "id": 7,
        "name": "Vietnam Fight Club",
        "rating": "4.9",
        "reviewCount": "88",
        "address": "25 Hoàng Sa, P. Đa Kao, Quận 1",
        "district": "Quận 1",
        "price": "500.000 - 900.000đ/tháng",
        "categoryId": 2,
        "description": "Vietnam Fight Club là không gian tập luyện hiện đại với đầy đủ thiết bị dành cho gym, fitness và strength training. Phòng tập phù hợp cho người mới bắt đầu và người tập luyện chuyên sâu.",
        "facilities": [
            "Điều hòa",
            "Phòng thay đồ",
            "Tủ đồ",
            "Wifi",
            "Bãi giữ xe",
            "HLV Boxing",
            "Nước uống miễn phí"
        ],
        "openingHours": "06:00 - 22:00",
        "image": "https://images.unsplash.com/photo-1599901860904-17e6ed7083a0?q=80&w=1470&auto=format&fit=crop",
        "images": [
            "https://images.unsplash.com/photo-1599901860904-17e6ed7083a0?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1540497077202-7c8a3999166f?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1593079831268-3381b0c13c75?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?q=80&w=1470&auto=format&fit=crop"
        ],
        "trainers": [
            {
                "id": 1,
                "name": "Nguyễn Lê Tuấn",
                "role": "Master Trainer",
                "rating": 4.9,
                "avatar": "https://images.unsplash.com/photo-1568602471122-7832951cc4c5?q=80&w=1470&auto=format&fit=crop",
                "exp": "5 năm"
            },
            {
                "id": 2,
                "name": "Trần Minh Huy",
                "role": "Personal Trainer",
                "rating": 4.8,
                "avatar": "https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?q=80&w=1480&auto=format&fit=crop",
                "exp": "7 năm"
            }
        ],
        "reviews": [
            {
                "name": "Nguyễn Văn A",
                "rating": 5,
                "date": "12/09/2026",
                "text": "Phòng tập sạch sẽ, thiết bị đầy đủ. Rất thích không gian ở đây."
            },
            {
                "name": "Trần Minh B",
                "rating": 4,
                "date": "05/09/2026",
                "text": "Không gian rộng, nhân viên thân thiện. Giá cả hợp lý."
            }
        ],
        "badge": "<span class=\"gym-card-tag\"><i class=\"fa-solid fa-hand-fist text-secondary\"></i> Đối kháng</span>",
        "tags": [
            "Lồng bát giác",
            "Găng & bao cát",
            "HLV Boxing"
        ],
        "distance": "1.0 km"
    },
    {
        "id": 8,
        "name": "Spartan Strength Box",
        "rating": "4.8",
        "reviewCount": "160",
        "address": "15 Ung Văn Khiêm, Bình Thạnh",
        "district": "Bình Thạnh",
        "price": "450.000 - 750.000đ/tháng",
        "categoryId": 4,
        "description": "Spartan Strength Box là không gian tập luyện hiện đại với đầy đủ thiết bị dành cho gym, fitness và strength training. Phòng tập phù hợp cho người mới bắt đầu và người tập luyện chuyên sâu.",
        "facilities": [
            "Điều hòa",
            "Phòng thay đồ",
            "Tủ đồ",
            "Wifi",
            "Bãi giữ xe",
            "Dumbbell Rogue",
            "Nước uống miễn phí"
        ],
        "openingHours": "06:00 - 22:00",
        "image": "https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?q=80&w=1470&auto=format&fit=crop",
        "images": [
            "https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1540497077202-7c8a3999166f?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1593079831268-3381b0c13c75?q=80&w=1470&auto=format&fit=crop",
            "https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?q=80&w=1470&auto=format&fit=crop"
        ],
        "trainers": [
            {
                "id": 1,
                "name": "Nguyễn Lê Tuấn",
                "role": "Master Trainer",
                "rating": 4.9,
                "avatar": "https://images.unsplash.com/photo-1568602471122-7832951cc4c5?q=80&w=1470&auto=format&fit=crop",
                "exp": "5 năm"
            },
            {
                "id": 2,
                "name": "Trần Minh Huy",
                "role": "Personal Trainer",
                "rating": 4.8,
                "avatar": "https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?q=80&w=1480&auto=format&fit=crop",
                "exp": "7 năm"
            }
        ],
        "reviews": [
            {
                "name": "Nguyễn Văn A",
                "rating": 5,
                "date": "12/09/2026",
                "text": "Phòng tập sạch sẽ, thiết bị đầy đủ. Rất thích không gian ở đây."
            },
            {
                "name": "Trần Minh B",
                "rating": 4,
                "date": "05/09/2026",
                "text": "Không gian rộng, nhân viên thân thiện. Giá cả hợp lý."
            }
        ],
        "badge": "<span class=\"gym-card-tag\"><i class=\"fa-solid fa-person-walking text-secondary\"></i> CrossFit</span>",
        "tags": [
            "CrossFit chuẩn",
            "Khung xà kép",
            "Dumbbell Rogue"
        ],
        "distance": "2.8 km"
    }
];