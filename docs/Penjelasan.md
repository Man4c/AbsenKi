1. Tujuan Singkat
   • Bikin web absensi staff yang valid pakai 2 (sebenarnya 3) cek:
   Lokasi (geofencing: harus di area kantor)
   Kehadiran wajah asli di kamera saat ini (dicek pakai OpenCV → liveness / bukan foto boongan)
   Identitas wajah (pencocokan wajah dengan AWS Rekognition)
   • Dipakai di HP & Laptop (browser: minta izin kamera & GPS).
   • Admin yang daftarkan wajah staff. staff yang melakukan absen mandiri.

---

2. Aktor & Role
   • Admin:
    - Kelola staff (CRUD akun staff)
    - Daftarkan wajah staff (upload 3–5 foto per staff)
    - Kelola area kantor (polygon geofence)
    - Lihat laporan & export
      • staff:
    - Absen masuk/keluar (wajib kamera + GPS)
    - Lihat riwayat absensi miliknya sendiri
      Role di DB: admin, staff.

---

3. Fitur Utama
1. Auth & Role (Volt + Livewire)
1. Geofencing (cek titik GPS di polygon kantor)
1. Pengenalan Wajah:
   o OpenCV: deteksi wajah + liveness dasar (pastikan wajah hidup, bukan foto)
   o AWS Rekognition: cocokkan wajah dengan data staff yang terdaftar
1. Absen IN / OUT (gabungan geofence + wajah)
1. Laporan (harian/bulanan, filter staff, export CSV/PDF)

---

4. Alur Kerja (Flow)
   a. Admin Enroll Wajah staff
   b. Admin buat data akun staff.
   c. Admin upload 3–5 foto wajah staff (wajah jelas dari beberapa sudut/cahaya).
   d. Backend proses tiap foto:
   o OpenCV membaca file → deteksi wajah → crop wajah yang bersih → validasi bahwa itu wajah manusia (bukan kosong/blurry).
   o Hasil crop wajah dikirim sebagai Image Bytes ke AWS Rekognition IndexFaces(CollectionId).
   o AWS kembalikan FaceId.
   e. Sistem simpan FaceId + user_id ke tabel face_profiles.
   staff Melakukan Absen
   f. staff buka halaman absen → sistem minta izin kamera & lokasi.
   g. Browser kirim frame kamera (snapshot) ke backend.
   h. Backend pakai OpenCV:
   o Pastikan ada wajah di frame.
   o Pastikan wajah kelihatan jelas (tidak super gelap / kabur).
   o (Opsional liveness) Bisa minta staff kedip / gerak kepala sedikit dan ambil beberapa frame → cegah pakai foto/gambar.
   o Jika lulus, backend crop wajah bersih.
   i. Backend kirim crop wajah (Bytes) ke AWS Rekognition SearchFacesByImage pada collection staff.
   o AWS balikin:
    FaceId
    Similarity (0–100)
    user mana yang paling mirip.
   j. Browser juga kirim lokasi GPS staff.
   o Di sisi browser gunakan turf.js untuk cek apakah titik berada di dalam polygon geofence aktif.
   o (Opsional) Backend juga bisa verifikasi ulang.
   k. Keputusan final absensi:
   o Similarity >= FACE_THRESHOLD (mis: 80)
   o FaceId cocok dengan user yang login
   o Lokasi berada di area geofence
   → Catat absen sebagai sukses (masuk/keluar) ke tabel attendance.

---

5. Tech Stack
   • Backend: Laravel 12 + Volt (Livewire)
   • Auth: bawaan Volt
   • DB: MySQL
   • Storage file: Local disk (default Laravel), tanpa S3
   • Computer Vision / Liveness: OpenCV (service Python kecil atau modul yang kita panggil)
   • Fungsi: deteksi wajah, crop wajah, cek wajah hidup (bukan foto diam)
   • Face Recognition (identitas): AWS Rekognition (pakai Image Bytes, bukan S3Object)
   • Geofencing: turf.js (booleanPointInPolygon)
   • UI: Blade + Tailwind (Volt starter)
   • Build assets: Vite
   • Runtime dev: PHP 8.2+ (Laragon)
   • Production: HTTPS wajib (browser butuh https untuk akses kamera & GPS dengan aman)

---

6. Integrasi AWS (ringkas)
   • Kita buat 1 IAM User khusus (programmatic access) yang hanya boleh pakai Rekognition.
   • Kita buat 1 Rekognition Collection, misalnya: staf_desa_teromu.
   • Saat enroll:
   • Kirim crop wajah (Bytes) ke IndexFaces(CollectionId='staf_desa_teromu') → dapat FaceId.
   • Simpan FaceId ke DB dan hubungkan ke user (staff).
   • Saat verifikasi absen:
   • Kirim crop wajah (Bytes) ke SearchFacesByImage di collection yang sama.
   • Ambil hasil kemiripan (Similarity).
   🔥 Catatan penting:
   • Tidak pakai S3.
   • Foto mentah tidak langsung dikirim ke AWS.
   Cuma hasil crop dari OpenCV yang sudah dicek liveness.

---

7. Struktur Database (tabel inti)
   users
   • id
   • name
   • email
   • password
   • role (admin | staff)
   face_profiles
   • id
   • user_id (relasi ke users)
   • provider ('aws')
   • collection_id (mis. staf_desa_teromu)
   • face_id (FaceId dari Rekognition untuk staff ini)
   • image_path (opsional, path foto enrol yang disimpan lokal untuk audit)
   • created_at
   geofences
   • id
   • name
   • polygon_geojson (JSON: Polygon area kantor)
   • is_active (bool)
   attendance
   • id
   • user_id
   • type ('in' | 'out')
   • lat, lng
   • geo_ok (bool) → apakah titik lokasi valid
   • face_score (float) → similarity score dari Rekognition
   • status ('success' | 'fail')
   • device_info (JSON) → info browser/device saat absen
   • created_at
   Kenapa simpan FaceId, bukan embedding vector sendiri?
   → Karena embedding wajah dikelola internal oleh AWS Rekognition. Kita cukup simpan FaceId supaya nanti bisa dihubungkan balik ke user.

---

8. Endpoint / Modul (kontrak sederhana)
   Admin
   • POST /admin/staff
   • Buat akun staff baru (nama, email, role='staff', password awal)
   • POST /admin/staff/{id}/faces
   • Admin upload 3–5 foto wajah staff.
   • Backend:
   o OpenCV deteksi + crop + validasi wajah
   o Kirim crop (Bytes) ke IndexFaces
   o Simpan face_id di tabel face_profiles.
   • GET|POST /admin/geofence
   • Admin menambahkan / mengupdate polygon GeoJSON area kantor aktif
   • GET /admin/laporan?user_id&from&to&export=csv|pdf
   • Admin lihat rekap absensi dan bisa export.
   staff
   • GET /staff/absen
   • Halaman untuk absen masuk / keluar
   • POST /staff/absen/verify-geo
   • Kirim lat,lng
   • Balikkan geo_ok
   • POST /staff/absen/verify-face
   • Kirim snapshot (Bytes) dari kamera HP/laptop
   • Backend:
   o OpenCV cek wajah/liveness + crop
   o Rekognition SearchFacesByImage
   o Response: face_score, match_user_id
   • POST /staff/absen/commit
   • Backend finalisasi absensi:
   o Cek geo_ok
   o Cek face_score >= FACE_THRESHOLD
   o Cek match_user_id == auth()->id()
   o Simpan baris attendance

---

9. Geofencing (detail)
   • Admin menyimpan area kantor sebagai GeoJSON Polygon (format koordinat WGS84).
   • Di browser staff saat absen:
   • Ambil lokasi via navigator.geolocation.getCurrentPosition.
   • Cek turf.booleanPointInPolygon([lng, lat], polygonGeoJSON).
   • (Opsional) cek jarak ke titik pusat pakai turf.distance, misal toleransi <= 50 m.
   • Hasil pengecekan ini akan jadi geo_ok = true/false.

---

10. Face Recognition (detail)
    Tahap 1 — OpenCV (lokal / service internal)
    • Deteksi wajah dari frame kamera.
    • Crop wajah agar posisinya proper.
    • Cek kualitas:
    • jangan blur
    • jangan gelap total
    • Cek liveness sederhana:
    • Minta staff kedip / miringkan kepala sebentar
    • Ambil beberapa frame acak
    Jika gagal di tahap ini → langsung tolak, jangan lanjut.
    Tahap 2 — AWS Rekognition
    • Kirim crop wajah (Bytes) ke:
    • IndexFaces saat enrol
    • SearchFacesByImage saat verifikasi
    • Baca Similarity (0–100).
    • Default ambang (FACE_THRESHOLD) = 80.
    • Ambil FaceId terbaik dan cocokan dengan user_id yang login.
11. UI Minimal
    • Admin Dashboard
    • Card ringkas: jumlah staff terdaftar
    • Jumlah staff hadir hari ini
    • Akses cepat ke halaman Geofence, Laporan, Manajemen staff
    • Halaman Absen staff
    • Preview kamera <video>
    • Tombol “Ambil Foto”
    • Tombol “Ambil Lokasi”
    • Status realtime:
    o Lokasi: OK / Belum
    o Wajah: OK / Belum (similarity xx%)
    • Tombol “Absen Masuk” / “Absen Keluar”

---

12. Keamanan & Privasi
    • Production harus pakai HTTPS (untuk akses kamera dan GPS dengan aman).
    • IAM Policy AWS dibuat least-privilege (hanya Rekognition).
    • .env tidak boleh ikut git.
    • Kita hanya menyimpan:
    • FaceId dari Rekognition,
    • skor similarity,
    • lokasi absen.
    Bukan menyimpan embedding mentah.
    • Data wajah yang di-upload admin bisa disimpan lokal (untuk audit) atau langsung dibuang setelah berhasil di-enroll.
    • Akses data wajah hanya admin.
    • Tiap absensi simpan juga userAgent / device_info supaya bisa audit kalau ada kecurangan.

---

13. Non-Fungsional
    • Target respon verifikasi wajah <= 3 detik (jaringan normal).
    • Kompatibel di Chrome / Edge / Firefox (mobile & desktop).
    • Kalau internet jelek:
    • tampilkan retry
    • kompres snapshot sebelum kirim
    • Akurasi target:
    • similarity lolos ≥ 80
    • harapannya tingkat lolos untuk orang benar di kondisi normal ≥ 95% (wajah terang, jarak 30–70 cm)

---

14. Milestone (Story)
    • Story_001: Volt + Auth + Role + AWS ping
    • Story_002: Geofencing penuh (GeoJSON + turf.js + tabel geofences)
    • Story_003: Enroll wajah staff
    • Admin upload foto → OpenCV crop/liveness → IndexFaces → simpan FaceId
    • Story_004: Absen IN/OUT
    • Kamera staff → OpenCV liveness/crop → Rekognition verifikasi → geofence check → simpan attendance
    • Story_005: Laporan & export

---

15. Acceptance Global (akhir proyek)
    • Absensi hanya dianggap sah jika:
    • staff berada di dalam polygon geofence,
    • Wajahnya lolos liveness check (OpenCV),
    • Similarity dari Rekognition ≥ FACE_THRESHOLD,
    • user_id yang login sama dengan user_id hasil pencocokan wajah.
    • Data absensi yang tercatat:
    • waktu, lat, lng
    • face_score (similarity)
    • device_info (browser, device)
    • status success/fail
    • Admin bisa lihat & export laporan per rentang tanggal / per staff.

---

16. QA Ringkas
    • Login/Logout aman, role admin vs staff jalan.
    • Kamera & GPS minta izin dan tampil data.
    • Geofence check:
    • titik di dalam polygon → OK
    • titik di luar polygon → ditolak
    • Enroll 3–5 foto per staff → tiap staff punya FaceId tersimpan di face_profiles.
    • Absen tes di kondisi terang & sedikit gelap → similarity di atas threshold untuk orang yang benar.
    • Threshold bisa diubah dari .env.
    • Laporan bisa difilter dan di-export.

---

17. Risiko & Mitigasi
    • Jaringan lemah → retry upload snapshot, kompres ukuran gambar.
    • Cahaya buruk → tampilkan instruksi “Hadapkan wajah ke arah cahaya”.
    • Biaya AWS → kita kirim Bytes langsung (tanpa S3), buang file tmp cepat.
    • Privasi → hanya simpan FaceId dan metadata absen, bukan full embedding biometrik mentah.
