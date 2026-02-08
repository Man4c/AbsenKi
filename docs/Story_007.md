Story_007.md — Riwayat Absensi Staff (Self History)

1. Tujuan
   Staff bisa lihat riwayat absensi dirinya sendiri (bukan semua orang), jadi dia bisa cek:
   • Saya sudah absen belum hari ini?
   • Jam berapa saya absen masuk / keluar?
   • Lokasinya dianggap di dalam area atau di luar area?
   • Face match-nya lolos atau tidak?
   Halaman ini nanti jadi bukti transparansi untuk staff + bahan screenshot di laporan / skripsi.

---

2. Scope (yang harus dibuat di story ini)
1. Buat komponen Livewire baru untuk staff, misalnya:
   App\Livewire\Staff\History
   dengan view resources/views/livewire/staff/history.blade.php.
1. Tambahkan menu/halaman baru di sisi staff:
   o Route: /staff/riwayat
   o Middleware: auth, role:staff (sama kayak halaman /staff/absen)
   o Sidebar kiri di layout staff tambahkan item “Riwayat” yang link ke halaman ini.
1. Di halaman Riwayat:
   o Tampilkan tabel daftar absensi milik user yang sedang login (Auth::id()).
   o Urutkan dari yang terbaru ke yang paling lama.
   o Batasi default ke 30 data terakhir (pagination boleh, tapi tidak wajib di Story_007).
1. Kolom tabel yang harus ada:
   o Tanggal & Waktu (format: d M Y H:i)
   o Jenis
    Masuk (type = 'in') → badge warna hijau
    Keluar (type = 'out') → badge warna oranye
   o Status Lokasi
    jika geo_ok == true tampilkan badge hijau “Di dalam area”
    kalau false tampilkan badge merah “Di luar area”
   o Face Match
    tampilkan face_score dalam persen, contoh 97.5%
    kalau status == 'success' → teks hijau “Lolos”
    kalau status == 'fail' → teks merah “Gagal”
   o Koordinat
    tampilkan lat, lng dengan 5–6 digit desimal
    contoh: -5.12345, 119.48231
1. Header halaman:
   o Judul besar: Riwayat Absensi Saya
   o Subteks kecil: Data absensi Anda. Hanya Anda yang bisa melihat halaman ini.
   o Opsional kecil di kanan: badge jumlah total absen bulan ini.

---

3. Behavior detail
   • Hanya tampilkan data dari tabel attendance milik user login:
   Attendance::where('user_id', Auth::id())
   ->orderBy('created_at', 'desc')
   ->take(30)
   ->get();
   • Jangan pernah munculin data user lain, bahkan kalau dicoba akses langsung lewat URL manual.
   • Kalau belum ada data absensi sama sekali:
   o tampilkan state kosong seperti:
   “Belum ada catatan absensi. Silakan lakukan absen dari menu Absen.”
4. Acceptance Criteria
5. Staff bisa klik menu “Riwayat” dan masuk ke /staff/riwayat tanpa error.
6. Halaman tidak bisa diakses oleh admin (role:admin harus ditolak 403).
7. Tabel menampilkan maksimal 30 entri absensi terbaru milik staff yang login.
8. Kolom tabel sesuai spesifikasi:
   o Tanggal & Waktu
   o Jenis (Masuk/Keluar badge)
   o Lokasi (Di dalam area / Di luar area)
   o Face Match (angka persen + status Lolos/Gagal)
   o Koordinat (lat,lng)
9. Jika tidak ada data → muncul pesan kosong yang rapi, bukan tabel kosong jelek.
10. Styling pakai Tailwind seperti halaman lain (light & dark mode tetap jalan).
11. Sidebar staff sekarang punya 2 menu:
    o “Absen”
    o “Riwayat”
    dan icon/teksnya konsisten sama style sidebar yang sudah ada.

---

5. QA Checklist
   • Login sebagai staff (bukan admin), buka /staff/riwayat, halaman muncul.
   • Login sebagai admin, coba akses /staff/riwayat, harus ditolak (403 / redirect).
   • Data yang ditampilkan semuanya milik staff login yang aktif, tidak ada data user lain.
   • Badge warna:
   o Masuk → hijau
   o Keluar → oranye
   o Di dalam area → hijau
   o Di luar area → merah
   o Lolos → hijau
   o Gagal → merah
   • Koordinat tampil rapih dan tidak meledak panjang.
   • Jika staff baru (belum pernah absen), muncul pesan “Belum ada catatan absensi.”
   • Dark mode tetap terbaca (teks kontras masih aman).

---

6. Catatan Implementasi tambahan untuk Copilot Agent
   • Gunakan layout/komponen layout yang sama seperti halaman /staff/absen (biar header/sidebar konsisten).
   • Jangan ubah flow absen. Story_007 HANYA baca data, tidak boleh mengubah data absensi.
   • Jangan sentuh logika verifikasi geofence atau Rekognition di sini.
   • Jangan tambah fitur export di sini (export hanya ada di sisi admin /admin/laporan).
   • Di sidebar staff, pastikan link aktif punya state (teks bold/warna berbeda) biar UX enak.
