<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
            <a href="{{ route('admin.faces') }}" wire:navigate
                class="text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white">
                ← Kembali
            </a>
        </div>

        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            Kelola Wajah: {{ $user->name }}
        </h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            {{ $user->email }}
        </p>
    </div>

    @if (session()->has('saved'))
        <div
            class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded dark:bg-green-900 dark:text-green-200">
            {{ session('saved') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Upload Section -->
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
                Upload Foto Wajah
            </h2>

            <form wire:submit="uploadFace">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                        Pilih Foto Wajah (Multiple)
                    </label>

                    <!-- Dropzone + Input -->
                    <div x-data="faceUploader()" x-on:dragover.prevent="dragging = true"
                        x-on:dragleave.prevent="dragging = false"
                        x-on:drop.prevent="
                            dragging = false;
                            handleDrop($event, @this)
                        "
                        :class="dragging ? 'border-blue-500 bg-blue-50/40 dark:bg-blue-900/20' : ''"
                        class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center hover:border-blue-500 dark:hover:border-blue-400 transition-colors">
                        <!-- hidden file input yang di-bind ke Livewire -->
                        <input type="file" wire:model="photos" accept="image/jpeg,image/jpg,image/png" multiple
                            id="photos-input" class="hidden" x-ref="fileInput" x-on:change="filesChosen($event, @this)">

                        <label for="photos-input" class="cursor-pointer block">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                                viewBox="0 0 48 48">
                                <path
                                    d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>

                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                <span class="font-semibold text-blue-600 dark:text-blue-400">
                                    Klik untuk upload
                                </span>
                                atau drag & drop
                            </p>

                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                JPG, PNG maksimal 2MB per foto
                            </p>

                            {{-- <p class="text-xs text-blue-600 dark:text-blue-400 mt-2 font-medium">
                                💡 Tips: Tekan Ctrl + Klik untuk pilih multiple foto sekaligus
                            </p> --}}
                        </label>
                    </div>

                    @error('photos')
                        <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                    @enderror
                    @error('photos.*')
                        <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                    @enderror

                    <!-- Preview list foto yang baru dipilih (belum dikirim ke AWS) -->
                    @if ($photos)
                        <div class="mt-4 space-y-2">
                            @foreach ($photos as $idx => $photo)
                                <div
                                    class="flex items-center gap-3 p-3 border dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800">
                                    <img src="{{ $photo->temporaryUrl() }}"
                                        class="w-16 h-16 object-cover rounded border dark:border-gray-600"
                                        alt="Preview">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                            {{ $photo->getClientOriginalName() }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ number_format($photo->getSize() / 1024, 1) }} KB
                                        </p>
                                    </div>
                                    <button type="button" wire:click="removePhoto({{ $idx }})"
                                        class="px-3 py-1 text-xs text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                        Hapus
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Tips Upload -->
                <div
                    class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                    <p class="text-sm font-semibold text-blue-900 dark:text-blue-200 mb-2">
                        📋 Panduan Upload:
                    </p>
                    <ul class="text-xs text-blue-800 dark:text-blue-300 space-y-1">
                        <li>• Wajah harus terlihat jelas dan menghadap kamera</li>
                        <li>• Pencahayaan cukup (tidak terlalu gelap)</li>
                        <li>• Minimal resolusi 200x200 pixels</li>
                        <li>• Format: JPEG, JPG, atau PNG</li>
                        <li>• Ukuran maksimal 2MB</li>
                        <li>• Upload 3-5 foto dengan pose/angle berbeda untuk akurasi lebih baik</li>
                    </ul>
                </div>

                <!-- Submit -->
                <button type="submit"
                    class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
                    wire:loading.attr="disabled">
                    <span wire:loading.remove>Daftarkan Wajah</span>
                    <span wire:loading>Memproses...</span>
                </button>
            </form>
        </div>

        <!-- Registered Faces List -->
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
                Wajah Terdaftar ({{ $user->faceProfiles->count() }})
            </h2>

            @if ($user->faceProfiles->isEmpty())
                <p class="text-gray-500 dark:text-gray-400 text-sm">
                    Belum ada wajah terdaftar. Upload foto di sebelah kiri.
                </p>
            @else
                <div class="space-y-4">
                    @foreach ($user->faceProfiles as $profile)
                        <div class="border dark:border-gray-700 rounded-lg p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                                        <p>
                                            <strong>Face ID:</strong>
                                            <span class="font-mono text-xs">
                                                {{ substr($profile->face_id, 0, 20) }}...
                                            </span>
                                        </p>
                                        <p>
                                            <strong>Uploaded:</strong>
                                            {{ $profile->created_at->format('d M Y H:i') }}
                                        </p>
                                        <p>
                                            <strong>Confidence:</strong>
                                            {{ number_format($profile->confidence, 1) }}%
                                        </p>
                                        <p>
                                            <strong>Provider:</strong>
                                            {{ strtoupper($profile->provider) }}
                                        </p>
                                    </div>

                                    @if ($profile->image_path && Storage::exists($profile->image_path))
                                        <div class="mt-2">
                                            <img src="data:image/jpeg;base64,{{ base64_encode(Storage::get($profile->image_path)) }}"
                                                class="w-24 h-24 object-cover rounded border dark:border-gray-600"
                                                alt="Face photo">
                                        </div>
                                    @endif
                                </div>

                                <button wire:click="deleteFace({{ $profile->id }})"
                                    wire:confirm="Hapus foto wajah ini?"
                                    class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs rounded transition-colors">
                                    Hapus
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Alpine helper for drag & drop --}}
<script>
    function faceUploader() {
        return {
            dragging: false,

            // dipanggil ketika user pilih file lewat klik input
            filesChosen(event, livewire) {
                // Livewire sudah auto-sync wire:model="photos"
                // jadi kita gak perlu apa2 di sini sekarang.
            },

            // dipanggil ketika user drop file di area dropzone
            handleDrop(e, livewire) {
                const dt = e.dataTransfer;
                if (!dt || !dt.files || dt.files.length === 0) return;

                // file-file yg di-drop
                const droppedFiles = dt.files;

                // masukkan file hasil drop ke input hidden
                this.$refs.fileInput.files = droppedFiles;

                // paksa trigger change supaya Livewire notice
                const changeEvent = new Event('change', {
                    bubbles: true
                });
                this.$refs.fileInput.dispatchEvent(changeEvent);
            }
        }
    }
</script>
