# Story 012: Manajemen Izin & Cuti oleh Admin

## Tujuan

Memberikan kemampuan kepada admin untuk mencatat dan mengelola izin/cuti karyawan secara terpusat, sehingga data absensi lebih akurat dan terorganisir.

## Peran

### Admin

-   Menambahkan data izin/cuti untuk staff
-   Menentukan tanggal mulai dan selesai izin/cuti
-   Memilih jenis (izin/cuti/sakit)
-   Menambahkan keterangan
-   Mengedit atau menghapus data izin/cuti
-   Melihat daftar izin/cuti semua staff

### Staff

-   Hanya bisa melihat data izin/cuti milik sendiri di riwayat
-   Tidak bisa mengajukan atau mengubah izin/cuti

## Alur

### 1. Admin Menambahkan Izin/Cuti

```
Admin → Menu Izin/Cuti → Tombol Tambah
→ Pilih Staff (dropdown)
→ Pilih Jenis (Izin/Cuti/Sakit)
→ Pilih Tanggal Mulai & Selesai
→ Isi Keterangan
→ Simpan
→ Sistem catat ke database
→ Notifikasi berhasil
```

### 2. Admin Mengelola Data

```
Admin → Menu Izin/Cuti → Lihat Daftar
→ Filter berdasarkan staff/bulan/jenis
→ Edit: Ubah tanggal/jenis/keterangan
→ Hapus: Hapus data izin/cuti
```

### 3. Staff Melihat Izin/Cuti

```
Staff Login → Riwayat
→ Lihat izin/cuti yang diberikan admin
→ Informasi: tanggal, jenis, keterangan
```

### 4. Integrasi dengan Absensi

```
Saat admin input izin/cuti
→ Sistem otomatis tandai tanggal tersebut
→ Staff tidak perlu absen di tanggal izin/cuti
→ Status absensi: "Izin" / "Cuti" / "Sakit"
```

## Catatan Teknis

-   Validasi: tanggal selesai >= tanggal mulai
-   Cegah duplikasi periode untuk staff yang sama
-   Status: approved (langsung aktif karena admin yang input)
-   Tampilkan badge berbeda untuk setiap jenis (warna berbeda)
