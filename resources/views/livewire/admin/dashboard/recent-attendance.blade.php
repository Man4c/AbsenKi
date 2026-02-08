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
        @if ($recentRecords->isEmpty())
            <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                <svg class="mx-auto h-12 w-12 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <p class="text-sm font-medium">Belum ada data absensi</p>
                <p class="text-xs mt-1">Data absensi akan muncul setelah staff melakukan check-in/check-out</p>
            </div>
        @else
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
                    @foreach ($recentRecords as $record)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $record->user->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $record->user->email }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ $record->created_at->format('d M Y H:i') }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($record->type === 'in')
                                    <span
                                        class="inline-flex rounded bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-900 dark:text-green-200">
                                        Masuk
                                    </span>
                                @else
                                    <span
                                        class="inline-flex rounded bg-orange-100 px-2 py-0.5 text-xs font-semibold text-orange-700 dark:bg-orange-900 dark:text-orange-200">
                                        Pulang
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($record->geo_ok)
                                    <span
                                        class="inline-flex items-center gap-1 rounded bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700 dark:bg-green-900 dark:text-green-200">
                                        <span class="size-1.5 rounded-full bg-green-600"></span>
                                        Di dalam area
                                    </span>
                                @else
                                    <span
                                        class="inline-flex items-center gap-1 rounded bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700 dark:bg-red-900 dark:text-red-200">
                                        <span class="size-1.5 rounded-full bg-red-600"></span>
                                        Di luar area
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($record->face_score)
                                    <span class="font-medium">{{ number_format($record->face_score, 1) }}%</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($record->status === 'success' || $record->is_offsite)
                                    <span
                                        class="inline-flex rounded bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-900 dark:text-blue-200">
                                        Lolos
                                    </span>
                                @else
                                    <span
                                        class="inline-flex rounded bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                        Perlu Verifikasi
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
