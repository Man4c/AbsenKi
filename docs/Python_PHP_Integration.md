# Integrasi Python dan PHP di AbsenKi

## Overview

Aplikasi AbsenKi menggunakan **AWS Rekognition** untuk face recognition (pengenalan wajah), dan **Python OpenCV** hanya untuk pre-processing gambar (crop & quality check) sebelum dikirim ke AWS.

⚠️ **PENTING**: Aplikasi ini **TIDAK menggunakan model ML lokal**. Face recognition sepenuhnya menggunakan layanan cloud AWS Rekognition.

## Arsitektur Face Recognition

```
1. Upload Foto (User)
   ↓
2. PHP Laravel (Controller/Livewire)
   ↓
3. Python OpenCV (Pre-processing)
   - opencv_face_crop.py: Crop wajah dari foto
   - opencv_quality.py: Cek kualitas gambar (blur, brightness)
   ↓
4. AWS Rekognition (Face Recognition)
   - IndexFaces: Daftarkan wajah baru
   - SearchFacesByImage: Cocokkan wajah dengan database
   - DetectFaces: Deteksi wajah dalam gambar
   ↓
5. Response ke User
```

## Python-PHP Integration Flow

```
PHP (Laravel) → Symfony Process → Python Script → OpenCV Processing → JSON Output → PHP
```

## Setup Environment

### 1. Install Python

```bash
# Cek versi Python (minimal 3.8)
python --version
```

### 2. Buat Virtual Environment

```bash
# Di root project
python -m venv .venv
```

### 3. Aktivasi Virtual Environment

```bash
# Windows
.\.venv\Scripts\activate

# Linux/Mac
source .venv/bin/activate
```

### 4. Install Dependencies Python

```bash
pip install opencv-python numpy
```

**Note**: Tidak perlu install library face recognition seperti `face_recognition`, `dlib`, atau model ML lainnya karena face recognition dilakukan oleh AWS Rekognition.

### 5. Verifikasi Instalasi

```bash
python -c "import cv2; print(cv2.__version__)"
```

### 6. Setup AWS Credentials

Edit file `.env`:

```env
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=ap-southeast-2
REKOG_COLLECTION=absenki-staff
FACE_THRESHOLD=80
```

## File Structure

```
AbsenKi/
├── .venv/                              # Python virtual environment (hanya untuk OpenCV)
│   └── Scripts/
│       └── python.exe                  # Python executable
├── tools/                              # Python scripts (Pre-processing ONLY)
│   ├── opencv_face_crop.py             # Crop wajah dari foto
│   └── opencv_quality.py               # Cek kualitas gambar (blur, brightness)
└── app/
    └── Services/
        ├── FaceProcessor.php           # PHP service untuk OpenCV (pre-processing)
        ├── FaceEnrollService.php       # Daftarkan wajah ke AWS Rekognition
        └── FaceVerificationService.php # Verifikasi wajah via AWS Rekognition
```

**Catatan Penting:**

-   ❌ **Tidak ada model ML** di folder `tools/` atau `storage/`
-   ❌ **Tidak ada file** seperti `*.h5`, `*.pkl`, `*.pth`, `*.onnx`
-   ✅ Face recognition sepenuhnya di-handle oleh **AWS Rekognition Cloud**
-   ✅ Python hanya untuk **pre-processing gambar** (crop & quality check)

## Cara Kerja

### 1. Face Cropping (opencv_face_crop.py)

**PHP Call:**

```php
use Symfony\Component\Process\Process;

$process = new Process([
    base_path('.venv/Scripts/python.exe'),
    base_path('tools/opencv_face_crop.py'),
    '--image', $inputPath,
    '--out', $outputPath,
    '--bbox', '0.3,0.2,0.4,0.5'  // Optional: Rekognition bbox
]);
$process->setTimeout(10);
$process->run();

$result = json_decode($process->getOutput(), true);
```

**Python Output (JSON):**

```json
{
    "ok": true,
    "message": "Face cropped successfully",
    "source": "rekognition", // or "opencv"
    "width": 500,
    "height": 600
}
```

### 2. Quality Check (opencv_quality.py)

**PHP Call:**

```php
$process = new Process([
    base_path('.venv/Scripts/python.exe'),
    base_path('tools/opencv_quality.py'),
    '--image', $imagePath
]);
$process->setTimeout(5);
$process->run();

$result = json_decode($process->getOutput(), true);
```

**Python Output (JSON):**

```json
{
    "ok": true,
    "laplace": 125.5,
    "brightness": 85.2,
    "width": 640,
    "height": 480
}
```

## Implementasi di PHP

### 1. FaceProcessor Service (Python OpenCV - Pre-processing)

Lihat `app/Services/FaceProcessor.php`:

```php
public function cropFace(string $inputPath, string $outputPath, ?array $bbox = null): array
{
    $scriptPath = base_path('tools/opencv_face_crop.py');

    // Gunakan virtual environment dulu, fallback ke system python
    $pythonPath = base_path('.venv/Scripts/python.exe');
    if (!file_exists($pythonPath)) {
        $pythonPath = 'python';
    }

    $args = [$pythonPath, $scriptPath, '--image', $inputPath, '--out', $outputPath];

    if ($bbox) {
        $args[] = '--bbox';
        $args[] = sprintf('%s,%s,%s,%s', $bbox['Left'], $bbox['Top'], $bbox['Width'], $bbox['Height']);
    }

    $process = new Process($args);
    $process->setTimeout(10);
    $process->run();

    if (!$process->isSuccessful()) {
        return ['success' => false, 'message' => 'Python script failed'];
    }

    return json_decode($process->getOutput(), true);
}
```

### 2. FaceEnrollService (AWS Rekognition - Pendaftaran Wajah)

Lihat `app/Services/FaceEnrollService.php`:

```php
use Aws\Rekognition\RekognitionClient;

public function enrollFace(User $user, string $imagePath): array
{
    // Baca gambar dari storage
    $imageBytes = Storage::get($imagePath);

    // Kirim ke AWS Rekognition untuk di-index
    $result = $this->rekognition->indexFaces([
        'CollectionId' => $this->collectionId,  // Database wajah di AWS
        'Image' => ['Bytes' => $imageBytes],
        'ExternalImageId' => $user->id . ':' . $user->name,
        'MaxFaces' => 1,
        'QualityFilter' => 'AUTO',  // AWS otomatis filter kualitas rendah
    ]);

    // Simpan face_id ke database lokal
    $faceId = $result['FaceRecords'][0]['Face']['FaceId'];
    FaceProfile::create([
        'user_id' => $user->id,
        'face_id' => $faceId,  // ID dari AWS
        'provider' => 'aws',
        'collection_id' => $this->collectionId,
    ]);

    return ['success' => true, 'face_id' => $faceId];
}
```

### 3. FaceVerificationService (AWS Rekognition - Verifikasi Wajah)

Lihat `app/Services/FaceVerificationService.php`:

```php
public function verifyFace(User $user, string $imageBytes): array
{
    // Cari wajah di collection AWS yang cocok dengan foto
    $result = $this->rekognition->searchFacesByImage([
        'CollectionId' => $this->collectionId,
        'Image' => ['Bytes' => $imageBytes],
        'MaxFaces' => 1,
        'FaceMatchThreshold' => 80,  // Minimal 80% similarity
    ]);

    $faceMatches = $result['FaceMatches'] ?? [];

    if (empty($faceMatches)) {
        return ['ok' => false, 'message' => 'Wajah tidak cocok'];
    }

    $matchedFaceId = $faceMatches[0]['Face']['FaceId'];
    $similarity = $faceMatches[0]['Similarity'];  // Score 0-100

    // Cek apakah face_id ini milik user yang sedang login
    $isOwner = $user->faceProfiles()->where('face_id', $matchedFaceId)->exists();

    return [
        'ok' => $isOwner,
        'score' => $similarity,
        'message' => $isOwner ? 'Wajah cocok' : 'Wajah adalah orang lain'
    ];
}
```

## AWS Rekognition - Face Recognition (Cloud)

### Konsep AWS Rekognition

AWS Rekognition adalah **managed service** yang sudah memiliki:

-   ✅ Model deep learning yang sudah dilatih dengan miliaran foto
-   ✅ Infrastructure yang scalable dan reliable
-   ✅ Automatic updates dan improvements
-   ✅ High accuracy tanpa perlu training manual

### Collection (Database Wajah)

Collection adalah "database" penyimpanan face embeddings di AWS:

```php
// Config di services.php
'rekognition' => [
    'collection' => 'absenki-staff',  // Nama collection
    'threshold' => 80,                 // Minimal similarity score
],
```

**Cara kerja:**

1. Saat pendaftaran → AWS extract face features → simpan di collection
2. Saat verifikasi → AWS compare dengan semua faces di collection
3. Return list faces yang mirip dengan similarity score

### API yang Digunakan

#### 1. IndexFaces (Pendaftaran)

```php
$result = $rekognition->indexFaces([
    'CollectionId' => 'absenki-staff',
    'Image' => ['Bytes' => $imageBytes],
    'ExternalImageId' => '123:John_Doe',  // ID eksternal untuk referensi
    'MaxFaces' => 1,
    'QualityFilter' => 'AUTO',
]);

// Response
[
    'FaceRecords' => [
        [
            'Face' => [
                'FaceId' => 'abcd-1234-efgh-5678',  // ID unik dari AWS
                'Confidence' => 99.99,
            ]
        ]
    ]
]
```

#### 2. SearchFacesByImage (Verifikasi)

```php
$result = $rekognition->searchFacesByImage([
    'CollectionId' => 'absenki-staff',
    'Image' => ['Bytes' => $imageBytes],
    'MaxFaces' => 1,
    'FaceMatchThreshold' => 80,
]);

// Response
[
    'FaceMatches' => [
        [
            'Similarity' => 99.5,
            'Face' => [
                'FaceId' => 'abcd-1234-efgh-5678',
                'ExternalImageId' => '123:John_Doe',
            ]
        ]
    ]
]
```

#### 3. DetectFaces (Deteksi Wajah)

```php
$result = $rekognition->detectFaces([
    'Image' => ['Bytes' => $imageBytes],
    'Attributes' => ['ALL'],  // Get face details
]);

// Response
[
    'FaceDetails' => [
        [
            'BoundingBox' => [
                'Left' => 0.3,    // Normalized coordinates
                'Top' => 0.2,
                'Width' => 0.4,
                'Height' => 0.5,
            ],
            'Confidence' => 99.99,
            'Emotions' => [...],
            'Quality' => ['Brightness' => 85, 'Sharpness' => 90],
        ]
    ]
]
```

### Kenapa Tidak Pakai Model Lokal?

| Aspek              | Model Lokal                       | AWS Rekognition                       |
| ------------------ | --------------------------------- | ------------------------------------- |
| **Training**       | Perlu training dengan ribuan foto | Tidak perlu, sudah trained            |
| **Maintenance**    | Update manual, retrain berkala    | Auto-update oleh AWS                  |
| **Accuracy**       | Tergantung dataset & tuning       | High accuracy (99%+)                  |
| **Infrastructure** | Perlu GPU, RAM besar              | Tinggal API call                      |
| **Scalability**    | Limited by server                 | Unlimited (cloud)                     |
| **Cost**           | Server + GPU mahal                | Pay per use (murah untuk scale kecil) |
| **Complexity**     | High (perlu ML expertise)         | Low (tinggal API call)                |

### Full Flow: Pendaftaran Wajah

```
1. User upload foto
   ↓
2. FaceProcessor::qualityCheck()
   → Python: opencv_quality.py (cek blur, brightness)
   → Return: ok/tidak
   ↓
3. FaceProcessor::cropFace() [Optional]
   → Python: opencv_face_crop.py (crop & resize)
   → Return: cropped image
   ↓
4. FaceEnrollService::enrollFace()
   → AWS: IndexFaces API
   → AWS ekstrak face features & simpan di collection
   → Return: FaceId (ID unik dari AWS)
   ↓
5. Simpan ke database lokal
   → Table: face_profiles
   → Columns: user_id, face_id (dari AWS), provider='aws'
```

### Full Flow: Verifikasi Wajah (Absensi)

```
1. User ambil foto selfie
   ↓
2. FaceProcessor::qualityCheck()
   → Python: opencv_quality.py
   → Return: ok/tidak
   ↓
3. FaceVerificationService::verifyFace()
   → AWS: DetectFaces (deteksi wajah, dapat bounding box)
   → AWS: SearchFacesByImage (cari wajah yang mirip)
   → AWS return: FaceId + Similarity score
   ↓
4. Cek apakah FaceId milik user yang login
   → Query: face_profiles.where('user_id', $user->id)->where('face_id', $matchedFaceId)
   ↓
5. Return hasil verifikasi
   → ok: true/false
   → score: 0-100
   → message: 'Wajah cocok' / 'Wajah tidak cocok'
```

## Cara Menambahkan Script Python Baru (Pre-processing)

### 1. Buat Script Python

**tools/my_script.py:**

```python
#!/usr/bin/env python3
import json
import argparse
import sys

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--input', required=True)
    args = parser.parse_args()

    try:
        # Your processing here
        result = process_data(args.input)

        # Return JSON
        print(json.dumps({
            "ok": True,
            "data": result
        }))
    except Exception as e:
        print(json.dumps({
            "ok": False,
            "message": str(e)
        }))
        sys.exit(1)

if __name__ == "__main__":
    main()
```

### 2. Panggil dari PHP

```php
use Symfony\Component\Process\Process;

public function myFunction($input)
{
    $pythonPath = base_path('.venv/Scripts/python.exe');
    if (!file_exists($pythonPath)) {
        $pythonPath = 'python';
    }

    $process = new Process([
        $pythonPath,
        base_path('tools/my_script.py'),
        '--input', $input
    ]);

    $process->setTimeout(10);
    $process->run();

    if (!$process->isSuccessful()) {
        \Log::error('Python script failed', [
            'error' => $process->getErrorOutput(),
            'output' => $process->getOutput()
        ]);
        return ['success' => false];
    }

    $result = json_decode($process->getOutput(), true);
    return $result;
}
```

## Troubleshooting

### Python tidak ditemukan

```bash
# Cek PATH
where python

# Install Python dari python.org
# Atau gunakan Laragon's Python
```

### OpenCV tidak terinstall

```bash
.\.venv\Scripts\activate
pip install opencv-python numpy
```

### Script error tapi tidak ada output

```php
// Tambahkan logging
\Log::info('Python output', [
    'stdout' => $process->getOutput(),
    'stderr' => $process->getErrorOutput(),
    'exitCode' => $process->getExitCode()
]);
```

### Permission denied (Linux)

```bash
chmod +x tools/*.py
```

### Timeout error

```php
// Tambah timeout untuk script yang lama
$process->setTimeout(30); // 30 detik
```

### AWS Rekognition Error

```
Error: "ResourceNotFoundException - The collection id: xxx does not exist"
```

**Solusi:** Buat collection dulu via AWS CLI atau Console

```bash
aws rekognition create-collection --collection-id absenki-staff --region ap-southeast-2
```

```
Error: "InvalidImageFormatException"
```

**Solusi:** Pastikan format image JPEG/PNG, maksimal 15MB

```
Error: "InvalidParameterException - There are no faces in the image"
```

**Solusi:** Quality check dulu dengan opencv_quality.py sebelum kirim ke AWS

## Best Practices

### Python Pre-processing

1. **Selalu gunakan JSON untuk output** - Mudah di-parse di PHP
2. **Handle error dengan baik** - Return status code dan message
3. **Set timeout yang masuk akal** - Hindari hanging process
4. **Log error untuk debugging** - Gunakan Laravel Log
5. **Virtual environment** - Isolasi dependencies Python
6. **Validasi input/output** - Cek file exists sebelum proses
7. **Gunakan try-catch** - Di PHP dan Python

### AWS Rekognition

1. **Quality check sebelum kirim ke AWS** - Hemat cost & improve accuracy
2. **Set FaceMatchThreshold yang tepat** - Default 80, sesuaikan kebutuhan
3. **Gunakan QualityFilter: AUTO** - AWS filter foto blur/gelap otomatis
4. **Monitor AWS usage** - Track jumlah API calls untuk billing
5. **Handle error spesifik** - Bedakan error dari AWS vs error lokal
6. **Cache hasil verification** - Jangan verify berkali-kali untuk foto yang sama
7. **Cleanup collection berkala** - Hapus faces yang tidak digunakan

## Alternatif Lain

### 1. REST API (Flask/FastAPI)

```python
# api.py
from flask import Flask, request, jsonify
app = Flask(__name__)

@app.route('/crop-face', methods=['POST'])
def crop_face():
    # Process image
    return jsonify({"ok": True, "data": result})
```

```php
// PHP
$response = Http::post('http://localhost:5000/crop-face', [
    'image' => base64_encode(file_get_contents($path))
]);
```

**Kelebihan:** Bisa scale, multiple request concurrent  
**Kekurangan:** Perlu manage service, port, deployment

### 2. Message Queue (Redis/RabbitMQ)

```php
// PHP - Push job
Redis::publish('face-processing', json_encode($job));
```

```python
# Python - Consume
redis.subscribe('face-processing')
for message in redis.listen():
    process_job(message)
```

**Kelebihan:** Async, bisa handle load tinggi  
**Kekurangan:** Lebih complex, perlu infrastructure

### 3. Direct exec() / shell_exec()

```php
$output = shell_exec("python tools/script.py --input $file 2>&1");
```

**Kelebihan:** Simple  
**Kekurangan:** Security risk, susah handle error, no timeout control

## Rekomendasi Arsitektur

### Arsitektur Saat Ini (Hybrid)

```
Python OpenCV (Pre-processing) + AWS Rekognition (Face Recognition)
```

**Kelebihan:**
✅ **No ML expertise needed** - AWS handle semua face recognition
✅ **High accuracy** - Model AWS sudah trained dengan miliaran foto
✅ **Scalable** - Cloud-based, tidak terbatas resource server
✅ **Low maintenance** - Tidak perlu retrain model atau update
✅ **Fast development** - Tinggal API call, bukan develop model dari nol
✅ **Pre-processing lokal** - Python crop & quality check cepat (no latency ke cloud)

**Kekurangan:**
⚠️ **Cost per API call** - Bayar per request ke AWS (tapi murah untuk scale kecil)
⚠️ **Internet dependency** - Perlu koneksi internet untuk verification
⚠️ **Privacy concern** - Data foto dikirim ke AWS (walau AWS compliant)

### Kapan Pakai Model Lokal?

Gunakan model lokal (TensorFlow/PyTorch) jika:

-   ❌ Tidak boleh kirim data ke cloud (privacy/security strict)
-   ❌ Tidak ada koneksi internet (offline system)
-   ❌ Volume sangat tinggi (ribuan request/detik) → AWS mahal
-   ❌ Perlu customize model untuk kasus spesifik

Untuk aplikasi AbsenKi (sistem absensi karyawan):
✅ **AWS Rekognition adalah pilihan TERBAIK**

-   Volume tidak terlalu tinggi (staff absen 2x sehari)
-   Butuh accuracy tinggi untuk keamanan
-   Tidak ada resource untuk maintain ML model
-   Privacy masih acceptable (data di AWS secure)

## Testing

### Test Python Script

```bash
# Aktivasi venv
.\.venv\Scripts\activate

# Test face crop
python tools/opencv_face_crop.py --image test.jpg --out output.jpg

# Test quality check
python tools/opencv_quality.py --image test.jpg
```

### Test dari PHP

```php
// Tinker
php artisan tinker

// Test Python pre-processing
$processor = app(\App\Services\FaceProcessor::class);
$result = $processor->qualityCheck(storage_path('app/test.jpg'));
dd($result);

// Test AWS enrollment
$enrollService = app(\App\Services\FaceEnrollService::class);
$user = User::find(1);
$result = $enrollService->enrollFace($user, 'faces/test.jpg');
dd($result);

// Test AWS verification
$verifyService = app(\App\Services\FaceVerificationService::class);
$imageBytes = Storage::get('faces/verify.jpg');
$result = $verifyService->verifyFace($user, $imageBytes);
dd($result);
```

## Monitoring & Performance

### Python Processing Time

```php
$startTime = microtime(true);

$process->run();

$executionTime = microtime(true) - $startTime;
\Log::info('Python execution time', ['time' => $executionTime]);
```

### AWS Rekognition Performance

```php
// Monitor AWS API calls
$startTime = microtime(true);

$result = $this->rekognition->searchFacesByImage([...]);

$awsTime = microtime(true) - $startTime;

\Log::info('AWS Rekognition API call', [
    'operation' => 'searchFacesByImage',
    'duration' => $awsTime,
    'matches' => count($result['FaceMatches'] ?? []),
]);
```

**Typical Performance:**

-   DetectFaces: ~200-500ms
-   SearchFacesByImage: ~300-700ms
-   IndexFaces: ~400-800ms

### Cost Monitoring

```php
// Track API usage untuk billing
\Log::info('AWS API Usage', [
    'operation' => 'IndexFaces',
    'user_id' => $user->id,
    'timestamp' => now(),
]);

// Aggregate per bulan untuk estimasi cost
// IndexFaces: $1 per 1000 images
// SearchFacesByImage: $1 per 1000 images
```

## Security

1. **Validasi input** - Jangan pass user input langsung ke command
2. **Sanitize path** - Gunakan `basename()` atau validasi path
3. **Limit file size** - Cek ukuran file sebelum process
4. **Timeout** - Selalu set timeout untuk avoid DOS
5. **Error message** - Jangan expose internal path ke user

```php
// Good
$filename = basename($userInput);
if (!preg_match('/^[a-zA-Z0-9_-]+\.(jpg|png)$/', $filename)) {
    throw new \Exception('Invalid filename');
}

// Bad
$process = new Process(['python', 'script.py', $_GET['file']]);
```

## Kesimpulan

### Ringkasan Arsitektur AbsenKi

```
┌─────────────────────────────────────────────────────────────┐
│                    APLIKASI ABSENKI                          │
│                  (Laravel + Livewire)                        │
└─────────────────────────────────────────────────────────────┘
                            │
            ┌───────────────┴───────────────┐
            │                               │
            ▼                               ▼
┌───────────────────────┐       ┌──────────────────────┐
│   PYTHON OPENCV       │       │   AWS REKOGNITION    │
│  (Pre-processing)     │       │  (Face Recognition)  │
├───────────────────────┤       ├──────────────────────┤
│ • Face Crop           │       │ • IndexFaces         │
│ • Quality Check       │       │ • SearchFaces        │
│ • Image Enhancement   │       │ • DetectFaces        │
│                       │       │                      │
│ ❌ BUKAN ML Model     │       │ ✅ ML Model AWS      │
│ ✅ Lokal Processing   │       │ ✅ Cloud Service     │
└───────────────────────┘       └──────────────────────┘
```

### Key Points

1. **Python OpenCV**

    - Hanya untuk **pre-processing gambar** (crop, quality check)
    - **TIDAK ada model ML** atau face recognition di Python
    - Run via Symfony Process dari PHP
    - Lokal, cepat, tidak butuh internet

2. **AWS Rekognition**

    - **Face recognition sepenuhnya di AWS**
    - Model sudah dilatih oleh AWS (tidak perlu training)
    - Cloud-based, scalable, high accuracy
    - API-based integration dari PHP

3. **Integration Method**
    - Python ↔ PHP: **Symfony Process** (command execution)
    - AWS ↔ PHP: **AWS SDK** (HTTP API calls)
    - Simple, maintainable, proven approach

### Kapan Menggunakan Apa

| Task                   | Tool            | Reason                      |
| ---------------------- | --------------- | --------------------------- |
| Crop gambar            | Python OpenCV   | Lokal, cepat, simpel        |
| Cek blur/brightness    | Python OpenCV   | Lokal, hemat cost AWS       |
| Daftar wajah baru      | AWS Rekognition | Need ML model (IndexFaces)  |
| Verifikasi wajah       | AWS Rekognition | Need ML model (SearchFaces) |
| Deteksi multiple faces | AWS Rekognition | Need ML model (DetectFaces) |

### Tidak Perlu Training Model

❌ **Tidak perlu:**

-   Kumpulin dataset ribuan foto
-   Training model dengan TensorFlow/PyTorch
-   Setup GPU server
-   Hire ML engineer
-   Retrain model berkala

✅ **Cukup:**

-   Panggil AWS API
-   Upload foto user
-   AWS yang handle semua ML processing
