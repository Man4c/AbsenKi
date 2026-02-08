<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
/** @var \Illuminate\Foundation\Application $app */
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Aws\Rekognition\RekognitionClient;
use Illuminate\Support\Facades\DB;

echo "=== Cleanup All Orphan Faces ===\n\n";

// Face IDs yang mau dihapus berdasarkan log
$orphanFaceIds = [
    '03d3794a-9d58-4f31-801d-a42e6718b5c0',
];

echo "Found " . count($orphanFaceIds) . " potential orphan face(s)\n\n";

$toDelete = [];

// Verify each face
foreach ($orphanFaceIds as $faceId) {
    echo "Checking face: {$faceId}\n";

    $exists = DB::table('face_profiles')
        ->where('face_id', $faceId)
        ->exists();

    if ($exists) {
        echo "  ❌ EXISTS in database (NOT orphan) - skipping\n\n";
    } else {
        echo "  ✅ NOT in database (ORPHAN) - will delete\n\n";
        $toDelete[] = $faceId;
    }
}

if (empty($toDelete)) {
    echo "No orphan faces to delete.\n";
    exit(0);
}

echo "Will delete " . count($toDelete) . " orphan face(s) from AWS Rekognition...\n";
echo "Face IDs: " . implode(', ', $toDelete) . "\n\n";

try {
    $client = new RekognitionClient([
        'region' => config('services.aws.region'),
        'version' => 'latest',
        'credentials' => [
            'key' => config('services.aws.key'),
            'secret' => config('services.aws.secret'),
        ],
    ]);

    $result = $client->deleteFaces([
        'CollectionId' => config('services.rekognition.collection'),
        'FaceIds' => $toDelete,
    ]);

    echo "✅ Successfully deleted from AWS Rekognition!\n";
    echo "Deleted faces: " . json_encode($result['DeletedFaces']) . "\n";

    if (!empty($result['UnmatchedFaceIds'])) {
        echo "⚠️  Unmatched faces (already deleted?): " . json_encode($result['UnmatchedFaceIds']) . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✅ Cleanup completed!\n";

// Untuk jalankan ketik di terminal:
// php cleanup_all_orphan_faces.php
