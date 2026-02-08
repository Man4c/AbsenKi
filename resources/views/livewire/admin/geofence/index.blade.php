<div class="max-w-7xl mx-auto">

    {{-- HEADER --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Geofence Management</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Kelola area kantor untuk validasi lokasi absensi
        </p>
    </div>

    {{-- FLASH MESSAGE --}}
    @if (session()->has('message'))
        <div
            class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded dark:bg-green-900 dark:text-green-200">
            {{ session('message') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- FORM SECTION --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
                {{ $editingId ? 'Edit Geofence' : 'Create New Geofence' }}
            </h2>

            {{-- NAMA --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                    Name
                </label>
                <input type="text" wire:model.lazy="name"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-800 dark:text-gray-100"
                    placeholder="Contoh: Kantor Desa Teromu">
                @error('name')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            {{-- PETA INTERAKTIF --}}
            <div class="mb-4" wire:ignore>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                    Pilih Area di Peta
                </label>

                {{-- MAP CONTAINER --}}
                <div id="map"
                    class="w-full rounded-md border border-gray-300 dark:border-gray-600 overflow-hidden relative z-0"
                    style="height: 300px; background: #f9fafb;">
                </div>

                <div
                    class="mt-3 p-3 bg-blue-50 border-2 border-blue-400 rounded-md dark:bg-blue-900 dark:border-blue-600">
                    <p class="text-sm font-semibold mb-2 text-gray-900 dark:text-white">
                        🌍 Panduan Penggunaan:
                    </p>

                    <ul class="text-xs text-gray-700 dark:text-gray-200 space-y-1 list-disc list-outside ml-4">
                        <li>
                            Klik di peta untuk menambahkan titik batas area (minimal 3 titik, akan muncul marker biru
                            kecil).
                        </li>
                        <li>
                            Polygon merah akan terbentuk otomatis mengikuti titik-titik yang kamu buat.
                        </li>
                        <li>
                            Setelah selesai, klik tombol <strong>"Gunakan Polygon di Peta"</strong> untuk mengisi
                            GeoJSON otomatis.
                        </li>
                        <li>
                            Gunakan tombol <strong>"Reset Titik"</strong> untuk menghapus semua titik.
                        </li>
                    </ul>
                </div>

                <div class="mt-3 flex flex-wrap gap-2">
                    <button type="button" onclick="usePolygonFromMap()"
                        class="px-4 py-2 bg-blue-600 text-white
                        rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Gunakan Polygon di Peta
                    </button>

                    <button type="button" onclick="resetPolygonOnMap()"
                        class="px-3 py-2 bg-gray-200 text-gray-800 rounded text-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400">
                        Reset Titik
                    </button>
                </div>
            </div>

            {{-- TEXTAREA GEOJSON --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                    Polygon GeoJSON
                </label>
                <textarea id="polygon_geojson_input" wire:model.blur="polygon_geojson" rows="10"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono text-xs dark:bg-gray-800 dark:text-gray-100"></textarea>

                @error('polygon_geojson')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror

                {{-- HELPER BOX --}}
                <div
                    class="mt-2 p-3 bg-blue-50 border-2 border-blue-400 rounded-md dark:bg-blue-900 dark:border-blue-600">
                    <p class="text-sm font-semibold mb-2 text-gray-900 dark:text-white">
                        🌍 Format Koordinat:
                        <span class="font-mono text-blue-700 dark:text-blue-300">[longitude, latitude]</span>
                    </p>

                    <ul class="text-xs text-gray-700 dark:text-gray-200 space-y-1 list-disc list-outside ml-4">
                        <li>Titik awal dan akhir harus sama untuk menutup polygon</li>
                        <li>Contoh:
                            <code class="bg-gray-200 dark:bg-gray-700 px-1 rounded text-[11px] break-all">
                                {"type":"Polygon","coordinates":[[[<strong>119.4821,
                                    -5.1236</strong>],[119.4831, -5.1236],[119.4831, -5.1246],[<strong>119.4821,
                                    -5.1236</strong>]]]}
                            </code>
                            <strong>Awal</strong> dan <strong>akhir</strong> sama.
                        </li>

                        <li><strong>💡 Tips:</strong> Gunakan peta di atas untuk generate otomatis</li>

                    </ul>
                </div>
            </div>

            {{-- ACTIVE CHECKBOX --}}
            <div class="mb-4">
                <label class="flex items-start gap-2">
                    <input type="checkbox" wire:model="is_active"
                        class="mt-1 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                        Set as Active Geofence
                        <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">
                            Hanya satu geofence yang bisa aktif pada satu waktu. Jika dicentang, geofence lain akan
                            dinonaktifkan.
                        </span>
                    </span>
                </label>
            </div>

            {{-- ACTION BUTTONS --}}
            <div class="flex flex-wrap gap-2">
                <button wire:click="save"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    {{ $editingId ? 'Update Geofence' : 'Create Geofence' }}
                </button>

                @if ($editingId)
                    <button wire:click="toggleActive"
                        class="px-4 py-2 {{ $is_active ? 'bg-yellow-600 hover:bg-yellow-700' : 'bg-green-600 hover:bg-green-700' }} text-white rounded-md focus:outline-none focus:ring-2 text-sm">
                        {{ $is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                @endif
            </div>
        </div>

        {{-- LIST SECTION --}}
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Existing Geofences</h2>

            @if ($geofences->isEmpty())
                <p class="text-gray-500 dark:text-gray-400 text-sm">Belum ada geofence. Buat yang pertama di sebelah
                    kiri.</p>
            @else
                <div class="space-y-4">
                    @foreach ($geofences as $gf)
                        <div
                            class="border rounded-lg p-4 {{ $gf->is_active ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-200 dark:border-gray-700' }}">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ $gf->name }}</h3>
                                        @if ($gf->is_active)
                                            <span
                                                class="px-2 py-1 bg-green-600 text-white text-xs rounded-full">Active</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 space-y-0.5">
                                        <p>Created: {{ $gf->created_at->format('d M Y H:i') }}</p>
                                        <p>Last Updated: {{ $gf->updated_at->format('d M Y H:i') }}</p>
                                    </div>

                                    <details class="mt-2">
                                        <summary class="text-xs text-blue-600 cursor-pointer">View GeoJSON</summary>
                                        <textarea readonly title="Polygon GeoJSON Data" aria-label="Polygon GeoJSON Data"
                                            class="w-full text-xs bg-gray-100 dark:bg-gray-800 p-2 rounded mt-1 overflow-auto max-h-32 border dark:border-gray-700 font-mono">{{ json_encode($gf->polygon_geojson, JSON_PRETTY_PRINT) }}</textarea>
                                    </details>
                                </div>

                                <div class="flex flex-col gap-2 ml-4">
                                    @if (!$gf->is_active)
                                        <button wire:click="activate({{ $gf->id }})"
                                            class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs rounded">
                                            Activate
                                        </button>
                                    @endif

                                    <button wire:click="delete({{ $gf->id }})" wire:confirm="Hapus geofence ini?"
                                        class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs rounded">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <script>
        // ====== VARIABEL GLOBAL UNTUK PETA ======
        // Gunakan window scope untuk avoid re-declaration error saat Livewire re-render
        if (typeof window.geoMap === 'undefined') {
            window.geoMap = null;
            window.geoPolygonLayer = null;
            window.geoPointMarkers = [];
            window.geoPointsLngLat = [];
            window.geoMapInitialized = false;
        }

        // ====== CLEANUP MAP SAAT NAVIGASI (SPA MODE) ======
        function cleanupMap() {
            if (window.geoMap) {
                try {
                    window.geoMap.remove(); // destroy Leaflet map instance
                } catch (e) {
                    console.log('Map cleanup error (safe to ignore):', e);
                }
            }

            // Reset semua state
            window.geoMap = null;
            window.geoPolygonLayer = null;
            window.geoPointMarkers = [];
            window.geoPointsLngLat = [];
            window.geoMapInitialized = false;
        }

        // ====== INISIALISASI PETA SAAT HALAMAN SIAP (LAZY LOAD) ======
        // Gunakan Intersection Observer untuk load map hanya saat visible
        function setupMapObserver() {
            const mapContainer = document.getElementById('map');
            if (!mapContainer) return;

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && !window.geoMapInitialized) {
                        initMap();
                        observer.disconnect();
                    }
                });
            }, {
                rootMargin: '50px' // preload 50px sebelum visible
            });

            observer.observe(mapContainer);
        }

        // Event listener untuk DOMContentLoaded dan Livewire navigation
        document.addEventListener('DOMContentLoaded', setupMapObserver);
        document.addEventListener('livewire:navigated', () => {
            cleanupMap(); // cleanup dulu sebelum setup ulang
            setupMapObserver();
        });

        // Cleanup saat navigasi keluar (sebelum page di-unmount)
        document.addEventListener('livewire:navigating', cleanupMap);

        function initMap() {
            // Jangan init ulang kalau sudah ada
            if (window.geoMapInitialized || window.geoMap) return;

            // Pastikan container map ada dan Leaflet sudah loaded
            const mapContainer = document.getElementById('map');
            if (!mapContainer || typeof window.L === 'undefined') return;

            window.geoMapInitialized = true;

            // Atur posisi awal peta
            window.geoMap = window.L.map('map', {
                preferCanvas: true,
                zoomControl: true
            }).setView([-5.1236, 119.4821], 18);

            // Layer dasar dengan konfigurasi yang lebih optimal
            window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                minZoom: 10,
                attribution: '&copy; OpenStreetMap',
                keepBuffer: 2,
                updateWhenIdle: true,
                updateWhenZooming: false
            }).addTo(window.geoMap);

            // Setup event click
            attachMapClick();

            // Kalau sedang edit geofence yang sudah ada
            requestAnimationFrame(() => {
                window.geoMap.invalidateSize();
                tryRenderExistingPolygon();
            });
        }

        // ====== SAAT ADMIN KLIK PETA: TAMBAH TITIK ======
        function attachMapClick() {
            if (!window.geoMap) return;

            window.geoMap.off('click'); // supaya tidak double register

            window.geoMap.on('click', function(e) {
                const {
                    lat,
                    lng
                } = e.latlng;

                // simpan [lng, lat] (INI YANG KITA BUTUH BUAT GEOJSON)
                window.geoPointsLngLat.push([lng, lat]);

                // tampilkan marker kecil di titik yg diklik
                const marker = window.L.circleMarker([lat, lng], {
                    radius: 4,
                    color: '#1d4ed8',
                    fillColor: '#3b82f6',
                    fillOpacity: 0.9
                }).addTo(window.geoMap);
                window.geoPointMarkers.push(marker);

                redrawPolygon();
            });
        }

        // ====== REDRAW POLYGON DI PETA BERDASARKAN pointsLngLat ======
        function redrawPolygon() {
            // hapus layer polygon lama
            if (window.geoPolygonLayer) {
                window.geoMap.removeLayer(window.geoPolygonLayer);
                window.geoPolygonLayer = null;
            }

            if (window.geoPointsLngLat.length < 2) return;

            // Leaflet polygon butuh array [lat, lng]
            const latLngPairs = window.geoPointsLngLat.map(([lng, lat]) => [lat, lng]);

            window.geoPolygonLayer = window.L.polygon(latLngPairs, {
                color: 'red',
                fillColor: '#f03',
                fillOpacity: 0.2
            }).addTo(window.geoMap);
        }

        // ====== TOMBOL "RESET TITIK" ======
        function resetPolygonOnMap() {
            // hapus polygon
            if (window.geoPolygonLayer) {
                window.geoMap.removeLayer(window.geoPolygonLayer);
                window.geoPolygonLayer = null;
            }

            // hapus semua marker titik
            window.geoPointMarkers.forEach(m => window.geoMap.removeLayer(m));
            window.geoPointMarkers = [];

            // kosongkan array koordinat
            window.geoPointsLngLat = [];
        }

        // ====== TOMBOL "GUNAKAN POLYGON DI PETA" ======
        function usePolygonFromMap() {
            if (window.geoPointsLngLat.length < 3) {
                alert('Minimal 3 titik ya 😚');
                return;
            }

            // pastikan polygon tertutup
            const ring = [...window.geoPointsLngLat];
            const [firstLng, firstLat] = ring[0];
            const [lastLng, lastLat] = ring[ring.length - 1];

            if (firstLng !== lastLng || firstLat !== lastLat) {
                ring.push(ring[0]);
            }

            const geojson = {
                type: "Polygon",
                coordinates: [ring] // <- array of array of [lng, lat]
            };

            // isi textarea
            const txt = JSON.stringify(geojson, null, 2);
            document.getElementById('polygon_geojson_input').value = txt;

            // trigger Livewire binding manual (supaya wire:model ke-update)
            // ambil komponen wire dari textarea
            const evt = new Event('input', {
                bubbles: true
            });
            document.getElementById('polygon_geojson_input').dispatchEvent(evt);
        }

        // ====== RENDER POLYGON LAMA (KETIKA EDIT) ======
        function tryRenderExistingPolygon() {
            const textarea = document.getElementById('polygon_geojson_input');
            if (!textarea?.value) return;

            try {
                const parsed = JSON.parse(textarea.value);
                if (parsed?.type !== 'Polygon' || !parsed.coordinates?.[0]) return;

                const ring = parsed.coordinates[0];
                if (!Array.isArray(ring) || ring.length < 3) return;

                // Simpan ke memori global kita
                window.geoPointsLngLat = ring.slice(0, -1);
                window.geoPointMarkers = [];

                window.geoPointsLngLat.forEach(([lng, lat]) => {
                    const marker = window.L.circleMarker([lat, lng], {
                        radius: 4,
                        color: '#1d4ed8',
                        fillColor: '#3b82f6',
                        fillOpacity: 0.9
                    }).addTo(window.geoMap);
                    window.geoPointMarkers.push(marker);
                });

                // redraw polygon
                redrawPolygon();

                // optionally: fit bounds ke polygon biar rapi
                if (window.geoPolygonLayer) {
                    window.geoMap.fitBounds(window.geoPolygonLayer.getBounds(), {
                        maxZoom: 21
                    });
                }
            } catch (e) {
                console.error('Error rendering polygon:', e);
            }
        }
    </script>
</div>
