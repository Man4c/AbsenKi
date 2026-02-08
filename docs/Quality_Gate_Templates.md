# Quality Gate Configuration Templates

Template konfigurasi untuk berbagai kondisi lingkungan dan kebutuhan quality control.

---

## 🎯 **Template 1: STRICT (High Security)**

Untuk **lingkungan terkontrol** dengan:

-   Pencahayaan bagus (kantor indoor dengan lampu cukup)
-   Kamera berkualitas baik (minimal 5MP)
-   Butuh security tinggi

```env
# AWS Rekognition Settings
REKOG_COLLECTION=staf_desa_teromu
FACE_THRESHOLD=85              # ← Lebih ketat (85-90)

# Quality Gate (OpenCV)
FACE_MIN_LAPLACE=100           # ← Harus tajam (tidak boleh blur)
FACE_MIN_BRIGHTNESS=80         # ← Harus cukup terang
FACE_MIN_WIDTH=300             # ← Resolusi minimal lebih besar
FACE_MIN_HEIGHT=300
FACE_MIN_BOX_PERCENT=15        # ← Wajah harus cukup besar dalam frame

# Adaptive Laplacian Threshold
FACE_LAPLACE_BASE=120          # ← Baseline tinggi
FACE_LAPLACE_MIN=80            # ← Min threshold tinggi
FACE_LAPLACE_MAX=150
FACE_TARGET_BRIGHTNESS=100

# Toggle fitur
ENABLE_ADAPTIVE_LAPLACE=false  # ← Tidak perlu adaptive, langsung strict
ENABLE_CLIENT_ENHANCE=true

# Client encode
CLIENT_JPEG_QUALITY=0.95       # ← Kualitas JPEG maksimal
CLIENT_UNSHARP_AMOUNT=0.5      # ← Sharpening moderate
CLIENT_UNSHARP_RADIUS=1.0
CLIENT_BRIGHTNESS_DELTA=0.00
CLIENT_CONTRAST_FACTOR=1.05    # ← Contrast minimal
```

**Karakteristik:**

-   ✅ Security maksimal
-   ✅ False positive rendah (orang lain sulit masuk)
-   ⚠️ False negative mungkin tinggi (staff asli kadang ditolak)
-   ⚠️ User experience kurang baik jika kondisi tidak ideal

---

## ⚖️ **Template 2: BALANCED (Recommended for Production)**

Untuk **kondisi normal** dengan:

-   Pencahayaan indoor standar
-   Kamera smartphone modern (3-5MP)
-   Balance antara security & usability

```env
# AWS Rekognition Settings
REKOG_COLLECTION=staf_desa_teromu
FACE_THRESHOLD=80              # ← Standard AWS recommendation

# Quality Gate (OpenCV)
FACE_MIN_LAPLACE=60            # ← Moderate sharpness
FACE_MIN_BRIGHTNESS=65         # ← Toleran terhadap pencahayaan rendah
FACE_MIN_WIDTH=200             # ← Resolusi standar
FACE_MIN_HEIGHT=200
FACE_MIN_BOX_PERCENT=10        # ← Wajah tidak perlu terlalu besar

# Adaptive Laplacian Threshold
FACE_LAPLACE_BASE=100          # ← Baseline normal
FACE_LAPLACE_MIN=50            # ← Min threshold rendah (lebih toleran)
FACE_LAPLACE_MAX=120
FACE_TARGET_BRIGHTNESS=90

# Toggle fitur
ENABLE_ADAPTIVE_LAPLACE=true   # ← Adaptive ON untuk handle variasi
ENABLE_CLIENT_ENHANCE=true     # ← Enhancement ON

# Client encode
CLIENT_JPEG_QUALITY=0.85       # ← Good quality
CLIENT_UNSHARP_AMOUNT=0.6      # ← Moderate sharpening
CLIENT_UNSHARP_RADIUS=1.0
CLIENT_BRIGHTNESS_DELTA=0.00
CLIENT_CONTRAST_FACTOR=1.08    # ← Slight contrast boost
```

**Karakteristik:**

-   ✅ Balance security & usability
-   ✅ User experience baik
-   ✅ False negative & false positive rendah
-   ✅ **Recommended untuk production**

---

## 🟢 **Template 3: LENIENT (Permissive)**

Untuk **kondisi challenging** dengan:

-   Pencahayaan buruk/bervariasi (outdoor, malam hari)
-   Kamera kualitas rendah (<3MP)
-   Prioritas: semua staff bisa absen (usability > security)

```env
# AWS Rekognition Settings
REKOG_COLLECTION=staf_desa_teromu
FACE_THRESHOLD=75              # ← Lebih permisif

# Quality Gate (OpenCV)
FACE_MIN_LAPLACE=30            # ← Toleran terhadap blur
FACE_MIN_BRIGHTNESS=50         # ← Bisa dalam kondisi gelap
FACE_MIN_WIDTH=160             # ← Resolusi minimal kecil
FACE_MIN_HEIGHT=160
FACE_MIN_BOX_PERCENT=8         # ← Wajah bisa kecil dalam frame

# Adaptive Laplacian Threshold
FACE_LAPLACE_BASE=80           # ← Baseline rendah
FACE_LAPLACE_MIN=30            # ← Min threshold sangat rendah
FACE_LAPLACE_MAX=100
FACE_TARGET_BRIGHTNESS=80

# Toggle fitur
ENABLE_ADAPTIVE_LAPLACE=true   # ← Adaptive ON
ENABLE_CLIENT_ENHANCE=true     # ← Enhancement sangat penting

# Client encode
CLIENT_JPEG_QUALITY=0.80       # ← Lower quality OK
CLIENT_UNSHARP_AMOUNT=0.8      # ← Sharpening lebih agresif
CLIENT_UNSHARP_RADIUS=1.2
CLIENT_BRIGHTNESS_DELTA=0.05   # ← Boost brightness sedikit
CLIENT_CONTRAST_FACTOR=1.15    # ← Boost contrast lebih tinggi
```

**Karakteristik:**

-   ✅ User experience sangat baik (hampir semua foto diterima)
-   ✅ False negative sangat rendah (staff asli selalu bisa masuk)
-   ⚠️ Security lebih rendah
-   ⚠️ False positive mungkin naik (foto blur/buram bisa lolos)

---

## 🧪 **Template 4: DEVELOPMENT / TESTING**

Untuk **development & testing** dengan:

-   Tidak ada quality gate (semua foto diterima)
-   Testing fitur tanpa hambatan
-   **JANGAN untuk production!**

```env
# AWS Rekognition Settings
REKOG_COLLECTION=staf_desa_teromu
FACE_THRESHOLD=70              # ← Very lenient

# Quality Gate (OpenCV) - DISABLED
FACE_MIN_LAPLACE=5             # ← Hampir tidak ada filter
FACE_MIN_BRIGHTNESS=20         # ← Gelap pun OK
FACE_MIN_WIDTH=100             # ← Resolusi minimal sangat kecil
FACE_MIN_HEIGHT=100
FACE_MIN_BOX_PERCENT=5         # ← Wajah bisa sangat kecil

# Adaptive Laplacian Threshold
FACE_LAPLACE_BASE=50
FACE_LAPLACE_MIN=10            # ← Sangat permisif
FACE_LAPLACE_MAX=80
FACE_TARGET_BRIGHTNESS=70

# Toggle fitur
ENABLE_ADAPTIVE_LAPLACE=true
ENABLE_CLIENT_ENHANCE=true

# Client encode
CLIENT_JPEG_QUALITY=0.75
CLIENT_UNSHARP_AMOUNT=0.7
CLIENT_UNSHARP_RADIUS=1.0
CLIENT_BRIGHTNESS_DELTA=0.00
CLIENT_CONTRAST_FACTOR=1.10
```

**Karakteristik:**

-   ⚠️ **HANYA untuk testing/development**
-   ✅ Semua foto hampir pasti lolos
-   ❌ No security (foto blur/gelap/orang lain bisa lolos)
-   ❌ **NEVER use in production!**

---

## 🌙 **Template 5: NIGHT MODE**

Untuk **kondisi malam/pencahayaan rendah**:

-   Absensi malam hari
-   Security guard shift malam
-   Outdoor dengan minim cahaya

```env
# AWS Rekognition Settings
REKOG_COLLECTION=staf_desa_teromu
FACE_THRESHOLD=78              # ← Sedikit lebih lenient

# Quality Gate (OpenCV)
FACE_MIN_LAPLACE=40            # ← Toleran blur (karena low light = noisy)
FACE_MIN_BRIGHTNESS=45         # ← Brightness minimal rendah
FACE_MIN_WIDTH=180
FACE_MIN_HEIGHT=180
FACE_MIN_BOX_PERCENT=10

# Adaptive Laplacian Threshold
FACE_LAPLACE_BASE=80
FACE_LAPLACE_MIN=35
FACE_LAPLACE_MAX=100
FACE_TARGET_BRIGHTNESS=75      # ← Target brightness lebih rendah

# Toggle fitur
ENABLE_ADAPTIVE_LAPLACE=true
ENABLE_CLIENT_ENHANCE=true     # ← Enhancement critical untuk night mode

# Client encode
CLIENT_JPEG_QUALITY=0.85
CLIENT_UNSHARP_AMOUNT=0.7      # ← Sharpening lebih tinggi
CLIENT_UNSHARP_RADIUS=1.2
CLIENT_BRIGHTNESS_DELTA=0.10   # ← Boost brightness signifikan
CLIENT_CONTRAST_FACTOR=1.20    # ← Boost contrast tinggi untuk low light
```

**Karakteristik:**

-   ✅ Optimized untuk low light
-   ✅ Brightness & contrast enhancement agresif
-   ⚠️ Noise mungkin meningkat
-   ⚠️ Security sedikit berkurang

---

## 📱 **Template 6: MOBILE OPTIMIZED**

Untuk **smartphone dengan kamera bervariasi**:

-   Staff pakai HP sendiri (kualitas bervariasi)
-   Mix antara flagship & budget phone
-   Internet tidak selalu stabil

```env
# AWS Rekognition Settings
REKOG_COLLECTION=staf_desa_teromu
FACE_THRESHOLD=80

# Quality Gate (OpenCV)
FACE_MIN_LAPLACE=50            # ← Moderate
FACE_MIN_BRIGHTNESS=60         # ← Moderate
FACE_MIN_WIDTH=180             # ← Smaller untuk support budget phone
FACE_MIN_HEIGHT=180
FACE_MIN_BOX_PERCENT=10

# Adaptive Laplacian Threshold
FACE_LAPLACE_BASE=90
FACE_LAPLACE_MIN=40
FACE_LAPLACE_MAX=120
FACE_TARGET_BRIGHTNESS=85

# Toggle fitur
ENABLE_ADAPTIVE_LAPLACE=true
ENABLE_CLIENT_ENHANCE=true

# Client encode
CLIENT_JPEG_QUALITY=0.80       # ← Lower untuk bandwidth
CLIENT_UNSHARP_AMOUNT=0.65
CLIENT_UNSHARP_RADIUS=1.0
CLIENT_BRIGHTNESS_DELTA=0.00
CLIENT_CONTRAST_FACTOR=1.10
```

**Karakteristik:**

-   ✅ Balance untuk berbagai kualitas kamera
-   ✅ File size lebih kecil (JPEG quality 0.80)
-   ✅ Adaptive untuk handle variasi
-   ✅ Good untuk mixed device environment

---

## 🏢 **Template 7: OFFICE INDOOR (Optimal)**

Untuk **kantor modern dengan kondisi ideal**:

-   Lighting konsisten (LED office)
-   Webcam/fixed camera berkualitas
-   Controlled environment

```env
# AWS Rekognition Settings
REKOG_COLLECTION=staf_desa_teromu
FACE_THRESHOLD=82              # ← Slightly higher security

# Quality Gate (OpenCV)
FACE_MIN_LAPLACE=80            # ← Good sharpness required
FACE_MIN_BRIGHTNESS=70         # ← Well-lit environment
FACE_MIN_WIDTH=250             # ← Higher resolution
FACE_MIN_HEIGHT=250
FACE_MIN_BOX_PERCENT=12

# Adaptive Laplacian Threshold
FACE_LAPLACE_BASE=110
FACE_LAPLACE_MIN=70
FACE_LAPLACE_MAX=140
FACE_TARGET_BRIGHTNESS=95

# Toggle fitur
ENABLE_ADAPTIVE_LAPLACE=false  # ← Not needed in consistent lighting
ENABLE_CLIENT_ENHANCE=true

# Client encode
CLIENT_JPEG_QUALITY=0.90       # ← High quality
CLIENT_UNSHARP_AMOUNT=0.5      # ← Moderate sharpening
CLIENT_UNSHARP_RADIUS=1.0
CLIENT_BRIGHTNESS_DELTA=0.00
CLIENT_CONTRAST_FACTOR=1.05    # ← Minimal adjustment
```

**Karakteristik:**

-   ✅ Optimized untuk controlled environment
-   ✅ Consistent quality
-   ✅ Good security & usability balance
-   ✅ Recommended untuk office dengan fixed camera

---

## 🔧 **Cara Menggunakan Template**

### **1. Backup `.env` saat ini**

```bash
cp .env .env.backup
```

### **2. Copy template yang sesuai**

Pilih template sesuai kondisi Anda, copy paste ke `.env`

### **3. Test & Monitor**

```bash
# Test quality check
php artisan tinker
$processor = app(\App\Services\FaceProcessor::class);
$result = $processor->qualityCheck(storage_path('app/test.jpg'));
dd($result);
```

### **4. Fine-tuning**

-   Monitor rejection rate selama 1-2 minggu
-   Jika terlalu banyak reject → Turunkan threshold
-   Jika terlalu mudah lolos → Naikkan threshold

---

## 📊 **Comparison Table**

| Template             | Security   | Usability  | Rejection Rate | Best For                     |
| -------------------- | ---------- | ---------- | -------------- | ---------------------------- |
| **Strict**           | ⭐⭐⭐⭐⭐ | ⭐⭐       | High           | High security area           |
| **Balanced**         | ⭐⭐⭐⭐   | ⭐⭐⭐⭐   | Low-Medium     | **Production (Recommended)** |
| **Lenient**          | ⭐⭐       | ⭐⭐⭐⭐⭐ | Very Low       | Challenging conditions       |
| **Development**      | ⭐         | ⭐⭐⭐⭐⭐ | Almost 0       | Testing only                 |
| **Night Mode**       | ⭐⭐⭐     | ⭐⭐⭐⭐   | Medium         | Low light conditions         |
| **Mobile Optimized** | ⭐⭐⭐⭐   | ⭐⭐⭐⭐   | Low            | Mixed devices                |
| **Office Indoor**    | ⭐⭐⭐⭐   | ⭐⭐⭐⭐   | Low            | Controlled environment       |

---

## 💡 **Tips Pemilihan Template**

### **Mulai dari Balanced**, lalu adjust:

1. **Monitor selama 1 minggu:**

    ```sql
    -- Cek rejection rate
    SELECT
        DATE(created_at) as date,
        COUNT(*) as total_attempts,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        (SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) * 100.0 / COUNT(*)) as rejection_rate
    FROM attendance_logs
    GROUP BY DATE(created_at)
    ORDER BY date DESC;
    ```

2. **Jika rejection rate > 20%** → Pakai **Lenient** atau **Mobile Optimized**
3. **Jika rejection rate < 5%** dan butuh security lebih → Pakai **Strict**
4. **Jika kondisi pencahayaan buruk** → Pakai **Night Mode**
5. **Jika mixed device (HP beragam)** → Pakai **Mobile Optimized**

### **Seasonal Adjustment:**

```bash
# Musim hujan / mendung
# → Lower brightness threshold, enable client enhance

# Siang terik outdoor
# → Higher brightness threshold, lower contrast

# Shift malam
# → Switch to Night Mode template
```

---

## 🔍 **Debug & Monitoring**

### **Log quality metrics**

Tambahkan di `FaceProcessor.php`:

```php
\Log::info('Quality check result', [
    'laplace' => $result['laplace'],
    'brightness' => $result['brightness'],
    'threshold_laplace' => $minLaplace,
    'threshold_brightness' => $minBright,
    'passed' => $result['success'],
]);
```

### **Dashboard monitoring**

Buat endpoint untuk melihat metrics:

```php
// routes/web.php (admin only)
Route::get('/admin/quality-metrics', function() {
    $logs = \DB::table('attendance_logs')
        ->select('quality_blur', 'quality_brightness', 'status')
        ->whereNotNull('quality_blur')
        ->orderBy('created_at', 'desc')
        ->limit(100)
        ->get();

    return view('admin.quality-metrics', compact('logs'));
});
```

---

## 🎯 **Rekomendasi untuk Desa Teromu**

Berdasarkan nama collection `staf_desa_teromu`, kemungkinan:

-   Lingkungan: Kantor desa (outdoor + indoor)
-   Device: Mixed (HP staff bervariasi)
-   Kondisi: Pencahayaan bisa bervariasi

**Recommended Template: Mobile Optimized** atau **Balanced**

Start dengan **Balanced**, monitor 1-2 minggu, lalu adjust sesuai feedback staff.
