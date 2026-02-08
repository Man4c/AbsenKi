Story_002 — Geofencing (Validasi Lokasi Absensi)
Tujuan
• Sistem bisa menentukan apakah posisi staff saat absen ada di dalam area kantor yang valid atau tidak.
• Admin bisa mendefinisikan / mengubah area kantor (geofence).
• Halaman absen staff bisa cek lokasi dan memberi status Lokasi OK atau Lokasi Tidak Valid.
• Informasi lokasi (lat, lng, geo_ok) tercatat saat absensi.
Tujuan ini penting karena absensi hanya dianggap sah jika dilakukan di lokasi yang diizinkan.

---

Scope Story_002
Yang dibangun di tahap ini:

1. Tabel geofences
   o Simpan polygon area kantor dalam bentuk GeoJSON.
   o Hanya ada 1 geofence aktif yang dipakai saat absen (bisa lebih nanti, tapi minimal 1).
2. Halaman manajemen geofence (admin)
   o Admin bisa melihat polygon aktif saat ini.
   o Admin bisa menyimpan / mengupdate polygon kantor (format GeoJSON Polygon).
   o Admin bisa mengaktifkan / menonaktifkan polygon.
   (UI peta boleh masih sangat sederhana di tahap ini: cukup textarea JSON dulu. Map interaktif pake leaflet/bing/google map bisa masuk Story_002.5 kalau mau, tapi tidak wajib di tahap dasar.)
3. Cek lokasi pada halaman absen staff
   o Di halaman /staff/absen, staff bisa klik tombol “Tes GPS”.
   o Frontend akan:
    Ambil koordinat lat,lng via navigator.geolocation.getCurrentPosition.
    Ambil polygon kantor (GeoJSON) dari backend (AJAX / fetch endpoint).
    Jalankan turf.booleanPointInPolygon([lng, lat], polygonGeoJSON) di browser.
    Tampilkan status: “Lokasi OK ✅” atau “Di luar area ❌”.
   o Status geo_ok ini harus bisa dikirim balik ke backend saat proses absen nanti (Story_004).
4. Endpoint backend untuk kirim polygon
   o Backend menyediakan endpoint (misal /api/geofence/active) yang mengembalikan polygon geofence aktif dalam bentuk GeoJSON.
   o Endpoint ini hanya dipakai di halaman absen staff.
5. Kolom lokasi di attendance
   o Pastikan tabel attendance sudah punya kolom:
    lat, lng
    geo_ok (boolean)
   o Kalau belum ada, migrasi sekarang.
   Catatan:
   • Story_002 belum melakukan “simpan absensi final”. Itu akan masuk Story_004.
   • Di Story_002 kita baru memastikan sistem bisa:
   o deteksi lokasi staff,
   o validasi geofence,
   o kirim status lokasi dengan benar.

---

Acceptance Criteria

1. Tabel geofences
   o Tabel geofences tersedia dengan kolom:
    id
    name (nama lokasi, contoh: “Kantor Desa Teromu”)
    polygon_geojson (JSON Polygon)
    is_active (boolean)
    timestamps
   o Minimal 1 data geofence aktif bisa disimpan lewat fitur admin.
2. Admin bisa atur area kantor
   o Ada halaman/admin component (contoh Livewire: Admin/Geofence) untuk:
    Melihat data geofence aktif.
    Mengedit polygon_geojson (sementara boleh pakai textarea input JSON).
    Menandai is_active true/false.
   o Saat disimpan, data masuk ke tabel geofences.
3. Endpoint polygon aktif tersedia
   o Ada endpoint (misal GET /api/geofence/active) yang mengembalikan geofence aktif berupa GeoJSON Polygon.
   o Hanya mengembalikan 1 polygon yang is_active = true.
   o Response bentuk JSON: { "name": "...", "polygon": { ...geojson polygon... } }
4. Halaman /staff/absen bisa cek lokasi
   o Saat staff klik tombol “Tes GPS”:
    Browser dapat lat,lng.
    Browser fetch polygon_geojson dari backend (/api/geofence/active).
    Browser jalankan turf.booleanPointInPolygon([lng, lat], polygon).
    Browser menampilkan hasil:
    “Lokasi OK ✅” jika true
    “Di luar area ❌” jika false
5. Informasi koordinat tampil
   o Halaman /staff/absen menampilkan:
    nilai latitude & longitude hasil deteksi
   o Ini digunakan nanti untuk pencatatan di attendance.
6. Keamanan dasar
   o /admin geofence editor hanya bisa dibuka oleh role admin.
   o /staff/absen hanya bisa dibuka oleh role staff.

---

QA Checklist
• Tabel geofences ada di database dan migrasinya sukses.
• Bisa insert minimal satu data geofence aktif (secara manual lewat form admin atau seeder).
• Halaman admin geofence (misal /admin/geofence) hanya bisa diakses oleh admin dan menampilkan polygon dalam bentuk JSON.
• Admin bisa update polygon_geojson dan is_active.
• Endpoint GET /api/geofence/active mengembalikan polygon aktif dalam format JSON valid.
• Di halaman /staff/absen:
o Klik “Tes GPS” memunculkan koordinat lat/lng.
o Status lokasi muncul: “Lokasi OK ✅” atau “Di luar area ❌”.
o turf.js dipakai untuk booleanPointInPolygon (di frontend).
• Role check:
o Admin tidak bisa akses /staff/absen.
o Staff tidak bisa akses halaman admin geofence.
