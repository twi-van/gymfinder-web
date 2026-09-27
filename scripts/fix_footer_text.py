import os

old_text = "Nền tảng tìm kiếm và khám phá phòng tập, yoga, boxing và huấn luyện viên cá nhân."
new_text = "Nền tảng tìm kiếm và so sánh các phòng tập gym, tiện ích, loại hình tập luyện và đội ngũ huấn luyện viên."

for root, dirs, files in os.walk('.'):
    for file in files:
        if file.endswith('.html'):
            filepath = os.path.join(root, file)
            with open(filepath, 'r', encoding='utf-8') as f:
                content = f.read()
            if old_text in content:
                content = content.replace(old_text, new_text)
                with open(filepath, 'w', encoding='utf-8') as f:
                    f.write(content)
                print(f"Updated text in {filepath}")

