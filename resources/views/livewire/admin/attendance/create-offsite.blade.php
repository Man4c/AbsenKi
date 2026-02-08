<div x-data="{ open: @entangle('isOpen') }" x-init="$watch('open', value => {
    if (value) {
        document.body.classList.add('overflow-hidden');
    } else {
        document.body.classList.remove('overflow-hidden');
    }
})" @keydown.window.escape="open = false">

    @if ($isOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">

                <div class="fixed inset-0 bg-gray-900/75 transition-opacity" wire:click="closeModal" aria-hidden="true">
                </div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div
                    class="relative inline-block overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl dark:bg-gray-800 sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                Tambah Entri Absensi Offsite
                            </h3>
                            <button wire:click="closeModal" type="button"
                                class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Form untuk mencatat absensi staff yang bekerja di luar kantor (offsite)
                        </p>
                    </div>

                    <form wire:submit.prevent="save">
                        <div class="px-6 py-4 space-y-4">
                            {{-- Staff Selection --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Staff
                                    <span class="text-red-500">*</span></label>
                                <select wire:model="userId"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                                    <option value="">-- Pilih Staff --</option>
                                    @foreach ($staffList as $staff)
                                        <option value="{{ $staff->id }}">{{ $staff->name }} ({{ $staff->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('userId')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Date & Time --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tanggal
                                        <span class="text-red-500">*</span></label>
                                    <input type="date" wire:model="attendanceDate"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                                    @error('attendanceDate')
                                        <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Jam
                                        <span class="text-red-500">*</span></label>
                                    <input type="time" wire:model="attendanceTime"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                                    @error('attendanceTime')
                                        <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            {{-- Type Selection --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Jenis
                                    Absensi <span class="text-red-500">*</span></label>
                                <div class="flex gap-4">
                                    <label class="flex items-center"><input type="radio" wire:model="type"
                                            value="in" class="w-4 h-4 text-blue-600"> <span
                                            class="ml-2 dark:text-white">Masuk</span></label>
                                    <label class="flex items-center"><input type="radio" wire:model="type"
                                            value="out" class="w-4 h-4 text-blue-600"> <span
                                            class="ml-2 dark:text-white">Keluar</span></label>
                                    <label class="flex items-center"><input type="radio" wire:model="type"
                                            value="hadir" class="w-4 h-4 text-blue-600"> <span
                                            class="ml-2 dark:text-white">Hadir
                                            (Full)</span></label>
                                </div>
                                @error('type')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Location --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Lokasi
                                    <span class="text-red-500">*</span></label>
                                <textarea wire:model="location" rows="2"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                                    placeholder="Masukkan lokasi"></textarea>
                                @error('location')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Coordinates --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Koordinat
                                    (Opsional)</label>
                                <input type="text" wire:model="coordinates"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"
                                    placeholder="-6.200000, 106.816666">
                            </div>

                            {{-- Evidence --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bukti
                                    (Opsional)</label>
                                <input type="file" wire:model="evidence" accept=".jpg,.jpeg,.png,.pdf"
                                    class="block w-full text-sm text-gray-500 border border-gray-300 rounded-lg cursor-pointer focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-gray-200 file:text-gray-700 hover:file:bg-gray-300 dark:file:bg-gray-600 dark:file:text-white">

                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Format: JPG, PNG, PDF | Maksimal: 5MB
                                </p>

                                @error('evidence')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror

                                {{-- Loading indicator --}}
                                <div wire:loading wire:target="evidence" class="mt-2">
                                    <span class="text-sm text-blue-600 dark:text-blue-400">⏳ Mengupload file...</span>
                                </div>

                                {{-- Preview after upload --}}
                                @if ($evidence)
                                    <div class="mt-3">
                                        <div
                                            class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-5 h-5 text-green-600 dark:text-green-400 flex-shrink-0"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <p class="text-sm font-medium text-green-800 dark:text-green-300">
                                                    File berhasil dipilih: <span
                                                        class="font-normal">{{ $evidence->getClientOriginalName() }}</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Evidence Note --}}
                            @if ($evidence)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Catatan Bukti (Opsional)
                                    </label>
                                    <textarea wire:model="evidenceNote" rows="2"
                                        class="w-full px-3 py-2 border dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white @error('evidenceNote') border-red-500 @enderror"
                                        placeholder="Contoh: Foto saat meeting dengan klien, Dokumen penawaran, dll"></textarea>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Tambahkan keterangan tentang file bukti yang diupload
                                    </p>
                                    @error('evidenceNote')
                                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif
                        </div>

                        <div
                            class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 flex justify-end gap-3">
                            <button type="button" wire:click="closeModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                                Batal
                            </button>
                            <button type="submit" wire:loading.attr="disabled"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50">
                                <span wire:loading.remove wire:target="save">Simpan</span>
                                <span wire:loading wire:target="save">Menyimpan...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
