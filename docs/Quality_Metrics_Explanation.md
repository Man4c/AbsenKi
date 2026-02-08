# Quality Metrics - Laplacian Variance & HSV Brightness

## 📋 Ringkasan

Sistem AbsenKi menggunakan **OpenCV preprocessing** untuk memvalidasi kualitas foto wajah sebelum dikirim ke AWS Rekognition. Ini menghemat biaya API dan meningkatkan akurasi.

---

## 🔬 Preprocessing yang Dilakukan

### 0. **Face Detection & Cropping** ✨ NEW

-   **Apa itu?**: Memotong foto agar hanya area wajah saja (menghilangkan background dan badan)
-   **Cara kerja**:

    -   **Priority 1**: Jika ada bbox dari AWS Rekognition DetectFaces → gunakan bbox tersebut (akurat)
    -   **Priority 2 (Fallback)**: Jika tidak ada bbox → gunakan Haar Cascade OpenCV untuk deteksi wajah terbesar
    -   Tambahkan padding 10% di sekitar wajah agar tidak terlalu ketat
    -   Crop sesuai koordinat yang didapat
    -   Resize minimum ke 224x224 px untuk kualitas konsisten

-   **Teknologi**: `tools/opencv_face_crop.py`
-   **Input**: Foto asli + optional bbox dari Rekognition
-   **Output**: Foto cropped hanya area wajah
-   **Formula Python**:

    ```python
    # Jika ada bbox dari Rekognition (normalized 0-1)
    if bbox:
        x = bbox.Left * image_width
        y = bbox.Top * image_height
        w = bbox.Width * image_width
        h = bbox.Height * image_height
    # Fallback ke Haar Cascade
    else:
        faces = cv2.CascadeClassifier.detectMultiScale(gray_image)
        x, y, w, h = largest_face

    # Tambah padding
    x -= pad * w
    y -= pad * h
    w *= (1 + 2*pad)
    h *= (1 + 2*pad)

    # Crop
    cropped = image[y:y+h, x:x+w]
    ```

### 1. **Laplacian Variance (Blur Detection)**

-   **Apa itu?**: Mengukur ketajaman/keburaman foto menggunakan operator Laplacian
-   **Cara kerja**:

    -   Foto dikonversi ke grayscale
    -   Operator Laplacian mendeteksi edge (tepi) pada gambar
    -   Variance (variasi) dari nilai Laplacian menunjukkan seberapa tajam gambar
    -   **Variance tinggi** = banyak edge yang jelas = foto tajam
    -   **Variance rendah** = edge kabur/tidak jelas = foto buram

-   **Threshold**: `FACE_MIN_LAPLACE=30` (di file `.env`)
-   **Formula Python**:
    ```python
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    laplacian = cv2.Laplacian(gray, cv2.CV_64F)
    lap_var = float(laplacian.var())
    ```

### 2. **HSV Brightness (Light Detection)**

-   **Apa itu?**: Mengukur tingkat kecerahan foto menggunakan color space HSV
-   **Cara kerja**:

    -   Foto dikonversi dari BGR (Blue-Green-Red) ke HSV (Hue-Saturation-Value)
    -   **V channel** (Value) merepresentasikan brightness/kecerahan
    -   Rata-rata nilai V channel dihitung untuk seluruh pixel
    -   Nilai 0 = hitam total, nilai 255 = putih total

-   **Threshold**: `FACE_MIN_BRIGHTNESS=65` (di file `.env`)
-   **Formula Python**:
    ```python
    hsv = cv2.cvtColor(img, cv2.COLOR_BGR2HSV)
    brightness = float(np.mean(hsv[:, :, 2]))  # Channel 2 = V channel
    ```

---

## 📂 Struktur Kode

### 1. **Script Python** (`tools/opencv_quality.py`)

```
Lokasi: c:\laragon\www\AbsenKi\tools\opencv_quality.py
Fungsi: Standalone script yang menerima path gambar dan mengembalikan JSON
Input: --image /path/to/image.jpg
Output: JSON dengan laplace, brightness, width, height
```

**Contoh Output**:

```json
{
    "ok": true,
    "laplace": 45.32,
    "brightness": 78.5,
    "width": 1280,
    "height": 720,
    "message": "Quality check completed successfully"
}
```

### 2. **PHP Service** (`app/Services/FaceProcessor.php`)

```php
Lokasi: c:\laragon\www\AbsenKi\app\Services\FaceProcessor.php
Method: qualityCheck(string $localPath)
Fungsi: Memanggil Python script via Symfony Process
Return: Array dengan success status dan metrics
```

**Flow**:

1. Cek threshold dari environment variables
2. Jalankan Python script dengan Symfony Process
3. Parse JSON output dari Python
4. Validasi metrics terhadap threshold
5. Return success/error dengan pesan user-friendly

### 3. **Livewire Component** (`app/Livewire/Staff/Absen.php`)

```php
Lokasi: c:\laragon\www\AbsenKi\app\Livewire\Staff\Absen.php
Method: verifyFace(string $imageDataUrl)
Properties: $qualityBlur, $qualityBrightness
```

**Flow di Absen.php**:

```
User ambil foto
   ↓
JavaScript convert ke base64
   ↓
Livewire terima imageDataUrl
   ↓
Decode base64 → simpan temp file
   ↓
FaceProcessor::qualityCheck()  ← PREPROCESSING DI SINI
   ↓
Jika gagal: return error ke user
Jika lolos: lanjut ke AWS Rekognition
   ↓
Simpan metrics ke database (attendances table)
```

---

## 🗄️ Penyimpanan Data

### Database Schema

```sql
-- Table: attendances
-- Columns ditambahkan via migration: 2025_11_12_150032_add_quality_columns_to_attendances_table.php

quality_blur_var       FLOAT NULL    -- Laplacian variance (sharpness)
quality_brightness     FLOAT NULL    -- HSV brightness (0-255)
```

### Model

```php
// app/Models/Attendance.php
protected $fillable = [
    // ... existing fields
    'quality_blur_var',
    'quality_brightness',
];

protected $casts = [
    // ... existing casts
    'quality_blur_var' => 'decimal:2',
    'quality_brightness' => 'decimal:2',
];
```

---

## 👁️ Di Mana Melihat Hasil?

### 1. **Halaman Laporan Admin** (RECOMMENDED)

```
URL: http://127.0.0.1:8000/admin/laporan
Menu: Admin Dashboard → Laporan
```

**Fitur**:

-   ✅ Tabel dengan kolom "Quality Metrics"
-   ✅ Tampilan visual: hijau (lolos) / merah (gagal)
-   ✅ Threshold ditampilkan (min: 30 untuk blur, min: 65 untuk brightness)
-   ✅ Filter berdasarkan staff, tanggal, jenis absen
-   ✅ Export CSV dengan quality metrics
-   ✅ Export PDF (belum include quality metrics - bisa ditambahkan)

**Screenshot Tabel**:

```
| Nama Staff | ... | Face Match | Quality Metrics                  | Koordinat |
|------------|-----|------------|----------------------------------|-----------|
| Firman     | ... | 100.0%     | Blur: 45.3 (min: 30) ✅          | ...       |
|            |     |            | Light: 78.5 (min: 65) ✅         |           |
```

### 2. **Export CSV**

```
Lokasi: Download file dari tombol "Export CSV" di halaman laporan
Format: CSV dengan kolom:
  - Quality Blur (Laplacian)
  - Quality Brightness (HSV)
```

**Cara Export**:

1. Buka `/admin/laporan`
2. Set filter (staff, tanggal, jenis)
3. Klik tombol "Export CSV"
4. Buka file dengan Excel/LibreOffice

### 3. **Database Langsung**

```sql
-- Query manual via phpMyAdmin atau Artisan Tinker
SELECT
    users.name,
    attendances.created_at,
    attendances.type,
    attendances.face_score,
    attendances.quality_blur_var,
    attendances.quality_brightness
FROM attendances
INNER JOIN users ON attendances.user_id = users.id
ORDER BY attendances.created_at DESC
LIMIT 20;
```

### 4. **Log File** (Debugging)

```
Lokasi: storage/logs/laravel.log
Cari: "Quality check" atau "qualityCheck"
```

---

## ⚙️ Kapan Preprocessing Dilakukan?

### Timing

**SEBELUM** foto dikirim ke AWS Rekognition, di 2 tempat:

#### 1. **Staff Absen** (`app/Livewire/Staff/Absen.php`)

```
Flow:
Staff klik "Ambil Foto"
   ↓
verifyFace() method dipanggil
   ↓
FaceProcessor::qualityCheck() ← DI SINI (SEBELUM AWS)
   ↓
Jika lolos → AWS SearchFacesByImage
Jika gagal → Stop, tampilkan error
```

#### 2. **Admin Enroll Face** (`app/Livewire/Admin/Manage.php`)

```
Flow:
Admin upload foto wajah staff
   ↓
enrollFace() method dipanggil
   ↓
FaceProcessor::qualityCheck() ← DI SINI (SEBELUM AWS)
   ↓
Jika lolos → AWS IndexFaces (simpan ke collection)
Jika gagal → Stop, tampilkan error
```

---

## 🎯 Threshold yang Digunakan

### Current Settings (`.env`)

```env
# Laplacian variance threshold (blur detection)
# Semakin tinggi = semakin tajam
# Minimum: 30 (turun dari 80 → 50 → 30 untuk webcam)
FACE_MIN_LAPLACE=30

# HSV brightness threshold (light detection)
# Range: 0-255, dimana 0=hitam, 255=putih
# Minimum: 65 (turun dari 60 → 70 → 65)
FACE_MIN_BRIGHTNESS=65

# Dimension requirements
FACE_MIN_WIDTH=200
FACE_MIN_HEIGHT=200
```

### Adjustment History

```
FACE_MIN_LAPLACE:
  80 (initial) → terlalu strict, banyak foto ditolak
  50 (v2)      → masih terlalu ketat untuk webcam
  30 (current) → sweet spot untuk webcam 1280x720

FACE_MIN_BRIGHTNESS:
  60 (initial) → terlalu gelap
  70 (v2)      → terlalu terang, banyak ditolak
  65 (current) → balanced
```

---

## 🔧 Debugging

### Jika Quality Check Gagal

#### 1. **Foto Buram**

Error: "Foto buram atau goyang..."

-   **Penyebab**: Laplacian variance < 30
-   **Solusi**:
    -   Pastikan kamera fokus
    -   Jangan gerakkan kamera/wajah saat foto
    -   Cek resolusi kamera (harus 1280x720 atau lebih)
    -   Bersihkan lensa kamera

#### 2. **Foto Gelap**

Error: "Foto terlalu gelap..."

-   **Penyebab**: HSV brightness < 65
-   **Solusi**:
    -   Tambah cahaya (lampu, dekat jendela)
    -   Hindari backlighting (cahaya dari belakang)
    -   Cek setting brightness kamera

#### 3. **Script Tidak Jalan**

Error: "Quality check script not found"

-   **Penyebab**: Python script tidak ditemukan
-   **Solusi**:

    ```bash
    # Cek file ada
    ls tools/opencv_quality.py

    # Cek Python installed
    python --version

    # Cek OpenCV installed
    python -c "import cv2; print(cv2.__version__)"
    ```

---

## 📊 Interpretasi Nilai

### Laplacian Variance

| Range  | Interpretasi          | Action               |
| ------ | --------------------- | -------------------- |
| < 10   | Sangat buram          | ❌ Ditolak           |
| 10-29  | Buram                 | ❌ Ditolak           |
| 30-50  | Cukup tajam (minimal) | ✅ Lolos             |
| 50-100 | Tajam                 | ✅ Lolos (baik)      |
| > 100  | Sangat tajam          | ✅ Lolos (excellent) |

### HSV Brightness

| Range   | Interpretasi  | Action                |
| ------- | ------------- | --------------------- |
| < 40    | Terlalu gelap | ❌ Ditolak            |
| 40-64   | Gelap         | ❌ Ditolak            |
| 65-100  | Cukup terang  | ✅ Lolos              |
| 100-150 | Terang        | ✅ Lolos (baik)       |
| 150-200 | Sangat terang | ✅ Lolos              |
| > 200   | Overexposed   | ⚠️ Lolos (cek manual) |

---

## 🚀 Testing

### Manual Test via Command Line

```bash
# Activate virtual environment (jika ada)
.venv\Scripts\activate

# Test script langsung
python tools/opencv_quality.py --image "path/to/test/image.jpg"

# Expected output:
# {"ok": true, "laplace": 45.32, "brightness": 78.50, "width": 1280, "height": 720, "message": "..."}
```

### Test via Artisan Tinker

```bash
php artisan tinker

# Test FaceProcessor
$processor = new \App\Services\FaceProcessor();
$result = $processor->qualityCheck('C:\\path\\to\\image.jpg');
dd($result);
```

### Test End-to-End

1. Login sebagai staff
2. Buka `/staff/absen`
3. Klik "Mulai Kamera"
4. Ambil foto
5. Lihat response (success/error dengan metrics)
6. Cek database: `select quality_blur_var, quality_brightness from attendances order by id desc limit 1;`

---

## 📚 Referensi

### OpenCV Documentation

-   Laplacian: https://docs.opencv.org/4.x/d5/db5/tutorial_laplace_operator.html
-   Color Spaces (HSV): https://docs.opencv.org/4.x/df/d9d/tutorial_py_colorspaces.html

### Implementation Files

-   Python Script: `tools/opencv_quality.py`
-   PHP Service: `app/Services/FaceProcessor.php`
-   Livewire (Staff): `app/Livewire/Staff/Absen.php`
-   Livewire (Admin): `app/Livewire/Admin/Manage.php`
-   View (Report): `resources/views/livewire/admin/reports/index.blade.php`
-   Migration: `database/migrations/2025_11_12_150032_add_quality_columns_to_attendances_table.php`
-   Model: `app/Models/Attendance.php`

### Related Stories

-   Story_008.md - OpenCV Quality Gate Implementation
-   Story_008_Implementation.md - Step-by-step implementation

---

## ✅ Kesimpulan

### **Di Mana Melihat Hasil?**

**Jawaban: Halaman Laporan Admin** (`/admin/laporan`)

-   Kolom "Quality Metrics" menampilkan:
    -   Blur (Laplacian Variance) dengan warna hijau/merah
    -   Light (HSV Brightness) dengan warna hijau/merah
    -   Threshold minimal untuk referensi

### **Kapan Preprocessing Dilakukan?**

**Jawaban: SEBELUM AWS Rekognition**

-   Di method `verifyFace()` (Staff Absen)
-   Di method `enrollFace()` (Admin Manage)
-   Sebelum foto dikirim ke AWS untuk hemat biaya API

### **Teknologi yang Digunakan**

1. **OpenCV Python** - Image processing
2. **Symfony Process** - PHP to Python bridge
3. **Livewire** - Real-time UI updates
4. **MySQL** - Persistent storage
5. **AWS Rekognition** - Face verification (SETELAH quality check)

---

**Dibuat**: 13 November 2025
**Author**: GitHub Copilot
**Project**: AbsenKi - Face Recognition Attendance System
