Story_003 — Enroll Wajah Staff (Pendaftaran Wajah oleh Admin)
Tujuan
• Admin bisa daftar/mendaftarkan wajah setiap staff ke sistem.
• Sistem menyimpan identitas wajah staff agar nanti bisa dipakai buat verifikasi saat absen.
• Foto wajah yang dipakai bukan selfie sembarangan, tapi sudah:
• dideteksi wajahnya,
• di-crop ke area wajah,
• dicek minimal kualitasnya (tidak gelap/blur),
• lalu dikirim ke AWS Rekognition untuk disimpan.
• Setelah proses berhasil, setiap staff punya face_id yang terhubung ke AWS Rekognition, dan datanya tercatat di database.
Tujuan ini penting karena mulai Story_004, absensi tidak bisa dilakukan kalau wajah staff belum terdaftar.

---

Scope Story_003
Yang harus dibuat di tahap ini:

1. Halaman Admin: Manajemen Wajah Staff
   Admin perlu halaman baru untuk kelola wajah per staff:
   • List semua staff (role = staff) → tampilkan:
   o nama
   o email
   o status wajah:
    "Belum Terdaftar"
    atau "Terdaftar (Face ID: xxxx)"
   o tombol "Kelola Wajah"
   • Klik "Kelola Wajah" buka halaman/detail staff tersebut:
   o tampil identitas staff
   o form upload 1 foto wajah pada satu waktu
   o daftar foto wajah yang sudah pernah diupload (riwayat + face_id)
   Ini bisa dibuat sebagai Livewire component:
   • App\Livewire\Admin\Faces\StaffList → daftar semua staff
   • App\Livewire\Admin\Faces\Manage → halaman untuk 1 staff
   Atur route admin:
   • /admin/faces → StaffList
   • /admin/faces/{userId} → Manage
   Akses route ini hanya boleh oleh role:admin.

---

2. Upload Foto Wajah
   Di halaman Manage untuk 1 staff:
   • Admin bisa upload foto (JPEG/PNG).
   • Validasi basic:
   o wajib gambar
   o max size misal 2MB
   o resolusi minimal misal 200x200 px
   • Setelah upload → diproses oleh backend (bukan langsung disimpan mentah di DB).
   Foto asli boleh disimpan di storage lokal (mis. storage/app/faces/raw/{user_id}/...) hanya untuk audit / skripsi bukti. Ini opsional, tapi bagus untuk dokumentasi skripsi.

---

3. Deteksi & Crop Wajah (OpenCV tahap ringan)
   Sebelum ngirim ke AWS Rekognition, foto harus dipotong agar hanya bagian muka (bukan badan + background).
   Flow:
1. Admin upload foto.
1. Backend kirim gambar itu ke modul pemrosesan wajah (mis: service internal kita, fungsi helper pakai OpenCV).
   o Cari wajah utama.
   o Ambil bounding box wajah terbesar.
   o Crop ke wajah saja.
   o Pastikan wajah menghadap kamera (frontal-ish). Kalau gagal deteksi, balikin error "Wajah tidak terdeteksi dengan jelas".
1. Opsional liveness very-basic:
   o Kasih aturan ke admin: gunakan foto yang berbeda-beda (muka normal, sedikit miring, ada ekspresi, cahaya cukup).
   o Untuk sekarang cukup cek brightness dan ukuran bounding box:
    jangan terlalu gelap (rata-rata pixel terlalu rendah → tolak)
    wajah tidak super kecil (bounding box < 80x80 → tolak)
   o Kalau tidak lolos → tampilkan error ke admin.
1. Hasil crop wajah (sudah bersih) disimpan sementara (mis storage/app/faces/cropped/{user_id}/...).
   Catatan penting:
   • Tahap ini TIDAK melakukan verifikasi ke orangnya live (bukan selfie realtime staff).
   Sudah cukup untuk Story_003 karena ini proses "pendaftaran wajah oleh admin".

---

4. Kirim ke AWS Rekognition (IndexFaces)
   Setelah punya wajah crop yang ok:
1. Ambil bytes gambar crop.
1. Panggil AWS Rekognition IndexFaces dengan parameter:
   o CollectionId = env('REKOG_COLLECTION')
   o Image = ['Bytes' => <binary image bytes>]
   o ExternalImageId = {user_id atau nama staff} (bagus untuk tracking)
1. Rekognition akan balikin satu atau lebih FaceRecords. Dari situ ambil:
   o Face.FaceId
   o (opsional) Face.Confidence
1. Simpan hasilnya ke DB.
   Kalau Rekognition gak balikin FaceId (misal wajah blur), tampilin error ke admin: "Wajah tidak dapat didaftarkan, coba upload foto yang lebih jelas".

---

5. Simpan ke Database (face_profiles)
   Kita buat / gunakan tabel face_profiles dengan kolom:
   • id
   • user_id (staff pemilik wajah)
   • face_id (string dari AWS Rekognition FaceId)
   • provider (isi 'aws')
   • collection_id (isi dari .env → REKOG_COLLECTION)
   • image_path (path lokal foto crop yg berhasil dipakai daftar)
   • confidence (nilai kepercayaan Rekognition kalau kamu mau simpan)
   • created_at
   Aturan penyimpanan:
   • Setiap staff boleh punya beberapa baris face_profiles (3–5 foto wajah).
   Kenapa banyak? → biar Rekognition punya variasi angle cahaya/pose.
   • Nanti di Story_004, saat verifikasi wajah, kita cuma perlu tahu bahwa staff X punya data di Rekognition (bukan cuma satu FaceId tunggal).
   Kalau staff sudah punya 1+ face_profiles, di UI StaffList nanti tandain “Terdaftar ✔”.

---

6. Update UI setelah berhasil
   Setelah admin upload satu foto dan sukses daftar:
   • Tampilkan notifikasi sukses: “Wajah berhasil diregistrasi (FaceId: xxxx...)”
   • Tambahkan entry baru di bawah, contoh:
   o FaceId: abcd1234efg...
   o Uploaded at: 30 Oct 2025 15:02
   o Confidence: 98.7%
   o [Lihat Foto Crop] (preview kecil)
   Ini bukti audit bahwa wajah itu benar-benar sudah dikirim.

---

Acceptance Criteria

1. Halaman /admin/faces
   • Admin bisa melihat daftar semua user dengan role staff.
   • Tiap staff menampilkan status:
   o “Belum Terdaftar”
   o atau “X foto wajah terdaftar”.
   • Ada tombol “Kelola Wajah” untuk tiap staff.
2. Halaman /admin/faces/{userId}
   • Menampilkan nama staff + email.
   • Menampilkan list wajah yang sudah pernah di-enroll (FaceId, tanggal, confidence).
   • Ada form upload foto baru (input type="file").
   • Admin bisa unggah minimal 1 foto wajah, lalu klik “Daftarkan Wajah”.
3. Validasi Upload
   • Jika bukan gambar → ditolak.
   • Jika wajah tidak terdeteksi jelas → ditolak dengan pesan error.
   • Jika terang/ukuran terlalu jelek → ditolak dengan pesan error “Foto terlalu gelap / wajah terlalu kecil”.
4. Proses Enroll
   • Foto diproses (OpenCV crop wajah utama).
   • Image hasil crop dikirim ke rekognition IndexFaces.
   • Sistem menyimpan:
   o user_id
   o face_id
   o collection_id
   o confidence (jika tersedia)
   o image_path (lokasi simpan crop lokal)
   • Jika berhasil → muncul notifikasi sukses.
5. Keamanan Akses
   • Semua route /admin/faces/\* hanya bisa diakses role admin.
   • Staff tidak bisa buka halaman ini.
6. DB Konsisten
   • Tabel face_profiles ada dan migrate sukses.
   • Upload sukses menambahkan baris baru di face_profiles terkait staff tersebut.
7. Status Pendaftaran
   • Di halaman list staff (/admin/faces), staff yang sudah punya minimal 1 entry di face_profiles ditandai sebagai “Terdaftar ✔”.
   • Yang belum punya → “Belum Terdaftar ❌”.

---

QA Checklist
• Masuk sebagai admin → buka /admin/faces. Daftar staff muncul semua.
• Klik satu staff → masuk ke /admin/faces/{id} dan lihat detail orang itu.
• Upload foto wajah jelas → status sukses. Muncul FaceId dari Rekognition.
• Upload foto asal (gelap / kepala kebalik / blur) → muncul error penolakan, data tidak disimpan.
• Setelah 3 foto didaftarkan, halaman staff menunjukkan “3 foto wajah terdaftar”.
• Halaman list /admin/faces sekarang menandai staff tsb sebagai “Terdaftar ✔”.
• Coba login pakai akun role staff dan akses /admin/faces → HARUS ditolak (403 / redirect).
• Cek database face_profiles:
o Kolom user_id benar
o face_id terisi
o collection_id sesuai .env (REKOG_COLLECTION)
o image_path terisi (path foto crop)
• File crop wajah benar-benar tersimpan di storage/app/... sesuai desain (jadi ada bukti audit buat skripsi).

Catatan Teknis untuk Implementasi:

1. Model & Migration face_profiles
   o Buat migration baru create_face_profiles_table.
   o Buat model FaceProfile.
2. Service helper OpenCV
   o Boleh disiapkan sebagai class service, misal app/Services/FaceProcessor.php.
   o Tugasnya:
    terima file upload,
    jalankan deteksi wajah (python script / opencv),
    balikin hasil crop (bytes / path sementara),
    balikin info kualitas (ok / fail reason).
   o Kalau sekarang kamu belum integrasi python/OpenCV beneran, boleh dummy dulu:
    anggap “deteksi wajah selalu lolos”.
    langsung kirim foto asli ke Rekognition sebagai Bytes.
   o Nanti pas udah siap integrasi OpenCV beneran, tinggal upgrade service-nya. Ini masih aman buat Story_003.
3. Integrasi AWS Rekognition
   o Bikin service misal app/Services/FaceEnrollService.php:
    method enrollFace($user, $imageBytes): array
    call IndexFaces
    return ['face_id' => '...', 'confidence' => 99.12] atau throw exception.
4. .env pastikan punya
   o REKOG_COLLECTION=staf_desa_teromu
   o FACE_THRESHOLD=80
   Ini akan dipakai Story_004 saat verifikasi.

---

Definition of Done
Story_003 dianggap selesai kalau:
• Admin sudah bisa daftar beberapa foto wajah untuk setiap staff.
• Data wajah staff telah tersimpan di AWS Rekognition dan dicatat di DB (face_profiles).
• Untuk setiap staff, sistem tahu apakah dia “sudah punya wajah terdaftar atau belum”.
• Route admin terlindungi, staff gak bisa akses.
• Semua ini bisa jadi bukti di bab implementasi skripsi nanti (“Admin melakukan registrasi biometrik wajah staff sebelum sistem absensi digunakan”).
