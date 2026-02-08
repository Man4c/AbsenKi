1. Adaptive Threshold Berdasarkan Kondisi
   Buat threshold yang dinamis:
   • Jika brightness rendah - turunkan threshold laplacian (karena foto gelap = low contrast = laplacian rendah)
   • Jika brightness tinggi - tetap gunakan threshold normal
   • Formula: adjusted_threshold = base_threshold \* (brightness / target_brightness)

2. Client-Side Image Enhancement (JavaScript)
   Sebelum kirim ke server, enhance foto di browser dengan Canvas API:
   • Apply brightness/contrast adjustment
   • Apply unsharp mask filter
   • Convert to optimal format/quality

Tujuan
Biar foto yang agak gelap/kurang tajam tetap dinilai adil (tidak auto gagal), dan kamera HP “biasa” terbantu tanpa ganti perangkat.
Scope (jangan ubah alur besar yang sudah ada)
• Server QC tetap di FaceProcessor / quality check yang dipakai sekarang.
• Verifikasi tetap lewat FaceVerificationService + Rekognition (DetectFaces/SearchFacesByImage).
• Tidak mengubah format penyimpanan, route, atau DB.

---

ENV Baru (toggle aman)
Tambahkan dan pakai env ini (jangan ganti env lama):
• ENABLE_ADAPTIVE_LAPLACE=true|false (default true)
• FACE_LAPLACE_TARGET_BRIGHTNESS=130 (skala 0–255)
• FACE_LAPLACE_MIN=60 (batas bawah hasil adaptasi)
• FACE_LAPLACE_MAX=200 (batas atas hasil adaptasi)
• ENABLE_CLIENT_ENHANCE=true|false (default true)
Env lama tetap dipakai:
• FACE_MIN_LAPLACE (ini jadi base_threshold)
• FACE_MIN_BRIGHTNESS
• FACE_MIN_WIDTH, FACE_MIN_HEIGHT, FACE_MIN_BOX_PERCENT (jika ada)

---

Server: Adaptive Threshold (di quality check)
• Ambil laplace & brightness (yang sudah dihitung sekarang).
• Jika ENABLE_ADAPTIVE_LAPLACE=true, hitung:
o adjusted_threshold = base_threshold \* (brightness / TARGET_BRIGHTNESS)
o Clamp ke [FACE_LAPLACE_MIN … FACE_LAPLACE_MAX].
• Lulus ketajaman jika: laplace >= adjusted_threshold.
• Brightness & ukuran tetap pakai aturan sekarang.
• Logging (INFO): simpan laplace, brightness, base_threshold, adjusted_threshold, dan status lolos/gagal.
Catatan: kalau ENABLE_ADAPTIVE_LAPLACE=false, pakai aturan lama 100% (backward compatible).

---

Client: Enhancement ringan (Canvas, sebelum upload)
Aktif hanya saat ENABLE_CLIENT_ENHANCE=true, jalankan di halaman kamera staff:
• Naikkan brightness/contrast tipis (aman).
• Unsharp mask tipis (biar sedikit lebih tajam).
• Encode JPEG kualitas ±0.85.
• Kirim hasil enhancenya ke server (tanpa mengubah API yang ada).
Catatan: efek ringan saja, jangan agresif biar wajah tetap natural dan match Rekognition.

---

Integrasi (jangan putus flow)
• Enroll (admin upload): adaptive threshold dan enhance server-side tetap jalan seperti sekarang.
• Verify (staff kamera): client-side enhance → kirim → QC server (adaptive) → Rekognition (DetectFaces untuk bbox, SearchFacesByImage untuk match).

---

Selesai bila (Acceptance)
• Bisa ON/OFF via ENV tanpa ubah kode lain.
• Log menunjukkan nilai: laplace, brightness, base_threshold, adjusted_threshold.
• Foto gelap tipis yang dulu gagal, kini lolos bila masuk batas aman.
• Tidak ada perubahan pada DB, route, dan API existing.
• Bbox dan hasil match tetap tampil seperti biasa.

---

Uji Cepat

1. Adaptive ON, Enhance OFF: foto agak gelap → lihat log adjusted_threshold turun → QC lolos.
2. Enhance ON: ambil foto gelap → terlihat lebih cerah/tajam → QC lolos.
3. Semua OFF: sistem kembali ke perilaku lama (pembanding).
