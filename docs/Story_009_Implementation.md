# Story_009 Implementation — Entri Offsite oleh Admin

**Status**: ✅ Selesai Diimplementasi  
**Tanggal**: 29 November 2025  
**Developer**: Senior Laravel Developer

---

## 📋 Summary Perubahan

Fitur ini memungkinkan Admin untuk mencatat absensi offsite (di luar kantor) untuk staff tanpa memerlukan validasi geofence atau face recognition. Entri langsung tersimpan dengan status `success` dan `is_offsite=true`.

---

## 🗄️ Perubahan Database

### Migration: `2025_11_29_000001_add_offsite_fields_to_attendances_table.php`

**Kolom baru di tabel `attendances`:**

-   `is_offsite` (boolean, default: false) — Flag untuk menandai entri offsite
-   `offsite_location_text` (string 255, nullable) — Alamat/lokasi offsite
-   `offsite_coords` (string 64, nullable) — Koordinat dalam format "lat,lng"
-   `offsite_reason` (text, nullable) — Alasan/keterangan kerja offsite
-   `evidence_path` (string 255, nullable) — Path file bukti (JPG/PNG/PDF)
-   `created_by_admin_id` (unsignedBigInteger, nullable) — FK ke users.id (admin yang membuat)

**Foreign Key:**

-   `created_by_admin_id` → `users.id` dengan `onDelete('set null')`

**Indexes:**

-   `is_offsite` — untuk filter cepat
-   `created_by_admin_id` — untuk audit trail

**Status**: ✅ Migration berhasil dijalankan

---

## 📦 Model Updates

### `app/Models/Attendance.php`

**Perubahan:**

-   ✅ Tambah 6 field baru ke `$fillable`
-   ✅ Tambah cast `is_offsite` → boolean
-   ✅ Tambah relationship `createdByAdmin()` → belongsTo User

### `app/Models/User.php`

**Perubahan:**

-   ✅ Tambah relationship `offsiteAttendancesCreated()` → hasMany Attendance

---

## 🎨 Frontend Components

### 1. Livewire Component: `CreateOffsite.php`

**Lokasi**: `app/Livewire/Admin/Attendance/CreateOffsite.php`

**Fitur:**

-   Form modal untuk input entri offsite
-   Validasi komprehensif (staff, tanggal, lokasi, koordinat, file)
-   Upload evidence dengan validation (JPG/PNG/PDF, max 5MB)
-   Anti-duplikasi: cek entri "Masuk" di tanggal yang sama
-   Auto-save dengan status `success` dan `is_offsite=true`
-   Event `offsite-created` untuk refresh parent component

**Validasi:**

-   Staff: required, exists, role=staff
-   Tanggal: required, date, max=today
-   Waktu: required, format HH:MM
-   Jenis: required, in:in,out,hadir
-   Lokasi: required, string, max:255
-   Koordinat: nullable, regex `/^-?\d+(\.\d+)?,-?\d+(\.\d+)?$/`
-   Alasan: nullable, string, max:1000
-   Evidence: nullable, file, mimes:jpg,jpeg,png,pdf, max:5120KB

### 2. View: `create-offsite.blade.php`

**Lokasi**: `resources/views/livewire/admin/attendance/create-offsite.blade.php`

**UI Elements:**

-   Modal overlay dengan backdrop
-   Form fields: Staff (select), Tanggal, Jam, Jenis (radio), Lokasi (textarea), Koordinat, Alasan, Evidence (file upload)
-   Loading states untuk file upload dan save
-   Error messages dengan styling Tailwind
-   Responsive design (mobile-friendly)

### 3. Updated: `Reports/Index.php` Component

**Lokasi**: `app/Livewire/Admin/Reports/Index.php`

**Perubahan:**

-   ✅ Tambah property `$locationType` (all/office/offsite)
-   ✅ Tambah listener `offsite-created` untuk refresh data
-   ✅ Update `getRecordsQuery()` dengan filter locationType
-   ✅ Update export CSV dengan 7 kolom baru
-   ✅ Update export PDF dengan parameter locationType

### 4. Updated: Reports Index View

**Lokasi**: `resources/views/livewire/admin/reports/index.blade.php`

**Perubahan:**

-   ✅ Tambah filter dropdown "Jenis Lokasi" (Semua/Di Kantor/Offsite)
-   ✅ Tambah tombol "Tambah Entri Offsite" (purple button)
-   ✅ Tambah kolom "Lokasi" di tabel
-   ✅ Tampilkan badge "🌍 Offsite" dengan warna purple
-   ✅ Tampilkan lokasi offsite di bawah badge
-   ✅ Include component CreateOffsite di bawah tabel
-   ✅ Success message flash setelah save

---

## 📄 Export Updates

### CSV Export

**Kolom baru:**

1. Jenis Lokasi (Offsite/Di Kantor)
2. Lokasi Offsite
3. Koordinat Offsite
4. Alasan Offsite
5. Bukti File (path)
6. Dibuat Oleh Admin (nama admin)

### PDF Export

**File**: `resources/views/livewire/admin/reports/pdf.blade.php`

**Perubahan:**

-   ✅ Tambah badge style `.badge-offsite` (purple)
-   ✅ Tambah kolom "Lokasi" di tabel PDF
-   ✅ Tampilkan badge Offsite dan alamat
-   ✅ Filter info mencakup jenis lokasi

**File**: `app/Http/Controllers/Admin/ReportController.php`

**Perubahan:**

-   ✅ Tambah parameter `locationType` dari request
-   ✅ Include relasi `createdByAdmin` di query
-   ✅ Apply filter locationType di query
-   ✅ Tambah info locationType di PDF header

---

## 🔒 Security & Access Control

-   ✅ Route hanya accessible oleh role `admin` (via middleware existing)
-   ✅ File evidence disimpan di `storage/app/private/offsite-evidence/{userId}/`
-   ✅ Field `created_by_admin_id` auto-filled dengan `auth()->id()`
-   ✅ Validasi role staff sebelum save

---

## 📊 Business Logic

### Aturan Anti-Duplikasi

Jika type = "in" atau "hadir", sistem akan cek apakah sudah ada entri "Masuk" di tanggal yang sama untuk staff tersebut. Jika ada, akan muncul error validation.

### Jenis Absensi "Hadir"

Ketika admin pilih "Hadir (Full Day)", sistem akan menyimpan 1 entri dengan `type='in'` yang merepresentasikan kehadiran penuh hari.

### Data yang Tersimpan

-   `is_offsite` = true
-   `status` = success
-   `geo_ok` = false (karena tidak ada validasi geofence)
-   `face_score` = null (karena tidak ada face check)
-   `device_info` = "Offsite entry by admin"
-   `created_at` = kombinasi dari tanggal + jam yang dipilih
-   `created_by_admin_id` = ID admin yang login

---

## 🧪 Testing Checklist

### ✅ Functional Testing

-   [x] Admin dapat membuka form Tambah Entri Offsite
-   [x] Form validasi bekerja dengan benar
-   [x] Upload file JPG, PNG, PDF berhasil
-   [x] Upload file > 5MB ditolak
-   [x] Koordinat format invalid ditolak
-   [x] Anti-duplikasi mencegah double entry "Masuk"
-   [x] Entri tersimpan dengan `is_offsite=true`
-   [x] Badge "Offsite" muncul di tabel
-   [x] Filter "Offsite" menampilkan hanya entri offsite
-   [x] Filter "Di Kantor" menyembunyikan entri offsite
-   [x] Export CSV menyertakan kolom offsite
-   [x] Export PDF menyertakan kolom offsite
-   [x] Success message muncul setelah save
-   [x] Modal menutup otomatis setelah save
-   [x] Tabel refresh otomatis setelah save

### ⏭️ Security Testing (To Be Done)

-   [ ] Staff tidak dapat akses form offsite (403/redirect)
-   [ ] File evidence tidak dapat diakses langsung via URL
-   [ ] Admin lain dapat melihat entri yang dibuat admin berbeda

---

## 📁 Files Created/Modified

### Files Baru (7 files):

1. `database/migrations/2025_11_29_000001_add_offsite_fields_to_attendances_table.php`
2. `app/Livewire/Admin/Attendance/CreateOffsite.php`
3. `resources/views/livewire/admin/attendance/create-offsite.blade.php`
4. `docs/Story_009_Implementation.md`

### Files Diubah (6 files):

1. `app/Models/Attendance.php`
2. `app/Models/User.php`
3. `app/Livewire/Admin/Reports/Index.php`
4. `resources/views/livewire/admin/reports/index.blade.php`
5. `resources/views/livewire/admin/reports/pdf.blade.php`
6. `app/Http/Controllers/Admin/ReportController.php`

---

## 🚀 Cara Menggunakan Fitur

### 1. Membuat Entri Offsite

1. Login sebagai Admin
2. Buka menu **Laporan Absensi**
3. Klik tombol **"Tambah Entri Offsite"** (button purple di kanan atas)
4. Isi form:
    - Pilih Staff (searchable dropdown)
    - Pilih Tanggal & Jam
    - Pilih Jenis: Masuk / Keluar / Hadir
    - Isi Lokasi/Alamat (wajib)
    - Isi Koordinat (opsional, format: -6.200000,106.816666)
    - Isi Alasan (opsional)
    - Upload Bukti (opsional, max 5MB)
5. Klik **"Simpan Entri Offsite"**
6. Modal akan menutup otomatis dan data muncul di tabel dengan badge Offsite

### 2. Filter Entri Offsite

1. Di halaman Laporan, gunakan dropdown **"Jenis Lokasi"**
2. Pilih:
    - **Semua** — tampilkan semua entri (kantor + offsite)
    - **Di Kantor** — hanya entri reguler dengan geofence
    - **Offsite** — hanya entri offsite
3. Klik **"Terapkan Filter"**

### 3. Export Data

-   **CSV**: Klik tombol "Export CSV" — kolom offsite otomatis included
-   **PDF**: Klik tombol "Export PDF" — badge offsite dan lokasi tercantum di PDF

---

## 🐛 Known Issues

### False Positive dari Static Analysis:

1. **Tailwind CSS Conflicts** di `create-offsite.blade.php`

    - Issue: `border-gray-300` dan `border-red-500` dianggap conflict
    - Impact: **NONE** — ini adalah pattern conditional class standar Laravel
    - Solution: Ignore, tidak perlu fix

2. **auth()->id() undefined method**
    - Issue: Static analyzer tidak recognize helper Laravel
    - Impact: **NONE** — helper ini valid dan tested
    - Solution: Ignore, tidak perlu fix

---

## 🔄 Rollback Plan

Jika perlu rollback fitur ini:

```bash
# Rollback migration
php artisan migrate:rollback --step=1

# Hapus files baru
rm app/Livewire/Admin/Attendance/CreateOffsite.php
rm resources/views/livewire/admin/attendance/create-offsite.blade.php

# Restore file yang diubah dari git
git checkout app/Models/Attendance.php
git checkout app/Models/User.php
git checkout app/Livewire/Admin/Reports/Index.php
git checkout resources/views/livewire/admin/reports/index.blade.php
git checkout resources/views/livewire/admin/reports/pdf.blade.php
git checkout app/Http/Controllers/Admin/ReportController.php
```

Data lama di tabel attendances tetap aman karena kita hanya menambah kolom baru (tidak mengubah kolom existing).

---

## 📝 Next Steps (Story_010)

Fitur yang **belum** diimplementasi di Story_009 ini (sesuai scope):

-   ❌ Alur Pengajuan Offsite oleh Staff (form staff untuk request offsite)
-   ❌ Flow Approval/Reject oleh Admin
-   ❌ Status Pending untuk offsite request
-   ❌ Notifikasi ke Admin saat ada pengajuan baru
-   ❌ History approval/rejection

Semua fitur di atas akan dikerjakan di **Story_010**.

---

## ✅ Acceptance Criteria Status

| Kriteria                                                   | Status |
| ---------------------------------------------------------- | ------ |
| Admin dapat membuka form Tambah Entri Offsite              | ✅     |
| Entri tersimpan dengan `is_offsite=true` dan field terkait | ✅     |
| Badge Offsite muncul di laporan                            | ✅     |
| Filter laporan dapat menampilkan hanya entri Offsite       | ✅     |
| Export CSV/PDF menyertakan kolom offsite                   | ✅     |
| Tidak ada validasi geofence/face-check di jalur ini        | ✅     |
| `created_by_admin_id` tercatat                             | ✅     |

**Status**: ✅ **ALL ACCEPTANCE CRITERIA MET**

---

## 🎉 Conclusion

Implementasi Story_009 telah selesai dengan sukses. Semua fitur core berfungsi dengan baik:

-   ✅ Database schema updated
-   ✅ Backend logic implemented
-   ✅ Frontend UI completed
-   ✅ Export (CSV/PDF) updated
-   ✅ Migration successful
-   ✅ No breaking changes to existing features

Fitur ini siap untuk **User Acceptance Testing (UAT)**.
