# GYMFINDER — Hướng dẫn tích hợp AI Chat (theo Sprint)

Tài liệu này mô tả cách thêm **chat AI gợi ý phòng tập** vào dự án PHP SSR hiện tại, chia theo sprint nhỏ để làm đồ án (ưu tiên **miễn phí**).

**Mục tiêu MVP:** User hỏi bằng tiếng Việt → hệ thống trả lời ngắn + **link 1–3 phòng tập thật** trong DB (không bịa gym).

**Khuyến nghị provider free:** [Groq](https://console.groq.com) hoặc [Google AI Studio (Gemini)](https://aistudio.google.com). Backup offline: [Ollama](https://ollama.com).

---

## Nguyên tắc chung (mọi sprint)

1. **API key chỉ ở server** — file local gitignore (giống `backend/smtp.local.php`), không đưa key vào JS/HTML.
2. **AI không được tự tạo gym** — chỉ chọn từ danh sách gym `active` lấy từ DB rồi nhét vào prompt.
3. **Kết quả luôn map lại DB** — nếu model trả `id` không tồn tại → bỏ qua / báo “không tìm thấy phù hợp”.
4. **Không bắt buộc** cho đặc tả lõi; xem như **tính năng bổ sung** khi demo.

```
User hỏi
   → Backend lấy N gym từ DB (filter thô: quận / giá / từ khóa)
   → Gửi prompt + catalog ngắn cho LLM
   → LLM trả JSON { reply, gym_ids: [...] }
   → UI hiện câu trả lời + card/link gym
```

---

## Sprint 0 — Chuẩn bị (½–1 ngày)

### Việc cần làm

| # | Việc | Chi tiết |
| --- | --- | --- |
| 0.1 | Chọn provider | **Groq** (nhanh, free tier) hoặc **Gemini** (free, tiếng Việt ổn) |
| 0.2 | Tạo API key | Đăng ký console → tạo key → lưu tạm ngoài repo |
| 0.3 | Thêm file config local | `backend/ai.local.php` (gitignore) + `backend/ai.local.php.example` (commit) |
| 0.4 | Cập nhật `.gitignore` | Thêm `backend/ai.local.php` |
| 0.5 | Ghi chú Hướng dẫn chạy | 1 đoạn trong `GymFinder_HuongDanChay.md`: bật AI chat cần key |

### Mẫu `backend/ai.local.php.example`

```php
<?php
// Copy thành ai.local.php (không commit).
return [
    'provider' => 'groq', // groq | gemini | ollama
    'api_key'  => '',     // Groq / Gemini
    'model'    => 'qwen/qwen3.8-27b', // Groq free; fallback openai/gpt-oss-20b | Gemini: đổi provider + model
    'base_url' => '',     // để trống = default; Ollama: http://127.0.0.1:11434
    'enabled'  => true,
];
```

### Tiêu chí xong Sprint 0

- [x] Có key free hoạt động (test bằng curl/Postman gọi chat completion 1 câu)
- [x] File example + gitignore đã có
- [x] Team thống nhất provider (Groq)

---

## Sprint 1 — Backend gọi LLM + “grounding” DB (1–2 ngày)

### Việc cần làm

| # | Việc | Gợi ý file |
| --- | --- | --- |
| 1.1 | Load config AI | `backend/config.php` đọc `ai.local.php` (giống SMTP) |
| 1.2 | Helper HTTP | `backend/ai_client.php` — `gf_ai_chat(array $messages): array` |
| 1.3 | Lấy catalog gym | Query tối đa ~30–50 gym `status=active`: `id, name, district, price_min/max, avg_rating, categories` |
| 1.4 | Hàm gợi ý | `gf_ai_recommend_gyms(string $userMessage): array` |
| 1.5 | Endpoint | `api/` route mới **hoặc** `pages/ai/chat.php` nhận POST + CSRF |

### Prompt tối thiểu (ý tưởng)

```
Bạn là trợ lý GYMFINDER. Chỉ được chọn gym trong danh sách JSON dưới đây.
Trả về ĐÚNG JSON:
{"reply":"...","gym_ids":[1,2]}
Không bịa id. Nếu không phù hợp: gym_ids rỗng + giải thích ngắn.
Danh sách gym: ...
Câu hỏi user: ...
```

### Sau khi LLM trả lời

1. `json_decode` (nếu fail → trả lỗi thân thiện).
2. Lọc `gym_ids` chỉ giữ id có trong DB.
3. Load lại thông tin gym để render card.

### Tiêu chí xong Sprint 1

- [x] Gọi được AI từ PHP (`backend/ai_client.php` + `gf_ai_recommend_gyms`)
- [x] Response luôn kèm gym thật hoặc `gym_ids: []` (validate lại DB)
- [x] Không key → báo trên UI “AI chưa cấu hình”

---

## Sprint 2 — UI Chat widget (1–2 ngày)

### Việc cần làm

| # | Việc | Gợi ý |
| --- | --- | --- |
| 2.1 | Trang chat (đơn giản) | `pages/ai/chat.php` — form hỏi + khung hội thoại |
| 2.2 | (Tuỳ chọn) Floating button | Nút góc phải trang chủ / search → mở panel chat |
| 2.3 | Render kết quả | Bubble AI + list card gym (tái dùng partial giống search) |
| 2.4 | UX cơ bản | Loading “Đang tìm…”, disable nút gửi, CSRF, giới hạn độ dài câu hỏi |
| 2.5 | Guest vs User | Guest cũng chat được (khuyến nghị đồ án); hoặc yêu cầu login nếu muốn |

### Gợi ý câu mẫu (chips)

- “Gym quanh Quận 7 giá dưới 500k”
- “Phòng tập có Yoga, rating cao”
- “Gợi ý gym cho người mới bắt đầu”

### Tiêu chí xong Sprint 2

- [x] User hỏi trên UI → thấy câu trả lời + link/card gym (`pages/ai/chat.php`)
- [x] Mobile dùng được (layout container hẹp)
- [x] Không cấu hình AI → hiện hướng dẫn tạo `ai.local.php`

---

## Sprint 3 — Filter thông minh hơn + an toàn (1 ngày)

### Việc cần làm

| # | Việc | Mục đích |
| --- | --- | --- |
| 3.1 | Pre-filter DB trước khi gọi AI | Giảm token: theo `district_id`, khoảng giá, category nếu parse được từ câu hỏi |
| 3.2 | Rate limit đơn giản | Session: tối đa N câu / phút (tránh burn free tier) |
| 3.3 | Timeout / fallback | AI chậm hoặc lỗi → “Thử lại” + gợi ý dùng trang Tìm kiếm |
| 3.4 | Log tối thiểu | `error_log` khi API fail (không log full key / PII thừa) |
| 3.5 | Soft-hide gym | Chỉ đưa gym `active` vào catalog |

### Tiêu chí xong Sprint 3

- [x] Pre-filter DB theo quận / giá / keyword trước khi gọi AI (`gf_ai_parse_question_hints`)
- [x] Rate limit session 6 câu/phút + thông báo thân thiện khi Groq rate-limit
- [x] Lỗi AI → message fallback + link Tìm kiếm (không trắng trang); `error_log` tối thiểu
- [x] Catalog chỉ `active` (gym/HLV soft-hide)

---

## Sprint 4 — Demo đồ án + tài liệu (½–1 ngày)

### Việc cần làm

| # | Việc |
| --- | --- |
| 4.1 | Thêm mục “AI Chat (bổ sung)” vào `GymFinder_HuongDanChay.md` |
| 4.2 | 3–5 câu demo chuẩn để chấm (ghi sẵn trong UI hoặc doc) |
| 4.3 | Screenshot / clip 30–60s: hỏi → ra gym |
| 4.4 | Slide/báo cáo: kiến trúc 4 bước (DB → prompt → LLM → map id) |
| 4.5 | Ghi rõ: free tier, key local, không commit secret |

### Checklist nộp / demo

- [ ] Máy demo đã có `ai.local.php` + key còn hạn mức
- [ ] Mạng ổn (Groq/Gemini cần internet)
- [ ] Fallback nói được nếu API die lúc chấm

---

## (Tuỳ chọn) Sprint 5 — Nâng cao

Chỉ làm nếu còn thời gian; **không bắt buộc** đồ án:

- Lưu lịch sử chat theo `user_id` (bảng `ai_chat_messages`)
- Stream từng token (SSE) — UX mượt hơn, phức tạp hơn trên PHP thuần
- Gợi ý HLV (`trainers`) tương tự gym
- RAG / embedding — overkill cho scale seed hiện tại
- Admin bật/tắt AI + xem số lần gọi

---

## Phân công gợi ý (2 người)

| Vai trò | Sprint phụ trách |
| --- | --- |
| Backend / DB | S0 config, S1 client + recommend, S3 rate limit |
| Frontend / Docs | S2 UI chat, S4 hướng dẫn + demo script |
| Cả hai | Chọn provider, test câu tiếng Việt, review prompt |

---

## Cấu trúc file đề xuất (sau khi làm xong)

```
backend/
  ai.local.php.example
  ai.local.php          # gitignore
  ai_client.php
  ai_recommend.php      # hoặc gộp trong ai_client.php
pages/ai/
  chat.php
assets/css/             # (tuỳ) chat.css
api/                    # (tuỳ) route POST /api/ai/chat
docs/specs/
  GymFinder_AI_Chat_Sprints.md   # file này
```

---

## Ước lượng effort

| Phạm vi | Thời gian tham khảo |
| --- | --- |
| S0 + S1 + S2 (MVP demo) | ~3–5 ngày làm việc nhẹ |
| + S3 + S4 | +1–2 ngày |
| + S5 | tuỳ chọn |

---

## Rủi ro thường gặp

| Rủi ro | Cách xử lý |
| --- | --- |
| AI bịa tên gym | Chỉ cho phép id trong catalog; validate lại |
| Hết free quota lúc demo | Có sẵn 2 key / hoặc Ollama backup |
| Câu hỏi mơ hồ | Chip gợi ý + prompt yêu cầu hỏi lại 1 tiêu chí |
| Commit nhầm API key | gitignore + `ai.local.php.example` không chứa secret |

---

## Quyết định nhanh

| Câu hỏi | Trả lời ngắn |
| --- | --- |
| Có cần API key? | **Có** (Groq/Gemini). Ollama thì không cần key cloud. |
| Có phá CSDL hiện tại? | Không bắt buộc thêm bảng cho MVP. |
| Có thay search cũ? | Không — AI là kênh phụ, search/filter vẫn chính. |
| Làm hết S5? | Không cần nếu chỉ demo đồ án. |

Khi bắt đầu code: làm **Sprint 0 → 1 → 2** trước; chỉ mở rộng S3+ khi MVP đã chạy ổn trên máy local.
