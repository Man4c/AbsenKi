Yang perlu ditambah dikit

1. Kolom di tabel attendances
   • evidence_path (string, nullable)
   • evidence_mime (string, nullable)
   • evidence_size (integer, nullable)
   • evidence_note (text, nullable)
   • evidence_uploaded_at (timestamp, nullable)
2. Simpan file saat admin input “Offsite”
   • Validasi: mimes:jpg,jpeg,png,pdf dan max:5120 (5MB).
   • Simpan ke storage/app/public/evidence/{user_id}/{Ymd}/...
   • Isi kolom di atas + catat catatan (note) yang dimasukkan admin.
3. Model Attendance (biar gampang dipakai di view)
   • Tambah accessor kecil:
   o evidence_url → Storage::disk('public')->url($this->evidence_path)
   o has_evidence → bool dari evidence_path.
   Ubah tampilan Laporan (halaman index)
   • Tambah kolom baru “Bukti” di tabel daftar.
   o Jika tidak ada bukti → tampilkan “—”.
   o Jika ada bukti → tampilkan tombol kecil “Lihat”.
   • Saat klik “Lihat” → buka modal:
   o Kalau mime gambar → <img> di modal.
   o Kalau mime pdf → <iframe> atau buka tab baru (download link).
   • Boleh tambahkan filter kecil: “Dengan bukti / Tanpa bukti”.
   Catatan: di CSV, cukup tambahkan dua kolom opsional evidence_note dan evidence_url (biar admin bisa cek link).
   Di PDF, saran tampilkan ikon klip + teks “Ada bukti (lihat di sistem)” + note; jangan embed file besar ke PDF (berat).
   Keamanan & akses
   • Pakai disk public (atau signed URL kalo mau lebih aman).
   • Pastikan policy/guard: hanya admin boleh lihat/unduh buktinya.
   • Saat hapus attendance → hapus file buktinya juga (biar gak nyisa).
   UX kecil biar enak dipakai
   • Di baris tabel, kasih badge “Offsite entry” (kamu sudah ada) + tombol “Lihat bukti”.
   • Di modal, tampilkan: Nama file, ukuran, tipe, catatan.
   • Di form “Tambah Entri Offsite”, beri preview kecil setelah pilih file.
   Checklist singkat (biar langsung jalan)
   • Migration tambah kolom bukti.
   • Simpan file & metadata saat admin buat Offsite.
   • Accessor evidence_url & has_evidence di model.
   • Kolom “Bukti” + modal pratinjau di reports/index.blade.php.
   • Tambahkan data bukti ke export CSV (opsional) & tanda di PDF.
   • Hapus file saat attendance dihapus.
