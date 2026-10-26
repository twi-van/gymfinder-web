# GYMFINDER — Quy định ảnh & validate form (cho team / Lợi)

Tài liệu ngắn để thống nhất seed dữ liệu ảnh và cách báo lỗi form trên UI. Cập nhật: 2026-10-27.

---

## 1. Ảnh phòng tập (Gym)


| Loại       | Trường / bảng          | Số lượng chuẩn                        | Ghi chú                                     |
| ---------- | ---------------------- | ------------------------------------- | ------------------------------------------- |
| Ảnh bìa    | `gyms.cover_image_url` | **1** (bắt buộc với gym demo)         | Hiện ở card danh sách + hero trang chi tiết |
| Gallery    | `gym_images`           | **3–5** / gym (featured có thể **6**) | Lưới 2 cột trên trang chi tiết              |
| Trần admin | —                      | **Tối đa 8** ảnh gallery / gym        | Tránh form/admin quá nặng                   |


**Không** nhồi 10–20 ảnh/gym cho đồ án — screenshot và performance kém lợi.

### Gợi ý file ảnh

- Định dạng: JPEG / PNG / WebP
- Dung lượng: ≤ **5MB** / file (đúng rule upload backend)
- Cover: khoảng 1200×800 (ngang)
- Gallery: cạnh ngắn ~800–1200px
- Avatar PT/user: vuông ~400–512px



### Seed hiện tại (tham chiếu)

- Nhiều gym đã có **cover** nhưng gallery thường chỉ **1** ảnh → nên bổ sung gallery lên 3–5 cho các gym dùng demo / báo cáo.

---



## 2. Ảnh huấn luyện viên (Trainer / PT)


| Loại       | Trường                | Số lượng chuẩn                      |
| ---------- | --------------------- | ----------------------------------- |
| Avatar     | `trainers.avatar_url` | **1**                               |
| Gallery PT | —                     | **Không** (MVP chưa có bảng ảnh PT) |


Đừng mở rộng gallery PT trừ khi đặc tả yêu cầu thêm.

---





