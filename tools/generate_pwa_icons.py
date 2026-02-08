"""
Script untuk generate PWA icons dari eabsensi.png
"""
from PIL import Image
import os

# Sizes yang dibutuhkan untuk PWA
ICON_SIZES = [72, 96, 128, 144, 152, 192, 384, 512]

# Path
source_icon = 'public/eabsensi.png'
output_dir = 'public/images'

# Create output directory if not exists
os.makedirs(output_dir, exist_ok=True)

try:
    # Load source icon
    print(f"Loading {source_icon}...")
    img = Image.open(source_icon)

    # Convert to RGBA if needed
    if img.mode != 'RGBA':
        img = img.convert('RGBA')

    # Generate icons
    for size in ICON_SIZES:
        output_path = f'{output_dir}/icon-{size}x{size}.png'

        # Resize with high quality
        resized = img.resize((size, size), Image.Resampling.LANCZOS)

        # Save
        resized.save(output_path, 'PNG', optimize=True)
        print(f"✅ Generated: {output_path}")

    print("\n✅ All icons generated successfully!")
    print("\nGenerated icons:")
    for size in ICON_SIZES:
        print(f"  - icon-{size}x{size}.png")

except FileNotFoundError:
    print(f"❌ Error: {source_icon} not found!")
    print("Please make sure the file exists.")
except Exception as e:
    print(f"❌ Error: {e}")
