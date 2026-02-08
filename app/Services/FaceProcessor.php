<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class FaceProcessor
{
    private function resolvePythonBinary(): string
    {
        $configured = config('services.face.python_bin');

        if (is_string($configured) && trim($configured) !== '') {
            return trim($configured);
        }

        // Prefer project virtualenv on Windows dev; on Linux shared hosting this typically won't exist.
        $windowsVenv = base_path('.venv/Scripts/python.exe');
        if (file_exists($windowsVenv)) {
            return $windowsVenv;
        }

        // Most Linux hosts expose python3, not python.
        return 'python3';
    }

    /**
     * Process uploaded face image
     * For now this is a dummy implementation
     * TODO: Integrate with OpenCV for real face detection
     * @return array<string, mixed>
     */
    public function processImage(UploadedFile $file, int $userId): array
    {
        // Basic validation
        if (!$file->isValid()) {
            throw new \Exception('File yang diunggah tidak valid');
        }

        // Check if image
        $mimeType = $file->getMimeType();
        if (!$mimeType || !str_starts_with($mimeType, 'image/')) {
            throw new \Exception('File harus berupa gambar');
        }

        // Get image dimensions using getimagesize
        $imageInfo = getimagesize($file->path());
        if (!$imageInfo) {
            throw new \Exception('Tidak dapat membaca file gambar');
        }

        $width = $imageInfo[0];
        $height = $imageInfo[1];

        // Check minimum dimensions
        if ($width < 200 || $height < 200) {
            throw new \Exception('Image resolution too small. Minimum 200x200 pixels required.');
        }

        // Save raw image for audit
        $rawPath = "faces/raw/{$userId}/" . time() . '_' . $file->getClientOriginalName();
        $fileContents = file_get_contents($file->path());

        if ($fileContents === false) {
            throw new \Exception('Failed to read uploaded file');
        }

        Storage::put($rawPath, $fileContents);

        // Get absolute path for OpenCV processing
        $rawAbsolutePath = Storage::path($rawPath);

        // Crop face using OpenCV
        $croppedPath = "faces/cropped/{$userId}/" . time() . '_cropped.jpg';
        $croppedAbsolutePath = Storage::path($croppedPath);

        // Call OpenCV face crop (with optional bbox from Rekognition)
        $cropResult = $this->cropFace($rawAbsolutePath, $croppedAbsolutePath, null);

        if (!$cropResult['success']) {
            // Delete raw file if crop failed
            Storage::delete($rawPath);

            return [
                'success' => false,
                'message' => $cropResult['message'] ?? 'Gagal mendeteksi wajah di foto. Pastikan wajah terlihat jelas.'
            ];
        }

        // Quality check on cropped face
        $qualityResult = $this->qualityCheck($croppedAbsolutePath);

        if (!$qualityResult['success']) {
            // Delete both files if quality check failed
            Storage::delete($rawPath);
            Storage::delete($croppedPath);

            return [
                'success' => false,
                'message' => $qualityResult['message']
            ];
        }

        return [
            'success' => true,
            'raw_path' => $rawPath,
            'cropped_path' => $croppedPath,
            'width' => $cropResult['width'],
            'height' => $cropResult['height'],
            'crop_source' => $cropResult['source'], // 'bbox' or 'haar'
            'message' => 'Wajah berhasil dideteksi dan dipotong'
        ];
    }

    /**
     * Crop face from image using OpenCV Python script
     *
     * @param string $inputPath Absolute path to input image
     * @param string $outputPath Absolute path to save cropped image
     * @param array<string, float>|null $bbox Optional Rekognition bounding box ['Left' => float, 'Top' => float, 'Width' => float, 'Height' => float]
     * @return array<string, mixed> ['success' => bool, 'message' => string, 'source' => string, 'width' => int, 'height' => int]
     */
    public function cropFace(string $inputPath, string $outputPath, ?array $bbox = null): array
    {
        $scriptPath = base_path('tools/opencv_face_crop.py');

        if (!file_exists($scriptPath)) {
            return [
                'success' => false,
                'message' => 'Face crop script not found'
            ];
        }

        if (!file_exists($inputPath)) {
            return [
                'success' => false,
                'message' => 'Input image not found'
            ];
        }

        try {
            $pythonPath = $this->resolvePythonBinary();

            // Build command arguments
            $args = [
                $pythonPath,
                $scriptPath,
                '--image',
                $inputPath,
                '--out',
                $outputPath,
            ];

            // Add bbox if provided (from Rekognition DetectFaces)
            if ($bbox && isset($bbox['Left'], $bbox['Top'], $bbox['Width'], $bbox['Height'])) {
                $bboxString = sprintf(
                    '%s,%s,%s,%s',
                    $bbox['Left'],
                    $bbox['Top'],
                    $bbox['Width'],
                    $bbox['Height']
                );
                $args[] = '--bbox';
                $args[] = $bboxString;
            }

            // Create process
            $process = new Process($args);
            $process->setTimeout(10);
            $process->run();

            if (!$process->isSuccessful()) {
                Log::error('Face crop process failed', [
                    'error' => $process->getErrorOutput(),
                    'output' => $process->getOutput()
                ]);

                return [
                    'success' => false,
                    'message' => 'Gagal memproses wajah. Pastikan Python dan OpenCV terinstall.'
                ];
            }

            // Parse JSON output
            $output = $process->getOutput();
            $result = json_decode($output, true);

            if (!is_array($result)) {
                Log::error('Face crop invalid JSON', ['output' => $output]);
                return [
                    'success' => false,
                    'message' => 'Face crop returned invalid data'
                ];
            }

            if (!($result['ok'] ?? false)) {
                $message = $result['message'] ?? 'Face crop failed';
                return [
                    'success' => false,
                    'message' => is_string($message) ? $message : 'Face crop failed'
                ];
            }

            return [
                'success' => true,
                'message' => 'Face cropped successfully',
                'source' => is_string($result['source'] ?? null) ? $result['source'] : 'unknown',
                'width' => is_int($result['width'] ?? null) ? $result['width'] : 0,
                'height' => is_int($result['height'] ?? null) ? $result['height'] : 0,
                'bbox_px' => $result['bbox_px'] ?? null,
            ];
        } catch (ProcessFailedException $e) {
            Log::error('Face crop process exception', [
                'exception' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Face crop process failed'
            ];
        } catch (\Exception $e) {
            Log::error('Face crop unexpected error', [
                'exception' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses wajah'
            ];
        }
    }

    /**
     * Quality check using OpenCV Python script
     * Checks: sharpness (Laplacian), brightness, dimensions
     *
     * @param string $localPath Absolute path to image file
     * @return array<string, mixed> ['success' => bool, 'message' => string, 'laplace' => float, 'brightness' => float, ...]
     */
    public function qualityCheck(string $localPath): array
    {
        // Get thresholds from config
        $minLaplaceRaw = config('services.face.min_laplace', 80);
        $minBrightRaw = config('services.face.min_brightness', 60);
        $minWRaw = config('services.face.min_width', 200);
        $minHRaw = config('services.face.min_height', 200);

        $minLaplace = is_numeric($minLaplaceRaw) ? (float) $minLaplaceRaw : 80.0;
        $minBright = is_numeric($minBrightRaw) ? (float) $minBrightRaw : 60.0;
        $minW = is_numeric($minWRaw) ? (int) $minWRaw : 200;
        $minH = is_numeric($minHRaw) ? (int) $minHRaw : 200;

        // Adaptive threshold settings
        $enableAdaptive = (bool) config('services.face.enable_adaptive_laplace', true);
        $laplaceBaseRaw = config('services.face.laplace_base', 100);
        $laplaceMinRaw = config('services.face.laplace_min', 60);
        $laplaceMaxRaw = config('services.face.laplace_max', 140);
        $targetBrightnessRaw = config('services.face.target_brightness', 90);

        $laplaceBase = is_numeric($laplaceBaseRaw) ? (float) $laplaceBaseRaw : 100.0;
        $laplaceMin = is_numeric($laplaceMinRaw) ? (float) $laplaceMinRaw : 60.0;
        $laplaceMax = is_numeric($laplaceMaxRaw) ? (float) $laplaceMaxRaw : 140.0;
        $targetBrightness = is_numeric($targetBrightnessRaw) ? (float) $targetBrightnessRaw : 90.0;

        // BYPASS: If thresholds are extremely low, skip Python check (for production without OpenCV)
        if ($minLaplace <= 5 && $minBright <= 20) {
            return [
                'success' => true,
                'message' => 'Quality check bypassed (thresholds disabled)',
                'laplace' => 100,
                'brightness' => 100,
                'width' => 640,
                'height' => 480
            ];
        }

        // Path to Python script
        $scriptPath = base_path('tools/opencv_quality.py');

        // Check if script exists
        if (!file_exists($scriptPath)) {
            return [
                'success' => false,
                'message' => 'Quality check script not found'
            ];
        }

        // Check if image exists
        if (!file_exists($localPath)) {
            Log::error('Quality check: Image file not found', [
                'path' => $localPath,
                'realpath' => realpath($localPath),
            ]);

            return [
                'success' => false,
                'message' => 'Image file not found: ' . basename($localPath)
            ];
        }

        try {
            $pythonPath = $this->resolvePythonBinary();

            // Create process to run Python script
            $process = new Process([
                $pythonPath,
                $scriptPath,
                '--image',
                $localPath,
            ]);

            // Set timeout (5 seconds should be enough)
            $process->setTimeout(5);

            // Run the process
            $process->run();

            // Check if process was successful
            if (!$process->isSuccessful()) {
                Log::error('Quality check process failed', [
                    'error' => $process->getErrorOutput(),
                    'output' => $process->getOutput()
                ]);

                return [
                    'success' => false,
                    'message' => 'Gagal menjalankan quality check. Pastikan Python dan OpenCV terinstall.'
                ];
            }

            // Parse JSON output
            $output = $process->getOutput();
            $result = json_decode($output, true);

            if (!is_array($result)) {
                Log::error('Quality check invalid JSON output', ['output' => $output]);
                return [
                    'success' => false,
                    'message' => 'Quality check returned invalid data'
                ];
            }

            // Check if quality check itself failed
            if (!($result['ok'] ?? false)) {
                $message = $result['message'] ?? 'Quality check failed';
                return [
                    'success' => false,
                    'message' => is_string($message) ? $message : 'Quality check failed'
                ];
            }

            // Extract metrics
            $lap = is_numeric($result['laplace'] ?? null) ? (float) $result['laplace'] : 0.0;
            $bri = is_numeric($result['brightness'] ?? null) ? (float) $result['brightness'] : 0.0;
            $w = is_numeric($result['width'] ?? null) ? (int) $result['width'] : 0;
            $h = is_numeric($result['height'] ?? null) ? (int) $result['height'] : 0;

            // Validate dimensions
            if ($w < $minW || $h < $minH) {
                return [
                    'success' => false,
                    'message' => "Resolusi gambar terlalu kecil. Minimal {$minW}x{$minH} pixels.",
                    'laplace' => $lap,
                    'brightness' => $bri,
                    'width' => $w,
                    'height' => $h,
                ];
            }

            // CEK BRIGHTNESS DULU sebelum blur
            // Foto gelap juga bisa menghasilkan laplacian rendah karena kurang kontras
            if ($bri < $minBright) {
                return [
                    'success' => false,
                    'message' => 'Foto terlalu gelap. Cari tempat dengan cahaya yang cukup, lalu coba lagi ya.',
                    'laplace' => $lap,
                    'brightness' => $bri,
                    'width' => $w,
                    'height' => $h,
                ];
            }

            // Calculate adaptive threshold based on brightness
            $adjustedThreshold = $minLaplace; // Default fallback

            if ($enableAdaptive && $targetBrightness > 0) {
                // Formula: adjusted_threshold = base_threshold * (brightness / target_brightness)
                $adjustedThreshold = $laplaceBase * ($bri / $targetBrightness);

                // Clamp to [min, max] range
                $adjustedThreshold = max($laplaceMin, min($laplaceMax, $adjustedThreshold));

                Log::info('Adaptive threshold calculated', [
                    'brightness' => $bri,
                    'target_brightness' => $targetBrightness,
                    'base_threshold' => $laplaceBase,
                    'adjusted_threshold' => $adjustedThreshold,
                    'laplace_value' => $lap,
                    'adaptive_enabled' => true,
                ]);
            } else {
                // Adaptive disabled, use base threshold
                Log::info('Adaptive threshold disabled, using base', [
                    'base_threshold' => $minLaplace,
                    'brightness' => $bri,
                    'laplace_value' => $lap,
                    'adaptive_enabled' => false,
                    'reason' => !$enableAdaptive ? 'disabled_in_config' : 'invalid_target_brightness',
                ]);
            }            // Validate sharpness (blur detection) with adaptive threshold
            if ($lap < $adjustedThreshold) {
                Log::warning('Quality check failed: Image too blurry', [
                    'laplace_value' => $lap,
                    'base_threshold' => $minLaplace,
                    'adjusted_threshold' => $adjustedThreshold,
                    'brightness' => $bri,
                    'adaptive_enabled' => $enableAdaptive,
                ]);

                return [
                    'success' => false,
                    'message' => sprintf(
                        "Foto buram atau goyang (sharpness: %.2f, minimal: %.2f). Pastikan kamera fokus dan tangan stabil, lalu coba lagi ya.",
                        $lap,
                        $adjustedThreshold
                    ),
                    'laplace' => $lap,
                    'brightness' => $bri,
                    'width' => $w,
                    'height' => $h,
                    'adjusted_threshold' => $adjustedThreshold,
                ];
            }

            // All checks passed!
            Log::info('Quality check passed', [
                'laplace' => $lap,
                'base_threshold' => $minLaplace,
                'adjusted_threshold' => $adjustedThreshold,
                'brightness' => $bri,
                'width' => $w,
                'height' => $h,
                'adaptive_enabled' => $enableAdaptive,
            ]);

            return [
                'success' => true,
                'message' => 'Kualitas foto bagus',
                'laplace' => $lap,
                'brightness' => $bri,
                'width' => $w,
                'height' => $h,
            ];
        } catch (ProcessFailedException $e) {
            Log::error('Quality check process exception', [
                'exception' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Quality check gagal dijalankan'
            ];
        } catch (\Exception $e) {
            Log::error('Quality check unexpected error', [
                'exception' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat quality check'
            ];
        }
    }
}
