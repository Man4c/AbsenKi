<?php

namespace App\Livewire\Admin\Faces;

use App\Models\FaceProfile;
use App\Models\User;
use App\Services\FaceEnrollService;
use App\Services\FaceProcessor;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;

class Manage extends Component
{
    use WithFileUploads;

    public User $user;
    /** @var array<int, mixed> */
    public array $photos = [];
    public bool $uploading = false;

    public function mount(int $userId): void
    {
        $this->user = User::with('faceProfiles')->findOrFail($userId);

        // Ensure user is staff
        if ($this->user->role !== 'staff') {
            abort(403, 'Can only manage face profiles for staff users');
        }
    }

    public function uploadFace(): void
    {
        $this->validate([
            'photos.*' => 'required|image|max:2048|mimes:jpeg,jpg,png',
            'photos'    => 'required|array|min:1',
        ], [
            'photos.*.image' => 'File harus gambar.',
            'photos.*.max'   => 'Maksimal 2MB per foto.',
        ]);

        $this->uploading = true;

        try {
            $faceProcessor  = new FaceProcessor();
            $enrollService  = new FaceEnrollService();

            // pastikan collection AWS ada
            $enrollService->ensureCollectionExists();

            foreach ($this->photos as $photo) {
                // Validate photo is UploadedFile
                if (!($photo instanceof \Illuminate\Http\UploadedFile)) {
                    continue;
                }

                // 1. Initial quality check on original photo
                $tempPath = $photo->path();

                Log::info('Face upload - starting process', [
                    'temp_path' => $tempPath,
                    'file_exists' => file_exists($tempPath),
                    'is_file' => is_file($tempPath),
                ]);

                // Quick quality check on original
                $qualityResult = $faceProcessor->qualityCheck($tempPath);
                if (!$qualityResult['success']) {
                    $message = $qualityResult['message'] ?? 'Kualitas gambar tidak memenuhi syarat';
                    throw new \Exception(is_string($message) ? $message : 'Kualitas gambar tidak memenuhi syarat');
                }

                // 2. Process image (will crop face using OpenCV)
                // Note: processImage now handles face detection and cropping internally
                $processed = $faceProcessor->processImage($photo, $this->user->id);

                if (!$processed['success']) {
                    $message = $processed['message'] ?? 'Gagal memproses wajah';
                    throw new \Exception(is_string($message) ? $message : 'Gagal memproses wajah');
                }

                Log::info('Face cropped successfully', [
                    'crop_source' => $processed['crop_source'] ?? 'unknown',
                    'width' => $processed['width'] ?? 0,
                    'height' => $processed['height'] ?? 0,
                ]);

                // 3. Enroll to Rekognition (IndexFaces)
                $croppedPath = $processed['cropped_path'] ?? null;
                if (!is_string($croppedPath)) {
                    throw new \Exception('Invalid cropped path');
                }
                $result = $enrollService->enrollFace($this->user, $croppedPath);

                Log::info('Face enrolled to AWS Rekognition', [
                    'face_id' => $result['face_id'],
                    'confidence' => $result['confidence'],
                ]);
            }

            // reset form setelah sukses
            $this->reset('photos');
            $this->uploading = false;

            // reload daftar wajah agar sisi kanan update
            $this->user->load('faceProfiles');

            session()->flash('saved', "Semua foto berhasil diregistrasi!");
        } catch (\Exception $e) {
            $this->uploading = false;
            $this->addError('photos', $e->getMessage());
        }
    }

    public function deleteFace(int $faceProfileId): void
    {
        try {
            $faceProfile = FaceProfile::where('user_id', $this->user->id)
                ->findOrFail($faceProfileId);

            $enrollService = new FaceEnrollService();
            $enrollService->deleteFace($faceProfile);

            $this->user->load('faceProfiles');

            session()->flash('saved', 'Profil wajah berhasil dihapus!');
        } catch (\Exception $e) {
            $this->addError('delete', $e->getMessage());
        }
    }

    public function removePhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos); // reindex biar gak lubang
    }

    public function render(): View
    {
        return view('livewire.admin.faces.manage');
    }
}
