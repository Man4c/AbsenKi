# Fix: Multiple Face Detection dengan Bounding Box Labels

## Masalah

Ketika sistem mendeteksi **2 wajah atau lebih** dalam satu snapshot:

-   ❌ Hanya menampilkan 1 bounding box (wajah pertama saja)
-   ❌ Tidak menampilkan nama user pada label
-   ❌ Tidak ada indikasi visual untuk wajah lain yang terdeteksi
-   ❌ User tidak tahu wajah mana yang cocok/tidak cocok

## Solusi Implementasi

### 1. **FaceVerificationService.php**

Mengubah return value dari `verifyFace()`:

**SEBELUM:**

```php
'boundingBox' => array|null  // Hanya 1 bounding box
```

**SESUDAH:**

```php
'boundingBoxes' => [         // Array of bounding boxes
    [
        'box' => [...],      // AWS BoundingBox
        'label' => 'string', // Label untuk ditampilkan
        'type' => 'string',  // matched|wrong_user|other|unknown
        'score' => float     // Similarity score (nullable)
    ],
    ...
]
```

**Type Classifications:**

-   `matched` - Wajah cocok dengan user yang login (✅ Hijau)
-   `wrong_user` - Wajah cocok tapi bukan milik user yang login (❌ Merah)
-   `other` - Wajah lain yang terdeteksi tapi tidak cocok dengan database (🟠 Orange)
-   `unknown` - Tidak ada match sama sekali (⚪ Abu-abu)

### 2. **Livewire Absen.php**

-   Mengubah property `$boundingBox` → `$boundingBoxes` (array)
-   Mengubah event `boundingBoxUpdated` → `boundingBoxesUpdated`
-   Pass array of bounding boxes ke frontend

### 3. **absen.blade.php (JavaScript)**

#### Fungsi Baru:

```javascript
drawBoundingBoxes(canvas, image, boundingBoxes);
```

Menggambar **semua bounding boxes** dengan warna berbeda berdasarkan type:

| Type         | Warna      | Arti                           |
| ------------ | ---------- | ------------------------------ |
| `matched`    | 🟢 Hijau   | Wajah cocok dengan user        |
| `wrong_user` | 🔴 Merah   | Wajah orang lain terdeteksi    |
| `other`      | 🟠 Orange  | Wajah lain (tidak di database) |
| `unknown`    | ⚪ Abu-abu | Tidak dikenali                 |

#### Event Listeners:

-   **NEW:** `boundingBoxesUpdated` - Handle multiple faces
-   **LEGACY:** `boundingBoxUpdated` - Backward compatibility untuk single face

## Hasil

### Sebelum Fix:

```
Screenshot:
┌─────────────────────┐
│  👤      👤         │  <- 2 wajah terdeteksi
│ [Wajah]             │  <- Hanya 1 box, label "Wajah"
│                     │
└─────────────────────┘

Error: "Wajah terdeteksi bukan milik Anda"
```

### Sesudah Fix:

```
Screenshot:
┌──────────────────────────────┐
│  👤              👤           │
│ [Bukan Wajah    [Wajah Lain] │
│  Anda (99%)]     (-)          │
│  🔴 Red          🟠 Orange    │
└──────────────────────────────┘

Error: "Wajah terdeteksi bukan milik Anda.
       Silakan gunakan wajah Anda sendiri.
       (Terdeteksi 2 wajah, pastikan hanya
       wajah Anda yang terlihat)"
```

## Manfaat

1. ✅ **User Experience Lebih Baik** - User langsung tahu ada berapa wajah yang terdeteksi
2. ✅ **Debugging Lebih Mudah** - Jelas mana wajah yang matched/unmatched
3. ✅ **Security Lebih Baik** - Terlihat jelas jika ada orang lain dalam frame
4. ✅ **Visual Feedback** - Warna berbeda untuk status berbeda
5. ✅ **Backward Compatible** - Masih support single face detection

## Testing

### Scenario 1: Satu Wajah (User Sendiri)

-   ✅ Tampil 1 bounding box hijau
-   ✅ Label: "Nama User (99%)"
-   ✅ Verifikasi berhasil

### Scenario 2: Dua Wajah (User + Orang Lain)

-   ✅ Tampil 2 bounding boxes
-   ✅ Wajah user: Hijau (matched) atau Merah (wrong_user)
-   ✅ Wajah lain: Orange (other)
-   ❌ Verifikasi gagal dengan message yang jelas

### Scenario 3: Tidak Ada Wajah

-   ✅ Tidak ada bounding box
-   ❌ Error: "Tidak ada wajah terdeteksi"

## Files Modified

1. `app/Services/FaceVerificationService.php`
    - Modified `verifyFace()` method
2. `app/Livewire/Staff/Absen.php`
    - Changed `$boundingBox` to `$boundingBoxes`
    - Updated event dispatch
3. `resources/views/livewire/staff/absen.blade.php`
    - Added `drawBoundingBoxes()` function
    - Added `boundingBoxesUpdated` event listener
    - Updated location check redraw logic

## Log Example

### Multiple Faces Detected:

```log
[2025-12-06 21:47:13] local.WARNING: Multiple faces detected during verification
{"user_id":3,"face_count":2}

[2025-12-06 21:47:14] local.WARNING: Face verification failed: Matched face does not belong to user
{"user_id":3,"matched_user_id":2,"matched_face_id":"b20277c3-...",
 "user_face_ids":["9e957141-...","d1c09ced-..."],"similarity":99.99712371826172}
```

## Browser Console Output

```javascript
🎯 Multiple bounding boxes event received: {...}
📦 Extracted data: {boundingBoxes: Array(2), faceCount: 2}
📐 BoundingBoxes: Array(2)
  [0]: {box: {...}, label: "Bukan Wajah Anda", type: "wrong_user", score: 99.99}
  [1]: {box: {...}, label: "Wajah Lain", type: "other", score: null}
🎨 drawBoundingBoxes called with 2 boxes
📦 Box 1: {x: 512.5, y: 145.2, width: 156.3, height: 312.8, type: "wrong_user", label: "Bukan Wajah Anda"}
✅ Box 1 drawn: Bukan Wajah Anda (100%)
📦 Box 2: {x: 40.1, y: 160.5, width: 180.2, height: 298.4, type: "other", label: "Wajah Lain"}
✅ Box 2 drawn: Wajah Lain
✅ All bounding boxes drawn
```

---

**Date:** 2025-12-06  
**Author:** GitHub Copilot  
**Status:** ✅ Completed & Tested
