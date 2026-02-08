#!/usr/bin/env python3
"""
Face Cropper (with optional fallback detection)
- If --bbox is given (normalized Rekognition bbox), use it to crop.
- Else, try Haar cascade to detect the largest face.
- Always returns JSON.
"""

import cv2, json, argparse, numpy as np, sys, os

def clamp(v, lo, hi): return max(lo, min(hi, v))

def parse_bbox(s):
    # "left,top,width,height" normalized (0..1)
    parts = [float(x.strip()) for x in s.split(",")]
    if len(parts) != 4: raise ValueError("bbox must be 4 numbers")
    return parts  # L,T,W,H

def pad_box(x, y, w, h, pad, W, H):
    x -= pad*w; y -= pad*h; w *= (1+2*pad); h *= (1+2*pad)
    x2, y2 = x + w, y + h
    x  = clamp(int(round(x)), 0, W-1)
    y  = clamp(int(round(y)), 0, H-1)
    x2 = clamp(int(round(x2)), 0, W-1)
    y2 = clamp(int(round(y2)), 0, H-1)
    return x, y, max(1, x2-x), max(1, y2-y)

def detect_largest_face(img):
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)

    # Try to get Haar cascade path with fallback
    cascade_path = None
    try:
        # Try cv2.data first (newer OpenCV versions)
        if hasattr(cv2, 'data'):
            cascade_path = cv2.data.haarcascades + "haarcascade_frontalface_default.xml"  # type: ignore
    except (AttributeError, TypeError):
        pass

    if not cascade_path:
        # Fallback: construct path manually for older OpenCV
        import site
        import glob

        # Try to find haarcascades in site-packages
        site_packages = site.getsitepackages()

        for sp in site_packages:
            pattern = os.path.join(sp, "cv2", "data", "haarcascade_frontalface_default.xml")
            matches = glob.glob(pattern)
            if matches:
                cascade_path = matches[0]
                break

        if not cascade_path:
            # Last resort: try relative to cv2 module
            cv2_path = os.path.dirname(cv2.__file__)
            cascade_path = os.path.join(cv2_path, "data", "haarcascade_frontalface_default.xml")

    cc = cv2.CascadeClassifier(cascade_path)

    # Check if cascade loaded successfully
    if cc.empty():
        # If still fails, return None (no face detected)
        return None

    faces = cc.detectMultiScale(gray, scaleFactor=1.1, minNeighbors=5, minSize=(80,80))
    if len(faces) == 0: return None
    # pick largest area
    areas = [w*h for (x,y,w,h) in faces]
    return faces[int(np.argmax(areas))]  # x,y,w,h (pixels)

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--image", required=True)
    ap.add_argument("--out",   required=True)
    ap.add_argument("--bbox",  help="normalized 'left,top,width,height' from Rekognition")
    ap.add_argument("--pad",   type=float, default=0.1, help="extra padding around bbox (0.0..0.5)")
    args = ap.parse_args()

    try:
        img = cv2.imread(args.image)
        if img is None:
            print(json.dumps({"ok": False, "message": "Gambar tidak bisa dibaca"})); return
        H, W = img.shape[:2]

        used = None
        source = None

        if args.bbox:
            L,T,WW,HH = parse_bbox(args.bbox)   # normalized
            x = L * W; y = T * H; w = WW * W; h = HH * H
            x, y, w, h = pad_box(x, y, w, h, args.pad, W, H)
            used = (x,y,w,h); source = "bbox"
        else:
            det = detect_largest_face(img)
            if det is not None:
                x,y,w,h = det
                x, y, w, h = pad_box(x, y, w, h, args.pad, W, H)
                used = (x,y,w,h); source = "haar"

        if used is None:
            print(json.dumps({"ok": False, "message": "Wajah tidak ditemukan"})); return

        x,y,w,h = used
        crop = img[y:y+h, x:x+w].copy()

        # Optional: standarkan ukuran minimum
        MIN = 224
        if min(crop.shape[:2]) < MIN:
            scale = float(MIN) / float(min(crop.shape[:2]))
            crop = cv2.resize(crop, (int(crop.shape[1]*scale), int(crop.shape[0]*scale)), interpolation=cv2.INTER_CUBIC)

        os.makedirs(os.path.dirname(args.out), exist_ok=True)
        cv2.imwrite(args.out, crop)

        print(json.dumps({
            "ok": True,
            "out": args.out,
            "source": source,               # "bbox" atau "haar"
            "bbox_px": {"x": x, "y": y, "w": w, "h": h},
            "width": int(crop.shape[1]),
            "height": int(crop.shape[0])
        }))
    except Exception as e:
        print(json.dumps({"ok": False, "message": f"Error: {str(e)}"}))

if __name__ == "__main__":
    main()
