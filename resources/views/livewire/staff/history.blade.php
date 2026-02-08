<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-6 flex items-start justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Riwayat Absensi Saya</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Data absensi Anda. Hanya Anda yang bisa melihat halaman ini.
            </p>
        </div>

        @if ($totalThisMonth > 0)
            <div class="px-4 py-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                <p class="text-xs text-blue-600 dark:text-blue-300 font-medium">Bulan Ini</p>
                <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">{{ $totalThisMonth }}</p>
                <p class="text-xs text-blue-600 dark:text-blue-300">absensi</p>
            </div>
        @endif
    </div>

    <livewire:staff.history-table lazy />
</div>
