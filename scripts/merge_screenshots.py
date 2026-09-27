import os
from PIL import Image

def merge_screenshots():
    folder = 'screenshots'
    files = [
        '1_trang_chu.png',
        '2_phong_tap.png',
        '3_yeu_thich.png',
        '4_gym_chi_tiet.png',
        '5_huan_luyen_vien.png'
    ]
    
    images = []
    for f in files:
        path = os.path.join(folder, f)
        if os.path.exists(path):
            images.append(Image.open(path))
        else:
            print(f"Warning: {f} not found!")

    if not images:
        print("No images found to merge.")
        return

    # Calculate padding and dimensions
    padding = 50
    total_width = sum(img.width for img in images) + padding * (len(images) - 1) + padding * 2
    max_height = max(img.height for img in images) + padding * 2

    # Create new background image
    background_color = (240, 242, 245) # Light gray/blueish background
    new_im = Image.new('RGB', (total_width, max_height), background_color)

    # Paste images
    x_offset = padding
    for img in images:
        # We can add a drop shadow effect or just paste it
        # Just pasting for simplicity, aligning at the top (padding)
        new_im.paste(img, (x_offset, padding))
        x_offset += img.width + padding

    output_path = os.path.join(folder, 'all_screens_combined.png')
    new_im.save(output_path)
    print(f"Successfully created {output_path}")

if __name__ == '__main__':
    merge_screenshots()
