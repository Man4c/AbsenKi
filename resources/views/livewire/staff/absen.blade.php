<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="mb-5 sm:mb-6">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">Absensi Staff</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Verifikasi lokasi dan wajah Anda untuk melakukan absensi
        </p>

        <!-- Schedule Info -->
        @if ($isOnLeave)
            <div
                class="mt-3 p-3 bg-yellow-100 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-700 rounded-lg">
                <div class="flex items-center gap-2 text-yellow-800 dark:text-yellow-200">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <p class="font-semibold text-sm">
                            Anda sedang
                            @if ($leaveType === 'izin')
                                Izin
                            @elseif($leaveType === 'cuti')
                                Cuti
                            @else
                                Sakit
                            @endif
                            ({{ $leaveStartDate }} - {{ $leaveEndDate }})
                        </p>
                        <p class="text-xs mt-0.5">Absensi tidak diperlukan selama periode ini.</p>
                    </div>
                </div>
            </div>
        @elseif ($isHoliday)
            <div class="mt-3 p-3 bg-red-100 dark:bg-red-900 border border-red-300 dark:border-red-700 rounded-lg">
                <div class="flex items-center gap-2 text-red-800 dark:text-red-200">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <p class="font-semibold text-sm">Hari Libur: {{ $holidayName }}</p>
                        <p class="text-xs mt-0.5">Absensi tidak dapat dilakukan pada hari libur</p>
                    </div>
                </div>
            </div>
        @elseif ($todaySchedule)
            <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <div class="flex items-center gap-2 text-blue-800 dark:text-blue-200">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div class="text-sm">
                        <p class="font-semibold">Jadwal Kerja Hari Ini</p>
                        <p class="text-xs mt-0.5">
                            Jam Masuk: {{ substr($todaySchedule['in_time'], 0, 5) }} •
                            Jam Pulang: {{ substr($todaySchedule['out_time'], 0, 5) }}
                            @if ($todaySchedule['grace_late_minutes'] > 0 || $todaySchedule['grace_early_minutes'] > 0)
                                • Toleransi: {{ $todaySchedule['grace_late_minutes'] }} menit
                            @else
                                • Toleransi: 0 menit
                            @endif
                        </p>
                        @if ($todaySchedule['lock_in_start'] && $todaySchedule['lock_in_end'])
                            <p class="text-xs mt-1 text-blue-700 dark:text-blue-300">
                                ⏰ Absen masuk: {{ substr($todaySchedule['lock_in_start'], 0, 5) }} -
                                {{ substr($todaySchedule['lock_in_end'], 0, 5) }}
                            </p>
                        @endif
                        @if ($todaySchedule['lock_out_start'] && $todaySchedule['lock_out_end'])
                            <p class="text-xs text-blue-700 dark:text-blue-300">
                                ⏰ Absen keluar: {{ substr($todaySchedule['lock_out_start'], 0, 5) }} -
                                {{ substr($todaySchedule['lock_out_end'], 0, 5) }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        @else
            <div
                class="mt-3 p-3 bg-yellow-100 dark:bg-yellow-900 border border-yellow-300 dark:border-yellow-700 rounded-lg">
                <div class="flex items-center gap-2 text-yellow-800 dark:text-yellow-200">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <p class="font-semibold text-sm">Tidak Ada Jadwal Kerja</p>
                        <p class="text-xs mt-0.5">Absensi tidak dapat dilakukan hari ini</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Message Alert -->
    @if ($message)
        <div
            class="mb-4 sm:mb-6 p-3 sm:p-4 text-sm sm:text-base rounded-lg border {{ $messageType === 'success' ? 'bg-green-100 border-green-400 text-green-700 dark:bg-green-900 dark:text-green-200' : ($messageType === 'error' ? 'bg-red-100 border-red-400 text-red-700 dark:bg-red-900 dark:text-red-200' : 'bg-blue-100 border-blue-400 text-blue-700 dark:bg-blue-900 dark:text-blue-200') }}">
            {{ $message }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
        <!-- Left Column: Location & Camera -->
        <div class="space-y-4 sm:space-y-6">
            <!-- Location Check -->
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-4 sm:p-6">
                <h2
                    class="text-base sm:text-lg font-semibold mb-3 sm:mb-4 text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Cek Lokasi
                </h2>

                <button type="button" id="checkLocationBtn"
                    class="w-full px-4 py-2.5 sm:py-2 text-sm sm:text-base bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Cek Lokasi Saya
                </button>

                @if ($lat && $lng)
                    <!-- Mini Map -->
                    <div
                        class="mt-3 sm:mt-4 rounded-lg overflow-hidden border-2 {{ $geoStatus === 'inside' ? 'border-gray-300' : 'border-red-500' }}">
                        <div id="locationMap" wire:ignore class="h-48 sm:h-64 w-full"></div>
                    </div>

                    <div class="mt-3 sm:mt-4 p-2.5 sm:p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-xs sm:text-sm">
                        <p class="text-gray-700 dark:text-gray-300"><strong>Latitude:</strong> {{ $lat }}
                        </p>
                        <p class="text-gray-700 dark:text-gray-300"><strong>Longitude:</strong> {{ $lng }}
                        </p>
                        <p class="mt-2">
                            <span
                                class="px-2 py-1 rounded text-xs font-semibold {{ $geoStatus === 'inside' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                {{ $geoStatus === 'inside' ? '✅ Di area kantor' : '❌ Di luar area' }}
                            </span>
                        </p>
                    </div>
                @endif
            </div>

            <!-- Camera Preview -->
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-4 sm:p-6">
                <h2
                    class="text-base sm:text-lg font-semibold mb-3 sm:mb-4 text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Verifikasi Wajah
                </h2>

                <div class="space-y-3">
                    <div class="relative" wire:ignore>
                        <video id="cameraPreview" autoplay playsinline
                            class="w-full rounded-lg border dark:border-gray-600 aspect-[3/4] sm:aspect-auto object-cover"
                            style="display: none;"></video>
                    </div>

                    <div class="flex gap-2" wire:ignore>
                        <button type="button" id="startCameraBtn"
                            class="flex-1 px-3 sm:px-4 py-2.5 sm:py-2 text-sm sm:text-base bg-green-600 text-white rounded-lg hover:bg-green-700">
                            Mulai Kamera
                        </button>
                        <button type="button" id="captureBtn" style="display: none;"
                            class="flex-1 px-3 sm:px-4 py-2.5 sm:py-2 text-sm sm:text-base bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                            Ambil Foto
                        </button>
                    </div>

                    @if ($facePreview)
                        <div class="mt-2 sm:mt-3 relative">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1.5 sm:mb-2">
                                Snapshot:
                                @if ($faceCount > 0)
                                    <span class="text-blue-600 dark:text-blue-400">{{ $faceCount }} wajah
                                        terdeteksi</span>
                                @endif
                            </p>
                            <div class="relative inline-block" wire:key="snapshot-{{ $snapshotTimestamp }}">
                                <img src="{{ $facePreview }}" id="snapshotImage"
                                    class="w-full rounded-lg border dark:border-gray-600" alt="Face preview">
                                <canvas id="snapshotCanvas"
                                    class="absolute top-0 left-0 w-full h-full pointer-events-none"></canvas>
                            </div>
                        </div>
                    @endif

                    <!-- Loading State -->
                    <div id="verifyingLoader" style="display: none;" class="text-center py-2 sm:py-4">
                        <div
                            class="inline-flex items-center gap-2 sm:gap-3 px-3 sm:px-4 py-2 sm:py-3 bg-blue-50 dark:bg-blue-900/30 rounded-lg">
                            <svg class="animate-spin h-5 w-5 text-blue-600 dark:text-blue-400"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span class="text-sm font-medium text-blue-700 dark:text-blue-300">Memverifikasi
                                wajah...</span>
                        </div>
                    </div>



                    @if ($faceOk && $faceScore)
                        <div
                            class="mt-2 sm:mt-3 p-2.5 sm:p-3 bg-green-50 dark:bg-green-900 rounded-lg border border-green-200">
                            <p class="text-xs sm:text-sm font-semibold text-green-800 dark:text-green-200">
                                ✅ Terdeteksi:
                                @if ($faceName)
                                    <span class="font-bold">{{ $faceName }}</span> •
                                @endif
                                {{ number_format($faceScore, 2) }}%
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Right Column: Attendance Actions -->
        <div class="space-y-3 sm:space-y-6">
            <!-- Status Summary -->
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-4 sm:p-6">
                <h2 class="text-base sm:text-lg font-semibold mb-3 sm:mb-4 text-gray-900 dark:text-white">Status
                    Verifikasi</h2>

                <div class="space-y-2 sm:space-y-3">
                    <div class="flex items-center justify-between p-2.5 sm:p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <span class="text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300">Lokasi</span>
                        <span
                            class="px-2 py-1 rounded text-xs font-semibold {{ $geoStatus === 'inside' ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }}">
                            {{ $geoStatus === 'inside' ? 'OK' : ($geoStatus === 'outside' ? 'Gagal' : 'Belum Dicek') }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between p-2.5 sm:p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <span class="text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300">Wajah</span>
                        <span
                            class="px-2 py-1 rounded text-xs font-semibold {{ $faceOk ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-600' }}">
                            {{ $faceOk ? 'OK' : 'Belum Dicek' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Attendance Buttons -->
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-4 sm:p-6">
                <h2 class="text-base sm:text-lg font-semibold mb-3 sm:mb-4 text-gray-900 dark:text-white">Absensi</h2>

                <div class="space-y-2 sm:space-y-3">
                    <button type="button" wire:click="commitAttendance('in')"
                        {{ !$this->canDoCheckIn ? 'disabled' : '' }}
                        class="w-full px-4 py-2.5 sm:py-3 text-sm sm:text-base bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <span class="font-semibold">Absen Masuk</span>
                        @if ($this->hasCheckedInToday)
                            <span class="block text-xs mt-1 opacity-75">Sudah absen masuk hari ini</span>
                        @endif
                    </button>

                    <button type="button" wire:click="commitAttendance('out')"
                        {{ !$this->canDoCheckOut ? 'disabled' : '' }}
                        class="w-full px-4 py-2.5 sm:py-3 text-sm sm:text-base bg-orange-600 text-white rounded-lg hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <span class="font-semibold">Absen Pulang</span>
                        @if ($this->hasCheckedOutToday)
                            <span class="block text-xs mt-1 opacity-75">Sudah absen pulang hari ini</span>
                        @endif
                    </button>

                    @if (!$this->canCheckIn)
                        <p class="text-xs text-gray-500 dark:text-gray-400 text-center mt-2">
                            Selesaikan verifikasi lokasi dan wajah terlebih dahulu
                        </p>
                    @elseif ($this->hasCheckedInToday && $this->hasCheckedOutToday)
                        <p class="text-xs text-green-600 dark:text-green-400 text-center mt-2">
                            ✅ Anda sudah absen masuk dan keluar hari ini
                        </p>
                    @endif
                </div>
            </div>

            <!-- Info Panel -->
            <div
                class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 sm:p-4">
                <p class="text-xs sm:text-sm font-semibold text-blue-900 dark:text-blue-200 mb-1.5 sm:mb-2">📋 Langkah
                    Absensi:</p>
                <ol class="text-xs text-blue-800 dark:text-blue-300 space-y-0.5 sm:space-y-1 list-decimal list-inside">
                    <li>Klik "Cek Lokasi Saya" untuk memastikan Anda di area kantor</li>
                    <li>Klik "Mulai Kamera" dan "Ambil Foto" untuk verifikasi wajah</li>
                    <li>Jika kedua verifikasi berhasil, klik "Absen Masuk" atau "Absen Pulang"</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <!-- Fix Leaflet z-index issue with sidebar -->
    <style>
        #locationMap {
            position: relative;
            z-index: 1 !important;
        }

        .leaflet-pane,
        .leaflet-top,
        .leaflet-bottom {
            z-index: auto !important;
        }

        .leaflet-container {
            z-index: 1 !important;
        }
    </style>

</div>

@script
    <script>
        // SOLUSI: Matikan kamera saat user berpindah halaman (SPA Navigation)
        document.addEventListener('livewire:navigating', () => {
            console.log('🛑 Navigasi terdeteksi, mematikan kamera...');
            stopCamera();
        });

        // Cadangan: Tetap pakai ini untuk jaga-jaga kalau user tutup tab/browser
        window.addEventListener('beforeunload', () => {
            stopCamera();
        });

        let stream = null;
        let currentBoundingBox = null; // Store current bounding box data
        let locationMap = null; // Leaflet map instance

        const video = document.getElementById('cameraPreview');
        const startCameraBtn = document.getElementById('startCameraBtn');
        const captureBtn = document.getElementById('captureBtn');
        const checkLocationBtn = document.getElementById('checkLocationBtn');

        /**
         * Initialize or update location map
         * @param {number} lat - User latitude
         * @param {number} lng - User longitude
         * @param {Object|null} geofence - Geofence polygon data
         * @param {string} status - 'inside' or 'outside'
         */
        function updateLocationMap(lat, lng, geofence, status) {
            const mapElement = document.getElementById('locationMap');
            if (!mapElement) return;

            // Initialize map if not exists
            if (!locationMap) {
                locationMap = L.map('locationMap', {
                    zoomControl: true,
                    dragging: true,
                    touchZoom: true,
                    scrollWheelZoom: false,
                    doubleClickZoom: false,
                    boxZoom: false,
                    keyboard: false,
                }).setView([lat, lng], 19);

                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '© OpenStreetMap contributors',
                    maxZoom: 19,
                }).addTo(locationMap);
            } else {
                // Update view
                locationMap.setView([lat, lng], 19);
                locationMap.eachLayer((layer) => {
                    if (layer instanceof L.Marker || layer instanceof L.Polygon) {
                        locationMap.removeLayer(layer);
                    }
                });
            }

            // Add user marker
            const userIcon = L.divIcon({
                className: 'custom-user-marker',
                // atur besar kecil Titik Biru
                html: '<div style="background: #3b82f6; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>',
                iconSize: [12, 12],
                iconAnchor: [6, 6],
            });

            L.marker([lat, lng], {
                    icon: userIcon
                })
                .addTo(locationMap)
                .bindPopup('<b>Posisi Anda</b>');

            // Add geofence polygon if available
            if (geofence && geofence.coordinates && geofence.coordinates[0]) {
                const coords = geofence.coordinates[0].map(coord => [coord[1], coord[0]]); // Swap lng,lat to lat,lng
                const color = status === 'inside' ? '#10b981' : '#ef4444';

                L.polygon(coords, {
                    color: color,
                    fillColor: color,
                    fillOpacity: 0.2,
                    weight: 2,
                }).addTo(locationMap).bindPopup('<b>Area Kantor</b>');
            }

            // Fit bounds to show both user and geofence
            setTimeout(() => {
                if (locationMap) {
                    locationMap.invalidateSize();
                }
            }, 100);
        }
        /**
         * Draw bounding box with name label on canvas
         * @param {HTMLCanvasElement} targetCanvas - Canvas element to draw on
         * @param {HTMLImageElement|HTMLVideoElement} sourceElement - Source image/video
         * @param {Object} box - AWS BoundingBox object {Width, Height, Left, Top}
         * @param {string|null} name - User name to display
         * @param {number|null} score - Match score percentage
         */
        function drawBoundingBox(targetCanvas, sourceElement, box, name = null, score = null) {
            // Validate required parameters silently (no console error)
            if (!box || !targetCanvas || !sourceElement) {
                return;
            }

            const ctx = targetCanvas.getContext('2d');

            // Set canvas size to match the DISPLAYED size of the image (not natural size)
            // This ensures the bounding box coordinates align correctly
            targetCanvas.width = sourceElement.offsetWidth || sourceElement.width;
            targetCanvas.height = sourceElement.offsetHeight || sourceElement.height;

            // Clear previous drawings
            ctx.clearRect(0, 0, targetCanvas.width, targetCanvas.height);

            // Calculate box coordinates (AWS returns normalized values 0-1)
            const x = box.Left * targetCanvas.width;
            const y = box.Top * targetCanvas.height;
            const width = box.Width * targetCanvas.width;
            const height = box.Height * targetCanvas.height;

            // Draw green rectangle
            ctx.strokeStyle = '#10b981'; // Green color
            ctx.lineWidth = 3;
            ctx.strokeRect(x, y, width, height);

            // Draw label with name and score if available
            if (name && score) {
                const displayScore = score >= 99.95 ? '100' : score.toFixed(1);
                const labelText = `${name} (${displayScore}%)`;

                // Measure text width for dynamic label size
                ctx.font = 'bold 14px sans-serif';
                const textMetrics = ctx.measureText(labelText);
                const labelWidth = textMetrics.width + 16; // Add padding
                const labelHeight = 25;

                // Draw semi-transparent background for label above the box
                ctx.fillStyle = 'rgba(16, 185, 129, 0.9)';
                ctx.fillRect(x, y - labelHeight, labelWidth, labelHeight);

                // Draw name and score text
                ctx.fillStyle = '#ffffff';
                ctx.fillText(labelText, x + 8, y - 7);
            } else {
                // Fallback to "Wajah" if no name
                ctx.fillStyle = 'rgba(16, 185, 129, 0.8)';
                ctx.fillRect(x, y - 25, 80, 25);

                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 14px sans-serif';
                ctx.fillText('Wajah', x + 5, y - 7);
            }
        }

        /**
         * Draw multiple bounding boxes with different colors based on type
         * @param {HTMLCanvasElement} targetCanvas - Canvas element to draw on
         * @param {HTMLImageElement} sourceElement - Source image
         * @param {Array} boundingBoxes - Array of {box, label, type, score}
         */
        function drawBoundingBoxes(targetCanvas, sourceElement, boundingBoxes) {
            // Validate required parameters silently (no console error)
            if (!targetCanvas || !sourceElement || !boundingBoxes || boundingBoxes.length === 0) {
                return;
            }

            console.log('🎨 drawBoundingBoxes called with', boundingBoxes.length, 'boxes');

            const ctx = targetCanvas.getContext('2d');

            // Set canvas size to match the DISPLAYED size of the image
            targetCanvas.width = sourceElement.offsetWidth || sourceElement.width;
            targetCanvas.height = sourceElement.offsetHeight || sourceElement.height;

            console.log('📐 Canvas size set to:', targetCanvas.width, 'x', targetCanvas.height);

            // Clear previous drawings
            ctx.clearRect(0, 0, targetCanvas.width, targetCanvas.height);

            // Draw each bounding box
            boundingBoxes.forEach((item, index) => {
                const box = item.box;
                const label = item.label;
                const type = item.type;
                const score = item.score;

                // Calculate box coordinates (AWS returns normalized values 0-1)
                const x = box.Left * targetCanvas.width;
                const y = box.Top * targetCanvas.height;
                const width = box.Width * targetCanvas.width;
                const height = box.Height * targetCanvas.height;

                console.log(`📦 Box ${index + 1}:`, {
                    x,
                    y,
                    width,
                    height,
                    type,
                    label
                });

                // Choose color based on type
                let boxColor, labelBgColor;
                switch (type) {
                    case 'matched':
                        boxColor = '#10b981'; // Green - matched user
                        labelBgColor = 'rgba(16, 185, 129, 0.9)';
                        break;
                    case 'wrong_user':
                        boxColor = '#ef4444'; // Red - wrong user detected
                        labelBgColor = 'rgba(239, 68, 68, 0.9)';
                        break;
                    case 'other':
                        boxColor = '#f59e0b'; // Orange - other face
                        labelBgColor = 'rgba(245, 158, 11, 0.9)';
                        break;
                    case 'unknown':
                        boxColor = '#6b7280'; // Gray - unknown
                        labelBgColor = 'rgba(107, 114, 128, 0.9)';
                        break;
                    default:
                        boxColor = '#10b981';
                        labelBgColor = 'rgba(16, 185, 129, 0.9)';
                }

                // Draw rectangle
                ctx.strokeStyle = boxColor;
                ctx.lineWidth = 3;
                ctx.strokeRect(x, y, width, height);

                // Prepare label text
                let labelText = label;
                if (score) {
                    const displayScore = score.toFixed(2);
                    labelText += ` (${displayScore}%)`;
                }

                // Draw label
                ctx.font = 'bold 14px sans-serif';
                const textMetrics = ctx.measureText(labelText);
                const labelWidth = textMetrics.width + 16;
                const labelHeight = 25;

                // Draw semi-transparent background for label above the box
                ctx.fillStyle = labelBgColor;
                ctx.fillRect(x, y - labelHeight, labelWidth, labelHeight);

                // Draw label text
                ctx.fillStyle = '#ffffff';
                ctx.fillText(labelText, x + 8, y - 7);

                console.log(`✅ Box ${index + 1} drawn:`, labelText);
            });

            console.log('✅ All bounding boxes drawn');
        }

        // NEW: Listen for multiple bounding boxes event
        $wire.on('boundingBoxesUpdated', (event) => {
            console.log('🎯 boundingBoxesUpdated event received!', event);

            const data = event[0] || event; // Handle both array and object format
            console.log('📦 Event data:', data);

            if (data && data.boundingBoxes && data.boundingBoxes.length > 0) {
                console.log('✅ Valid boundingBoxes data:', data.boundingBoxes);

                // Store bounding boxes data for re-drawing after Livewire updates
                currentBoundingBox = {
                    boxes: data.boundingBoxes,
                    faceCount: data.faceCount
                };

                // Use setTimeout to ensure DOM is updated by Livewire first
                setTimeout(() => {
                    console.log('⏭️ Delayed execution: DOM should be ready now');

                    // Retry mechanism to wait for DOM elements
                    const tryDrawBoundingBoxes = (retries = 0, maxRetries = 15) => {
                        const snapshotImage = document.getElementById('snapshotImage');
                        const canvas = document.getElementById('snapshotCanvas');

                        console.log(`🔍 Attempt ${retries + 1}: snapshotImage=`, snapshotImage,
                            'canvas=', canvas);

                        if (snapshotImage && canvas) {
                            console.log('🖼️ Elements found!');
                            console.log('  Image complete:', snapshotImage.complete);
                            console.log('  Image naturalWidth:', snapshotImage.naturalWidth);
                            console.log('  Canvas width:', canvas.width, 'height:', canvas.height);

                            // Wait for image to load if not already loaded
                            const attemptDraw = () => {
                                if (snapshotImage.complete && snapshotImage.naturalWidth > 0) {
                                    console.log('🎨 Drawing bounding boxes now...');
                                    drawBoundingBoxes(canvas, snapshotImage, data.boundingBoxes);
                                    console.log('✅ Bounding boxes drawn!');
                                } else {
                                    console.log('⏳ Image not loaded yet, waiting for onload...');
                                    snapshotImage.onload = () => {
                                        console.log(
                                            '🎨 Image loaded, drawing bounding boxes...');
                                        drawBoundingBoxes(canvas, snapshotImage, data
                                            .boundingBoxes);
                                        console.log('✅ Bounding boxes drawn!');
                                    };
                                }
                            };

                            requestAnimationFrame(attemptDraw);
                        } else if (retries < maxRetries) {
                            console.log(
                                `⏳ Elements not found, retrying in 150ms... (${retries + 1}/${maxRetries})`
                            );
                            setTimeout(() => tryDrawBoundingBoxes(retries + 1, maxRetries), 150);
                        } else {
                            console.error('⚠️ Could not find image/canvas elements after', maxRetries,
                                'retries');
                        }
                    };

                    tryDrawBoundingBoxes();
                }, 100); // Wait 100ms for DOM update
            } else {
                console.warn('⚠️ Invalid or empty boundingBoxes data in event');
            }
        });

        // LEGACY: Listen for single bounding box event (backward compatibility)
        $wire.on('boundingBoxUpdated', (event) => {
            // Check if we're on the right page (has snapshot elements)
            const snapshotImage = document.getElementById('snapshotImage');
            const canvas = document.getElementById('snapshotCanvas');

            if (!snapshotImage || !canvas) {
                return; // Not on absen page, ignore event
            }

            const data = event[0] || event; // Handle both array and object format

            if (data && data.boundingBox) {

                // Store bounding box data for re-drawing after Livewire updates
                currentBoundingBox = {
                    box: data.boundingBox,
                    name: data.name || null,
                    score: data.score || null
                };

                // Retry mechanism to wait for DOM elements (optimized)
                const tryDrawBoundingBox = (retries = 0, maxRetries = 10) => {
                    const snapshotImage = document.getElementById('snapshotImage');
                    const canvas = document.getElementById('snapshotCanvas');

                    if (snapshotImage && canvas) {
                        console.log('🖼️ Elements found, drawing bounding box...');

                        // Wait for image to load if not already loaded
                        const attemptDraw = () => {
                            if (snapshotImage.complete && snapshotImage.naturalWidth > 0) {
                                console.log('🎨 Drawing bounding box now...');
                                drawBoundingBox(canvas, snapshotImage, data.boundingBox, data.name, data
                                    .score);
                                console.log('✅ Bounding box drawn!');
                            } else {
                                snapshotImage.onload = () => {
                                    console.log('🎨 Image loaded, drawing bounding box...');
                                    drawBoundingBox(canvas, snapshotImage, data.boundingBox, data.name,
                                        data.score);
                                    console.log('✅ Bounding box drawn!');
                                };
                            }
                        };

                        requestAnimationFrame(attemptDraw);
                    } else if (retries < maxRetries) {
                        // Elements not found yet, retry after a delay (increased retries and delay for Livewire re-render)
                        console.log(`⏳ Elements not found, retrying... (${retries + 1}/${maxRetries})`);
                        setTimeout(() => tryDrawBoundingBox(retries + 1, maxRetries), 100);
                    } else {
                        console.warn('⚠️ Could not find image/canvas elements after retries');
                    }
                };

                tryDrawBoundingBox();
            } else {
                console.warn('⚠️ No boundingBox data in event');
            }
        });

        // Start/Stop camera handler
        let isCameraActive = false;

        const stopCamera = () => {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                video.srcObject = null;
                video.style.display = 'none';
                startCameraBtn.textContent = 'Mulai Kamera';
                captureBtn.style.display = 'none';
                isCameraActive = false;
            }
        };

        const startCamera = async () => {
            try {
                stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'user',
                        width: {
                            min: 640,
                            ideal: 1280,
                            max: 1920
                        },
                        height: {
                            min: 480,
                            ideal: 720,
                            max: 1080
                        }
                    },
                    audio: false
                });

                video.srcObject = stream;
                video.style.display = 'block';
                startCameraBtn.textContent = 'Stop Kamera';
                captureBtn.style.display = 'inline-block';
                isCameraActive = true;
            } catch (error) {
                alert('Gagal mengakses kamera: ' + error.message);
                console.error('Camera error:', error);
            }
        };

        // Start camera button click handler
        startCameraBtn.addEventListener('click', () => {
            if (isCameraActive) {
                stopCamera();
            } else {
                startCamera();
            }
        });

        // Auto-start camera on page load
        startCamera();

        // Hook into Livewire lifecycle to redraw bounding box after DOM updates
        Livewire.hook('morph.updated', ({
            el,
            component
        }) => {
            console.log('🔄 Livewire morph.updated hook triggered');

            // Check if we have stored bounding box data
            if (currentBoundingBox) {
                console.log('📦 Re-drawing bounding box after Livewire update');

                // Use setTimeout to ensure DOM is fully ready
                setTimeout(() => {
                    const snapshotImage = document.getElementById('snapshotImage');
                    const canvas = document.getElementById('snapshotCanvas');

                    if (snapshotImage && canvas) {
                        console.log('✅ Elements found, drawing stored bounding box');

                        // Wait for image to load
                        if (snapshotImage.complete && snapshotImage.naturalWidth > 0) {
                            drawBoundingBox(canvas, snapshotImage, currentBoundingBox.box,
                                currentBoundingBox.name, currentBoundingBox.score);
                            console.log('✅ Bounding box redrawn after update');
                        } else {
                            snapshotImage.onload = () => {
                                drawBoundingBox(canvas, snapshotImage, currentBoundingBox.box,
                                    currentBoundingBox.name, currentBoundingBox.score);
                                console.log('✅ Bounding box redrawn after image load');
                            };
                        }
                    } else {
                        console.warn('⚠️ Snapshot elements not found after update');
                    }
                }, 50);
            }
        });

        /**
         * Enhance image with brightness, contrast, and unsharp mask
         * @param {HTMLCanvasElement} canvas - Canvas with original image
         * @param {Object} config - Enhancement configuration
         * @returns {string} Enhanced image as data URL
         */
        function enhanceImage(canvas, config) {
            const ctx = canvas.getContext('2d');
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
            const data = imageData.data;

            // Apply brightness and contrast adjustment
            const brightnessDelta = config.brightnessDelta * 255; // Convert to 0-255 scale
            const contrastFactor = config.contrastFactor;

            for (let i = 0; i < data.length; i += 4) {
                // Adjust brightness and contrast for each RGB channel
                for (let j = 0; j < 3; j++) {
                    let value = data[i + j];

                    // Contrast: (value - 128) * factor + 128
                    value = (value - 128) * contrastFactor + 128;

                    // Brightness: value + delta
                    value = value + brightnessDelta;

                    // Clamp to [0, 255]
                    data[i + j] = Math.max(0, Math.min(255, value));
                }
                // Alpha channel stays the same
            }

            // Apply unsharp mask (simple approximation)
            const unsharpAmount = config.unsharpAmount;
            if (unsharpAmount > 0) {
                // Create temporary canvas for blur
                const blurCanvas = document.createElement('canvas');
                blurCanvas.width = canvas.width;
                blurCanvas.height = canvas.height;
                const blurCtx = blurCanvas.getContext('2d');

                // Draw blurred version
                blurCtx.filter = `blur(${config.unsharpRadius}px)`;
                blurCtx.drawImage(canvas, 0, 0);
                const blurredData = blurCtx.getImageData(0, 0, canvas.width, canvas.height).data;

                // Apply unsharp mask: original + amount * (original - blurred)
                for (let i = 0; i < data.length; i += 4) {
                    for (let j = 0; j < 3; j++) {
                        const original = data[i + j];
                        const blurred = blurredData[i + j];
                        const sharpened = original + unsharpAmount * (original - blurred);
                        data[i + j] = Math.max(0, Math.min(255, sharpened));
                    }
                }
            }

            // Put enhanced data back to canvas
            ctx.putImageData(imageData, 0, 0);

            // Return as data URL with specified quality
            return canvas.toDataURL('image/jpeg', config.jpegQuality);
        }

        // Capture photo with Non-blocking + Optimistic UI
        let isCapturing = false;
        captureBtn.addEventListener('click', () => {
            // Debounce: prevent multiple captures
            if (isCapturing) {
                console.log('⏸️ Already capturing, ignoring click');
                return;
            }
            isCapturing = true;

            // Clear previous bounding box data
            currentBoundingBox = null;

            // 1. IMMEDIATE FEEDBACK (Optimistic UI)
            console.log('📸 Capture button clicked - immediate feedback');

            // Disable button instantly
            captureBtn.disabled = true;

            // Capture image from video
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);

            // Get enhancement config
            const enableEnhance = @json($enableClientEnhance);
            const enhanceConfig = @json($clientEnhanceConfig);

            let imageDataUrl;

            // Apply enhancement if enabled
            if (enableEnhance) {
                console.log('✨ Applying client-side enhancement...');
                imageDataUrl = enhanceImage(canvas, enhanceConfig);
            } else {
                imageDataUrl = canvas.toDataURL('image/jpeg', 0.8);
            }

            // Stop camera after capture
            stopCamera();

            // Show loading indicator
            const loader = document.getElementById('verifyingLoader');
            if (loader) {
                loader.style.display = 'block';
            }

            // 3. NON-BLOCKING API CALL (in background)
            // Use requestAnimationFrame to ensure UI updates first
            requestAnimationFrame(() => {
                console.log('🌐 Starting API call in background...');

                $wire.call('verifyFace', imageDataUrl)
                    .then(() => {
                        console.log('✅ API call completed successfully');
                        // Hide loading state
                        if (loader) {
                            loader.style.display = 'none';
                        }
                        // Re-enable button
                        captureBtn.disabled = false;
                        isCapturing = false;
                    })
                    .catch((error) => {
                        console.error('❌ API call failed:', error);
                        // Hide loading state
                        if (loader) {
                            loader.style.display = 'none';
                        }
                        // Re-enable button
                        captureBtn.disabled = false;
                        isCapturing = false;
                    });
            });

            console.log('✅ UI updated, API processing in background');
        });

        // Check location with improved fallback strategy
        let isCheckingLocation = false;
        let locationAttempt = 0;

        checkLocationBtn.addEventListener('click', () => {
            if (!navigator.geolocation) {
                alert('Geolocation tidak didukung browser Anda.\n\nGunakan HP dengan GPS untuk absensi.');
                return;
            }

            // Debounce: prevent multiple clicks
            if (isCheckingLocation) {
                return;
            }
            isCheckingLocation = true;
            locationAttempt = 0;

            // Immediate feedback
            checkLocationBtn.disabled = true;
            checkLocationBtn.textContent = 'Mengambil lokasi...';

            // Try getting location with smart fallback
            tryGetLocationSmart();
        });

        /**
         * Smart location strategy:
         * 1. Try WiFi positioning first (faster, 5 sec)
         * 2. If fails, try GPS (more accurate, 15 sec)
         */
        function tryGetLocationSmart() {
            locationAttempt++;

            requestAnimationFrame(() => {
                let geoOptions;

                if (locationAttempt === 1) {
                    // First attempt: WiFi positioning (fast)
                    console.log('📍 Attempt 1: WiFi positioning (fast mode)...');
                    checkLocationBtn.textContent = 'Mencari lokasi...';
                    geoOptions = {
                        enableHighAccuracy: true, // WiFi/Network positioning
                        timeout: 5000, // 5 detik
                        maximumAge: 10000 // Accept cached up to 10 sec
                    };
                } else {
                    // Second attempt: GPS (accurate)
                    console.log('📍 Attempt 2: GPS positioning (accurate mode)...');
                    checkLocationBtn.textContent = 'Mencari GPS...';
                    geoOptions = {
                        enableHighAccuracy: true, // Force GPS
                        timeout: 15000, // 15 detik
                        maximumAge: 0 // Fresh only
                    };
                }

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const accuracy = position.coords.accuracy;

                        console.log('✅ Location obtained (attempt ' + locationAttempt + '): ±' + Math.round(
                            accuracy) + ' meters');

                        // Send to Livewire
                        $wire.call('checkLocation', lat, lng).then(() => {
                            const geofenceData = @js($this->getActiveGeofence());
                            const statusData = @js($geoStatus);

                            console.log('🗺️ Updating location map...');
                            updateLocationMap(lat, lng, geofenceData, statusData);

                            // Re-draw bounding box(es) after Livewire update
                            if (currentBoundingBox) {
                                requestAnimationFrame(() => {
                                    const snapshotImage = document.getElementById(
                                        'snapshotImage');
                                    const canvas = document.getElementById('snapshotCanvas');
                                    if (snapshotImage && canvas) {
                                        console.log('🔄 Re-drawing bounding box(es)');
                                        if (currentBoundingBox.boxes) {
                                            drawBoundingBoxes(canvas, snapshotImage,
                                                currentBoundingBox
                                                .boxes);
                                        } else {
                                            drawBoundingBox(canvas, snapshotImage,
                                                currentBoundingBox.box,
                                                currentBoundingBox.name, currentBoundingBox
                                                .score);
                                        }
                                    }
                                });
                            }
                        });

                        checkLocationBtn.disabled = false;
                        checkLocationBtn.textContent = 'Cek Lokasi Saya';
                        isCheckingLocation = false;
                        locationAttempt = 0;
                    },
                    (error) => {
                        console.error('❌ Location error (attempt ' + locationAttempt + '):', error);

                        // If first attempt timeout, try GPS
                        if (locationAttempt === 1 && error.code === error.TIMEOUT) {
                            console.log('⚠️ WiFi timeout, trying GPS...');
                            tryGetLocationSmart();
                            return;
                        }

                        // All attempts failed
                        let errorMsg = '';
                        switch (error.code) {
                            case error.PERMISSION_DENIED:
                                errorMsg =
                                    'Izin lokasi ditolak.\n\nCara aktifkan:\n1. Klik ikon gembok di address bar\n2. Izin → Lokasi → Izinkan';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMsg =
                                    'Lokasi tidak tersedia.\n\nPastikan:\n• GPS aktif di HP\n• Koneksi internet tersedia\n• Izin lokasi diizinkan';
                                break;
                            case error.TIMEOUT:
                                errorMsg =
                                    'Tidak dapat menemukan lokasi.\n\nSolusi:\n• Pindah ke area lebih terbuka\n• Aktifkan GPS di HP\n• Atau gunakan WiFi';
                                break;
                            default:
                                errorMsg = 'Gagal mendapatkan lokasi: ' + error.message;
                        }
                        alert(errorMsg);

                        checkLocationBtn.disabled = false;
                        checkLocationBtn.textContent = 'Cek Lokasi Saya';
                        isCheckingLocation = false;
                        locationAttempt = 0;
                    },
                    geoOptions
                );
            });
        }
    </script>
@endscript
