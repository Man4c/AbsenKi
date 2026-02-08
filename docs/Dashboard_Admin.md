1. Isi yang wajib ada di dashboard admin
   A. Kartu ringkas (tiga kotak kecil di bagian atas)
   Ini buat kasih info kilat dalam 1 detik. Cocok ditaruh di 3 card pertama kamu.
1. Total Staff Terdaftar
   o Angka besar, misal: 12 Staff
   o Keterangan kecil: “3 belum punya wajah”
   o Data ambil dari tabel users role=staff + relasi faceProfiles()
1. Hadir Hari Ini
   o Angka besar: 9 Hadir
   o Keterangan kecil: “2 Terlambat (absen masuk > 08:00)”
   o Ini ngambil dari tabel attendance tipe in untuk tanggal = today, hitung unique user_id
1. Status Sistem
   o Bisa jadi card "Sistem Siap ✅" / "Perlu Perhatian ⚠️"
   o Isi kecil di bawahnya:
    Geofence aktif: Lokasi Tes
    Rekognition: Connected
    Terakhir absen dicatat: 01 Nov 2025 13:22
   o Ini bikin admin merasa "aman sistemnya masih jalan"
   Jadi row pertama dashboard = "apa kabar sistem hari ini?"

---

B. Panel daftar absensi terakhir
Ini cocok buat container besar paling bawah (yang sekarang masih full abu2 garis miring).
Judul: Aktivitas Terbaru
Tabel kecil 5 baris terakhir attendance:
• Nama staff
• Waktu (ex: 01 Nov 2025 13:22)
• Jenis (Masuk / Keluar)
• Lokasi (✅ Di dalam area / ❌ Di luar area)
• Face match (misal 98.5%)
• Status (Hijau kalau lolos dua validasi, Merah kalau gagal)
Kenapa penting?
• Admin bisa langsung liat siapa yang baru check-in/check-out barusan, tanpa buka menu Laporan.

---

C. Card Geofence Aktif
Kamu punya geofencing canggih. Itu harus kelihatan di dashboard 🤏
Bisa jadi 1 card kecil (bisa gantikan salah satu x-placeholder-pattern card kalau kamu mau):
Isi:
• Area Aktif: Lokasi Tes
• Titik koordinat pusat (lat,lng)
• Tombol kecil "Kelola Geofence" → link ke /admin/geofence
Bonus lucu: kasih badge warna hijau “Active”.
Kalau gak ada geofence aktif, warnanya merah “No Active Geofence ❌” = admin jadi sadar langsung. 2. Rencana struktur komponen Livewire
Biar rapi, jangan semua logic ditumpuk di dashboard.blade.php. Lebih cakep kalau dashboard cuma “nyusun komponen”.

1.  <livewire:admin.dashboard.stats-cards />
    Isinya 3 kartu ringkas:
    • Total staff + status wajah
    • Hadir hari ini
    • Status sistem
    Data yang perlu dihitung di komponen ini:
    // contoh field yang dihitung:
    $totalStaff
$staffBelumPunyaWajah
    $hadirHariIni
$terlambatHariIni
    $geofenceAktifName
$rekognitionOk (true/false)
    $lastAttendanceTime
2.  <livewire:admin.dashboard.recent-attendance />
    Tabel 5 absen terakhir.
    Field tiap baris:
    [
    'nama' => 'Staff Demo',
    'waktu' => '01 Nov 2025 13:22',
    'jenis' => 'Masuk',
    'geo_ok' => true,
    'face_score' => 98.5,
    'status' => 'Lolos',
    ]
3.  Contoh revisi layout dashboard.blade.php
    Aku kasih draft markup baru (masih statis dummy, tapi udah ada slot tempat komponen Livewire masuk).
    Kamu tinggal ganti x-placeholder-pattern jadi komponen beneran pelan-pelan.

    <x-layouts.app :title="\_\_('Admin Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">

            <!-- Header / Welcome -->
            <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-zinc-900">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Halo Admin
                        </h1>
                        <p class="mt-2 text-gray-600 dark:text-gray-400 text-sm">
                            Ringkasan status sistem absensi hari ini.
                            Kelola staff, area geofence, dan laporan dari sini.
                        </p>
                    </div>

                    {{-- Quick Actions --}}
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.faces') }}"
                           class="px-3 py-2 text-sm rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                            Kelola Wajah Staff
                        </a>
                        <a href="{{ route('admin.geofence') }}"
                           class="px-3 py-2 text-sm rounded-lg bg-amber-500 text-white hover:bg-amber-600">
                            Kelola Geofence
                        </a>
                        <a href="{{ route('admin.laporan') }}"
                           class="px-3 py-2 text-sm rounded-lg bg-neutral-800 text-white hover:bg-neutral-900 dark:bg-neutral-700 dark:hover:bg-neutral-600">
                            Laporan Absensi
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-3">
                {{-- Card 1: Staff --}}
                <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-zinc-900">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Total Staff Terdaftar
                    </p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                        12
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        3 belum punya data wajah
                    </p>
                </div>

                {{-- Card 2: Kehadiran Hari Ini --}}
                <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-zinc-900">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Kehadiran Hari Ini
                    </p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                        9<span class="text-base font-medium text-gray-500 dark:text-gray-400"> / 12</span>
                    </p>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        2 terlambat (>08:00)
                    </p>
                </div>

                {{-- Card 3: Status Sistem --}}
                <div class="rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-zinc-900">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                        Status Sistem
                    </p>

                    <div class="mt-2 flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-green-600 px-2 py-0.5 text-xs font-semibold text-white">
                            Sistem Normal
                        </span>
                    </div>

                    <ul class="mt-3 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                        <li>Geofence aktif: <span class="font-medium text-gray-900 dark:text-white">Lokasi Tes</span></li>
                        <li>Rekognition: <span class="text-green-600 dark:text-green-400 font-medium">Connected</span></li>
                        <li>Absen terakhir: <span class="text-gray-900 dark:text-white">01 Nov 2025 13:22</span></li>
                    </ul>
                </div>
            </div>

            <!-- Aktivitas Terbaru -->
            <div class="rounded-xl border border-neutral-200 bg-white dark:border-neutral-700 dark:bg-zinc-900">
                <div class="p-5 border-b border-neutral-200 dark:border-neutral-700 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                            Aktivitas Absensi Terbaru
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            5 aktivitas terakhir dari semua staff
                        </p>
                    </div>

                    <a href="{{ route('admin.laporan') }}"
                       class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                        Lihat semua laporan →
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="bg-neutral-50 text-gray-600 dark:bg-zinc-800 dark:text-gray-300 text-xs uppercase">
                            <tr>
                                <th class="px-4 py-2 font-medium">Staff</th>
                                <th class="px-4 py-2 font-medium">Waktu</th>
                                <th class="px-4 py-2 font-medium">Jenis</th>
                                <th class="px-4 py-2 font-medium">Lokasi</th>
                                <th class="px-4 py-2 font-medium">Face Match</th>
                                <th class="px-4 py-2 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700 text-gray-700 dark:text-gray-200">
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 dark:text-white">Staff Demo</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">staff@demo.test</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    01 Nov 2025 13:22
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">
                                        Masuk
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 rounded bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">
                                        <span class="size-1.5 rounded-full bg-green-600"></span>
                                        Di dalam area
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    100.0%
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">
                                        Lolos
                                    </span>
                                </td>
                            </tr>

                            {{-- baris berikutnya nanti loop @foreach data attendance terbaru --}}
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </x-layouts.app>
