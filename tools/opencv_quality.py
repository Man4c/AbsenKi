#!/usr/bin/env python3
"""
OpenCV Quality Gate Checker
Checks image quality: sharpness (Laplacian), brightness, and dimensions
"""

import cv2
import json
import argparse
import numpy as np
import sys

def main():
    parser = argparse.ArgumentParser(description='Check image quality for face recognition')
    parser.add_argument('--image', required=True, help='Path to image file')
    args = parser.parse_args()

    try:
        # Read image
        img = cv2.imread(args.image)

        if img is None:
            print(json.dumps({
                "ok": False,
                "message": "Gambar tidak bisa dibaca atau format tidak didukung"
            }))
            sys.exit(0)

        # Convert to grayscale for Laplacian
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)

        # Check if image has proper dimensions
        if len(img.shape) < 2:
            print(json.dumps({
                "ok": False,
                "message": "Format gambar tidak valid"
            }))
            sys.exit(0)

        # Calculate Laplacian variance (sharpness/blur detection)
        laplacian = cv2.Laplacian(gray, cv2.CV_64F)
        lap_var = float(laplacian.var())

        # Calculate brightness using V channel in HSV
        hsv = cv2.cvtColor(img, cv2.COLOR_BGR2HSV)
        v_channel = hsv[:, :, 2]
        brightness = float(np.mean(v_channel))  # type: ignore

        # Get dimensions
        height, width = img.shape[:2]

        # Return success with metrics
        result = {
            "ok": True,
            "laplace": round(lap_var, 2),
            "brightness": round(brightness, 2),
            "width": int(width),
            "height": int(height),
            "message": "Quality check completed successfully"
        }

        print(json.dumps(result))
        sys.exit(0)

    except Exception as e:
        print(json.dumps({
            "ok": False,
            "message": f"Error during quality check: {str(e)}"
        }))
        sys.exit(1)

if __name__ == "__main__":
    main()
