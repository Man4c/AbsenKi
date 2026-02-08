Story_004 — Absensi IN/OUT dengan Validasi Lokasi + Wajah (Staff)

1. Tujuan
   Membuat halaman absensi mandiri untuk staff, di mana staff hanya bisa melakukan absen jika:
1. Lokasi fisik staff berada di dalam area geofence kantor yang aktif.
1. Wajah staff cocok dengan data biometrik yang sudah diregistrasi sebelumnya oleh admin (pakai AWS Rekognition).
   Output dari absensi harus tercatat ke tabel attendance sebagai bukti sah kehadiran.
   Sasaran user: staff (bukan admin).
   Endpoint utama untuk staff:
   • /staff/absen

---

2. Ruang Lingkup (Scope Story_004)
   Hal-hal yang WAJIB jadi bagian Story_004:
   A. Halaman Absensi Staff (/staff/absen)
   Halaman ini hanya bisa diakses oleh user role staff.
   Elemen yang harus ada di UI:
1. Status Geofence
   o Tombol/section “Cek Lokasi Saya”
   o Menampilkan koordinat (lat, lng)
   o Menampilkan status:
    ✅ “Di area kantor” (jika point di dalam polygon aktif)
    ❌ “Lokasi di luar area kantor” (kalau tidak di dalam polygon)
1. Preview Kamera
   o <video> live dari kamera depan (atau kamera default device)
   o Tombol “Ambil Foto Wajah Sekarang”
   o Setelah klik tombol, ambil 1 frame snapshot jadi <img> preview
   o Snapshot ini nanti dikirim ke backend untuk verifikasi wajah
1. Info Status Verifikasi
   o Hasil cek lokasi: OK / Gagal
   o Hasil cek wajah: match / tidak match / belum dicek
   o Catatan confidence match (%) kalau match
1. Tombol “Absen Masuk” dan “Absen Keluar”
   o Aktif hanya jika:
    lokasi OK
    wajah OK
   o Kalau salah satu gagal, tombol disabled + ada pesan error kecil
   Catatan: Dalam Story_004 kita cukup bikin dasar submit satu tombol “Absen Masuk”. “Absen Keluar” bisa reuse logic sama, boleh jadi bagian lanjutan / optional. Tapi tolong siapkan kolom type di database (in / out) jadi dari sekarang kita aman.

---

B. API/Logic Geofencing di Frontend

1. Ambil polygon geofence aktif dari endpoint yang SUDAH ADA:
   o GET /api/geofence/active
   o Response: { name, polygon }
   o polygon = GeoJSON Polygon (koordinat [lng, lat]).
2. Di browser (JavaScript):
   o Ambil lokasi user pakai navigator.geolocation.getCurrentPosition.
   o Point staff = [lng, lat].
   o Pakai turf.js → turf.booleanPointInPolygon(point, polygonGeoJSON).
   o Simpan hasilnya di state frontend (misalnya geoOk = true/false) dan tampilkan status ke user.
   Penting: Hasil cek geofence TIDAK boleh diputuskan di frontend doang.
   Ketika submit absen, lat/lng HARUS ikut dikirim ke backend dan backend HARUS cek ulang (server-side) pakai turf juga, supaya tidak bisa dimanipulasi dari devtools.

---

C. API/Logic Face Recognition (Verifikasi)
Flow verifikasi wajah:

1. Staff klik “Ambil Foto Wajah Sekarang”.
2. Dari <video>, ambil frame → jadi gambar (base64 / Blob).
3. Kirim snapshot itu ke backend via POST, misal:
   o POST /staff/absen/verify-face
   o Body:
    image (snapshot wajah staff, base64 atau blob file)
   o Backend step:
    Simpan sementara (storage lokal sementara, tidak perlu permanent).
    Kirim “Bytes” tersebut ke AWS Rekognition:
    Gunakan SearchFacesByImage (atau CompareFaces jika kamu simpan face exemplar).
    Cari match dalam collection yang sama seperti Story_003.
    Ambang batas awal (FACE_THRESHOLD di .env) misalnya 80%.
    Ambil:
    FaceId yg match
    Similarity score
    Pastikan FaceId yang match adalah milik user yg lagi login
    Kalau match-nya wajah orang lain → TOLAK.
    Return JSON:
   {
   "ok": true,
   "score": 97.3
   }
   Atau
   {
   "ok": false,
   "message": "Wajah tidak cocok"
   }
4. Frontend menampilkan hasil itu:
   o Kalau ok=true → tampilkan “✅ Wajah cocok (97.3%)”
   o Kalau gagal → tampilkan “❌ Wajah tidak cocok”
   Nilai ini (score, dll) harus disimpan sementara di komponen Livewire agar nanti pas submit absen bisa dipakai.

---

D. Commit Absensi
Setelah dua validasi lolos, staff klik tombol “Absen Masuk”:
• Kirim POST ke backend, misal POST /staff/absen/commit
Body minimal:
o type: "in" atau "out"
o lat
o lng
o face_score
o geo_ok
o face_ok
Backend melakukan langkah-langkah:

1. Ambil user login (auth).
2. VALIDASI SERVER-SIDE ULANG:
   o Cek titik [lng, lat] di dalam polygon aktif pakai turf.js di server (PHP-side turf versi PHP atau lib equivalent; kalau belum ada helper, sementara boleh cek lagi di Livewire pakai package geospatial, atau TODO).
   o Cek bahwa face_ok yg dikirim berasal dari verifikasi barusan dan user benar (✱ kalau belum ada session flag di Story_003, boleh sementara pakai trust dari request — nanti bisa dikerasin di Story_004b).
3. Simpan ke tabel attendance:
   o user_id = staff
   o type = "in" atau "out"
   o lat, lng
   o geo_ok
   o face_score
   o status = "success" kalau semua ok, "fail" kalau tidak memenuhi
   o device_info = userAgent browser (User-Agent dari request)
   o timestamp = now()
4. Return sukses → tampilkan notifikasi ke user:
   o “✅ Absen Masuk berhasil dicatat pada 31 Okt 2025 10:22”
   o Kalau gagal → tampilkan alert merah.

---

E. Gambaran Struktur Tabel attendance (harus sudah ada/segera dibuat)
Kolom minimal:
• id
• user_id (FK ke users)
• type enum/string: 'in' | 'out'
• lat decimal(10,7)
• lng decimal(10,7)
• geo_ok boolean
• face_score float
• status string / enum 'success'|'fail'
• device_info text/json (userAgent, platform)
• created_at
Ini tabel yang nanti akan dipakai untuk laporan di Story_005.

---

3. Acceptance Criteria (harus terpenuhi sebelum Story_004 dianggap selesai)
1. Akses Halaman
   o User role staff bisa buka /staff/absen.
   o User role admin / user lain TIDAK boleh buka halaman itu (403 atau redirect).
1. Geofence Check
   o Tombol “Cek Lokasi Saya” bisa ambil koordinat GPS.
   o Sistem kasih tahu “Di area kantor ✅” atau “Di luar area kantor ❌”.
   o Saat submit absen, lat/lng ikut tercatat di DB.
1. Kamera & Snapshot
   o Halaman menampilkan live preview kamera (<video>).
   o Tombol “Ambil Foto Wajah Sekarang” menghasilkan preview foto diam (<img>).
   o Foto snapshot DIKIRIM ke backend untuk verifikasi wajah.
1. Verifikasi Wajah
   o Jika wajah cocok dengan FaceId milik staff ini → status wajah = OK.
   o Jika tidak cocok → status wajah = GAGAL.
   o Confidence (similarity %) ditampilkan.
1. Tombol Absen
   o Tombol “Absen Masuk” hanya aktif kalau:
    Geofence OK
    Wajah OK
   o Setelah klik, data absensi tersimpan di tabel attendance dengan status success.
1. Data Attendance Tersimpan
   o Entry baru di tabel attendance muncul dengan:
    user_id = staff yang login
    type = "in"
    lat/lng tersimpan
    face_score tersimpan
    geo_ok = true
    status = "success"
    device_info berisi user agent (browser info)
1. Notifikasi
   o Setelah absen berhasil, staff melihat pesan sukses di halaman.
   o Tidak perlu reload manual halaman.
1. Tombol Absen
   o Tombol “Absen Masuk” hanya aktif kalau:
    Geofence OK
    Wajah OK
   o Setelah klik, data absensi tersimpan di tabel attendance dengan status success.
1. Data Attendance Tersimpan
   o Entry baru di tabel attendance muncul dengan:
    user_id = staff yang login
    type = "in"
    lat/lng tersimpan
    face_score tersimpan
    geo_ok = true
    status = "success"
    device_info berisi user agent (browser info)
1. Notifikasi
   o Setelah absen berhasil, staff melihat pesan sukses di halaman.
   o Tidak perlu reload manual halaman.

---

4. QA Checklist (yang diuji manual sebelum centang Story_004 Selesai)
   ✅ Login sebagai staff:
   • Staff bisa buka /staff/absen.
   • Admin TIDAK bisa buka /staff/absen.
   ✅ Geofence:
   • Klik “Cek Lokasi Saya”:
   o Koordinat muncul (contoh: lat -6.1754, lng 106.8271).
   o Status lokasi benar (uji coba di dalam polygon aktif → harus ✅).
   o Kalau polygon dinonaktifkan / staff “di luar area” → status ❌ dan tombol absen harus nonaktif.
   ✅ Kamera:
   • Kamera minta izin.
   • Video preview tampil real-time.
   • Klik “Ambil Foto Wajah Sekarang” → muncul snapshot bawahnya.
   ✅ Verifikasi wajah:
   • Setelah snapshot dikirim:
   o Jika wajah cocok, tampil “Wajah cocok 98.5% ✅”.
   o Jika wajah gak cocok / wajah orang lain → muncul peringatan merah dan tombol absen tetap nonaktif.
   ✅ Commit absen:
   • Tombol “Absen Masuk” baru aktif kalau:
   o Lokasi OK
   o Wajah OK
   • Setelah klik:
   o Tidak error 500
   o Muncul pesan sukses “Absen Masuk berhasil dicatat …”
   o Row baru muncul di tabel attendance berisi data lengkap (lat/lng, score wajah, dsb).
   ✅ Security dasar:
   • Kalau user staff coba manggil /staff/absen/commit langsung tanpa verifikasi wajah → harus tetap diverifikasi server-side (minimal cek auth & role).
   • Field sensitif seperti FACE_THRESHOLD tetap diambil dari .env, bukan hardcode di Blade.

---

5. Catatan Implementasi untuk Copilot (biar Agent kamu ngerti arah coding)
   • Buat komponen Livewire Staff\Absen (misal App\Livewire\Staff\Absen).
   o Render view resources/views/livewire/staff/absen.blade.php.
   o Lindungi route /staff/absen pakai middleware auth + role:staff.
   • Di komponen ini, butuh state:
   o $lat, $lng
o	$geoStatus (misal: 'unknown' | 'inside' | 'outside')
o	$facePreview (data URL foto snapshot untuk ditampilkan di UI)
o	$faceScore (angka confidence dari Rekognition)
o	$faceOk (bool)
o	$message (pesan sukses/gagal absensi)
o	$canCheckIn (computed: $geoStatus === 'inside' && $faceOk === true)
•	Aksi yang harus ada di Livewire:
o	checkLocation($lat, $lng)
→ Simpan koordinat, panggil service geofence checker (boleh sementara di-PHP-kan pakai helper turf equivalent / TODO), set $geoStatus.
o	verifyFace($snapshotImage)
   → Kirim snapshot ke service Rekognition VerifyFaceService (mirip Enroll tapi pakai SearchFacesByImage). Set $faceScore dan $faceOk.
o	commitAttendance($type)
   → Simpan record ke tabel attendance sesuai format yang disebut di atas.
   → Set $message = "Absen $type berhasil ...".
   → Reset state UI seperlunya.
   • Jangan lupa .env kamu sudah punya:
   o REKOG_COLLECTION=...
   o FACE_THRESHOLD=80
   Ini harus dipakai di service verifikasi wajah untuk nentuin kapan dianggap “match”.
