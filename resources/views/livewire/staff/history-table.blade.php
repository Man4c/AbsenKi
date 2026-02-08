<div>
    <!-- Attendance & Leave Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        @if ($records->isEmpty())
            <!-- Empty State -->
            <div class="p-12 text-center">
                <svg class="mx-auto h-16 w-16 text-gray-400 dark:text-gray-500 mb-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Belum ada catatan</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Silakan lakukan absen dari menu <a href="{{ route('staff.absen') }}"
                        class="text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">Absen</a>.
                </p>
            </div>
        @else
            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Tanggal
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Jenis
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Keterangan
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($records as $record)
                            @if ($record['type'] === 'attendance')
                                @php $attendance = $record['data']; @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                    <!-- Tanggal -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $attendance->created_at->format('d M Y H:i') }}
                                    </td>

                                    <!-- Jenis -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($attendance->type === 'in')
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                Absen Masuk
                                            </span>
                                        @else
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                                Absen Pulang
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Keterangan -->
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                        <div class="flex flex-col gap-1">
                                            @if ($attendance->is_offsite)
                                                <span class="text-xs font-medium text-purple-600 dark:text-purple-400">
                                                    Luar Kantor: {{ $attendance->offsite_location_text }}
                                                </span>
                                                @if ($attendance->evidence_path)
                                                    <a href="{{ Storage::url($attendance->evidence_path) }}"
                                                        target="_blank"
                                                        class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                                        📎 Lihat Bukti
                                                    </a>
                                                @endif
                                            @else
                                                <span class="text-xs">
                                                    @if ($attendance->geo_ok)
                                                        ✅ Di dalam area
                                                    @else
                                                        ❌ Di luar area
                                                    @endif
                                                </span>
                                            @endif
                                            @if ($attendance->face_score)
                                                <span class="text-xs">
                                                    Face: {{ number_format($attendance->face_score, 1) }}%
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($attendance->status === 'success')
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                Berhasil
                                            </span>
                                        @else
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                                Gagal
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @else
                                @php $leave = $record['data']; @endphp
                                <tr
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700 transition bg-blue-50 dark:bg-blue-900/20">
                                    <!-- Tanggal -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $leave->start_date->format('d M Y') }}
                                        @if ($leave->start_date->format('Y-m-d') !== $leave->end_date->format('Y-m-d'))
                                            - {{ $leave->end_date->format('d M Y') }}
                                        @endif
                                    </td>

                                    <!-- Jenis -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                            @if ($leave->type === 'izin') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                            @elseif($leave->type === 'cuti') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                            @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 @endif">
                                            {{ $leave->type_label }}
                                        </span>
                                    </td>

                                    <!-- Keterangan -->
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                        <div class="flex flex-col gap-1">
                                            <span class="font-medium">{{ $leave->reason ?? '-' }}</span>
                                            <span class="text-xs text-gray-500">
                                                Durasi: {{ $leave->duration }} hari
                                            </span>
                                            @if ($leave->evidence_path)
                                                <a href="{{ Storage::url($leave->evidence_path) }}" target="_blank"
                                                    class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                                    📎 Lihat Bukti
                                                </a>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            Disetujui
                                        </span>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Footer Info -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Menampilkan {{ $records->count() }} dari 30 catatan terakhir (Absensi & Izin/Cuti)
                </p>
            </div>
        @endif
    </div>
</div>
