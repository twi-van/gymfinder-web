# KỊCH BẢN THUYẾT TRÌNH DEMO — GYMFINDER (CHI TIẾT)

**Đề tài:** Hệ thống tìm kiếm và đánh giá phòng tập & huấn luyện viên cá nhân tại TP. Hồ Chí Minh  
**Nhóm:** Phan Phạm Thùy Vân (52400254) · Nguyễn Ngọc Kim Lợi (52400211)  
**Demo:** [https://gymfinder-wed.up.railway.app](https://gymfinder-wed.up.railway.app)  

---

## Cách dùng file

- **Vân** đảm nhận: **mở đầu + bài toán + mục tiêu + phân công + công nghệ/kiến trúc/CSDL (nói)** + phần lớn demo Guest/User/AI/VI/EN + kết luận mở.  
- **Lợi** đảm nhận: Auth sâu (Google/OTP/bảo mật) + **toàn bộ Demo Admin** + hướng phát triển kỹ thuật.  
- Mỗi mục có: **Thao tác** → **Lời nói (đọc)** → **Ý mình đang nói** (hiểu / trả lời hỏi).  
- Demo online: **một chiều**, ít đổi phiên (xem mục Nguyên tắc).

---

## Nguyên tắc demo online (bắt buộc)

```text
Guest (hết) → login User 1 lần → User (hết) → logout → login Admin 1 lần → Admin (hết) → kết
```

- Chỉ **1 tab** trình duyệt.  
- Đổi tài khoản tối đa **2 lần**.  
- OTP/Google: ưu tiên trong khối Auth; **không** logout giữa chừng để quay lại lọc gym.


| Khối                                       | Cầm chuột                               | Nói chính           |
| ------------------------------------------ | --------------------------------------- | ------------------- |
| A–C mở đầu + Guest + VI/EN + tìm kiếm + AI | **Vân**                                 | **Vân**             |
| D Auth                                     | **Lợi** (hoặc Vân login hộ nếu Lợi nói) | **Lợi**             |
| E User                                     | **Vân**                                 | **Vân**             |
| F Admin                                    | **Lợi**                                 | **Lợi**             |
| G Kết luận                                 | —                                       | **Vân** rồi **Lợi** |


**Tài khoản sẵn:**


| Thứ tự | Vai trò | Gợi ý                                                |
| ------ | ------- | ---------------------------------------------------- |
| 1      | Guest   | Không login                                          |
| 2      | User    | `u02@gymfinder.test` / `password` hoặc user Railway  |
| 3      | Admin   | `u01@gymfinder.test` / `password` hoặc admin Railway |


---

## Checklist đủ nội dung thuyết trình

Dùng để đối chiếu trước giờ — **không bỏ mục có dấu ★**:


| #   | Nội dung                                                          | Ai     | Khối |
| --- | ----------------------------------------------------------------- | ------ | ---- |
| ★1  | Chào hỏi, tên đề tài, thành viên, MSSV                            | Vân    | A    |
| ★2  | Bài toán / nhu cầu thực tế                                        | Vân    | A    |
| ★3  | Mục tiêu đồ án                                                    | Vân    | A    |
| ★4  | Phạm vi MVP + ngoài phạm vi                                       | Vân    | A    |
| ★5  | Ba vai trò Guest / User / Admin                                   | Vân    | A    |
| ★6  | Phân công nhóm Vân–Lợi                                            | Vân    | A    |
| ★7  | Công nghệ (PHP SSR, Bootstrap, MySQL, API, AI, deploy)            | Vân    | B    |
| ★8  | Kiến trúc tổng quan (User Portal / Admin Portal)                  | Vân    | B    |
| ★9  | CSDL: nhóm bảng, N–N, đặc điểm, review, OTP                       | Vân    | B    |
| ★10 | Trang chủ                                                         | Vân    | C    |
| ★11 | Đa ngôn ngữ VI/EN                                                 | Vân    | C    |
| ★12 | Tìm/lọc/sắp xếp gym + toast lọc trống                             | Vân    | C    |
| ★13 | Chi tiết gym (ảnh, giá, đặc điểm, tiện ích, HLV, review, liên hệ) | Vân    | C    |
| ★14 | Tìm/chi tiết PT                                                   | Vân    | C    |
| ★15 | Soft-gate yêu thích/hồ sơ                                         | Vân    | C    |
| ★16 | Trợ lý AI (Groq, grounding catalog)                               | Vân    | C    |
| ★17 | Đăng ký / đăng nhập / remember-me                                 | Lợi    | D    |
| ★18 | Google OAuth + PKCE (demo hoặc nói)                               | Lợi    | D    |
| ★19 | Quên mật khẩu OTP 3 bước (demo hoặc nói)                          | Lợi    | D    |
| ★20 | Bảo mật CSRF, XSS, Bcrypt, upload, phân quyền                     | Lợi    | D    |
| ★21 | Yêu thích (User)                                                  | Vân    | E    |
| ★22 | Đánh giá pending → chờ Admin                                      | Vân    | E    |
| ★23 | Hồ sơ + đăng xuất có modal                                        | Vân    | E    |
| ★24 | Admin dashboard + portal-only                                     | Lợi    | F    |
| ★25 | Quản lý gym/PT + soft-hide                                        | Lợi    | F    |
| ★26 | Danh mục / tab Đặc điểm end-to-end                                | Lợi    | F    |
| ★27 | Duyệt / từ chối đánh giá                                          | Lợi    | F    |
| ★28 | Khóa user + mời Admin (MK tạm 5 phút)                             | Lợi    | F    |
| ★29 | Kết luận đạt được                                                 | Vân    | G    |
| ★30 | Hướng phát triển                                                  | Lợi    | G    |
| ★31 | Cảm ơn + sẵn sàng Q&A                                             | Cả hai | G    |


---

## Khung thời gian (~18–22 phút)


| Phút  | Khối    | Nội dung                                       | Ai            |
| ----- | ------- | ---------------------------------------------- | ------------- |
| 0–4   | **A+B** | Mở đầu + công nghệ + CSDL (nói, web Guest sẵn) | **Vân**       |
| 4–9   | **C**   | Demo Guest đủ                                  | **Vân**       |
| 9–12  | **D**   | Auth + bảo mật                                 | **Lợi**       |
| 12–15 | **E**   | Demo User đủ                                   | **Vân**       |
| 15–20 | **F**   | Demo Admin đủ                                  | **Lợi**       |
| 20–22 | **G**   | Kết luận                                       | **Vân + Lợi** |


---

# KHỐI A — MỞ ĐẦU (VÂN ĐẢM NHẬN)

> Trước giờ: mở 1 tab Railway trang chủ, **chưa đăng nhập**.

## A1. Chào hỏi & giới thiệu đề tài

### Thao tác

- Chỉ logo **GYMFINDER** trên trang chủ.

### Lời nói

> Em chào thầy/cô và hội đồng.  
> Em là **Phan Phạm Thùy Vân**, MSSV **52400254**. Bên cạnh em là bạn **Nguyễn Ngọc Kim Lợi**, MSSV **52400211**.  
> Nhóm em thực hiện đề tài **GYMFINDER** — hệ thống tìm kiếm và đánh giá phòng tập thể hình cùng huấn luyện viên cá nhân tại **Thành phố Hồ Chí Minh**.  
> Đây là bản demo đang chạy online trên Railway. Em sẽ mở đầu phần giới thiệu và công nghệ; sau đó nhóm demo lần lượt theo vai trò Guest, User rồi Admin để tránh phải đăng nhập–đăng xuất liên tục.

### Ý mình đang nói

- Nêu rõ tên + MSSV + tên hệ thống + địa bàn.  
- Báo trước **trình tự một chiều** = chuyên nghiệp, chủ động.

## A2. Bài toán thực tế

### Lời nói

> Hiện nay thông tin phòng tập và PT ở TP.HCM đang **phân tán**: fanpage, website riêng, nhóm review rời rạc.  
> Người dùng muốn so sánh **vị trí, mức giá, tiện ích, đặc điểm phòng tập, chất lượng đánh giá** thì phải tự đối chiếu nhiều nguồn điều này gây mất thời gian và dễ thiếu thông [tin.Do](http://tin.Do) đó nhóm em quyết định tạo ra 1 nền tảng GYMFINDER giải quyết bài toán đó bằng cách **gom thông tin**, cho phép **tra cứu – lọc – xem chi tiết – lưu yêu thích – đánh giá có kiểm duyệt**, và hỗ trợ thêm **Trợ lý AI** gợi ý theo dữ liệu thật trên hệ thống.

## A3. Mục tiêu đồ án

### Lời nói

> Mục tiêu cụ thể gồm năm nhóm:  
> **Một**, xây website tra cứu gym và PT: trang chủ, danh sách, trang chi tiết đầy đủ mô tả, ảnh, giá, đặc điểm, tiện ích, đánh giá đã duyệt và thông tin liên hệ công bố.  
> **Hai**, hỗ trợ tìm kiếm, lọc, sắp xếp theo tiêu chí thực tế: từ khóa, quận huyện, khoảng giá, điểm đánh giá, loại hình, đặc điểm, tiện ích, chuyên môn.  
> **Ba**, trải nghiệm thành viên gồm hồ sơ cá nhân, yêu thích, gửi–sửa–xóa đánh giá; đánh giá mới phải **.**  
> **Bốn**, Trợ lý AI gợi ý gym/PT bám catalog đang hoạt động.  
> **Năm**, cổng Admin quản trị để quản lý dữ liệu, danh mục, kiểm duyệt nội dung và quản lý người dùng.

## A4. Phạm vi của đồ án và ngoài phạm vi

### Lời nói

> Trong phạm vi MVP, hệ thống là nền tảng **tra cứu và đánh giá**. Khi người dùng muốn đăng ký tập, họ **tự liên hệ** phòng tập hoặc PT qua số điện thoại được công bố trên trang chi tiết — có modal hỗ trợ gọi hoặc sao chép SĐT.  
> Nhóm **không** triển khai trong đồ án này: bản đồ Google Maps chỉ đường, đặt lịch online, thanh toán hoặc ví điện tử, chat realtime giữa user và PT.(đây là hướng pt tương lai)

## A5. vai trò của hệ thống

### Thao tác

- Chỉ nhanh navbar: Đăng nhập / Đăng ký (Guest); sau này sẽ thấy Yêu thích, Hồ sơ, Admin.

### Lời nói

> Hệ thống phân ba vai trò.  
> **Guest** — khách chưa đăng nhập: xem trang chủ, tìm kiếm, xem chi tiết, đọc đánh giá đã duyệt, hỏi Trợ lý AI. Khi vào yêu thích hoặc hồ sơ, hệ thống dùng **soft-gate**: hiện lời mời đăng nhập/đăng ký.  
> **User** — thành viên đã đăng nhập: thêm quyền lưu yêu thích, quản lý hồ sơ, gửi đánh giá, lưu phiên chat AI.   
> **Admin** — làm việc trong cổng `/admin`: dashboard, quản lý gym/PT, danh mục, duyệt đánh giá, khóa user, mời Admin mới. 



## A6. Phân công nhóm

### Lời nói

> Về phân công: em **Vân** phụ trách điều phối dự án, phân tích UML phía người dùng và AI, hiện thực **Frontend** User Portal, **Trợ lý AI**, và **đa ngôn ngữ VI/EN**;  
> Bạn **Lợi** phụ trách **Backend, API, cơ sở dữ liệu, bảo mật**, đăng nhập **Google OAuth**, luồng **OTP**, và toàn bộ **cổng quản trị Admin**.  
> Hai thành viên làm trên cùng repository GitHub và cùng bản deploy Railway.

---

# KHỐI B — CÔNG NGHỆ & KIẾN TRÚC & CSDL (VÂN ĐẢM NHẬN)

## B1. Công nghệ sử dụng

### Lời nói

> Về công nghệ, nhóm chọn stack phù hợp đồ án web và môi trường lab:  
>
> - **Giao diện:** PHP render phía server — **SSR**, HTML5, CSS3, **Bootstrap 5.3**, JavaScript, Font Awesome.  
> - **Backend:** PHP 8 trở lên, **thuần**, không bắt buộc Composer, dễ chạy trên XAMPP.  
> - **Cơ sở dữ liệu mặc định:** **MySQL**; SQLite chỉ còn là tuỳ chọn  
> - **API:** cổng REST trong thư mục `api/` phục vụ favorites, reviews, Trợ lý AI và một số thao tác JSON.  
> - **AI:** gọi **Groq** Chat Completions, cấu hình bằng biến `GF_AI_`* trong file môi trường.  
> - **Triển khai:** Git/GitHub; local bằng `php -S` với `router.php` hoặc XAMPP; bản demo online bằng **Docker trên Railway**.  
> Mọi cấu hình nhạy cảm — database, SMTP, Google, API key AI — đặt trong `**.env` / Variables**

## B2. Kiến trúc tổng quan

### Lời nói

> Kiến trúc chia hai cổng:  
> **User Portal** trong `pages/` — trang chủ, auth, gym, PT, yêu thích, hồ sơ, AI, trang pháp lý.  
> **Admin Portal** trong `admin/` — dashboard và các trang quản trị.  
> Mọi request đều qua lớp khởi tạo `backend/bootstrap.php`: bật session an toàn, áp dụng ngôn ngữ VI/EN, nạp cấu hình, kết nối PDO, kiểm tra CSRF với form POST, và chặn phân quyền Admin khi cần.  
> Tầng dưới có **repositories** truy vấn CSDL và **resources** xử lý nghiệp vụ; mailer gửi OTP hoặc thư mời Admin; `google_auth` và `ai_client` gọi dịch vụ ngoài.

## B3. Cơ sở dữ liệu (đủ ý, không cần mở ERD trừ khi hỏi)

### Lời nói

> Về CSDL, nhóm thiết kế quan hệ và chia **bốn nhóm thực thể**:  
> **Một — danh mục nền:** quận/huyện (`districts`), loại hình (`categories`), **đặc điểm phòng tập** (`features`), tiện ích (`amenities`), chuyên môn HLV (`specialties`).  
> **Hai — thực thể lõi và liên kết:** `users`, `gyms`, `trainers`, cùng bảng N–N như `gym_features`, `gym_amenities`, `gym_categories`, liên kết trainer–gym.  
> **Ba — tương tác người dùng:** `reviews` có trạng thái chờ duyệt / đã duyệt / từ chối; `favorites`; `remember_tokens`; bảng OTP quên mật khẩu.  
> **Bốn — quy tắc nghiệp vụ trong dữ liệu:** gym/PT có thể **soft-hide** bằng trạng thái thay vì xóa cứng trên UI; user có thể bị **khóa**; đánh giá chỉ công khai khi `approved`.  
> Em lưu ý thêm: **đặc điểm** khác **tiện ích**. Đặc điểm mô tả tính chất vận hành — ví dụ mở cửa 24/7, có PT, hỗ trợ giảm cân. Tiện ích là cơ sở vật chất — Wifi, tủ khóa, điều hòa. Cả hai đều do Admin quản trong trang Danh mục và gắn vào từng phòng tập.

### Ý mình đang nói

- 4 nhóm + phân biệt đặc điểm/tiện ích = điểm “hiểu domain”.  
- Soft-hide / duyệt review gắn với demo sau — gieo thuật ngữ sớm.

## B4. Chuyển tiếp sang demo

### Lời nói

> Tiếp theo em demo trực tiếp trên hệ thống, bắt đầu với vai trò **Guest** — chưa đăng nhập — để thấy các chức năng công khai: trang chủ, đổi ngôn ngữ, tìm kiếm, chi tiết, soft-gate và Trợ lý AI.

### Ý mình đang nói

- Câu chuyển mượt → khối C. Không cần Lợi xen vào trừ khi thầy hỏi sâu DB ngay.

---

# KHỐI C — DEMO GUEST (VÂN)

## C1. Trang chủ

### Thao tác

- Chỉ hero, ô tìm nhanh, khối gym/HLV nổi bật, footer nếu cần.

### Lời nói

> Đây là trang chủ User Portal: giới thiệu sản phẩm, lối tắt tìm phòng tập và huấn luyện viên, đồng thời nổi bật các cơ sở và PT được đánh giá cao trong dữ liệu mẫu.

## C2. Đa ngôn ngữ VI/EN ★

### Lời nói

> Hệ thống hỗ trợ **hai ngôn ngữ giao diện**. Nút VI/EN lưu lựa chọn bằng cookie và session, **không ghi vào CSDL**.  
> Trong code, chuỗi giao diện được bọc hàm `gf_t`. Khi chọn English, hệ thống tra từ điển tĩnh trong `lang/en.php` và `lang/en_pages.php`.  
> Ngược lại, tên phòng tập, tên quận, nội dung Admin nhập vẫn giữ tiếng Việt vì đó là **dữ liệu nghiệp vụ**, không phải nhãn UI.

## C3. Tìm / lọc phòng tập 

### Thao tác

1. Vào **Phòng tập**.
2. Chọn 1–2 lọc thật (quận, giá, đặc điểm…).
3. (Nếu kịp) bấm lọc khi chưa chọn gì → toast.
4. Chỉ kết quả card.

### Lời nói

> Trang tìm kiếm cho phép lọc theo khu vực, khoảng giá, mức đánh giá, loại hình, **đặc điểm**, tiện ích và sắp xếp kết quả.  
> Nếu người dùng bấm áp dụng lọc khi **chưa nhập từ khóa và chưa chọn tiêu chí nào**, giao diện hiện **toast cảnh báo** và **không gửi form** .  
> Kết quả chỉ lấy các phòng đang **active**, đúng dữ liệu công khai.

## C4. Chi tiết phòng tập ★

### Lời nói

> Trang chi tiết hiển thị đầy đủ thông tin để so sánh: hình ảnh, mức giá tham khảo, giờ hoạt động, đặc điểm và tiện ích đã gắn, danh sách HLV tại phòng, các đánh giá **đã được Admin duyệt**, và thông tin liên hệ.  
> Nút liên hệ mở modal hỗ trợ gọi hoặc sao chép số điện thoại — người dùng tự thỏa thuận ngoài hệ thống, đúng phạm vi MVP.

## C5. Huấn luyện viên ★

### Thao tác

- **Huấn luyện viên** → mở 1 PT → chỉ chuyên môn, phòng gắn, đánh giá, liên hệ.

### Lời nói

> Luồng PT tương tự gym: tìm–lọc–chi tiết. Một huấn luyện viên có thể gắn với **nhiều phòng tập** nhờ quan hệ nhiều–nhiều, sát thực tế PT dạy nhiều nơi.

## C6. Soft-gate yêu thích ★

### Thao tác

- Bấm **Yêu thích** (vẫn Guest) → chỉ CTA đăng nhập.  
- Quay lại (Back) để sang AI — **chưa login**.

### Lời nói

> Khi Guest vào yêu thích hoặc hồ sơ, hệ thống không redirect cứng mà hiện **soft-gate**: giải thích cần đăng nhập và đưa nút Đăng nhập / Đăng ký. Như vậy khách vẫn hiểu tính năng trước khi tạo tài khoản.

## C7. Trợ lý AI (Guest) ★

### Thao tác

- **Trợ lý AI** → gửi **một** câu, ví dụ:  
`Gợi ý phòng tập ở Quận 7 giá dưới 500 nghìn có điều hòa`  
- Đợi trả lời → chỉ tên/gợi ý bám dữ liệu.

### Lời nói

> Trợ lý AI dùng mô hình qua **Groq**. Phía backend lấy ngữ cảnh từ **catalog gym và PT đang active**, rồi mới gọi LLM — gọi là **grounding**, giúp câu trả lời bám dữ liệu hệ thống thay vì bịa phòng không tồn tại.  
> Guest hỏi được ngay; User đăng nhập sẽ lưu thêm được phiên chat. AI **không** thay đặt lịch hay thanh toán.

### Câu chuyển mic

> Em xin chuyển sang bạn Lợi để trình bày phần xác thực tài khoản và bảo mật, sau đó nhóm sẽ đăng nhập User để demo các chức năng thành viên.

---

# KHỐI D — AUTH + BẢO MẬT (LỢI)

> Mục tiêu: vào được phiên **User** và nói đủ ý Auth/bảo mật. Online: **đừng** phá phiên bằng OTP thật nếu vừa login xong.

## D1. Đăng nhập / đăng ký / remember-me ★

### Thao tác

- Login User đã chuẩn bị (ưu tiên). Đăng ký chỉ khi cần tạo user mới.

### Lời nói

> Phân hệ xác thực hỗ trợ đăng ký thành viên và đăng nhập bằng **email, số điện thoại hoặc tên tài khoản**.  
> Mật khẩu lưu bằng **Bcrypt**. Sau đăng nhập thành công, hệ thống **regenerate session id** để chống chiếm phiên.  
> Tùy chọn **Ghi nhớ đăng nhập** phát hành token lưu bảng `remember_tokens` kèm cookie, thời hạn khoảng 30 ngày cho User — đây là remember-me thật, không chỉ lưu form phía trình duyệt.

### Ý mình đang nói

- Đủ ý cho câu hỏi bảo mật mật khẩu + session.

## D2. Google OAuth ★ (demo nếu ổn; không thì nói)

### Thao tác

- Nếu đã login ở D1 → **chỉ nói**, không logout.  
- Nếu chưa login và Google OK → bấm Google một lần vào User.

### Lời nói

> Hệ thống tích hợp **Đăng nhập / Đăng ký Google** theo OAuth 2.0 kèm **PKCE S256**: tạo `state` và cặp PKCE, đổi authorization code lấy thông tin email đã xác minh, rồi tạo hoặc liên kết tài khoản.  
> Client ID/Secret và **redirect URI** phải khớp đúng domain local hoặc Railway.

### Ý mình đang nói

- PKCE = bảo vệ authorization code.  
- Avatar Google có thể cần `referrerpolicy` / fallback — nếu hỏi UI.

## D3. Quên mật khẩu OTP ★ (ưu tiên nói miệng khi online)

### Lời nói

> Quên mật khẩu gồm **ba bước**: nhập email → nhận OTP **6 số** hiệu lực **5 phút**, dùng một lần, lưu **hash** trong bảng OTP → đặt mật khẩu mới. OTP cũ chưa dùng sẽ bị vô hiệu khi gửi lại.  
> Môi trường demo local có thể bật `GF_SMTP_DEMO_OTP` để hiện mã trên UI; môi trường thật dùng SMTP gửi email.

### Ý mình đang nói

- Online đang giữ phiên User: **đừng** logout chỉ để bấm OTP trừ khi thầy yêu cầu.  
- Phân biệt OTP quên MK ≠ mật khẩu tạm mời Admin (Admin sẽ nói ở F).

## D4. Bảo mật tổng hợp ★

### Lời nói

> Ngoài Bcrypt và session, nhóm áp dụng: **CSRF token** trên mọi form POST; escape output bằng `gf_h` chống **XSS**; kiểm soát **upload** ảnh (loại, dung lượng, chỉ lưu dưới `/uploads`); phân quyền Guest/User/Admin rõ; Admin bị giữ trong `/admin`.

### Câu chuyển mic

> Em đã đăng nhập User. Xin chuyển lại bạn Vân demo yêu thích, đánh giá và hồ sơ.

---

# KHỐI E — DEMO USER (VÂN)

## E1. Yêu thích ★

### Thao tác

- Vào gym/PT vừa xem → bấm tim → **Yêu thích**.

### Lời nói

> Sau đăng nhập, soft-gate không còn chặn. User lưu hoặc bỏ yêu thích gym và PT; trang yêu thích quản lý danh sách đã lưu.

## E2. Đánh giá có kiểm duyệt ★

### Thao tác

- Trang chi tiết → gửi đánh giá (sao + nội dung).  
- Chỉ trạng thái **chờ duyệt**.

### Lời nói

> Mỗi thành viên chỉ gửi **một đánh giá** cho mỗi phòng tập hoặc mỗi PT.  
> Đánh giá mới ở trạng thái **pending**: Guest chưa thấy, điểm trung bình chưa tính. Chỉ khi Admin **duyệt** mới công khai. Nếu từ chối, Admin phải nhập lý do.  
> Em cố ý để review này **pending** — bạn Lợi sẽ duyệt trong phần Admin ngay sau đây.

### Ý mình đang nói

- Nối tiếp khối F = mạch chuyện liền.

## E3. Hồ sơ + đăng xuất modal ★

### Thao tác

- Hồ sơ → chỉ thông tin/avatar.  
- Đăng xuất → **modal xác nhận** → Đồng ý.

### Lời nói

> User cập nhật hồ sơ cơ bản và ảnh đại diện trong giới hạn hệ thống.  
> Đăng xuất có **hộp thoại xác nhận** để tránh bấm nhầm, dùng chung cơ chế confirm của giao diện.

### Câu chuyển mic

> Em đã đăng xuất User. Xin mời bạn Lợi đăng nhập Admin và demo cổng quản trị.

---

# KHỐI F — DEMO ADMIN (LỢI)

## F1. Dashboard + portal-only ★

### Thao tác

- Login Admin → dashboard. (Tuỳ chọn: mở URL trang chủ rồi bị giữ/đưa về admin — nếu hành vi portal-only rõ.)

### Lời nói

> Đây là **Dashboard** quản trị: các chỉ số tổng quan vận hành.  
> Admin làm việc trong `/admin` — **portal-only** — tách khỏi thao tác hội viên trên User Portal.

## F2. Quản lý gym / PT + soft-hide ★

### Thao tác

- Phòng tập: mở xem/sửa ngắn **hoặc** nói + chỉ nút tạm ẩn.  
- Không tạo gym dài trên live trừ khi thừa thời gian.

### Lời nói

> Admin thêm, sửa thông tin phòng tập và HLV, gán danh mục–đặc điểm–tiện ích–chuyên môn.  
> Nhóm chọn tạm ẩn (soft-hide) thay vì xóa cứng vì phòng tập và HLV đã gắn nhiều dữ liệu liên quan: đánh giá, yêu thích, liên kết danh mục–đặc điểm–tiện ích, quan hệ HLV–phòng tập.  
>  Nếu xóa cứng bản ghi gym/PT, các liên kết đó dễ mất hoặc lỗi tham chiếu, lịch sử đánh giá của hội viên cũng biến mất — khó kiểm soát và khó khôi phục.  
>  Với soft-hide, Admin chỉ đổi trạng thái (ví dụ ẩn khỏi trang công khai): Guest/User không còn thấy, nhưng bản ghi và quan hệ vẫn còn trong CSDL, sau này bật lại được nếu phòng mở cửa trở lại hoặc ẩn nhầm.  
>  *Việc “dọn hẳn” nếu cần sẽ là quyết định kỹ thuật/DB riêng, không đặt làm thao tác mặc định trên giao diện quản trị.*

## F3. Danh mục — Đặc điểm ★

### Thao tác

- **Danh mục** → tab **Đặc điểm** → chỉ vài dòng catalog.  
- Nối với lọc User và chi tiết gym đã demo.

### Lời nói

> Trang Danh mục quản hợp nhất loại hình, **đặc điểm**, chuyên môn, tiện ích, khu vực.  
> Đặc điểm là catalog động; gắn N–N với gym qua `gym_features`. User lọc và xem chi tiết theo đúng dữ liệu Admin cấu hình — đó là vòng end-to-end nhóm đã hoàn thiện.

## F4. Duyệt đánh giá ★

### Thao tác

- **Đánh giá** → duyệt pending vừa tạo ở E2 (hoặc từ chối có lý do).

### Lời nói

> Hàng đợi kiểm duyệt: **Duyệt** thì review công khai và cập nhật điểm trung bình; **Từ chối** bắt buộc lý do để thành viên hiểu.

## F5. User: khóa & mời Admin ★

### Thao tác

- Chỉ bảng user / khóa nếu kịp.  
- Mời Admin: demo ngắn nếu SMTP ổn; không thì nói đủ quy trình.

### Lời nói

> Với hội viên thường, Admin **khóa hoặc mở khóa**, không xóa cứng để giữ lịch sử.  
> **Mời Admin:** nhập tên tài khoản và email; hệ thống tạo tài khoản role admin, gửi email **mật khẩu tạm hiệu lực 5 phút**, lần đăng nhập đầu **bắt buộc đổi mật khẩu**. Cần cấu hình SMTP. Nếu hết hạn mà chưa đổi mật khẩu, có thể gửi lại cùng email để cấp mật khẩu tạm mới — tránh kẹt trạng thái 

---

# KHỐI G — KẾT LUẬN

## G1. Kết luận (Vân) ★

### Lời nói

> Tóm lại, đồ án GYMFINDER đã triển khai đủ quy trình: khảo sát và đặc tả, phân tích UML, thiết kế CSDL, hiện thực User Portal và Admin Portal, tích hợp Trợ lý AI, đa ngôn ngữ VI/EN, kiểm thử và triển khai demo trên Railway.  
> Hệ thống đáp ứng mục tiêu gom thông tin phòng tập và PT tại TP.HCM, hỗ trợ tìm–so sánh–yêu thích–đánh giá có kiểm duyệt trong đúng phạm vi MVP nhóm đã tuyên bố.  
> Phần Frontend, AI và đa ngôn ngữ do em phụ trách đã được gắn với Backend và dữ liệu do bạn Lợi phụ trách trên cùng một hệ thống chạy thật.

## G2. Hướng phát triển (Lợi) ★

### Lời nói

> Hướng phát triển tiếp theo có thể gồm: tích hợp bản đồ và chỉ đường, đặt lịch, thông báo khi đánh giá được duyệt hoặc từ chối, nhật ký kiểm toán thao tác Admin, và mở rộng khả năng Trợ lý AI.

## G3. Cảm ơn ★

### Lời nói (Vân)

> Nhóm xin cảm ơn thầy/cô và hội đồng đã lắng nghe. Nhóm sẵn sàng trả lời các câu hỏi.

---

# PHỤ LỤC 1 — Câu hỏi thường gặp


| Câu hỏi                             | Ai      | Gợi ý trả lời                                                                                           |
| ----------------------------------- | ------- | ------------------------------------------------------------------------------------------------------- |
| Vì sao không thanh toán / đặt lịch? | Vân     | Ngoài phạm vi MVP; tập trung tra cứu, đánh giá, liên hệ trực tiếp.                                      |
| Soft-gate khác soft-hide?           | Vân/Lợi | Soft-gate: mời login khi Guest vào tính năng thành viên. Soft-hide: ẩn gym/PT khỏi public, giữ bản ghi. |
| VI/EN dịch thế nào?                 | Vân     | `gf_t` + `lang/en.php`, `en_pages.php`; data DB không auto-dịch.                                        |
| AI lấy dữ liệu đâu?                 | Vân     | Grounding catalog active trong MySQL + gọi Groq.                                                        |
| OTP lưu thế nào?                    | Lợi     | Hash Bcrypt, TTL 5 phút, one-time.                                                                      |
| Admin xóa user?                     | Lợi     | Không xóa cứng hội viên trên UI; khóa/mở khóa.                                                          |
| Đặc điểm khác tiện ích?             | Vân     | Đặc điểm = tính chất vận hành; tiện ích = CSVC.                                                         |
| CSRF?                               | Lợi     | Token session gắn form POST, chống giả mạo liên site.                                                   |
| Vì sao SSR không SPA?               | Vân     | Phù hợp đồ án, form/CSRF/phân quyền rõ, deploy PHP đơn giản.                                            |


---

# PHỤ LỤC 2 — Checklist trước giờ

- [ ] 1 tab Railway, Guest, trang chủ  
- [ ] User + Admin mật khẩu viết sẵn  
- [ ] 1 gym + 1 PT đẹp đã chọn  
- [ ] AI trả lời được 1 câu (test trước)  
- [ ] SMTP chỉ cần nếu demo mời Admin  
- [ ] Vân nắm khối **A+B+C+E+G1**; Lợi nắm **D+F+G2**  
- [ ] In hoặc mở file này khi luyện nói  

---

# PHỤ LỤC 3 — Bản siêu rút 12 phút (vẫn đủ ý ★)

1. **Vân A:** chào + bài toán + mục tiêu + ngoài phạm vi + phân công (1.5’)
2. **Vân B:** tech + kiến trúc + CSDL đặc điểm (1.5’)
3. **Vân C:** EN/VI + lọc gym + chi tiết + soft-gate + AI 1 câu (3.5’)
4. **Lợi D:** login User + bảo mật + nói OTP/Google (1.5’)
5. **Vân E:** tim + review pending + logout modal (1.5’)
6. **Lợi F:** dashboard + đặc điểm + duyệt review + nói mời Admin (2’)
7. **Vân+Lợi G:** kết + hướng phát triển (0.5’)

---

*File này ưu tiên: Vân nói mở đầu + công nghệ đủ ý; demo một chiều; checklist đủ nội dung thuyết trình.*