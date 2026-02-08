<div>
    @if ($isOpen && $attendance)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-900/50 transition-opacity" wire:click="closeModal" aria-hidden="true">
                </div>

                <!-- Modal panel -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div
                    class="relative inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl dark:bg-gray-800 sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    Bukti Absensi Offsite
                                </h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $attendance->user->name }} - {{ $attendance->created_at->format('d M Y H:i') }}
                                </p>
                            </div>
                            <button wire:click="closeModal" type="button"
                                class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="px-6 py-4 space-y-4 max-h-[calc(100vh-250px)] overflow-y-auto">
                        <!-- File Info -->
                        <div class="grid grid-cols-2 gap-4 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Nama File</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ $attendance->evidence_file_names[0] ?? 'N/A' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Ukuran</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $attendance->evidence_size_formatted ?? 'N/A' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Tipe File</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $attendance->evidence_mime ?? 'N/A' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Diupload</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $attendance->evidence_uploaded_at ? $attendance->evidence_uploaded_at->format('d M Y H:i') : 'N/A' }}
                                </p>
                            </div>
                        </div>

                        <!-- Evidence Note -->
                        @if ($attendance->evidence_note)
                            <div
                                class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                <p class="text-xs font-medium text-blue-800 dark:text-blue-300 mb-1">Catatan:</p>
                                <p class="text-sm text-blue-700 dark:text-blue-400">{{ $attendance->evidence_note }}</p>
                            </div>
                        @endif

                        <!-- File Preview -->
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                            @if ($attendance->evidence_mime && str_starts_with($attendance->evidence_mime, 'image/'))
                                <!-- Image Preview -->
                                <div class="bg-gray-100 dark:bg-gray-900 p-4">
                                    <img src="{{ $attendance->evidence_url }}" alt="Bukti"
                                        class="max-w-full h-auto mx-auto rounded-lg shadow-lg"
                                        style="max-height: 500px;">
                                </div>
                            @elseif($attendance->evidence_mime === 'application/pdf')
                                <!-- PDF Preview -->
                                <div>
                                    <div class="bg-gray-100 dark:bg-gray-900">
                                        <iframe src="{{ $attendance->evidence_url }}" class="w-full border-0"
                                            style="height: 500px;">
                                        </iframe>
                                    </div>
                                    <div
                                        class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border-t border-yellow-200 dark:border-yellow-800">
                                        <p class="text-sm text-yellow-800 dark:text-yellow-300">
                                            💡 Jika PDF tidak tampil, klik tombol "Buka di Tab Baru" di bawah untuk
                                            melihat file.
                                        </p>
                                    </div>
                                </div>
                            @else
                                <!-- Unknown type -->
                                <div class="p-8 text-center">
                                    <svg class="w-16 h-16 mx-auto text-gray-400 dark:text-gray-600" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">Preview tidak tersedia
                                        untuk tipe file ini</p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">Gunakan tombol "Buka di Tab
                                        Baru" untuk melihat file</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                        <div class="flex justify-between items-center">
                            <a href="{{ $attendance->evidence_url }}" target="_blank"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-400 dark:hover:bg-blue-900/40">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                Buka di Tab Baru
                            </a>

                            <div class="flex gap-3">
                                <button type="button" wire:click="closeModal"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                                    Tutup
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
