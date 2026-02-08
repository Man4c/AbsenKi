<?php

namespace App\Services;

use App\Models\FaceProfile;
use App\Models\User;
use Aws\Rekognition\RekognitionClient;
use Illuminate\Support\Facades\Storage;

class FaceEnrollService
{
    protected RekognitionClient $rekognition;
    protected string $collectionId;

    public function __construct()
    {
        $this->rekognition = new RekognitionClient([
            'region' => config('services.aws.region'),
            'version' => 'latest',
            'credentials' => [
                'key' => config('services.aws.key'),
                'secret' => config('services.aws.secret'),
            ],
        ]);

        $collectionId = config('services.rekognition.collection', 'absenki-staff');
        $this->collectionId = is_string($collectionId) ? $collectionId : 'absenki-staff';
    }

    /**
     * Enroll face to AWS Rekognition
     * @return array<string, mixed>
     */
    public function enrollFace(User $user, string $imagePath): array
    {
        try {
            // Get image bytes from storage
            $imageBytes = Storage::get($imagePath);

            if (!$imageBytes) {
                throw new \Exception('Tidak dapat membaca file gambar dari storage');
            }

            // Sanitize name for AWS Rekognition (only allow: a-zA-Z0-9_.-:)
            // Replace spaces with underscores, remove other invalid characters
            $sanitizedName = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $user->name);

            // Call AWS Rekognition IndexFaces
            $result = $this->rekognition->indexFaces([
                'CollectionId' => $this->collectionId,
                'Image' => [
                    'Bytes' => $imageBytes,
                ],
                'ExternalImageId' => $user->id . ':' . $sanitizedName, // Format: "{user_id}:{sanitized_name}" (colon allowed by AWS)
                'DetectionAttributes' => ['ALL'],
                'MaxFaces' => 1, // Only index the primary face
                'QualityFilter' => 'AUTO', // Filter low quality faces
            ]);

            // Check if face was detected
            $faceRecords = $result['FaceRecords'] ?? [];

            if (empty($faceRecords)) {
                throw new \Exception('Tidak ada wajah yang terdeteksi dalam gambar. Silakan unggah foto wajah yang jelas.');
            }

            // Get the first (and should be only) face record
            if (!is_array($faceRecords) || !isset($faceRecords[0])) {
                throw new \Exception('Data wajah tidak valid');
            }
            $faceRecord = $faceRecords[0];
            /** @var array{Face: array{FaceId: string, Confidence: float}} $faceRecord */
            $faceId = $faceRecord['Face']['FaceId'];
            $confidence = $faceRecord['Face']['Confidence'];

            // Save to database
            $faceProfile = FaceProfile::create([
                'user_id' => $user->id,
                'face_id' => $faceId,
                'provider' => 'aws',
                'collection_id' => $this->collectionId,
                'image_path' => $imagePath,
                'confidence' => $confidence,
            ]);

            return [
                'success' => true,
                'face_id' => $faceId,
                'confidence' => $confidence,
                'face_profile_id' => $faceProfile->id,
                'message' => 'Wajah berhasil didaftarkan'
            ];
        } catch (\Aws\Rekognition\Exception\RekognitionException $e) {
            // AWS Rekognition specific errors
            throw new \Exception('Kesalahan AWS Rekognition: ' . $e->getAwsErrorMessage());
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Delete face from AWS Rekognition
     */
    public function deleteFace(FaceProfile $faceProfile): bool
    {
        try {
            $this->rekognition->deleteFaces([
                'CollectionId' => $faceProfile->collection_id,
                'FaceIds' => [$faceProfile->face_id],
            ]);

            // Delete from database
            $faceProfile->delete();

            // Optionally delete image file
            if ($faceProfile->image_path && Storage::exists($faceProfile->image_path)) {
                Storage::delete($faceProfile->image_path);
            }

            return true;
        } catch (\Exception $e) {
            throw new \Exception('Gagal menghapus wajah: ' . $e->getMessage());
        }
    }

    /**
     * Check if collection exists, create if not
     */
    public function ensureCollectionExists(): bool
    {
        try {
            $this->rekognition->describeCollection([
                'CollectionId' => $this->collectionId,
            ]);
            return true;
        } catch (\Aws\Rekognition\Exception\RekognitionException $e) {
            // Collection doesn't exist, create it
            if ($e->getAwsErrorCode() === 'ResourceNotFoundException') {
                $this->rekognition->createCollection([
                    'CollectionId' => $this->collectionId,
                ]);
                return true;
            }
            throw $e;
        }
    }
}
