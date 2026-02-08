Story_009 — Entri Offsite oleh Admin (MVP)
Tujuan
Admin bisa mencatat absensi di luar kantor (offsite) untuk staff tanpa geofence/face-check, dengan data alasan, lokasi, waktu, dan bukti. Data masuk ke laporan & export dengan flag Offsite.

---

Ruang Lingkup (yang dikerjakan)
• Tambah field offsite di tabel attendances.
• Form “Tambah Entri Offsite” di halaman Laporan Admin.
• Simpan entri langsung Approved (karena admin yang input).
• Tampilkan badge Offsite di tabel & ikut di filter/export.
Catatan: Pengajuan Offsite oleh staff & approval admin akan dikerjakan di Story_009 (bukan di story ini).

---

Perubahan Data (DB)
Update tabel attendances (nullable sesuai kebutuhan):
• is_offsite (boolean, default false)
• offsite_location_text (string 255, nullable)
• offsite_coords (string 64, nullable) — simpan "lat,lng"
• offsite_reason (text, nullable)
• evidence_path (string 255, nullable) — simpan path bukti
• created_by_admin_id (unsignedBigInteger, nullable) — FK ke users.id
Constraint/Indeks:
• FK created_by_admin_id → users.id (on delete set null).
• Index gabungan yang sudah ada (mis. user_id, created_at) tetap.

---

Aturan Bisnis
• Entri offsite melewati geofence dan tidak butuh face-check (karena admin yang input).
• Minimal data wajib:
o Staff, Tanggal & Jam, Jenis (in/out atau “hadir” sekali sesuai kebijakan), Lokasi/Alamat.
• Anti duplikasi: hormati aturan satu kali “Masuk” per hari (kecuali shift—ikuti rule yang sudah berlaku di sistem laporanmu).
• Bukti format: JPG/PNG/PDF (maks 2–5 MB).
• Semua entri dari fitur ini otomatis status = success + is_offsite=true.

---

UI & Navigasi
Admin > Laporan
• Tambah tombol “Tambah Entri” → pilih tab “Offsite” (modal/halaman).
• Form:
o Pilih Staff (select2/search)
o Tanggal & Jam
o Jenis: Masuk / Keluar / Hadir (sehari) (kalau pilih “Hadir”, simpan 1 entri bertipe “in” dengan penanda hadir sehari; atau sesuaikan kebijakanmu)
o Lokasi/Alamat (wajib)
o Koordinat (opsional, text "lat,lng")
o Alasan (opsional)
o Bukti upload (opsional)
o Tombol Simpan
• Tabel Laporan:
o Tambah badge “Offsite” pada baris terkait.
o Filter baru: Jenis Lokasi = Semua / Di Kantor / Offsite.

---

Validasi
• Staff wajib ada & aktif.
• Tanggal & jam valid (tidak masa depan jauh/masa lalu “absurd”, ikuti kebijakan).
• Lokasi/Alamat wajib (max 255).
• Koordinat (jika diisi) harus format "lat,lng" numerik.
• File bukti (jika ada): mime jpg,jpeg,png,pdf, ukuran ≤ batas ENV.

---

Integrasi (tanpa ubah alur lain)
• Tidak mengubah logika absen existing (geofence/face).
• Export CSV/PDF: sertakan kolom:
o is_offsite, offsite_location_text, offsite_coords, offsite_reason, created_by_admin_id, evidence_path.

---

Keamanan & Akses
• Hanya role:admin yang bisa membuka & menyimpan entri offsite.
• Simpan created_by_admin_id = auth()->id() untuk jejak audit.
• File bukti disimpan di storage local sesuai kebijakan (privat/terbatas admin).

---

Acceptance Criteria
• Admin dapat membuka form Tambah Entri Offsite dari halaman Laporan.
• Entri berhasil tersimpan ke attendances dengan is_offsite=true dan semua field terkait.
• Baris laporan menampilkan badge Offsite.
• Filter laporan bisa menampilkan hanya entri Offsite.
• Export CSV/PDF menyertakan kolom offsite.
• Tidak ada validasi geofence/face-check di jalur ini.
• Aktivitas menyimpan mencatat created_by_admin_id.

---

QA Checklist
• Buat 1 entri Offsite jenis Masuk → tampil di laporan dengan badge.
• Coba unggah bukti JPG & PDF (ukuran wajar) → tersimpan & dapat dilihat/diunduh admin.
• Coba input koordinat valid & invalid → validasi bekerja.
• Filter Offsite menampilkan entri yang benar, Di Kantor menyembunyikannya.
• Export menampilkan kolom tambahan offsite dengan nilai benar.
• Pastikan route & guard admin mencegah akses staff.

---

Out of Scope (dialihkan ke Story_010)
• Alur Pengajuan Offsite oleh staff (Pending → Approve/Tolak).

---

Rollback
• Hapus/rollback migration field offsite.
• Sembunyikan tombol “Tambah Entri”.
• Data lama tetap aman (tidak mengubah catatan non-offsite).
