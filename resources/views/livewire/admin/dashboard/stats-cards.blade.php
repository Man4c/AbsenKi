<div class="grid gap-4 md:grid-cols-3">
    {{-- Card 1: Staff --}}
    <div class="h-full rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-zinc-900">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
            Total Staff Terdaftar
        </p>
        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
            {{ $totalStaff }}
        </p>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ $staffBelumPunyaWajah }} belum punya data wajah
        </p>
    </div>

    {{-- Card 2: Kehadiran Hari Ini --}}
    <div class="h-full rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-zinc-900">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
            Kehadiran Hari Ini
        </p>
        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
            {{ $hadirHariIni }}<span class="text-base font-medium text-gray-500 dark:text-gray-400"> /
                {{ $totalStaff }}</span>
        </p>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ $terlambatHariIni }} terlambat (>07:30)
        </p>
    </div>

    {{-- Card 3: Status Sistem --}}
    <div class="h-full rounded-xl border border-neutral-200 bg-white p-5 dark:border-neutral-700 dark:bg-zinc-900">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
            Status Sistem
        </p>

        <div class="mt-2 flex items-center gap-2">
            <span
                class="inline-flex items-center rounded-full {{ $rekognitionOk && $geofenceAktifName !== 'Tidak ada' ? 'bg-green-600' : 'bg-yellow-600' }} px-2 py-0.5 text-xs font-semibold text-white">
                {{ $rekognitionOk && $geofenceAktifName !== 'Tidak ada' ? 'Sistem Normal' : 'Perlu Perhatian' }}
            </span>
        </div>

        <ul class="mt-3 space-y-1 text-sm text-gray-600 dark:text-gray-400">
            <li>Geofence aktif: <span
                    class="font-medium {{ $geofenceAktifName !== 'Tidak ada' ? 'text-gray-900 dark:text-white' : 'text-red-600 dark:text-red-400' }}">{{ $geofenceAktifName }}</span>
            </li>
            <li>Rekognition: <span
                    class="{{ $rekognitionOk ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} font-medium">{{ $rekognitionOk ? 'Connected' : 'Not Configured' }}</span>
            </li>
            <li>Absen terakhir: <span class="text-gray-900 dark:text-white">{{ $lastAttendanceTime }}</span></li>
        </ul>
    </div>
</div>
