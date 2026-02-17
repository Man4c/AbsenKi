<?php

namespace App\Services;

use App\Models\User;
use Aws\Rekognition\RekognitionClient;
use Illuminate\Support\Facades\Log;

class FaceVerificationService
{
    protected RekognitionClient $rekognition;
    protected string $collectionId;
    protected float $threshold;

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
        $threshold = config('services.rekognition.threshold', 80);
        $this->threshold = (float) (is_numeric($threshold) ? $threshold : 80);
    }

    /**
     * Verify face from snapshot image against registered faces for the user
     *
     * @param User $user The user attempting to verify
     * @param string $imageBytes Binary image data
     * @return array<string, mixed> ['ok' => bool, 'score' => float|null, 'message' => string, 'boundingBoxes' => array, 'faceCount' => int]
     */
    public function verifyFace(User $user, string $imageBytes): array
    {
        try {
            // Check if user has registered faces
            if (!$user->hasFaceRegistered()) {
                return [
                    'ok' => false,
                    'score' => null,
                    'message' => 'Anda belum mendaftarkan wajah. Silakan hubungi admin untuk registrasi wajah.',
                    'boundingBoxes' => [],
                    'faceCount' => 0,
                ];
            }

            // Get all face IDs registered for this user
            $userFaceIds = $user->faceProfiles()->pluck('face_id')->toArray();

            if (empty($userFaceIds)) {
                return [
                    'ok' => false,
                    'score' => null,
                    'message' => 'Data wajah terdaftar tidak ditemukan.',
                    'boundingBoxes' => [],
                    'faceCount' => 0,
                ];
            }

            // First, detect faces to get bounding box coordinates
            $detectResult = $this->rekognition->detectFaces([
                'Image' => [
                    'Bytes' => $imageBytes,
                ],
                'Attributes' => ['DEFAULT'], // Get basic face attributes
            ]);

            $detectedFaces = $detectResult['FaceDetails'] ?? [];
            if (!is_array($detectedFaces)) {
                $detectedFaces = [];
            }
            $faceCount = count($detectedFaces);

            // If no face detected at all, return early
            if ($faceCount === 0) {
                Log::warning('Face verification: No face detected', [
                    'user_id' => $user->id,
                ]);

                return [
                    'ok' => false,
                    'score' => null,
                    'message' => 'Tidak ada wajah terdeteksi. Pastikan wajah Anda terlihat jelas di kamera.',
                    'boundingBoxes' => [],
                    'faceCount' => 0,
                ];
            }

            // Warn if multiple faces detected
            if ($faceCount > 1) {
                Log::warning('Multiple faces detected during verification', [
                    'user_id' => $user->id,
                    'face_count' => $faceCount,
                ]);
            }

            // Call AWS Rekognition SearchFacesByImage
            $result = $this->rekognition->searchFacesByImage([
                'CollectionId' => $this->collectionId,
                'Image' => [
                    'Bytes' => $imageBytes,
                ],
                'MaxFaces' => 1,
                'FaceMatchThreshold' => $this->threshold,
            ]);

            // Check if any faces were matched
            $faceMatches = $result['FaceMatches'] ?? [];

            if (empty($faceMatches)) {
                Log::warning('Face verification failed: No matches found', [
                    'user_id' => $user->id,
                    'threshold' => $this->threshold,
                    'face_count' => $faceCount,
                ]);

                // Return all bounding boxes as "unknown"
                $boundingBoxes = array_map(function ($face) {
                    $bbox = is_array($face) && isset($face['BoundingBox']) && is_array($face['BoundingBox'])
                        ? $face['BoundingBox']
                        : [];
                    return [
                        'box' => $bbox,
                        'label' => 'Tidak Dikenal',
                        'type' => 'unknown',
                        'score' => null,
                    ];
                }, $detectedFaces);

                return [
                    'ok' => false,
                    'score' => null,
                    'message' => 'Wajah tidak cocok atau tidak terdeteksi dengan jelas. Silakan coba lagi dengan pencahayaan yang lebih baik.',
                    'boundingBoxes' => $boundingBoxes,
                    'faceCount' => $faceCount,
                ];
            }

            // Get the best match
            if (!is_array($faceMatches) || !isset($faceMatches[0])) {
                throw new \Exception('Invalid face matches data');
            }
            $bestMatch = $faceMatches[0];
            /** @var array{Face: array{FaceId: string, ExternalImageId?: string}, Similarity: float} $bestMatch */
            $matchedFaceId = $bestMatch['Face']['FaceId'];
            $similarity = $bestMatch['Similarity'];
            $externalImageId = $bestMatch['Face']['ExternalImageId'] ?? null;

            // Parse ExternalImageId to get matched user ID and name
            // Format: "{user_id}:{sanitized_name}" (colon separator)
            $matchedUserId = null;
            $matchedUserName = null;

            if ($externalImageId !== null && strpos($externalImageId, ':') !== false) {
                $parts = explode(':', $externalImageId, 2);
                $matchedUserId = (int) $parts[0];
                $matchedUserName = $parts[1] ?? null;
            }

            // Check if matched face belongs to current user
            $belongsToUser = in_array($matchedFaceId, $userFaceIds);

            // Build bounding boxes array with labels
            $boundingBoxes = [];

            if ($faceCount === 1) {
                // Only one face detected - this must be the matched face
                $firstFace = $detectedFaces[0] ?? null;
                if (is_array($firstFace) && isset($firstFace['BoundingBox'])) {
                    $boundingBoxes[] = [
                        'box' => is_array($firstFace['BoundingBox']) ? $firstFace['BoundingBox'] : [],
                        'label' => $belongsToUser ? ($matchedUserName ?? 'User') : 'Bukan Wajah Anda',
                        'type' => $belongsToUser ? 'matched' : 'wrong_user',
                        'score' => $similarity,
                    ];
                }
            } else {
                // Multiple faces detected - mark first as matched (highest confidence from detectFaces)
                // and others as "Wajah Lain"
                foreach ($detectedFaces as $index => $face) {
                    if (!is_array($face) || !isset($face['BoundingBox'])) {
                        continue;
                    }
                    if ($index === 0) {
                        // First face is typically the largest/most prominent - assume it's the matched one
                        $boundingBoxes[] = [
                            'box' => is_array($face['BoundingBox']) ? $face['BoundingBox'] : [],
                            'label' => $belongsToUser ? ($matchedUserName ?? 'User') : 'Bukan Wajah Anda',
                            'type' => $belongsToUser ? 'matched' : 'wrong_user',
                            'score' => $similarity,
                        ];
                    } else {
                        // Other faces are unmatched
                        $boundingBoxes[] = [
                            'box' => is_array($face['BoundingBox']) ? $face['BoundingBox'] : [],
                            'label' => 'Wajah Lain',
                            'type' => 'other',
                            'score' => null,
                        ];
                    }
                }
            }

            // Verify that the matched face belongs to this user
            if (!in_array($matchedFaceId, $userFaceIds)) {
                Log::warning('Face verification failed: Matched face does not belong to user', [
                    'user_id' => $user->id,
                    'matched_user_id' => $matchedUserId,
                    'matched_face_id' => $matchedFaceId,
                    'user_face_ids' => $userFaceIds,
                    'similarity' => $similarity,
                ]);

                return [
                    'ok' => false,
                    'score' => $similarity,
                    'name' => null, // Don't expose other user's name
                    'message' => 'Wajah terdeteksi bukan milik Anda. Silakan gunakan wajah Anda sendiri.',
                    'boundingBoxes' => $boundingBoxes,
                    'faceCount' => $faceCount,
                ];
            }

            // Success - face matched and belongs to the user
            Log::info('Face verification successful', [
                'user_id' => $user->id,
                'matched_user_id' => $matchedUserId,
                'face_id' => $matchedFaceId,
                'similarity' => $similarity,
                'face_count' => $faceCount,
            ]);

            // Truncate (potong) ke 2 desimal tanpa pembulatan
            $truncatedScore = floor($similarity * 100) / 100;

            return [
                'ok' => true,
                'score' => $truncatedScore,
                'name' => $matchedUserName,
                'face_id' => $matchedFaceId,
                'message' => sprintf('Wajah cocok dengan confidence %s%%', number_format($truncatedScore, 2, '.', '')),
                'boundingBoxes' => $boundingBoxes,
                'faceCount' => $faceCount,
            ];
        } catch (\Aws\Rekognition\Exception\RekognitionException $e) {
            Log::error('AWS Rekognition error during face verification', [
                'user_id' => $user->id,
                'error' => $e->getAwsErrorMessage(),
            ]);

            return [
                'ok' => false,
                'score' => null,
                'message' => 'Terjadi kesalahan saat verifikasi wajah: ' . $e->getAwsErrorMessage(),
                'boundingBoxes' => [],
                'faceCount' => 0,
            ];
        } catch (\Exception $e) {
            Log::error('Error during face verification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'score' => null,
                'message' => 'Terjadi kesalahan sistem. Silakan coba lagi.',
                'boundingBoxes' => [],
                'faceCount' => 0,
            ];
        }
    }

    /**
     * Crop face from snapshot for audit/storage (optional)
     *
     * @param string $imageBytes Binary image data
     * @param array<string, float> $boundingBox Rekognition bounding box ['Left', 'Top', 'Width', 'Height']
     * @param int $userId User ID for storage path
     * @return array<string, mixed> ['success' => bool, 'path' => string|null, 'message' => string]
     */
    public function cropSnapshotForAudit(string $imageBytes, array $boundingBox, int $userId): array
    {
        try {
            // Save temporary file
            $tempPath = storage_path('app/temp/snapshot_' . $userId . '_' . time() . '.jpg');
            file_put_contents($tempPath, $imageBytes);

            // Crop using FaceProcessor
            $faceProcessor = new FaceProcessor();
            $croppedPath = "attendance/snapshots/{$userId}/" . time() . '_cropped.jpg';
            $croppedAbsPath = storage_path('app/' . $croppedPath);

            $cropResult = $faceProcessor->cropFace($tempPath, $croppedAbsPath, $boundingBox);

            // Delete temp file
            @unlink($tempPath);

            if (!$cropResult['success']) {
                return [
                    'success' => false,
                    'path' => null,
                    'message' => $cropResult['message']
                ];
            }

            return [
                'success' => true,
                'path' => $croppedPath,
                'message' => 'Snapshot berhasil dipotong'
            ];
        } catch (\Exception $e) {
            Log::error('Error cropping snapshot', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'path' => null,
                'message' => 'Failed to crop snapshot'
            ];
        }
    }
}
