Story_005 — Laporan Absensi & Export

1. Tujuan
   • Admin bisa melihat semua data absensi staff dalam bentuk tabel.
   • Admin bisa melakukan filter (per staff, rentang tanggal, tipe absen).
   • Admin bisa download hasilnya sebagai CSV atau PDF untuk laporan resmi.
   Fitur ini dipakai buat rekap harian/bulanan sama buat bukti ke pimpinan desa.

---

2. Lingkup / Scope Story_005
   Yang harus dikerjakan di story ini:
1. Halaman baru untuk Admin
   o Route: /admin/laporan
   o Hanya role admin yang boleh akses.
   o Gunakan Livewire/Volt component (contoh nama: App\Livewire\Admin\Reports\Index)
   o View responsif pakai Tailwind (match style dashboard admin yg sekarang).
1. Filter data absensi
   Di bagian atas halaman laporan, sediakan kontrol filter:
   o Dropdown Staff:
    Option “Semua Staff”
    Lalu list semua user role staff (name + email)
   o Rentang tanggal:
    Tanggal mulai (default: 7 hari lalu)
    Tanggal selesai (default: hari ini)
   o Jenis absen:
    “Semua”
    “Masuk”
    “Keluar”
   o Tombol Terapkan Filter
   Filter ini hanya berlaku di tampilan admin (Livewire), tanpa reload full page.
1. Tabel Absensi
   Tampilkan hasil query dalam bentuk tabel (scrollable kalau data panjang). Kolom minimal:
   o Nama Staff
   o Waktu (format: 01 Nov 2025 13:22)
   o Jenis Absen (Masuk / Keluar)
   o Status Lokasi:
    Di dalam area jika geo_ok == true
    Di luar area jika geo_ok == false
   o Face Match:
    Tampilkan nilai face_score dibulatkan 1 angka, contoh 98.4%
    Kalau face_score == null, tampilkan -
   o Koordinat:
    Tampilkan lat, lng (4 digit desimal sudah cukup)
   o Device:
    Ambil dari kolom device_info (tampilkan ringkas, misal userAgent dipotong 30-40 char)
   Urutan default: data terbaru di atas (descending by created_at).
   Pagination:
   o Beri pagination Livewire sederhana (misal 10 atau 20 baris per halaman).
1. Export CSV
   o Admin klik tombol Export CSV.
   o Sistem akan generate file .csv berisi data absensi sesuai filter aktif saat ini.
   o Kolom CSV:
    staff_name
    staff_email
    datetime
    type (in / out)
    geo_ok (true/false)
    face_score
    lat
    lng
    device_info (full raw json/string)
   o Response download langsung.
   Catatan penting:
   o Export harus mengikuti filter yang sedang dipilih admin.
   o Tidak boleh cuma export semua data mentah tanpa filter.
1. Export PDF
   o Admin klik tombol Export PDF.
   o Generate PDF rapi yang bisa langsung diprint / dikirim ke pimpinan.
   o Header PDF:
    Judul: Laporan Absensi Staff
    Tanggal cetak
    Info filter (misal: “Periode: 01 Nov 2025 - 07 Nov 2025 • Staff: Semua”)
   o Tabel di PDF mirip tabel di halaman:
    Nama Staff
    Waktu
    Jenis Absen
    Status Lokasi (Dalam / Luar Area)
    Face Match (%)
   o PDF dibuat pakai library PDF Laravel yang umum (contoh dompdf / snappy / tcpdf — silakan pilih yang paling gampang diintegrasi).
   o Admin langsung dapat file download .pdf.
   Catatan:
   o PDF tidak perlu tampil di browser, langsung download.
   o Tidak perlu screenshot peta, cukup tabel.
1. Keamanan
   o Route /admin/laporan + semua aksi export HARUS behind middleware auth dan role:admin.
   o Staff tidak boleh akses route ini walaupun tahu URL.

---

3. Desain Data (koneksi tabel)
   Gunakan tabel yang sudah ada:
   Tabel: attendance
   Field penting:
   • user_id → relasi ke users
   • type → 'in' atau 'out'
   • lat, lng → koordinat absen
   • geo_ok → boolean apakah di dalam geofence aktif saat absen
   • face_score → angka similarity dari face recognition (0-100), boleh null kalau belum ada verifikasi wajah
   • device_info → json/string informasi device (userAgent, platform, dll)
   • created_at → timestamp absennya
   Relasi yang perlu:
   • attendance belongsTo user
   • user hasMany attendance
   Di Livewire Reports component:
   • Query attendance dengan join user:
   o filter user (jika user dipilih)
   o filter tanggal (created_at between start-end)
   o filter type (if type != "all")

---

4. Acceptance Criteria
1. Admin bisa buka /admin/laporan tanpa error.
1. Filter bekerja:
   o Pilih staff tertentu → tabel hanya tampilkan absen staff itu.
   o Pilih tanggal range → data di luar range tidak muncul.
   o Pilih type = Masuk → hanya “Masuk”.
1. Tabel menampilkan kolom sesuai spesifikasi dan urut terbaru di atas.
1. Pagination berjalan.
1. Klik “Export CSV” menghasilkan file CSV sesuai filter aktif.
1. Klik “Export PDF” menghasilkan file PDF rapi sesuai filter aktif.
1. Staff biasa TIDAK bisa akses /admin/laporan atau URL export (mendapat 403 / redirect).

---

5. QA Checklist (yang dites manual)
   • Login sebagai admin, buka /admin/laporan → halaman tampil.
   • Dropdown staff muncul dan berisi semua user role staff.
   • Default filter tanggal = 7 hari terakhir, type = Semua.
   • Ganti rentang tanggal → klik Terapkan → tabel berubah.
   • Pilih staff tertentu → hanya record staff itu yang muncul.
   • Kolom “Status Lokasi” benar:

-   geo_ok = 1 → “Di dalam area”
-   geo_ok = 0 → “Di luar area”
    • Face score tampil pakai %, atau - kalau null.
    • Klik “Export CSV” → file terunduh, isinya sesuai data di tabel.
    • Klik “Export PDF” → file terunduh, header berisi periode filter.
    • Coba akses /admin/laporan pakai akun role staff → ditolak (403).
    • Pagination bekerja (misal next/prev page jalan).

---

6. Catatan Implementasi Teknis (arahan ke Copilot Agent)
   • Buat Livewire component baru:
   o app/Livewire/Admin/Reports/Index.php
   o view: resources/views/livewire/admin/reports/index.blade.php
   • State Livewire yang dibutuhkan:
   o public $staffId = 'all'
   o public $startDate
   o public $endDate
   o public $type = 'all' // all | in | out
   • Di mount():
   o default $startDate = now()->subDays(7)->format('Y-m-d')
   o default $endDate = now()->format('Y-m-d')
   • Method getRecordsQuery():
   o base query Attendance::with('user')
   o whereBetween created_at pakai startDate 00:00:00 sampai endDate 23:59:59
   o if $staffId !== 'all' → filter user_id = staffId
   o if $type !== 'all' → filter type = $type
   o orderBy created_at desc
   • Di render():
   o records = $this->getRecordsQuery()->paginate(20);
   o staffList = User::where('role','staff')->orderBy('name')->get(['id','name','email']);
   o return view(..., compact('records','staffList'))
   • Tambahkan dua route baru (di group admin):
   o GET /admin/laporan → Admin\Reports\Index (Livewire)
   o GET /admin/laporan/export/csv
   o GET /admin/laporan/export/pdf
   Pastikan semua route ini pakai middleware ['auth','role:admin'].
   • Export CSV:
   o Gunakan query yang sama dengan filter saat ini.
   o Bangun response text/csv pakai fputcsv / stream download.
   • Export PDF:
   o Boleh pakai dompdf (laravel-dompdf).
   o Buat view blade khusus PDF (misal resources/views/admin/reports/pdf.blade.php).
   o Isi tabel ringkas + header info filter.
   o Return sebagai download.

---

7. Selesai Story_005 jika:
   • Admin bisa melihat, memfilter, dan mengekspor data absensi staff tanpa sentuh database manual, dan hasil export bisa langsung dipakai jadi lampiran laporan kerja.
