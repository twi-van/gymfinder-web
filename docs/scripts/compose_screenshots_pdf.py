#!/usr/bin/env python3
"""Compose viewport screenshots into one multipage PDF with browser address bar."""
from __future__ import annotations

import json
import sys
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont


def load_font(size: int) -> ImageFont.ImageFont:
    candidates = [
        "/System/Library/Fonts/SFNS.ttf",
        "/System/Library/Fonts/SFNSText.ttf",
        "/System/Library/Fonts/Helvetica.ttc",
        "/Library/Fonts/Arial.ttf",
        "/System/Library/Fonts/Supplemental/Arial.ttf",
    ]
    for path in candidates:
        try:
            return ImageFont.truetype(path, size=size)
        except OSError:
            continue
    return ImageFont.load_default()


def draw_browser_chrome(width: int, url: str, title: str) -> Image.Image:
    chrome_h = 88
    img = Image.new("RGB", (width, chrome_h), "#e8eaed")
    draw = ImageDraw.Draw(img)
    font_sm = load_font(13)
    font_url = load_font(14)
    font_title = load_font(13)

    # traffic lights
    for i, color in enumerate(("#ff5f57", "#febc2e", "#28c840")):
        x = 18 + i * 20
        draw.ellipse((x, 16, x + 12, 28), fill=color)

    # tab
    tab_w = min(280, width // 3)
    draw.rounded_rectangle((90, 10, 90 + tab_w, 38), radius=8, fill="#ffffff")
    tab_label = title if len(title) < 34 else title[:31] + "…"
    draw.text((102, 16), tab_label, fill="#202124", font=font_title)

    # address bar
    bar_top, bar_bot = 46, 78
    draw.rounded_rectangle((16, bar_top, width - 16, bar_bot), radius=16, fill="#ffffff")
    draw.text((34, bar_top + 7), url, fill="#202124", font=font_url)

    # subtle bottom edge
    draw.line((0, chrome_h - 1, width, chrome_h - 1), fill="#dadce0")
    return img


def frame_screen(shot_path: Path, url: str, title: str, index: int, total: int) -> Image.Image:
    shot = Image.open(shot_path).convert("RGB")
    chrome = draw_browser_chrome(shot.width, url, title)
    pad = 36
    header_h = 52
    canvas_w = shot.width + pad * 2
    canvas_h = pad + header_h + chrome.height + shot.height + pad
    canvas = Image.new("RGB", (canvas_w, canvas_h), "#f1f3f4")
    draw = ImageDraw.Draw(canvas)
    font = load_font(18)
    font_meta = load_font(13)
    label = f"{index:02d}/{total:02d}  {title}"
    draw.text((pad, pad + 8), label, fill="#202124", font=font)
    draw.text((pad, pad + 32), url, fill="#5f6368", font=font_meta)

    y = pad + header_h
    # window shadow-ish border
    frame = Image.new("RGB", (shot.width + 2, chrome.height + shot.height + 2), "#c5c8cc")
    canvas.paste(frame, (pad - 1, y - 1))
    canvas.paste(chrome, (pad, y))
    canvas.paste(shot, (pad, y + chrome.height))
    return canvas


def main() -> int:
    if len(sys.argv) < 3:
        print("Usage: compose_screenshots_pdf.py manifest.json out.pdf [out.png]", file=sys.stderr)
        return 2
    manifest = Path(sys.argv[1])
    out_pdf = Path(sys.argv[2])
    out_png = Path(sys.argv[3]) if len(sys.argv) > 3 else None
    items = json.loads(manifest.read_text(encoding="utf-8"))
    pages: list[Image.Image] = []
    total = len(items)
    for i, item in enumerate(items, start=1):
        pages.append(
            frame_screen(
                Path(item["file"]),
                item["fullUrl"],
                item["title"],
                i,
                total,
            )
        )
    if not pages:
        print("No pages", file=sys.stderr)
        return 1
    # Normalize widths for PDF by padding to max width
    max_w = max(p.width for p in pages)
    normalized = []
    for p in pages:
        if p.width == max_w:
            normalized.append(p)
        else:
            canvas = Image.new("RGB", (max_w, p.height), "#f1f3f4")
            canvas.paste(p, (0, 0))
            normalized.append(canvas)
    first, *rest = normalized
    first.save(out_pdf, "PDF", save_all=True, append_images=rest, resolution=100.0)
    print(f"PDF pages: {len(normalized)} -> {out_pdf}")

    if out_png is not None:
        gap = 28
        total_h = sum(p.height for p in normalized) + gap * (len(normalized) + 1)
        collage = Image.new("RGB", (max_w, total_h), "#e8eaed")
        y = gap
        for p in normalized:
            collage.paste(p, (0, y))
            y += p.height + gap
        # Cap extremely tall images for viewers: keep full collage (user asked all screens)
        collage.save(out_png, "PNG", optimize=True)
        print(f"PNG collage: {collage.size} -> {out_png}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
