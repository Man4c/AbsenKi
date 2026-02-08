<?php

namespace App\Livewire\Staff;

use App\Models\Attendance;
use App\Models\Geofence;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\WorkSchedule;
use App\Services\FaceVerificationService;
use App\Services\FaceProcessor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Contracts\View\View;

#[Layout('components.layouts.app', ['title' => 'Absensi Staff'])]
class Absen extends Component
{
    // Location properties
    public ?float $lat = null;
    public ?float $lng = null;
    public string $geoStatus = 'unknown'; // unknown, inside, outside

    // Face verification properties
    public ?string $facePreview = null;
    public ?float $faceScore = null;
    public bool $faceOk = false;
    public int $snapshotTimestamp = 0; // Timestamp to force refresh

    // Quality metrics
    public ?float $qualityBlur = null;
    public ?float $qualityBrightness = null;

    // Bounding box and face detection
    /** @var array<int, array<string, mixed>> */
    public array $boundingBoxes = []; // Array of bounding boxes with labels
    public int $faceCount = 0;
    public ?string $faceName = null; // Matched user name for label (backward compatibility)

    // Client enhancement config
    public bool $enableClientEnhance = false;
    /** @var array<string, float> */
    public array $clientEnhanceConfig = [];

    // UI state
    public string $message = '';
    public string $messageType = ''; // success, error, info

    // Schedule info
    /** @var array<string, mixed>|null */
    public ?array $todaySchedule = null;
    public bool $isHoliday = false;
    public ?string $holidayName = null;

    // Leave info
    public bool $isOnLeave = false;
    public ?string $leaveType = null;
    public ?string $leaveStartDate = null;
    public ?string $leaveEndDate = null;

    /**
     * Get active geofence for map display
     * @return array<string, mixed>|null
     */
    public function getActiveGeofence(): ?array
    {
        $geofence = Geofence::where('is_active', true)->first();
        if (!$geofence) {
            return null;
        }

        /** @var mixed $polygon */
        $polygon = $geofence->polygon_geojson;

        if (!is_array($polygon) || array_is_list($polygon)) {
            return null;
        }

        /** @var array<string, mixed> $polygon */
        return $polygon;
    }

    /**
     * Get today's work schedule info
     */
    private function loadScheduleInfo(): void
    {
        // Check if user is on leave today
        $today = today();
        $activeLeave = Leave::where('user_id', Auth::id())
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->where('status', 'approved')
            ->first();

        if ($activeLeave) {
            $this->isOnLeave = true;
            $this->leaveType = $activeLeave->type;
            $this->leaveStartDate = $activeLeave->start_date->format('d M Y');
            $this->leaveEndDate = $activeLeave->end_date->format('d M Y');
            return;
        }

        // Check if today is holiday
        $holiday = Holiday::getHolidayForDate(today());
        if ($holiday && $holiday->is_active) {
            $this->isHoliday = true;
            $this->holidayName = $holiday->title;
            return;
        }

        // Get today's schedule
        $schedule = WorkSchedule::getTodaySchedule();
        if ($schedule && $schedule->is_active) {
            $this->todaySchedule = [
                'in_time' => $schedule->in_time,
                'out_time' => $schedule->out_time,
                'grace_late_minutes' => $schedule->grace_late_minutes,
                'grace_early_minutes' => $schedule->grace_early_minutes,
                'lock_in_start' => $schedule->lock_in_start,
                'lock_in_end' => $schedule->lock_in_end,
                'lock_out_start' => $schedule->lock_out_start,
                'lock_out_end' => $schedule->lock_out_end,
            ];
        }
    }

    public function mount(): void
    {
        // Load client enhancement config
        $this->enableClientEnhance = (bool) config('services.face.enable_client_enhance', true);
        $jpegQuality = config('services.face.client_jpeg_quality', 0.85);
        $unsharpAmount = config('services.face.client_unsharp_amount', 0.6);
        $unsharpRadius = config('services.face.client_unsharp_radius', 1.0);
        $brightnessDelta = config('services.face.client_brightness_delta', 0.0);
        $contrastFactor = config('services.face.client_contrast_factor', 1.08);

        $this->clientEnhanceConfig = [
            'jpegQuality' => is_numeric($jpegQuality) ? (float) $jpegQuality : 0.85,
            'unsharpAmount' => is_numeric($unsharpAmount) ? (float) $unsharpAmount : 0.6,
            'unsharpRadius' => is_numeric($unsharpRadius) ? (float) $unsharpRadius : 1.0,
            'brightnessDelta' => is_numeric($brightnessDelta) ? (float) $brightnessDelta : 0.0,
            'contrastFactor' => is_numeric($contrastFactor) ? (float) $contrastFactor : 1.08,
        ];

        // Load schedule info
        $this->loadScheduleInfo();
    }

    // Computed property for can check in
    public function getCanCheckInProperty(): bool
    {
        return $this->geoStatus === 'inside' && $this->faceOk === true;
    }

    // Check if user has checked in today
    public function getHasCheckedInTodayProperty(): bool
    {
        return Attendance::where('user_id', Auth::id())
            ->where('type', 'in')
            ->whereDate('created_at', today())
            ->exists();
    }

    // Check if user has checked out today
    public function getHasCheckedOutTodayProperty(): bool
    {
        return Attendance::where('user_id', Auth::id())
            ->where('type', 'out')
            ->whereDate('created_at', today())
            ->exists();
    }

    // Can user check in? (location + face verified AND not checked in yet today AND not holiday AND schedule active AND within lock window)
    public function getCanDoCheckInProperty(): bool
    {
        // Basic checks
        if (!$this->getCanCheckInProperty() || $this->getHasCheckedInTodayProperty()) {
            return false;
        }

        // Check if today is holiday
        if ($this->isHoliday) {
            return false;
        }

        // Check if schedule is active for today
        if (!$this->todaySchedule) {
            return false;
        }

        // Check lock window for check-in
        if ($this->todaySchedule['lock_in_start'] && $this->todaySchedule['lock_in_end']) {
            $now = Carbon::now()->format('H:i:s');
            if ($now < $this->todaySchedule['lock_in_start'] || $now > $this->todaySchedule['lock_in_end']) {
                return false;
            }
        }

        return true;
    }

    // Can user check out? (location + face verified AND has checked in today AND not checked out yet today AND not holiday AND schedule active AND within lock window)
    public function getCanDoCheckOutProperty(): bool
    {
        // Basic checks
        if (!$this->getCanCheckInProperty() || !$this->getHasCheckedInTodayProperty() || $this->getHasCheckedOutTodayProperty()) {
            return false;
        }

        // Check if today is holiday
        if ($this->isHoliday) {
            return false;
        }

        // Check if schedule is active for today
        if (!$this->todaySchedule) {
            return false;
        }

        // Check lock window for check-out
        if ($this->todaySchedule['lock_out_start'] && $this->todaySchedule['lock_out_end']) {
            $now = Carbon::now()->format('H:i:s');
            if ($now < $this->todaySchedule['lock_out_start'] || $now > $this->todaySchedule['lock_out_end']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check location against active geofence
     */
    public function checkLocation(float $lat, float $lng): void
    {
        $this->lat = $lat;
        $this->lng = $lng;

        try {
            // Get active geofence
            $geofence = Geofence::where('is_active', true)->first();

            if (!$geofence) {
                $this->geoStatus = 'outside';
                $this->message = 'Tidak ada geofence aktif. Hubungi admin.';
                $this->messageType = 'error';
                return;
            }

            // Get GeoJSON polygon (already decoded by model cast)
            /** @var mixed $polygon */
            $polygon = $geofence->polygon_geojson;

            if (!is_array($polygon) || !isset($polygon['coordinates']) || !is_array($polygon['coordinates'])) {
                $this->geoStatus = 'outside';
                $this->message = 'Format geofence tidak valid.';
                $this->messageType = 'error';
                return;
            }

            $coordinates = $polygon['coordinates'];
            if (!isset($coordinates[0]) || !is_array($coordinates[0])) {
                $this->geoStatus = 'outside';
                $this->message = 'Format geofence tidak valid.';
                $this->messageType = 'error';
                return;
            }

            $ringRaw = $coordinates[0];
            $ring = [];
            foreach ($ringRaw as $point) {
                if (!is_array($point) || !isset($point[0], $point[1]) || !is_numeric($point[0]) || !is_numeric($point[1])) {
                    $this->geoStatus = 'outside';
                    $this->message = 'Format geofence tidak valid.';
                    $this->messageType = 'error';
                    return;
                }
                $ring[] = [(float) $point[0], (float) $point[1]];
            }

            if ($ring === []) {
                $this->geoStatus = 'outside';
                $this->message = 'Format geofence tidak valid.';
                $this->messageType = 'error';
                return;
            }

            // Check if point is inside polygon (simple ray-casting algorithm)
            $isInside = $this->pointInPolygon([$lng, $lat], $ring);

            $this->geoStatus = $isInside ? 'inside' : 'outside';

            if ($isInside) {
                $this->message = 'Lokasi Anda di dalam area kantor';
                $this->messageType = 'success';
            } else {
                $this->message = 'Lokasi Anda di luar area kantor';
                $this->messageType = 'error';
            }
        } catch (\Exception $e) {
            Log::error('Error checking location', [
                'user_id' => Auth::id(),
                'lat' => $lat,
                'lng' => $lng,
                'error' => $e->getMessage(),
            ]);

            $this->geoStatus = 'outside';
            $this->message = 'Gagal memeriksa lokasi. Silakan coba lagi.';
            $this->messageType = 'error';
        }
    }

    /**
     * Verify face from snapshot
     */
    public function verifyFace(string $imageDataUrl): void
    {
        try {
            // Clear previous state first
            $this->facePreview = null;
            $this->faceOk = false;
            $this->faceScore = null;
            $this->boundingBoxes = [];
            $this->faceCount = 0;
            $this->faceName = null;
            $this->snapshotTimestamp = time(); // Update timestamp to force refresh

            // Extract base64 image data
            if (preg_match('/^data:image\/\w+;base64,(.+)$/', $imageDataUrl, $matches)) {
                $imageData = base64_decode($matches[1]);
            } else {
                throw new \Exception('Format gambar tidak valid');
            }

            // Save temporary file for quality check
            $tempPath = 'private/temp_face_' . Auth::id() . '_' . time() . '.jpg';
            Storage::put($tempPath, $imageData);

            // Get proper filesystem path (fixes Windows path separator issue)
            $localPath = Storage::path($tempPath);

            // QUALITY CHECK FIRST before sending to AWS
            $faceProcessor = new FaceProcessor();
            $qualityResult = $faceProcessor->qualityCheck($localPath);

            if (!$qualityResult['success']) {
                // Delete temp file
                Storage::delete($tempPath);

                // Quality check failed - show user-friendly message
                $this->faceOk = false;
                $this->faceScore = null;
                $this->boundingBoxes = [];
                $this->faceCount = 0;
                $this->message = is_string($qualityResult['message'] ?? null) ? $qualityResult['message'] : 'Kualitas gambar tidak memenuhi syarat';
                $this->messageType = 'error';

                // Store quality metrics even on failure (for debugging)
                $this->qualityBlur = is_numeric($qualityResult['laplace'] ?? null) ? (float) $qualityResult['laplace'] : null;
                $this->qualityBrightness = is_numeric($qualityResult['brightness'] ?? null) ? (float) $qualityResult['brightness'] : null;

                return;
            }

            // Store quality metrics for later
            $this->qualityBlur = is_numeric($qualityResult['laplace'] ?? null) ? (float) $qualityResult['laplace'] : null;
            $this->qualityBrightness = is_numeric($qualityResult['brightness'] ?? null) ? (float) $qualityResult['brightness'] : null;

            // Quality check passed - proceed to AWS Rekognition
            $verificationService = new FaceVerificationService();
            $user = Auth::user();
            if (!$user) {
                throw new \Exception('Pengguna tidak terautentikasi');
            }
            $result = $verificationService->verifyFace($user, $imageData);

            // Clean up temp file
            Storage::delete($tempPath);

            // Store preview for UI ONLY AFTER successful verification
            $this->facePreview = $imageDataUrl;

            $this->faceOk = (bool) ($result['ok'] ?? false);
            $this->faceScore = is_numeric($result['score'] ?? null) ? (float) $result['score'] : null;
            $this->faceName = isset($result['name']) && is_string($result['name']) ? $result['name'] : null;
            $boundingBoxesRaw = $result['boundingBoxes'] ?? [];
            /** @var array<int, array<string, mixed>> $boundingBoxes */
            $boundingBoxes = is_array($boundingBoxesRaw) ? $boundingBoxesRaw : [];
            $this->boundingBoxes = $boundingBoxes;
            $this->faceCount = is_int($result['faceCount'] ?? null) ? $result['faceCount'] : 0;
            $this->message = is_string($result['message'] ?? null) ? $result['message'] : 'Verifikasi wajah selesai';
            $this->messageType = $this->faceOk ? 'success' : 'error';

            // Add warning message if multiple faces detected
            if ($this->faceCount > 1) {
                $this->message .= ' (Terdeteksi ' . $this->faceCount . ' wajah, pastikan hanya wajah Anda yang terlihat)';
            }

            // Dispatch event to JavaScript to draw all bounding boxes with labels
            if (!empty($this->boundingBoxes)) {
                $this->dispatch('boundingBoxesUpdated', [
                    'boundingBoxes' => $this->boundingBoxes,
                    'faceCount' => $this->faceCount,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error verifying face', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            $this->faceOk = false;
            $this->faceScore = null;
            $this->boundingBoxes = [];
            $this->faceCount = 0;
            $this->message = 'Gagal memverifikasi wajah. Silakan coba lagi.';
            $this->messageType = 'error';
        }
    }

    /**
     * Commit attendance record
     */
    public function commitAttendance(string $type = 'in'): void
    {
        // Validate prerequisites
        if ($this->geoStatus !== 'inside') {
            $this->message = 'Lokasi Anda di luar area kantor. Tidak bisa absen.';
            $this->messageType = 'error';
            return;
        }

        if (!$this->faceOk) {
            $this->message = 'Wajah belum diverifikasi atau tidak cocok. Silakan verifikasi wajah terlebih dahulu.';
            $this->messageType = 'error';
            return;
        }

        // Check if today is holiday
        if ($this->isHoliday) {
            $this->message = 'Hari ini adalah hari libur (' . $this->holidayName . '). Absensi tidak dapat dilakukan.';
            $this->messageType = 'error';
            return;
        }

        // Check if schedule is active for today
        if (!$this->todaySchedule) {
            $this->message = 'Tidak ada jadwal kerja untuk hari ini. Absensi tidak dapat dilakukan.';
            $this->messageType = 'error';
            return;
        }

        // Check lock window
        $now = Carbon::now();
        $nowTime = $now->format('H:i:s');

        if ($type === 'in') {
            if ($this->todaySchedule['lock_in_start'] && $this->todaySchedule['lock_in_end']) {
                $lockInStart = $this->todaySchedule['lock_in_start'];
                $lockInEnd = $this->todaySchedule['lock_in_end'];
                if ($nowTime < $lockInStart || $nowTime > $lockInEnd) {
                    $this->message = 'Absen masuk hanya dapat dilakukan antara ' .
                        substr(is_string($lockInStart) ? $lockInStart : '', 0, 5) . ' - ' .
                        substr(is_string($lockInEnd) ? $lockInEnd : '', 0, 5);
                    $this->messageType = 'error';
                    return;
                }
            }
        } else {
            if ($this->todaySchedule['lock_out_start'] && $this->todaySchedule['lock_out_end']) {
                $lockOutStart = $this->todaySchedule['lock_out_start'];
                $lockOutEnd = $this->todaySchedule['lock_out_end'];
                if ($nowTime < $lockOutStart || $nowTime > $lockOutEnd) {
                    $this->message = 'Absen keluar hanya dapat dilakukan antara ' .
                        substr(is_string($lockOutStart) ? $lockOutStart : '', 0, 5) . ' - ' .
                        substr(is_string($lockOutEnd) ? $lockOutEnd : '', 0, 5);
                    $this->messageType = 'error';
                    return;
                }
            }
        }

        // Check if already checked in/out today
        $alreadyExists = Attendance::where('user_id', Auth::id())
            ->where('type', $type)
            ->whereDate('created_at', today())
            ->exists();

        if ($alreadyExists) {
            $typeLabel = $type === 'in' ? 'masuk' : 'keluar';
            $this->message = "Anda sudah absen {$typeLabel} hari ini.";
            $this->messageType = 'error';
            return;
        }

        try {
            // Get device info
            $userAgent = request()->header('User-Agent', 'Unknown');

            // Get work schedule for status calculation
            $schedule = WorkSchedule::getTodaySchedule();
            $statusFlag = null;

            if ($schedule) {
                if ($type === 'in') {
                    $statusFlag = $schedule->calculateCheckInStatus($now);
                } else {
                    $statusFlag = $schedule->calculateCheckOutStatus($now);
                }
            }

            // Create attendance record
            Attendance::create([
                'user_id' => Auth::id(),
                'type' => $type,
                'lat' => $this->lat,
                'lng' => $this->lng,
                'geo_ok' => true,
                'face_score' => $this->faceScore,
                'status' => 'success',
                'status_flag' => $statusFlag,
                'device_info' => $userAgent,
                'quality_blur_var' => $this->qualityBlur,
                'quality_brightness' => $this->qualityBrightness,
            ]);

            // Success message
            $typeLabel = $type === 'in' ? 'Masuk' : 'Keluar';
            $statusText = $statusFlag ? ' (' . ($statusFlag === 'on_time' ? 'Tepat Waktu' : ($statusFlag === 'late' ? 'Terlambat' : ($statusFlag === 'normal_leave' ? 'Pulang Normal' : 'Pulang Cepat'))) . ')' : '';
            $this->message = "✅ Absen {$typeLabel} berhasil dicatat pada " . now()->format('d M Y H:i') . $statusText;
            $this->messageType = 'success';

            // Reset state
            $this->reset(['facePreview', 'faceScore', 'faceOk', 'faceName', 'lat', 'lng', 'geoStatus', 'qualityBlur', 'qualityBrightness', 'boundingBoxes', 'faceCount']);
        } catch (\Exception $e) {
            Log::error('Error committing attendance', [
                'user_id' => Auth::id(),
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            $this->message = 'Gagal menyimpan absensi. Silakan coba lagi.';
            $this->messageType = 'error';
        }
    }

    /**
     * Check if a point is inside a polygon using ray-casting algorithm
     * @param array<int, float> $point
     * @param array<int, array<int, float>> $polygon
     */
    private function pointInPolygon(array $point, array $polygon): bool
    {
        $x = $point[0];
        $y = $point[1];
        $inside = false;

        for ($i = 0, $j = count($polygon) - 1; $i < count($polygon); $j = $i++) {
            $xi = $polygon[$i][0];
            $yi = $polygon[$i][1];
            $xj = $polygon[$j][0];
            $yj = $polygon[$j][1];

            $intersect = (($yi > $y) != ($yj > $y))
                && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi);

            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    public function render(): View
    {
        return view('livewire.staff.absen');
    }
}
