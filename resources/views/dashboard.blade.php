<x-layouts.app :title="__('Admin Dashboard')">
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

        <!-- Stats Cards Component -->
        <livewire:admin.dashboard.stats-cards lazy />

        <!-- Recent Attendance Component -->
        <livewire:admin.dashboard.recent-attendance lazy />

    </div>
</x-layouts.app>
