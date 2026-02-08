Story_001 — Fondasi Volt + Auth + Role + AWS (siap dipakai)
Tujuan
• Proyek Laravel + Volt (Livewire) berdiri rapi.
• Fitur login / logout sudah bekerja.
• User punya 2 role: admin dan staff, dengan akses halaman yang berbeda.
• Halaman awal sudah ada:
o /admin/dashboard (khusus admin)
o /staff/absen (khusus staff, ada tombol Tes Kamera & Tes GPS sederhana)
• Koneksi ke AWS Rekognition sudah siap (kredensial ada di .env dan bisa di-ping lewat perintah artisan).
Catatan: Geofencing penuh akan dikerjakan di Story_002.
Enroll wajah & verifikasi wajah (OpenCV + Rekognition) akan dikerjakan di Story_003 dan Story_004.

---

Scope Story_001
• Install Volt (sudah) dan gunakan auth bawaannya (login, register, logout).
• Tambahkan kolom role di tabel users, default staff.
• Buat seeder 1 akun admin dan 1 akun staff.
• Proteksi route pakai Gate / middleware sederhana berdasarkan role.
• Buat komponen Livewire dasar:
o Admin/Dashboard (placeholder, teks “Halo Admin”)
o stafff/Absen (tombol “Tes Kamera” & “Tes GPS” + preview sederhana)
• Setup AWS Rekognition:
o Tambahkan kredensial AWS ke .env
o Tambahkan artisan command aws:ping untuk ngetes koneksi Rekognition (misalnya panggil ListCollections).
• Tidak ada S3 di tahap ini, penyimpanan masih local.

---

Acceptance Criteria

1. Auth
   o Login dan logout berhasil untuk admin dan staff.
2. Role & Akses
   o Field users.role ada dan default-nya staff.
   o User dengan role admin bisa akses /admin/dashboard, tapi user staff tidak boleh.
   o User dengan role staff bisa akses /staff/absen, tapi user admin tidak boleh.
3. UI Minimal
   o Halaman /admin/dashboard menampilkan teks sederhana “Halo Admin”.
   o Halaman /staff/absen menampilkan:
    Tombol “Tes Kamera” → menyalakan preview <video>.
    Tombol “Tes GPS” → menampilkan lat,lng.
4. AWS Siap
   o File .env berisi:
    AWS_ACCESS_KEY_ID
    AWS_SECRET_ACCESS_KEY
    AWS_DEFAULT_REGION
    REKOG_COLLECTION
    FACE_THRESHOLD
   o Menjalankan php artisan aws:ping berhasil memanggil AWS Rekognition tanpa error kredensial.
5. Database
   o Migrasi untuk kolom users.role sukses.
   o Seeder berhasil membuat:
    Admin contoh (role = admin)
    staff contoh (role = staff)
6. Keamanan Dasar
   o File .env tidak ikut ke git (.gitignore sudah benar).
7. Redirect dasar setelah login (opsional tapi bagus dicek di QA)
   o Jika login sebagai admin → diarahkan ke /admin/dashboard
   o Jika login sebagai staff → diarahkan ke /staff/absen

---

QA Checklist
• Bisa login pakai akun admin dan staff.
• Setelah login, admin diarahkan / bisa buka /admin/dashboard. staff ditolak kalau coba buka halaman admin.
• Setelah login, staff diarahkan / bisa buka /staff/absen. Admin ditolak kalau coba buka halaman staff.
• Tombol “Tes Kamera” di halaman staff menyalakan preview video <video>.
• Tombol “Tes GPS” di halaman staff menampilkan koordinat lat,lng.
• Menjalankan php artisan aws:ping berhasil (tanpa error kredensial AWS).
• Seeder berhasil membuat akun:
o admin@demo.test / password (role = admin)
o staff@demo.test / password (role = staff)
• Kolom role sudah ada di tabel users, default staff.
• .env tidak ke-commit.
