<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Pengaturan Jam Kerja</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Atur jadwal jam masuk & pulang untuk setiap hari dalam seminggu
        </p>
    </div>

    <!-- Success Message -->
    @if (session()->has('message'))
        <div
            class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg dark:bg-green-900 dark:border-green-700 dark:text-green-200">
            {{ session('message') }}
        </div>
    @endif

    <!-- Schedule Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Hari</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Jam Masuk</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Jam Pulang</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Toleransi</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Status</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($schedules as $schedule)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][$schedule['day_of_week']] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ substr($schedule['in_time'], 0, 5) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ substr($schedule['out_time'], 0, 5) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs">
                                <div class="space-y-1">
                                    <div class="text-gray-600 dark:text-gray-400">
                                        Terlambat: <span class="font-medium">{{ $schedule['grace_late_minutes'] }}
                                            menit</span>
                                    </div>
                                    <div class="text-gray-600 dark:text-gray-400">
                                        Pulang cepat: <span class="font-medium">{{ $schedule['grace_early_minutes'] }}
                                            menit</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button wire:click="toggleActive({{ $schedule['day_of_week'] }})"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                                    {{ $schedule['is_active'] ? 'bg-green-600' : 'bg-gray-300 dark:bg-gray-600' }}">
                                    <span
                                        class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform
                                        {{ $schedule['is_active'] ? 'translate-x-6' : 'translate-x-1' }}">
                                    </span>
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <button wire:click="editDay({{ $schedule['day_of_week'] }})"
                                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                    Edit
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Modal -->
    @if ($editingDay !== null)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-900/50 transition-opacity" wire:click="cancelEdit" aria-hidden="true">
                </div>

                <!-- Modal panel -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div
                    class="relative inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl dark:bg-gray-800 sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Edit Jadwal -
                                {{ ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][$dayOfWeek] }}
                            </h3>
                            <button wire:click="cancelEdit" type="button"
                                class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Body -->
                    <form wire:submit.prevent="save">
                        <div class="px-6 py-4 space-y-4 max-h-[calc(100vh-300px)] overflow-y-auto">
                            <!-- Active Status -->
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                <div>
                                    <label class="text-sm font-medium text-gray-900 dark:text-white">Hari Kerja
                                        Aktif</label>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Nonaktifkan untuk hari
                                        libur/weekend</p>
                                </div>
                                <input type="checkbox" wire:model="isActive"
                                    class="h-5 w-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            </div>

                            <!-- Working Hours -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Jam Masuk <span class="text-red-500">*</span>
                                    </label>
                                    <input type="time" wire:model="inTime"
                                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white {{ $errors->has('inTime') ? 'border-red-500' : 'border-gray-300 dark:border-gray-600' }}">
                                    @error('inTime')
                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Jam Pulang <span class="text-red-500">*</span>
                                    </label>
                                    <input type="time" wire:model="outTime"
                                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white {{ $errors->has('outTime') ? 'border-red-500' : 'border-gray-300 dark:border-gray-600' }}">
                                    @error('outTime')
                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Grace Periods -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Toleransi Terlambat (menit)
                                    </label>
                                    <input type="number" wire:model="graceLateMinutes" min="0" max="60"
                                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white {{ $errors->has('graceLateMinutes') ? 'border-red-500' : 'border-gray-300 dark:border-gray-600' }}">
                                    @error('graceLateMinutes')
                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Toleransi Pulang Cepat (menit)
                                    </label>
                                    <input type="number" wire:model="graceEarlyMinutes" min="0" max="60"
                                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white {{ $errors->has('graceEarlyMinutes') ? 'border-red-500' : 'border-gray-300 dark:border-gray-600' }}">
                                    @error('graceEarlyMinutes')
                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <!-- Lock Windows (Optional) -->
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Pembatasan Waktu
                                    Absen (Opsional)</h4>

                                <!-- Check-in Lock -->
                                <div class="mb-4">
                                    <label class="block text-sm text-gray-700 dark:text-gray-300 mb-2">Rentang Waktu
                                        Absen Masuk</label>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <input type="time" wire:model="lockInStart" placeholder="Dari"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                                        </div>
                                        <div>
                                            <input type="time" wire:model="lockInEnd" placeholder="Sampai"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                                        </div>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Contoh: 06:30 - 09:00
                                        (staff hanya bisa absen masuk dalam rentang ini)</p>
                                </div>

                                <!-- Check-out Lock -->
                                <div>
                                    <label class="block text-sm text-gray-700 dark:text-gray-300 mb-2">Rentang Waktu
                                        Absen Pulang</label>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <input type="time" wire:model="lockOutStart" placeholder="Dari"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                                        </div>
                                        <div>
                                            <input type="time" wire:model="lockOutEnd" placeholder="Sampai"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                                        </div>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Contoh: 15:00 - 20:00
                                        (staff hanya bisa absen pulang dalam rentang ini)</p>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div
                            class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                            <div class="flex justify-end gap-3">
                                <button type="button" wire:click="cancelEdit"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                                    Batal
                                </button>
                                <button type="submit" wire:loading.attr="disabled"
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50">
                                    <span wire:loading.remove wire:target="save">Simpan</span>
                                    <span wire:loading wire:target="save">Menyimpan...</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
