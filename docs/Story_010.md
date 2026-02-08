Story_010 — Aturan Jam Kerja (Masuk/Pulang) Desa Teromu
Tujuan
Sistem paham jam kantor: Masuk 07:30, Pulang 16:00, tanpa toleransi. Otomatis tandai: Tepat Waktu / Terlambat / Pulang Normal / Pulang Cepat. Opsi kunci jam agar absen di luar waktu terkunci.

---

Aturan Bisnis (fix)
• Hari kerja: Senin–Jumat aktif, Sabtu–Minggu nonaktif.
• Jam Masuk: 07:30 →
o ≤ 07:30 = Tepat Waktu
o > 07:30 = Terlambat
• Jam Pulang: 16:00 →
o ≥ 16:00 = Pulang Normal
o < 16:00 = Pulang Cepat
• Toleransi (grace): 0 menit (tidak ada toleransi).
• Offsite (diinput admin): tidak dinilai (status “—”).

---

Data yang perlu (tanpa ubah struktur besar)

1. Tabel jadwal kerja (baru) – work_schedules
   Field minimal per hari (Senin–Jumat):
   o day_of_week (0=Ahad … 6=Sabtu)
   o in_time (time) → 07:30
   o out_time (time) → 16:00
   o grace_late_minutes (int) → 0
   o grace_early_minutes (int) → 0
   o lock_in_start, lock_in_end (time, nullable) → opsional
   o lock_out_start, lock_out_end (time, nullable) → opsional
   o is_active (bool)
2. Tabel hari libur (baru) – holidays
   o date, title, is_active
3. Attendance (tanpa rombak):
   o Boleh tanpa simpan status (hitung saat render), atau simpan status_flag (normal/late/early_leave) kalau mau.
   Seeder: buat default Senin–Jumat aktif (07:30–16:00, grace 0), Sabtu–Minggu nonaktif.

---

UI Admin
• Menu: Pengaturan Jam Kerja
o Tabel 7 hari: toggle aktif/nonaktif.
o Input jam masuk & pulang, semua hari.
o Input lock window (opsional):
 Masuk hanya: contoh 06:30–09:00
 Keluar hanya: contoh 15:00–20:00
• Menu: Hari Libur
o Tambah/hapus libur (tanggal + nama).

---

UI Staff
• Di halaman absen tampil info singkat:
“Jam masuk 07:30 • Jam pulang 16:00 • Toleransi 0 menit”
• Jika di luar lock window (jika diaktifkan): tombol absen nonaktif + pesan kenapa terkunci.

---

Perhitungan Status (logika ringkas)
• Saat Absen Masuk:
o bandingkan now() dengan in_time (hari itu) + grace_late_minutes (0).
o hasil: Tepat Waktu / Terlambat.
• Saat Absen Keluar:
o bandingkan now() dengan out_time (hari itu) − grace_early_minutes (0).
o hasil: Pulang Normal / Pulang Cepat.
• Jika hari libur atau schedule nonaktif → tombol absen nonaktif + pesan.
• Offsite by admin → status “—” (lewati penilaian).

---

Laporan & Export
• Tabel laporan: kolom Status menampilkan badge:
o Masuk: Tepat Waktu / Terlambat
o Keluar: Pulang Normal / Pulang Cepat
o Offsite: —
• Filter tambahan: Status (Semua / Terlambat / Pulang Cepat).
• Export CSV/PDF: ikut tampilkan kolom Status.

---

Validasi & Edge Case
• Weekend: nonaktif (ikuti is_active=false).
• Hari libur: nonaktifkan absen.
• Lock window (jika diaktifkan): di luar rentang → absen ditolak (pesan ramah).
• Offsite tidak dihitung telat/pulang cepat.
• Shift malam: out of scope story ini.

---

Acceptance Criteria
• Ada pengaturan jam kerja per hari: 07:30–16:00, grace 0, aktif Senin–Jumat.
• Staff melihat info jam kerja & (jika aktif) notifikasi kunci waktu.
• Absen Masuk diberi status: Tepat Waktu / Terlambat sesuai aturan.
• Absen Keluar diberi status: Pulang Normal / Pulang Cepat sesuai aturan.
• Offsite (admin) tampil status “—”.
• Laporan & Export menampilkan status dengan benar.
• Hari libur/Weekend menonaktifkan absen.

---

Langkah Eksekusi (urut, tanpa kode)

1. Buat tabel work_schedules & holidays.
2. Seeder: Senin–Jumat (07:30–16:00, grace 0), Sabtu–Minggu nonaktif.
3. Halaman Admin Pengaturan Jam Kerja + Hari Libur.
4. Hook penilaian saat staff absen (Masuk & Keluar) → tetapkan status sesuai aturan.
5. Tampilkan badge status di Laporan & Dashboard.
6. (Opsional) Aktifkan lock window dan kunci tombol di UI staff.
7. Tambah filter status & pastikan Export ikut status.

---

Catatan Integrasi
• Jangan ubah flow geofence/face yang sudah ada.
• Jangan ubah struktur laporan besar; cukup tambah kolom/status.
• Pastikan Offsite by admin tetap “—”.
